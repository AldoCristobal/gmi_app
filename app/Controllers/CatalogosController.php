<?php

declare(strict_types=1);

namespace App\Controllers;

use PDO;
use App\Support\DB;
use App\Http\Request;
use App\Http\Response;
use App\Services\EmpresaService;
use App\Repositories\UserRepository;

final class CatalogosController
{
   public function __construct(
      private UserRepository $userRepo = new UserRepository(),
      private EmpresaService $empresaService = new EmpresaService(),
   ) {}

   private function enrichScope(array $scope, array $user): array
   {
      $scope2 = $scope;

      if (empty($scope2['user_id']) && !empty($user['id'])) {
         $scope2['user_id'] = (int)$user['id'];
      }

      if (empty($scope2['area_id']) && !empty($user['area_id'])) {
         $scope2['area_id'] = (int)$user['area_id'];
      }

      $userId   = (int)($scope2['user_id'] ?? 0);
      $viewTeam = !empty($scope2['view_team']);

      if ($viewTeam && $userId > 0) {
         // mismo método que usas en TareaTrabajoService / TareaExtraService
         $scope2['team_user_ids'] = $this->userRepo->findTeamUserIds($userId, true);
      } else {
         $scope2['team_user_ids'] = $scope2['team_user_ids'] ?? [];
      }

      return $scope2;
   }

   public function areas(): void
   {
      $st = DB::pdo()->query("SELECT id, nombre FROM area WHERE activo=1 ORDER BY nombre");
      Response::json(['ok' => true, 'data' => $st->fetchAll(PDO::FETCH_ASSOC)]);
   }

   public function jefes(Request $req): void
   {
      $scope = $req->attr('scope') ?? [];
      $q     = $req->get ?? [];

      $rows = $this->empresaService->catalogoJefesDesdeEmpresas($q, $scope);

      Response::json(['ok' => true, 'data' => $rows]);
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

   public function empresas(Request $req): void
   {
      $scope = $req->attr('scope') ?? [];
      $q     = $req->get ?? [];

      // Delegamos al servicio que ya sabe usar scopeWhere()
      $rows = $this->empresaService->catalogoEmpresas($q, $scope);

      Response::json(['ok' => true, 'data' => $rows]);
   }
}
