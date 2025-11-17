<?php

declare(strict_types=1);

namespace App\Services;

use PDO;
use DateTime;
use Exception;
use DateInterval;
use App\Support\DB;
use App\Repositories\TareaRepository;
use App\Repositories\TareaNotificacionRepository;

final class RutinaSchedulerService
{
   public function __construct(
      private ?PDO $db = null,
      private ?TareaRepository $tareaRepo = null,
      private ?TareaNotificacionRepository $tnRepo = null,
   ) {
      $this->db        = $this->db        ?: DB::pdo();
      $this->tareaRepo = $this->tareaRepo ?: new TareaRepository($this->db);
      $this->tnRepo    = $this->tnRepo    ?: new TareaNotificacionRepository($this->db);
   }

   /**
    * Punto de entrada: procesa todas las rutinas activas y devuelve un resumen.
    */
   public function ejecutar(): array
   {
      $hoy = new DateTime('today');
      $result = [
         'ok'       => true,
         'fecha'    => $hoy->format('Y-m-d'),
         'creadas'  => 0,
         'omitidas' => 0,
         'errores'  => [],
      ];

      try {
         $rutinas = $this->listarRutinasActivas();

         foreach ($rutinas as $r) {
            try {
               $log = $this->procesarRutina($r, $hoy);
               if ($log['creada'] ?? false) {
                  $result['creadas']++;
               } else {
                  $result['omitidas']++;
               }
            } catch (Exception $e) {
               $result['errores'][] = [
                  'empresa_id'     => $r['empresa_id'] ?? null,
                  'empresa_obl_id' => $r['eo_id'] ?? null,
                  'mensaje'        => $e->getMessage(),
               ];
            }
         }
      } catch (Exception $e) {
         $result['ok'] = false;
         $result['errores'][] = ['mensaje' => $e->getMessage()];
      }

      return $result;
   }

   /**
    * Obtiene rutinas activas (empresa_obligacion + empresa + obligacion).
    */
   private function listarRutinasActivas(): array
   {
      $sql = "
            SELECT 
                eo.id                 AS eo_id,
                eo.empresa_id,
                eo.obligacion_id,
                eo.periodicidad,
                eo.tipo_dias,
                eo.dia_vencimiento,
                eo.offset_dias,
                eo.dias_anticipacion,
                eo.fecha_inicio,
                eo.fecha_fin,
                eo.responsable_id,
                eo.enviar_correo,
                eo.notas,
                e.nombre             AS empresa_nombre,
                e.rfc                AS empresa_rfc,
                e.responsable_id     AS empresa_responsable_id,
                e.area_id            AS empresa_area_id,
                o.clave              AS obligacion_clave,
                o.descripcion        AS obligacion_desc,
                o.organismo          AS obligacion_organismo
            FROM empresa_obligacion eo
            INNER JOIN empresa e    ON e.id = eo.empresa_id
            INNER JOIN obligacion o ON o.id = eo.obligacion_id
            WHERE eo.activo = 1
              AND (eo.fecha_inicio IS NULL OR eo.fecha_inicio <= CURDATE())
              AND (eo.fecha_fin IS NULL OR eo.fecha_fin >= CURDATE())
        ";

      $stmt = $this->db->query($sql);
      return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
   }

