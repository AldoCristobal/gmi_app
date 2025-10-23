<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\EmpresaObligacionRepository;

final class EmpresaObligacionService
{
   public function __construct(private EmpresaObligacionRepository $repo = new EmpresaObligacionRepository()) {}

   public function listarPorEmpresa(int $empresaId): array
   {
      try {
         $rows = $this->repo->listByEmpresa($empresaId);
         return ['ok' => true, 'data' => $rows];
      } catch (\Throwable $e) {
         return ['ok' => false, 'error' => ['code' => 'ERR', 'message' => $e->getMessage()]];
      }
   }

   /** Alta individual (form de detalle) */
   public function asignar(array $in): array
   {
      $req = ['empresa_id', 'obligacion_id', 'periodicidad', 'tipo_dias', 'fecha_inicio'];
      foreach ($req as $k) {
         if (!isset($in[$k]) || $in[$k] === '' || $in[$k] === null) {
            return ['ok' => false, 'error' => ['code' => 'VALIDATION', 'message' => "$k requerido"]];
         }
      }

      // Normalizaciones
      $in['dia_vencimiento'] = isset($in['dia_vencimiento'])
         ? (is_numeric($in['dia_vencimiento']) ? (int)$in['dia_vencimiento'] : null) : null;
      $in['offset_dias']     = isset($in['offset_dias']) ? (int)$in['offset_dias'] : 0;
      $in['activo']          = isset($in['activo']) ? (int)$in['activo'] : 1;
      $in['responsable_id']  = isset($in['responsable_id']) && $in['responsable_id'] !== '' ? (int)$in['responsable_id'] : null;
      $in['area_id']         = isset($in['area_id']) && $in['area_id'] !== '' ? (int)$in['area_id'] : null;
      $in['fecha_fin']       = $in['fecha_fin'] ?? null;

      try {
         $newId = $this->repo->insert($in);
         return ['ok' => true, 'data' => ['id' => $newId]];
      } catch (\Throwable $e) {
         return ['ok' => false, 'error' => ['code' => 'ERR', 'message' => $e->getMessage()]];
      }
   }

   /** Edición individual (form de detalle) */
   public function actualizar(int $id, array $in): array
   {
      $in['id'] = $id;
      $in['dia_vencimiento'] = isset($in['dia_vencimiento'])
         ? (is_numeric($in['dia_vencimiento']) ? (int)$in['dia_vencimiento'] : null) : null;
      $in['offset_dias']     = isset($in['offset_dias']) ? (int)$in['offset_dias'] : 0;
      $in['activo']          = isset($in['activo']) ? (int)$in['activo'] : 1;
      $in['responsable_id']  = isset($in['responsable_id']) && $in['responsable_id'] !== '' ? (int)$in['responsable_id'] : null;
      $in['area_id']         = isset($in['area_id']) && $in['area_id'] !== '' ? (int)$in['area_id'] : null;
      $in['fecha_fin']       = $in['fecha_fin'] ?? null;

      try {
         $ok = $this->repo->update($in);
         return $ok
            ? ['ok' => true, 'data' => ['id' => $id]]
            : ['ok' => false, 'error' => ['code' => 'NOT_FOUND', 'message' => 'Asignación no encontrada']];
      } catch (\Throwable $e) {
         return ['ok' => false, 'error' => ['code' => 'ERR', 'message' => $e->getMessage()]];
      }
   }

   public function desasignar(int $id): array
   {
      try {
         $ok = $this->repo->delete($id);
         return $ok ? ['ok' => true] : ['ok' => false, 'error' => ['code' => 'NOT_FOUND', 'message' => 'Asignación no encontrada']];
      } catch (\Throwable $e) {
         return ['ok' => false, 'error' => ['code' => 'ERR', 'message' => $e->getMessage()]];
      }
   }

   /**
    * Sincroniza el conjunto completo (checkboxes del árbol).
    * Inserta los que faltan y elimina los que ya no estén.
    */
   public function syncAsignaciones(int $empresaId, array $nuevosIds): array
   {
      try {
         $final = $this->repo->syncForEmpresa($empresaId, $nuevosIds);
         return [
            'ok'   => true,
            'data' => [
               'final_ids' => $final
            ]
         ];
      } catch (\Throwable $e) {
         return ['ok' => false, 'error' => ['code' => 'ERR', 'message' => $e->getMessage()]];
      }
   }
}
