<?php

declare(strict_types=1);

namespace App\Http\Middlewares;

use App\Http\Request;
use App\Http\Response;
use App\Security\Session;

final class CsrfMiddleware
{
   public function handle(Request $req, callable $next)
   {
      if (in_array($req->method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
         Session::start();
         $token = $req->headers['X-CSRF-Token'] ?? $req->headers['x-csrf-token'] ?? '';
         $sessionToken = $_SESSION['csrf_token'] ?? '';
         if (!$token || !$sessionToken || !hash_equals($sessionToken, $token)) {
            return Response::json(['ok' => false, 'error' => ['code' => 'CSRF', 'message' => 'Token inválido']], 419);
         }
      }
      return $next($req);
   }
}
