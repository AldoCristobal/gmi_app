<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Services\AuthService;

final class AuthController
{
   public function __construct(private AuthService $svc = new AuthService()) {}

   public function login(Request $req): void
   {
      // Acepta JSON o form-urlencoded
      $raw = file_get_contents('php://input') ?: '';
      $json = json_decode($raw, true);
      $email = trim((string)($json['email'] ?? $req->post['email'] ?? ''));
      $pass  = (string)($json['password'] ?? $req->post['password'] ?? '');

      if ($email === '' || $pass === '') {
         \App\Http\Response::json(['ok' => false, 'error' => ['code' => 'VALIDATION', 'message' => 'Faltan credenciales']], 400);
         return;
      }

      $res = $this->svc->login($email, $pass);
      $status = ($res['ok'] ?? false) ? 200 : 401;
      \App\Http\Response::json($res, $status);
      return;
   }


   public function logout(): void
   {
      $this->svc->logout();
      Response::json(['ok' => true]);
      return;
   }

   public function csrf(): void
   {
      $res = $this->svc->csrf();
      Response::json($res);
      return;
   }

   public function whoami(Request $r): void
   {
      $u = $r->attr('user', []);
      Response::json([
         'ok'   => true,
         'data' => [
            'id'        => $u['id']        ?? null,
            'nombre'    => $u['nombre']    ?? null,
            'roles'     => $u['roles']     ?? [],
            'permisos'  => $u['permisos']  ?? [],
         ]
      ]);
   }
}
