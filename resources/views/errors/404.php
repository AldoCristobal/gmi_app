<?php

/** @var int   $code */
/** @var array|null $error */
/** @var string $path */
?>
<!DOCTYPE html>
<html lang="es">

<head>
   <meta charset="utf-8">
   <title>404 · Página no encontrada</title>
   <meta name="viewport" content="width=device-width, initial-scale=1">
   <style>
      body {
         margin: 0;
         padding: 0;
         font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
         background: #0b1120;
         color: #e5e7eb;
         display: flex;
         align-items: center;
         justify-content: center;
         min-height: 100vh;
      }

      .box {
         max-width: 520px;
         padding: 2.5rem 2rem;
         background: #020617;
         border-radius: 0.75rem;
         border: 1px solid rgba(148, 163, 184, 0.35);
         box-shadow: 0 22px 45px rgba(0, 0, 0, 0.65);
      }

      .code {
         font-size: 2.5rem;
         font-weight: 800;
         color: #60a5fa;
         margin-bottom: .25rem;
      }

      .title {
         font-size: 1.25rem;
         font-weight: 600;
         margin-bottom: .75rem;
      }

      .msg {
         font-size: .95rem;
         color: #9ca3af;
         margin-bottom: 1.5rem;
      }

      .path {
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
         background: rgba(148, 163, 184, 0.12);
      }
   </style>
</head>

<body>
   <div class="box">
      <div class="code"><?= htmlspecialchars((string)$code, ENT_QUOTES, 'UTF-8') ?></div>
      <div class="title">Página no encontrada</div>
      <div class="msg">
         La ruta que intentas abrir no existe o fue movida.
      </div>
      <div class="path">
         Ruta solicitada:
         <code><?= htmlspecialchars($path ?? '', ENT_QUOTES, 'UTF-8') ?></code>
      </div>
      <div class="btn-row">
         <a href="/" class="btn btn-primary">Ir al inicio</a>
         <a href="javascript:history.back()" class="btn btn-ghost">Regresar</a>
      </div>
   </div>
</body>

</html>