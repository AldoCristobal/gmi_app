<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\PermisoRepository;

final class PermisoService
{
   public function __construct(private PermisoRepository $repo = new PermisoRepository()) {}

   public function listar(string $q = ''): array
   {
      $data = $this->repo->list(trim($q));
      return ['ok' => true, 'data' => $data];
   }

   public function crear(array $d): array
   {
      $clave = $this->normalizeClave((string)($d['clave'] ?? ''));
      $desc  = array_key_exists('descripcion', $d) ? (string)$d['descripcion'] : null;

      if ($clave === '') {
         return ['ok' => false, 'code' => 'VALIDATION', 'msg' => 'clave requerida'];
      }
      if (!$this->isValidKey($clave)) {
         return ['ok' => false, 'code' => 'VALIDATION', 'msg' => 'clave inválida (usa a-z, 0-9, ., _, -)'];
      }
      if (method_exists($this->repo, 'existsClave') && $this->repo->existsClave($clave)) {
         return ['ok' => false, 'code' => 'CONFLICT', 'msg' => 'permiso ya existe'];
      }

      try {
         $id = $this->repo->create($clave, $desc);
         return ['ok' => true, 'data' => ['id' => $id]];
      } catch (\Throwable $e) {
         // Si hay UNIQUE en BD, capturamos conflicto
         if ((string)($e->getCode() ?? '') === '23000') {
            return ['ok' => false, 'code' => 'CONFLICT', 'msg' => 'permiso ya existe'];
         }
         return ['ok' => false, 'code' => 'SERVER', 'msg' => 'Error al crear permiso'];
      }
   }

   public function actualizar(array $d): array
   {
      $id    = (int)($d['id'] ?? 0);
      $clave = $this->normalizeClave((string)($d['clave'] ?? ''));
      $desc  = array_key_exists('descripcion', $d) ? (string)$d['descripcion'] : null;

      if ($id <= 0 || $clave === '') {
         return ['ok' => false, 'code' => 'VALIDATION', 'msg' => 'id y clave requeridos'];
      }
      if (!$this->isValidKey($clave)) {
         return ['ok' => false, 'code' => 'VALIDATION', 'msg' => 'clave inválida (usa a-z, 0-9, ., _, -)'];
      }
      if (method_exists($this->repo, 'existsClave') && $this->repo->existsClave($clave, $id)) {
         return ['ok' => false, 'code' => 'CONFLICT', 'msg' => 'clave duplicada'];
      }

      try {
         $ok = $this->repo->update($id, $clave, $desc);
         return $ok ? ['ok' => true] : ['ok' => false, 'code' => 'NOT_FOUND', 'msg' => 'permiso no actualizado'];
      } catch (\Throwable $e) {
         if ((string)($e->getCode() ?? '') === '23000') {
            return ['ok' => false, 'code' => 'CONFLICT', 'msg' => 'clave duplicada'];
         }
         return ['ok' => false, 'code' => 'SERVER', 'msg' => 'Error al actualizar permiso'];
      }
   }

   public function eliminar(int $id): array
   {
      if ($id <= 0) {
         return ['ok' => false, 'code' => 'VALIDATION', 'msg' => 'id inválido'];
      }

      // Bloquear si está en uso (si implementaste inUseCount en el repo)
      if (method_exists($this->repo, 'inUseCount')) {
         $count = (int)$this->repo->inUseCount($id);
         if ($count > 0) {
            return ['ok' => false, 'code' => 'CONFLICT', 'msg' => "permiso asignado a {$count} rol(es)"];
         }
      }

      $ok = $this->repo->delete($id);
      return $ok ? ['ok' => true] : ['ok' => false, 'code' => 'NOT_FOUND', 'msg' => 'permiso no encontrado'];
   }

   /** -------- Helpers -------- */

   private function normalizeClave(string $k): string
   {
      $k = strtolower(trim($k));
      // sin espacios internos
      $k = preg_replace('/\s+/', '', $k);
      return $k ?? '';
   }

   private function isValidKey(string $k): bool
   {
      // formato sugerido: modulo.accion-subaccion_ok
      return (bool)preg_match('/^[a-z0-9._-]+$/', $k);
   }
}
