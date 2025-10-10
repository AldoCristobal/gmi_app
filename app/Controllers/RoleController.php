<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Services\RoleService;

final class RoleController
{
   /** @var RoleService */
   private RoleService $svc;

   public function __construct()
   {
      // Evita usar "new" en el parámetro del constructor (no permitido)
      $this->svc = new RoleService();
   }

   /** GET /api/v1/roles?q=... */
   public function index(Request $r): void
   {
      $q = trim((string)($r->get['q'] ?? ''));
      $res = $this->svc->listar($q);
      Response::json($this->httpify($res), $this->statusOf($res));
   }

   /** POST /api/v1/roles */
   public function store(Request $r): void
   {
      $in = $this->json($r);
      $res = $this->svc->crear($in);
      Response::json($this->httpify($res), $res['ok'] ? 201 : $this->statusOf($res));
   }

   /** PUT /api/v1/admin/roles  body: { id, nombre, slug, descripcion, prioridad, activo } */
   public function update(Request $r): void
   {
      $in = $this->json($r);
      $id = (int)($in['id'] ?? 0);
      $res = $this->svc->actualizar($id, $in);
      Response::json($this->httpify($res), $this->statusOf($res));
   }

   /** DELETE /api/v1/admin/roles?id=123 */
   public function destroy(Request $r): void
   {
      $id = (int)($r->get['id'] ?? 0);
      $res = $this->svc->eliminar($id);
      Response::json($this->httpify($res), $this->statusOf($res));
   }

   public function permisos(Request $r): void
   {
      $id = (int)($r->get['id'] ?? 0);
      $res = $this->svc->permisos($id);
      Response::json($this->httpify($res), $this->statusOf($res));
   }

   public function savePermisos(Request $r): void
   {
      $in   = $this->json($r);
      $id   = (int)($in['role_id'] ?? 0);
      $keys = is_array($in['permisos'] ?? null) ? $in['permisos'] : [];
      $res  = $this->svc->guardarPermisos($id, $keys);
      Response::json($this->httpify($res), $this->statusOf($res));
   }

   /* ---------- Helpers ---------- */

   private function json(Request $r): array
   {
      $raw = file_get_contents('php://input') ?: '';
      $j = json_decode($raw, true);
      return is_array($j) ? $j : $r->post;
   }

   private function httpify(array $res): array
   {
      if (($res['ok'] ?? false) === true) return $res;
      if (!isset($res['code']) && isset($res['error']['code'])) $res['code'] = $res['error']['code'];
      if (!isset($res['msg']) && isset($res['error']['message'])) $res['msg'] = $res['error']['message'];
      unset($res['error']);
      return $res;
   }

   private function statusOf(array $res): int
   {
      if (($res['ok'] ?? false) === true) return 200;
      return match ($res['code'] ?? 'SERVER') {
         'VALIDATION' => 422,
         'CONFLICT'   => 409,
         'FORBIDDEN'  => 403,
         'NOT_FOUND'  => 404,
         default      => 500,
      };
   }
}
