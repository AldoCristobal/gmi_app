<?php

declare(strict_types=1);

// Cargar entorno CLI
require __DIR__ . '/../bootstrap/cli.php';

use App\Support\DB;
use App\Support\Mailer;
use App\Repositories\TareaRepository;
use App\Services\TareaNotificacionService;
use App\Repositories\TareaNotificacionRepository;

// Solo CLI
if (php_sapi_name() !== 'cli') {
   http_response_code(403);
   echo "Forbidden\n";
   exit;
}

// Crear dependencias
$db = DB::pdo();

$repoN = new TareaNotificacionRepository($db);
$repoT = new TareaRepository($db);
$mailer = new Mailer();

// Service
$svc = new TareaNotificacionService($repoN, $repoT, $mailer);

// Ejecutar
$result = $svc->procesarPendientes();

// Imprimir resumen
echo "=== CRON NOTIFICACIONES DE TAREAS ===\n";
echo "Fecha: " . ($result['fecha_corrida'] ?? '') . "\n";
echo "Total pendientes: " . ($result['total'] ?? 0) . "\n";
echo "Enviadas: " . ($result['enviadas'] ?? 0) . "\n";
echo "Omitidas: " . ($result['omitidas'] ?? 0) . "\n";
echo "Fallidas: " . ($result['fallidas'] ?? 0) . "\n\n";

if (!empty($result['detalles'])) {
   echo "--- DETALLES ---\n";
   foreach ($result['detalles'] as $d) {
      echo json_encode($d, JSON_UNESCAPED_UNICODE) . "\n";
   }
}
