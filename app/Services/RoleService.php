<?php

declare(strict_types=1);

namespace App\Services;

use PDOException;
use App\Repositories\RoleRepository;

final class RoleService
{
   public function __construct(private RoleRepository $repo = new RoleRepository()) {}

   /**
    * NUEVO ESTÁNDAR:
    * Lista roles usando filtros.
    * Soporta:
    *  - q: string (búsqueda por nombre/slug)
    *
    * Devuelve SOLO el arreglo de roles (sin envoltura ok/data).
    */
   public function list(array $filters = []): array
   {
      $q = '';
      if (isset($filters['q'])) {
         $q = (string)$filters['q'];
      }

      $res = $this->listar($q);
      return $res['data'] ?? [];
   }

   /**
    * NUEVO ESTÁNDAR:
    * Obtiene un rol por ID.
    * Lanza RuntimeException si no existe.
    */
   public function get(int $id): array
   {
      if ($id <= 0) {
         throw new \InvalidArgumentException('id inválido');
      }

      $row = $this->repo->findById($id);
      if (!$row) {
         throw new \RuntimeException('Rol no encontrado');
      }

      return $row;
   }

   /**
    * NUEVO ESTÁNDAR:
    * Crea un rol y devuelve su ID.
    * Usa la lógica existente de crear() y, si hay error, lanza RuntimeException.
    */
   public function create(array $in): int
   {
      $res = $this->crear($in);

      if (!($res['ok'] ?? false)) {
         $msg = $res['msg'] ?? 'Error al crear rol';
         throw new \RuntimeException($msg);
      }

      return (int)($res['data']['id'] ?? 0);
   }

   /**
    * NUEVO ESTÁNDAR:
    * Actualiza un rol, devuelve true si se actualizó, false si no existe.
    * Si hay error de validación/servidor, lanza RuntimeException.
    */
   public function update(int $id, array $in): bool
   {
      $res = $this->actualizar($id, $in);

      if (!($res['ok'] ?? false)) {
         $code = $res['code'] ?? '';
         if ($code === 'NOT_FOUND') {
            return false;
         }
         $msg = $res['msg'] ?? 'Error al actualizar rol';
         throw new \RuntimeException($msg);
      }

      return true;
   }

   /**
    * NUEVO ESTÁNDAR:
    * Elimina un rol, devuelve true si se eliminó, false si no existía.
    * Si hay error de validación/servidor, lanza RuntimeException.
    */
   public function delete(int $id): bool
   {
      $res = $this->eliminar($id);

      if (!($res['ok'] ?? false)) {
         $code = $res['code'] ?? '';
         if ($code === 'NOT_FOUND') {
            return false;
         }
         $msg = $res['msg'] ?? 'Error al eliminar rol';
         throw new \RuntimeException($msg);
      }

      return true;
   }

   /**
    * MÉTODO LEGACY ACTUAL:
    * Mantiene la firma y comportamiento existente.
    * DEVUELVE envoltura ['ok' => bool, 'data' => [...]].
    */
   public function listar(string $q = ''): array
   {
      $data = $this->repo->findAll(trim($q));
      return ['ok' => true, 'data' => $data];
   }

   /**
    * MÉTODO LEGACY ACTUAL:
    * Crea un rol y devuelve envoltura ['ok' => bool, 'data' => ['id' => int]].
    */
   public function crear(array $in): array
   {
      $nombre = trim((string)($in['nombre'] ?? ''));
      if ($nombre === '') {
         return ['ok' => false, 'code' => 'VALIDATION', 'msg' => 'nombre requerido'];
      }

      // slug: usar el que venga o generarlo
      $slug = isset($in['slug']) && $in['slug'] !== ''
         ? $this->normalizeSlug((string)$in['slug'])
         : $this->slugify($nombre);

      if ($slug === '') {
         return ['ok' => false, 'code' => 'VALIDATION', 'msg' => 'slug inválido'];
      }

      $dto = [
         'nombre'      => $nombre,
         'slug'        => $slug,
         'descripcion' => isset($in['descripcion']) ? trim((string)$in['descripcion']) : null,
         'prioridad'   => isset($in['prioridad']) ? (int)$in['prioridad'] : 100,
         'activo'      => isset($in['activo']) ? (int)$in['activo'] : 1,
      ];

      try {
         $id = $this->repo->create($dto);
         return ['ok' => true, 'data' => ['id' => $id]];
      } catch (PDOException $e) {
         // 23000 -> violación de UNIQUE (nombre/slug)
         if ($e->getCode() === '23000') {
            return ['ok' => false, 'code' => 'CONFLICT', 'msg' => 'Nombre o slug duplicado'];
         }
         return ['ok' => false, 'code' => 'SERVER', 'msg' => 'Error al crear rol'];
      }
   }

