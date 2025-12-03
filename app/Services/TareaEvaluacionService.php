<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserRepository;
use App\Repositories\TareaRepository;
use App\Repositories\TareaEvaluacionRepository;

final class TareaEvaluacionService
{
   public function __construct(
      private TareaRepository $tareaRepo = new TareaRepository(),
      private TareaEvaluacionRepository $evalRepo = new TareaEvaluacionRepository(),
      private UserRepository $userRepo = new UserRepository()
   ) {}

   /**
    * Enriquecer scope con user_id, area_id y team_user_ids (igual que en TareaTrabajoService).
    *
    * @param array<string,mixed> $scope
    * @param array<string,mixed> $user
    * @return array<string,mixed>
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
         // mismo método que en otros módulos
         $scope2['team_user_ids'] = $this->userRepo->findTeamUserIds($userId, true);
      } else {
         $scope2['team_user_ids'] = $scope2['team_user_ids'] ?? [];
      }

      return $scope2;
   }

   /**
    * Listado de tareas a evaluar, respetando scope (TEAM/AREA/ALL/MINE).
    */
   public function listTasks(array $q, array $user, array $scope): array
   {
      // Enriquecemos igual que en Mis Tareas
      $scope = $this->enrichScope($scope, $user);

      $page = max(1, (int)($q['page'] ?? 1));
      $size = min(200, max(1, (int)($q['size'] ?? 20)));

      $filters = [];

      if (!empty($q['q'])) {
         // Por ahora el repo no tiene filtro "q", pero lo dejamos preparado
         $filters['q'] = trim((string)$q['q']);
      }
      if (!empty($q['empresa_id'])) {
         $filters['empresa_id'] = (int)$q['empresa_id'];
      }
      if (!empty($q['responsable_id'])) {
         $filters['responsable_id'] = (int)$q['responsable_id'];
      }
      if (!empty($q['estado'])) {
         $filters['estado'] = (string)$q['estado'];
      }
      if (!empty($q['tipo_tarea'])) {
         $filters['tipo_tarea'] = (string)$q['tipo_tarea'];
      }
      if (!empty($q['desde'])) {
         // Igual: el repo aún no filtra por fechas, pero lo dejamos anotado
         $filters['desde'] = (string)$q['desde'];
      }
      if (!empty($q['hasta'])) {
         $filters['hasta'] = (string)$q['hasta'];
      }

      // ⚠️ IMPORTANTE: TareaRepository::list() regresa un array plano de filas
      $items = $this->tareaRepo->list($filters, $scope);

      return [
         'ok'   => true,
         'data' => $items,             // el JS usa j.data directamente
         'meta' => [
            'page'  => $page,
            'size'  => $size,
            'total' => count($items),
         ],
      ];
   }

   /**
    * Detalle de tarea + historial de evaluaciones.
    */
   public function detail(int $tareaId, array $user, array $scope): array
   {
      $scope = $this->enrichScope($scope, $user);

      $tarea = $this->tareaRepo->findVisible($tareaId, $scope);
      if (!$tarea) {
         return [
            'ok'    => false,
            'error' => ['code' => 'FORBIDDEN', 'message' => 'Tarea no visible o inexistente'],
         ];
      }

      $hist = $this->evalRepo->historyByTask($tareaId);

      return [
         'ok'   => true,
         'data' => [
            'tarea'      => $tarea,
            'evaluacion' => $hist,
         ],
      ];
   }

   /**
    * Registrar una evaluación (acción del supervisor/gerencia/dirección).
    */
   public function store(array $input, array $user, array $scope): array
   {
      $scope = $this->enrichScope($scope, $user);

      $tareaId     = (int)($input['tarea_id'] ?? 0);
      $estadoNuevo = strtoupper((string)($input['estado_nuevo'] ?? ''));
      $comentario  = trim((string)($input['comentario'] ?? ''));

      if ($tareaId <= 0 || $estadoNuevo === '') {
         return [
            'ok'    => false,
            'error' => ['code' => 'VALIDATION', 'message' => 'tarea_id y estado_nuevo son requeridos'],
         ];
      }

      $allowedEstados = ['PENDIENTE', 'EN_REVISION', 'COMPLETA', 'CANCELADA', 'BLOQUEADA'];
      if (!in_array($estadoNuevo, $allowedEstados, true)) {
         return [
            'ok'    => false,
            'error' => ['code' => 'VALIDATION', 'message' => 'estado_nuevo inválido'],
         ];
      }

      // Validar visibilidad de la tarea según scope
      $tarea = $this->tareaRepo->findVisible($tareaId, $scope);
      if (!$tarea) {
         return [
            'ok'    => false,
            'error' => ['code' => 'FORBIDDEN', 'message' => 'Tarea no visible'],
         ];
      }

      $estadoAnterior = (string)$tarea['estado'];

      // Si no hay cambios y tampoco comentario, no hay nada que registrar
      if ($estadoAnterior === $estadoNuevo && $comentario === '') {
         return [
            'ok'    => false,
            'error' => ['code' => 'NOOP', 'message' => 'Sin cambios en el estado ni comentario'],
         ];
      }

      $evaluadorId = (int)($scope['user_id'] ?? 0);
      if ($evaluadorId <= 0) {
         return [
            'ok'    => false,
            'error' => ['code' => 'SERVER', 'message' => 'Evaluador no identificado'],
         ];
      }

      // Insertar en historial
      $this->evalRepo->insertEvaluation([
         'tarea_id'        => $tareaId,
         'evaluador_id'    => $evaluadorId,
         'estado_anterior' => $estadoAnterior,
         'estado_nuevo'    => $estadoNuevo,
         'comentario'      => $comentario,
      ]);

      // Actualizar estado de la tarea
      $ok = $this->tareaRepo->changeStatus($tareaId, $estadoNuevo, null);
      if (!$ok) {
         return [
            'ok'    => false,
            'error' => ['code' => 'UPDATE_FAIL', 'message' => 'No se pudo actualizar tarea'],
         ];
      }

      return ['ok' => true];
   }

   /**
    * Historial global de evaluaciones (para reportes).
    */
   public function history(array $q): array
   {
      $page = max(1, (int)($q['page'] ?? 1));
      $size = min(200, max(1, (int)($q['size'] ?? 20)));

      $filters = [];
      if (!empty($q['evaluador_id'])) {
         $filters['evaluador_id'] = (int)$q['evaluador_id'];
      }
      if (!empty($q['desde'])) {
         $filters['desde'] = (string)$q['desde'];
      }
      if (!empty($q['hasta'])) {
         $filters['hasta'] = (string)$q['hasta'];
      }

      $res = $this->evalRepo->historyList($filters, $page, $size);

      return [
         'ok'   => true,
         'data' => $res['rows'],
         'meta' => [
            'page'  => $page,
            'size'  => $size,
            'total' => $res['total'],
         ],
      ];
   }
}
