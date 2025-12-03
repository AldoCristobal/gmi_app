<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Services\TareaEvaluacionService;

final class TareaEvaluacionController
{
   public function __construct(
      private TareaEvaluacionService $service = new TareaEvaluacionService(),
   ) {}

   /**
    * GET /api/v1/tareas/evaluacion
    */
   public function index(Request $req): void
   {
      $user  = $req->attr('user')  ?? [];
      $scope = $req->attr('scope') ?? [];
      $q     = $req->get ?? [];

      $res = $this->service->listTasks($q, $user, $scope);
      Response::json($res, $res['ok'] ? 200 : 400);
   }

   /**
    * GET /api/v1/tareas/evaluacion/show?tarea_id=123
    */
   public function show(Request $req): void
   {
      $user    = $req->attr('user')  ?? [];
      $scope   = $req->attr('scope') ?? [];
      $tareaId = (int)($req->get['tarea_id'] ?? 0);

      if ($tareaId <= 0) {
         Response::json([
            'ok'    => false,
            'error' => ['code' => 'VALIDATION', 'message' => 'tarea_id requerido'],
         ], 400);
         return;
      }

      $res = $this->service->detail($tareaId, $user, $scope);
      Response::json($res, $res['ok'] ? 200 : 403);
   }

   /**
    * POST /api/v1/tareas/evaluacion
    * body form-data: tarea_id, estado_nuevo, comentario?
    */
   public function store(Request $req): void
   {
      $user  = $req->attr('user')  ?? [];
      $scope = $req->attr('scope') ?? [];

      // Viene de FormData → $_POST / $req->post
      $body = $req->post ?? [];

      $res = $this->service->store($body, $user, $scope);
      Response::json($res, $res['ok'] ? 200 : 400);
   }

   /**
    * GET /api/v1/tareas/evaluacion/history
    */
   public function history(Request $req): void
   {
      $q   = $req->get ?? [];
      $res = $this->service->history($q);

      Response::json($res, 200);
   }
}
