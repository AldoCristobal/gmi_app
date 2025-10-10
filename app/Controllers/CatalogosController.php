<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Response;
use App\Support\DB;
use PDO;

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
}
