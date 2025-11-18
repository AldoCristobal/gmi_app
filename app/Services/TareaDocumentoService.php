<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\TareaRepository;
use App\Repositories\TareaDocumentoRepository;
use App\Http\Response;

final class TareaDocumentoService
{
   public function __construct(
      private TareaRepository $tRepo = new TareaRepository(),
      private TareaDocumentoRepository $dRepo = new TareaDocumentoRepository()
   ) {}

   /**
    * Listar documentos de una tarea
    *
    * @param array<string,mixed> $user
    * @param array<string,mixed> $scope
    * @return array<string,mixed>
    */
   public function listar(int $tareaId, array $user, array $scope): array
   {
      $tarea = $this->tRepo->findById($tareaId);
      if (!$tarea) {
         return [
            'ok' => false,
            'error' => ['code' => 'NOT_FOUND', 'message' => 'Tarea no encontrada']
         ];
      }

      if (!$this->puedeVer($tarea, $user, $scope)) {
         return [
            'ok' => false,
            'error' => ['code' => 'FORBIDDEN', 'message' => 'No puedes ver esta tarea']
         ];
      }

      $docs = $this->dRepo->listByTarea($tareaId);

      return [
         'ok'   => true,
         'data' => $docs
      ];
   }

   /**
    * Subir uno o varios archivos de evidencia
    *
    * @param array<string,mixed> $user
    * @param array<string,mixed> $scope
    * @param array<string,mixed> $files  $_FILES['files'] (multiple)
    * @return array<string,mixed>
    */
   public function subir(int $tareaId, array $user, array $scope, array $files): array
   {
      $tarea = $this->tRepo->findById($tareaId);
      if (!$tarea) {
         return [
            'ok' => false,
            'error' => ['code' => 'NOT_FOUND', 'message' => 'Tarea no encontrada']
         ];
      }

      if (!$this->puedeSubir($tarea, $user, $scope)) {
         return [
            'ok' => false,
            'error' => ['code' => 'FORBIDDEN', 'message' => 'No puedes subir evidencias a esta tarea']
         ];
      }

      // Solo permitimos subir en estado PENDIENTE
      $estado = strtoupper((string)$tarea['estado']);
      if ($estado !== 'PENDIENTE') {
         return [
            'ok' => false,
            'error' => [
               'code' => 'ESTADO_INVALIDO',
               'message' => 'Solo puedes subir evidencias cuando la tarea está pendiente.'
            ]
         ];
      }

      $root = dirname(__DIR__, 2); // /app/Services -> raíz del proyecto
      $relBaseDir = 'uploads/tareas/' . $tareaId;
      $fullBaseDir = $root . '/public/' . $relBaseDir;

      if (!is_dir($fullBaseDir)) {
         if (!mkdir($fullBaseDir, 0775, true) && !is_dir($fullBaseDir)) {
            return [
               'ok' => false,
               'error' => [
                  'code' => 'FS_ERROR',
                  'message' => 'No se pudo crear el directorio de evidencias.'
               ]
            ];
         }
      }

      $subidos = [];
      $errores = [];

      // Soportar multiple: files[name][i], files[tmp_name][i], etc.
      $nombres   = $files['name'] ?? [];
      $tmpNames  = $files['tmp_name'] ?? [];
      $sizes     = $files['size'] ?? [];
      $errors    = $files['error'] ?? [];
      $types     = $files['type'] ?? [];

      $total = is_array($nombres) ? count($nombres) : 0;

      for ($i = 0; $i < $total; $i++) {
         $name = $nombres[$i] ?? null;
         $tmp  = $tmpNames[$i] ?? null;
         $size = $sizes[$i] ?? 0;
         $err  = $errors[$i] ?? UPLOAD_ERR_NO_FILE;
         $type = $types[$i] ?? null;

         if (!$name || !$tmp || $err !== UPLOAD_ERR_OK || $size <= 0) {
            $errores[] = [
               'file' => $name,
               'error' => 'Archivo inválido o error de subida'
            ];
            continue;
         }

         $safeName = $this->sanearNombreArchivo((string)$name);
         $ext = pathinfo($safeName, PATHINFO_EXTENSION);
         $uniqueName = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . ($ext ? '.' . $ext : '');
         $fullPath = $fullBaseDir . '/' . $uniqueName;
         $relPath  = $relBaseDir . '/' . $uniqueName;

         if (!move_uploaded_file($tmp, $fullPath)) {
            $errores[] = [
               'file' => $name,
               'error' => 'No se pudo mover el archivo al destino'
            ];
            continue;
         }

         $docId = $this->dRepo->insert([
            'tarea_id'        => $tareaId,
            'nombre_original' => $name,
            'archivo_path'    => $relPath,
            'mime_type'       => $type,
            'extension'       => $ext ?: null,
            'size_bytes'      => $size,
            'subido_por'      => $user['id'] ?? null,
            'nota'            => null,
         ]);

         $doc = $this->dRepo->findById($docId);
         if ($doc) {
            $subidos[] = $doc;
         }
      }

      return [
         'ok' => true,
         'data' => [
            'subidos' => $subidos,
            'errores' => $errores
         ]
      ];
   }

