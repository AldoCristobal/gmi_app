<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Services\UserService;

final class UserController
{
   public function __construct(private UserService $svc = new UserService()) {}

   /** GET /api/v1/admin/usuarios?page=&size=&q=&activo= */
   public function index(Request $req): void
   {
      $query = $req->query ?? [];

      $out = $this->svc->list($query);

      Response::json($out, 200);
   }

   /** POST /api/v1/admin/usuarios */
   public function store(Request $req): void
   {
      $input = $this->json($req);
      $out   = $this->svc->create($input);

      $status = ($out['ok'] ?? false) ? 200 : 422;
      Response::json($out, $status);
   }

   /** PUT /api/v1/admin/usuarios */
   public function update(Request $req): void
   {
      $input = $this->json($req);
      $id    = (int) ($input['id'] ?? 0);

      if ($id <= 0) {
         Response::json([
            'ok'    => false,
            'error' => ['code' => 'VALIDATION', 'message' => 'id required'],
         ], 400);
         return;
      }

      $out = $this->svc->update($id, $input);
      $status = ($out['ok'] ?? false) ? 200 : 422;
      Response::json($out, $status);
   }

   /** PATCH /api/v1/admin/usuarios/password */
   public function resetPassword(Request $req): void
   {
      $input = $this->json($req);
      $id    = (int) ($input['id'] ?? 0);
      $pwd   = (string) ($input['password'] ?? '');

      if ($id <= 0 || $pwd === '') {
         Response::json([
            'ok'    => false,
            'error' => ['code' => 'VALIDATION', 'message' => 'id and password required'],
         ], 400);
         return;
      }

      $out    = $this->svc->resetPassword($id, $pwd);
      $status = ($out['ok'] ?? false) ? 200 : 422;
      Response::json($out, $status);
   }

   /** DELETE /api/v1/admin/usuarios?id=123 */
   public function destroy(Request $req): void
   {
      $id = (int) (($req->query['id'] ?? 0));

      if ($id <= 0) {
         Response::json([
            'ok'    => false,
            'error' => ['code' => 'VALIDATION', 'message' => 'id required'],
         ], 400);
         return;
      }

      $out = $this->svc->delete($id);

      if (!($out['ok'] ?? false)) {
         $code = $out['error']['code'] ?? '';
         $status = $code === 'NOT_FOUND' ? 404 : 422;
         Response::json($out, $status);
         return;
      }

      Response::json($out, 200);
   }

   private function json(Request $r): array
   {
      $raw = file_get_contents('php://input') ?: '';
      $j   = json_decode($raw, true);
      return is_array($j) ? $j : ($r->post ?? []);
   }
}
