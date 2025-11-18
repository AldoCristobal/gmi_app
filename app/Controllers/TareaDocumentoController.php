<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Services\TareaDocumentoService;

final class TareaDocumentoController
{
   public function __construct(
      private TareaDocumentoService $svc = new TareaDocumentoService()
   ) {}

   /** GET /api/v1/tareas/documentos?tarea_id=123 */
   public function list(Request $req): void
   {
      $user  = $req->attr('user') ?? [];
      $scope = $req->attr('scope') ?? [];

      $tareaId = (int)($req->get['tarea_id'] ?? 0);
      if ($tareaId <= 0) {
         Response::json([
            'ok' => false,
            'error' => ['code' => 'VALIDATION', 'message' => 'tarea_id requerido']
         ], 400);
         return;
      }

      $out = $this->svc->listar($tareaId, $user, $scope);
      Response::json($out, ($out['ok'] ?? false) ? 200 : 422);
   }

   /** POST /api/v1/tareas/documentos  (multipart: tarea_id, files[]) */
   public function upload(Request $req): void
   {
      $user  = $req->attr('user') ?? [];
      $scope = $req->attr('scope') ?? [];

      $tareaId = (int)($req->post['tarea_id'] ?? 0);
      if ($tareaId <= 0) {
         Response::json([
            'ok' => false,
            'error' => ['code' => 'VALIDATION', 'message' => 'tarea_id requerido']
         ], 400);
         return;
      }

      $files = $_FILES['files'] ?? null;
      if (!$files) {
         Response::json([
            'ok' => false,
            'error' => ['code' => 'VALIDATION', 'message' => 'files[] requerido']
         ], 400);
         return;
      }

      $out = $this->svc->subir($tareaId, $user, $scope, $files);
      Response::json($out, ($out['ok'] ?? false) ? 200 : 422);
   }

   /** GET /api/v1/tareas/documentos/download?id=10 */
   public function download(Request $req): void
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

      $this->svc->download($id, $user, $scope);
   }

   /** DELETE /api/v1/tareas/documentos?id=10 */
   public function delete(Request $req): void
   {
      $user  = $req->attr('user') ?? [];
      $scope = $req->attr('scope') ?? [];

      $id = (int)($req->get['id'] ?? 0);
      if ($id <= 0) {
         $in = $req->post;
         $id = (int)($in['id'] ?? 0);
      }

      if ($id <= 0) {
         Response::json([
            'ok' => false,
            'error' => ['code' => 'VALIDATION', 'message' => 'id requerido']
         ], 400);
         return;
      }

      $out = $this->svc->eliminar($id, $user, $scope);
      Response::json($out, ($out['ok'] ?? false) ? 200 : 422);
   }
}
