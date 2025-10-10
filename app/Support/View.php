<?php

declare(strict_types=1);

namespace App\Support;

final class View
{
   /**
    * Renderiza una vista PHP con layout opcional.
    *
    * @param string $name  Nombre de la vista relativa a resources/views (sin .php)
    * @param array  $data  Variables a inyectar
    * @param string $layout  Layout base (opcional)
    */
   public static function render(string $view, array $data = []): string
   {
      $viewFile = dirname(__DIR__, 2) . "/resources/views/{$view}.php";
      if (!is_file($viewFile)) {
         throw new \RuntimeException("View not found: {$view}");
      }

      extract($data, EXTR_SKIP);

      // 1) Renderizar fragmento
      ob_start();
      include $viewFile; // el HTML de la vista queda en $content
      $content = ob_get_clean();

      // 2) Renderizar layout usando $content + variables ($title, $script, $isLogin, etc.)
      $layout = dirname(__DIR__, 2) . "/resources/views/layout.php";
      ob_start();
      include $layout;
      return ob_get_clean();
   }
}
