<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use App\Support\DB;

final class EmpresaRepository
{
   private PDO $db;

   public function __construct(?PDO $db = null)
   {
      $this->db = $db ?? DB::pdo();
   }

   /**
    * Genera el fragmento de WHERE según scope (sin incluir la palabra WHERE).
    * Devuelve algo como:
    *   "e.activo = 1 AND e.borrado_en IS NULL AND (e.area_id = :sc_area_id OR ...)"
    */
   private function scopeWhere(array $scope, array &$params): string
   {
      // Base: solo empresas activas y no borradas
      $conds = [
         'e.activo = 1'
      ];

      // Datos básicos de scope
      $areaId = $scope['area_id'] ?? null;
      $userId = $scope['user_id'] ?? null;

      // Scopes "nuevos"
      $viewAll  = $scope['view_all']  ?? null;
      $viewArea = $scope['view_area'] ?? null;
      $viewTeam = $scope['view_team'] ?? null;
      $viewMine = $scope['view_mine'] ?? null;

      // Team IDs (si el Service los calculó)
      $teamUserIds = $scope['team_user_ids'] ?? null;
      if (!is_array($teamUserIds)) {
         $teamUserIds = [];
      }

      $hasNewScopes =
         !is_null($viewAll) ||
         !is_null($viewArea) ||
         !is_null($viewTeam) ||
         !is_null($viewMine);

      // ------------------------------------------------------------------
      // A) MODO NUEVO: usamos view_all / view_area / view_team / view_mine
      // ------------------------------------------------------------------
      if ($hasNewScopes) {

         // Dirección / "ver todo"
         if ($viewAll) {
            // Solo condiciones base
            return implode(' AND ', $conds);
         }

         $ors = [];

         // VER ÁREA
         if ($viewArea && $areaId) {
            $ors[] = 'e.area_id = :sc_area_id';
            $params[':sc_area_id'] = (int)$areaId;
         }

         // VER EQUIPO (lista de responsables en su equipo)
         if ($viewTeam && !empty($teamUserIds)) {
            $phs = [];
            foreach ($teamUserIds as $idx => $uid) {
               $ph = ':sc_team_' . $idx;
               $phs[] = $ph;
               $params[$ph] = (int)$uid;
            }

            if ($phs) {
               $ors[] = 'e.responsable_id IN (' . implode(',', $phs) . ')';
            }
         }

         // VER MÍAS
         if ($viewMine && $userId) {
            $ors[] = 'e.responsable_id = :sc_user_id';
            $params[':sc_user_id'] = (int)$userId;
         }

         if (!empty($ors)) {
            $conds[] = '(' . implode(' OR ', $ors) . ')';
         }

         return implode(' AND ', $conds);
      }
      // Si por alguna razón no matchea nada, dejamos solo la base
      return implode(' AND ', $conds);
   }

   public function list(array $filters, array $scope, int $page, int $size): array
   {
      // 1) Scope base + parámetros del scope
      $params    = [];
      $whereExpr = $this->scopeWhere($scope, $params);

      // 2) Filtros adicionales (vienen desde el Service)
      if (!empty($filters['q'])) {
         $params[':q'] = '%' . $filters['q'] . '%';
         $whereExpr   .= " AND (e.cliente_grupo LIKE :q OR e.nombre LIKE :q OR e.rfc LIKE :q)";
      }

      if (!empty($filters['area_id'])) {
         $params[':f_area'] = (int)$filters['area_id'];
         $whereExpr        .= " AND e.area_id = :f_area";
      }

      if (!empty($filters['responsable_id'])) {
         $params[':f_resp'] = (int)$filters['responsable_id'];
         $whereExpr        .= " AND e.responsable_id = :f_resp";
      }

      // OJO: aquí usamos isset para permitir 0/1
      if (isset($filters['activo'])) {
         $params[':f_act'] = (int)$filters['activo'];
         $whereExpr       .= " AND e.activo = :f_act";
      }

      $page   = max(1, (int)$page);
      $size   = max(1, (int)$size);
      $offset = ($page - 1) * $size;

      // =======================
      // 3) TOTAL (COUNT)
      // =======================
      $sqlCount = "SELECT COUNT(*) FROM empresa e WHERE {$whereExpr}";
      $stc = $this->db->prepare($sqlCount);
      $stc->execute($params);
      $total = (int)$stc->fetchColumn();

      // =======================
      // 4) DATA (SELECT)
      // =======================
      $sql = "
      SELECT
         e.id,
         e.cliente_grupo,
         e.nombre,
         e.rfc,
         e.tipo_persona,
         e.area_id,
         e.responsable_id,
         e.activo,
         e.contrato_servicios,
         e.nombre_facturacion,
         e.correo_facturacion,
         e.telefono_facturacion,
         e.tipo_regimen,
         e.actividad_principal,
         e.estatus_domicilio
      FROM empresa e
      WHERE {$whereExpr}
      ORDER BY e.nombre ASC
      LIMIT :lim OFFSET :off
   ";

      // Misma base de params + paginación, igual que en expedientePanelList
      $paramsData = $params;
      $paramsData[':lim'] = (int)$size;
      $paramsData[':off'] = (int)$offset;

      $st = $this->db->prepare($sql);
      $st->execute($paramsData);
      $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

      return ['rows' => $rows, 'total' => $total];
   }


