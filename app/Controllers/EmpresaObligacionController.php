<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\EmpresaObligacionService;

final class EmpresaObligacionController
{
   public function __construct(private EmpresaObligacionService $svc = new EmpresaObligacionService()) {}

   /**
    * GET /api/v1/empresas/obligaciones?empresa_id=123
    */
   public function index($req): void
   {
      $empresaId = (int)($_GET['empresa_id'] ?? 0);
      if ($empresaId <= 0) {
         \App\Http\Response::json(['ok' => false, 'error' => ['code' => 'VALIDATION', 'message' => 'empresa_id requerido']], 400);
         return;
      }
      $out = $this->svc->listarPorEmpresa($empresaId);
      \App\Http\Response::json($out, ($out['ok'] ?? false) ? 200 : 422);
   }

   /**
    * POST /api/v1/empresas/obligaciones
    * 1) Alta individual (form detalle): body con campos de una asignación
    * 2) Sincronización masiva (árbol): body { empresa_id, obligacion_ids: number[] }
    */
   public function store($req): void
   {
      $raw = file_get_contents('php://input') ?: '';
      $in  = json_decode($raw, true);
      if (!is_array($in)) $in = $_POST ?? [];

      // MODO SINCRONIZACIÓN (checkboxes)
      if (isset($in['empresa_id']) && isset($in['obligacion_ids']) && is_array($in['obligacion_ids'])) {
         $empresaId = (int)$in['empresa_id'];
         $ids = array_values(array_unique(array_map('intval', $in['obligacion_ids'])));
         if ($empresaId <= 0) {
            \App\Http\Response::json(['ok' => false, 'error' => ['code' => 'VALIDATION', 'message' => 'empresa_id requerido']], 400);
            return;
         }
         $out = $this->svc->syncAsignaciones($empresaId, $ids);
         \App\Http\Response::json($out, ($out['ok'] ?? false) ? 200 : 422);
         return;
      }

      // MODO ALTA INDIVIDUAL (form detalle)
      $out = $this->svc->asignar($in);
      \App\Http\Response::json($out, ($out['ok'] ?? false) ? 200 : 422);
   }

   /**
    * PUT /api/v1/empresas/obligaciones
    * body: { id, ...campos }
    */
   public function update($req): void
   {
      $raw = file_get_contents('php://input') ?: '';
      $in  = json_decode($raw, true);
      if (!is_array($in)) $in = $_POST ?? [];

      $id = (int)($in['id'] ?? 0);
      if ($id <= 0) {
         \App\Http\Response::json(['ok' => false, 'error' => ['code' => 'VALIDATION', 'message' => 'id requerido']], 400);
         return;
      }
      $out = $this->svc->actualizar($id, $in);
      \App\Http\Response::json($out, ($out['ok'] ?? false) ? 200 : 422);
   }

   /**
    * DELETE /api/v1/empresas/obligaciones?id=999
    */
   public function destroy($req): void
   {
      $id = (int)($_GET['id'] ?? 0);
      if ($id <= 0) {
         $raw = file_get_contents('php://input') ?: '';
         $j = json_decode($raw, true);
         if (is_array($j)) $id = (int)($j['id'] ?? 0);
      }
      if ($id <= 0) {
         \App\Http\Response::json(['ok' => false, 'error' => ['code' => 'VALIDATION', 'message' => 'id requerido']], 400);
         return;
      }
      $out = $this->svc->desasignar($id);
      \App\Http\Response::json($out, ($out['ok'] ?? false) ? 200 : 422);
   }
}
