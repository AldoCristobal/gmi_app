<?php

declare(strict_types=1);

namespace App\Controllers;

use PDO;
use App\Support\DB;
use App\Http\Response;

final class CatalogosController
{
   public function areas(): void
   {
      $st = DB::pdo()->query("SELECT id, nombre FROM area WHERE activo=1 ORDER BY nombre");
      Response::json(['ok' => true, 'data' => $st->fetchAll(PDO::FETCH_ASSOC)]);
   }
   public function jefes(): void
   {
      // todos los usuarios activos para seleccionar como jefe (ajusta si tienes columna es_jefe)
      $st = DB::pdo()->query("SELECT id, nombre FROM usuario WHERE activo=1 ORDER BY nombre");
      Response::json(['ok' => true, 'data' => $st->fetchAll(PDO::FETCH_ASSOC)]);
   }
   public function roles(): void
   {
      $st = DB::pdo()->query("SELECT id, nombre FROM rol WHERE activo=1 ORDER BY nombre");
      Response::json(['ok' => true, 'data' => $st->fetchAll(PDO::FETCH_ASSOC)]);
   }

   public function empresaDocumentoTipos(): void
   {
      $pdo = \App\Support\DB::pdo();

      $sql = "SELECT id, clave, nombre, requerido_pf, requerido_pm, multiple,
                   acepta_ext, max_mb, activo
            FROM empresa_documento_tipo
            WHERE activo = 1
            ORDER BY nombre ASC";

      $st = $pdo->query($sql);
      $rows = $st->fetchAll(\PDO::FETCH_ASSOC) ?: [];

      // normaliza tipos
      foreach ($rows as &$r) {
         // JSON → array
         if (is_string($r['acepta_ext']) && $r['acepta_ext'] !== '') {
            $decoded = json_decode($r['acepta_ext'], true);
            $r['acepta_ext'] = is_array($decoded) ? $decoded : [];
         } elseif (!is_array($r['acepta_ext'])) {
            $r['acepta_ext'] = [];
         }

         $r['id']           = (int)$r['id'];
         $r['requerido_pf'] = (int)$r['requerido_pf'];
         $r['requerido_pm'] = (int)$r['requerido_pm'];
         $r['multiple']     = (int)$r['multiple'];
         $r['max_mb']       = isset($r['max_mb']) ? (int)$r['max_mb'] : null;
         $r['activo']       = (int)$r['activo'];
      }

      \App\Http\Response::json(['ok' => true, 'data' => $rows]);
   }

   public function obligaciones(\App\Http\Request $req): void
   {
      try {
         $pdo = \App\Support\DB::pdo(); // ajusta si tu helper DB es diferente
         $st = $pdo->query("
            SELECT id, clave, descripcion, organismo, activo
            FROM obligacion
            ORDER BY organismo, descripcion
        ");
         $rows = $st->fetchAll(\PDO::FETCH_ASSOC) ?: [];
         \App\Http\Response::json(['ok' => true, 'data' => $rows], 200);
      } catch (\Throwable $e) {
         \App\Http\Response::json(
            ['ok' => false, 'error' => ['code' => 'ERR', 'message' => $e->getMessage()]],
            500
         );
      }
   }

   public function revisionTipos(\App\Http\Request $req): void
   {
      try {
         $db = \App\Support\DB::pdo();

         $q = trim((string)($req->get['q'] ?? ''));                 // filtro opcional por texto
         $incluyeInactivos = (string)($req->get['inactivos'] ?? '') === '1';

         $where = $incluyeInactivos ? '1=1' : 'rt.activo = 1';
         $params = [];

         if ($q !== '') {
            $where .= ' AND (rt.nombre LIKE :q OR rt.clave LIKE :q)';
            $params[':q'] = '%' . $q . '%';
         }

         $st = $db->prepare("
         SELECT rt.id, rt.nombre, rt.clave
         FROM revision_tipo rt
         WHERE {$where}
         ORDER BY rt.nombre ASC
      ");
         $st->execute($params);
         $rows = $st->fetchAll(\PDO::FETCH_ASSOC) ?: [];

         \App\Http\Response::json(['ok' => true, 'data' => $rows], 200);
      } catch (\Throwable $e) {
         error_log($e->getMessage());
         \App\Http\Response::json(['ok' => false, 'error' => ['code' => 'SERVER_ERROR', 'message' => 'No se pudo obtener revision_tipos']], 500);
      }
   }

   public function empresas(\App\Http\Request $req): void
   {
      try {
         $pdo = \App\Support\DB::pdo();

         // 1) Datos que (idealmente) pone el middleware
         $scope = $req->attr('scope') ?? [];
         $user  = $req->attr('user')  ?? [];

         // 2) ¿Es Dirección?
         $esDireccion = !empty($scope['direccion']) || (isset($user['role']) && $user['role'] === 'direccion');

         // 3) Determinar area_id “efectivo”
         $areaEfectiva = 0;

         if ($esDireccion) {
            // Dirección ve todas, no forzamos área
            $areaEfectiva = 0;
         } else {
            // Primero intenta con scope / user
            $areaEfectiva = (int)($scope['area_id'] ?? $user['area_id'] ?? 0);

            // Fallback: si sigue en 0, lo leemos de BD por el user actual
            if ($areaEfectiva <= 0 && isset($user['id'])) {
               $stA = $pdo->prepare("SELECT area_id FROM usuario WHERE id = :id LIMIT 1");
               $stA->execute([':id' => (int)$user['id']]);
               $areaEfectiva = (int)($stA->fetchColumn() ?: 0);
            }

            // Si aún no tenemos área, por seguridad devolvemos lista vacía
            if ($areaEfectiva <= 0) {
               \App\Http\Response::json(['ok' => true, 'data' => []], 200);
               return;
            }
         }

         // 4) Filtros opcionales
         $q                = trim((string)($req->get['q'] ?? ''));
         $incluyeInactivos = (string)($req->get['inactivos'] ?? '') === '1';

         // 5) WHERE dinámico
         $where  = $incluyeInactivos ? '1=1' : 'e.activo = 1';
         $params = [];

         if (!$esDireccion) {
            $where .= ' AND e.area_id = :a';
            $params[':a'] = $areaEfectiva;
         }
         if ($q !== '') {
            $where .= ' AND e.nombre LIKE :q';
            $params[':q'] = '%' . $q . '%';
         }

         // 6) Consulta
         $sql = "
         SELECT e.id, e.nombre, e.area_id
         FROM empresa e
         WHERE {$where}
         ORDER BY e.nombre ASC
      ";
         $st = $pdo->prepare($sql);
         $st->execute($params);
         $rows = $st->fetchAll(\PDO::FETCH_ASSOC) ?: [];

         foreach ($rows as &$r) {
            $r['id'] = (int)$r['id'];
            if (isset($r['area_id'])) $r['area_id'] = (int)$r['area_id'];
         }

         \App\Http\Response::json(['ok' => true, 'data' => $rows], 200);
      } catch (\Throwable $e) {
         // Si quieres ver qué `scope` y `user` llegan realmente, descomenta:
         // error_log('empresas scope='.json_encode($req->attr('scope')).' user='.json_encode($req->attr('user')));

         \App\Http\Response::json(
            ['ok' => false, 'error' => ['code' => 'SERVER_ERROR', 'message' => 'No se pudo obtener empresas']],
            500
         );
      }
   }
}
