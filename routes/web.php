<?php

use App\Http\Response;
use App\Support\View;
use App\Http\Middlewares\AuthMiddleware;
use App\Http\Middlewares\RbacMiddleware;

$router->get('/login', function ($req) {
   $html = \App\Support\View::render('auth/login', [
      'title'   => 'Iniciar sesión',
      'isLogin' => true
   ]);
   \App\Http\Response::html($html);
});

// Home: redirige según sesión
$router->get('/', function ($req) {
   if (isset($_SESSION['user'])) {
      Response::redirect('/admin/usuarios');
   } else {
      $html = View::render('auth/login', [
         'title' => 'Iniciar sesión'
      ]);
      Response::html($html);
   }
});

// Login explícito (por si navegan directo)
$router->get('/login', function ($req) {
   $html = View::render('auth/login', ['title' => 'Iniciar sesión']);
   Response::html($html);
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
         'script' => 'roles.js'   // ⬅️ crea public/assets/js/roles.js
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
         'script' => 'menu.js' // ⬅️ asegúrate de tener /assets/js/menu.js
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
         'csrfToken'  => $_SESSION['csrf_token'], // <- pásalo a la vista
      ]);
      \App\Http\Response::html($html);
   }
]);

$router->get('/empresas/expediente', [
   new \App\Http\Middlewares\AuthMiddleware(),
   new \App\Http\Middlewares\RbacMiddleware(['empresa.expediente']),
   function ($req) {
      $html = \App\Support\View::render('empresas/expediente', [
         'title'  => 'Expediente de Empresas',
         'script' => 'empresas.expediente.js'
      ]);
      \App\Http\Response::html($html);
   }
]);