   /**
    * Procesa una rutina concreta para la fecha dada.
    */
   private function procesarRutina(array $r, DateTime $hoy): array
   {
      $periodicidad = $r['periodicidad'] ?? null;
      $diaVenc      = $r['dia_vencimiento'] !== null ? (int) $r['dia_vencimiento'] : null;
      $diasAnt      = $r['dias_anticipacion'] !== null ? (int) $r['dias_anticipacion'] : 5;
      $offsetDias   = $r['offset_dias'] !== null ? (int) $r['offset_dias'] : 0; // ignorado en vencimiento por ahora
      $tipoDias     = $r['tipo_dias'] ?? 'NATURALES';

      if (!$periodicidad || !$diaVenc) {
         return ['creada' => false, 'motivo' => 'config_incompleta'];
      }

      // 1) Periodo actual
      [$periodoInicio, $periodoFin, $periodoLabel] = $this->calcularPeriodo($hoy, $periodicidad);
      $piStr = $periodoInicio->format('Y-m-d');
      $pfStr = $periodoFin->format('Y-m-d');

      // 2) Fecha objetivo (día_base = dia_vencimiento)
      $fechaObjetivo = $this->calcularFechaObjetivo($periodoInicio, $periodoFin, $diaVenc);

      // 3) Fecha de creación de la tarea
      //    REGLA: fecha_creacion = fecha_objetivo - dias_anticipacion
      $fechaCreacion = clone $fechaObjetivo;
      if ($diasAnt > 0) {
         $fechaCreacion->sub(new DateInterval('P' . $diasAnt . 'D'));
      }
      // Si quisieras que también respete HABILES aquí, podríamos usar ajustarPorHabiles,
      // pero por ahora lo dejamos como NATURALES (tal como lo explicaste).
      $fcStr = $fechaCreacion->format('Y-m-d');

      // Solo creamos tarea si hoy coincide con la fecha de creación
      if ($fcStr !== $hoy->format('Y-m-d')) {
         return ['creada' => false, 'motivo' => 'hoy_no_corresponde'];
      }

      // 4) Evitar duplicado por periodo
      if ($this->tareaRepo->existsForPeriodo((int) $r['eo_id'], $piStr, $pfStr)) {
         return ['creada' => false, 'motivo' => 'ya_existe'];
      }

      // 5) Feriados + fecha de vencimiento
      //    REGLA: fecha_vencimiento = fecha_objetivo + N días
      //    donde N = último dígito numérico del RFC, luego se ajusta según tipo_dias.
      $feriados = $this->listarFeriados();
      $fechaVencimiento = $this->calcularFechaVencimiento(
         $r['empresa_rfc'] ?? '',
         $fechaObjetivo,
         $tipoDias,
         $offsetDias,
         $feriados
      );

      // 6) Título de la tarea
      $titulo = $this->formatearTituloTarea(
         $r['obligacion_organismo'] ?? '',
         $r['obligacion_desc'] ?? '',
         $periodoLabel
      );

      // 7) Responsable (de la rutina o de la empresa)
      $responsableId = $r['responsable_id'] ?: $r['empresa_responsable_id'];

      // 8) Crear tarea
      $tareaId = $this->tareaRepo->crear([
         'empresa_obligacion_id' => (int) $r['eo_id'],
         'tipo_tarea'            => 'OBLIGACION',
         'origen'                => 'AUTOMATICO',
         'empresa_id'            => (int) $r['empresa_id'],
         'periodo_inicio'        => $piStr,
         'periodo_fin'           => $pfStr,
         'fecha_objetivo'        => $fechaObjetivo->format('Y-m-d'),
         'fecha_vencimiento'     => $fechaVencimiento->format('Y-m-d'),
         'estado'                => 'PENDIENTE',
         'progreso'              => 0.00,
         'responsable_id'        => $responsableId,
         'titulo'                => $titulo,
         'observaciones'         => 'Tarea generada automáticamente por configuración de rutina.',
      ]);

      // 9) Crear notificación pendiente (si aplica)
      if ((int) $r['enviar_correo'] === 1) {
         $correoDest = $this->obtenerCorreoResponsable($responsableId);
         if ($correoDest) {
            $asunto = $this->formatearAsuntoCorreo($titulo, $fechaVencimiento);
            $this->tnRepo->crear([
               'tarea_id'        => $tareaId,
               'tipo'            => 'CREACION',
               'canal'           => 'EMAIL',
               'destinatario'    => $correoDest,
               'asunto'          => $asunto,
               'estado'          => 'PENDIENTE',
               'programada_para' => date('Y-m-d H:i:s'),
            ]);
         }
      }

      return ['creada' => true];
   }

   /**
    * Calcula el periodo (inicio, fin, etiqueta) según la periodicidad.
    */
   private function calcularPeriodo(DateTime $hoy, string $periodicidad): array
   {
      $periodicidad = strtoupper($periodicidad);
      $year  = (int) $hoy->format('Y');
      $month = (int) $hoy->format('m');

      switch ($periodicidad) {
         case 'MENSUAL':
            $inicio = new DateTime("$year-$month-01");
            $fin    = (clone $inicio)->modify('last day of this month');
            $label  = strtoupper($inicio->format('M')) . ' ' . $year;
            break;

         case 'BIMESTRAL':
            $pairStartMonth = $month - (($month - 1) % 2);
            $inicio = new DateTime("$year-$pairStartMonth-01");
            $fin    = (clone $inicio)->modify('+1 month')->modify('last day of this month');
            $label  = strtoupper($inicio->format('M')) . '–' . strtoupper($fin->format('M')) . " $year";
            break;

         case 'TRIMESTRAL':
            $trimIndex  = (int) floor(($month - 1) / 3);
            $startMonth = $trimIndex * 3 + 1;
            $inicio = new DateTime("$year-$startMonth-01");
            $fin    = (clone $inicio)->modify('+2 months')->modify('last day of this month');
            $label  = strtoupper($inicio->format('M')) . '–' . strtoupper($fin->format('M')) . " $year";
            break;

         case 'SEMESTRAL':
            $startMonth = ($month <= 6) ? 1 : 7;
            $inicio = new DateTime("$year-$startMonth-01");
            $fin    = (clone $inicio)->modify('+5 months')->modify('last day of this month');
            $label  = strtoupper($inicio->format('M')) . '–' . strtoupper($fin->format('M')) . " $year";
            break;

         case 'ANUAL':
            $inicio = new DateTime("$year-01-01");
            $fin    = new DateTime("$year-12-31");
            $label  = (string) $year;
            break;

         default: // EVENTUAL u otra
            $inicio = new DateTime("$year-$month-01");
            $fin    = (clone $inicio)->modify('last day of this month');
            $label  = strtoupper($inicio->format('M')) . " $year";
            break;
      }

      return [$inicio, $fin, $label];
   }

