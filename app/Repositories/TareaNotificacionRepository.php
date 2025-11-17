<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use DateTimeInterface;
use App\Support\DB;

class TareaNotificacionRepository
{
   private PDO $db;

   public function __construct(?PDO $db = null)
   {
      $this->db = DB::pdo();
   }

   /**
    * Crea un registro de notificación para una tarea.
    */
   public function crear(array $data): int
   {
      $sql = "
            INSERT INTO tarea_notificacion (
                tarea_id,
                tipo,
                canal,
                destinatario,
                asunto,
                estado,
                intentos,
                programada_para
            ) VALUES (
                :tarea_id,
                :tipo,
                :canal,
                :destinatario,
                :asunto,
                :estado,
                :intentos,
                :programada_para
            )
        ";

      $stmt = $this->db->prepare($sql);
      $stmt->execute([
         ':tarea_id'        => $data['tarea_id'],
         ':tipo'            => $data['tipo'] ?? 'CREACION',
         ':canal'           => $data['canal'] ?? 'EMAIL',
         ':destinatario'    => $data['destinatario'],
         ':asunto'          => $data['asunto'],
         ':estado'          => $data['estado'] ?? 'PENDIENTE',
         ':intentos'        => $data['intentos'] ?? 0,
         ':programada_para' => $data['programada_para'] ?? date('Y-m-d H:i:s'),
      ]);

      return (int) $this->db->lastInsertId();
   }


   public function findPendientes(DateTimeInterface $ahora): array
   {
      $sql = "
            SELECT *
            FROM tarea_notificacion
            WHERE estado = 'PENDIENTE'
              AND (programada_para IS NULL OR programada_para <= :ahora)
              AND intentos < 3
            ORDER BY programada_para ASC, id ASC
        ";

      $stmt = $this->db->prepare($sql);
      $stmt->execute([':ahora' => $ahora->format('Y-m-d H:i:s')]);

      return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
   }


   public function marcarEnviada(int $id): void
   {
      $sql = "
            UPDATE tarea_notificacion
            SET estado='ENVIADO',
                enviado_en=NOW(),
                intentos=intentos+1
            WHERE id=:id
        ";
      $stmt = $this->db->prepare($sql);
      $stmt->execute([':id' => $id]);
   }


   public function marcarFallida(int $id, string $error): void
   {
      $sql = "
            UPDATE tarea_notificacion
            SET estado='ERROR',
                intentos=intentos+1,
                error_ultimo=:err,
                ultimo_intento_en=NOW()
            WHERE id=:id
        ";
      $stmt = $this->db->prepare($sql);
      $stmt->execute([
         ':id'  => $id,
         ':err' => $error
      ]);
   }
}
