<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\MenuRepository;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

final class MenuService
{
   public function __construct(private MenuRepository $repo = new MenuRepository()) {}

   /**
    * NUEVO ESTÁNDAR:
    * Lista plana usando filtros (p.ej. ['namespace' => 'sidebar']).
    * Devuelve SOLO la lista de items.
    */
   public function list(array $filters = []): array
   {
      $ns  = $filters['namespace'] ?? 'sidebar';
      $res = $this->listar($ns);
      return $res['data'] ?? [];
   }

   /**
    * LEGACY:
    * Lista plana por namespace (el front arma el árbol).
    * Devuelve envoltura ok/data.
    */
   public function listar(string $namespace = 'sidebar'): array
   {
      try {
         $rows = $this->repo->list($namespace);
         return ['ok' => true, 'data' => $rows];
      } catch (Throwable $e) {
         return [
            'ok'    => false,
            'error' => ['code' => 'SERVER', 'message' => $e->getMessage()],
         ];
      }
   }

   /**
    * NUEVO ESTÁNDAR:
    * Obtiene un ítem de menú por ID o lanza RuntimeException si no existe.
    */
   public function get(int $id): array
   {
      if ($id <= 0) {
         throw new InvalidArgumentException('id inválido');
      }

      $row = $this->repo->findById($id);
      if (!$row) {
         throw new RuntimeException('Elemento de menú no encontrado');
      }

      return $row;
   }

   /**
    * NUEVO ESTÁNDAR:
    * Crea ítem de menú y devuelve su ID.
    * Usa la lógica de crear().
    */
   public function create(array $d): int
   {
      $res = $this->crear($d);
      if (!($res['ok'] ?? false)) {
         $msg = $res['error']['message'] ?? 'Error al crear menú';
         throw new RuntimeException($msg);
      }
      return (int)($res['data']['id'] ?? 0);
   }

   /**
    * NUEVO ESTÁNDAR:
    * Actualiza ítem de menú (sin mover jerarquía).
    * Devuelve true si se actualizó, false si no existe.
    */
   public function update(int $id, array $d): bool
   {
      $d['id'] = $id;
      $res     = $this->actualizar($d);

      if (!($res['ok'] ?? false) && ($res['error']['code'] ?? '') === 'NOT_FOUND') {
         return false;
      }

      if (!($res['ok'] ?? false)) {
         $msg = $res['error']['message'] ?? 'Error al actualizar menú';
         throw new RuntimeException($msg);
      }

      return true;
   }

   /**
    * NUEVO ESTÁNDAR:
    * Elimina (cascade) un ítem de menú. Devuelve true si se eliminó, false si no existe.
    */
   public function delete(int $id): bool
   {
      $res = $this->eliminar($id, 'cascade');

      if (!($res['ok'] ?? false) && ($res['error']['code'] ?? '') === 'NOT_FOUND') {
         return false;
      }

      if (!($res['ok'] ?? false)) {
         $msg = $res['error']['message'] ?? 'Error al eliminar menú';
         throw new RuntimeException($msg);
      }

      return true;
   }

   /** Crear ítem (calcula orden = max+1) */
   public function crear(array $d): array
   {
      try {
         $data = $this->norm($d, false);
         $this->validateTypeFields($data);

         if (!empty($data['slug']) && $this->repo->slugExists($data['parent_id'] ?? null, $data['slug'], null)) {
            throw new RuntimeException('El slug ya existe entre los hermanos');
         }

         if (!isset($data['orden'])) {
            $max = $this->repo->maxOrden($data['parent_id'] ?? null, $data['namespace'] ?? 'sidebar');
            $data['orden'] = (int)(($max ?? -1) + 1);
         }

         $id = $this->repo->create($data);
         return ['ok' => true, 'data' => ['id' => $id]];
      } catch (Throwable $e) {
         return [
            'ok'    => false,
            'error' => ['code' => 'VALIDATION', 'message' => $e->getMessage()],
         ];
      }
   }

