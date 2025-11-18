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
         t.id,
         t.tipo_tarea,
         t.origen,
         t.empresa_id,
         e.nombre AS empresa_nombre,
         t.titulo,
         t.estado,
         t.periodo_inicio,
         t.periodo_fin,
         t.fecha_objetivo,
         t.fecha_vencimiento,
         t.responsable_id,
         u.nombre AS responsable_nombre,

         -- ¿Tiene al menos una evidencia?
         CASE WHEN EXISTS (
            SELECT 1
            FROM tarea_documento td
            WHERE td.tarea_id = t.id
         ) THEN 1 ELSE 0 END AS tiene_evidencia

      FROM tarea t
      LEFT JOIN empresa e ON e.id = t.empresa_id
      LEFT JOIN usuario u ON u.id = t.responsable_id
      WHERE t.id = :id
      LIMIT 1
   ";

      $stmt = $this->db->prepare($sql);
      $stmt->execute([':id' => $id]);

      $row = $stmt->fetch(\PDO::FETCH_ASSOC);
      return $row ?: null;
   }



   public function listar(array $filtros): array
   {
      $sql = "
      SELECT
         t.id,
         t.tipo_tarea,
         t.origen,
         t.empresa_id,
         e.nombre AS empresa_nombre,
         t.titulo,
         t.estado,
         t.fecha_objetivo,
         t.fecha_vencimiento,
         t.responsable_id,
         u.nombre AS responsable_nombre,
         -- ¿Tiene al menos una evidencia?
         CASE WHEN EXISTS (
            SELECT 1
            FROM tarea_documento td
            WHERE td.tarea_id = t.id
         ) THEN 1 ELSE 0 END AS tiene_evidencia
      FROM tarea t
      LEFT JOIN empresa e ON e.id = t.empresa_id
      LEFT JOIN usuario u ON u.id = t.responsable_id
      WHERE 1=1
   ";

      $params = [];

      if (!empty($filtros['estado'])) {
         $sql .= " AND t.estado = :estado";
         $params[':estado'] = $filtros['estado'];
      }

      if (!empty($filtros['tipo_tarea'])) {
         $sql .= " AND t.tipo_tarea = :tipo_tarea";
         $params[':tipo_tarea'] = $filtros['tipo_tarea'];
      }

      if (!empty($filtros['empresa_id'])) {
         $sql .= " AND t.empresa_id = :empresa_id";
         $params[':empresa_id'] = (int)$filtros['empresa_id'];
      }

      if (!empty($filtros['responsable_id'])) {
         $sql .= " AND t.responsable_id = :responsable_id";
         $params[':responsable_id'] = (int)$filtros['responsable_id'];
      }

      // Filtros por fecha objetivo
      if (!empty($filtros['fec_obj_desde'])) {
         $sql .= " AND t.fecha_objetivo >= :fobj_desde";
         $params[':fobj_desde'] = $filtros['fec_obj_desde'];
      }
      if (!empty($filtros['fec_obj_hasta'])) {
         $sql .= " AND t.fecha_objetivo <= :fobj_hasta";
         $params[':fobj_hasta'] = $filtros['fec_obj_hasta'];
      }

      // Filtros por fecha de vencimiento
      if (!empty($filtros['fec_venc_desde'])) {
         $sql .= " AND t.fecha_vencimiento >= :fvenc_desde";
         $params[':fvenc_desde'] = $filtros['fec_venc_desde'];
      }
      if (!empty($filtros['fec_venc_hasta'])) {
         $sql .= " AND t.fecha_vencimiento <= :fvenc_hasta";
         $params[':fvenc_hasta'] = $filtros['fec_venc_hasta'];
      }

      $sql .= " ORDER BY t.fecha_vencimiento ASC, t.id DESC";

      $stmt = $this->db->prepare($sql);
      $stmt->execute($params);

      return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
   }


   public function cambiarEstado(int $id, string $estado, ?string $obs = null): bool
   {
      // Armamos el SQL dinámicamente para no tener placeholders "huérfanos"
      $sql = "UPDATE tarea SET estado = :estado, actualizado_en = NOW()";

      $params = [
         ':estado' => $estado,
         ':id'     => $id,
      ];

      // Si quieres que al cambiar de estado se registre observación:
      if ($obs !== null && $obs !== '') {
         $sql .= ", observaciones = :obs";
         $params[':obs'] = $obs;
      }

      $sql .= " WHERE id = :id";

      $stmt = $this->db->prepare($sql);
      return $stmt->execute($params);
   }
}
