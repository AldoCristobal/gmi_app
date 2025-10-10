<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Services\EmpresaObligacionService;

final class EmpresaObligacionController
{
   public function __construct(private EmpresaObligacionService $svc = new EmpresaObligacionService()) {}

   /**
    * GET /api/v1/empresas/obligaciones?empresa_id=123
    */
   public function index(Request $req): void
   {
      $empresaId = (int)($req->get['empresa_id'] ?? 0);
      if ($empresaId <= 0) {
         Response::json(['ok' => false, 'error' => ['code' => 'VALIDATION', 'message' => 'empresa_id requerido']], 400);
         return;
      }

      $out = $this->svc->listarPorEmpresa($empresaId);
      Response::json($out, ($out['ok'] ?? false) ? 200 : 422);
   }

   /**
    * POST /api/v1/empresas/obligaciones
    * body: {
    *   empresa_id, obligacion_id, periodicidad, tipo_dias,
    *   dia_vencimiento (nullable), offset_dias, fecha_inicio, fecha_fin (nullable),
    *   responsable_id (nullable), area_id (nullable), activo (1/0), notas (nullable)
    * }
    */
   public function store(Request $req): void
   {
      $in = $this->json($req);
      $out = $this->svc->asignar($in);
      Response::json($out, ($out['ok'] ?? false) ? 200 : 422);
   }

   /**
    * PUT /api/v1/empresas/obligaciones
    * body: {
    *   id, empresa_id, obligacion_id, ... (mismos campos que store)
    * }
    */
   public function update(Request $req): void
   {
      $in = $this->json($req);
      $id = (int)($in['id'] ?? 0);
      if ($id <= 0) {
         Response::json(['ok' => false, 'error' => ['code' => 'VALIDATION', 'message' => 'id requerido']], 400);
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
         // permite también en JSON
         $in = $this->json($req);
         $id = (int)($in['id'] ?? 0);
      }
      if ($id <= 0) {
         Response::json(['ok' => false, 'error' => ['code' => 'VALIDATION', 'message' => 'id requerido']], 400);
         return;
      }
      $out = $this->svc->desasignar($id);
      Response::json($out, ($out['ok'] ?? false) ? 200 : 422);
   }

   private function json(Request $r): array
   {
      $raw = file_get_contents('php://input') ?: '';
      $j = json_decode($raw, true);
      return is_array($j) ? $j : $r->post;
   }
}
