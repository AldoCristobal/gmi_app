<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Services\EmpresaObligacionRutinaService;

final class EmpresaObligacionRutinaController
{
   public function __construct(
      private EmpresaObligacionRutinaService $svc = new EmpresaObligacionRutinaService()
   ) {}

   /**
    * GET /api/v1/empresas/obligaciones/rutinas?empresa_id=123
    * Lista obligaciones de la empresa + resumen de rutina.
    */
   public function index(Request $req): void
   {
      $scope     = $req->attr('scope') ?? [];
      $empresaId = (int)($req->get['empresa_id'] ?? 0);

      if ($empresaId <= 0) {
         Response::json([
            'ok'    => false,
            'error' => ['code' => 'VALIDATION', 'message' => 'empresa_id requerido']
         ], 400);
         return;
      }

      $out = $this->svc->listarPorEmpresa($empresaId, $scope);
      Response::json($out, ($out['ok'] ?? false) ? 200 : 422);
   }

   /**
    * GET /api/v1/empresas/obligaciones/rutina?empresa_id=123&obligacion_id=45
    * Detalle de configuración de rutina para una empresa + obligación.
    */
   public function show(Request $req): void
   {
      $scope       = $req->attr('scope') ?? [];
      $empresaId   = (int)($req->get['empresa_id'] ?? 0);
      $obligacionId = (int)($req->get['obligacion_id'] ?? 0);

      if ($empresaId <= 0 || $obligacionId <= 0) {
         Response::json([
            'ok'    => false,
            'error' => ['code' => 'VALIDATION', 'message' => 'empresa_id y obligacion_id requeridos']
         ], 400);
         return;
      }

      $out = $this->svc->obtenerRutina($empresaId, $obligacionId, $scope);

      // si la empresa/obligación no existe o no está en scope, el service puede regresar ok=false
      Response::json($out, ($out['ok'] ?? false) ? 200 : 404);
   }

   /**
    * PUT /api/v1/empresas/obligaciones/rutina
    * Body JSON: { empresa_id, obligacion_id, dia_vencimiento, dias_anticipacion, offset_dias, ... }
    */
   public function update(Request $req): void
   {
      $scope = $req->attr('scope') ?? [];
      $user  = $req->attr('user')  ?? [];

      $in = $this->json($req); // aquí ya es SIEMPRE array

      $empresaId    = (int)($in['empresa_id'] ?? 0);
      $obligacionId = (int)($in['obligacion_id'] ?? 0);

      if ($empresaId <= 0 || $obligacionId <= 0) {
         Response::json([
            'ok'    => false,
            'error' => ['code' => 'VALIDATION', 'message' => 'empresa_id y obligacion_id requeridos']
         ], 400);
         return;
      }

      // metadatos opcionales para el service
      $in['__user_id']   = $user['id']      ?? null;
      $in['__user_area'] = $user['area_id'] ?? null;
      $in['__scope']     = $scope;

      $out = $this->svc->guardarRutina($empresaId, $obligacionId, $in, $scope);

      Response::json($out, ($out['ok'] ?? false) ? 200 : 422);
   }


   private function json(Request $r): array
   {
      $raw = file_get_contents('php://input') ?: '';
      $j   = json_decode($raw, true);

      if (is_array($j)) {
         return $j;
      }

      // fallback a $r->post si es array
      if (isset($r->post) && is_array($r->post)) {
         return $r->post;
      }

      // último fallback: array vacío
      return [];
   }
}
