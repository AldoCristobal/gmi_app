<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\RevisionRepository;

final class RevisionDocumentoService
{
   public function __construct(private RevisionRepository $repo = new RevisionRepository()) {}

   /** Listado read-only de documentos de una revisión */
   public function list(int $revisionId, array $scope): array
   {
      $rev = $this->repo->findVisible($revisionId, $scope);
      if (!$rev) return ['ok' => false, 'error' => ['code' => 'FORBIDDEN', 'message' => 'Revisión no visible']];

      $docs = $this->repo->docsDeRevision($revisionId);
      // separar inicial vs anexos
      $inicial = null;
      $anexos = [];
      foreach ($docs as $d) {
         if ((int)($d['is_inicial'] ?? 0) === 1) $inicial = $d;
         else $anexos[] = $d;
      }
      return ['ok' => true, 'data' => ['inicial' => $inicial, 'anexos' => $anexos]];
   }

   /**
    * Subida de anexos (uno o varios) a una revisión
    * $files: estructura $_FILES normalizada con files[]
    */
   public function upload(int $revisionId, array $files, int $userId, array $scope): array
   {
      $rev = $this->repo->findVisible($revisionId, $scope);
      if (!$rev) return ['ok' => false, 'error' => ['code' => 'FORBIDDEN', 'message' => 'Revisión no visible']];

      if (empty($files['files']) || empty($files['files']['tmp_name'])) {
         return ['ok' => false, 'error' => ['code' => 'VALIDATION', 'message' => 'Sin archivo']];
      }

      $uploaded = [];
      $publicRoot = dirname(__DIR__, 2) . '/public';
      $destRelDir = "/uploads/revisiones/{$revisionId}";
      $destAbsDir = $publicRoot . $destRelDir;
      if (!is_dir($destAbsDir)) mkdir($destAbsDir, 0775, true);

      $count = count($files['files']['tmp_name']);
      for ($i = 0; $i < $count; $i++) {
         $tmp  = $files['files']['tmp_name'][$i];
         $name = $files['files']['name'][$i] ?? 'archivo';
         $mime = $files['files']['type'][$i] ?? 'application/octet-stream';
         $size = (int)($files['files']['size'][$i] ?? 0);

         if (!is_uploaded_file($tmp)) continue;

         $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
         $uuid = bin2hex(random_bytes(8));
         $filename = time() . "_anexo_{$uuid}" . ($ext ? ".{$ext}" : '');
         $destAbs  = $destAbsDir . '/' . $filename;

         if (!move_uploaded_file($tmp, $destAbs)) {
            return ['ok' => false, 'error' => ['code' => 'SERVER_ERROR', 'message' => 'No se pudo mover un archivo']];
         }

         $relPath = $destRelDir . '/' . $filename;
         $version = $this->repo->nextDocVersion($revisionId);

         $docId = $this->repo->insertDoc([
            ':revision_id'    => $revisionId,
            ':version'        => $version,
            ':is_inicial'     => 0,
            ':nombre_original' => $name,
            ':archivo_path'   => $relPath,
            ':mime'           => $mime,
            ':size_bytes'     => $size,
            ':uploaded_by'    => $userId
         ]);

         $uploaded[] = ['doc_id' => $docId, 'version' => $version, 'archivo' => $name];
         $this->repo->bitacoraAppend($revisionId, 'subida_doc', [
            'docId' => $docId,
            'nombre' => $name,
            'is_inicial' => 0
         ], $userId);
      }

      return ['ok' => true, 'data' => ['uploaded' => $uploaded]];
   }

   /**
    * Descarga (stream binario) o JSON con metadatos si $stream=false
    */
   public function download(int $revisionId, int $docId, array $scope, bool $stream = false): ?array
   {
      $rev = $this->repo->findVisible($revisionId, $scope);
      if (!$rev) return ['ok' => false, 'error' => ['code' => 'FORBIDDEN', 'message' => 'Revisión no visible']];

      $doc = $this->repo->docPath($revisionId, $docId);
      if (!$doc) return ['ok' => false, 'error' => ['code' => 'NOT_FOUND', 'message' => 'Documento no encontrado']];

      $publicRoot = dirname(__DIR__, 2) . '/public';
      $abs = $publicRoot . ($doc['archivo_path'] ?? '');
      if (!is_file($abs)) {
         return ['ok' => false, 'error' => ['code' => 'NOT_FOUND', 'message' => 'Archivo no existe en disco']];
      }

      if ($stream) {
         // Forzar descarga (o usa inline si prefieres mostrar PDF en navegador)
         header('Content-Description: File Transfer');
         header('Content-Type: ' . ($doc['mime'] ?? 'application/octet-stream'));
         $nombre = $doc['archivo_nombre'] ?? $doc['nombre_original'] ?? basename($abs);
         header('Content-Disposition: attachment; filename="' . $nombre . '"');
         header('Content-Length: ' . filesize($abs));
         header('Cache-Control: private, max-age=0, must-revalidate');
         header('Pragma: public');
         readfile($abs);
         exit; // IMPORTANTE: detener el flujo normal
      }

      // Modo no stream (si lo necesitas en algún flujo)
      return ['ok' => true, 'data' => [
         'path'   => $doc['archivo_path'],
         'nombre' => $doc['archivo_nombre'] ?? $doc['nombre_original'] ?? basename($abs),
         'mime'   => $doc['mime'] ?? 'application/octet-stream'
      ]];
   }

