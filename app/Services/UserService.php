<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserRepository;

final class UserService {
  public function __construct(private UserRepository $repo = new UserRepository()) {}

  public function listar(array $q): array {
    $page=max(1,(int)($q['page']??1)); $size=min(max(1,(int)($q['size']??20)),200);
    $filters=['q'=>trim((string)($q['q']??'')),'activo'=>isset($q['activo'])?(int)$q['activo']:1];
    $res=$this->repo->list($filters,$page,$size);
    return ['ok'=>true,'data'=>$res['rows'],'meta'=>['page'=>$page,'size'=>$size,'total'=>$res['total']]];
  }

  public function crear(array $in): array {
    $v=$this->validar($in,false); if($v!==true) return $v;
    $in['pass_hash']=password_hash((string)$in['password'], PASSWORD_DEFAULT);
    $id=$this->repo->create($in);
    if (!empty($in['roles'])) $this->repo->assignRoles($id, array_map('intval',$in['roles']));
    return ['ok'=>true,'data'=>['id'=>$id]];
  }

  public function actualizar(int $id, array $in): array {
    $exists = $this->repo->find($id); if(!$exists) return ['ok'=>false,'error'=>['code'=>'NOT_FOUND']];
    $v=$this->validar($in,true); if($v!==true) return $v;
    $ok=$this->repo->update($id,$in); if(!$ok) return ['ok'=>false,'error'=>['code'=>'UPDATE_FAIL']];
    if (isset($in['roles'])) $this->repo->assignRoles($id, array_map('intval',$in['roles']));
    return ['ok'=>true];
  }

  public function resetPassword(int $id, string $password): array {
    $exists=$this->repo->find($id); if(!$exists) return ['ok'=>false,'error'=>['code'=>'NOT_FOUND']];
    if (strlen($password)<6) return ['ok'=>false,'error'=>['code'=>'VALIDATION','message'=>'password min 6']];
    $hash=password_hash($password, PASSWORD_DEFAULT);
    $this->repo->setPassword($id,$hash);
    return ['ok'=>true];
  }

  public function borrar(int $id): array {
    $exists=$this->repo->find($id); if(!$exists) return ['ok'=>false,'error'=>['code'=>'NOT_FOUND']];
    $this->repo->softDelete($id); return ['ok'=>true];
  }

  private function validar(array &$in, bool $isUpdate): true|array {
    $in['nombre']=trim((string)($in['nombre']??'')); if($in['nombre']==='') return ['ok'=>false,'error'=>['code'=>'VALIDATION','message'=>'nombre requerido']];
    $in['email']=trim((string)($in['email']??'')); if(!filter_var($in['email'], FILTER_VALIDATE_EMAIL)) return ['ok'=>false,'error'=>['code'=>'VALIDATION','message'=>'email inválido']];
    $in['area_id']=isset($in['area_id'])?(int)$in['area_id']:null;
    $in['jefe_id']=isset($in['jefe_id'])?(int)$in['jefe_id']:null;
    if(!$isUpdate){
      if(empty($in['password']) || strlen((string)$in['password'])<6)
        return ['ok'=>false,'error'=>['code'=>'VALIDATION','message'=>'password min 6']];
    }
    if(isset($in['roles']) && !is_array($in['roles'])) return ['ok'=>false,'error'=>['code'=>'VALIDATION','message'=>'roles debe ser arreglo']];
    return true;
  }
}
