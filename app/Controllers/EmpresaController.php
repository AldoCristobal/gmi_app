<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Services\EmpresaService;

final class EmpresaController
{
   public function __construct(
      private EmpresaService $svc = new EmpresaService()
   ) {}

   // ---------- CRUD BÁSICO ----------

   public function index(Request $req): void
   {
      $scope = $req->attr('scope') ?? [];

      $q = [
         'page'           => $req->get['page']           ?? 1,
         'size'           => $req->get['size']           ?? 20,
         'q'              => $req->get['q']              ?? '',
         'area_id'        => $req->get['area_id']        ?? null,
         'responsable_id' => $req->get['responsable_id'] ?? null,
         'activo'         => $req->get['activo']         ?? null,
      ];

      $out = $this->svc->list($q, $scope);
      Response::json($out, ($out['ok'] ?? false) ? 200 : 422);
   }

   public function show(Request $req): void
   {
      $scope = $req->attr('scope') ?? [];
      $id    = (int)($req->get['id'] ?? 0);

      if ($id <= 0) {
         Response::json(
            ['ok' => false, 'error' => ['code' => 'VALIDATION', 'message' => 'id requerido']],
            400
         );
         return;
      }

      $out = $this->svc->show($id, $scope);
      Response::json($out, ($out['ok'] ?? false) ? 200 : 404);
   }

   public function store(Request $req): void
   {
      $in  = $this->json($req);
      $out = $this->svc->create($in);
      Response::json($out, ($out['ok'] ?? false) ? 200 : 422);
   }

   public function update(Request $req): void
   {
      $scope = $req->attr('scope') ?? [];
      $in    = $this->json($req);
      $id    = (int)($in['id'] ?? 0);

      if ($id <= 0) {
         Response::json(
            ['ok' => false, 'error' => ['code' => 'VALIDATION', 'message' => 'id requerido']],
            400
         );
         return;
      }

      $out = $this->svc->update($id, $in, $scope);
      Response::json($out, ($out['ok'] ?? false) ? 200 : 422);
   }

   public function destroy(Request $req): void
   {
      $scope = $req->attr('scope') ?? [];

      // acepta ?id= en query o en body JSON
      $id = (int)($req->get['id'] ?? 0);
      if ($id <= 0) {
         $in = $this->json($req);
         $id = (int)($in['id'] ?? 0);
      }

      if ($id <= 0) {
         Response::json(
            ['ok' => false, 'error' => ['code' => 'VALIDATION', 'message' => 'id requerido']],
            400
         );
         return;
      }

      $out = $this->svc->delete($id, $scope);
      Response::json($out, ($out['ok'] ?? false) ? 200 : 404);
   }

   // ---------- EXPEDIENTE ----------

   /** GET /api/v1/empresas/expediente?id={empresaId} */
   public function expedienteList(Request $req): void
   {
      $scope     = $req->attr('scope') ?? [];
      $empresaId = (int)($req->get['id'] ?? 0);

      if ($empresaId <= 0) {
         Response::json(
            ['ok' => false, 'error' => ['code' => 'VALIDATION', 'message' => 'id requerido']],
            400
         );
         return;
      }

      $out = $this->svc->expedienteList($empresaId, $scope);
      Response::json($out, ($out['ok'] ?? false) ? 200 : 403);
   }

