<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;

final class UploadTempController
{
   /** POST multipart /api/v1/uploads/temp (file) */
   public function store(Request $req): void
   {
      if (empty($_FILES['file'])) {
         Response::json(['ok' => false, 'error' => ['code' => 'VALIDATION', 'message' => 'file requerido']], 400);
         return;
      }
      $file = $_FILES['file'];
      if (!is_uploaded_file($file['tmp_name'])) {
         Response::json(['ok' => false, 'error' => ['code' => 'VALIDATION', 'message' => 'Archivo inválido']], 400);
         return;
      }

      $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
      $token = 'tmp_' . bin2hex(random_bytes(8));

      $publicRoot = dirname(__DIR__, 2) . '/public';
      $tempDir = $publicRoot . '/uploads/temp/revisiones';
      if (!is_dir($tempDir)) mkdir($tempDir, 0775, true);

      $dest = $tempDir . '/' . $token . '.' . $ext;
      if (!move_uploaded_file($file['tmp_name'], $dest)) {
         Response::json(['ok' => false, 'error' => ['code' => 'SERVER_ERROR', 'message' => 'No se pudo guardar temporalmente']], 500);
         return;
      }

      Response::json(['ok' => true, 'data' => [
         'token' => $token,
         'filename' => $file['name'],
         'size' => (int)$file['size'],
         'mime' => $file['type'] ?? 'application/octet-stream',
         'expires_at' => date('c', strtotime('+24 hours'))
      ]], 200);
   }
}
