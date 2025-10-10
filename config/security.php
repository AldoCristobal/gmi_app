<?php
return [
  'session_name' => $_ENV['SESSION_NAME'] ?? 'GMISESSID',
  'csrf_secret'  => $_ENV['CSRF_SECRET']  ?? 'changeme-32chars-min',
  'cookie_secure' => filter_var($_ENV['COOKIE_SECURE'] ?? false, FILTER_VALIDATE_BOOL), // true en HTTPS
  'cookie_samesite' => $_ENV['COOKIE_SAMESITE'] ?? 'Lax', // 'Lax' o 'Strict'
];
