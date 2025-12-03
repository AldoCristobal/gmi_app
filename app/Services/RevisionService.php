<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\RevisionRepository;
use App\Repositories\UserRepository;

final class RevisionService
{
   public function __construct(
      private RevisionRepository $repo = new RevisionRepository(),
      private UserRepository $userRepo = new UserRepository()
   ) {}

   // --------- Listado / Detalle ----------

   public function listar(array $q, array $scope): array
   {
      $scope = $this->applyTeamScope($scope);

      $page = max(1, (int)($q['page'] ?? 1));
      $size = min(200, max(1, (int)($q['size'] ?? 20)));

      $filters = [
         'q' => trim((string)($q['q'] ?? '')),
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
         if (isset($q[$k])) {
            $filters[$k] = $q[$k];
         }
      }

      $res = $this->repo->list($filters, $scope, $page, $size);
      return [
         'ok'   => true,
         'data' => $res['rows'],
         'meta' => [
            'page'  => $page,
            'size'  => $size,
            'total' => $res['total'],
         ],
      ];
   }

   public function show(int $id, array $scope): array
   {
      $scope = $this->applyTeamScope($scope);

      $row = $this->repo->findVisible($id, $scope);
      if (!$row) {
         return [
            'ok'    => false,
            'error' => ['code' => 'NOT_FOUND', 'message' => 'Revisión no visible o inexistente'],
         ];
      }

      $docs = $this->repo->docsDeRevision($id);
      $bita = $this->repo->bitacoraList($id, 20, 0);

      $inicial = null;
      $anexos  = [];
      foreach ($docs as $d) {
         ((int)$d['is_inicial'] === 1) ? $inicial = $d : $anexos[] = $d;
      }

      return [
         'ok'   => true,
         'data' => [
            'revision'   => $row,
            'documentos' => [
               'inicial' => $inicial,
               'anexos'  => $anexos,
            ],
            'bitacora'   => $bita,
         ],
      ];
   }

   // --------- Crear / Actualizar / Borrar ----------

   public function crear(array $in): array
   {
      $v = $this->validar($in, false);
      if ($v !== true) {
         return $v;
      }

      if (empty($in['evidencia_inicial_token'])) {
         return [
            'ok'    => false,
            'error' => [
               'code'    => 'VALIDATION',
               'message' => 'evidencia_inicial_token requerido',
            ],
         ];
      }

      // Reglas de scope en creación (las dejo tal cual las tenías)
      if (!empty($in['__user_role']) && $in['__user_role'] === 'auxiliar') {
         if ((int)$in['responsable_id'] !== (int)$in['__user_id'] || (int)$in['area_id'] !== (int)$in['__user_area']) {
            return [
               'ok'    => false,
               'error' => [
                  'code'    => 'FORBIDDEN',
                  'message' => 'Fuera de alcance (auxiliar)',
               ],
            ];
         }
      }
      if (!empty($in['__user_role']) && $in['__user_role'] === 'supervisor') {
         if ((int)$in['area_id'] !== (int)$in['__user_area']) {
            return [
               'ok'    => false,
               'error' => [
                  'code'    => 'FORBIDDEN',
                  'message' => 'Área fuera de alcance (supervisor)',
               ],
            ];
         }
      }

      // Insert principal (estatus forzado a en_proceso)
      $id = $this->repo->create([
         ':nombre'             => trim($in['nombre']),
         ':numero_orden'       => $in['numero_orden'] ?? null,
         ':numero_oficio'      => $in['numero_oficio'] ?? null,
         ':ejercicio'          => $in['ejercicio'] ?? null,
         ':fecha_notificacion' => $in['fecha_notificacion'],
         ':fecha_vencimiento'  => $in['fecha_vencimiento'],
         ':tipo_revision_id'   => (int)$in['tipo_revision_id'],
         ':tipo_impuesto'      => $in['tipo_impuesto'] ?? null,
         ':dependencia'        => $in['dependencia'] ?? null,
         ':antecedente'        => $in['antecedente'] ?? null,
         ':estatus'            => 'en_proceso',               // FORZADO
         ':riesgo'             => $in['riesgo'] ?? 'medio',
         ':observaciones'      => $in['observaciones'] ?? null,
         ':area_id'            => (int)$in['area_id'],
         ':responsable_id'     => (int)$in['responsable_id'],
         ':created_by'         => (int)($in['__user_id'] ?? 0),
      ]);

      // Mover archivo temporal → definitivo y registrar doc inicial
      $moved = $this->consumeTemp($in['evidencia_inicial_token'], "/uploads/revisiones/{$id}");
      if (!$moved['ok']) {
         return $moved;
      }

      $docId = $this->repo->insertDoc([
         ':revision_id'     => $id,
         ':version'         => 1,
         ':is_inicial'      => 1,
         ':nombre_original' => $moved['data']['original'] ?? $moved['data']['filename'],
         ':archivo_path'    => $moved['data']['relpath'],
         ':mime'            => $moved['data']['mime'],
         ':size_bytes'      => $moved['data']['size'],
         ':uploaded_by'     => (int)($in['__user_id'] ?? 0),
      ]);

      $this->repo->bitacoraAppend(
         $id,
         'creacion',
         ['area_id' => $in['area_id'], 'responsable_id' => $in['responsable_id']],
         (int)($in['__user_id'] ?? 0)
      );
      $this->repo->bitacoraAppend(
         $id,
         'subida_doc',
         [
            'docId'   => $docId,
            'nombre'  => $moved['data']['original'] ?? $moved['data']['filename'],
            'is_inicial' => 1,
         ],
         (int)($in['__user_id'] ?? 0)
      );

      return [
         'ok'   => true,
         'data' => [
            'id'                => $id,
            'documento_inicial' => ['id' => $docId],
         ],
      ];
   }