   /**
    * MÉTODO LEGACY ACTUAL:
    * Actualiza un rol y devuelve envoltura ['ok' => bool, ...].
    */
   public function actualizar(int $id, array $in): array
   {
      if ($id <= 0) return ['ok' => false, 'code' => 'VALIDATION', 'msg' => 'id inválido'];

      $data = [];

      if (array_key_exists('nombre', $in)) {
         $nombre = trim((string)$in['nombre']);
         if ($nombre === '') return ['ok' => false, 'code' => 'VALIDATION', 'msg' => 'nombre requerido'];
         $data['nombre'] = $nombre;

         // si también te mandan slug, lo normalizamos; si no, NO lo cambiamos automáticamente
         if (array_key_exists('slug', $in)) {
            $slug = $this->normalizeSlug((string)$in['slug']);
            if ($slug === '') return ['ok' => false, 'code' => 'VALIDATION', 'msg' => 'slug inválido'];
            $data['slug'] = $slug;
         }
      } elseif (array_key_exists('slug', $in)) {
         $slug = $this->normalizeSlug((string)$in['slug']);
         if ($slug === '') return ['ok' => false, 'code' => 'VALIDATION', 'msg' => 'slug inválido'];
         $data['slug'] = $slug;
      }

      if (array_key_exists('descripcion', $in)) {
         $data['descripcion'] = $in['descripcion'] !== null ? trim((string)$in['descripcion']) : null;
      }
      if (array_key_exists('prioridad', $in)) {
         $data['prioridad'] = (int)$in['prioridad'];
      }
      if (array_key_exists('activo', $in)) {
         $a = (int)$in['activo'];
         $data['activo'] = ($a === 0 || $a === 1) ? $a : 1;
      }

      try {
         $ok = $this->repo->update($id, $data);
         return $ok ? ['ok' => true] : ['ok' => false, 'code' => 'NOT_FOUND', 'msg' => 'Rol no actualizado'];
      } catch (PDOException $e) {
         if ($e->getCode() === '23000') {
            return ['ok' => false, 'code' => 'CONFLICT', 'msg' => 'Nombre o slug duplicado'];
         }
         return ['ok' => false, 'code' => 'SERVER', 'msg' => 'Error al actualizar rol'];
      }
   }

   /**
    * MÉTODO LEGACY ACTUAL:
    * Elimina un rol, devuelve envoltura ['ok' => bool, ...].
    */
   public function eliminar(int $id): array
   {
      if ($id <= 0) return ['ok' => false, 'code' => 'VALIDATION', 'msg' => 'id inválido'];

      // Política simple: eliminar directo. Si quieres bloquear si está asignado, aquí checks en usuario_rol.
      $ok = $this->repo->delete($id);
      return $ok ? ['ok' => true] : ['ok' => false, 'code' => 'NOT_FOUND', 'msg' => 'Rol no encontrado'];
   }

   /** Retorna array de CLAVES de permisos del rol. */
   public function permisos(int $rolId): array
   {
      if ($rolId <= 0) return ['ok' => false, 'code' => 'VALIDATION', 'msg' => 'id inválido'];
      $keys = $this->repo->getPermKeys($rolId);
      return ['ok' => true, 'data' => $keys];
   }

   /** Guarda permisos del rol a partir de CLAVES. */
   public function guardarPermisos(int $rolId, array $claves): array
   {
      if ($rolId <= 0) return ['ok' => false, 'code' => 'VALIDATION', 'msg' => 'id inválido'];

      // Normaliza claves: string, trim, minúsculas, no vacías
      $norm = [];
      foreach ($claves as $k) {
         if (!is_string($k)) continue;
         $kk = strtolower(trim($k));
         if ($kk !== '') $norm[$kk] = true;
      }
      $keys = array_keys($norm);

      // valida que existan en 'permiso'
      $existing = $this->repo->findExistingPermKeys($keys);
      $missing = array_values(array_diff($keys, $existing));
      if (!empty($missing)) {
         return ['ok' => false, 'code' => 'VALIDATION', 'msg' => 'Permisos inexistentes', 'missing' => $missing];
      }

      try {
         $saved = $this->repo->replacePermsByKeys($rolId, $existing);
         return ['ok' => true, 'saved' => $saved];
      } catch (\Throwable $e) {
         return ['ok' => false, 'code' => 'SERVER', 'msg' => 'Error al guardar permisos'];
      }
   }

   /** ------- Helpers de dominio ------- */

   private function slugify(string $name): string
   {
      $n = strtolower(trim($name));
      // reemplaza espacios por '_' y quita caracteres no permitidos
      $n = preg_replace('/\s+/', '_', $n);
      $n = preg_replace('/[^a-z0-9_]+/', '', $n);
      return $n ?? '';
   }

   private function normalizeSlug(string $slug): string
   {
      $s = strtolower(trim($slug));
      $s = preg_replace('/\s+/', '_', $s);
      $s = preg_replace('/[^a-z0-9_]+/', '', $s);
      return $s ?? '';
   }
}
