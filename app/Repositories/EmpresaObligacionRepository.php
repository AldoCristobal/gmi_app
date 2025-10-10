<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class EmpresaObligacionRepository
{
   private PDO $db;

   public function __construct()
   {
      $this->db = \App\Support\DB::pdo();
   }

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

      // Estructura amigable para el front (anidar obligacion)
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

   public function existsAsignacion(int $empresaId, int $obligacionId): bool
   {
      $st = $this->db->prepare("SELECT id FROM empresa_obligacion WHERE empresa_id = :e AND obligacion_id = :o LIMIT 1");
      $st->execute([':e' => $empresaId, ':o' => $obligacionId]);
      return (bool)$st->fetch(PDO::FETCH_ASSOC);
   }

   public function insert(array $in): int
   {
      $sql = "
            INSERT INTO empresa_obligacion
            (empresa_id, obligacion_id, periodicidad, tipo_dias, dia_vencimiento, offset_dias,
             fecha_inicio, fecha_fin, responsable_id, area_id, activo, notas)
            VALUES
            (:empresa_id, :obligacion_id, :periodicidad, :tipo_dias, :dia_vencimiento, :offset_dias,
             :fecha_inicio, :fecha_fin, :responsable_id, :area_id, :activo, :notas)
        ";
      $st = $this->db->prepare($sql);
      $st->execute([
         ':empresa_id'     => (int)$in['empresa_id'],
         ':obligacion_id'  => (int)$in['obligacion_id'],
         ':periodicidad'   => (string)$in['periodicidad'],
         ':tipo_dias'      => (string)$in['tipo_dias'],
         ':dia_vencimiento' => $in['periodicidad'] === 'EVENTUAL' ? null : ($in['dia_vencimiento'] ?? null),
         ':offset_dias'    => (int)($in['offset_dias'] ?? 0),
         ':fecha_inicio'   => (string)$in['fecha_inicio'],
         ':fecha_fin'      => $in['fecha_fin'] ?: null,
         ':responsable_id' => $in['responsable_id'] ?: null,
         ':area_id'        => $in['area_id'] ?: null,
         ':activo'         => (int)($in['activo'] ?? 1),
         ':notas'          => $in['notas'] ?? null,
      ]);
      return (int)$this->db->lastInsertId();
   }

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
                activo = :activo,
                notas = :notas
            WHERE id = :id
            LIMIT 1
        ";
      $st = $this->db->prepare($sql);
      $st->execute([
         ':periodicidad'   => (string)$in['periodicidad'],
         ':tipo_dias'      => (string)$in['tipo_dias'],
         ':dia_vencimiento' => $in['periodicidad'] === 'EVENTUAL' ? null : ($in['dia_vencimiento'] ?? null),
         ':offset_dias'    => (int)($in['offset_dias'] ?? 0),
         ':fecha_inicio'   => (string)$in['fecha_inicio'],
         ':fecha_fin'      => $in['fecha_fin'] ?: null,
         ':responsable_id' => $in['responsable_id'] ?: null,
         ':area_id'        => $in['area_id'] ?: null,
         ':activo'         => (int)($in['activo'] ?? 1),
         ':notas'          => $in['notas'] ?? null,
         ':id'             => (int)$in['id'],
      ]);
      return $st->rowCount() > 0;
   }

   public function delete(int $id): bool
   {
      // Si prefieres baja lógica, cambia por UPDATE activo=0
      $st = $this->db->prepare("DELETE FROM empresa_obligacion WHERE id = :id LIMIT 1");
      $st->execute([':id' => $id]);
      return $st->rowCount() > 0;
   }
}
