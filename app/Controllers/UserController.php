<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Services\UserService;
use App\Repositories\UserRepository;

final class UserController
{
   private UserRepository $repo;

   public function __construct(private UserService $svc = new UserService())
   {
      $this->repo = $repo ?? new UserRepository();
   }

   public function index(Request $req): void
   {
      $page  = max(1, (int)($req->query['page'] ?? 1));
      $size  = min(500, max(1, (int)($req->query['size'] ?? 100)));
      $q     = trim((string)($req->query['q'] ?? ''));
      $activoParam = $req->query['activo'] ?? '';
      $activo = ($activoParam === '1' || $activoParam === '0') ? (int)$activoParam : null;

      [$rows, $total] = $this->repo->listUsers($q === '' ? null : $q, $activo, $page, $size);

      Response::json([
         'ok'   => true,
         'data' => $rows,
         'meta' => [
            'page'  => $page,
            'size'  => $size,
            'total' => $total
         ]
      ]);
   }
   public function store(Request $r): void
   {
      $in = $this->json($r);
      $out = $this->svc->crear($in);
      Response::json($out, ($out['ok'] ?? false) ? 200 : 422);
   }
   public function update(Request $r): void
   {
      $in = $this->json($r);
      $id = (int)($in['id'] ?? 0);
      if ($id <= 0) {
         Response::json(['ok' => false, 'error' => ['code' => 'VALIDATION', 'message' => 'id requerido']], 400);
         return;
      }
      $out = $this->svc->actualizar($id, $in);
      Response::json($out, ($out['ok'] ?? false) ? 200 : 422);
   }
   public function resetPassword(Request $r): void
   {
      $in = $this->json($r);
      $id = (int)($in['id'] ?? 0);
      $pwd = (string)($in['password'] ?? '');
      if ($id <= 0 || $pwd === '') {
         Response::json(['ok' => false, 'error' => ['code' => 'VALIDATION']], 400);
         return;
      }
      $out = $this->svc->resetPassword($id, $pwd);
      Response::json($out, ($out['ok'] ?? false) ? 200 : 422);
   }
   public function destroy(Request $r): void
   {
      $in = $this->json($r);
      $id = (int)($in['id'] ?? 0);
      if ($id <= 0) {
         Response::json(['ok' => false, 'error' => ['code' => 'VALIDATION']], 400);
         return;
      }
      $out = $this->svc->borrar($id);
      Response::json($out, ($out['ok'] ?? false) ? 200 : 404);
   }
   private function json(Request $r): array
   {
      $raw = file_get_contents('php://input') ?: '';
      $j = json_decode($raw, true);
      return is_array($j) ? $j : $r->post;
   }
}