   /** Actualizar propiedades (no toca parent/orden; eso va en reorder) */
   public function actualizar(array $d): array
   {
      try {
         $id = (int)($d['id'] ?? 0);
         if (!$id) throw new InvalidArgumentException('ID requerido');

         $current = $this->repo->findById($id);
         if (!$current) {
            return ['ok' => false, 'error' => ['code' => 'NOT_FOUND']];
         }

         $data = $this->norm($d, true);
         unset($data['parent_id'], $data['orden']);

         $merged = array_merge($current, $data);
         $this->validateTypeFields($merged);

         if (array_key_exists('slug', $data) && !empty($data['slug'])) {
            if ($this->repo->slugExists($current['parent_id'], $data['slug'], $id)) {
               throw new RuntimeException('El slug ya existe entre los hermanos');
            }
         }

         $ok = $this->repo->update($id, $data);
         return $ok ? ['ok' => true] : ['ok' => false, 'error' => ['code' => 'NOT_MODIFIED']];
      } catch (Throwable $e) {
         return [
            'ok'    => false,
            'error' => ['code' => 'VALIDATION', 'message' => $e->getMessage()],
         ];
      }
   }

   /** Eliminar: cascade o reparent */
   public function eliminar(int $id, string $mode = 'cascade'): array
   {
      try {
         $current = $this->repo->findById($id);
         if (!$current) {
            return ['ok' => false, 'error' => ['code' => 'NOT_FOUND']];
         }

         if ($mode === 'reparent') {
            $ok = $this->repo->reparentChildrenToParent($id, $current['parent_id'] ?? null)
               && $this->repo->delete($id);
         } else {
            $ok = $this->repo->deleteCascade($id);
         }
         return ['ok' => (bool)$ok];
      } catch (Throwable $e) {
         return [
            'ok'    => false,
            'error' => ['code' => 'SERVER', 'message' => $e->getMessage()],
         ];
      }
   }

   /** Reordenar/mover (DnD) en lote */
   public function reorder(array $changes): array
   {
      try {
         if (!is_array($changes) || !count($changes)) {
            return ['ok' => true, 'saved' => 0];
         }

         foreach ($changes as $c) {
            if (!isset($c['id'])) throw new InvalidArgumentException('Cada cambio requiere id');
            if (isset($c['parent_id']) && $c['parent_id'] !== null && (int)$c['parent_id'] === (int)$c['id']) {
               throw new InvalidArgumentException('parent_id no puede ser igual al id');
            }
         }
         if ($this->detectCycle($changes)) {
            throw new InvalidArgumentException('Hay ciclos en la propuesta de reordenamiento');
         }

         $saved = $this->repo->reorderBatch($changes);
         return ['ok' => true, 'saved' => $saved];
      } catch (Throwable $e) {
         return [
            'ok'    => false,
            'error' => ['code' => 'VALIDATION', 'message' => $e->getMessage()],
         ];
      }
   }

   public function roles(int $menuId): array
   {
      if (!method_exists($this->repo, 'getRolesForMenu')) {
         return [
            'ok'    => false,
            'error' => [
               'code'    => 'NOT_IMPLEMENTED',
               'message' => 'Repo::getRolesForMenu no implementado',
            ],
         ];
      }
      return ['ok' => true, 'data' => $this->repo->getRolesForMenu($menuId)];
   }

   public function setRoles(int $menuId, array $roles): array
   {
      $this->repo->assignToRoles($menuId, array_map('intval', $roles));
      return ['ok' => true];
   }

   public function treeForUser(array $user): array
   {
      $tree = $this->repo->treeForUser($user);
      return ['ok' => true, 'data' => $tree];
   }

   // ----------------- Helpers internos -----------------

