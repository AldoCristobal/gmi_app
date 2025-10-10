<?php
function jsonResponse($arr, int $status = 200)
{
   http_response_code($status);
   header('Content-Type: application/json; charset=utf-8');
   echo json_encode($arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
   exit;
}

// ⚠️ Ajusta a tu sistema real de auth/ACL
function requirePermission(PDO $db, string $permKey)
{
   // TODO: valida permisos efectivos del usuario actual.
   // Si no tiene: jsonResponse(['ok'=>false,'code'=>403,'msg'=>'No autorizado'], 403);
}
