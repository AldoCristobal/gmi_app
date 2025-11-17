<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap/cli.php';

use App\Services\RutinaSchedulerService;

// Solo permitir ejecución por CLI
if (php_sapi_name() !== 'cli') {
   http_response_code(403);
   echo "Forbidden\n";
   exit;
}

$svc    = new RutinaSchedulerService();
$result = $svc->ejecutar();

$fecha = $result['fecha'] ?? date('Y-m-d');

echo "[" . $fecha . "] Rutinas ejecutadas\n";
echo "Tareas creadas: " . ($result['creadas'] ?? 0) . "\n";
echo "Rutinas omitidas: " . ($result['omitidas'] ?? 0) . "\n";

if (!empty($result['errores'])) {
   echo "Errores:\n";
   foreach ($result['errores'] as $err) {
      echo "- " . ($err['mensaje'] ?? 'Error desconocido') . "\n";
   }
}
