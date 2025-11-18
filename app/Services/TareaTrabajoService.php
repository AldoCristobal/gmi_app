<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\TareaRepository;
use App\Support\DB;
use PDO;
use Exception;

final class TareaTrabajoService
{
   public function __construct(
      private ?PDO $db = null,
      private ?TareaRepository $repo = null
   ) {
      $this->db   = $this->db   ?: DB::pdo();
      $this->repo = $this->repo ?: new TareaRepository($this->db);
   }

   /**
    * Lista tareas según filtros y alcance.
    * Para auxiliares: fuerza responsable_id = user.id (Mis tareas).
    */
   public function listar(array $q, array $user, array $scope): array
   {
      try {
         $role = strtoupper((string)($user['role'] ?? ''));
         $userId = (int)($user['id'] ?? 0);

         $filtros = [];

         // Filtros básicos desde query
         foreach (
            [
               'estado',
               'tipo_tarea',
               'empresa_id',
               'responsable_id',
               'fec_obj_desde',
               'fec_obj_hasta',
               'fec_venc_desde',
               'fec_venc_hasta'
            ] as $k
         ) {
            if (!empty($q[$k])) {
               $filtros[$k] = $q[$k];
            }
         }

         $esAuxiliar = ($role === 'AUXILIAR');

         // Mis tareas: si es AUXILIAR, siempre filtra por su id
         if ($esAuxiliar && $userId > 0) {
            $filtros['responsable_id'] = $userId;
         }

         // Para otros roles (supervisor/gerencia), permitimos filtrar por responsable_id
         // pero podríamos aquí aplicar más reglas con $scope si lo necesitas más adelante.

         $items = $this->repo->listar($filtros);

         return [
            'ok'   => true,
            'data' => [
               'items' => $items,
               'total' => count($items),
            ]
         ];
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

   /**
    * Detalle de una tarea, respetando alcance.
    */
   public function show(int $id, array $user, array $scope): array
   {
      try {
         $role   = strtoupper((string)($user['role'] ?? ''));
         $userId = (int)($user['id'] ?? 0);

         $tarea = $this->repo->findById($id);
         if (!$tarea) {
            return [
               'ok'    => false,
               'error' => ['code' => 'NOT_FOUND', 'message' => 'Tarea no encontrada']
            ];
         }

         $esAuxiliar = ($role === 'AUXILIAR');

         if ($esAuxiliar && $userId > 0 && (int)$tarea['responsable_id'] !== $userId) {
            return [
               'ok'    => false,
               'error' => ['code' => 'FORBIDDEN', 'message' => 'No puedes ver esta tarea']
            ];
         }

         // Para otros roles, podrías validar alcance adicional usando $scope.

         return [
            'ok'   => true,
            'data' => $tarea
         ];
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

   /**
    * Cambia el estado de una tarea desde "Mis tareas".
    * Auxiliares: solo pueden PENDIENTE <-> EN_REVISION sobre sus propias tareas.
    */
   public function cambiarEstado(
      int $id,
      string $nuevoEstado,
      array $user,
      array $scope,
      ?string $obs
   ): array {
      try {
         $role   = strtoupper((string)($user['role'] ?? ''));
         $userId = (int)($user['id'] ?? 0);
         $nuevoEstado = strtoupper(trim($nuevoEstado));

         $tarea = $this->repo->findById($id);
         if (!$tarea) {
            return [
               'ok'    => false,
               'error' => ['code' => 'NOT_FOUND', 'message' => 'Tarea no encontrada']
            ];
         }

         $esAuxiliar = ($role === 'AUXILIAR');

         if ($esAuxiliar && $userId > 0 && (int)$tarea['responsable_id'] !== $userId) {
            return [
               'ok'    => false,
               'error' => ['code' => 'FORBIDDEN', 'message' => 'No puedes modificar esta tarea']
            ];
         }

         $estadoActual = strtoupper((string)$tarea['estado']);
         $tieneEvidencia = (int)($tarea['tiene_evidencia'] ?? 0);

         // ❗ Regla de negocio firme:
         // No se puede pasar a EN_REVISION si no tiene evidencias
         if ($nuevoEstado === 'EN_REVISION' && $tieneEvidencia === 0) {
            return [
               'ok'    => false,
               'error' => [
                  'code'    => 'NO_EVIDENCIA',
                  'message' => 'No puedes enviar a revisión una tarea sin evidencia.'
               ]
            ];
         }

         // Reglas de transición para auxiliares
         if ($esAuxiliar) {
            $permitidas = [
               'PENDIENTE'   => ['EN_REVISION'],
               'EN_REVISION' => ['PENDIENTE'],
            ];

            if (
               !isset($permitidas[$estadoActual]) ||
               !in_array($nuevoEstado, $permitidas[$estadoActual], true)
            ) {
               return [
                  'ok'    => false,
                  'error' => [
                     'code'    => 'INVALID_TRANSITION',
                     'message' => "No puedes cambiar de {$estadoActual} a {$nuevoEstado}"
                  ]
               ];
            }
         } else {
            // Para otros roles, más adelante definiremos reglas del módulo de seguimiento.
         }

         $ok = $this->repo->cambiarEstado($id, $nuevoEstado, $obs);

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
