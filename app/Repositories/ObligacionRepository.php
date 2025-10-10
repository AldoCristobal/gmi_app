<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use App\Support\DB;

final class ObligacionRepository
{
   private PDO $pdo;

   public function __construct(?PDO $pdo = null)
   {
      $this->pdo = $pdo ?? DB::pdo();
   }

   /**
    * @param array{q?:string, organismo?:string, activo?:int|null} $filtros
    * @return array<int, array<string,mixed>>
    */
   public function findAll(array $filtros = []): array
   {
      $sql = "SELECT id, clave, descripcion, organismo, activo
                  FROM obligacion
                 WHERE 1=1";
      $args = [];

      if (isset($filtros['q']) && $filtros['q'] !== '') {
         $sql .= " AND (clave LIKE :q OR descripcion LIKE :q)";
         $args[':q'] = '%' . $filtros['q'] . '%';
      }
      if (isset($filtros['organismo']) && $filtros['organismo'] !== '') {
         $sql .= " AND organismo = :org";
         $args[':org'] = $filtros['organismo'];
      }
      if (array_key_exists('activo', $filtros) && $filtros['activo'] !== null && $filtros['activo'] !== '') {
         $sql .= " AND activo = :act";
         $args[':act'] = (int)$filtros['activo'];
      }

      $sql .= " ORDER BY organismo IS NULL, organismo, descripcion";

      $st = $this->pdo->prepare($sql);
      $st->execute($args);
      return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
   }

   public function findById(int $id): ?array
   {
      $st = $this->pdo->prepare("SELECT id, clave, descripcion, organismo, activo FROM obligacion WHERE id = :id");
      $st->execute([':id' => $id]);
      $row = $st->fetch(PDO::FETCH_ASSOC);
      return $row ?: null;
   }
}
