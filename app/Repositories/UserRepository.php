<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use App\Support\DB;

final class UserRepository
{
   private PDO $db;

   public function __construct()
   {
      $this->db = DB::pdo();
   }

   public function findByEmail(string $email): ?array
   {
      $st = $this->db->prepare("
            SELECT u.id, u.nombre, u.email, u.pass_hash, u.activo, u.area_id, u.jefe_id
            FROM usuario u
            WHERE u.email = :email
            LIMIT 1
        ");
      $st->execute([':email' => $email]);
      $row = $st->fetch(PDO::FETCH_ASSOC);
      return $row ?: null;
   }

   public function rolesOfUser(int $userId): array
   {
      $st = $this->db->prepare("
            SELECT r.nombre
            FROM usuario_rol ur
            JOIN rol r ON r.id = ur.rol_id
            WHERE ur.usuario_id = :uid
        ");
      $st->execute([':uid' => $userId]);
      return array_map(fn($r) => $r['nombre'], $st->fetchAll(PDO::FETCH_ASSOC));
   }

   public function permsOfRoles(int $userId): array
   {
      $st = $this->db->prepare("
            SELECT DISTINCT p.clave
            FROM usuario_rol ur
            JOIN rol_permiso rp ON rp.rol_id = ur.rol_id
            JOIN permiso p ON p.id = rp.permiso_id
            WHERE ur.usuario_id = :uid
        ");
      $st->execute([':uid' => $userId]);
      return array_map(fn($r) => $r['clave'], $st->fetchAll(PDO::FETCH_ASSOC));
   }

   /**
    * List users with filters and pagination.
    * Filters supported:
    *  - q: string (search in nombre/email)
    *  - activo: 0|1 (int)
    *
    * Returns:
    * [
    *   'rows'  => array<usuario>,
    *   'total' => int,
    *   'page'  => int,
    *   'size'  => int,
    * ]
    */
   public function list(array $filters = [], int $page = 1, int $size = 20): array
   {
      $where  = [];
      $params = [];

      $q = $filters['q'] ?? null;
      if ($q !== null && $q !== '') {
         $where[] = '(u.nombre LIKE :q OR u.email LIKE :q)';
         $params[':q'] = '%' . $q . '%';
      }

      if (array_key_exists('activo', $filters)) {
         $activo = (int) $filters['activo'];
         if ($activo === 0 || $activo === 1) {
            $where[] = 'u.activo = :activo';
            $params[':activo'] = $activo;
         }
      }

      $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

      $page   = max(1, $page);
      $size   = max(1, $size);
      $offset = ($page - 1) * $size;

      // total
      $stc = $this->db->prepare("SELECT COUNT(*) FROM usuario u {$whereSql}");
      $stc->execute($params);
      $total = (int) $stc->fetchColumn();

      // data + roles_ids_csv + area_name + jefe_name
      $sql = "
            SELECT
                u.id,
                u.nombre,
                u.email,
                u.area_id,
                a.nombre AS area_name,
                u.jefe_id,
                j.nombre AS jefe_name,
                u.activo,
                GROUP_CONCAT(ur.rol_id ORDER BY ur.rol_id SEPARATOR ',') AS roles_ids_csv
            FROM usuario u
            LEFT JOIN usuario_rol ur ON ur.usuario_id = u.id
            LEFT JOIN area a ON a.id = u.area_id
            LEFT JOIN usuario j ON j.id = u.jefe_id
            {$whereSql}
            GROUP BY u.id
            ORDER BY u.id DESC
            LIMIT :lim OFFSET :off
        ";
      $std = $this->db->prepare($sql);
      foreach ($params as $k => $v) {
         $std->bindValue($k, $v);
      }
      $std->bindValue(':lim', $size, PDO::PARAM_INT);
      $std->bindValue(':off', $offset, PDO::PARAM_INT);
      $std->execute();

      $rows = $std->fetchAll(PDO::FETCH_ASSOC) ?: [];

      foreach ($rows as &$r) {
         $csv = $r['roles_ids_csv'] ?? null;
         $r['roles_ids'] = $csv && $csv !== ''
            ? array_map('intval', array_filter(explode(',', $csv)))
            : [];
         unset($r['roles_ids_csv']);
      }

      return [
         'rows'  => $rows,
         'total' => $total,
         'page'  => $page,
         'size'  => $size,
      ];
   }

   public function findById(int $id): ?array
   {
      $st = $this->db->prepare("SELECT * FROM usuario WHERE id=:id LIMIT 1");
      $st->execute([':id' => $id]);
      $r = $st->fetch(PDO::FETCH_ASSOC);
      return $r ?: null;
   }

   public function create(array $d): int
   {
      $st = $this->db->prepare(
         "INSERT INTO usuario(nombre,email,pass_hash,area_id,jefe_id,activo)
             VALUES(:n,:e,:ph,:a,:j,1)"
      );
      $st->execute([
         ':n'  => $d['nombre'],
         ':e'  => $d['email'],
         ':ph' => $d['pass_hash'],
         ':a'  => $d['area_id'],
         ':j'  => $d['jefe_id']
      ]);
      return (int) $this->db->lastInsertId();
   }

   public function update(int $id, array $d): bool
   {
      $st = $this->db->prepare(
         "UPDATE usuario
             SET nombre=:n,email=:e,area_id=:a,jefe_id=:j
             WHERE id=:id"
      );
      return $st->execute([
         ':n'  => $d['nombre'],
         ':e'  => $d['email'],
         ':a'  => $d['area_id'],
         ':j'  => $d['jefe_id'],
         ':id' => $id
      ]);
   }

   public function softDelete(int $id): bool
   {
      return $this->db
         ->prepare("UPDATE usuario SET activo=0 WHERE id=:id")
         ->execute([':id' => $id]);
   }

   public function delete(int $id): bool
   {
      return $this->softDelete($id);
   }

   public function setPassword(int $id, string $hash): bool
   {
      return $this->db
         ->prepare("UPDATE usuario SET pass_hash=:h WHERE id=:id")
         ->execute([':h' => $hash, ':id' => $id]);
   }

   public function roles(int $userId): array
   {
      $st = $this->db->prepare(
         "SELECT r.id,r.nombre
             FROM usuario_rol ur
             JOIN rol r ON r.id=ur.rol_id
             WHERE ur.usuario_id=:id"
      );
      $st->execute([':id' => $userId]);
      return $st->fetchAll(PDO::FETCH_ASSOC);
   }

   public function assignRoles(int $userId, array $roleIds): void
   {
      $this->db->beginTransaction();
      $this->db->prepare("DELETE FROM usuario_rol WHERE usuario_id=:id")
         ->execute([':id' => $userId]);

      if ($roleIds) {
         $st = $this->db->prepare(
            "INSERT INTO usuario_rol(usuario_id,rol_id)
                 VALUES(:u,:r)"
         );
         foreach ($roleIds as $rid) {
            $st->execute([':u' => $userId, ':r' => $rid]);
         }
      }

      $this->db->commit();
   }

   public function findTeamUserIds(int $bossId): array
   {
      $sql = "
            SELECT id
            FROM usuario
            WHERE jefe_id = :jid
              AND activo = 1
        ";

      $st = $this->db->prepare($sql);
      $st->execute([':jid' => $bossId]);

      $rows = $st->fetchAll(PDO::FETCH_COLUMN, 0) ?: [];
      return array_map('intval', $rows);
   }

   public function findHomeMenuVistaByUserId(int $userId): ?string
   {
      $sql = "
      SELECT m.vista
      FROM usuario_rol ur
      INNER JOIN rol r ON r.id = ur.rol_id AND r.activo = 1
      INNER JOIN menu m ON m.id = r.home_menu_id
      WHERE ur.usuario_id = :uid
      ORDER BY r.prioridad ASC
      LIMIT 1
   ";

      $st = $this->db->prepare($sql);
      $st->bindValue(':uid', $userId, PDO::PARAM_INT);
      $st->execute();
      $vista = $st->fetchColumn();

      return $vista !== false ? (string)$vista : null;
   }

}
