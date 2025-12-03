<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\TareaRepository;
use App\Repositories\UserRepository;
use Exception;

final class TareaTrabajoService
{
   public function __construct(
      private TareaRepository $repo = new TareaRepository(),
      private UserRepository $userRepo = new UserRepository(),
   ) {}

   /**
    * Enriquecer scope con user_id, area_id y team_user_ids.
    */
   private function enrichScope(array $scope, array $user): array
   {
      $scope2 = $scope;

      if (empty($scope2['user_id']) && !empty($user['id'])) {
         $scope2['user_id'] = (int)$user['id'];
      }

      if (empty($scope2['area_id']) && !empty($user['area_id'])) {
         $scope2['area_id'] = (int)$user['area_id'];
      }

      $userId   = (int)($scope2['user_id'] ?? 0);
      $viewTeam = !empty($scope2['view_team']);

      if ($viewTeam && $userId > 0) {
         // Método ya normalizado: findTeamUserIds(int $userId, bool $includeSelf = true)
         $scope2['team_user_ids'] = $this->userRepo->findTeamUserIds($userId, true);
      } else {
         $scope2['team_user_ids'] = $scope2['team_user_ids'] ?? [];
      }

      return $scope2;
   }

   /**
    * Listar "Mis tareas" (o por equipo/área/todas según scope).
    */
   public function list(array $filters, array $user, array $scope): array
   {
      try {
         $scope = $this->enrichScope($scope, $user);

         // Ya no forzamos filtros de área / responsable aquí,
         // eso lo controla el scope en el repositorio.
         $items = $this->repo->list($filters, $scope);

         return [
            'ok'   => true,
            'data' => [
               'items' => $items,
               'total' => count($items),
            ]
         ];
      } catch (\Throwable $e) {
         return [
            'ok'    => false,
            'error' => [
               'code'    => 'EXCEPTION',
               'message' => $e->getMessage()
            ]
         ];
      }
   }

   public function show(int $id, array $user, array $scope): array
   {
      try {
         $scope = $this->enrichScope($scope, $user);

         $tarea = $this->repo->findVisible($id, $scope);
         if (!$tarea) {
            return [
               'ok'   => false,
               'error' => [
                  'code'    => 'NOT_FOUND',
                  'message' => 'Tarea no encontrada o fuera de alcance'
               ]
            ];
         }

         return [
            'ok'   => true,
            'data' => $tarea
         ];
      } catch (\Throwable $e) {
         return [
            'ok'    => false,
            'error' => [
               'code'    => 'EXCEPTION',
               'message' => $e->getMessage()
            ]
         ];
      }
   }

   /**
    * Cambiar estado de una tarea (PENDIENTE <-> EN_REVISION).
    * Solo el responsable puede hacerlo, independientemente del scope de lectura.
    */
   public function changeStatus(
      int $id,
      string $newStatus,
      array $user,
      array $scope,
      ?string $obs
   ): array {
      try {
         $scope  = $this->enrichScope($scope, $user);
         $userId = (int)($scope['user_id'] ?? ($user['id'] ?? 0));

         $tarea = $this->repo->findById($id);
         if (!$tarea) {
            return [
               'ok'   => false,
               'error' => ['code' => 'NOT_FOUND', 'message' => 'Tarea no encontrada']
            ];
         }

         // Solo el responsable puede cambiar estado, aunque tenga view_all
         if ($userId <= 0 || (int)$tarea['responsable_id'] !== $userId) {
            return [
               'ok'   => false,
               'error' => ['code' => 'FORBIDDEN', 'message' => 'No puedes modificar esta tarea']
            ];
         }

         $currentStatus = strtoupper((string)$tarea['estado']);
         $newStatus     = strtoupper(trim($newStatus));
         $hasEvidence   = (int)($tarea['tiene_evidencia'] ?? 0);

         if ($newStatus === 'EN_REVISION' && $hasEvidence === 0) {
            return [
               'ok'    => false,
               'error' => [
                  'code'    => 'NO_EVIDENCIA',
                  'message' => 'No puedes enviar a revisión una tarea sin evidencia.'
               ]
            ];
         }

         if ($newStatus === 'EN_REVISION' && $currentStatus !== 'PENDIENTE') {
            return [
               'ok' => false,
               'error' => [
                  'code'    => 'INVALID_TRANSITION',
                  'message' => 'Solo puedes enviar a revisión tareas pendientes'
               ]
            ];
         }

         if ($newStatus === 'PENDIENTE' && $currentStatus !== 'EN_REVISION') {
            return [
               'ok' => false,
               'error' => [
                  'code'    => 'INVALID_TRANSITION',
                  'message' => 'Solo puedes reabrir tareas en revisión'
               ]
            ];
         }

         $ok = $this->repo->changeStatus($id, $newStatus, $obs);

         if (!$ok) {
            return [
               'ok'    => false,
               'error' => [
                  'code'    => 'DB_ERROR',
                  'message' => 'No se pudo actualizar el estado de la tarea'
               ]
            ];
         }

         return ['ok' => true];
      } catch (Exception $e) {
         return [
            'ok'    => false,
            'error' => [
               'code'    => 'EXCEPTION',
               'message' => $e->getMessage()
            ]
         ];
      }
   }
}
