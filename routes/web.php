<?php

use App\Http\Response;
use App\Support\View;
use App\Http\Middlewares\AuthMiddleware;
use App\Http\Middlewares\RbacMiddleware;

$router->get('/login', function ($req) {
   $html = View::render('auth/login', [
      'title'   => 'Iniciar sesión',
      'isLogin' => true
   ]);
   Response::html($html);
});

// Home: redirige según sesión
$router->get('/', function ($req) {
   if (isset($_SESSION['user'])) {
      Response::redirect('/admin/usuarios');
   } else {
      $html = View::render('auth/login', [
         'title' => 'Iniciar sesión',
         'isLogin' => true,
      ]);
      Response::html($html);
   }
});

// Página de administración de usuarios (protegida)
$router->get('/admin/usuarios', [
   new AuthMiddleware(),
   new RbacMiddleware(['admin.usuarios.ver']),
   function ($req) {
      $html = View::render('admin/usuarios', [
         'title'  => 'Administrar usuarios',
         'script' => 'users.js'
      ]);
      Response::html($html);
   }
]);

$router->get('/admin/roles', [
   new AuthMiddleware(),
   new RbacMiddleware(['admin.roles.ver']),
   function ($req) {
      $html = View::render('admin/roles', [
         'title'  => 'Administrar roles',
         'script' => 'roles.js'
      ]);
      Response::html($html);
   }
]);

$router->get('/admin/menu', [
   new AuthMiddleware(),
   new RbacMiddleware(['admin.menu.ver']),
   function ($req) {
      $html = View::render('admin/menu', [
         'title'  => 'Administrar menú',
         'script' => 'menu.js'
      ]);
      Response::html($html);
   }
]);

// Módulo Empresas (fuera de /admin)
$router->get('/empresas', [
   new \App\Http\Middlewares\AuthMiddleware(),
   new \App\Http\Middlewares\RbacMiddleware(['empresa.ver']),
   function ($req) {
      // --- CSRF: asegura token en sesión
      if (session_status() !== PHP_SESSION_ACTIVE) {
         App\Security\Session::start();
      }
      if (empty($_SESSION['csrf_token'])) {
         $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
      }

      $html = \App\Support\View::render('empresas/index', [
         'title'      => 'Empresas',
         'script'     => 'empresas.index.js',
         'csrfToken'  => $_SESSION['csrf_token'],
      ]);
      \App\Http\Response::html($html);
   }
]);

// Módulo Revisiones (fuera de /admin)
$router->get('/revisiones', [
   new \App\Http\Middlewares\AuthMiddleware(),
   new \App\Http\Middlewares\RbacMiddleware(['revisiones.ver']),
   function ($req) {
      // --- CSRF: asegura token en sesión (igual que en /empresas)
      if (session_status() !== PHP_SESSION_ACTIVE) {
         \App\Security\Session::start();
      }
      if (empty($_SESSION['csrf_token'])) {
         $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
      }

      $html = \App\Support\View::render('revisiones/index', [
         'title'     => 'Revisiones',
         'script'    => 'revisiones.index.js',
         'csrfToken' => $_SESSION['csrf_token'],
      ]);
      \App\Http\Response::html($html);
   }
]);

// Historial de Revisiones (solo lectura)
$router->get('/revisiones/historial', [
   new \App\Http\Middlewares\AuthMiddleware(),
   new \App\Http\Middlewares\RbacMiddleware(['revisiones.historial.ver']),
   function ($req) {
      // Asegura CSRF en la vista
      if (session_status() !== PHP_SESSION_ACTIVE) {
         App\Security\Session::start();
      }
      if (empty($_SESSION['csrf_token'])) {
         $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
      }
      $html = \App\Support\View::render('revisiones/historial', [
         'title'     => 'Historial de Revisiones',
         'script'    => 'revisiones.historial.js',
         'csrfToken' => $_SESSION['csrf_token'],
      ]);
      \App\Http\Response::html($html);
   }
]);

