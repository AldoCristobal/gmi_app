<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\TareaNotificacionRepository;
use App\Repositories\TareaRepository;
use App\Support\Mailer;
use DateTimeImmutable;
use Exception;

/**
 * Procesa la tabla tarea_notificacion y envía correos pendientes.
 */
final class TareaNotificacionService
{
   public function __construct(
      private TareaNotificacionRepository $repoNoti,
      private TareaRepository $repoTarea,
      private Mailer $mailer
   ) {}

   /**
    * Procesa todas las notificaciones PENDIENTES para las cuales:
    *   - programada_para <= ahora
    *   - intentos < 3
    *
    * Retorna métricas.
    */
   public function procesarPendientes(): array
   {
      $ahora = new DateTimeImmutable('now');

      $pendientes = $this->repoNoti->findPendientes($ahora);

      $stats = [
         'fecha_corrida' => $ahora->format('Y-m-d H:i:s'),
         'total'         => count($pendientes),
         'enviadas'      => 0,
         'fallidas'      => 0,
         'omitidas'      => 0,
         'detalles'      => []
      ];

      foreach ($pendientes as $n) {
         $idNoti  = (int) $n['id'];
         $tareaId = (int) $n['tarea_id'];
         $tipo    = (string) $n['tipo'];

         // Recuperar la tarea
         $tarea = $this->repoTarea->findById($tareaId);

         if (!$tarea) {
            $this->repoNoti->marcarFallida($idNoti, 'tarea_no_encontrada');
            $stats['omitidas']++;
            $stats['detalles'][] = [
               'notificacion_id' => $idNoti,
               'status' => 'omitida',
               'motivo' => 'tarea_no_encontrada'
            ];
            continue;
         }

         $email = (string)($n['destinatario'] ?? '');
         if ($email === '') {
            $this->repoNoti->marcarFallida($idNoti, 'sin_destinatario');
            $stats['omitidas']++;
            $stats['detalles'][] = [
               'notificacion_id' => $idNoti,
               'status' => 'omitida',
               'motivo' => 'sin_destinatario'
            ];
            continue;
         }

         // Preparar correo
         try {
            $asunto = $n['asunto'] ?: ('Tarea: ' . ($tarea['titulo'] ?? ''));
            $html   = $this->renderCorreo($tarea);

            $this->mailer->send($email, $asunto, $html);

            $this->repoNoti->marcarEnviada($idNoti);

            $stats['enviadas']++;
            $stats['detalles'][] = [
               'notificacion_id' => $idNoti,
               'status' => 'enviada',
               'to' => $email
            ];
         } catch (Exception $ex) {

            $this->repoNoti->marcarFallida($idNoti, mb_substr($ex->getMessage(), 0, 500));

            $stats['fallidas']++;
            $stats['detalles'][] = [
               'notificacion_id' => $idNoti,
               'status' => 'fallida',
               'to' => $email,
               'error' => $ex->getMessage()
            ];
         }
      }

      return $stats;
   }


   /**
    * Render simple del correo de tarea creada.
    */
   private function renderCorreo(array $tarea): string
   {
      // Campos principales
      $titulo   = htmlspecialchars((string)($tarea['titulo'] ?? ''), ENT_QUOTES, 'UTF-8');
      $vence    = htmlspecialchars((string)($tarea['fecha_vencimiento'] ?? ''), ENT_QUOTES, 'UTF-8');
      $empresa  = htmlspecialchars((string)($tarea['empresa_nombre'] ?? ''), ENT_QUOTES, 'UTF-8');

      $linkBase = rtrim((string)(getenv('APP_URL') ?: 'http://localhost'), '/');
      $linkTask = $linkBase . '/tareas/show?id=' . (int)$tarea['id'];

      return <<<HTML
<!doctype html>
<html>
  <body style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#111;">
    <h2 style="margin:0 0 10px;">Nueva tarea asignada</h2>

    <p><strong>{$titulo}</strong></p>
    <p><strong>Empresa:</strong> {$empresa}</p>
    <p><strong>Vence:</strong> {$vence}</p>

    <p style="margin-top:20px;">
      <a href="{$linkTask}" style="padding:10px 14px; background:#111; color:#fff; text-decoration:none; border-radius:6px;">
        Abrir tarea
      </a>
    </p>

    <hr>
    <p style="font-size:12px; color:#666;">Mensaje automático de ERP-GMI. No responda este correo.</p>
  </body>
</html>
HTML;
   }
}
