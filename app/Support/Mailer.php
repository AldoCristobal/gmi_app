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
      $this->mail->CharSet = 'UTF-8';

      // SMTP
      $this->mail->isSMTP();
      $this->mail->Host       = Env::get('SMTP_HOST', '');
      $this->mail->Port       = (int)(Env::get('SMTP_PORT', '587'));
      $this->mail->SMTPAuth   = true;
      $this->mail->Username   = Env::get('SMTP_USER', '');
      $this->mail->Password   = Env::get('SMTP_PASS', '');

      // TLS / SSL
      $tls = strtolower((string)Env::get('SMTP_TLS', 'true'));
      if ($tls === 'ssl') {
         $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
      } elseif ($tls === 'false' || $tls === '0' || $tls === 'off') {
         $this->mail->SMTPSecure = false;
      } else {
         $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
      }

      $from     = Env::get('SMTP_FROM', '');
      $fromName = Env::get('SMTP_FROM_NAME', 'ERP-GMI');

      if ($this->mail->Host === '' || $from === '') {
         throw new Exception('Mailer mal configurado: define SMTP_HOST y SMTP_FROM en .env');
      }

      $this->mail->setFrom($from, $fromName);

      // Timeouts y debug
      $this->mail->Timeout   = (int)(Env::get('SMTP_TIMEOUT', '15'));
      $this->mail->SMTPDebug = 0; // 0 prod; 2 para depurar temporalmente
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
