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

   /**
    * Pila de grupos (tipo Laravel):
    * cada item: ['prefix' => string, 'middleware' => array]
    */
   private array $groupStack = [];

   /**
    * Define un grupo de rutas con prefix y/o middlewares.
    */
   public function group(array $options, callable $callback): void
   {
      $parent = end($this->groupStack) ?: ['prefix' => '', 'middleware' => []];

      $prefix     = $options['prefix']     ?? '';
      $middleware = $options['middleware'] ?? [];

      $ctx = [
         'prefix'     => rtrim($parent['prefix'], '/') . ($prefix ? '/' . ltrim($prefix, '/') : ''),
         'middleware' => array_merge($parent['middleware'], $middleware),
      ];

      $this->groupStack[] = $ctx;

      try {
         $callback($this);
      } finally {
         array_pop($this->groupStack);
      }
   }

   /**
    * Aplica contexto de grupo (prefix + middlewares) a una ruta que se está registrando.
    *
    * @param string               $path
    * @param callable|array<mixed> $handler
    * @return array{0:string,1:callable|array}
    */
   private function applyGroupContext(string $path, callable|array $handler): array
   {
      if (empty($this->groupStack)) {
         return [$path, $handler];
      }

      $ctx = end($this->groupStack);

      // Prefix
      if (!empty($ctx['prefix'])) {
         $prefix = rtrim($ctx['prefix'], '/');

         if ($path === '' || $path === '/') {
            // Ruta "índice" del grupo: que quede exactamente el prefijo
            $path = $prefix === '' ? '/' : $prefix;
         } else {
            $path = $prefix . '/' . ltrim($path, '/');
         }
      }

      // Middlewares del grupo
      if (!empty($ctx['middleware'])) {
         if (is_array($handler)) {
            // handler ya es [mw..., controller]
            $handler = array_merge($ctx['middleware'], $handler);
         } else {
            // handler es callable simple → lo volvemos [mw..., callable]
            $handler = array_merge($ctx['middleware'], [$handler]);
         }
      }

      return [$path, $handler];
   }

   public function get(string $path, callable|array $handler): void
   {
      [$path, $handler] = $this->applyGroupContext($path, $handler);
      $this->routes['GET'][$path] = $handler;
   }

   public function post(string $path, callable|array $handler): void
   {
      [$path, $handler] = $this->applyGroupContext($path, $handler);
      $this->routes['POST'][$path] = $handler;
   }

   public function put(string $path, callable|array $handler): void
   {
      [$path, $handler] = $this->applyGroupContext($path, $handler);
      $this->routes['PUT'][$path] = $handler;
   }

   public function patch(string $path, callable|array $handler): void
   {
      [$path, $handler] = $this->applyGroupContext($path, $handler);
      $this->routes['PATCH'][$path] = $handler;
   }

   public function delete(string $path, callable|array $handler): void
   {
      [$path, $handler] = $this->applyGroupContext($path, $handler);
      $this->routes['DELETE'][$path] = $handler;
   }

   public function dispatch(Request $req): void
   {
      $method  = $req->method;
      $path    = $req->uri ?: '/';
      $handler = $this->routes[$method][$path] ?? null;

      if (!$handler) {
         Response::error(
            $req,
            404,
            [
               'ok'    => false,
               'error' => [
                  'code' => 'NOT_FOUND',
                  'path' => $path,
                  'message' => 'Ruta no encontrada',
               ],
            ]
         );
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

               Response::error(
                  $rq,
                  500,
                  [
                     'ok'    => false,
                     'error' => [
                        'code'    => 'MW_INVALID',
                        'message' => 'Middleware inválido en la ruta',
                     ],
                  ]
               );
            };
         }

         // Ejecutar la cadena
         $next($req);
         return;
      }

      Response::error(
         $req,
         500,
         [
            'ok'    => false,
            'error' => [
               'code'    => 'HANDLER_INVALID',
               'message' => 'Handler inválido en la ruta',
            ],
         ]
      );
   }
}
