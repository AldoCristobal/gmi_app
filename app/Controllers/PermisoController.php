<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Services\PermisoService;

final class PermisoController
{
   /** @var PermisoService */
   private PermisoService $svc;

   public function __construct()
   {
      // Evita usar "new" como valor por defecto en la firma del constructor
      $this->svc = new PermisoService();
   }

   /** GET /api/v1/permisos?q=... */
   public function index(Request $r): void
   {
      $q = trim((string)($r->get['q'] ?? ''));
      $res = $this->svc->listar($q);
      Response::json($this->httpify($res), $this->statusOf($res));
   }

   /** POST /api/v1/permisos  body: { clave:string, descripcion?:string } */
   public function store(Request $r): void
   {
      $in  = $this->json($r);
      $res = $this->svc->crear($in);
      Response::json($this->httpify($res), $res['ok'] ? 201 : $this->statusOf($res));
   }

   /** PUT /api/v1/permisos/{id}  body: { clave:string, descripcion?:string } */
   public function update(Request $r, int $id): void
   {
      $in      = $this->json($r);
      $in['id'] = $id; // normalizamos para el service
      $res     = $this->svc->actualizar($in);
      Response::json($this->httpify($res), $this->statusOf($res));
   }

   /** DELETE /api/v1/permisos/{id} */
   public function destroy(Request $r, int $id): void
   {
      $res = $this->svc->eliminar($id);
      Response::json($this->httpify($res), $this->statusOf($res));
   }

   /* -------- Helpers -------- */

   private function json(Request $r): array
   {
      $raw = file_get_contents('php://input') ?: '';
      $j = json_decode($raw, true);
      return is_array($j) ? $j : $r->post;
   }

   private function httpify(array $res): array
   {
      if (($res['ok'] ?? false) === true) return $res;
      if (!isset($res['code']) && isset($res['error']['code'])) {
         $res['code'] = $res['error']['code'];
      }
      if (!isset($res['msg']) && isset($res['error']['message'])) {
         $res['msg'] = $res['error']['message'];
      }
      unset($res['error']);
      return $res;
   }

   private function statusOf(array $res): int
   {
      if (($res['ok'] ?? false) === true) return 200;
      return match ($res['code'] ?? 'SERVER') {
         'VALIDATION' => 422,
         'CONFLICT'   => 409,
         'FORBIDDEN'  => 403,
         'NOT_FOUND'  => 404,
         default      => 500,
      };
   }
}
