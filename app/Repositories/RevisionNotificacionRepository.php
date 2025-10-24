<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class RevisionNotificacionRepository
{
    public function __construct(private PDO $pdo) {}

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
        $st = $this->pdo->prepare($sql);
        $st->execute([':rid' => $revisionId, ':tipo' => $tipo, ':to' => $destinatario]);
        return (bool)$st->fetchColumn();
    }

    public function insertEnviada(int $revisionId, string $tipo, string $destinatario, int $diasAntes): void
    {
        $sql = "
            INSERT INTO revision_notificacion (revision_id, tipo, enviado_a, dias_antes)
            VALUES (:rid, :tipo, :to, :dias)
        ";
        $st = $this->pdo->prepare($sql);
        $st->execute([
            ':rid'  => $revisionId,
            ':tipo' => $tipo,
            ':to'   => $destinatario,
            ':dias' => $diasAntes
        ]);
    }

    /** Si quieres dejar trazabilidad cuando falta email, registramos igual con “(sin email)”. */
    public function insertOmitida(int $revisionId, string $tipo, string $destinatario, int $diasAntes): void
    {
        $sql = "
            INSERT INTO revision_notificacion (revision_id, tipo, enviado_a, dias_antes)
            VALUES (:rid, :tipo, :to, :dias)
        ";
        $st = $this->pdo->prepare($sql);
        $st->execute([
            ':rid'  => $revisionId,
            ':tipo' => $tipo,
            ':to'   => $destinatario,
            ':dias' => $diasAntes
        ]);
    }

    /** Si no guardas estatus/error en la tabla, este insert mantiene la UNIQUE diaria igualmente. */
    public function insertFallida(int $revisionId, string $tipo, string $destinatario, int $diasAntes): void
    {
        $sql = "
            INSERT INTO revision_notificacion (revision_id, tipo, enviado_a, dias_antes)
            VALUES (:rid, :tipo, :to, :dias)
        ";
        $st = $this->pdo->prepare($sql);
        $st->execute([
            ':rid'  => $revisionId,
            ':tipo' => $tipo,
            ':to'   => $destinatario,
            ':dias' => $diasAntes
        ]);
    }
}
