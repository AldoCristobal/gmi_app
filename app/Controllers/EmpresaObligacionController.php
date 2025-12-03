<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Services\EmpresaObligacionService;

final class EmpresaObligacionController
{
   public function __construct(
      private EmpresaObligacionService $svc = new EmpresaObligacionService()
   ) {}

   /**
    * GET /api/v1/empresas/obligaciones?empresa_id=123
    */
   public function index(Request $req): void
   {
      $scope     = $req->attr('scope') ?? [];
      $empresaId = (int)($req->get['empresa_id'] ?? 0);

      if ($empresaId <= 0) {
         Response::json(
            [
               'ok'    => false,
               'error' => [
                  'code'    => 'VALIDATION',
                  'message' => 'empresa_id requerido',
               ],
            ],
            400
         );
         return;
      }

      $out = $this->svc->listarPorEmpresa($empresaId, $scope);

      $status = 200;
      if (!($out['ok'] ?? false)) {
         $code = $out['error']['code'] ?? '';
         if ($code === 'FORBIDDEN')      $status = 403;
         elseif ($code === 'VALIDATION') $status = 400;
         else                            $status = 422;
      }

      Response::json($out, $status);
   }

   /**
    * POST /api/v1/empresas/obligaciones
    * 1) Alta individual (form detalle): body con campos de una asignación
    * 2) Sincronización masiva (árbol): body { empresa_id, obligacion_ids: number[] }
    */
   public function store(Request $req): void
   {
      $scope = $req->attr('scope') ?? [];
      $in    = $this->json($req);

      // MODO SINCRONIZACIÓN (checkboxes del árbol)
      if (
         isset($in['empresa_id']) &&
         isset($in['obligacion_ids']) &&
         is_array($in['obligacion_ids'])
      ) {
         $empresaId = (int)$in['empresa_id'];
         $ids       = array_values(array_unique(array_map('intval', $in['obligacion_ids'])));

         if ($empresaId <= 0) {
            Response::json(
               [
                  'ok'    => false,
                  'error' => [
                     'code'    => 'VALIDATION',
                     'message' => 'empresa_id requerido',
                  ],
               ],
               400
            );
            return;
         }

         $out = $this->svc->syncAsignaciones($empresaId, $ids, $scope);

         $status = 200;
         if (!($out['ok'] ?? false)) {
            $code = $out['error']['code'] ?? '';
            if ($code === 'FORBIDDEN')      $status = 403;
            elseif ($code === 'VALIDATION') $status = 400;
            else                            $status = 422;
         }

         Response::json($out, $status);
         return;
      }

      // MODO ALTA INDIVIDUAL (form detalle)
      $out = $this->svc->asignar($in);
      Response::json($out, ($out['ok'] ?? false) ? 200 : 422);
   }

   /**
    * PUT /api/v1/empresas/obligaciones
    * body: { id, ...campos }
    */
   public function update(Request $req): void
   {
      $in = $this->json($req);

      $id = (int)($in['id'] ?? 0);
      if ($id <= 0) {
         Response::json(
            [
               'ok'    => false,
               'error' => [
                  'code'    => 'VALIDATION',
                  'message' => 'id requerido',
               ],
            ],
            400
         );
         return;
      }

      $out = $this->svc->actualizar($id, $in);
      Response::json($out, ($out['ok'] ?? false) ? 200 : 422);
   }

   /**
    * DELETE /api/v1/empresas/obligaciones?id=999
    */
   public function destroy(Request $req): void
   {
      $id = (int)($req->get['id'] ?? 0);
      if ($id <= 0) {
         $in = $this->json($req);
         $id = (int)($in['id'] ?? 0);
      }

      if ($id <= 0) {
         Response::json(
            [
               'ok'    => false,
               'error' => [
                  'code'    => 'VALIDATION',
                  'message' => 'id requerido',
               ],
            ],
            400
         );
         return;
      }

      $out = $this->svc->desasignar($id);
      Response::json($out, ($out['ok'] ?? false) ? 200 : 422);
   }

   // ---------- Helper JSON (igual que en otros controladores) ----------

   private function json(Request $r): array
   {
      $raw = file_get_contents('php://input') ?: '';
      $j   = json_decode($raw, true);
      return is_array($j) ? $j : $r->post;
   }
}
