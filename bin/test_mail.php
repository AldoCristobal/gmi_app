<?php

declare(strict_types=1);

// 1) Cargar bootstrap CLI (esto carga .env en $_ENV/$_SERVER)
require __DIR__ . '/../bootstrap/cli.php';

use App\Support\Env;
use App\Support\Mailer;

try {
   // 2) Mostrar variables como las verá el Mailer (Env::get, no getenv)
   echo "SMTP_HOST=" . (Env::get('SMTP_HOST', '(vacío)')) . PHP_EOL;
   echo "SMTP_FROM=" . (Env::get('SMTP_FROM', '(vacío)')) . PHP_EOL;

   // 3) Destinatario: usa argumento 1, o cae a SMTP_USER, o por último a FROM
   $to = $argv[1]
      ?? Env::get('SMTP_USER', '')
      ?? Env::get('SMTP_FROM', '');

   if (!$to) {
      throw new \RuntimeException('No tengo destinatario. Pásalo como argumento: php bin/test_mail.php correo@dominio.com');
   }

   // 4) Enviar
   $mailer  = new Mailer();
   $subject = '🧪 Prueba de correo SMTP desde ERP-GMI';
   $html    = '<h2>Prueba SMTP OK</h2><p>' . date('Y-m-d H:i:s') . '</p>';

   $mailer->send($to, $subject, $html);
   echo "✅ Correo de prueba enviado a: {$to}\n";
} catch (\Throwable $e) {
   echo "❌ Error al enviar: " . $e->getMessage() . "\n";
}