   /** Borrar un documento (no inicial) */
   public function delete(int $revisionId, int $docId, array $scope): array
   {
      $rev = $this->repo->findVisible($revisionId, $scope);
      if (!$rev) return ['ok' => false, 'error' => ['code' => 'FORBIDDEN']];

      $doc = $this->repo->docPath($revisionId, $docId);
      if (!$doc) return ['ok' => false, 'error' => ['code' => 'NOT_FOUND']];

      // Evita borrar el documento inicial (regla segura)
      if ((int)($doc['is_inicial'] ?? 0) === 1) {
         return ['ok' => false, 'error' => ['code' => 'FORBIDDEN', 'message' => 'No puedes borrar la evidencia inicial']];
      }

      // Borra el archivo físico si existe
      $publicRoot = dirname(__DIR__, 2) . '/public';
      $abs = $publicRoot . ($doc['archivo_path'] ?? '');
      if (is_file($abs)) @unlink($abs);

      $ok = $this->repo->deleteDoc($revisionId, $docId);
      if (!$ok) return ['ok' => false, 'error' => ['code' => 'UPDATE_FAIL']];

      $this->repo->bitacoraAppend($revisionId, 'borrado_doc', ['docId' => $docId], (int)($scope['user_id'] ?? 0));
      return ['ok' => true];
   }

   /**
    * Reemplazar evidencia inicial (subida directa).
    * Registra nueva versión como inicial (=1) y marca el anterior como histórico.
    */
   public function replaceInitial(int $revisionId, array $files, int $userId, array $scope): array
   {
      $rev = $this->repo->findVisible($revisionId, $scope);
      if (!$rev) return ['ok' => false, 'error' => ['code' => 'FORBIDDEN']];

      if (empty($files['files']) || empty($files['files']['tmp_name'])) {
         return ['ok' => false, 'error' => ['code' => 'VALIDATION', 'message' => 'Sin archivo']];
      }

      // Tomamos solo el primer archivo (reemplazo único)
      $tmp  = $files['files']['tmp_name'][0] ?? null;
      $name = $files['files']['name'][0] ?? 'archivo';
      $mime = $files['files']['type'][0] ?? 'application/octet-stream';
      $size = (int)($files['files']['size'][0] ?? 0);

      if (!$tmp || !is_uploaded_file($tmp)) {
         return ['ok' => false, 'error' => ['code' => 'VALIDATION', 'message' => 'Archivo inválido']];
      }

      $publicRoot = dirname(__DIR__, 2) . '/public';
      $destRelDir = "/uploads/revisiones/{$revisionId}";
      $destAbsDir = $publicRoot . $destRelDir;
      if (!is_dir($destAbsDir)) mkdir($destAbsDir, 0775, true);

      $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
      $uuid = bin2hex(random_bytes(8));
      $filename = time() . "_inicial_{$uuid}" . ($ext ? ".{$ext}" : '');
      $destAbs  = $destAbsDir . '/' . $filename;

      if (!move_uploaded_file($tmp, $destAbs)) {
         return ['ok' => false, 'error' => ['code' => 'SERVER_ERROR', 'message' => 'No se pudo mover el archivo']];
      }

      $relPath = $destRelDir . '/' . $filename;
      $version = $this->repo->nextDocVersion($revisionId);

      // Inserta nueva evidencia inicial (is_inicial=1)
      $docId = $this->repo->insertDoc([
         ':revision_id'     => $revisionId,
         ':version'         => $version,
         ':is_inicial'      => 1,
         ':nombre_original' => $name,
         ':archivo_path'    => $relPath,
         ':mime'            => $mime,
         ':size_bytes'      => $size,
         ':uploaded_by'     => $userId
      ]);

      $this->repo->bitacoraAppend($revisionId, 'reemplazo_inicial', [
         'docId' => $docId,
         'nombre' => $name
      ], $userId);

      return ['ok' => true, 'data' => ['doc_id' => $docId, 'version' => $version]];
   }
}
