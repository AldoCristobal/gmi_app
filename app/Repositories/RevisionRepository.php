<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use App\Support\DB;

final class RevisionRepository
{
   private PDO $db;
   public function __construct()
   {
      $this->db = DB::pdo();
   }

   /** WHERE + params según scope (direccion/gerencia/auxiliar) */
   private function scopeWhere(array $scope, array &$params): string
   {
      $w = ['1=1'];
      if (!empty($scope['direccion'])) {
         return implode(' AND ', $w);
      }
      if (!empty($scope['gerencia'])) {
         $w[] = 'r.area_id = :area_id';
         $params[':area_id'] = (int)$scope['area_id'];
         return implode(' AND ', $w);
      }
      if (!empty($scope['auxiliar'])) {
         $w[] = 'r.responsable_id = :uid';
         $params[':uid'] = (int)$scope['user_id'];
         return implode(' AND ', $w);
      }
      if (!empty($scope['area_id'])) {
         $w[] = 'r.area_id = :area_id';
         $params[':area_id'] = (int)$scope['area_id'];
      }
      return implode(' AND ', $w);
   }

   /** Listado con filtros + paginación */
   public function list(array $filters, array $scope, int $page, int $size): array
   {
      $params = [];
      $where = $this->scopeWhere($scope, $params);

      if (!empty($filters['q'])) {
         $params[':q'] = '%' . $filters['q'] . '%';
         $where .= " AND (r.nombre LIKE :q OR r.numero_orden LIKE :q OR r.numero_oficio LIKE :q OR r.dependencia LIKE :q OR r.tipo_impuesto LIKE :q)";
      }
      if (isset($filters['tipo_revision_id'])) {
         $params[':f_tipo'] = (int)$filters['tipo_revision_id'];
         $where .= " AND r.tipo_revision_id = :f_tipo";
      }
      if (isset($filters['estatus'])) {
         $params[':f_est'] = $filters['estatus'];
         $where .= " AND r.estatus = :f_est";
      }
      if (isset($filters['riesgo'])) {
         $params[':f_ries'] = $filters['riesgo'];
         $where .= " AND r.riesgo = :f_ries";
      }
      if (isset($filters['area_id'])) {
         $params[':f_area'] = (int)$filters['area_id'];
         $where .= " AND r.area_id = :f_area";
      }
      if (isset($filters['responsable_id'])) {
         $params[':f_resp'] = (int)$filters['responsable_id'];
         $where .= " AND r.responsable_id = :f_resp";
      }
      if (isset($filters['fecha_notificacion_desde'])) {
         $params[':fnd'] = $filters['fecha_notificacion_desde'];
         $where .= " AND r.fecha_notificacion >= :fnd";
      }
      if (isset($filters['fecha_notificacion_hasta'])) {
         $params[':fnh'] = $filters['fecha_notificacion_hasta'];
         $where .= " AND r.fecha_notificacion <= :fnh";
      }
      if (isset($filters['fecha_venc_desde'])) {
         $params[':fvd'] = $filters['fecha_venc_desde'];
         $where .= " AND r.fecha_vencimiento >= :fvd";
      }
      if (isset($filters['fecha_venc_hasta'])) {
         $params[':fvh'] = $filters['fecha_venc_hasta'];
         $where .= " AND r.fecha_vencimiento <= :fvh";
      }

      $offset = ($page - 1) * $size;

      // total
      $stc = $this->db->prepare("SELECT COUNT(*) FROM revision r WHERE {$where}");
      $stc->execute($params);
      $total = (int)$stc->fetchColumn();

      // data
      $sql = "
        SELECT r.*,
               rt.nombre AS tipo_revision,
               DATEDIFF(r.fecha_vencimiento, CURRENT_DATE()) AS dias_restantes
        FROM revision r
        JOIN revision_tipo rt ON rt.id = r.tipo_revision_id
        WHERE {$where}
        ORDER BY r.fecha_vencimiento ASC, r.id DESC
        LIMIT :lim OFFSET :off
      ";
      $std = $this->db->prepare($sql);
      foreach ($params as $k => $v) $std->bindValue($k, $v);
      $std->bindValue(':lim', $size, PDO::PARAM_INT);
      $std->bindValue(':off', $offset, PDO::PARAM_INT);
      $std->execute();
      $rows = $std->fetchAll(PDO::FETCH_ASSOC) ?: [];

      return ['rows' => $rows, 'total' => $total];
   }

