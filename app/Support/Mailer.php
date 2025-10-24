<?php

declare(strict_types=1);

namespace App\Support;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer
{
   private PHPMailer $mail;

   public function __construct()
   {
      $this->mail = new PHPMailer(true);

      // Charset
      $this->mail->CharSet = 'UTF-8';

      // SMTP
      $this->mail->isSMTP();
      $this->mail->Host       = getenv('SMTP_HOST') ?: 'smtp.example.com';
      $this->mail->Port       = (int)(getenv('SMTP_PORT') ?: 587);
      $this->mail->SMTPAuth   = true;
      $this->mail->Username   = getenv('SMTP_USER') ?: '';
      $this->mail->Password   = getenv('SMTP_PASS') ?: '';

      // TLS/SSL
      $tls = strtolower(getenv('SMTP_TLS') ?: 'true');
      if ($tls === 'ssl') {
         $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
      } elseif ($tls === 'false' || $tls === '0' || $tls === 'off') {
         $this->mail->SMTPSecure = false;
      } else {
         $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
      }

      // Remitente
      $from     = getenv('SMTP_FROM') ?: 'no-reply@example.com';
      $fromName = getenv('SMTP_FROM_NAME') ?: 'ERP-GMI';
      $this->mail->setFrom($from, $fromName);

      // Opcional: timeout y debug
      $this->mail->Timeout   = (int)(getenv('SMTP_TIMEOUT') ?: 15);
      $this->mail->SMTPDebug = 0; // 0 en producción; 2 para depurar
   }

   public function send(string $to, string $subject, string $html): void
   {
      try {
         $this->mail->clearAllRecipients();
         $this->mail->addAddress($to);

         $this->mail->isHTML(true);
         $this->mail->Subject = $subject;
         $this->mail->Body    = $html;

         $this->mail->send();
      } catch (Exception $e) {
         throw new Exception("Error al enviar correo a {$to}: " . $e->getMessage(), 0, $e);
      }
   }
}
