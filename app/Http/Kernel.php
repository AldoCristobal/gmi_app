<?php

declare(strict_types=1);

namespace App\Http;

final class Kernel
{
   /** Si luego quieres middlewares globales, orquéstralos aquí */
   public function handle(Request $req, Router $router): void
   {
      $router->dispatch($req);
   }
}
