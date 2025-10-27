<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use App\Support\DB;

final class RevisionNotificacionRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = DB::pdo();
    }

    /** ¿Ya hay un registro hoy para (revision_id, tipo, enviado_a)? */
    public function existsForToday(int $revisionId, string $tipo, string $destinatario): bool
    {
        $sql = "
            SELECT 1
            FROM revision_notificacion
            WHERE revision_id = :rid
              AND tipo = :tipo
              AND enviado_a = :to
              AND enviado_fecha = CURDATE()
            LIMIT 1
        ";
        $st = $this->db->prepare($sql);
        $st->execute([':rid' => $revisionId, ':tipo' => $tipo, ':to' => $destinatario]);
        return (bool)$st->fetchColumn();
    }

    public function insertEnviada(int $revisionId, string $tipo, string $destinatario, int $diasAntes): void
    {
        $sql = "
            INSERT INTO revision_notificacion (revision_id, tipo, enviado_a, dias_antes)
            VALUES (:rid, :tipo, :to, :dias)
        ";
        $st = $this->db->prepare($sql);
        $st->execute([':rid' => $revisionId, ':tipo' => $tipo, ':to' => $destinatario, ':dias' => $diasAntes]);
    }

    public function insertOmitida(int $revisionId, string $tipo, string $destinatario, int $diasAntes, ?string $motivo = null): void
    {
        $sql = "
            INSERT INTO revision_notificacion (revision_id, tipo, enviado_a, dias_antes)
            VALUES (:rid, :tipo, :to, :dias)
        ";
        $st = $this->db->prepare($sql);
        $st->execute([':rid' => $revisionId, ':tipo' => $tipo, ':to' => $destinatario, ':dias' => $diasAntes]);
    }

    public function insertFallida(int $revisionId, string $tipo, string $destinatario, int $diasAntes, string $err): void
    {
        // Igual que insertEnviada por compatibilidad con tu esquema actual.
        $sql = "
            INSERT INTO revision_notificacion (revision_id, tipo, enviado_a, dias_antes)
            VALUES (:rid, :tipo, :to, :dias)
        ";
        $st = $this->db->prepare($sql);
        $st->execute([':rid' => $revisionId, ':tipo' => $tipo, ':to' => $destinatario, ':dias' => $diasAntes]);
    }

    /* ——— Variante si agregas columnas estatus/error_msg ———
    public function insertEnviada(int $rid, string $tipo, string $to, int $dias): void {
        $this->db->prepare("INSERT INTO revision_notificacion (revision_id,tipo,enviado_a,dias_antes,estatus) VALUES (?,?,?,?, 'enviada')")
                 ->execute([$rid,$tipo,$to,$dias]);
    }
    public function insertOmitida(int $rid, string $tipo, string $to, int $dias, ?string $err=null): void {
        $this->db->prepare("INSERT INTO revision_notificacion (revision_id,tipo,enviado_a,dias_antes,estatus,error_msg) VALUES (?,?,?,?, 'omitida', ?)")
                 ->execute([$rid,$tipo,$to,$dias,$err]);
    }
    public function insertFallida(int $rid, string $tipo, string $to, int $dias, string $err): void {
        $this->db->prepare("INSERT INTO revision_notificacion (revision_id,tipo,enviado_a,dias_antes,estatus,error_msg) VALUES (?,?,?,?, 'fallida', ?)")
                 ->execute([$rid,$tipo,$to,$dias,$err]);
    }
    */
}
