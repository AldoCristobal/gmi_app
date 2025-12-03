<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Services\RevisionService;

final class RevisionController
{
   public function __construct(private RevisionService $svc = new RevisionService()) {}

   public function index(Request $req): void
   {
      $scope = $req->attr('scope') ?? [];
      $q = [
         'page' => $req->get['page'] ?? 1,
         'size' => $req->get['size'] ?? 20,
         'q'    => $req->get['q'] ?? ''
      ];
      foreach (
         [
            'tipo_revision_id',
            'estatus',
            'riesgo',
            'area_id',
            'responsable_id',
            'fecha_notificacion_desde',
            'fecha_notificacion_hasta',
            'fecha_venc_desde',
            'fecha_venc_hasta'
         ] as $k
      ) {
         if (isset($req->get[$k])) {
            $q[$k] = $req->get[$k];
         }
      }

      $out = $this->svc->listar($q, $scope);
      Response::json($out, ($out['ok'] ?? false) ? 200 : 422);
   }

   public function show(Request $req): void
   {
      $scope = $req->attr('scope') ?? [];
      $id = (int)($req->get['id'] ?? 0);
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
      $in = $this->json($req);

      // Inyecta metadatos mínimos del usuario para validación de alcance en creación
      $user = $req->attr('user') ?? [];
      $in['__user_role'] = $user['role']    ?? null;
      $in['__user_id']   = $user['id']      ?? null;
      $in['__user_area'] = $user['area_id'] ?? null;

      $out = $this->svc->crear($in);
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

      $out = $this->svc->actualizar($id, $in, $scope);
      Response::json($out, ($out['ok'] ?? false) ? 200 : 422);
   }

   public function destroy(Request $req): void
   {
      $scope = $req->attr('scope') ?? [];
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
      $out = $this->svc->borrar($id, $scope);
      Response::json($out, ($out['ok'] ?? false) ? 200 : 404);
   }

   /** PATCH /api/v1/revisiones/estatus  body: { id, estatus } */
   public function estatus(Request $req): void
   {
      $scope   = $req->attr('scope') ?? [];
      $in      = $this->json($req);
      $id      = (int)($in['id'] ?? 0);
      $estatus = (string)($in['estatus'] ?? '');

      if ($id <= 0 || $estatus === '') {
         Response::json(
            ['ok' => false, 'error' => ['code' => 'VALIDATION', 'message' => 'id y estatus requeridos']],
            400
         );
         return;
      }

      $out = $this->svc->cambiarEstatus($id, $estatus, $scope);
      Response::json($out, ($out['ok'] ?? false) ? 200 : 422);
   }

   private function json(Request $r): array
   {
      $raw = file_get_contents('php://input') ?: '';
      $j   = json_decode($raw, true);
      return is_array($j) ? $j : $r->post;
   }
}
