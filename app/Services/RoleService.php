<?php

declare(strict_types=1);

namespace App\Services;

use PDOException;
use App\Repositories\RoleRepository;

final class RoleService
{
   public function __construct(private RoleRepository $repo = new RoleRepository()) {}

   public function listar(string $q = ''): array
   {
      $data = $this->repo->findAll(trim($q));
      return ['ok' => true, 'data' => $data];
   }

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