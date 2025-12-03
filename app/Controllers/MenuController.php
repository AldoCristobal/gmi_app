<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Services\MenuService;
use InvalidArgumentException;

final class MenuController
{
   public function __construct(private MenuService $svc = new MenuService()) {}

   /** GET /api/v1/admin/menu?namespace=sidebar */
   public function index(): void
   {
      $ns  = $_GET['namespace'] ?? 'sidebar';
      $res = $this->svc->listar($ns);

      if ($res['ok'] ?? false) {
         Response::json($res, 200);
      } else {
         Response::json($res, 500);
      }
   }

   /** POST /api/v1/admin/menu */
   public function store(Request $r): void
   {
      $j   = $this->json($r);
      $res = $this->svc->crear($j);

      $status = ($res['ok'] ?? false) ? 200 : 400;
      Response::json($res, $status);
   }

   /** PUT /api/v1/admin/menu?id=123  (también acepta id en body) */
   public function update(Request $r): void
   {
      $j  = $this->json($r);
      $id = isset($_GET['id']) ? (int)$_GET['id'] : (int)($j['id'] ?? 0);
      if ($id <= 0) {
         Response::json([
            'ok'    => false,
            'error' => ['code' => 'VALIDATION', 'message' => 'ID requerido'],
         ], 400);
         return;
      }

      $j['id'] = $id;
      $res     = $this->svc->actualizar($j);

      if (!($res['ok'] ?? false) && ($res['error']['code'] ?? '') === 'NOT_FOUND') {
         Response::json($res, 404);
         return;
      }

      $status = ($res['ok'] ?? false) ? 200 : 400;
      Response::json($res, $status);
   }

   /** DELETE /api/v1/admin/menu?id=123&mode=cascade|reparent  (también acepta id/mode en body) */
   public function destroy(Request $r): void
   {
      $j    = $this->json($r);
      $id   = isset($_GET['id']) ? (int)$_GET['id'] : (int)($j['id'] ?? 0);
      $mode = $_GET['mode'] ?? ($j['mode'] ?? 'cascade');

      if ($id <= 0) {
         Response::json([
            'ok'    => false,
            'error' => ['code' => 'VALIDATION', 'message' => 'ID requerido'],
         ], 400);
         return;
      }

      $res = $this->svc->eliminar($id, $mode);

      if (!($res['ok'] ?? false) && ($res['error']['code'] ?? '') === 'NOT_FOUND') {
         Response::json($res, 404);
         return;
      }

      $status = ($res['ok'] ?? false) ? 200 : 400;
      Response::json($res, $status);
   }

   /** PATCH /api/v1/admin/menu/reorder  body: [{id,parent_id,orden,namespace}] */
   public function reorder(Request $r): void
   {
      $changes = $this->json($r);
      if (!is_array($changes)) {
         Response::json([
            'ok'    => false,
            'error' => ['code' => 'VALIDATION', 'message' => 'Payload inválido'],
         ], 400);
         return;
      }

      $res    = $this->svc->reorder($changes);
      $status = ($res['ok'] ?? false) ? 200 : 400;
      Response::json($res, $status);
   }

   /** POST /api/v1/admin/menu/roles */
   public function setRoles(Request $r): void
   {
      $j       = $this->json($r);
      $menuId  = (int)($j['menu_id'] ?? 0);
      $roleIds = is_array($j['role_ids'] ?? null) ? $j['role_ids'] : [];

      if ($menuId <= 0) {
         Response::json([
            'ok'    => false,
            'error' => ['code' => 'VALIDATION', 'message' => 'menu_id requerido'],
         ], 400);
         return;
      }

      $res    = $this->svc->setRoles($menuId, $roleIds);
      $status = ($res['ok'] ?? false) ? 200 : 400;
      Response::json($res, $status);
   }

   /** GET /api/v1/menu/tree */
   public function myTree(Request $r): void
   {
      $u   = $r->attr('user', []);
      $res = $this->svc->treeForUser($u);
      Response::json($res, ($res['ok'] ?? false) ? 200 : 500);
   }

   // ----------------- Helpers -----------------

   private function json(Request $r): array
   {
      $raw = file_get_contents('php://input') ?: '';
      $j   = json_decode($raw, true);
      return is_array($j) ? $j : ($r->post ?? []);
   }
}