   public function actualizar(int $id, array $in, array $scope): array
   {
      $scope = $this->applyTeamScope($scope);

      $rev = $this->repo->findVisible($id, $scope);
      if (!$rev) {
         return [
            'ok'    => false,
            'error' => [
               'code'    => 'FORBIDDEN',
               'message' => 'Revisión no visible',
            ],
         ];
      }

      $v = $this->validar($in, true, $rev);
      if ($v !== true) {
         return $v;
      }

      $set = $this->onlyUpdatable($in, $rev);
      $ok  = $this->repo->update($id, $set);
      if (!$ok) {
         return [
            'ok'    => false,
            'error' => ['code' => 'UPDATE_FAIL'],
         ];
      }

      $this->repo->bitacoraAppend(
         $id,
         'actualizacion',
         ['campos' => array_keys($set)],
         (int)($scope['user_id'] ?? 0)
      );

      return ['ok' => true];
   }

   public function borrar(int $id, array $scope): array
   {
      $scope = $this->applyTeamScope($scope);

      $rev = $this->repo->findVisible($id, $scope);
      if (!$rev) {
         return [
            'ok'    => false,
            'error' => ['code' => 'FORBIDDEN'],
         ];
      }

      $this->repo->delete($id);
      $this->repo->bitacoraAppend(
         $id,
         'borrado_revision',
         null,
         (int)($scope['user_id'] ?? 0)
      );

      return ['ok' => true];
   }

   /**
    * Cambiar estatus: SOLO se permite pasar a 'completa'.
    * (El alta siempre queda en 'en_proceso' y no se “revierte” por aquí.)
    */
   public function cambiarEstatus(int $id, string $estatus, array $scope): array
   {
      if ($estatus !== 'completa') {
         return [
            'ok'    => false,
            'error' => [
               'code'    => 'VALIDATION',
               'message' => 'Estatus no permitido',
            ],
         ];
      }

      $scope = $this->applyTeamScope($scope);

      $rev = $this->repo->findVisible($id, $scope);
      if (!$rev) {
         return [
            'ok'    => false,
            'error' => ['code' => 'FORBIDDEN'],
         ];
      }

      if (($rev['estatus'] ?? '') === 'completa') {
         return ['ok' => true]; // idempotente
      }

      // Solo actualiza la columna existente
      $ok = $this->repo->update($id, ['estatus' => 'completa']);
      if (!$ok) {
         return [
            'ok'    => false,
            'error' => ['code' => 'UPDATE_FAIL'],
         ];
      }

      $this->repo->bitacoraAppend(
         $id,
         'cambio_estatus',
         ['de' => $rev['estatus'], 'a' => 'completa'],
         (int)($scope['user_id'] ?? 0)
      );

      return ['ok' => true];
   }

   // --------- Validaciones / Helpers ----------

