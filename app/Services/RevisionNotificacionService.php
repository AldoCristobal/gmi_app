<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\RevisionRepository;
use App\Repositories\RevisionNotificacionRepository;
use App\Support\Mailer;

final class RevisionNotificacionService
{
   public function __construct(
      private RevisionRepository $revRepo,
      private RevisionNotificacionRepository $logRepo,
      private Mailer $mailer
   ) {}

   /**
    * Envía correos para revisiones que vencen en 5 días (estatus 'en_proceso').
    * Retorna métricas del proceso.
    */
   public function sendPreVencimiento5(): array
   {
      $hoyMx    = (new \DateTimeImmutable('today'))->format('Y-m-d');
      $objetivo = (new \DateTimeImmutable('today +5 days'))->format('Y-m-d');

      $revisiones = $this->revRepo->findRevisionesVencenEl($objetivo);

      $stats = [
         'fecha_corrida'  => $hoyMx,
         'fecha_objetivo' => $objetivo,
         'total'   => count($revisiones),
         'enviadas' => 0,
         'omitidas' => 0,
         'fallidas' => 0,
         'detalles' => []
      ];

      foreach ($revisiones as $r) {
         $revisionId = (int)($r['id'] ?? 0);
         $dest       = trim((string)($r['responsable_email'] ?? ''));

         if ($revisionId <= 0) {
            continue;
         }

         if ($dest === '') {
            $this->logRepo->insertOmitida($revisionId, 'proxima', '(sin email)', 5, 'responsable sin email');
            $stats['omitidas']++;
            $stats['detalles'][] = ['revision_id' => $revisionId, 'status' => 'omitida', 'motivo' => 'sin_email'];
            continue;
         }

         if ($this->logRepo->existsForToday($revisionId, 'proxima', $dest)) {
            $stats['detalles'][] = ['revision_id' => $revisionId, 'status' => 'ya_registrada', 'to' => $dest];
            continue;
         }

         $asunto = sprintf(
            '[ERP-GMI] Aviso: “%s” vence el %s',
            (string)($r['nombre'] ?? 'Revisión'),
            (new \DateTimeImmutable((string)$r['fecha_vencimiento']))->format('d/m/Y')
         );

         $linkDetalle = rtrim((string)(getenv('APP_URL') ?: 'http://localhost'), '/')
            . '/revisiones/show?id=' . $revisionId;

         $html = $this->renderCorreo($r, $linkDetalle);

         try {
            $this->mailer->send($dest, $asunto, $html);
            $this->logRepo->insertEnviada($revisionId, 'proxima', $dest, 5);
            $stats['enviadas']++;
            $stats['detalles'][] = ['revision_id' => $revisionId, 'status' => 'enviada', 'to' => $dest];
         } catch (\Throwable $ex) {
            $this->logRepo->insertFallida($revisionId, 'proxima', $dest, 5, mb_substr($ex->getMessage(), 0, 500));
            $stats['fallidas']++;
            $stats['detalles'][] = ['revision_id' => $revisionId, 'status' => 'fallida', 'to' => $dest, 'error' => $ex->getMessage()];
         }
      }

      return $stats;
   }

   private function renderCorreo(array $r, string $linkDetalle): string
   {
      $nombre    = htmlspecialchars((string)($r['nombre'] ?? ''), ENT_QUOTES, 'UTF-8');
      $vence     = (new \DateTimeImmutable((string)$r['fecha_vencimiento']))->format('d/m/Y');
      $ejercicio = htmlspecialchars((string)($r['ejercicio'] ?? ''), ENT_QUOTES, 'UTF-8');
      $impuesto  = htmlspecialchars((string)($r['tipo_impuesto'] ?? ''), ENT_QUOTES, 'UTF-8');
      $depend    = htmlspecialchars((string)($r['dependencia'] ?? ''), ENT_QUOTES, 'UTF-8');
      $riesgo    = htmlspecialchars((string)($r['riesgo'] ?? ''), ENT_QUOTES, 'UTF-8');
      $link      = htmlspecialchars($linkDetalle, ENT_QUOTES, 'UTF-8');

      return <<<HTML
<!doctype html>
<html>
  <body style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#111;">
    <h2 style="margin:0 0 10px;">Revisión próxima a vencer (5 días)</h2>
    <p>La revisión <strong>“{$nombre}”</strong> está próxima a vencer.</p>
    <ul>
      <li><strong>Vence:</strong> {$vence}</li>
      <li><strong>Ejercicio:</strong> {$ejercicio}</li>
      <li><strong>Tipo de impuesto:</strong> {$impuesto}</li>
      <li><strong>Dependencia:</strong> {$depend}</li>
      <li><strong>Riesgo:</strong> {$riesgo}</li>
    </ul>
    <p><a href="{$link}" style="display:inline-block; padding:10px 14px; text-decoration:none; border-radius:6px; background:#111; color:#fff;">Abrir revisión</a></p>
    <hr>
    <p style="font-size:12px; color:#666;">Mensaje automático de ERP-GMI. Por favor, no responda este correo.</p>
  </body>
</html>
HTML;
   }
}
