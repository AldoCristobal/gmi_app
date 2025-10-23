<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Services\RevisionDocumentoService;

final class RevisionDocumentoController
{
   public function __construct(
      private RevisionDocumentoService $svc = new RevisionDocumentoService()
   ) {}

   /**
    * GET /api/v1/revisiones/documentos?revision_id=
    * Lista documentos (inicial + anexos) de una revisión (solo lectura)
    */
   public function list(Request $req): void
   {
      $scope      = $req->attr('scope') ?? [];
      $revisionId = (int)($req->get['revision_id'] ?? 0);

      if ($revisionId <= 0) {
         Response::json(['ok' => false, 'error' => ['code' => 'VALIDATION', 'message' => 'revision_id requerido']], 400);
         return;
      }

      $out = $this->svc->list($revisionId, $scope);
      Response::json($out, ($out['ok'] ?? false) ? 200 : 403);
   }

   /**
    * POST /api/v1/revisiones/documentos
    * Subida de anexos (multipart). Campos:
    *  - revision_id (int, requerido)
    *  - file o files[] (requerido; admite FilePond -> normalizamos a files[])
    */
   public function upload(Request $req): void
   {
      $scope      = $req->attr('scope') ?? [];
      $userId     = (int)($scope['user_id'] ?? 0);
      $revisionId = (int)($_POST['revision_id'] ?? 0);

      if ($revisionId <= 0) {
         Response::json(['ok' => false, 'error' => ['code' => 'VALIDATION', 'message' => 'revision_id requerido']], 400);
         return;
      }

      // Normaliza FilePond (envía "file") a "files[]"
      $files = $_FILES ?? [];
      if (!empty($files['file']) && empty($files['files'])) {
         $files['files'] = [
            'name'     => [$files['file']['name']     ?? null],
            'type'     => [$files['file']['type']     ?? null],
            'tmp_name' => [$files['file']['tmp_name'] ?? null],
            'error'    => [$files['file']['error']    ?? UPLOAD_ERR_NO_FILE],
            'size'     => [$files['file']['size']     ?? 0],
         ];
      }

      $out = $this->svc->upload($revisionId, $files, $userId, $scope);
      Response::json($out, ($out['ok'] ?? false) ? 200 : 422);
   }

   /**
    * GET /api/v1/revisiones/documentos/download?revision_id=&doc_id=
    * Descarga/abre un documento (stream binario). Si hay error, regresa JSON.
    */
   public function download(Request $req): void
   {
      $scope      = $req->attr('scope') ?? [];
      $revisionId = (int)($req->get['revision_id'] ?? 0);
      $docId      = (int)($req->get['doc_id'] ?? 0);

      if ($revisionId <= 0 || $docId <= 0) {
         Response::json(['ok' => false, 'error' => ['code' => 'VALIDATION', 'message' => 'revision_id y doc_id requeridos']], 400);
         return;
      }

      // stream=true -> el Service hará headers + readfile + exit
      $out = $this->svc->download($revisionId, $docId, $scope, true);
      if ($out === null) {
         // stream exitoso: ya se envió el archivo y se hizo exit;
         return;
      }

      // Si no se hizo stream (por error), responde JSON
      $status = 200;
      if (!($out['ok'] ?? false)) {
         $code = $out['error']['code'] ?? '';
         $status = ($code === 'FORBIDDEN') ? 403 : (($code === 'NOT_FOUND') ? 404 : 422);
      }
      Response::json($out, $status);
   }

   /**
    * DELETE /api/v1/revisiones/documentos
    * Body JSON: { revision_id, doc_id }
    */
   public function delete(Request $req): void
   {
      $scope = $req->attr('scope') ?? [];
      $raw = file_get_contents('php://input') ?: '';
      $in  = json_decode($raw, true) ?: $req->post;

      $revisionId = (int)($in['revision_id'] ?? 0);
      $docId      = (int)($in['doc_id'] ?? 0);

      if ($revisionId <= 0 || $docId <= 0) {
         Response::json(['ok' => false, 'error' => ['code' => 'VALIDATION', 'message' => 'revision_id y doc_id requeridos']], 400);
         return;
      }

      $out = $this->svc->delete($revisionId, $docId, $scope);
      Response::json($out, ($out['ok'] ?? false) ? 200 : 422);
   }

   /**
    * PATCH /api/v1/revisiones/documentos/inicial
    * Reemplaza la evidencia inicial (subida directa multipart).
    * Campos: revision_id, file (obligatorio)
    */
   public function replaceInitial(Request $req): void
   {
      $scope      = $req->attr('scope') ?? [];
      $userId     = (int)($scope['user_id'] ?? 0);
      $revisionId = (int)($_POST['revision_id'] ?? 0);

      if ($revisionId <= 0) {
         Response::json(['ok' => false, 'error' => ['code' => 'VALIDATION', 'message' => 'revision_id requerido']], 400);
         return;
      }

      $files = $_FILES ?? [];
      if (empty($files['file']) && empty($files['files'])) {
         Response::json(['ok' => false, 'error' => ['code' => 'VALIDATION', 'message' => 'Sin archivo']], 422);
         return;
      }
      // Normaliza a files[]
      if (!empty($files['file']) && empty($files['files'])) {
         $files['files'] = [
            'name'     => [$files['file']['name']     ?? null],
            'type'     => [$files['file']['type']     ?? null],
            'tmp_name' => [$files['file']['tmp_name'] ?? null],
            'error'    => [$files['file']['error']    ?? UPLOAD_ERR_NO_FILE],
            'size'     => [$files['file']['size']     ?? 0],
         ];
      }

      $out = $this->svc->replaceInitial($revisionId, $files, $userId, $scope);
      Response::json($out, ($out['ok'] ?? false) ? 200 : 422);
   }
}