   private function norm(array $j, bool $isUpdate): array
   {
      $parentId = array_key_exists('parent_id', $j)
         ? ($j['parent_id'] === null ? null : (int)$j['parent_id'])
         : null;

      $out = [
         'parent_id'        => $isUpdate ? null : $parentId,
         'etiqueta'         => $this->nullIfEmpty($j['etiqueta'] ?? ''),
         'icono'            => $this->nullIfEmpty($j['icono'] ?? null),
         'vista'            => $this->nullIfEmpty($j['vista'] ?? null),
         'url_externa'      => $this->nullIfEmpty($j['url_externa'] ?? null),
         'target'           => $this->sanitizeTarget($j['target'] ?? '_self'),
         'tipo'             => $j['tipo'] ?? 'item',
         'slug'             => $this->nullIfEmpty($j['slug'] ?? null),
         'visible'          => isset($j['visible']) ? (int)!!$j['visible'] : 1,
         'requiere_permiso' => $this->nullIfEmpty($j['requiere_permiso'] ?? null),
         'badge_text'       => $this->nullIfEmpty($j['badge_text'] ?? null),
         'badge_variant'    => $this->nullIfEmpty($j['badge_variant'] ?? null),
         'namespace'        => $this->nullIfEmpty($j['namespace'] ?? 'sidebar'),
      ];

      if (!$isUpdate) {
         $out['orden'] = (int)($j['orden'] ?? 0);
      } else {
         foreach ($out as $k => $v) {
            if ($v === null && !in_array($k, ['vista', 'url_externa', 'icono', 'requiere_permiso', 'badge_text', 'badge_variant', 'slug'], true)) {
               unset($out[$k]);
            }
         }
      }

      return $out;
   }

   private function validateTypeFields(array $d): void
   {
      $tipo = $d['tipo'] ?? 'item';

      // 👉 ahora incluimos 'group' como tipo válido
      if (!in_array($tipo, ['item', 'external', 'header', 'divider', 'group'], true)) {
         throw new InvalidArgumentException('Tipo inválido');
      }

      if (empty(trim($d['etiqueta'] ?? ''))) {
         throw new InvalidArgumentException('La etiqueta es obligatoria');
      }

      // item: vista obligatoria, sin URL externa
      if ($tipo === 'item') {
         if (empty(trim($d['vista'] ?? ''))) {
            throw new InvalidArgumentException('Para tipo "item", la vista es obligatoria');
         }
         if (!empty(trim($d['url_externa'] ?? ''))) {
            throw new InvalidArgumentException('No mezcles vista con URL externa');
         }
      }

      // external: URL obligatoria, sin vista
      if ($tipo === 'external') {
         if (empty(trim($d['url_externa'] ?? ''))) {
            throw new InvalidArgumentException('Para tipo "external", la URL es obligatoria');
         }
         if (!empty(trim($d['vista'] ?? ''))) {
            throw new InvalidArgumentException('No mezcles URL externa con vista');
         }
         if (!in_array(($d['target'] ?? '_self'), ['_self', '_blank'], true)) {
            throw new InvalidArgumentException('Target inválido');
         }
      }

      // group: contenedor puro, sin vista ni URL
      if ($tipo === 'group') {
         if (!empty(trim($d['vista'] ?? ''))) {
            throw new InvalidArgumentException('Los elementos de tipo "group" no deben tener vista');
         }
         if (!empty(trim($d['url_externa'] ?? ''))) {
            throw new InvalidArgumentException('Los elementos de tipo "group" no deben tener URL externa');
         }
      }

      // header/divider: por ahora ninguna validación extra (pero también deberían ir sin vista/URL)
   }

   private function detectCycle(array $changes): bool
   {
      $parent = [];
      foreach ($changes as $c) {
         $id  = (int)$c['id'];
         $pid = array_key_exists('parent_id', $c)
            ? ($c['parent_id'] === null ? null : (int)$c['parent_id'])
            : null;
         $parent[$id] = $pid;
      }

      $vis   = [];
      $stack = [];
      $getp  = fn(int $x) => $parent[$x] ?? null;

      $dfs = function (int $u) use (&$dfs, &$vis, &$stack, $getp): bool {
         $vis[$u]   = 1;
         $stack[$u] = 1;
         $v         = $getp($u);
         if ($v !== null) {
            if (!isset($vis[$v]) && $dfs($v)) return true;
            if (!empty($stack[$v])) return true;
         }
         $stack[$u] = 0;
         return false;
      };

      foreach (array_keys($parent) as $u) {
         if (!isset($vis[$u]) && $dfs($u)) return true;
      }
      return false;
   }

   private function nullIfEmpty($v)
   {
      $s = is_string($v) ? trim($v) : $v;
      return ($s === '' || $s === null) ? null : $s;
   }

   private function sanitizeTarget($t): string
   {
      return in_array($t, ['_self', '_blank'], true) ? $t : '_self';
   }
}
