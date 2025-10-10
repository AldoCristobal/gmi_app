<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use App\Support\DB;

final class EmpresaRepository
{
   private PDO $db;
   public function __construct()
   {
      $this->db = DB::pdo();
   }

   /** WHERE + params según scope */
   private function scopeWhere(array $scope, array &$params): string
   {
      $w = ['e.activo = 1'];
      if (!empty($scope['direccion'])) {
         // Dirección ve todo
         return implode(' AND ', $w);
      }
      if (!empty($scope['gerencia'])) {
         $w[] = 'e.area_id = :area_id';
         $params[':area_id'] = (int)$scope['area_id'];
         return implode(' AND ', $w);
      }
      if (!empty($scope['auxiliar'])) {
         $w[] = 'e.responsable_id = :uid';
         $params[':uid'] = (int)$scope['user_id'];
         return implode(' AND ', $w);
      }
      // Por defecto: restringir por área (seguro)
      if (!empty($scope['area_id'])) {
         $w[] = 'e.area_id = :area_id';
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
         $where .= " AND (e.cliente_grupo LIKE :q OR e.nombre LIKE :q OR e.rfc LIKE :q)";
      }
      if (isset($filters['area_id'])) {
         $params[':f_area'] = (int)$filters['area_id'];
         $where .= " AND e.area_id = :f_area";
      }
      if (isset($filters['responsable_id'])) {
         $params[':f_resp'] = (int)$filters['responsable_id'];
         $where .= " AND e.responsable_id = :f_resp";
      }
      if (isset($filters['activo'])) {
         $params[':f_act'] = (int)$filters['activo'];
         $where .= " AND e.activo = :f_act";
      }

      $offset = ($page - 1) * $size;

      // total
      $stc = $this->db->prepare("SELECT COUNT(*) FROM empresa e WHERE {$where}");
      $stc->execute($params);
      $total = (int)$stc->fetchColumn();

      // data
      $sql = "
        SELECT
           e.id, e.cliente_grupo, e.nombre, e.rfc, e.tipo_persona,
           e.area_id, e.responsable_id, e.activo,
           e.contrato_servicios, e.nombre_facturacion, e.correo_facturacion,
           e.telefono_facturacion, e.tipo_regimen, e.actividad_principal, e.estatus_domicilio
         FROM empresa e
        WHERE {$where}
        ORDER BY e.nombre ASC
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
      $where = 'e.id = :id AND ' . $this->scopeWhere($scope, $params);
      $st = $this->db->prepare("
         SELECT e.*
         FROM empresa e
         WHERE {$where}
         LIMIT 1
      ");
      $st->execute($params);
      $r = $st->fetch(PDO::FETCH_ASSOC);
      return $r ?: null;
   }

   public function rfcExists(string $rfc, ?int $excludeId = null): bool
   {
      if ($excludeId) {
         $st = $this->db->prepare("SELECT 1 FROM empresa WHERE rfc=:rfc AND id<>:id LIMIT 1");
         $st->execute([':rfc' => $rfc, ':id' => $excludeId]);
      } else {
         $st = $this->db->prepare("SELECT 1 FROM empresa WHERE rfc=:rfc LIMIT 1");
         $st->execute([':rfc' => $rfc]);
      }
      return (bool)$st->fetchColumn();
   }

   public function create(array $d): int
   {
      $st = $this->db->prepare("
        INSERT INTO empresa(
          cliente_grupo,nombre,rfc,tipo_persona,
          contrato_servicios,nombre_facturacion,telefono_facturacion,correo_facturacion,
          tipo_regimen,actividad_principal,estatus_domicilio,
          area_id,responsable_id,activo
        ) VALUES (
          :cliente_grupo,:nombre,:rfc,:tipo_persona,
          :contrato_servicios,:nombre_facturacion,:telefono_facturacion,:correo_facturacion,
          :tipo_regimen,:actividad_principal,:estatus_domicilio,
          :area_id,:responsable_id,:activo
        )
      ");
      $st->execute($d);
      return (int)$this->db->lastInsertId();
   }

   public function update(int $id, array $set): bool
   {
      if (!$set) return true;
      $fields = [];
      $params = [':id' => $id];
      foreach ($set as $k => $v) {
         $fields[] = "$k = :$k";
         $params[":$k"] = $v;
      }
      $sql = "UPDATE empresa SET " . implode(',', $fields) . " WHERE id=:id";
      return $this->db->prepare($sql)->execute($params);
   }

   public function softDelete(int $id): bool
   {
      return $this->db->prepare("UPDATE empresa SET activo=0 WHERE id=:id")->execute([':id' => $id]);
   }

   // ---------- Expediente ----------
   public function tiposDocActivos(): array
   {
      $st = $this->db->query("SELECT id, clave, nombre, requerido_pf, requerido_pm, multiple, acepta_ext, max_mb FROM empresa_documento_tipo WHERE activo=1 ORDER BY nombre");
      return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
   }

   public function docsUltimaVersion(int $empresaId): array
   {
      $q = $this->db->prepare("
        SELECT ed.*
        FROM empresa_documento ed
        JOIN (
          SELECT tipo_id, MAX(version) max_ver
          FROM empresa_documento
          WHERE empresa_id=:eid
          GROUP BY tipo_id
        ) t ON t.tipo_id=ed.tipo_id AND t.max_ver=ed.version
        WHERE ed.empresa_id=:eid
        ORDER BY ed.tipo_id
      ");
      $q->execute([':eid' => $empresaId]);
      return $q->fetchAll(PDO::FETCH_ASSOC) ?: [];
   }

   public function resolverTipo(?int $tipoId, ?string $tipoClave): ?array
   {
      if ($tipoClave) {
         $st = $this->db->prepare("SELECT id,clave,acepta_ext,max_mb FROM empresa_documento_tipo WHERE clave=:c AND activo=1");
         $st->execute([':c' => $tipoClave]);
      } else {
         $st = $this->db->prepare("SELECT id,clave,acepta_ext,max_mb FROM empresa_documento_tipo WHERE id=:i AND activo=1");
         $st->execute([':i' => $tipoId]);
      }
      $r = $st->fetch(PDO::FETCH_ASSOC);
      return $r ?: null;
   }

   public function nextVersion(int $empresaId, int $tipoId): int
   {
      $nv = $this->db->prepare("SELECT COALESCE(MAX(version),0)+1 FROM empresa_documento WHERE empresa_id=:e AND tipo_id=:t");
      $nv->execute([':e' => $empresaId, ':t' => $tipoId]);
      return (int)$nv->fetchColumn();
   }

   public function insertDoc(array $d): int
   {
      $st = $this->db->prepare("
        INSERT INTO empresa_documento
          (empresa_id,tipo_id,version,archivo_nombre,archivo_path,mime,size_bytes,metadata,subido_por)
        VALUES
          (:empresa_id,:tipo_id,:version,:archivo_nombre,:archivo_path,:mime,:size_bytes,:metadata,:subido_por)
      ");
      $st->execute($d);
      return (int)$this->db->lastInsertId();
   }

   public function docDeEmpresa(int $empresaId, int $docId): ?array
   {
      $st = $this->db->prepare("
        SELECT ed.id, ed.archivo_path, ed.metadata, edt.clave
        FROM empresa_documento ed
        JOIN empresa_documento_tipo edt ON edt.id=ed.tipo_id
        WHERE ed.id=:d AND ed.empresa_id=:e
      ");
      $st->execute([':d' => $docId, ':e' => $empresaId]);
      $r = $st->fetch(PDO::FETCH_ASSOC);
      return $r ?: null;
   }

   public function setDocMetadata(int $docId, array $meta): bool
   {
      $st = $this->db->prepare("UPDATE empresa_documento SET metadata=:m WHERE id=:d");
      return $st->execute([':m' => json_encode($meta, JSON_UNESCAPED_UNICODE), ':d' => $docId]);
   }

   public function empresaMin(int $id): ?array
   {
      $st = $this->db->prepare("SELECT id, rfc, nombre, tipo_persona, origen_captura, constancia_doc_id FROM empresa WHERE id=:id");
      $st->execute([':id' => $id]);
      $r = $st->fetch(PDO::FETCH_ASSOC);
      return $r ?: null;
   }

   public function docsPorTipo(int $empresaId, int $tipoId): array
   {
      $st = $this->db->prepare("
        SELECT
          id, empresa_id, tipo_id, version,
          archivo_nombre, archivo_path, mime, size_bytes, metadata,
          subido_por, subido_en AS creado_en
        FROM empresa_documento
        WHERE empresa_id = :e AND tipo_id = :t
        ORDER BY version DESC
    ");
      $st->execute([':e' => $empresaId, ':t' => $tipoId]);
      return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
   }

   public function ultimosPorTipo(int $empresaId, int $tipoId, int $limit = 5): array
   {
      $st = $this->db->prepare("
        SELECT
          id, empresa_id, tipo_id, version,
          archivo_nombre, archivo_path, mime, size_bytes, metadata,
          subido_por, subido_en AS creado_en
        FROM empresa_documento
        WHERE empresa_id = :e AND tipo_id = :t
        ORDER BY version DESC
        LIMIT :lim
    ");
      $st->bindValue(':e', $empresaId, PDO::PARAM_INT);
      $st->bindValue(':t', $tipoId, PDO::PARAM_INT);
      $st->bindValue(':lim', $limit, PDO::PARAM_INT);
      $st->execute();
      return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
   }


   public function docPath(int $empresaId, int $docId): ?array
   {
      $st = $this->db->prepare("
         SELECT ed.id, ed.empresa_id, ed.archivo_nombre, ed.archivo_path, ed.mime
         FROM empresa_documento ed
         WHERE ed.id=:d AND ed.empresa_id=:e
         LIMIT 1
      ");
      $st->execute([':d' => $docId, ':e' => $empresaId]);
      $r = $st->fetch(PDO::FETCH_ASSOC);
      return $r ?: null;
   }
}
