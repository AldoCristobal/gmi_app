<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Support\DB;
use PDO;

final class MenuRolRepository
{
   private PDO $db;

   public function __construct(?PDO $db = null)
   {
      // Igual patrón que el resto de tus repos: si no se pasa PDO, lo obtenemos de DB
      $this->db = $db ?? DB::pdo();
   }

   /**
    * Regresa los IDs de menú asignados a un rol.
    *
    * @return int[]
    */
   public function getMenuIdsByRol(int $rolId): array
   {
      $sql  = 'SELECT menu_id FROM menu_rol WHERE rol_id = :rol_id';
      $stmt = $this->db->prepare($sql);
      $stmt->bindValue(':rol_id', $rolId, PDO::PARAM_INT);
      $stmt->execute();

      // Normalizamos a int
      $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
      return array_map('intval', $ids ?: []);
   }

   /**
    * Borra todas las asignaciones para un rol.
    */
   public function deleteByRol(int $rolId): void
   {
      $sql  = 'DELETE FROM menu_rol WHERE rol_id = :rol_id';
      $stmt = $this->db->prepare($sql);
      $stmt->bindValue(':rol_id', $rolId, PDO::PARAM_INT);
      $stmt->execute();
   }

   /**
    * Inserta múltiples registros en menu_rol.
    * Implementación sencilla: un INSERT por cada menú.
    */
   public function insertMany(int $rolId, array $menuIds): void
   {
      // Normalizar y quitar duplicados
      $menuIds = array_unique(array_map('intval', $menuIds));
      if (empty($menuIds)) {
         return;
      }

      $sql  = 'INSERT IGNORE INTO menu_rol (menu_id, rol_id) VALUES (:menu_id, :rol_id)';
      $stmt = $this->db->prepare($sql);

      foreach ($menuIds as $menuId) {
         if ($menuId <= 0) {
            continue;
         }
         $stmt->bindValue(':menu_id', $menuId, PDO::PARAM_INT);
         $stmt->bindValue(':rol_id', $rolId, PDO::PARAM_INT);
         $stmt->execute();
      }
   }

   /**
    * Helper estándar:
    * Reemplaza completamente los menús asignados al rol por la lista dada.
    * Hace delete + insertMany dentro de una transacción.
    */
   public function setMenuIdsForRol(int $rolId, array $menuIds): void
   {
      // Normalizamos aquí también para evitar basura
      $menuIds = array_unique(array_map('intval', $menuIds));

      $this->db->beginTransaction();
      try {
         // borramos todo lo actual
         $sqlDel = 'DELETE FROM menu_rol WHERE rol_id = :rol_id';
         $del    = $this->db->prepare($sqlDel);
         $del->bindValue(':rol_id', $rolId, PDO::PARAM_INT);
         $del->execute();

         if (!empty($menuIds)) {
            $sqlIns = 'INSERT IGNORE INTO menu_rol (menu_id, rol_id) VALUES (:menu_id, :rol_id)';
            $ins    = $this->db->prepare($sqlIns);

            foreach ($menuIds as $menuId) {
               if ($menuId <= 0) {
                  continue;
               }
               $ins->bindValue(':menu_id', $menuId, PDO::PARAM_INT);
               $ins->bindValue(':rol_id', $rolId, PDO::PARAM_INT);
               $ins->execute();
            }
         }

         $this->db->commit();
      } catch (\Throwable $e) {
         $this->db->rollBack();
         throw $e;
      }
   }
}
