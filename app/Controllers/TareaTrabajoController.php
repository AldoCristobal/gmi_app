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
    */
   public function index(Request $req): void
   {
      $user  = $req->attr('user') ?? [];
      $scope = $req->attr('scope') ?? [];

      $filters = [];

      if (!empty($req->get['estado'])) {
         $filters['estado'] = (string)$req->get['estado'];
      }
      if (!empty($req->get['tipo_tarea'])) {
         $filters['tipo_tarea'] = (string)$req->get['tipo_tarea'];
      }
      if (!empty($req->get['empresa_id'])) {
         $filters['empresa_id'] = (int)$req->get['empresa_id'];
      }

      $out = $this->svc->list($filters, $user, $scope);

      Response::json($out, ($out['ok'] ?? false) ? 200 : 422);
   }

   /**
    * GET /api/v1/tareas/show?id=123
    */
   public function show(Request $req): void
   {
      $user  = $req->attr('user') ?? [];
      $scope = $req->attr('scope') ?? [];

      $id = (int)($req->get['id'] ?? 0);
      if ($id <= 0) {
         Response::json([
            'ok' => false,
            'error' => ['code' => 'VALIDATION', 'message' => 'id requerido']
         ], 400);
         return;
      }

      $out = $this->svc->show($id, $user, $scope);
      Response::json($out, ($out['ok'] ?? false) ? 200 : 404);
   }

   /**
    * PATCH /api/v1/tareas/estado
    * body: { id, estado, observaciones? }
    */
   public function estado(Request $req): void
   {
      $user  = $req->attr('user') ?? [];
      $scope = $req->attr('scope') ?? [];

      $raw = file_get_contents('php://input') ?: '';
      $in  = json_decode($raw, true);
      if (!is_array($in)) {
         $in = $req->post;
      }

      $id     = (int)($in['id'] ?? 0);
      $estado = (string)($in['estado'] ?? '');
      $obs    = isset($in['observaciones']) ? (string)$in['observaciones'] : null;

      if ($id <= 0 || $estado === '') {
         Response::json([
            'ok' => false,
            'error' => ['code' => 'VALIDATION', 'message' => 'id y estado requeridos']
         ], 400);
         return;
      }

      $out = $this->svc->changeStatus($id, $estado, $user, $scope, $obs);
      Response::json($out, ($out['ok'] ?? false) ? 200 : 422);
   }
}
