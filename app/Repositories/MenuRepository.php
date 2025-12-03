<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use App\Support\DB;

final class MenuRepository
{
   private PDO $db;

   public function __construct()
   {
      $this->db = DB::pdo();
      $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
   }

   /**
    * NUEVO ESTÁNDAR FLEXIBLE:
    * Lista plana por namespace (para el editor).
    *
    * Soporta dos formas:
    *  - list('sidebar')
    *  - list(['namespace' => 'sidebar'])
    */
   public function list(string|array $arg = 'sidebar'): array
   {
      if (is_array($arg)) {
         $namespace = $arg['namespace'] ?? 'sidebar';
      } else {
         $namespace = $arg;
      }

      $st = $this->db->prepare("
            SELECT id,parent_id,etiqueta,icono,vista,url_externa,target,tipo,slug,
                   orden,visible,requiere_permiso,badge_text,badge_variant,namespace
            FROM menu
            WHERE namespace = :ns
            ORDER BY parent_id, orden, id
        ");
      $st->execute([':ns' => $namespace]);
      return $st->fetchAll();
   }

   /** Máximo orden entre los hermanos */
   public function maxOrden(?int $parentId, string $namespace = 'sidebar'): ?int
   {
      if ($parentId === null) {
         $st = $this->db->prepare("SELECT MAX(orden) AS m FROM menu WHERE parent_id IS NULL AND namespace=:ns");
         $st->execute([':ns' => $namespace]);
      } else {
         $st = $this->db->prepare("SELECT MAX(orden) AS m FROM menu WHERE parent_id=:p AND namespace=:ns");
         $st->execute([':p' => $parentId, ':ns' => $namespace]);
      }
      $row = $st->fetch();
      return isset($row['m']) ? (int)$row['m'] : null;
   }

   /** Verifica slug único entre hermanos (mismo parent_id) */
   public function slugExists(?int $parentId, string $slug, ?int $excludeId = null): bool
   {
      $sql = "SELECT 1 FROM menu WHERE slug=:s AND ";
      if ($parentId === null) {
         $sql .= "parent_id IS NULL";
         $params = [':s' => $slug];
      } else {
         $sql .= "parent_id=:p";
         $params = [':s' => $slug, ':p' => $parentId];
      }
      if ($excludeId) {
         $sql .= " AND id <> :id";
         $params[':id'] = $excludeId;
      }
      $sql .= " LIMIT 1";
      $st = $this->db->prepare($sql);
      $st->execute($params);
      return (bool)$st->fetch();
   }

   public function create(array $d): int
   {
      $st = $this->db->prepare("
            INSERT INTO menu
            (parent_id, etiqueta, icono, vista, url_externa, target, tipo, slug,
             orden, visible, requiere_permiso, badge_text, badge_variant, namespace)
            VALUES
            (:p, :e, :i, :v, :u, :t, :tp, :sl, :o, :vis, :rp, :bt, :bv, :ns)
        ");
      $st->bindValue(':p',  $d['parent_id'] ?? null, ($d['parent_id'] ?? null) === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
      $st->bindValue(':e',  $d['etiqueta'] ?? null, PDO::PARAM_STR);
      $st->bindValue(':i',  $d['icono'] ?? null, PDO::PARAM_STR);
      $st->bindValue(':v',  $d['vista'] ?? null, PDO::PARAM_STR);
      $st->bindValue(':u',  $d['url_externa'] ?? null, PDO::PARAM_STR);
      $st->bindValue(':t',  $d['target'] ?? '_self', PDO::PARAM_STR);
      $st->bindValue(':tp', $d['tipo'] ?? 'item', PDO::PARAM_STR);
      $st->bindValue(':sl', $d['slug'] ?? null, PDO::PARAM_STR);
      $st->bindValue(':o',  (int)($d['orden'] ?? 0), PDO::PARAM_INT);
      $st->bindValue(':vis', (int)($d['visible'] ?? 1), PDO::PARAM_INT);
      $st->bindValue(':rp', $d['requiere_permiso'] ?? null, PDO::PARAM_STR);
      $st->bindValue(':bt', $d['badge_text'] ?? null, PDO::PARAM_STR);
      $st->bindValue(':bv', $d['badge_variant'] ?? null, PDO::PARAM_STR);
      $st->bindValue(':ns', $d['namespace'] ?? 'sidebar', PDO::PARAM_STR);
      $st->execute();
      return (int)$this->db->lastInsertId();
   }

   /** NUEVO ESTÁNDAR: findById(). */
   public function findById(int $id): ?array
   {
      $st = $this->db->prepare("SELECT * FROM menu WHERE id=:id LIMIT 1");
      $st->execute([':id' => $id]);
      $row = $st->fetch();
      return $row ?: null;
   }

   /** LEGACY: alias a findById() para compatibilidad. */
   public function getById(int $id): ?array
   {
      return $this->findById($id);
   }

   public function getRolesForMenu(int $menuId): array
   {
      $st = $this->db->prepare("SELECT rol_id FROM menu_rol WHERE menu_id = :m");
      $st->execute([':m' => $menuId]);
      return array_map('intval', array_column($st->fetchAll(), 'rol_id'));
   }

   /** Update parcial solo con campos permitidos (sin parent_id/orden aquí) */
   public function update(int $id, array $d): bool
   {
      $allowed = [
         'etiqueta',
         'icono',
         'vista',
         'url_externa',
         'target',
         'tipo',
         'slug',
         'visible',
         'requiere_permiso',
         'badge_text',
         'badge_variant',
         'namespace'
      ];
      $set = [];
      $params = [':id' => $id];
      foreach ($allowed as $k) {
         if (array_key_exists($k, $d)) {
            $set[] = "$k = :$k";
            $params[":$k"] = $d[$k];
         }
      }
      if (!$set) return true; // nada que actualizar
      $sql = "UPDATE menu SET " . implode(', ', $set) . " WHERE id=:id";
      $st = $this->db->prepare($sql);
      return $st->execute($params);
   }

   /** Elimina un nodo (solo ese registro) */
   public function delete(int $id): bool
   {
      $st = $this->db->prepare("DELETE FROM menu WHERE id=:id");
      return $st->execute([':id' => $id]);
   }

   /**
    * Borra en cascada (subárbol) sin depender de FK CASCADE.
    */
   public function deleteCascade(int $id): bool
   {
      $this->db->beginTransaction();
      try {
         $this->deleteSubtree($id);
         $this->db->commit();
         return true;
      } catch (\Throwable $e) {
         $this->db->rollBack();
         throw $e;
      }
   }

   private function deleteSubtree(int $id): void
   {
      $st = $this->db->prepare("SELECT id FROM menu WHERE parent_id=:p");
      $st->execute([':p' => $id]);
      $children = $st->fetchAll();
      foreach ($children as $ch) {
         $this->deleteSubtree((int)$ch['id']);
      }
      $this->delete($id);
   }

   public function reparentChildrenToParent(int $fromId, ?int $toParentId): bool
   {
      $this->db->beginTransaction();
      try {
         $st = $this->db->prepare("SELECT id FROM menu WHERE parent_id=:p ORDER BY orden, id");
         $st->execute([':p' => $fromId]);
         $children = $st->fetchAll();

         if ($toParentId === null) {
            $stMax = $this->db->prepare("SELECT COALESCE(MAX(orden), -1) AS m FROM menu WHERE parent_id IS NULL");
            $stMax->execute();
         } else {
            $stMax = $this->db->prepare("SELECT COALESCE(MAX(orden), -1) AS m FROM menu WHERE parent_id=:p");
            $stMax->execute([':p' => $toParentId]);
         }
         $max  = (int)($stMax->fetch()['m'] ?? -1);
         $next = $max + 1;

         $up = $this->db->prepare("UPDATE menu SET parent_id=:newp, orden=:o WHERE id=:id");
         foreach ($children as $i => $row) {
            $cid = (int)$row['id'];
            if ($toParentId === null) {
               $up->bindValue(':newp', null, PDO::PARAM_NULL);
            } else {
               $up->bindValue(':newp', $toParentId, PDO::PARAM_INT);
            }
            $up->bindValue(':o', $next + $i, PDO::PARAM_INT);
            $up->bindValue(':id', $cid, PDO::PARAM_INT);
            $up->execute();
         }

         $this->db->commit();
         return true;
      } catch (\Throwable $e) {
         $this->db->rollBack();
         throw $e;
      }
   }

   public function reorderBatch(array $changes): int
   {
      if (!$changes) return 0;

      $this->db->beginTransaction();
      try {
         $st = $this->db->prepare("UPDATE menu SET parent_id=:p, orden=:o WHERE id=:id");

         $saved = 0;
         foreach ($changes as $c) {
            $id = (int)($c['id'] ?? 0);
            if ($id <= 0) continue;

            $o = (int)($c['orden'] ?? 0);
            $p = array_key_exists('parent_id', $c)
               ? ($c['parent_id'] === null ? null : (int)$c['parent_id'])
               : null;

            $st->bindValue(':id', $id, PDO::PARAM_INT);
            $st->bindValue(':o', $o, PDO::PARAM_INT);
            if ($p === null) {
               $st->bindValue(':p', null, PDO::PARAM_NULL);
            } else {
               $st->bindValue(':p', $p, PDO::PARAM_INT);
            }

            $st->execute();
            $saved += $st->rowCount();
         }

         $this->db->commit();
         return $saved;
      } catch (\Throwable $e) {
         $this->db->rollBack();
         throw $e;
      }
   }

   public function assignToRoles(int $menuId, array $roleIds): void
   {
      $this->db->beginTransaction();
      try {
         $del = $this->db->prepare("DELETE FROM menu_rol WHERE menu_id=:m");
         $del->execute([':m' => $menuId]);

         if ($roleIds) {
            $ins = $this->db->prepare("INSERT INTO menu_rol(menu_id, rol_id) VALUES(:m, :r)");
            foreach ($roleIds as $rid) {
               $ins->execute([':m' => $menuId, ':r' => (int)$rid]);
            }
         }

         $this->db->commit();
      } catch (\Throwable $e) {
         $this->db->rollBack();
         throw $e;
      }
   }

   public function treeForUser(array $user): array
   {
      $roles = $user['roles'] ?? [];
      if (!$roles) return [];

      $in  = implode(',', array_fill(0, count($roles), '?'));
      $sql = "
            SELECT DISTINCT m.*
            FROM menu m
            JOIN menu_rol mr ON mr.menu_id = m.id
            JOIN rol r ON r.id = mr.rol_id
            WHERE r.nombre IN ($in) AND m.visible = 1
            ORDER BY m.parent_id, m.orden, m.id
        ";
      $st = $this->db->prepare($sql);
      foreach ($roles as $i => $rolNombre) {
         $st->bindValue($i + 1, $rolNombre, PDO::PARAM_STR);
      }
      $st->execute();
      $items = $st->fetchAll();

      $perms = $user['permisos'] ?? [];
      $items = array_values(array_filter($items, function ($it) use ($perms) {
         return empty($it['requiere_permiso']) || in_array($it['requiere_permiso'], $perms, true);
      }));

      $byId = [];
      foreach ($items as $it) {
         $it['children'] = [];
         $byId[$it['id']] = $it;
      }
      $root = [];
      foreach ($byId as $id => &$it) {
         $pid = $it['parent_id'];
         if ($pid !== null && isset($byId[$pid])) {
            $byId[$pid]['children'][] = &$it;
         } else {
            $root[] = &$it;
         }
      }
      return $root;
   }
}
