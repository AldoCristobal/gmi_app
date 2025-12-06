<?php

declare(strict_types=1);

namespace App\Http\Middlewares;

use App\Http\Request;
use App\Http\Response;
use App\Security\Session;

final class AuthMiddleware
{
   public function __invoke(Request $req, callable $next): void
   {
      Session::start();

      $user = $_SESSION['user'] ?? null;

      if (!$user) {
         Response::error(
            $req,
            401,
            [
               'ok'    => false,
               'error' => [
                  'code'    => 'UNAUTH',
                  'message' => 'No autenticado',
               ],
            ]
         );
         return;
      }

      // Inyecta user en el Request
      $req = $req->withAttr('user', $user);

      $next($req);
   }
}
