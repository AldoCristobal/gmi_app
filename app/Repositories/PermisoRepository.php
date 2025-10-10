<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use App\Support\DB;

final class PermisoRepository
{
   private PDO $db;

   public function __construct()
   {
      $this->db = DB::pdo();
      // Recomendado (o setearlo en DB::pdo()):
      // $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
   }

   public function list(string $q = ''): array
   {
      if ($q !== '') {
         $st = $this->db->prepare(
            "SELECT id, clave, descripcion
                 FROM permiso
                 WHERE clave LIKE :q
                 ORDER BY clave"
         );
         $st->execute([':q' => "%{$q}%"]);
         return $st->fetchAll(PDO::FETCH_ASSOC);
      }
      $st = $this->db->query(
         "SELECT id, clave, descripcion
             FROM permiso
             ORDER BY clave"
      );
      return $st->fetchAll(PDO::FETCH_ASSOC);
   }

   /** Devuelve true si existe otra fila con esa clave (excluyendo opcionalmente un id). */
   public function existsClave(string $clave, ?int $excludeId = null): bool
   {
      if ($excludeId) {
         $st = $this->db->prepare("SELECT COUNT(*) FROM permiso WHERE clave = :c AND id <> :id");
         $st->execute([':c' => $clave, ':id' => $excludeId]);
      } else {
         $st = $this->db->prepare("SELECT COUNT(*) FROM permiso WHERE clave = :c");
         $st->execute([':c' => $clave]);
      }
      return (int)$st->fetchColumn() > 0;
   }

   public function create(string $clave, ?string $desc): int
   {
      $st = $this->db->prepare(
         "INSERT INTO permiso (clave, descripcion)
             VALUES (:c, :d)"
      );
      $st->execute([':c' => $clave, ':d' => $desc]);
      return (int)$this->db->lastInsertId();
   }

   public function update(int $id, string $clave, ?string $desc): bool
   {
      return $this->db->prepare(
         "UPDATE permiso
             SET clave = :c, descripcion = :d
             WHERE id = :id"
      )->execute([':c' => $clave, ':d' => $desc, ':id' => $id]);
   }

   public function delete(int $id): bool
   {
      return $this->db->prepare("DELETE FROM permiso WHERE id = :id")
         ->execute([':id' => $id]);
   }

   /** Opcional: cuántos roles lo usan (para bloquear delete si quieres). */
   public function inUseCount(int $permisoId): int
   {
      $st = $this->db->prepare("SELECT COUNT(*) FROM rol_permiso WHERE permiso_id = :id");
      $st->execute([':id' => $permisoId]);
      return (int)$st->fetchColumn();
   }
}
