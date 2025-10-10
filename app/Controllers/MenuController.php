<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Repositories\MenuRepository;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

final class MenuController
{
   public function __construct(private MenuRepository $repo = new MenuRepository()) {}

   /** GET /api/v1/admin/menu?namespace=sidebar */
   public function index(): void
   {
      try {
         $ns = $_GET['namespace'] ?? 'sidebar';
         $rows = $this->repo->list($ns); // <-- ajusta tu repo->list($namespace)
         Response::json(['ok' => true, 'data' => $rows]);
      } catch (Throwable $e) {
         Response::json(['ok' => false, 'msg' => $e->getMessage()], 500);
      }
   }

   /** POST /api/v1/admin/menu */
   public function store(Request $r): void
   {
      try {
         $j = $this->json($r);
         $data = $this->norm($j, isUpdate: false);

         // Validaciones por tipo (item/external/header/divider)
         $this->validateTypeFields($data);

         // Slug único por hermano (si viene)
         if (!empty($data['slug'])) {
            if ($this->repo->slugExists($data['parent_id'], $data['slug'], null)) {
               throw new RuntimeException('El slug ya existe entre los hermanos.');
            }
         }

         // Asignar orden automáticamente al final
         $max = $this->repo->maxOrden($data['parent_id'], $data['namespace'] ?? 'sidebar');
         $data['orden'] = ($max ?? -1) + 1;

         $id = $this->repo->create($data);
         Response::json(['ok' => true, 'data' => ['id' => $id]]);
      } catch (Throwable $e) {
         Response::json(['ok' => false, 'msg' => $e->getMessage()], 400);
      }
   }

   /** PUT /api/v1/admin/menu?id=123  (también acepta id en body) */
   public function update(Request $r): void
   {
      try {
         $j = $this->json($r);
         $id = isset($_GET['id']) ? (int)$_GET['id'] : (int)($j['id'] ?? 0);
         if (!$id) throw new InvalidArgumentException('ID requerido');

         $current = $this->repo->getById($id);
         if (!$current) {
            Response::json(['ok' => false, 'msg' => 'No existe'], 404);
            return;
         }

         // Normaliza sólo campos editables desde el form de detalle
         $data = $this->norm($j, isUpdate: true);
         // Mantén parent_id/orden fuera de update normal (eso va por reorder)
         unset($data['parent_id'], $data['orden']);

         // Validaciones
         $merged = array_merge($current, $data);
         $this->validateTypeFields($merged);

         if (array_key_exists('slug', $data) && !empty($data['slug'])) {
            if ($this->repo->slugExists($current['parent_id'], $data['slug'], $id)) {
               throw new RuntimeException('El slug ya existe entre los hermanos.');
            }
         }

         $ok = $this->repo->update($id, $data);
         Response::json(['ok' => $ok], $ok ? 200 : 422);
      } catch (Throwable $e) {
         Response::json(['ok' => false, 'msg' => $e->getMessage()], 400);
      }
   }

   /** DELETE /api/v1/admin/menu?id=123&mode=cascade|reparent  (también acepta id/mode en body) */
   public function destroy(Request $r): void
   {
      try {
         $j = $this->json($r);
         $id = isset($_GET['id']) ? (int)$_GET['id'] : (int)($j['id'] ?? 0);
         if (!$id) throw new InvalidArgumentException('ID requerido');

         $mode = $_GET['mode'] ?? ($j['mode'] ?? 'cascade');
         $node = $this->repo->getById($id);
         if (!$node) {
            Response::json(['ok' => false, 'msg' => 'No existe'], 404);
            return;
         }

         $ok = false;
         if ($mode === 'reparent') {
            // mueve hijos al padre del nodo y borra el nodo
            $ok = $this->repo->reparentChildrenToParent($id, $node['parent_id'] ?? null) && $this->repo->delete($id);
         } else {
            // cascade: borrar subárbol (si hay FK CASCADE basta con delete(); si no, hazlo recursivo en repo)
            $ok = $this->repo->deleteCascade($id);
         }

         Response::json(['ok' => $ok], $ok ? 200 : 404);
      } catch (Throwable $e) {
         Response::json(['ok' => false, 'msg' => $e->getMessage()], 400);
      }
   }