   public function findVisible(int $id, array $scope): ?array
   {
      $params    = [':id' => $id];
      $whereExpr = 'e.id = :id AND ' . $this->scopeWhere($scope, $params);

      $st = $this->db->prepare("
      SELECT
         e.*,
         a.nombre AS area_nombre,
         u.nombre AS responsable_nombre
      FROM empresa e
      LEFT JOIN area a    ON a.id = e.area_id
      LEFT JOIN usuario u ON u.id = e.responsable_id
      WHERE {$whereExpr}
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
         $fields[]      = "$k = :$k";
         $params[":$k"] = $v;
      }

      $sql = "UPDATE empresa SET " . implode(',', $fields) . " WHERE id=:id";
      return $this->db->prepare($sql)->execute($params);
   }

   public function softDelete(int $id): bool
   {
      return $this->db
         ->prepare("UPDATE empresa SET activo=0 WHERE id=:id")
         ->execute([':id' => $id]);
   }

   // ---------- Expediente ----------
   public function tiposDocActivos(): array
   {
      $st = $this->db->query("
         SELECT id, clave, nombre, requerido_pf, requerido_pm, multiple, acepta_ext, max_mb
         FROM empresa_documento_tipo
         WHERE activo=1
         ORDER BY nombre
      ");
      return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
   }

   public function docsUltimaVersion(int $empresaId): array
   {
      $sql = "
      SELECT ed.*
      FROM empresa_documento ed
      JOIN (
         SELECT tipo_id, MAX(version) AS max_ver
         FROM empresa_documento
         WHERE empresa_id = :eid1
         GROUP BY tipo_id
      ) t
         ON t.tipo_id = ed.tipo_id
        AND t.max_ver = ed.version
      WHERE ed.empresa_id = :eid2
      ORDER BY ed.tipo_id
   ";

      $st = $this->db->prepare($sql);
      $st->execute([
         ':eid1' => $empresaId,
         ':eid2' => $empresaId,
      ]);

      return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
   }


   public function resolverTipo(?int $tipoId, ?string $tipoClave): ?array
   {
      if ($tipoClave) {
         $st = $this->db->prepare("
            SELECT id,clave,acepta_ext,max_mb
            FROM empresa_documento_tipo
            WHERE clave=:c AND activo=1
         ");
         $st->execute([':c' => $tipoClave]);
      } else {
         $st = $this->db->prepare("
            SELECT id,clave,acepta_ext,max_mb
            FROM empresa_documento_tipo
            WHERE id=:i AND activo=1
         ");
         $st->execute([':i' => $tipoId]);
      }
      $r = $st->fetch(PDO::FETCH_ASSOC);
      return $r ?: null;
   }

   public function nextVersion(int $empresaId, int $tipoId): int
   {
      $nv = $this->db->prepare("
         SELECT COALESCE(MAX(version),0)+1
         FROM empresa_documento
         WHERE empresa_id=:e AND tipo_id=:t
      ");
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
      $st = $this->db->prepare("
         UPDATE empresa_documento
         SET metadata=:m
         WHERE id=:d
      ");
      return $st->execute([
         ':m' => json_encode($meta, JSON_UNESCAPED_UNICODE),
         ':d' => $docId
      ]);
   }

   public function empresaMin(int $id): ?array
   {
      $st = $this->db->prepare("
         SELECT id, rfc, nombre, tipo_persona, origen_captura, constancia_doc_id
         FROM empresa
         WHERE id=:id
      ");
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

   public function findById(int $id): ?array
   {
      $sql  = "SELECT * FROM empresa WHERE id = :id LIMIT 1";
      $stmt = $this->db->prepare($sql);
      $stmt->execute(['id' => $id]);
      $row = $stmt->fetch(PDO::FETCH_ASSOC);

      return $row ?: null;
   }

   public function listForCatalog(array $filters, array $scope): array
   {
      $params    = [];
      $whereExpr = $this->scopeWhere($scope, $params);

      if (!empty($filters['q'])) {
         $params[':q'] = '%' . $filters['q'] . '%';
         $whereExpr   .= " AND (e.cliente_grupo LIKE :q OR e.nombre LIKE :q OR e.rfc LIKE :q)";
      }
      if (isset($filters['area_id'])) {
         $params[':f_area'] = (int)$filters['area_id'];
         $whereExpr        .= " AND e.area_id = :f_area";
      }
      if (isset($filters['responsable_id'])) {
         $params[':f_resp'] = (int)$filters['responsable_id'];
         $whereExpr        .= " AND e.responsable_id = :f_resp";
      }

      $sql = "
      SELECT
         e.id,
         e.nombre,
         e.area_id,
         e.responsable_id
      FROM empresa e
      WHERE {$whereExpr}
      ORDER BY e.nombre ASC
   ";

      $st = $this->db->prepare($sql);
      $st->execute($params);

      return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
   }

   /**
    * Jefes visibles (usuarios responsables) derivados de las empresas visibles por scope.
    *
    * @param array  $scope
    * @param string $search (opcional) filtro por nombre
    * @return array<int,array<string,mixed>>
    */
   public function jefesDesdeEmpresasConScope(array $scope, string $search = ''): array
   {
      $params    = [];
      $whereExpr = $this->scopeWhere($scope, $params);

      // Aseguramos que sólo usuarios activos
      $whereExpr = '(' . $whereExpr . ') AND u.activo = 1';

      if ($search !== '') {
         $params[':uq'] = '%' . $search . '%';
         $whereExpr    .= " AND u.nombre LIKE :uq";
      }

      $sql = "
      SELECT DISTINCT
         u.id,
         u.nombre
      FROM empresa e
      INNER JOIN usuario u ON u.id = e.responsable_id
      WHERE {$whereExpr}
      ORDER BY u.nombre ASC
   ";

      $st = $this->db->prepare($sql);
      $st->execute($params);

      return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
   }

   public function expedientePanelList(array $filters, array $scope, int $page, int $size): array
   {
      // 1) Scope base + parámetros del scope
      $params    = [];
      $whereExpr = $this->scopeWhere($scope, $params);

      // 2) Filtros adicionales
      if (!empty($filters['q'])) {
         $params[':q'] = '%' . $filters['q'] . '%';
         $whereExpr   .= " AND (e.cliente_grupo LIKE :q OR e.nombre LIKE :q OR e.rfc LIKE :q)";
      }

      if (!empty($filters['area_id'])) {
         $params[':f_area'] = (int)$filters['area_id'];
         $whereExpr        .= " AND e.area_id = :f_area";
      }

      if (!empty($filters['responsable_id'])) {
         $params[':f_resp'] = (int)$filters['responsable_id'];
         $whereExpr        .= " AND e.responsable_id = :f_resp";
      }

      if (!empty($filters['solo_con_docs'])) {
         $whereExpr .= " AND COALESCE(d.docs_count, 0) > 0";
      }

      $page   = max(1, (int)$page);
      $size   = max(1, (int)$size);
      $offset = ($page - 1) * $size;

      // ---------------- TOTAL ----------------
      $sqlCount = "
      SELECT COUNT(*)
      FROM empresa e
      LEFT JOIN (
         SELECT empresa_id, COUNT(*) AS docs_count, MAX(subido_en) AS last_doc_at
         FROM empresa_documento
         GROUP BY empresa_id
      ) d ON d.empresa_id = e.id
      WHERE {$whereExpr}
   ";

      $stc = $this->db->prepare($sqlCount);
      // Aquí se pueden pasar parámetros de más sin problema
      $stc->execute($params);
      $total = (int)$stc->fetchColumn();

      // ---------------- DATA ----------------
      $sql = "
      SELECT
         e.id,
         e.nombre,
         e.rfc,
         e.cliente_grupo,
         e.tipo_persona,
         e.contrato_servicios,
         e.actividad_principal,
         e.estatus_domicilio,
         e.area_id,
         a.nombre AS area_nombre,
         e.responsable_id,
         u.nombre AS responsable_nombre,
         COALESCE(d.docs_count, 0) AS docs_count,
         d.last_doc_at
      FROM empresa e
      LEFT JOIN (
         SELECT empresa_id, COUNT(*) AS docs_count, MAX(subido_en) AS last_doc_at
         FROM empresa_documento
         GROUP BY empresa_id
      ) d ON d.empresa_id = e.id
      LEFT JOIN area a ON a.id = e.area_id
      LEFT JOIN usuario u ON u.id = e.responsable_id
      WHERE {$whereExpr}
      ORDER BY e.nombre ASC
      LIMIT :lim OFFSET :off
   ";

      // Misma base de params + los de paginación
      $paramsData = $params;
      $paramsData[':lim'] = (int)$size;
      $paramsData[':off'] = (int)$offset;

      $st = $this->db->prepare($sql);
      $st->execute($paramsData);
      $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

      return ['rows' => $rows, 'total' => $total];
   }



   // NUEVO: versión para el panel, sin tocar la que usas en el módulo original
   public function docsUltimaVersionPanel(int $empresaId): array
   {
      $sql = "
         SELECT ed.*
         FROM empresa_documento ed
         INNER JOIN (
            SELECT empresa_id, tipo_id, MAX(version) AS max_ver
            FROM empresa_documento
            GROUP BY empresa_id, tipo_id
         ) t
           ON t.empresa_id = ed.empresa_id
          AND t.tipo_id    = ed.tipo_id
          AND t.max_ver    = ed.version
         WHERE ed.empresa_id = :empresa_id
         ORDER BY ed.tipo_id
      ";

      $st = $this->db->prepare($sql);
      $st->bindValue(':empresa_id', $empresaId, PDO::PARAM_INT);
      $st->execute();

      return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
   }
}
