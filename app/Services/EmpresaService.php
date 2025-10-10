<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\EmpresaRepository;

final class EmpresaService
{
   public function __construct(private EmpresaRepository $repo = new EmpresaRepository()) {}

   // --------- Empresas ----------
   public function listar(array $q, array $scope): array
   {
      $page = max(1, (int)($q['page'] ?? 1));
      $size = min(200, max(1, (int)($q['size'] ?? 20)));
      $filters = [];
      $filters['q'] = trim((string)($q['q'] ?? ''));
      if (isset($q['area_id'])) $filters['area_id'] = (int)$q['area_id'];
      if (isset($q['responsable_id'])) $filters['responsable_id'] = (int)$q['responsable_id'];
      if (isset($q['activo'])) $filters['activo'] = (int)$q['activo'];

      $res = $this->repo->list($filters, $scope, $page, $size);
      return ['ok' => true, 'data' => $res['rows'], 'meta' => ['page' => $page, 'size' => $size, 'total' => $res['total']]];
   }

   public function crear(array $in): array
   {
      $v = $this->validar($in, false);
      if ($v !== true) return $v;
      if ($this->repo->rfcExists($in['rfc'])) return ['ok' => false, 'error' => ['code' => 'CONFLICT', 'message' => 'RFC ya registrado']];
      $id = $this->repo->create([
         ':cliente_grupo' => $in['cliente_grupo'],
         ':nombre' => $in['nombre'],
         ':rfc' => $in['rfc'],
         ':tipo_persona' => $in['tipo_persona'],
         ':contrato_servicios' => $in['contrato_servicios'] ?? null,
         ':nombre_facturacion' => $in['nombre_facturacion'] ?? null,
         ':telefono_facturacion' => $in['telefono_facturacion'] ?? null,
         ':correo_facturacion' => $in['correo_facturacion'] ?? null,
         ':tipo_regimen' => $in['tipo_regimen'] ?? null,
         ':actividad_principal' => $in['actividad_principal'] ?? null,
         ':estatus_domicilio' => $in['estatus_domicilio'] ?? 'LOCALIZADO',
         ':area_id' => (int)$in['area_id'],
         ':responsable_id' => (int)$in['responsable_id'],
         ':activo' => (int)($in['activo'] ?? 1),
      ]);
      return ['ok' => true, 'data' => ['id' => $id]];
   }

   public function actualizar(int $id, array $in, array $scope): array
   {
      $emp = $this->repo->findVisible($id, $scope);
      if (!$emp) return ['ok' => false, 'error' => ['code' => 'FORBIDDEN', 'message' => 'Empresa no visible']];
      $v = $this->validar($in, true);
      if ($v !== true) return $v;
      if (isset($in['rfc']) && $this->repo->rfcExists($in['rfc'], $id)) {
         return ['ok' => false, 'error' => ['code' => 'CONFLICT', 'message' => 'RFC ya registrado']];
      }
      $ok = $this->repo->update($id, $this->onlyUpdatable($in));
      if (!$ok) return ['ok' => false, 'error' => ['code' => 'UPDATE_FAIL']];
      return ['ok' => true];
   }

   public function borrar(int $id, array $scope): array
   {
      $emp = $this->repo->findVisible($id, $scope);
      if (!$emp) return ['ok' => false, 'error' => ['code' => 'FORBIDDEN']];
      $this->repo->softDelete($id);
      return ['ok' => true];
   }

   private function validar(array &$in, bool $isUpdate): true|array
   {
      $req = ['cliente_grupo', 'nombre', 'rfc', 'tipo_persona', 'area_id', 'responsable_id'];
      if (!$isUpdate) {
         foreach ($req as $k) {
            if (empty($in[$k])) return ['ok' => false, 'error' => ['code' => 'VALIDATION', 'message' => "{$k} requerido"]];
         }
      }
      if (isset($in['tipo_persona']) && !in_array($in['tipo_persona'], ['FISICA', 'MORAL'], true)) {
         return ['ok' => false, 'error' => ['code' => 'VALIDATION', 'message' => 'tipo_persona inválido']];
      }
      return true;
   }

   private function onlyUpdatable(array $in): array
   {
      $allowed = [
         'cliente_grupo',
         'nombre',
         'rfc',
         'tipo_persona',
         'contrato_servicios',
         'nombre_facturacion',
         'telefono_facturacion',
         'correo_facturacion',
         'tipo_regimen',
         'actividad_principal',
         'estatus_domicilio',
         'area_id',
         'responsable_id',
         'activo'
      ];
      $out = [];
      foreach ($allowed as $k) {
         if (array_key_exists($k, $in)) $out[$k] = $in[$k];
      }
      return $out;
   }

   // --------- Expediente ----------
   public function expedienteList(int $empresaId, array $scope): array
   {
      $emp = $this->repo->findVisible($empresaId, $scope);
      if (!$emp) return ['ok' => false, 'error' => ['code' => 'FORBIDDEN', 'message' => 'Empresa no visible']];
      $tipos = $this->repo->tiposDocActivos();
      $docs  = $this->repo->docsUltimaVersion($empresaId);
      return ['ok' => true, 'data' => ['empresa' => ['id' => $empresaId], 'tipos' => $tipos, 'documentos_ultima_version' => $docs]];
   }

