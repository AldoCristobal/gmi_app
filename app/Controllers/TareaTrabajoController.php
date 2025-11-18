<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Services\TareaTrabajoService;

final class TareaTrabajoController
{
   public function __construct(
      private TareaTrabajoService $svc = new TareaTrabajoService()
   ) {}

   /**
    * GET /api/v1/tareas
    * Lista tareas para el usuario actual (Mis tareas) o, según el rol,
    * permite filtros más amplios (para supervisores/gerentes).
    */
   public function index(Request $req): void
   {
      $scope = $req->attr('scope') ?? [];
      $user  = $req->attr('user')  ?? [];

      $q = [
         'page'  => $req->get['page']  ?? null,
         'size'  => $req->get['size']  ?? null,
         'estado'        => $req->get['estado']        ?? null,
         'tipo_tarea'    => $req->get['tipo_tarea']    ?? null,
         'empresa_id'    => $req->get['empresa_id']    ?? null,
         'responsable_id' => $req->get['responsable_id'] ?? null,
         'fec_obj_desde' => $req->get['fec_obj_desde'] ?? null,
         'fec_obj_hasta' => $req->get['fec_obj_hasta'] ?? null,
         'fec_venc_desde' => $req->get['fec_venc_desde'] ?? null,
         'fec_venc_hasta' => $req->get['fec_venc_hasta'] ?? null,
      ];

      $out = $this->svc->listar($q, $user, $scope);
      Response::json($out, ($out['ok'] ?? false) ? 200 : 422);
   }

   /**
    * GET /api/v1/tareas/show?id=123
    * Detalle de una tarea.
    */
   public function show(Request $req): void
   {
      $scope = $req->attr('scope') ?? [];
      $user  = $req->attr('user')  ?? [];

      $id = (int)($req->get['id'] ?? 0);
      if ($id <= 0) {
         Response::json([
            'ok'    => false,
            'error' => ['code' => 'VALIDATION', 'message' => 'id requerido']
         ], 400);
         return;
      }

      $out = $this->svc->show($id, $user, $scope);

      // NOT_FOUND -> 404, resto 200/422
      if (($out['ok'] ?? false) === false && ($out['error']['code'] ?? '') === 'NOT_FOUND') {
         Response::json($out, 404);
         return;
      }

      Response::json($out, ($out['ok'] ?? false) ? 200 : 422);
   }

   /**
    * PATCH /api/v1/tareas/estado
    * body: { id, estado, observaciones? }
    *
    * Desde "Mis tareas", el auxiliar puede:
    *  - PENDIENTE <-> EN_REVISION
    */
   public function estado(Request $req): void
   {
      $scope = $req->attr('scope') ?? [];
      $user  = $req->attr('user')  ?? [];

      $in = $this->json($req);

      $id     = (int)($in['id']      ?? 0);
      $estado = (string)($in['estado'] ?? '');
      $obs    = isset($in['observaciones']) ? (string)$in['observaciones'] : null;

      if ($id <= 0 || $estado === '') {
         Response::json([
            'ok'    => false,
            'error' => ['code' => 'VALIDATION', 'message' => 'id y estado son requeridos']
         ], 400);
         return;
      }

      $out = $this->svc->cambiarEstado($id, $estado, $user, $scope, $obs);
      Response::json($out, ($out['ok'] ?? false) ? 200 : 422);
   }

   /**
    * Helper para leer JSON del cuerpo.
    */
   private function json(Request $r): array
   {
      $raw = file_get_contents('php://input') ?: '';
      $j = json_decode($raw, true);
      return is_array($j) ? $j : $r->post;
   }
}