   /**
    * Calcula la fecha objetivo con base en el dia_vencimiento
    * dentro del último mes del periodo.
    */
   private function calcularFechaObjetivo(DateTime $periodoInicio, DateTime $periodoFin, int $diaObjetivo): DateTime
   {
      $year  = (int) $periodoFin->format('Y');
      $month = (int) $periodoFin->format('m');

      $fecha = new DateTime("$year-$month-01");
      $lastDay = (int) $fecha->format('t');

      if ($diaObjetivo > $lastDay) {
         $diaObjetivo = $lastDay;
      }

      $fecha->setDate($year, $month, $diaObjetivo);
      return $fecha;
   }

   /**
    * Recupera feriados como array de strings Y-m-d.
    */
   private function listarFeriados(): array
   {
      $sql = "SELECT fecha FROM feriado";
      $stmt = $this->db->query($sql);
      $rows = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
      return array_map('strval', $rows);
   }

   /**
    * Ajusta una fecha según tipo_dias.
    * NATURALES: no cambia.
    * HABILES: avanza hasta el siguiente día hábil (no sábado, no domingo, no feriado).
    */
   private function ajustarPorHabiles(DateTime $fecha, string $tipoDias, array $feriados): DateTime
   {
      $tipoDias = strtoupper($tipoDias);
      if ($tipoDias !== 'HABILES') {
         return $fecha;
      }

      $f = clone $fecha;
      while (true) {
         $dow  = (int) $f->format('N'); // 6=sáb, 7=dom
         $fStr = $f->format('Y-m-d');
         if ($dow >= 6 || in_array($fStr, $feriados, true)) {
            $f->modify('+1 day');
            continue;
         }
         break;
      }

      return $f;
   }

   /**
    * Calcula fecha de vencimiento en función de la fecha objetivo
    * y del último dígito numérico del RFC:
    *
    *   fecha_vencimiento = fecha_objetivo + N días
    *   N = último dígito numérico del RFC (0..9)
    *
    * Luego se ajusta según tipo_dias (HABILES/NATURALES).
    */
   private function calcularFechaVencimiento(
      string $rfc,
      DateTime $fechaObjetivo,
      string $tipoDias,
      int $offsetDias,
      array $feriados
   ): DateTime {
      $venc = clone $fechaObjetivo;

      // 1) sacar el último número del RFC (de derecha a izquierda)
      $ultimoDigito = $this->extraerUltimoDigitoRfc($rfc);

      // 2) sumar N días donde N = último dígito (si existe)
      if ($ultimoDigito !== null) {
         $diasExtra = $this->diasExtraPorRfc($ultimoDigito);
         if ($diasExtra !== 0) {
            $modifier = ($diasExtra > 0 ? '+' : '') . $diasExtra . ' days';
            $venc->modify($modifier);
         }
      }

      // 3) ajustar a días hábiles si corresponde
      return $this->ajustarPorHabiles($venc, $tipoDias, $feriados);
   }

   /**
    * Extrae el ÚLTIMO NÚMERO del RFC recorriendo de derecha a izquierda.
    * Ej:
    *   ABC010203AB3  -> 3
    *   ABC010203ABX  -> 3
    *   XYZ           -> null (no tiene dígitos)
    */
   private function extraerUltimoDigitoRfc(string $rfc): ?int
   {
      $rfc = trim($rfc);
      if ($rfc === '') {
         return null;
      }

      for ($i = strlen($rfc) - 1; $i >= 0; $i--) {
         $ch = $rfc[$i];
         if ($ch >= '0' && $ch <= '9') {
            return (int) $ch;
         }
      }

      return null;
   }

   /**
    * Días extra a sumar según último dígito del RFC.
    * Regla simple: N = dígito tal cual (0..9).
    */
   private function diasExtraPorRfc(int $ultimoDigito): int
   {
      return $ultimoDigito;
   }

   /**
    * Título de la tarea, ej: [SAT] Declaración IVA – ENE 2025
    */
   private function formatearTituloTarea(string $organismo, string $descripcion, string $periodoLabel): string
   {
      $organismo   = trim($organismo)   !== '' ? trim($organismo)   : 'SIN ORGANISMO';
      $descripcion = trim($descripcion) !== '' ? trim($descripcion) : 'Obligación';

      return sprintf('[%s] %s – %s', $organismo, $descripcion, $periodoLabel);
   }

   /**
    * Asunto para correo:
    *   Nueva tarea: [SAT] Declaración IVA – ENE 2025 (vence el 25/02)
    */
   private function formatearAsuntoCorreo(string $tituloTarea, DateTime $fechaVencimiento): string
   {
      $vence = $fechaVencimiento->format('d/m');
      return sprintf('Nueva tarea: %s (vence el %s)', $tituloTarea, $vence);
   }

   /**
    * Obtiene el correo del responsable.
    */
   private function obtenerCorreoResponsable(?int $responsableId): ?string
   {
      if (!$responsableId) {
         return null;
      }

      $sql = "SELECT email FROM usuario WHERE id = :id LIMIT 1";
      $stmt = $this->db->prepare($sql);
      $stmt->execute([':id' => $responsableId]);
      $correo = $stmt->fetchColumn();

      return $correo ? (string) $correo : null;
   }
}
