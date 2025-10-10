<?php

declare(strict_types=1);

namespace App\Http;

final class Request
{
   private array $attrs = [];

   public function __construct(
      public readonly string $method,
      public readonly string $uri,
      public array $get,
      public array $post,
      public array $server,
      public array $files,
      public array $cookies,
      public array $headers
   ) {}

   public static function capture(): self
   {
      $headers = function_exists('getallheaders') ? getallheaders() : [];

      $rawUri = $_SERVER['REQUEST_URI'] ?? '/';
      $path = parse_url($rawUri, PHP_URL_PATH) ?: '/';

      // Detecta base path (carpeta donde vive index.php)
      $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';          // p.ej. /erp-gmi/public/index.php
      $basePath   = rtrim(str_replace('\\', '/', dirname($scriptName)), '/'); // /erp-gmi/public

      // Si la URL comienza con ese basePath, quítalo
      if ($basePath && str_starts_with($path, $basePath)) {
         $path = substr($path, strlen($basePath));
         if ($path === false || $path === '') $path = '/';
      }

      // Además, si quedara un /public por delante, quítalo (por setups peculiares)
      if (str_starts_with($path, '/public')) {
         $path = substr($path, 7) ?: '/';
      }

      $path = rtrim($path, '/') ?: '/';

      return new self(
         strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'),
         $path,
         $_GET,
         $_POST,
         $_SERVER,
         $_FILES,
         $_COOKIE,
         $headers
      );
   }


   public function wantsJson(): bool
   {
      $acc = $this->headers['Accept'] ?? ($this->headers['accept'] ?? '');
      return str_contains($acc, 'application/json') || str_starts_with($this->uri, '/api/');
   }

   /** Atributos (user, scope, etc.) */
   public function withAttr(string $key, mixed $value): self
   {
      $clone = clone $this;
      $clone->attrs[$key] = $value;
      return $clone;
   }
   public function attr(string $key, mixed $default = null): mixed
   {
      return $this->attrs[$key] ?? $default;
   }
}
