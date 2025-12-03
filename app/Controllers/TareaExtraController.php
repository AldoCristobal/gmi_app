<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Services\TareaExtraService;

final class TareaExtraController
{
   public function __construct(
      private TareaExtraService $svc = new TareaExtraService()
   ) {}

   /** GET /api/v1/tareas/extra */
   // App\Controllers\TareaExtraController.php

   public function index(Request $req): void
   {
      // 👇 Igual que en TareaTrabajoController
      $user  = $req->attr('user') ?? [];
      $scope = $req->attr('scope') ?? [];

      $filters = [];

      if (!empty($req->get['estado'])) {
         $filters['estado'] = (string)$req->get['estado'];
      }

      if (!empty($req->get['empresa_id'])) {
         $filters['empresa_id'] = (int)$req->get['empresa_id'];
      }

      if (!empty($req->get['responsable_id'])) {
         $filters['responsable_id'] = (int)$req->get['responsable_id'];
      }

      // Usa el service igual que Mis Tareas
      $out = $this->svc->list($filters, $user, $scope);

      Response::json($out, ($out['ok'] ?? false) ? 200 : 422);
   }



   /** POST /api/v1/tareas/extra */
   public function store(Request $req): void
   {
      $input = $this->json($req);
      $out   = $this->svc->create($input);

      $status = ($out['ok'] ?? false) ? 200 : 422;
      Response::json($out, $status);
   }

   /** PUT /api/v1/tareas/extra */
   public function update(Request $req): void
   {
      $input = $this->json($req);
      $id    = (int)($input['id'] ?? 0);

      if ($id <= 0) {
         Response::json([
            'ok'    => false,
            'error' => ['code' => 'VALIDATION', 'message' => 'id required'],
         ], 400);
         return;
      }

      // 👇 Aquí el cambio
      $user  = $req->attr('user')  ?? [];
      $scope = $req->attr('scope') ?? [];

      $out = $this->svc->update($id, $input, $user, $scope);

      $code   = $out['error']['code'] ?? '';
      $status = ($out['ok'] ?? false)
         ? 200
         : ($code === 'NOT_FOUND' ? 404 : 422);

      Response::json($out, $status);
   }

   public function destroy(Request $req): void
   {
      $input = $this->json($req);
      $id    = (int)($input['id'] ?? 0);

      if ($id <= 0) {
         Response::json([
            'ok'    => false,
            'error' => ['code' => 'VALIDATION', 'message' => 'id required'],
         ], 400);
         return;
      }

      // 👇 Aquí también
      $user  = $req->attr('user')  ?? [];
      $scope = $req->attr('scope') ?? [];

      $out = $this->svc->delete($id, $user, $scope);

      $code   = $out['error']['code'] ?? '';
      $status = ($out['ok'] ?? false)
         ? 200
         : ($code === 'NOT_FOUND' ? 404 : 422);

      Response::json($out, $status);
   }


   private function json(Request $r): array
   {
      $raw = file_get_contents('php://input') ?: '';
      $j   = json_decode($raw, true);

      return is_array($j)
         ? $j
         : ($r->post ?? []);
   }
}
