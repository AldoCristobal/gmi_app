<?php

declare(strict_types=1);

namespace App\Http\Middlewares;

use App\Http\Request;

final class ScopeMiddleware
{
   public function handle(Request $req, callable $next)
   {
      $u = $req->attr('user');
      $roles = array_map('strtolower', $u['roles'] ?? []);
      $scope = [
         'direccion' => in_array('direccion', $roles),
         'gerencia'  => in_array('gerencia',  $roles),
         'auxiliar'  => in_array('auxiliar',  $roles),
         'area_id'   => $u['area_id'] ?? null,
         'user_id'   => $u['id'] ?? null,
      ];
      $req = $req->withAttr('scope', $scope);
      return $next($req);
   }
}