   /** PATCH /api/v1/admin/menu/reorder  body: [{id,parent_id,orden,namespace}] */
   public function reorder(Request $r): void
   {
      try {
         $changes = $this->json($r);
         if (!is_array($changes)) throw new InvalidArgumentException('Payload inválido');
         if (!count($changes)) {
            Response::json(['ok' => true, 'saved' => 0]);
            return;
         }

         // Validaciones rápidas + no ciclos
         foreach ($changes as $c) {
            if (!isset($c['id'])) throw new InvalidArgumentException('Cada cambio requiere id');
            if (isset($c['parent_id']) && $c['parent_id'] !== null && (int)$c['parent_id'] === (int)$c['id']) {
               throw new InvalidArgumentException('parent_id no puede ser igual al id');
            }
         }
         if ($this->detectCycle($changes)) {
            throw new InvalidArgumentException('Hay ciclos en la propuesta de reordenamiento');
         }

         // Transaccional en repo
         $saved = $this->repo->reorderBatch($changes);
         Response::json(['ok' => true, 'saved' => $saved]);
      } catch (Throwable $e) {
         Response::json(['ok' => false, 'msg' => $e->getMessage()], 400);
      }
   }

   /** POST /api/v1/admin/menu/roles */
   public function setRoles(Request $r): void
   {
      $j = $this->json($r);
      $this->repo->assignToRoles((int)$j['menu_id'], array_map('intval', $j['role_ids'] ?? []));
      Response::json(['ok' => true]);
   }

   /** GET /api/v1/menu/tree */
   public function myTree(Request $r): void
   {
      $u = $r->attr('user', []);
      $tree = $this->repo->treeForUser($u);
      Response::json(['ok' => true, 'data' => $tree]);
   }

   // ----------------- Helpers -----------------

   private function norm(array $j, bool $isUpdate): array
   {
      // Sólo normaliza campos que el front manipula; parent/orden sólo en creación y reorder
      $parentId = array_key_exists('parent_id', $j) ? ($j['parent_id'] === null ? null : (int)$j['parent_id']) : null;

      $out = [
         'parent_id'        => $isUpdate ? null : $parentId,                  // en update común, no tocar parent
         'etiqueta'         => $this->nullIfEmpty($j['etiqueta'] ?? ''),
         'icono'            => $this->nullIfEmpty($j['icono'] ?? ''),
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

      // Orden: sólo en create lo calculamos; en update normal no se toca (reorder lo maneja aparte)
      if (!$isUpdate) {
         $out['orden'] = (int)($j['orden'] ?? 0);
      }

      // Limpia claves con nulls en update para no sobreescribir sin querer
      if ($isUpdate) {
         foreach ($out as $k => $v) {
            if ($v === null && !in_array($k, ['vista', 'url_externa', 'icono', 'requiere_permiso', 'badge_text', 'badge_variant', 'slug'], true)) {
               unset($out[$k]);
            }
         }
      }

      return $out;
   }

   private function json(Request $r): array
   {
      $raw = file_get_contents('php://input') ?: '';
      $j = json_decode($raw, true);
      return is_array($j) ? $j : ($r->post ?? []);
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

   private function validateTypeFields(array $d): void
   {
      $tipo = $d['tipo'] ?? 'item';
      if (!in_array($tipo, ['item', 'external', 'header', 'divider'], true)) {
         throw new InvalidArgumentException('Tipo inválido');
      }
      if (empty(trim($d['etiqueta'] ?? ''))) {
         throw new InvalidArgumentException('La etiqueta es obligatoria');
      }

      if ($tipo === 'item') {
         if (empty(trim($d['vista'] ?? ''))) {
            throw new InvalidArgumentException('Para tipo "item", la vista es obligatoria');
         }
         if (!empty(trim($d['url_externa'] ?? ''))) {
            throw new InvalidArgumentException('No mezcles vista con URL externa');
         }
      }

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

      // header/divider: sin vista/url; si llegan, las ignoras en repo o las rechazas aquí
   }

   /** Detección simple de ciclos para reorder: child -> parent */
   private function detectCycle(array $changes): bool
   {
      $parent = [];
      foreach ($changes as $c) {
         $id = (int)$c['id'];
         $pid = array_key_exists('parent_id', $c)
            ? ($c['parent_id'] === null ? null : (int)$c['parent_id'])
            : null;
         $parent[$id] = $pid;
      }

      $vis = [];
      $stack = [];
      $getp = function (int $x) use (&$parent) {
         return $parent[$x] ?? null;
      };

      $dfs = function (int $u) use (&$dfs, &$vis, &$stack, $getp): bool {
         $vis[$u] = 1;
         $stack[$u] = 1;
         $v = $getp($u);
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
}
