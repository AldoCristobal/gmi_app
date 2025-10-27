#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap/cli.php';

use App\Repositories\RevisionRepository;
use App\Repositories\RevisionNotificacionRepository;
use App\Services\RevisionNotificacionService;
use App\Support\Mailer;

// Dependencias (usan App\Support\DB::pdo() internamente)
$revRepo = new RevisionRepository();
$logRepo = new RevisionNotificacionRepository();
$mailer  = new Mailer();

$service = new RevisionNotificacionService($revRepo, $logRepo, $mailer);

// Permitir fecha objetivo por argumento (YYYY-MM-DD) para pruebas
$argvObjetivo = $argv[1] ?? null;
if ($argvObjetivo) {
   if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $argvObjetivo)) {
      fwrite(STDERR, "Formato inválido. Usa YYYY-MM-DD\n");
      exit(1);
   }
   // pequeño “hack”: fijamos la zona de búsqueda mediante variable de entorno temporal
   putenv('REV_OBJETIVO=' . $argvObjetivo);
}

// Si usas la variable REV_OBJETIVO, podrías modificar el Service para leerla.
// Para no tocar el Service, dejamos el objetivo a “hoy +5 días”.
$result = $service->sendPreVencimiento5();

echo sprintf(
   "[%s] Notifs pre-5d — objetivo:%s total:%d enviadas:%d omitidas:%d fallidas:%d\n",
   date('Y-m-d H:i:s'),
   $result['fecha_objetivo'],
   $result['total'],
   $result['enviadas'],
   $result['omitidas'],
   $result['fallidas']
);