   private function validar(array &$in, bool $isUpdate, ?array $rev = null): true|array
   {
      $req = ['nombre', 'fecha_notificacion', 'fecha_vencimiento', 'tipo_revision_id', 'area_id', 'responsable_id'];
      if (!$isUpdate) {
         foreach ($req as $k) {
            if (!isset($in[$k]) || $in[$k] === '') {
               return [
                  'ok'    => false,
                  'error' => ['code' => 'VALIDATION', 'message' => "{$k} requerido"],
               ];
            }
         }
      }

      // Fechas coherentes
      if (isset($in['fecha_notificacion']) || isset($in['fecha_vencimiento']) || !$isUpdate) {
         $fn = $in['fecha_notificacion'] ?? ($rev['fecha_notificacion'] ?? null);
         $fv = $in['fecha_vencimiento']  ?? ($rev['fecha_vencimiento']  ?? null);
         if ($fn && $fv && $fv < $fn) {
            return [
               'ok'    => false,
               'error' => [
                  'code'    => 'VALIDATION',
                  'message' => 'fecha_vencimiento no puede ser menor que fecha_notificacion',
               ],
            ];
         }
      }

      // Riesgo válido (estatus ya no se valida aquí para create)
      if (isset($in['riesgo']) && !in_array($in['riesgo'], ['bajo', 'medio', 'alto'], true)) {
         return [
            'ok'    => false,
            'error' => ['code' => 'VALIDATION', 'message' => 'riesgo inválido'],
         ];
      }

      return true;
   }

   private function onlyUpdatable(array $in, array $rev): array
   {
      $allowed = [
         'nombre',
         'numero_orden',
         'numero_oficio',
         'ejercicio',
         'fecha_notificacion',
         'fecha_vencimiento',
         'tipo_revision_id',
         'tipo_impuesto',
         'dependencia',
         'antecedente',
         // 'estatus',  // YA NO SE PUEDE EDITAR POR UPDATE
         'riesgo',
         'observaciones',
         'area_id',
         'responsable_id',
      ];

      $out = [];
      foreach ($allowed as $k) {
         if (array_key_exists($k, $in)) {
            $out[$k] = $in[$k];
         }
      }

      return $out;
   }

   /** Mueve archivo desde temp token a destino */
   private function consumeTemp(string $token, string $destRelDir): array
   {
      $publicRoot = dirname(__DIR__, 2) . '/public';
      $tempDir    = $publicRoot . '/uploads/temp/revisiones';

      $matches = glob($tempDir . '/' . $token . '.*');
      if (!$matches) {
         return [
            'ok'    => false,
            'error' => [
               'code'    => 'VALIDATION',
               'message' => 'Token temporal inválido o expirado',
            ],
         ];
      }

      $tempPath = $matches[0];
      $ext      = strtolower(pathinfo($tempPath, PATHINFO_EXTENSION));

      $destAbsDir = $publicRoot . $destRelDir;
      if (!is_dir($destAbsDir)) {
         mkdir($destAbsDir, 0775, true);
      }

      $filename = time() . '_' . $token . '.' . $ext;
      $destAbs  = $destAbsDir . '/' . $filename;

      if (!rename($tempPath, $destAbs)) {
         return [
            'ok'    => false,
            'error' => [
               'code'    => 'SERVER_ERROR',
               'message' => 'No se pudo mover archivo temporal',
            ],
         ];
      }

      $rel = $destRelDir . '/' . $filename;

      return [
         'ok'   => true,
         'data' => [
            'path'     => $destAbs,
            'relpath'  => $rel,
            'filename' => $filename,
            'original' => null,
            'mime'     => $this->mimeFromExt($ext),
            'size'     => filesize($destAbs),
         ],
      ];
   }

   private function mimeFromExt(string $ext): string
   {
      return match ($ext) {
         'pdf'        => 'application/pdf',
         'jpg', 'jpeg' => 'image/jpeg',
         'png'        => 'image/png',
         'doc'        => 'application/msword',
         'docx'       => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
         'xls'        => 'application/vnd.ms-excel',
         'xlsx'       => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
         'zip'        => 'application/zip',
         default      => 'application/octet-stream',
      };
   }

   /**
    * Rellena scope['team_user_ids'] cuando aplica view_team.
    */
   private function applyTeamScope(array $scope): array
   {
      $scope2 = $scope;
      $userId = (int)($scope['user_id'] ?? 0);

      if (!empty($scope['view_team']) && $userId > 0) {
         $scope2['team_user_ids'] = $this->userRepo->findTeamUserIds($userId);
      }

      return $scope2;
   }
}
