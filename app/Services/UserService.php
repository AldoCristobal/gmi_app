<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserRepository;

final class UserService
{
  public function __construct(private UserRepository $repo = new UserRepository()) {}

  /**
   * List users with pagination and filters.
   * Input $query: page, size, q, activo
   * Output:
   *  [
   *    'ok'   => bool,
   *    'data' => rows,
   *    'meta' => ['total'=>int,'page'=>int,'size'=>int],
   *  ]
   */
  public function list(array $query = []): array
  {
    $page = max(1, (int) ($query['page'] ?? 1));
    $size = min(max(1, (int) ($query['size'] ?? 20)), 200);

    $filters = [];

    $search = trim((string) ($query['q'] ?? ''));
    if ($search !== '') {
      $filters['q'] = $search;
    }

    if (array_key_exists('activo', $query) && $query['activo'] !== '') {
      $filters['activo'] = (int) $query['activo'];
    }

    $res = $this->repo->list($filters, $page, $size);

    return [
      'ok'   => true,
      'data' => $res['rows'],
      'meta' => [
        'page'  => $page,
        'size'  => $size,
        'total' => $res['total'],
      ],
    ];
  }

  public function get(int $id): array
  {
    if ($id <= 0) {
      throw new \InvalidArgumentException('invalid id');
    }

    $row = $this->repo->findById($id);
    if (!$row) {
      throw new \RuntimeException('user not found');
    }

    return $row;
  }

  public function create(array $input): array
  {
    $v = $this->validate($input, false);
    if ($v !== true) {
      return $v;
    }

    $input['pass_hash'] = password_hash((string) $input['password'], PASSWORD_DEFAULT);
    $id                 = $this->repo->create($input);

    if (!empty($input['roles'])) {
      $this->repo->assignRoles($id, array_map('intval', $input['roles']));
    }

    return ['ok' => true, 'data' => ['id' => $id]];
  }

  public function update(int $id, array $input): array
  {
    $exists = $this->repo->findById($id);
    if (!$exists) {
      return ['ok' => false, 'error' => ['code' => 'NOT_FOUND']];
    }

    $v = $this->validate($input, true);
    if ($v !== true) {
      return $v;
    }

    $ok = $this->repo->update($id, $input);
    if (!$ok) {
      return ['ok' => false, 'error' => ['code' => 'UPDATE_FAIL']];
    }

    if (isset($input['roles'])) {
      $this->repo->assignRoles($id, array_map('intval', $input['roles']));
    }

    return ['ok' => true];
  }

  public function delete(int $id): array
  {
    $exists = $this->repo->findById($id);
    if (!$exists) {
      return ['ok' => false, 'error' => ['code' => 'NOT_FOUND']];
    }

    $this->repo->softDelete($id);
    return ['ok' => true];
  }

  public function resetPassword(int $id, string $password): array
  {
    $exists = $this->repo->findById($id);
    if (!$exists) {
      return ['ok' => false, 'error' => ['code' => 'NOT_FOUND']];
    }

    if (strlen($password) < 6) {
      return [
        'ok'    => false,
        'error' => [
          'code'    => 'VALIDATION',
          'message' => 'password min 6',
        ],
      ];
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $this->repo->setPassword($id, $hash);

    return ['ok' => true];
  }

  /**
   * Validate payload for create/update.
   * Returns true on success, or ['ok'=>false,'error'=>...] on failure.
   */
  private function validate(array &$in, bool $isUpdate): true|array
  {
    $in['nombre'] = trim((string) ($in['nombre'] ?? ''));
    if ($in['nombre'] === '') {
      return [
        'ok'    => false,
        'error' => [
          'code'    => 'VALIDATION',
          'message' => 'nombre requerido',
        ],
      ];
    }

    $in['email'] = trim((string) ($in['email'] ?? ''));
    if (!filter_var($in['email'], FILTER_VALIDATE_EMAIL)) {
      return [
        'ok'    => false,
        'error' => [
          'code'    => 'VALIDATION',
          'message' => 'email inválido',
        ],
      ];
    }

    $in['area_id'] = isset($in['area_id']) ? (int) $in['area_id'] : null;
    $in['jefe_id'] = isset($in['jefe_id']) ? (int) $in['jefe_id'] : null;

    if (!$isUpdate) {
      if (empty($in['password']) || strlen((string) $in['password']) < 6) {
        return [
          'ok'    => false,
          'error' => [
            'code'    => 'VALIDATION',
            'message' => 'password min 6',
          ],
        ];
      }
    }

    if (isset($in['roles']) && !is_array($in['roles'])) {
      return [
        'ok'    => false,
        'error' => [
          'code'    => 'VALIDATION',
          'message' => 'roles debe ser arreglo',
        ],
      ];
    }

    return true;
  }
}
