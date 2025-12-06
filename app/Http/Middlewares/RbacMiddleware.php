<?php

declare(strict_types=1);

namespace App\Http\Middlewares;

use App\Http\Request;
use App\Http\Response;

final class RbacMiddleware
{
   public function __construct(private array $requiredPerms) {}

   public function handle(Request $req, callable $next)
   {
      $user  = $req->attr('user');
      $perms = $user['permisos'] ?? [];
      foreach ($this->requiredPerms as $p) {
         if (!in_array($p, $perms, true)) {

            Response::error(
               $req,
               403,
               [
                  'ok'    => false,
                  'error' => [
                     'code'    => 'FORBIDDEN',
                     'message' => "Falta permiso $p",
                  ],
               ]
            );
            return;
         }
      }
      return $next($req);
   }
}
