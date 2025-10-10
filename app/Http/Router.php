<?php

declare(strict_types=1);

namespace App\Http;

final class Router
{
   private array $routes = [
      'GET'    => [],
      'POST'   => [],
      'PUT'    => [],
      'PATCH'  => [],
      'DELETE' => [],
   ];

   public function get(string $path, callable|array $handler): void
   {
      $this->routes['GET'][$path]    = $handler;
   }
   public function post(string $path, callable|array $handler): void
   {
      $this->routes['POST'][$path]   = $handler;
   }
   public function put(string $path, callable|array $handler): void
   {
      $this->routes['PUT'][$path]    = $handler;
   }
   public function patch(string $path, callable|array $handler): void
   {
      $this->routes['PATCH'][$path]  = $handler;
   }
   public function delete(string $path, callable|array $handler): void
   {
      $this->routes['DELETE'][$path] = $handler;
   }

   public function dispatch(Request $req): void
   {
      $method  = $req->method;
      $path    = $req->uri ?: '/';
      $handler = $this->routes[$method][$path] ?? null;

      if (!$handler) {
         Response::json(['ok' => false, 'error' => ['code' => 'NOT_FOUND', 'path' => $path]], 404);
         return;
      }

      // Handler simple (callable)
      if (is_callable($handler)) {
         $handler($req); // ejecutar; no devolver valor
         return;
      }

      // Cadena de middlewares + controlador
      if (is_array($handler)) {
         $pipeline   = $handler;
         $controller = array_pop($pipeline);

         // El "next" final ejecuta el controlador
         $next = function (Request $rq) use ($controller): void {
            if (is_array($controller)) {
               // [obj, 'método'] o [Clase::class, 'método'] ya resuelto
               $callable = $controller;
            } else {
               $callable = $controller; // callable ya válido
            }
            $callable($rq); // ejecutar sin retornar valor
         };

         // Envolver middlewares (LIFO)
         while ($mw = array_pop($pipeline)) {
            $prevNext = $next;
            $next = function (Request $rq) use ($mw, $prevNext): void {
               if (is_object($mw) && method_exists($mw, 'handle')) {
                  $mw->handle($rq, $prevNext); // ejecutar; no retornar
                  return;
               }
               if (is_callable($mw)) {
                  $mw($rq, $prevNext); // ejecutar; no retornar
                  return;
               }
               Response::json(['ok' => false, 'error' => ['code' => 'MW_INVALID']], 500);
            };
         }

         // Ejecutar la cadena
         $next($req);
         return;
      }

      Response::json(['ok' => false, 'error' => ['code' => 'HANDLER_INVALID']], 500);
   }
}
