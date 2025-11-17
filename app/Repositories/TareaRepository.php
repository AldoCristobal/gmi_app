<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use App\Support\DB;

class TareaRepository
{
   private PDO $db;

   public function __construct(?PDO $db = null)
   {
      // Ajusta al helper real que ya usas en el proyecto
      $this->db = DB::pdo();
   }

   /**
    * Verifica si ya existe una tarea para esa empresa_obligacion y periodo.
    */
   public function existsForPeriodo(int $empresaObligacionId, string $periodoInicio, string $periodoFin): bool
   {
      $sql = "
            SELECT 1
            FROM tarea
            WHERE empresa_obligacion_id = :eo
              AND periodo_inicio = :pi
              AND periodo_fin   = :pf
            LIMIT 1
        ";

      $stmt = $this->db->prepare($sql);
      $stmt->execute([
         ':eo' => $empresaObligacionId,
         ':pi' => $periodoInicio,
         ':pf' => $periodoFin,
      ]);

      return (bool) $stmt->fetchColumn();
   }

   /**
    * Crea una tarea y devuelve el ID insertado.
    */
   public function crear(array $data): int
   {
      $sql = "
            INSERT INTO tarea (
                empresa_obligacion_id,
                tipo_tarea,
                origen,
                empresa_id,
                periodo_inicio,
                periodo_fin,
                fecha_objetivo,
                fecha_vencimiento,
                estado,
                progreso,
                responsable_id,
                titulo,
                observaciones
            ) VALUES (
                :empresa_obligacion_id,
                :tipo_tarea,
                :origen,
                :empresa_id,
                :periodo_inicio,
                :periodo_fin,
                :fecha_objetivo,
                :fecha_vencimiento,
                :estado,
                :progreso,
                :responsable_id,
                :titulo,
                :observaciones
            )
        ";

      $stmt = $this->db->prepare($sql);
      $stmt->execute([
         ':empresa_obligacion_id' => $data['empresa_obligacion_id'],
         ':tipo_tarea'            => $data['tipo_tarea'] ?? 'OBLIGACION',
         ':origen'                => $data['origen'] ?? 'AUTOMATICO',
         ':empresa_id'            => $data['empresa_id'],
         ':periodo_inicio'        => $data['periodo_inicio'],
         ':periodo_fin'           => $data['periodo_fin'],
         ':fecha_objetivo'        => $data['fecha_objetivo'],
         ':fecha_vencimiento'     => $data['fecha_vencimiento'],
         ':estado'                => $data['estado'] ?? 'PENDIENTE',
         ':progreso'              => $data['progreso'] ?? 0.00,
         ':responsable_id'        => $data['responsable_id'],
         ':titulo'                => $data['titulo'],
         ':observaciones'         => $data['observaciones'] ?? null,
      ]);

      return (int) $this->db->lastInsertId();
   }

   public function findById(int $id): ?array
   {
      $sql = "
        SELECT 
            t.*,
            e.nombre AS empresa_nombre,
            o.descripcion AS obligacion_desc,
            o.organismo AS obligacion_organismo
        FROM tarea t
        LEFT JOIN empresa e ON e.id = t.empresa_id
        LEFT JOIN empresa_obligacion eo ON eo.id = t.empresa_obligacion_id
        LEFT JOIN obligacion o ON o.id = eo.obligacion_id
        WHERE t.id = :id
        LIMIT 1
    ";

      $stmt = $this->db->prepare($sql);
      $stmt->execute([':id' => $id]);

      $row = $stmt->fetch(PDO::FETCH_ASSOC);

      return $row ?: null;
   }
}
