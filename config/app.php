<?php
return [
  'env'   => $_ENV['APP_ENV']   ?? 'prod',
  'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOL),
  'url'   => $_ENV['APP_URL']   ?? 'http://localhost',
];
