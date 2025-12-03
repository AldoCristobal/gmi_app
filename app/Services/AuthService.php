<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserRepository;
use App\Security\Session;

final class AuthService
{
   public function __construct(private UserRepository $repo = new UserRepository()) {}

   public function login(string $email, string $password): array
   {
      $user = $this->repo->findByEmail($email);
      if (!$user || !password_verify($password, $user['pass_hash'])) {
         return [
            'ok'    => false,
            'error' => [
               'code'    => 'BAD_CREDENTIALS',
               'message' => 'Usuario/contraseña incorrectos'
            ]
         ];
      }

      $roles = $this->repo->rolesOfUser((int)$user['id']);
      $perms = $this->repo->permsOfRoles((int)$user['id']);

      Session::start();
      session_regenerate_id(true);

      $_SESSION['user'] = [
         'id'        => (int)$user['id'],
         'nombre'    => $user['nombre'],
         'email'     => $user['email'],
         'area_id'   => $user['area_id'] ? (int)$user['area_id'] : null,
         'roles'     => $roles,
         'permisos'  => $perms,
      ];

      $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

      // NUEVO: determinar ruta de inicio según rol.home_menu_id
      $homeVista = $this->repo->findHomeMenuVistaByUserId((int)$user['id']);
      // Ajusta el fallback a la vista que hoy muestras por defecto
      $homePath = $homeVista
         ? '/' . ltrim($homeVista, '/')
         : '/admin/usuarios'; // <-- cámbialo si tu home actual es otro

      return [
         'ok'   => true,
         'data' => [
            'user'       => $_SESSION['user'],
            'csrf_token' => $_SESSION['csrf_token'],
            'home_path'  => $homePath,
         ]
      ];
   }


   public function logout(): void
   {
      Session::start();
      $_SESSION = [];
      if (ini_get('session.use_cookies')) {
         $params = session_get_cookie_params();
         setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
      }
      session_destroy();
   }

   public function csrf(): array
   {
      Session::start();
      if (empty($_SESSION['csrf_token'])) {
         $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
      }
      return ['ok' => true, 'data' => ['csrf_token' => $_SESSION['csrf_token']]];
   }
}
