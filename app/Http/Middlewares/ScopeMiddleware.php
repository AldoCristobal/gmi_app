<?php

declare(strict_types=1);

namespace App\Http\Middlewares;

use App\Http\Request;

final class ScopeMiddleware
{
   public function __invoke(Request $req, callable $next): void
   {
      $user = $req->attr('user') ?? null;

      if (!$user) {
         $next($req);
         return;
      }

      $perms = $user['permisos'] ?? [];
      $roles = $user['roles']    ?? [];

      $perms = is_array($perms) ? $perms : [];
      $roles = is_array($roles) ? $roles : [];

      // ------------------------------------------------------------------
      // SCOPES NUEVOS (con permisos scope.*)
      // ------------------------------------------------------------------
      $hasScopeMine = in_array('scope.mine', $perms, true);
      $hasScopeArea = in_array('scope.area', $perms, true);
      $hasScopeTeam = in_array('scope.team', $perms, true);
      $hasScopeAll  = in_array('scope.all',  $perms, true);

      $usingNewScopes = $hasScopeMine || $hasScopeArea || $hasScopeTeam || $hasScopeAll;

      $scope = [
         'view_all'  => false,
         'view_team' => false,
         'view_area' => false,
         'view_mine' => false,

         'area_id' => $user['area_id'] ?? null,
         'user_id' => $user['id']      ?? null,
      ];

      if ($usingNewScopes) {
         // Nuevo esquema activado por permisos
         $scope['view_all']  = $hasScopeAll;
         $scope['view_team'] = $hasScopeTeam;
         $scope['view_area'] = $hasScopeArea;

         // "mine" siempre que no haya "all / area / team"
         $scope['view_mine'] = $hasScopeMine || (!$hasScopeAll && !$hasScopeArea && !$hasScopeTeam);
      } else {
         // ------------------------------------------------------------------
         // LEGACY por ROL
         // ------------------------------------------------------------------
         $rl = array_map('strtolower', $roles);

         $isDireccion  = in_array('direccion',  $rl, true);
         $isGerencia   = in_array('gerencia',   $rl, true);
         $isSupervisor = in_array('supervisor', $rl, true);
         $isAuxiliar   = in_array('auxiliar',   $rl, true);

         if ($isDireccion) {
            $scope['view_all']  = true;
            $scope['view_team'] = true;
            $scope['view_area'] = true;
            $scope['view_mine'] = true;
         } elseif ($isGerencia) {
            $scope['view_area'] = true;
            $scope['view_mine'] = true;
         } elseif ($isSupervisor) {
            $scope['view_team'] = true;
            $scope['view_mine'] = true;
         } else {
            // Auxiliar / resto
            $scope['view_mine'] = true;
         }
      }

      // ------------------------------------------------------------------
      // LLAVES LEGACY (compatibilidad)
      // ------------------------------------------------------------------

      $scope['direccion'] = $scope['view_all'];

      // Gerencia = ve solo por área (pero NO todo y NO team)
      $scope['gerencia'] =
         (!$scope['view_all'])
         && $scope['view_area']
         && (!$scope['view_team']);

      // Auxiliar = solo mías (sin área, sin team, sin all)
      $scope['auxiliar'] =
         (!$scope['view_all'])
         && (!$scope['view_area'])
         && (!$scope['view_team'])
         && ($scope['view_mine']);

      // aux_only = legacy: solo responsable
      $scope['aux_only'] = $scope['auxiliar'];

      // Guardar en request
      $req = $req->withAttr('scope', $scope);

      $next($req);
   }
}
