<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use App\Support\DB;

final class TareaDocumentoRepository
{
   private PDO $db;

   public function __construct(?PDO $db = null)
   {
      // Ajusta DB::conn() al método real de tu helper (ej. DB::getConnection())
      $this->db = $db ?? DB::pdo();
   }

   /**
    * @return array<int,array<string,mixed>>
    */
   public function listByTarea(int $tareaId): array
   {
      $sql = "
         SELECT
            td.id,
            td.tarea_id,
            td.nombre_original,
            td.archivo_path,
            td.mime_type,
            td.extension,
            td.size_bytes,
            td.subido_por,
            u.nombre AS subido_por_nombre,
            td.nota,
            td.creado_en
         FROM tarea_documento td
         LEFT JOIN usuario u ON u.id = td.subido_por
         WHERE td.tarea_id = :tarea_id
         ORDER BY td.creado_en ASC, td.id ASC
      ";
      $stmt = $this->db->prepare($sql);
      $stmt->execute([':tarea_id' => $tareaId]);

      return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
   }

   /**
    * @return array<string,mixed>|null
    */
   public function findById(int $id): ?array
   {
      $sql = "
         SELECT
            td.*,
            u.nombre AS subido_por_nombre
         FROM tarea_documento td
         LEFT JOIN usuario u ON u.id = td.subido_por
         WHERE td.id = :id
         LIMIT 1
      ";
      $stmt = $this->db->prepare($sql);
      $stmt->execute([':id' => $id]);
      $row = $stmt->fetch(PDO::FETCH_ASSOC);
      return $row ?: null;
   }

   /**
    * Inserta un documento y devuelve el ID
    *
    * @param array<string,mixed> $data
    */
   public function insert(array $data): int
   {
      $sql = "
         INSERT INTO tarea_documento
            (tarea_id, nombre_original, archivo_path, mime_type, extension, size_bytes, subido_por, nota)
         VALUES
            (:tarea_id, :nombre_original, :archivo_path, :mime_type, :extension, :size_bytes, :subido_por, :nota)
      ";

      $stmt = $this->db->prepare($sql);
      $stmt->execute([
         ':tarea_id'        => $data['tarea_id'],
         ':nombre_original' => $data['nombre_original'],
         ':archivo_path'    => $data['archivo_path'],
         ':mime_type'       => $data['mime_type'] ?? null,
         ':extension'       => $data['extension'] ?? null,
         ':size_bytes'      => $data['size_bytes'] ?? null,
         ':subido_por'      => $data['subido_por'] ?? null,
         ':nota'            => $data['nota'] ?? null,
      ]);

      return (int)$this->db->lastInsertId();
   }

   public function delete(int $id): bool
   {
      $stmt = $this->db->prepare("DELETE FROM tarea_documento WHERE id = :id");
      return $stmt->execute([':id' => $id]);
   }
}
