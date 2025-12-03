<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use PDOException;
use App\Support\DB;

final class RoleRepository
{
   private PDO $db;

   public function __construct()
   {
      $this->db = DB::pdo();
      // Recomendado: configurar fetch asociativo por defecto en tu DB bootstrap
      // $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
   }

   /**
    * NUEVO ESTÁNDAR:
    * Lista roles con filtros opcionales.
    * Filtros soportados:
    *  - q: string (búsqueda en nombre/slug)
    */
   public function list(array $filters = []): array
   {
      $q = $filters['q'] ?? '';

      if ($q !== '') {
         $st = $this->db->prepare(
            "SELECT id,nombre,slug,descripcion,prioridad,activo,created_at,updated_at
             FROM rol
             WHERE nombre LIKE :q OR slug LIKE :q
             ORDER BY prioridad DESC, nombre ASC"
         );
         $st->execute([':q' => "%{$q}%"]);
         return $st->fetchAll(PDO::FETCH_ASSOC);
      }

      $st = $this->db->query(
         "SELECT id,nombre,slug,descripcion,prioridad,activo,created_at,updated_at
          FROM rol
          ORDER BY prioridad DESC, nombre ASC"
      );
      return $st->fetchAll(PDO::FETCH_ASSOC);
   }

   /**
    * ALIAS LEGACY:
    * Conservado para compatibilidad. Internamente usa list().
    */
   public function findAll(string $q = ''): array
   {
      return $this->list(['q' => $q]);
   }

   /**
    * NUEVO ESTÁNDAR:
    * Obtiene un rol por su ID o null si no existe.
    */
   public function findById(int $id): ?array
   {
      $st = $this->db->prepare(
         "SELECT
         id,
         nombre,
         slug,
         descripcion,
         prioridad,
         activo,
         home_menu_id,
         created_at,
         updated_at
       FROM rol
       WHERE id = :id"
      );
      $st->execute([':id' => $id]);
      $row = $st->fetch(PDO::FETCH_ASSOC);

      return $row === false ? null : $row;
   }


   /**
    * Crea rol. $data debe venir normalizado y validado desde el Service.
    * Mantiene el nombre create (ya estándar).
    */
   public function create(array $data): int
   {
      $sql = "INSERT INTO rol (nombre, slug, descripcion, prioridad, activo)
              VALUES (:n, :s, :d, :p, :a)";
      $st = $this->db->prepare($sql);
      $st->execute([
         ':n' => $data['nombre'],
         ':s' => $data['slug'],
         ':d' => $data['descripcion'] ?? null,
         ':p' => (int)($data['prioridad'] ?? 100),
         ':a' => (int)($data['activo'] ?? 1),
      ]);
      return (int)$this->db->lastInsertId();
   }

   /**
    * Actualiza rol. Solo actualiza las claves presentes en $data.
    * Acepta: nombre, slug, descripcion, prioridad, activo
    * Mantiene el nombre update (ya estándar).
    */
   public function update(int $id, array $data): bool
   {
      $fields = [];
      $params = [':id' => $id];

      foreach (['nombre', 'slug', 'descripcion', 'prioridad', 'activo'] as $k) {
         if (array_key_exists($k, $data)) {
            $fields[] = "$k = :$k";
            $params[":$k"] = ($k === 'prioridad' || $k === 'activo')
               ? (int)$data[$k]
               : $data[$k];
         }
      }

      if (empty($fields)) {
         return true; // nada que actualizar
      }

      $sql = "UPDATE rol SET " . implode(', ', $fields) . " WHERE id = :id";
      $st = $this->db->prepare($sql);
      return $st->execute($params);
   }

   /**
    * Elimina un rol por ID.
    * Mantiene el nombre delete (ya estándar).
    */
   public function delete(int $id): bool
   {
      $st = $this->db->prepare("DELETE FROM rol WHERE id = :id");
      return $st->execute([':id' => $id]);
   }

   /** Devuelve las CLAVES de permisos del rol. */
   public function getPermKeys(int $roleId): array
   {
      $st = $this->db->prepare(
         "SELECT p.clave
          FROM rol_permiso rp
          JOIN permiso p ON p.id = rp.permiso_id
          WHERE rp.rol_id = :id
          ORDER BY p.clave ASC"
      );
      $st->execute([':id' => $roleId]);
      return array_column($st->fetchAll(PDO::FETCH_ASSOC), 'clave');
   }

   /** Devuelve las claves existentes de una lista (para validar). */
   public function findExistingPermKeys(array $keys): array
   {
      if (empty($keys)) {
         return [];
      }

      $placeholders = implode(',', array_fill(0, count($keys), '?'));
      $st = $this->db->prepare(
         "SELECT clave FROM permiso WHERE clave IN ($placeholders)"
      );
      $st->execute(array_values($keys));
      return array_column($st->fetchAll(PDO::FETCH_ASSOC), 'clave');
   }

   /**
    * Reemplaza todos los permisos del rol usando CLAVES.
    * Retorna cuántos inserts se realizaron.
    */
   public function replacePermsByKeys(int $roleId, array $keys): int
   {
      $this->db->beginTransaction();
      try {
         $del = $this->db->prepare("DELETE FROM rol_permiso WHERE rol_id = :id");
         $del->execute([':id' => $roleId]);

         $inserted = 0;
         if (!empty($keys)) {
            $ins = $this->db->prepare(
               "INSERT INTO rol_permiso (rol_id, permiso_id)
                SELECT :rid, p.id FROM permiso p WHERE p.clave = :k"
            );
            foreach ($keys as $k) {
               $ins->execute([':rid' => $roleId, ':k' => $k]);
               $inserted += $ins->rowCount();
            }
         }

         $this->db->commit();
         return $inserted;
      } catch (PDOException $e) {
         $this->db->rollBack();
         throw $e;
      }
   }

   public function setHomeMenuId(int $rolId, ?int $menuId): void
   {
      $st = $this->db->prepare(
         "UPDATE rol
       SET home_menu_id = :home_menu_id
       WHERE id = :id"
      );
      if ($menuId === null || $menuId <= 0) {
         $st->bindValue(':home_menu_id', null, PDO::PARAM_NULL);
      } else {
         $st->bindValue(':home_menu_id', $menuId, PDO::PARAM_INT);
      }
      $st->bindValue(':id', $rolId, PDO::PARAM_INT);
      $st->execute();
   }
}
