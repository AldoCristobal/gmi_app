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
      $this->db = $db ?? DB::pdo();
   }

   /**
    * WHERE + params según scope (sin incluir la palabra WHERE).
    *
    * Nuevo esquema:
    *   - view_all  => ve todo
    *   - view_area => por área (e.area_id)
    *   - view_team => por equipo (ids en scope.team_user_ids → t.responsable_id)
    *   - view_mine => lo suyo (t.responsable_id = user_id)
    *
    * Fallback legacy:
    *   - direccion / gerencia / auxiliar
    */
   private function scopeWhere(array $scope, array &$params): string
   {
      $userId = isset($scope['user_id']) ? (int)$scope['user_id'] : 0;
      $areaId = isset($scope['area_id']) ? (int)$scope['area_id'] : 0;

      // ----- Nuevo mundo: view_* -----
      $viewAll  = $scope['view_all']  ?? null;
      $viewArea = $scope['view_area'] ?? null;
      $viewTeam = $scope['view_team'] ?? null;
      $viewMine = $scope['view_mine'] ?? null;

      $teamUserIds = $scope['team_user_ids'] ?? null;
      if (!is_array($teamUserIds)) {
         $teamUserIds = [];
      }

      $hasNewScopes = !is_null($viewAll) || !is_null($viewArea) || !is_null($viewTeam) || !is_null($viewMine);

      if ($hasNewScopes) {
         // Dirección / ver todo
         if (!empty($viewAll)) {
            return '1=1';
         }

         $ors = [];

         // Área
         if (!empty($viewArea) && $areaId > 0) {
            $ors[] = 'e.area_id = :sc_area_id';
            $params[':sc_area_id'] = $areaId;
         }

         // Equipo (ids ya calculados en el Service)
         if (!empty($viewTeam) && !empty($teamUserIds)) {
            $phs = [];
            foreach ($teamUserIds as $idx => $uid) {
               $ph = ':sc_team_' . $idx;
               $phs[] = $ph;
               $params[$ph] = (int)$uid;
            }
            if ($phs) {
               $ors[] = 't.responsable_id IN (' . implode(',', $phs) . ')';
            }
         }

         // Mías
         if (!empty($viewMine) && $userId > 0) {
            $ors[] = 't.responsable_id = :sc_user_id';
            $params[':sc_user_id'] = $userId;
         }

         if (!empty($ors)) {
            return '(' . implode(' OR ', $ors) . ')';
         }

         // Fallback: si algo quedó raro pero hay userId, al menos “mías”
         if ($userId > 0) {
            $params[':sc_user_id'] = $userId;
            return 't.responsable_id = :sc_user_id';
         }

         // Nada visible
         return '0=1';
      }

      return '0=1';
   }

   /**
    * Verifica si ya existe una tarea para esa empresa_obligacion y periodo.
    */
   public function existsForPeriod(int $empresaObligacionId, string $periodoInicio, string $periodoFin): bool
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
   public function create(array $data): int
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

   /**
    * Detalle sin aplicar scope (uso interno).
    */
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

      $row = $stmt->fetch(PDO::FETCH_ASSOC);
      return $row ?: null;
   }

   /**
    * Detalle respetando el scope.
    */
   public function findVisible(int $id, array $scope): ?array
   {
      $params = [':id' => $id];
      $where  = 't.id = :id AND ' . $this->scopeWhere($scope, $params);

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
            CASE WHEN EXISTS (
               SELECT 1
               FROM tarea_documento td
               WHERE td.tarea_id = t.id
            ) THEN 1 ELSE 0 END AS tiene_evidencia
         FROM tarea t
         LEFT JOIN empresa e ON e.id = t.empresa_id
         LEFT JOIN usuario u ON u.id = t.responsable_id
         WHERE {$where}
         LIMIT 1
      ";

      $stmt = $this->db->prepare($sql);
      $stmt->execute($params);

      $row = $stmt->fetch(PDO::FETCH_ASSOC);
      return $row ?: null;
   }

   /**
    * Lista tareas aplicando filtros + scope.
    *
    * filtros soportados:
    *  - estado
    *  - tipo_tarea
    *  - empresa_id
    *  - area_id
    *  - responsables (int[])
    *  - responsable_id
    */
   public function list(array $filtros, array $scope): array
   {
      $params = [];
      $where  = $this->scopeWhere($scope, $params);

      if (!empty($filtros['estado'])) {
         $where .= " AND t.estado = :estado";
         $params[':estado'] = $filtros['estado'];
      }

      if (!empty($filtros['tipo_tarea'])) {
         $where .= " AND t.tipo_tarea = :tipo_tarea";
         $params[':tipo_tarea'] = $filtros['tipo_tarea'];
      }

      if (!empty($filtros['empresa_id'])) {
         $where .= " AND t.empresa_id = :empresa_id";
         $params[':empresa_id'] = (int)$filtros['empresa_id'];
      }

      if (!empty($filtros['area_id'])) {
         $where .= " AND e.area_id = :area_id";
         $params[':area_id'] = (int)$filtros['area_id'];
      }

      // Equipo: varios responsables
      if (!empty($filtros['responsables']) && is_array($filtros['responsables'])) {
         $ids = array_values(array_unique(array_map('intval', $filtros['responsables'])));
         if (!empty($ids)) {
            $placeholders = [];
            foreach ($ids as $idx => $id) {
               $ph = ':resp' . $idx;
               $placeholders[] = $ph;
               $params[$ph] = $id;
            }
            $where .= " AND t.responsable_id IN (" . implode(',', $placeholders) . ")";
         }
      } elseif (!empty($filtros['responsable_id'])) {
         $where .= " AND t.responsable_id = :responsable_id";
         $params[':responsable_id'] = (int)$filtros['responsable_id'];
      }

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
            t.observaciones,
            u.nombre AS responsable_nombre,
            CASE WHEN EXISTS (
               SELECT 1
               FROM tarea_documento td
               WHERE td.tarea_id = t.id
            ) THEN 1 ELSE 0 END AS tiene_evidencia
         FROM tarea t
         LEFT JOIN empresa e ON e.id = t.empresa_id
         LEFT JOIN usuario u ON u.id = t.responsable_id
         WHERE {$where}
         ORDER BY t.fecha_vencimiento ASC, t.id DESC
      ";

      $stmt = $this->db->prepare($sql);
      $stmt->execute($params);

      return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
   }

   public function changeStatus(int $id, string $estado, ?string $obs = null): bool
   {
      $sql = "UPDATE tarea SET estado = :estado, actualizado_en = NOW()";
      $params = [
         ':estado' => $estado,
         ':id'     => $id,
      ];

      if ($obs !== null && $obs !== '') {
         $sql .= ", observaciones = :obs";
         $params[':obs'] = $obs;
      }

      $sql .= " WHERE id = :id";

      $stmt = $this->db->prepare($sql);
      return $stmt->execute($params);
   }

   /**
    * Update general fields for a task (used mainly for EXTRAORDINARIA).
    */
   public function update(int $id, array $data): bool
   {
      $sql = "
         UPDATE tarea
         SET
            empresa_id        = :empresa_id,
            titulo            = :titulo,
            periodo_inicio    = :periodo_inicio,
            periodo_fin       = :periodo_fin,
            fecha_objetivo    = :fecha_objetivo,
            fecha_vencimiento = :fecha_vencimiento,
            responsable_id    = :responsable_id,
            observaciones     = :observaciones,
            actualizado_en    = NOW()
         WHERE id = :id
      ";

      $stmt = $this->db->prepare($sql);
      return $stmt->execute([
         ':empresa_id'        => $data['empresa_id'],
         ':titulo'            => $data['titulo'],
         ':periodo_inicio'    => $data['periodo_inicio'],
         ':periodo_fin'       => $data['periodo_fin'],
         ':fecha_objetivo'    => $data['fecha_objetivo'],
         ':fecha_vencimiento' => $data['fecha_vencimiento'],
         ':responsable_id'    => $data['responsable_id'],
         ':observaciones'     => $data['observaciones'] ?? null,
         ':id'                => $id,
      ]);
   }

   /**
    * Hard delete for a task (only used for EXTRAORDINARIA from admin module).
    */
   public function delete(int $id): bool
   {
      $stmt = $this->db->prepare("DELETE FROM tarea WHERE id = :id");
      return $stmt->execute([':id' => $id]);
   }
}
