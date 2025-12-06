<?php

/** @var int   $code */
/** @var array|null $error */
/** @var string $path */
$msg = $error['message'] ?? 'No autenticado';
?>
<!DOCTYPE html>
<html lang="es">

<head>
   <meta charset="utf-8">
   <title>401 · Sesión requerida</title>
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

      .box {
         max-width: 520px;
         padding: 2.5rem 2rem;
         background: #020617;
         border-radius: 0.75rem;
         border: 1px solid rgba(248, 250, 252, 0.08);
         box-shadow: 0 22px 45px rgba(0, 0, 0, 0.65);
      }

      .code {
         font-size: 2.2rem;
         font-weight: 800;
         color: #fb923c;
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
         background: #16a34a;
         color: #f9fafb;
         border-color: #15803d;
      }

      .btn-primary:hover {
         background: #15803d;
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
      <div class="title">Sesión requerida</div>
      <div class="msg">
         <?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?><br>
         Por favor inicia sesión para continuar usando el sistema.
      </div>
      <div class="btn-row">
         <a href="/login" class="btn btn-primary">Ir al login</a>
         <a href="/" class="btn btn-ghost">Ir al inicio</a>
      </div>
   </div>
</body>

</html>