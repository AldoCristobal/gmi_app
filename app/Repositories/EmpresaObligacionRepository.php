<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use PDOException;

final class EmpresaObligacionRepository
{
   private PDO $db;

   public function __construct()
   {
      $this->db = \App\Support\DB::pdo();
   }

   /** Lista con detalle para la empresa (como ya usabas en el front) */
   public function listByEmpresa(int $empresaId): array
   {
      $sql = "
         SELECT eo.*,
                o.id   AS obligacion_id2,
                o.clave,
                o.descripcion,
                o.organismo,
                o.activo AS obligacion_activa
         FROM empresa_obligacion eo
         INNER JOIN obligacion o ON o.id = eo.obligacion_id
         WHERE eo.empresa_id = :emp
         ORDER BY o.organismo ASC, o.descripcion ASC
      ";
      $st = $this->db->prepare($sql);
      $st->execute([':emp' => $empresaId]);
      $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

      foreach ($rows as &$r) {
         $r['obligacion'] = [
            'id'          => (int)$r['obligacion_id'],
            'clave'       => $r['clave'],
            'descripcion' => $r['descripcion'],
            'organismo'   => $r['organismo'],
            'activo'      => (int)$r['obligacion_activa'],
         ];
         unset($r['obligacion_id2'], $r['clave'], $r['descripcion'], $r['organismo'], $r['obligacion_activa']);
      }
      return $rows;
   }

   /** IDs actualmente asignados a la empresa */
   public function getIdsAsignadas(int $empresaId): array
   {
      $st = $this->db->prepare("SELECT obligacion_id FROM empresa_obligacion WHERE empresa_id = :e");
      $st->execute([':e' => $empresaId]);
      $ids = $st->fetchAll(PDO::FETCH_COLUMN, 0) ?: [];
      return array_map('intval', $ids);
   }

   /** Inserta un registro puntual (flujo de alta individual) */
   public function insert(array $in): int
   {
      $sql = "
         INSERT INTO empresa_obligacion
            (empresa_id, obligacion_id, periodicidad, tipo_dias, dia_vencimiento, offset_dias,
             fecha_inicio, fecha_fin, responsable_id, area_id, activo)
         VALUES
            (:empresa_id, :obligacion_id, :periodicidad, :tipo_dias, :dia_vencimiento, :offset_dias,
             :fecha_inicio, :fecha_fin, :responsable_id, :area_id, :activo)
      ";
      $st = $this->db->prepare($sql);
      $st->execute([
         ':empresa_id'      => (int)$in['empresa_id'],
         ':obligacion_id'   => (int)$in['obligacion_id'],
         ':periodicidad'    => (string)$in['periodicidad'],
         ':tipo_dias'       => (string)$in['tipo_dias'],
         ':dia_vencimiento' => $in['periodicidad'] === 'EVENTUAL' ? null : ($in['dia_vencimiento'] ?? null),
         ':offset_dias'     => (int)($in['offset_dias'] ?? 0),
         ':fecha_inicio'    => (string)$in['fecha_inicio'],
         ':fecha_fin'       => $in['fecha_fin'] ?: null,
         ':responsable_id'  => $in['responsable_id'] ?: null,
         ':area_id'         => $in['area_id'] ?: null,
         ':activo'          => (int)($in['activo'] ?? 1),
      ]);
      return (int)$this->db->lastInsertId();
   }

   /** Actualiza un registro puntual (flujo de edición individual) */
   public function update(array $in): bool
   {
      $sql = "
         UPDATE empresa_obligacion
            SET periodicidad = :periodicidad,
                tipo_dias = :tipo_dias,
                dia_vencimiento = :dia_vencimiento,
                offset_dias = :offset_dias,
                fecha_inicio = :fecha_inicio,
                fecha_fin = :fecha_fin,
                responsable_id = :responsable_id,
                area_id = :area_id,
                activo = :activo
         WHERE id = :id
         LIMIT 1
      ";
      $st = $this->db->prepare($sql);
      $st->execute([
         ':periodicidad'    => (string)$in['periodicidad'],
         ':tipo_dias'       => (string)$in['tipo_dias'],
         ':dia_vencimiento' => $in['periodicidad'] === 'EVENTUAL' ? null : ($in['dia_vencimiento'] ?? null),
         ':offset_dias'     => (int)($in['offset_dias'] ?? 0),
         ':fecha_inicio'    => (string)$in['fecha_inicio'],
         ':fecha_fin'       => $in['fecha_fin'] ?: null,
         ':responsable_id'  => $in['responsable_id'] ?: null,
         ':area_id'         => $in['area_id'] ?: null,
         ':activo'          => (int)($in['activo'] ?? 1),
         ':id'              => (int)$in['id'],
      ]);
      return $st->rowCount() > 0;
   }

   /** Baja (si prefieres baja lógica, cámbiala aquí) */
   public function delete(int $id): bool
   {
      $st = $this->db->prepare("DELETE FROM empresa_obligacion WHERE id = :id LIMIT 1");
      $st->execute([':id' => $id]);
      return $st->rowCount() > 0;
   }

   /** Inserta masivamente con defaults mínimos */
   public function bulkInsertDefaults(int $empresaId, array $obligacionIds): void
   {
      if (empty($obligacionIds)) return;

      $sql = "
         INSERT INTO empresa_obligacion
            (empresa_id, obligacion_id, periodicidad, tipo_dias, dia_vencimiento, offset_dias,
             fecha_inicio, fecha_fin, responsable_id, area_id, activo)
         VALUES
            (:empresa_id, :obligacion_id, 'EVENTUAL', 'NATURALES', NULL, 0, :fecha_inicio, NULL, NULL, NULL, 1)
      ";
      $st = $this->db->prepare($sql);
      $today = date('Y-m-d');

      foreach ($obligacionIds as $oid) {
         $st->execute([
            ':empresa_id'    => $empresaId,
            ':obligacion_id' => (int)$oid,
            ':fecha_inicio'  => $today,
         ]);
      }
   }

   /** Elimina por empresa + conjunto de obligacion_id */
   public function bulkDeleteByIds(int $empresaId, array $obligacionIds): void
   {
      if (empty($obligacionIds)) return;
      $in  = implode(',', array_fill(0, count($obligacionIds), '?'));
      $sql = "DELETE FROM empresa_obligacion WHERE empresa_id = ? AND obligacion_id IN ($in)";
      $st  = $this->db->prepare($sql);
      $params = array_merge([$empresaId], array_map('intval', $obligacionIds));
      $st->execute($params);
   }

   /**
    * Sincroniza en transacción: inserta faltantes y elimina las que sobran.
    * Devuelve el arreglo final de IDs asignados.
    */
   public function syncForEmpresa(int $empresaId, array $nuevosIds): array
   {
      $nuevos = array_values(array_unique(array_map('intval', $nuevosIds)));
      $this->db->beginTransaction();
      try {
         $existentes = $this->getIdsAsignadas($empresaId);
         $toAdd = array_values(array_diff($nuevos, $existentes));
         $toDel = array_values(array_diff($existentes, $nuevos));

         if (!empty($toDel)) $this->bulkDeleteByIds($empresaId, $toDel);
         if (!empty($toAdd)) $this->bulkInsertDefaults($empresaId, $toAdd);

         $this->db->commit();
      } catch (PDOException $e) {
         if ($this->db->inTransaction()) $this->db->rollBack();
         throw $e;
      }
      return $this->getIdsAsignadas($empresaId);
   }
}
