<?php

declare(strict_types=1);

namespace App\Http;

final class Response
{
   public static function json(array $data, int $status = 200): void
   {
      http_response_code($status);
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode($data, JSON_UNESCAPED_UNICODE);
   }

   public static function html(string $html, int $status = 200): void
   {
      http_response_code($status);
      header('Content-Type: text/html; charset=utf-8');
      echo $html;
   }

   public static function redirect(string $to, int $code = 302): void
   {
      header('Location: ' . $to, true, $code);
      exit;
   }
}
