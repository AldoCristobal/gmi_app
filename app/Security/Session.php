<?php

declare(strict_types=1);

namespace App\Security;

final class Session
{
   public static function start(): void
   {
      if (session_status() === PHP_SESSION_ACTIVE) return;

      $sec = require __DIR__ . '/../../config/security.php';

      session_name($sec['session_name']);
      session_set_cookie_params([
         'lifetime' => 0,
         'path' => '/',
         'domain' => '',
         'secure' => $sec['cookie_secure'],
         'httponly' => true,
         'samesite' => $sec['cookie_samesite'], // 'Lax' o 'Strict'
      ]);

      session_start();
   }
}
