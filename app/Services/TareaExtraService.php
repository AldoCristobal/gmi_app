<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\TareaRepository;
use App\Repositories\UserRepository;

final class TareaExtraService
{
   public function __construct(
      private TareaRepository $repo = new TareaRepository(),
      private UserRepository $userRepo = new UserRepository()
   ) {}

   /**
    * Igual que en TareaTrabajoService:
    * - añade user_id y area_id al scope si faltan
    * - calcula team_user_ids si view_team está activo
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
         $scope2['team_user_ids'] = $this->userRepo->findTeamUserIds($userId, true);
      } else {
         $scope2['team_user_ids'] = $scope2['team_user_ids'] ?? [];
      }

      return $scope2;
   }

   /**
    * Listar tareas EXTRAORDINARIA con el mismo esquema de scope que "Mis tareas".
    */
   public function list(array $filters, array $user, array $scope): array
   {
      $scope = $this->enrichScope($scope, $user);

      // Nos aseguramos de que siempre sea EXTRAORDINARIA
      $filters['tipo_tarea'] = 'EXTRAORDINARIA';

      $items = $this->repo->list($filters, $scope);

      return [
         'ok'   => true,
         'data' => [
            'items' => $items,
            'total' => count($items),
         ],
      ];
   }

   /**
    * Crear tarea EXTRAORDINARIA (origen MANUAL).
    */
   public function create(array $input): array
   {
      $v = $this->validate($input, false);
      if ($v !== true) {
         return $v;
      }

      $data = [
         'empresa_obligacion_id' => null,
         'tipo_tarea'            => 'EXTRAORDINARIA',
         'origen'                => 'MANUAL',
         'empresa_id'            => (int)$input['empresa_id'],
         'periodo_inicio'        => $input['periodo_inicio'],
         'periodo_fin'           => $input['periodo_fin'],
         'fecha_objetivo'        => $input['fecha_objetivo'] ?? null,
         'fecha_vencimiento'     => $input['fecha_vencimiento'],
         'estado'                => $input['estado'] ?? 'PENDIENTE',
         'progreso'              => 0.00,
         'responsable_id'        => (int)$input['responsable_id'],
         'titulo'                => $input['titulo'],
         'observaciones'         => $input['observaciones'] ?? null,
      ];

      $id = $this->repo->create($data);

      return [
         'ok'   => true,
         'data' => ['id' => $id],
      ];
   }

   /**
    * Actualizar EXTRAORDINARIA.
    */
   public function update(int $id, array $input, array $user, array $scope): array
   {
      if ($id <= 0) {
         return ['ok' => false, 'error' => ['code' => 'VALIDATION', 'message' => 'id required']];
      }

      $scope = $this->enrichScope($scope, $user);

      $row = $this->repo->findVisible($id, $scope);
      if (!$row) {
         return ['ok' => false, 'error' => ['code' => 'NOT_FOUND', 'message' => 'task not found']];
      }

      if (($row['tipo_tarea'] ?? '') !== 'EXTRAORDINARIA') {
         return ['ok' => false, 'error' => ['code' => 'FORBIDDEN', 'message' => 'only EXTRAORDINARIA can be edited here']];
      }

      $v = $this->validate($input, true);
      if ($v !== true) {
         return $v;
      }

      $data = [
         'empresa_id'        => (int)$input['empresa_id'],
         'titulo'            => $input['titulo'],
         'periodo_inicio'    => $input['periodo_inicio'],
         'periodo_fin'       => $input['periodo_fin'],
         'fecha_objetivo'    => $input['fecha_objetivo'] ?? null,
         'fecha_vencimiento' => $input['fecha_vencimiento'],
         'responsable_id'    => (int)$input['responsable_id'],
         'observaciones'     => $input['observaciones'] ?? null,
      ];

      $ok = $this->repo->update($id, $data);
      if (!$ok) {
         return ['ok' => false, 'error' => ['code' => 'UPDATE_FAIL']];
      }

      return ['ok' => true];
   }

   /**
    * Borrar EXTRAORDINARIA (hard delete).
    */
   public function delete(int $id, array $user, array $scope): array
   {
      if ($id <= 0) {
         return ['ok' => false, 'error' => ['code' => 'VALIDATION', 'message' => 'id required']];
      }

      $scope = $this->enrichScope($scope, $user);

      $row = $this->repo->findVisible($id, $scope);
      if (!$row) {
         return ['ok' => false, 'error' => ['code' => 'NOT_FOUND', 'message' => 'task not found']];
      }

      if (($row['tipo_tarea'] ?? '') !== 'EXTRAORDINARIA') {
         return ['ok' => false, 'error' => ['code' => 'FORBIDDEN', 'message' => 'only EXTRAORDINARIA can be deleted here']];
      }

      $ok = $this->repo->delete($id);
      if (!$ok) {
         return ['ok' => false, 'error' => ['code' => 'DELETE_FAIL']];
      }

      return ['ok' => true];
   }

   /**
    * Validación simple.
    */
   private function validate(array &$input, bool $isUpdate): true|array
   {
      $input['titulo'] = trim((string)($input['titulo'] ?? ''));
      if ($input['titulo'] === '') {
         return ['ok' => false, 'error' => ['code' => 'VALIDATION', 'message' => 'titulo required']];
      }

      $input['empresa_id'] = isset($input['empresa_id']) ? (int)$input['empresa_id'] : 0;
      if ($input['empresa_id'] <= 0) {
         return ['ok' => false, 'error' => ['code' => 'VALIDATION', 'message' => 'empresa_id required']];
      }

      $input['responsable_id'] = isset($input['responsable_id']) ? (int)$input['responsable_id'] : 0;
      if ($input['responsable_id'] <= 0) {
         return ['ok' => false, 'error' => ['code' => 'VALIDATION', 'message' => 'responsable_id required']];
      }

      $input['periodo_inicio']    = trim((string)($input['periodo_inicio'] ?? ''));
      $input['periodo_fin']       = trim((string)($input['periodo_fin'] ?? ''));
      $input['fecha_vencimiento'] = trim((string)($input['fecha_vencimiento'] ?? ''));
      $input['fecha_objetivo']    = isset($input['fecha_objetivo']) && $input['fecha_objetivo'] !== ''
         ? trim((string)$input['fecha_objetivo'])
         : null;

      if ($input['periodo_inicio'] === '' || $input['periodo_fin'] === '' || $input['fecha_vencimiento'] === '') {
         return ['ok' => false, 'error' => ['code' => 'VALIDATION', 'message' => 'dates required']];
      }

      return true;
   }
}
