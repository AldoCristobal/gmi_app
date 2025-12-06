<?php

declare(strict_types=1);

namespace App\Http;

final class Response
{
   public static function json(array $data, int $status = 200): void
   {
      http_response_code($status);
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode($data, JSON_UNESCAPED_UNICODE);
   }

   public static function html(string $html, int $status = 200): void
   {
      http_response_code($status);
      header('Content-Type: text/html; charset=utf-8');
      echo $html;
   }

   public static function redirect(string $to, int $code = 302): void
   {
      header('Location: ' . $to, true, $code);
      exit;
   }

   /**
    * Respuesta de error unificada:
    * - Si es ruta API (/api/...), responde JSON.
    * - Si es ruta web, intenta renderizar una vista de error HTML.
    *
    * $payload mantiene el esquema actual: ['ok' => false, 'error' => ['code' => ..., 'message' => ...]]
    */
   public static function error(\App\Http\Request $req, int $status, array $payload): void
   {
      $uri = $req->uri ?? '';

      // Heurística simple: todo lo que sea /api/... se trata como API
      $isApi = (strpos($uri, '/api/') === 0);

      if ($isApi) {
         // API → JSON como siempre
         self::json($payload, $status);
         return;
      }

      // Web → intentar cargar vista de error
      $code = $status;
      $root = dirname(__DIR__, 2); // sube de app/Http → app → raíz del proyecto
      $viewFile = $root . '/resources/views/errors/' . $code . '.php';

      $error = $payload['error'] ?? null;
      $path  = $uri;

      if (is_file($viewFile)) {
         // Variables disponibles dentro de la vista:
         // $code, $error, $path
         ob_start();
         include $viewFile;
         $html = ob_get_clean() ?: '';
         self::html($html, $status);
         return;
      }

      // Fallback simple si no existe la vista
      $msg   = $error['message'] ?? 'Ha ocurrido un error.';
      $title = $code . ' · Error';

      $html = <<<HTML
         <!DOCTYPE html>
         <html lang="es">
         <head>
            <meta charset="utf-8">
            <title>{$title}</title>
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <style>
               body {
                  margin: 0;
                  padding: 0;
                  font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
                  background: #111827;
                  color: #e5e7eb;
                  display: flex;
                  align-items: center;
                  justify-content: center;
                  min-height: 100vh;
               }
               .error-box {
                  max-width: 540px;
                  padding: 2.5rem 2rem;
                  background: #020617;
                  border-radius: 0.75rem;
                  box-shadow: 0 20px 40px rgba(0,0,0,0.55);
                  border: 1px solid rgba(148,163,184,0.3);
               }
               .error-code {
                  font-size: 2.25rem;
                  font-weight: 700;
                  color: #60a5fa;
                  margin-bottom: .25rem;
               }
               .error-title {
                  font-size: 1.25rem;
                  font-weight: 600;
                  margin-bottom: .75rem;
               }
               .error-msg {
                  font-size: .95rem;
                  color: #9ca3af;
                  margin-bottom: 1.5rem;
               }
               .error-path {
                  font-size: .8rem;
                  color: #6b7280;
                  margin-bottom: 1.5rem;
               }
               .btn-row {
                  display: flex;
                  gap: .75rem;
                  flex-wrap: wrap;
               }
               .btn {
                  display: inline-flex;
                  align-items: center;
                  justify-content: center;
                  padding: .55rem 1.2rem;
                  border-radius: 999px;
                  font-size: .85rem;
                  font-weight: 500;
                  text-decoration: none;
                  border: 1px solid transparent;
                  transition: all .15s ease;
               }
               .btn-primary {
                  background: #2563eb;
                  color: #f9fafb;
                  border-color: #1d4ed8;
               }
               .btn-primary:hover {
                  background: #1d4ed8;
               }
               .btn-ghost {
                  background: transparent;
                  color: #e5e7eb;
                  border-color: #4b5563;
               }
               .btn-ghost:hover {
                  background: rgba(148,163,184,0.12);
               }
            </style>
         </head>
         <body>
            <div class="error-box">
               <div class="error-code">{$code}</div>
               <div class="error-title">{$msg}</div>
               <div class="error-path">Ruta: <code>{$path}</code></div>
               <div class="btn-row">
                  <a href="/" class="btn btn-primary">Ir al inicio</a>
                  <a href="javascript:history.back()" class="btn btn-ghost">Regresar</a>
               </div>
            </div>
         </body>
         </html>
      HTML;

      self::html($html, $status);
   }
}