   /**
    * POST /api/v1/empresas/expediente
    * Acepta multipart.
    * Campos: empresa_id, tipo_id | tipo_clave, files[] (o file único de FilePond → se normaliza a files[])
    */
   public function expedienteUpload(Request $req): void
   {
      $scope     = $req->attr('scope') ?? [];
      $empresaId = (int)($_POST['empresa_id'] ?? 0);

      if ($empresaId <= 0) {
         Response::json(
            ['ok' => false, 'error' => ['code' => 'VALIDATION', 'message' => 'empresa_id requerido']],
            400
         );
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

      $input = [
         'tipo_id'    => isset($_POST['tipo_id']) ? (int)$_POST['tipo_id'] : null,
         'tipo_clave' => $_POST['tipo_clave']     ?? null,
      ];

      $out = $this->svc->expedienteUpload($empresaId, $scope, $files, $input);
      Response::json($out, ($out['ok'] ?? false) ? 200 : 422);
   }

   /** GET /api/v1/empresas/expediente/versions?empresa_id=&tipo_id= */
   public function expedienteVersions(Request $req): void
   {
      $scope     = $req->attr('scope') ?? [];
      $empresaId = (int)($req->get['empresa_id'] ?? 0);
      $tipoId    = (int)($req->get['tipo_id']    ?? 0);
      $limit     = isset($req->get['limit']) ? (int)$req->get['limit'] : null;

      if ($empresaId <= 0 || $tipoId <= 0) {
         Response::json(
            ['ok' => false, 'error' => ['code' => 'VALIDATION', 'message' => 'empresa_id y tipo_id requeridos']],
            400
         );
         return;
      }

      if ($limit && $limit > 0) {
         $out = $this->svc->expedienteLatest($empresaId, $tipoId, $limit, $scope);
         Response::json($out, ($out['ok'] ?? false) ? 200 : 403);
         return;
      }

      // listado completo
      $out = $this->svc->expedienteVersions($empresaId, $tipoId, $scope);
      Response::json($out, ($out['ok'] ?? false) ? 200 : 403);
   }

   /** GET /api/v1/empresas/expediente/download?empresa_id=&doc_id= */
   public function expedienteDownload(Request $req): void
   {
      $scope     = $req->attr('scope') ?? [];
      $empresaId = (int)($req->get['empresa_id'] ?? 0);
      $docId     = (int)($req->get['doc_id']     ?? 0);
      $stream    = (isset($req->get['stream']) && (string)$req->get['stream'] === '1');

      if ($empresaId <= 0 || $docId <= 0) {
         Response::json(
            ['ok' => false, 'error' => ['code' => 'VALIDATION', 'message' => 'empresa_id y doc_id requeridos']],
            400
         );
         return;
      }

      // Si stream==1, el Service hace headers + readfile + exit
      $out = $this->svc->expedienteDownload($empresaId, $docId, $scope, $stream);
      if ($stream) {
         return; // ya se envió el archivo
      }

      Response::json($out, ($out['ok'] ?? false) ? 200 : 404);
   }

   // ---------- CIF ----------

   /** POST /api/v1/empresas/cif/upload (FilePond o input simple) */
   public function cifUpload(Request $req): void
   {
      $scope     = $req->attr('scope') ?? [];
      $empresaId = (int)($_POST['empresa_id'] ?? 0);

      if ($empresaId <= 0) {
         Response::json(
            ['ok' => false, 'error' => ['code' => 'VALIDATION', 'message' => 'empresa_id requerido']],
            400
         );
         return;
      }

      // Normaliza FilePond -> files[]
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

      $out = $this->svc->cifUpload($empresaId, $scope, $files);
      Response::json($out, ($out['ok'] ?? false) ? 200 : 422);
   }

   /** POST /api/v1/empresas/cif/parse  body: { empresa_id, doc_id } */
   public function cifParse(Request $req): void
   {
      $scope     = $req->attr('scope') ?? [];
      $in        = $this->json($req);
      $empresaId = (int)($in['empresa_id'] ?? 0);
      $docId     = (int)($in['doc_id']     ?? 0);

      if ($empresaId <= 0 || $docId <= 0) {
         Response::json(
            ['ok' => false, 'error' => ['code' => 'VALIDATION', 'message' => 'empresa_id y doc_id requeridos']],
            400
         );
         return;
      }

      $out = $this->svc->cifParse($empresaId, $docId, $scope);
      Response::json($out, ($out['ok'] ?? false) ? 200 : 422);
   }

   /** POST /api/v1/empresas/cif/apply  body: { empresa_id, doc_id, campos:{...} } */
   public function cifApply(Request $req): void
   {
      $scope     = $req->attr('scope') ?? [];
      $in        = $this->json($req);
      $empresaId = (int)($in['empresa_id'] ?? 0);
      $docId     = (int)($in['doc_id']     ?? 0);
      $campos    = is_array($in['campos'] ?? null) ? $in['campos'] : [];

      if ($empresaId <= 0 || $docId <= 0) {
         Response::json(
            ['ok' => false, 'error' => ['code' => 'VALIDATION', 'message' => 'empresa_id y doc_id requeridos']],
            400
         );
         return;
      }

      $out = $this->svc->cifApply($empresaId, $docId, $campos, $scope);
      Response::json($out, ($out['ok'] ?? false) ? 200 : 422);
   }

   // ---------- Helpers ----------

   private function json(Request $r): array
   {
      $raw = file_get_contents('php://input') ?: '';
      $j   = json_decode($raw, true);
      return is_array($j) ? $j : $r->post;
   }

   /**
    * Endpoint específico del PANEL: /api/v1/empresas/expediente-panel?empresa_id=XX
    * Usa el flujo nuevo que trae empresa completa + docs última versión
    */
   public function expedientePanelList(Request $req): void
   {
      $scope = $req->attr('scope') ?? [];

      // Si viene empresa_id => modo DETALLE (para el modal)
      $empresaId = (int)($req->get['empresa_id'] ?? 0);
      if ($empresaId > 0) {
         // Usa el método que ya tienes en el Service para detalle
         $out = $this->svc->expedientePanelList($empresaId, $scope);
         Response::json($out, ($out['ok'] ?? false) ? 200 : 403);
         return;
      }

      // Si NO viene empresa_id => modo LISTA (tarjetas del panel)
      $q = [
         'page'           => $req->get['page']           ?? 1,
         'size'           => $req->get['size']           ?? 12,
         'q'              => $req->get['q']              ?? '',
         'area_id'        => $req->get['area_id']        ?? null,
         'responsable_id' => $req->get['responsable_id'] ?? null,
         'solo_con_docs'  => $req->get['solo_con_docs']  ?? null,
      ];

      $out = $this->svc->expedientePanelIndex($q, $scope);
      Response::json($out, ($out['ok'] ?? false) ? 200 : 422);
   }
}
