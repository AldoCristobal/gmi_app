<?php

declare(strict_types=1);

namespace App\Http\Middlewares;

use App\Http\Request;
use App\Http\Response;
use App\Security\Session;

final class AuthMiddleware
{
   public function handle(Request $req, callable $next)
   {
      Session::start();
      if (!isset($_SESSION['user'])) {
         return Response::json(['ok' => false, 'error' => ['code' => 'UNAUTH', 'message' => 'No autenticado']], 401);
      }
      // inyecta user en el Request
      $req = $req->withAttr('user', $_SESSION['user']);
      return $next($req);
   }
}
