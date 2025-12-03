<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use App\Support\DB;

final class TareaEvaluacionRepository
{
   private PDO $db;

   public function __construct(?PDO $db = null)
   {
      $this->db = $db ?? DB::pdo();
   }

   public function insertEvaluation(array $d): int
   {
      $sql = "
         INSERT INTO tarea_evaluacion (
            tarea_id,
            evaluador_id,
            estado_anterior,
            estado_nuevo,
            comentario
         ) VALUES (
            :tarea_id,
            :evaluador_id,
            :estado_anterior,
            :estado_nuevo,
            :comentario
         )
      ";

      $st = $this->db->prepare($sql);
      $st->execute([
         ':tarea_id'        => $d['tarea_id'],
         ':evaluador_id'    => $d['evaluador_id'],
         ':estado_anterior' => $d['estado_anterior'],
         ':estado_nuevo'    => $d['estado_nuevo'],
         ':comentario'      => $d['comentario'] ?? null,
      ]);

      return (int)$this->db->lastInsertId();
   }

   public function historyByTask(int $tareaId): array
   {
      $sql = "
         SELECT
            te.id,
            te.tarea_id,
            te.evaluador_id,
            u.nombre AS evaluador_nombre,
            te.estado_anterior,
            te.estado_nuevo,
            te.comentario,
            te.creado_en
         FROM tarea_evaluacion te
         JOIN usuario u ON u.id = te.evaluador_id
         WHERE te.tarea_id = :tid
         ORDER BY te.creado_en DESC, te.id DESC
      ";

      $st = $this->db->prepare($sql);
      $st->execute([':tid' => $tareaId]);

      return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
   }

   /**
    * Historial filtrado (para reportes futuros).
    */
   public function historyList(array $filters, int $page, int $size): array
   {
      $where  = '1=1';
      $params = [];

      if (!empty($filters['evaluador_id'])) {
         $where .= ' AND te.evaluador_id = :ev';
         $params[':ev'] = (int)$filters['evaluador_id'];
      }

      if (!empty($filters['desde'])) {
         $where .= ' AND te.creado_en >= :d1';
         $params[':d1'] = $filters['desde'] . ' 00:00:00';
      }

      if (!empty($filters['hasta'])) {
         $where .= ' AND te.creado_en <= :d2';
         $params[':d2'] = $filters['hasta'] . ' 23:59:59';
      }

      $offset = ($page - 1) * $size;

      $stc = $this->db->prepare("
         SELECT COUNT(*)
         FROM tarea_evaluacion te
         WHERE {$where}
      ");
      $stc->execute($params);
      $total = (int)$stc->fetchColumn();

      $sql = "
         SELECT
            te.id,
            te.tarea_id,
            te.evaluador_id,
            u.nombre AS evaluador_nombre,
            te.estado_anterior,
            te.estado_nuevo,
            te.comentario,
            te.creado_en
         FROM tarea_evaluacion te
         JOIN usuario u ON u.id = te.evaluador_id
         WHERE {$where}
         ORDER BY te.creado_en DESC, te.id DESC
         LIMIT :lim OFFSET :off
      ";

      $st = $this->db->prepare($sql);
      foreach ($params as $k => $v) {
         $st->bindValue($k, $v);
      }
      $st->bindValue(':lim', $size, PDO::PARAM_INT);
      $st->bindValue(':off', $offset, PDO::PARAM_INT);
      $st->execute();

      $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

      return ['rows' => $rows, 'total' => $total];
   }
}