// Creación de rutinas
$router->get('/empresas/rutinas', [
   new \App\Http\Middlewares\AuthMiddleware(),
   // FIX: permiso existente y alineado con API (empresa.obligacion.ver)
   new \App\Http\Middlewares\RbacMiddleware(['empresa.obligacion.ver']),
   function ($req) {
      // Asegura CSRF en la vista
      if (session_status() !== PHP_SESSION_ACTIVE) {
         App\Security\Session::start();
      }
      if (empty($_SESSION['csrf_token'])) {
         $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
      }
      $html = \App\Support\View::render('empresas/rutinas', [
         'title'     => 'Rutinas',
         'script'    => 'empresas.rutinas.js',
         'csrfToken' => $_SESSION['csrf_token'],
      ]);
      \App\Http\Response::html($html);
   }
]);

// MIS TAREAS
$router->get('/tareas/mis_tareas', [
   new \App\Http\Middlewares\AuthMiddleware(),
   new \App\Http\Middlewares\RbacMiddleware(['tareas.ver']),
   function ($req) {
      // Asegura CSRF en la vista
      if (session_status() !== PHP_SESSION_ACTIVE) {
         App\Security\Session::start();
      }
      if (empty($_SESSION['csrf_token'])) {
         $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
      }
      $html = \App\Support\View::render('tareas/mis_tareas', [
         'title'     => 'Mis tareas',
         'script'    => 'tareas.mis.js',
         'csrfToken' => $_SESSION['csrf_token'],
      ]);
      \App\Http\Response::html($html);
   }
]);

// ADMIN: Asignar menú a roles
$router->get('/admin/menu_rol', [
   new \App\Http\Middlewares\AuthMiddleware(),
   new \App\Http\Middlewares\RbacMiddleware(['admin.menu.asignar']),
   function ($req) {
      // Asegura CSRF en la vista (mismo patrón que en mis_tareas)
      if (session_status() !== PHP_SESSION_ACTIVE) {
         \App\Security\Session::start();
      }
      if (empty($_SESSION['csrf_token'])) {
         $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
      }

      $html = \App\Support\View::render('admin/menu_rol', [
         'title'     => 'Asignar menú a roles',
         'script'    => 'admin.menu_rol.js',
         'csrfToken' => $_SESSION['csrf_token'],
      ]);

      \App\Http\Response::html($html);
   }
]);


$router->get('/tareas/extraordinarias', [
   new \App\Http\Middlewares\AuthMiddleware(),
   new \App\Http\Middlewares\RbacMiddleware(['tareas.extra.ver']),
   function ($req) {
      if (session_status() !== PHP_SESSION_ACTIVE) {
         \App\Security\Session::start();
      }
      if (empty($_SESSION['csrf_token'])) {
         $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
      }

      $html = \App\Support\View::render('tareas/extraordinarias', [
         'title'     => 'Tareas extraordinarias',
         'script'    => 'tareas.extra.js',
         'csrfToken' => $_SESSION['csrf_token'],
      ]);
      \App\Http\Response::html($html);
   }
]);

$router->get('/tareas/evaluacion', [
   new \App\Http\Middlewares\AuthMiddleware(),
   new \App\Http\Middlewares\RbacMiddleware(['tareas.evaluacion.ver']),
   function ($req) {
      if (session_status() !== PHP_SESSION_ACTIVE) {
         \App\Security\Session::start();
      }
      if (empty($_SESSION['csrf_token'])) {
         $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
      }

      $html = \App\Support\View::render('tareas/evaluacion', [
         'title'     => 'Evaluación de tareas',
         'script'    => 'tareas.evaluacion.js',
         'csrfToken' => $_SESSION['csrf_token'],
      ]);

      \App\Http\Response::html($html);
   }
]);


// Vista del panel de expediente
$router->get('/empresas/expediente-panel', [
   new \App\Http\Middlewares\AuthMiddleware(),
   new \App\Http\Middlewares\RbacMiddleware(['empresa.expediente']),
   function ($req) {
      if (session_status() !== PHP_SESSION_ACTIVE) {
         \App\Security\Session::start();
      }
      if (empty($_SESSION['csrf_token'])) {
         $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
      }

      $html = \App\Support\View::render('empresas/expediente-panel', [
         'title'     => 'Expediente de empresas',
         'script'    => 'empresas.expediente-panel.js',
         'csrfToken' => $_SESSION['csrf_token'],
      ]);

      \App\Http\Response::html($html);
   }
]);