   /**
    * Descargar un archivo de evidencia (hace stream + exit)
    *
    * @param array<string,mixed> $user
    * @param array<string,mixed> $scope
    */
   public function download(int $docId, array $user, array $scope): void
   {
      $doc = $this->dRepo->findById($docId);
      if (!$doc) {
         Response::json(['ok' => false, 'error' => ['code' => 'NOT_FOUND', 'message' => 'Documento no encontrado']], 404);
         return;
      }

      $tareaId = (int)$doc['tarea_id'];
      $tarea = $this->tRepo->findById($tareaId);
      if (!$tarea) {
         Response::json(['ok' => false, 'error' => ['code' => 'NOT_FOUND', 'message' => 'Tarea no encontrada']], 404);
         return;
      }

      if (!$this->puedeVer($tarea, $user, $scope)) {
         Response::json(['ok' => false, 'error' => ['code' => 'FORBIDDEN', 'message' => 'No puedes descargar este documento']], 403);
         return;
      }

      $root = dirname(__DIR__, 2);
      $relPath = (string)$doc['archivo_path'];
      $fullPath = $root . '/public/' . $relPath;

      if (!is_file($fullPath)) {
         Response::json(['ok' => false, 'error' => ['code' => 'FS_NOT_FOUND', 'message' => 'Archivo físico no encontrado']], 404);
         return;
      }

      $mime = $doc['mime_type'] ?: 'application/octet-stream';
      $orig = $doc['nombre_original'] ?: basename($fullPath);
      $size = (int)($doc['size_bytes'] ?? filesize($fullPath));

      header('Content-Type: ' . $mime);
      header('Content-Length: ' . $size);
      header('Content-Disposition: attachment; filename="' . rawurlencode($orig) . '"');
      header('Cache-Control: private, max-age=0, must-revalidate');
      header('Pragma: public');

      readfile($fullPath);
      exit;
   }

   /**
    * Eliminar un documento de evidencia
    *
    * @param array<string,mixed> $user
    * @param array<string,mixed> $scope
    * @return array<string,mixed>
    */
   public function eliminar(int $docId, array $user, array $scope): array
   {
      $doc = $this->dRepo->findById($docId);
      if (!$doc) {
         return [
            'ok' => false,
            'error' => ['code' => 'NOT_FOUND', 'message' => 'Documento no encontrado']
         ];
      }

      $tarea = $this->tRepo->findById((int)$doc['tarea_id']);
      if (!$tarea) {
         return [
            'ok' => false,
            'error' => ['code' => 'NOT_FOUND', 'message' => 'Tarea no encontrada']
         ];
      }

      if (!$this->puedeBorrar($tarea, $user, $scope)) {
         return [
            'ok' => false,
            'error' => ['code' => 'FORBIDDEN', 'message' => 'No puedes eliminar este documento']
         ];
      }

      // Solo permitir borrar en estado PENDIENTE
      $estado = strtoupper((string)$tarea['estado']);
      if ($estado !== 'PENDIENTE') {
         return [
            'ok' => false,
            'error' => [
               'code' => 'ESTADO_INVALIDO',
               'message' => 'Solo puedes eliminar evidencias cuando la tarea está pendiente.'
            ]
         ];
      }

      $root = dirname(__DIR__, 2);
      $relPath = (string)$doc['archivo_path'];
      $fullPath = $root . '/public/' . $relPath;

      $ok = $this->dRepo->delete($docId);

      if ($ok && is_file($fullPath)) {
         @unlink($fullPath);
      }

      return ['ok' => true];
   }

   // ================== Helpers de permisos básicos ==================

   /**
    * @param array<string,mixed> $tarea
    * @param array<string,mixed> $user
    * @param array<string,mixed> $scope
    */
   private function puedeVer(array $tarea, array $user, array $scope): bool
   {
      // Por ahora: cualquiera que ya pasó Auth + Scope y ve la tarea
      // puede ver sus evidencias. El alcance real lo controla el ScopeMiddleware.
      return true;
   }

   /**
    * @param array<string,mixed> $tarea
    * @param array<string,mixed> $user
    * @param array<string,mixed> $scope
    */
   private function puedeSubir(array $tarea, array $user, array $scope): bool
   {
      $userId = (int)($user['id'] ?? 0);

      // Regla principal: solo el responsable de la tarea puede subir evidencias
      if ($userId > 0 && (int)$tarea['responsable_id'] === $userId) {
         return true;
      }

      // Si más adelante quieres permitir GERENCIA / DIRECCION, podrías hacer:
      // $role = strtoupper((string)($user['role'] ?? ''));
      // if (in_array($role, ['DIRECCION','GERENCIA'], true)) return true;

      return false;
   }

   /**
    * @param array<string,mixed> $tarea
    * @param array<string,mixed> $user
    * @param array<string,mixed> $scope
    */
   private function puedeBorrar(array $tarea, array $user, array $scope): bool
   {
      // Misma lógica que subir por ahora:
      $userId = (int)($user['id'] ?? 0);

      if ($userId > 0 && (int)$tarea['responsable_id'] === $userId) {
         return true;
      }

      return false;
   }


   private function sanearNombreArchivo(string $name): string
   {
      $name = str_replace(['\\', '/'], '_', $name);
      // opcional: limitar caracteres
      return preg_replace('/[^A-Za-z0-9_\.\- áéíóúÁÉÍÓÚñÑ]/u', '_', $name) ?: $name;
   }
}