   public function expedienteUpload(int $empresaId, array $scope, array $files, array $input): array
   {
      $emp = $this->repo->findVisible($empresaId, $scope);
      if (!$emp) return ['ok' => false, 'error' => ['code' => 'FORBIDDEN']];

      $tipoId = isset($input['tipo_id']) ? (int)$input['tipo_id'] : null;
      $tipoClave = $input['tipo_clave'] ?? null;
      if (!$tipoId && !$tipoClave) return ['ok' => false, 'error' => ['code' => 'VALIDATION', 'message' => 'tipo_id o tipo_clave requerido']];

      $tipo = $this->repo->resolverTipo($tipoId, $tipoClave);
      if (!$tipo) return ['ok' => false, 'error' => ['code' => 'NOT_FOUND', 'message' => 'Tipo no activo']];

      if (empty($files['files'])) return ['ok' => false, 'error' => ['code' => 'VALIDATION', 'message' => 'Sin archivos']];

      $exts = $tipo['acepta_ext'] ? json_decode($tipo['acepta_ext'], true) : [];
      $uploaded = [];

      foreach ($files['files']['name'] as $i => $name) {
         $tmp  = $files['files']['tmp_name'][$i];
         $size = (int)$files['files']['size'][$i];
         $mime = $files['files']['type'][$i] ?: 'application/octet-stream';
         if (!is_uploaded_file($tmp)) continue;

         $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
         if ($exts && !in_array($ext, $exts, true)) return ['ok' => false, 'error' => ['code' => 'UNPROCESSABLE_ENTITY', 'message' => "Extensión no permitida .$ext"]];
         if (!empty($tipo['max_mb']) && $size > ((int)$tipo['max_mb'] * 1024 * 1024)) {
            return ['ok' => false, 'error' => ['code' => 'UNPROCESSABLE_ENTITY', 'message' => 'Archivo excede max_mb']];
         }

         $version = $this->repo->nextVersion($empresaId, (int)$tipo['id']);
         $root = rtrim((string)getenv('UPLOADS_DIR') ?: (dirname(__DIR__, 2) . '/public/uploads'), '/');
         $dir  = "{$root}/empresas/{$empresaId}/{$tipo['clave']}";
         if (!is_dir($dir)) mkdir($dir, 0775, true);
         $uuid = bin2hex(random_bytes(16));
         $dest = "{$dir}/{$uuid}.{$ext}";
         if (!move_uploaded_file($tmp, $dest)) return ['ok' => false, 'error' => ['code' => 'SERVER_ERROR', 'message' => 'No se pudo mover el archivo']];

         $relPath = str_replace(dirname(__DIR__, 2) . '/public', '', $dest); // path relativo a /public
         $docId = $this->repo->insertDoc([
            ':empresa_id' => $empresaId,
            ':tipo_id' => $tipo['id'],
            ':version' => $version,
            ':archivo_nombre' => $name,
            ':archivo_path' => $relPath,
            ':mime' => $mime,
            ':size_bytes' => $size,
            ':metadata' => null,
            ':subido_por' => $scope['user_id'] ?? null
         ]);

         $uploaded[] = ['doc_id' => $docId, 'tipo_clave' => $tipo['clave'], 'version' => $version, 'archivo_nombre' => $name];
      }
      return ['ok' => true, 'data' => ['uploaded' => $uploaded]];
   }

   // --------- CIF ----------
   public function cifUpload(int $empresaId, array $scope, array $files): array
   {
      if (!empty($files['file'])) {
         // normaliza a files[]
         $files['files'] = [
            'name' => [$files['file']['name']],
            'type' => [$files['file']['type']],
            'tmp_name' => [$files['file']['tmp_name']],
            'error' => [$files['file']['error']],
            'size' => [$files['file']['size']],
         ];
      }
      return $this->expedienteUpload($empresaId, $scope, $files, ['tipo_clave' => 'cif']);
   }

   public function cifParse(int $empresaId, int $docId, array $scope): array
   {
      $emp = $this->repo->findVisible($empresaId, $scope);
      if (!$emp) return ['ok' => false, 'error' => ['code' => 'FORBIDDEN']];
      $doc = $this->repo->docDeEmpresa($empresaId, $docId);
      if (!$doc) return ['ok' => false, 'error' => ['code' => 'NOT_FOUND', 'message' => 'Documento no encontrado']];
      if (($doc['clave'] ?? '') !== 'cif') return ['ok' => false, 'error' => ['code' => 'FORBIDDEN', 'message' => 'Documento no es CIF']];

      // Ubicación absoluta del archivo
      $publicRoot = dirname(__DIR__, 2) . '/public';
      $full = $publicRoot . ($doc['archivo_path'] ?? '');
      // TODO: integrar pdftotext/ocr y parsing real.
      $campos = ['rfc' => null, 'nombre' => null, 'cp' => null, 'domicilio' => null, 'tipo_persona' => null];
      $metadata = [
         'parse_engine' => 'pdftotext',
         'ocr' => false,
         'hash' => is_file($full) ? hash_file('sha256', $full) : null,
         'campos' => $campos,
         'confianza' => ['rfc' => 0.0]
      ];
      $this->repo->setDocMetadata($docId, $metadata);
      return ['ok' => true, 'data' => [
         'doc_id' => $docId,
         'engine' => $metadata['parse_engine'],
         'ocr' => $metadata['ocr'],
         'campos_detectados' => $campos,
         'confianza' => $metadata['confianza'],
         'snapshot_guardado' => true
      ]];
   }

