<?php

/** @var string $title */
/** @var string $content */
/** @var bool   $isLogin */
/** @var string|null $csrfToken */
?>
<!DOCTYPE html>
<html lang="es">

<head>
   <meta charset="utf-8">
   <title><?= htmlspecialchars($title ?? 'ERP GMI') ?></title>
   <meta name="viewport" content="width=device-width, initial-scale=1">

   <!-- CSRF META (clave para el front) -->
   <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken ?? ($_SESSION['csrf_token'] ?? '')) ?>">

   <!-- CSS -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
   <?php if (empty($isLogin)): ?>
      <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community@29.3.5/styles/ag-grid.css">
      <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community@29.3.5/styles/ag-theme-alpine-dark.css">
   <?php endif; ?>

   <link rel="stylesheet" href="/assets/css/app.css">

   <!-- Notyf CSS (SIEMPRE) -->
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/notyf/notyf.min.css">
   <!-- FancyTree (una sola vez) -->
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jquery.fancytree/dist/skin-win8/ui.fancytree.min.css">
   <!-- FilePond -->
   <link rel="stylesheet" href="https://unpkg.com/filepond/dist/filepond.min.css">
</head>

<body class="hold-transition <?= empty($isLogin) ? 'sidebar-mini layout-fixed layout-navbar-fixed dark-mode' : 'login-page dark-mode' ?>">

   <div class="wrapper">
      <?php if (empty($isLogin)): ?>
         <?php include __DIR__ . '/partials/navbar.php'; ?>
         <?php include __DIR__ . '/partials/sidebar.php'; ?>

         <div class="content-wrapper">
            <section class="content-header">
               <div class="container-fluid">
                  <h1 class="m-0"><?= htmlspecialchars($title ?? '') ?></h1>
               </div>
            </section>
            <section class="content">
               <div class="container-fluid"><?= $content ?? '' ?></div>
            </section>
         </div>
      <?php else: ?>
         <?= $content ?? '' ?>
      <?php endif; ?>
   </div>

   <!-- Loader overlay global -->
   <div id="app-loader" class="loader-overlay d-none">
      <div class="spinner"></div>
      <div class="loader-text">Procesando...</div>
   </div>

   <!-- JS: SIEMPRE dentro del body, al final -->
   <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
   <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>

   <?php if (empty($isLogin)): ?>
      <!-- AG Grid SOLO en páginas internas -->
      <script src="https://cdn.jsdelivr.net/npm/ag-grid-community@29.3.5/dist/ag-grid-community.min.js"></script>
   <?php endif; ?>

   <!-- Notyf JS -->
   <script src="https://cdn.jsdelivr.net/npm/notyf/notyf.min.js"></script>
   <!-- FancyTree -->
   <script src="https://cdn.jsdelivr.net/npm/jquery.fancytree/dist/jquery.fancytree-all-deps.min.js"></script>
   <!-- FilePond -->
   <script src="https://unpkg.com/filepond/dist/filepond.min.js"></script>
   <script src="https://unpkg.com/filepond-plugin-file-validate-type/dist/filepond-plugin-file-validate-type.min.js"></script>
   <script src="https://unpkg.com/filepond-plugin-file-validate-size/dist/filepond-plugin-file-validate-size.min.js"></script>

   <!-- Helpers globales -->
   <script src="/assets/js/api.js"></script>
   <script src="/assets/js/app.js"></script>
   <script src="/assets/js/gating.js"></script>

   <!-- Bootstrap del CSRF para api.js: copia meta -> localStorage.csrf_token -->
   <script>
      (function() {
         try {
            var meta = document.querySelector('meta[name="csrf-token"]');
            var t = meta && meta.content ? meta.content : '';
            if (t) {
               // api.js lo leerá de aquí
               localStorage.setItem('csrf_token', t);
            } else {
               console.warn('csrf-token meta vacío: las solicitudes POST/PUT/DELETE fallarán con 419.');
            }
         } catch (e) {
            console.error('No se pudo inicializar CSRF en localStorage:', e);
         }
      })();
   </script>

   <!-- Script específico de la vista -->
   <?php if (!empty($script)): ?>
      <script src="/assets/js/<?= htmlspecialchars($script) ?>"></script>
   <?php endif; ?>

   <!-- Inicialización Notyf (con fallback seguro) -->
   <script>
      if (window.Notyf) {
         window.notyf = new Notyf({
            duration: 4000,
            position: {
               x: 'right',
               y: 'top'
            },
            ripple: true,
            dismissible: true
         });
         window.notify = function(msg, type = 'info') {
            switch (type) {
               case 'success':
                  return notyf.success(msg);
               case 'error':
                  return notyf.error(msg);
               default:
                  return notyf.open({
                     type,
                     message: msg
                  });
            }
         };
      } else {
         window.notify = function(msg, type = 'info') {
            if (type === 'error') console.error(msg);
            else console.log(msg);
         };
      }
   </script>
</body>

</html>