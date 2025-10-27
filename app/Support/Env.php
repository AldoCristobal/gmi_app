<?php

declare(strict_types=1);

namespace App\Support;

use Dotenv\Dotenv;

final class Env
{
   public static function load(string $basePath): void
   {
      if (file_exists($basePath . '/.env')) {
         $dotenv = Dotenv::createImmutable($basePath);
         $dotenv->safeLoad();
      }
   }

   public static function get(string $key, ?string $default = null): ?string
   {
      if (array_key_exists($key, $_ENV)) {
         return is_string($_ENV[$key]) ? $_ENV[$key] : $default;
      }
      if (array_key_exists($key, $_SERVER)) {
         return is_string($_SERVER[$key]) ? $_SERVER[$key] : $default;
      }
      $v = getenv($key);
      if ($v !== false && $v !== null) {
         return (string)$v;
      }
      return $default;
    }
}
