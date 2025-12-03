<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\MenuRepository;
use App\Repositories\MenuRolRepository;
use App\Repositories\RoleRepository;
use Throwable;

final class MenuRolService
{
   public function __construct(
      private MenuRepository $menuRepo = new MenuRepository(),
      private MenuRolRepository $menuRolRepo = new MenuRolRepository(),
      private RoleRepository $rolRepo = new RoleRepository(),
   ) {}

   /**
    * Construye el árbol de menú (para FancyTree) para un rol.
    *
    * Devuelve:
    * [
    *   'ok' => true/false,
    *   'data' => [...tree...] | [],
    *   'error' => ['code' => '...', 'message' => '...'] (si falla)
    * ]
    */
   public function treeForRol(int $rolId, string $namespace = 'sidebar'): array
   {
      try {
         if ($rolId <= 0) {
            return [
               'ok'           => true,
               'data'         => [],
               'home_menu_id' => null, // 👈 clave correcta
            ];
         }

         // Menús del namespace
         $menuRows    = $this->menuRepo->list($namespace);
         $assignedIds = $this->menuRolRepo->getMenuIdsByRol($rolId);
         $assignedSet = array_flip($assignedIds);

         $nodesById = [];

         foreach ($menuRows as $row) {
            $id       = (int)$row['id'];
            $parentId = $row['parent_id'] !== null ? (int)$row['parent_id'] : null;

            $nodesById[$id] = [
               'key'      => (string)$id,
               'title'    => $row['etiqueta'],
               'icon'     => $row['icono'] ?? null,
               'parentId' => $parentId,
               'folder'   => ($row['tipo'] ?? 'item') === 'header',
               'selected' => isset($assignedSet[$id]),
               'extra'    => [
                  'tipo'      => $row['tipo'],
                  'slug'      => $row['slug'],
                  'vista'     => $row['vista'],
                  'namespace' => $row['namespace'],
                  'orden'     => (int)$row['orden'],
               ],
            ];
         }

         // Armar árbol padre → hijos
         $tree = [];
         foreach ($nodesById as $id => &$node) {
            $pid = $node['parentId'];
            if ($pid !== null && isset($nodesById[$pid])) {
               $nodesById[$pid]['children'][] = &$node;
            } else {
               $tree[] = &$node;
            }
         }
         unset($node);

         // Leer home_menu_id del rol
         $rolRow = $this->rolRepo->findById($rolId);
         $homeId = $rolRow['home_menu_id'] ?? null;
         $homeId = $homeId !== null ? (int)$homeId : null;

         return [
            'ok'           => true,
            'data'         => $tree,
            'home_menu_id' => $homeId, // 👈 misma clave que usa el controller y el JS
         ];
      } catch (Throwable $e) {
         return [
            'ok'    => false,
            'error' => [
               'code'    => 'SERVER',
               'message' => $e->getMessage(),
            ],
         ];
      }
   }



   /**
    * Dado un arreglo de menu_ids seleccionados, agrega automáticamente
    * todos los ancestros (padres, abuelos, etc.) en la tabla `menu`.
    *
    * Ej:
    *   [rutinas]  -> [administrar_empresas, rutinas]
    *
    * @param int[] $menuIds
    * @return int[]  IDs completos (seleccionados + ancestros), sin duplicados
    */
   private function resolveWithAncestors(array $menuIds): array
   {
      $menuIds = array_unique(array_map('intval', $menuIds));
      $menuIds = array_values(array_filter($menuIds, fn(int $id) => $id > 0));

      if (empty($menuIds)) {
         return [];
      }

      $seen   = [];
      $result = [];

      $visit = function (int $id) use (&$seen, &$result, &$visit): void {
         if ($id <= 0) {
            return;
         }
         if (isset($seen[$id])) {
            return;
         }
         $seen[$id] = true;

         $row = $this->menuRepo->findById($id);
         if (!$row) {
            return;
         }

         $result[] = $id;

         $parentId = $row['parent_id'] ?? null;
         if ($parentId !== null) {
            $visit((int)$parentId);
         }
      };

      foreach ($menuIds as $id) {
         $visit($id);
      }

      // normalizamos (sin duplicados, reindexado)
      $result = array_values(array_unique(array_map('intval', $result)));

      return $result;
   }

   /**
    * Guarda la asignación de menú de un rol.
    *
    * @param int   $rolId
    * @param int[] $menuIds  IDs marcados en FancyTree (hijos/padres)
    */
   public function saveRolMenu(int $rolId, array $menuIds, ?int $homeMenuId = null): array
   {
      try {
         if ($rolId <= 0) {
            throw new \InvalidArgumentException('rol_id inválido');
         }

         // Normalizamos IDs marcados en el árbol
         $menuIds = array_unique(array_map('intval', $menuIds));

         // 👇 AQUÍ metemos padres + abuelos + etc.
         $fullMenuIds = $this->resolveWithAncestors($menuIds);

         // 1) Guardar asignaciones en menu_rol (ya con ancestros)
         $this->menuRolRepo->setMenuIdsForRol($rolId, $fullMenuIds);

         // 2) Validar y guardar home_menu_id (puede ser null)
         $homeMenuId = $homeMenuId !== null ? (int)$homeMenuId : null;

         if ($homeMenuId !== null && $homeMenuId > 0) {
            // Aseguramos que el menú exista y tenga vista
            $menuRow = $this->menuRepo->findById($homeMenuId);
            if (
               !$menuRow ||
               ($menuRow['tipo'] ?? 'item') !== 'item' ||
               empty($menuRow['vista'])
            ) {
               $homeMenuId = null; // si no cumple, lo ignoramos
            } else {
               // Además, nos aseguramos de que esté realmente asignado al rol
               if (!in_array($homeMenuId, $fullMenuIds, true)) {
                  $homeMenuId = null;
               }
            }
         }

         // 3) Guardar en tabla rol (puede ser null)
         $this->rolRepo->setHomeMenuId($rolId, $homeMenuId);

         return ['ok' => true];
      } catch (\Throwable $e) {
         return [
            'ok'    => false,
            'error' => [
               'code'    => 'SERVER',
               'message' => $e->getMessage(),
            ],
         ];
      }
   }
}