   public function cifApply(int $empresaId, int $docId, array $campos, array $scope): array
   {
      $emp = $this->repo->findVisible($empresaId, $scope);
      if (!$emp) return ['ok' => false, 'error' => ['code' => 'FORBIDDEN']];
      $doc = $this->repo->docDeEmpresa($empresaId, $docId);
      if (!$doc) return ['ok' => false, 'error' => ['code' => 'NOT_FOUND']];
      if (($doc['clave'] ?? '') !== 'cif') return ['ok' => false, 'error' => ['code' => 'FORBIDDEN', 'message' => 'Documento no es CIF']];

      if (!empty($campos['rfc']) && $this->repo->rfcExists($campos['rfc'], $empresaId)) {
         return ['ok' => false, 'error' => ['code' => 'CONFLICT', 'message' => 'RFC ya existe en otra empresa']];
      }

      $allowed = ['rfc', 'nombre', 'tipo_persona', 'correo_facturacion', 'telefono_facturacion', 'actividad_principal', 'estatus_domicilio'];
      $set = [];
      foreach ($allowed as $k) {
         if (array_key_exists($k, $campos)) $set[$k] = $campos[$k];
      }
      $set['origen_captura'] = 'AUTOMATICO';
      $set['constancia_doc_id'] = $docId;

      $ok = $this->repo->update($empresaId, $set);
      if (!$ok) return ['ok' => false, 'error' => ['code' => 'UPDATE_FAIL']];
      return ['ok' => true, 'data' => $this->repo->empresaMin($empresaId)];
   }

   public function show(int $id, array $scope): array
   {
      $row = $this->repo->findVisible($id, $scope);
      if (!$row) return ['ok' => false, 'error' => ['code' => 'NOT_FOUND', 'message' => 'Empresa no visible o inexistente']];
      return ['ok' => true, 'data' => $row];
   }

   public function expedienteVersions(int $empresaId, int $tipoId, array $scope): array
   {
      $emp = $this->repo->findVisible($empresaId, $scope);
      if (!$emp) return ['ok' => false, 'error' => ['code' => 'FORBIDDEN', 'message' => 'Empresa no visible']];
      $rows = $this->repo->docsPorTipo($empresaId, $tipoId);
      return ['ok' => true, 'data' => $rows];
   }

   public function expedienteDownload(int $empresaId, int $docId, array $scope, bool $stream = false): array
   {
      $emp = $this->repo->findVisible($empresaId, $scope);
      if (!$emp) return ['ok' => false, 'error' => ['code' => 'FORBIDDEN', 'message' => 'Empresa no visible']];
      $doc = $this->repo->docPath($empresaId, $docId);
      if (!$doc) return ['ok' => false, 'error' => ['code' => 'NOT_FOUND', 'message' => 'Documento no encontrado']];

      if ($stream) {
         // Asumimos path relativo a /public
         $publicRoot = dirname(__DIR__, 2) . '/public';
         $abs = $publicRoot . ($doc['archivo_path'] ?? '');
         if (!is_file($abs)) {
            return ['ok' => false, 'error' => ['code' => 'NOT_FOUND', 'message' => 'Archivo no existe en disco']];
         }
         // Output directo
         header('Content-Description: File Transfer');
         header('Content-Type: ' . ($doc['mime'] ?? 'application/octet-stream'));
         header('Content-Disposition: attachment; filename="' . basename($doc['archivo_nombre']) . '"');
         header('Content-Length: ' . filesize($abs));
         header('Cache-Control: private, max-age=0, must-revalidate');
         header('Pragma: public');
         readfile($abs);
         exit; // MUY IMPORTANTE
      }

      // Modo actual: devolver path relativo
      return ['ok' => true, 'data' => [
         'path'   => $doc['archivo_path'],
         'nombre' => $doc['archivo_nombre'],
         'mime'   => $doc['mime']
      ]];
   }


   public function expedienteLatest(int $empresaId, int $tipoId, int $limit, array $scope): array
   {
      $emp = $this->repo->findVisible($empresaId, $scope);
      if (!$emp) return ['ok' => false, 'error' => ['code' => 'FORBIDDEN', 'message' => 'Empresa no visible']];
      $limit = max(1, min(50, $limit)); // cap de seguridad
      $rows = $this->repo->ultimosPorTipo($empresaId, $tipoId, $limit);
      return ['ok' => true, 'data' => $rows];
   }

}
