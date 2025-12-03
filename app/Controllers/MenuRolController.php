<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Services\MenuRolService;

final class MenuRolController
{
   public function __construct(
      private MenuRolService $service = new MenuRolService()
   ) {}

   /**
    * GET /api/v1/admin/menu-rol/tree?rol_id=1&namespace=sidebar
    */
   public function tree(Request $r): void
   {
      $rolId     = (int)($_GET['rol_id'] ?? 0);
      $namespace = $_GET['namespace'] ?? 'sidebar';

      if ($rolId <= 0) {
         Response::json([
            'ok'           => true,
            'data'         => [],
            'home_menu_id' => null,
         ]);
         return;
      }

      $res = $this->service->treeForRol($rolId, $namespace);

      if ($res['ok'] ?? false) {
         Response::json([
            'ok'           => true,
            'data'         => $res['data'] ?? [],
            'home_menu_id' => $res['home_menu_id'] ?? null,
         ]);
      } else {
         $err = $res['error'] ?? ['message' => 'Error desconocido', 'code' => 'SERVER'];
         Response::json(['ok' => false, 'error' => $err], 400);
      }
   }


   /**
    * POST /api/v1/admin/menu-rol/save
    * body JSON:
    *   { "rol_id": 1, "menu_ids": [1,2,3], "home_menu_id": 10|null }
    */
   public function save(Request $r): void
   {
      $j = $this->json($r);

      $rolId      = (int)($j['rol_id'] ?? 0);
      $menuIds    = $j['menu_ids'] ?? [];
      $homeMenuId = isset($j['home_menu_id']) ? (int)$j['home_menu_id'] : null; // 👈

      if ($rolId <= 0) {
         Response::json([
            'ok'    => false,
            'error' => ['code' => 'SERVER', 'message' => 'rol_id inválido']
         ], 400);
         return;
      }

      if (!is_array($menuIds)) {
         $menuIds = [];
      }

      $res = $this->service->saveRolMenu($rolId, $menuIds, $homeMenuId); // 👈

      if ($res['ok'] ?? false) {
         Response::json(['ok' => true]);
      } else {
         $err = $res['error'] ?? ['message' => 'Error desconocido', 'code' => 'SERVER'];
         Response::json(['ok' => false, 'error' => $err], 400);
      }
   }

   private function json(Request $r): array
   {
      $raw = file_get_contents('php://input') ?: '';
      $j   = json_decode($raw, true);
      return is_array($j) ? $j : ($r->post ?? []);
   }
}