   public function findVisible(int $id, array $scope): ?array
   {
      $params = [':id' => $id];
      $where = 'r.id = :id AND ' . $this->scopeWhere($scope, $params);
      $st = $this->db->prepare("
         SELECT r.*,
                rt.nombre AS tipo_revision,
                DATEDIFF(r.fecha_vencimiento, CURRENT_DATE()) AS dias_restantes
         FROM revision r
         JOIN revision_tipo rt ON rt.id = r.tipo_revision_id
         WHERE {$where}
         LIMIT 1
      ");
      $st->execute($params);
      $r = $st->fetch(PDO::FETCH_ASSOC);
      return $r ?: null;
   }

   public function create(array $d): int
   {
      $st = $this->db->prepare("
        INSERT INTO revision(
          nombre,numero_orden,numero_oficio,ejercicio,
          fecha_notificacion,fecha_vencimiento,
          tipo_revision_id,tipo_impuesto,dependencia,antecedente,
          estatus,riesgo,observaciones,
          area_id,responsable_id,created_by
        ) VALUES (
          :nombre,:numero_orden,:numero_oficio,:ejercicio,
          :fecha_notificacion,:fecha_vencimiento,
          :tipo_revision_id,:tipo_impuesto,:dependencia,:antecedente,
          :estatus,:riesgo,:observaciones,
          :area_id,:responsable_id,:created_by
        )
      ");
      $st->execute($d);
      return (int)$this->db->lastInsertId();
   }

   public function update(int $id, array $set): bool
   {
      if (!$set) return true;
      unset($set['id']);
      $fields = [];
      $params = [':id' => $id];
      foreach ($set as $k => $v) {
         $fields[] = "$k = :$k";
         $params[":$k"] = $v;
      }
      $sql = "UPDATE revision SET " . implode(',', $fields) . ", updated_at=CURRENT_TIMESTAMP WHERE id=:id";
      return $this->db->prepare($sql)->execute($params);
   }

   public function delete(int $id): bool
   {
      return $this->db->prepare("DELETE FROM revision WHERE id=:id")->execute([':id' => $id]);
   }

   // ---------- Documentos ----------
   public function nextVersion(int $revisionId): int
   {
      $nv = $this->db->prepare("SELECT COALESCE(MAX(version),1)+1 FROM revision_documento WHERE revision_id=:r");
      $nv->execute([':r' => $revisionId]);
      $n = (int)$nv->fetchColumn();
      return max(2, $n); // versión 1 se reserva al inicial
   }

   public function insertDoc(array $d): int
   {
      $st = $this->db->prepare("
        INSERT INTO revision_documento
          (revision_id,version,is_inicial,nombre_original,archivo_path,mime,size_bytes,uploaded_by)
        VALUES
          (:revision_id,:version,:is_inicial,:nombre_original,:archivo_path,:mime,:size_bytes,:uploaded_by)
      ");
      $st->execute($d);
      return (int)$this->db->lastInsertId();
   }

   public function findDoc(int $docId): ?array
   {
      $st = $this->db->prepare("SELECT * FROM revision_documento WHERE id=:d");
      $st->execute([':d' => $docId]);
      $r = $st->fetch(PDO::FETCH_ASSOC);
      return $r ?: null;
   }

   public function deleteDoc(int $docId): bool
   {
      return $this->db->prepare("DELETE FROM revision_documento WHERE id=:d")->execute([':d' => $docId]);
   }

   public function docsDeRevision(int $revisionId): array
   {
      $st = $this->db->prepare("
        SELECT id, revision_id, version, is_inicial, nombre_original, archivo_path, mime, size_bytes, uploaded_by, created_at
        FROM revision_documento
        WHERE revision_id=:r
        ORDER BY is_inicial DESC, version ASC, id ASC
      ");
      $st->execute([':r' => $revisionId]);
      return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
   }

   public function findInitial(int $revisionId): ?array
   {
      $st = $this->db->prepare("SELECT * FROM revision_documento WHERE revision_id=:r AND is_inicial=1 LIMIT 1");
      $st->execute([':r' => $revisionId]);
      $r = $st->fetch(PDO::FETCH_ASSOC);
      return $r ?: null;
   }

   public function docPath(int $revisionId, int $docId): ?array
   {
      $st = $this->db->prepare("
        SELECT
            rd.id,
            rd.revision_id,
            rd.is_inicial,
            rd.version,
            rd.nombre_original AS archivo_nombre,
            rd.archivo_path,
            rd.mime,
            rd.size_bytes,
            rd.uploaded_by,
            rd.created_at AS creado_en
        FROM revision_documento rd
        WHERE rd.id = :doc_id AND rd.revision_id = :rev_id
        LIMIT 1
    ");
      $st->execute([':doc_id' => $docId, ':rev_id' => $revisionId]);
      $r = $st->fetch(\PDO::FETCH_ASSOC);
      return $r ?: null;
   }


   // ---------- Bitácora ----------
   public function bitacoraAppend(int $revisionId, string $evento, ?array $detalle, ?int $actorId): bool
   {
      $st = $this->db->prepare("INSERT INTO revision_bitacora (revision_id,evento,detalle,actor_id) VALUES (:r,:e,:d,:a)");
      return $st->execute([
         ':r' => $revisionId,
         ':e' => $evento,
         ':d' => $detalle ? json_encode($detalle, JSON_UNESCAPED_UNICODE) : null,
         ':a' => $actorId
      ]);
   }

   public function bitacoraList(int $revisionId, int $limit = 50, int $offset = 0): array
   {
      $st = $this->db->prepare("
        SELECT id, evento, detalle, actor_id, created_at
        FROM revision_bitacora
        WHERE revision_id=:r
        ORDER BY id DESC
        LIMIT :lim OFFSET :off
      ");
      $st->bindValue(':r', $revisionId, PDO::PARAM_INT);
      $st->bindValue(':lim', $limit, PDO::PARAM_INT);
      $st->bindValue(':off', $offset, PDO::PARAM_INT);
      $st->execute();
      return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
   }

   public function nextDocVersion(int $revisionId): int
   {
      $st = $this->db->prepare("SELECT COALESCE(MAX(version), 0) + 1 AS nv
                              FROM revision_documento
                              WHERE revision_id = :rid");
      $st->execute([':rid' => $revisionId]);
      return (int)$st->fetchColumn();
   }


   public function findRevisionesVencenEl(string $yyyy_mm_dd): array
   {
      $sql = "
         SELECT r.id, r.nombre, r.ejercicio, r.fecha_vencimiento,
               r.tipo_impuesto, r.dependencia, r.riesgo,
               u.email AS responsable_email
         FROM revision r
         JOIN usuario u ON u.id = r.responsable_id
         WHERE r.estatus = 'en_proceso'
         AND DATE(r.fecha_vencimiento) = :fecha
      ";
      $st = $this->db->prepare($sql); // <- aquí estaba el problema
      $st->execute([':fecha' => $yyyy_mm_dd]);
      return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
   }
}
