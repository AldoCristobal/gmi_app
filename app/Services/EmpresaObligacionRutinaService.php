<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\EmpresaObligacionRepository;
use App\Repositories\EmpresaRepository;
use App\Repositories\ObligacionRepository;
use Throwable;

final class EmpresaObligacionRutinaService
{
   public function __construct(
      private EmpresaObligacionRepository $eoRepo = new EmpresaObligacionRepository(),
      private EmpresaRepository $empRepo        = new EmpresaRepository(),
      private ObligacionRepository $oblRepo     = new ObligacionRepository(),
   ) {}

   /**
    * Lista obligaciones asignadas a la empresa + resumen de rutina.
    */
   public function listarPorEmpresa(int $empresaId, array $scope = []): array
   {
      try {
         $empresa = $this->empRepo->findById($empresaId);
         if (!$empresa) {
            return [
               'ok'    => false,
               'error' => [
                  'code'    => 'NOT_FOUND',
                  'message' => 'Empresa no encontrada',
               ],
            ];
         }

         // Lista usando nombre global del repo
         $obligaciones = $this->eoRepo->listWithObligacionByEmpresa($empresaId);

         return [
            'ok'   => true,
            'data' => [
               'empresa'      => [
                  'id'             => $empresa['id'],
                  'nombre'         => $empresa['nombre'],
                  'rfc'            => $empresa['rfc'],
                  'responsable_id' => $empresa['responsable_id'],
               ],
               'obligaciones' => $obligaciones,
            ],
         ];
      } catch (Throwable $e) {
         return [
            'ok'    => false,
            'error' => [
               'code'    => 'EXCEPTION',
               'message' => $e->getMessage(),
            ],
         ];
      }
   }

   /**
    * Detalle de rutina para una empresa + obligación.
    */
   public function obtenerRutina(int $empresaId, int $obligacionId, array $scope = []): array
   {
      try {
         $empresa = $this->empRepo->findById($empresaId);
         if (!$empresa) {
            return [
               'ok'    => false,
               'error' => [
                  'code'    => 'NOT_FOUND',
                  'message' => 'Empresa no encontrada',
               ],
            ];
         }

         $obligacion = $this->oblRepo->findById($obligacionId);
         if (!$obligacion) {
            return [
               'ok'    => false,
               'error' => [
                  'code'    => 'NOT_FOUND',
                  'message' => 'Obligación no encontrada',
               ],
            ];
         }

         // Usar nombre alineado:
         $eo = $this->eoRepo->findByEmpresaAndObligacion($empresaId, $obligacionId);

         // Si la obligación no tiene rutina configurada
         if (!$eo) {
            return [
               'ok'   => true,
               'data' => [
                  'empresa_id'          => $empresa['id'],
                  'empresa_nombre'      => $empresa['nombre'],
                  'empresa_responsable' => $empresa['responsable_id'],
                  'obligacion_id'       => $obligacion['id'],
                  'obligacion_clave'    => $obligacion['clave'],
                  'obligacion_desc'     => $obligacion['descripcion'],
                  'periodicidad'        => null,
                  'dia_vencimiento'     => null,
                  'dias_anticipacion'   => 5,
                  'offset_dias'         => 0,
                  'responsable_id'      => null,
                  'responsable_default' => $empresa['responsable_id'],
                  'enviar_correo'       => 1,
                  'fecha_inicio'        => null,
                  'fecha_fin'           => null,
                  'notas'               => null,
               ],
            ];
         }

         // Ya existe configuración
         return [
            'ok'   => true,
            'data' => [
               'empresa_id'          => $empresa['id'],
               'empresa_nombre'      => $empresa['nombre'],
               'empresa_responsable' => $empresa['responsable_id'],
               'obligacion_id'       => $obligacion['id'],
               'obligacion_clave'    => $obligacion['clave'],
               'obligacion_desc'     => $obligacion['descripcion'],
               'periodicidad'        => $eo['periodicidad'],
               'dia_vencimiento'     => $eo['dia_vencimiento'],
               'dias_anticipacion'   => $eo['dias_anticipacion'],
               'offset_dias'         => $eo['offset_dias'],
               'responsable_id'      => $eo['responsable_id'],
               'responsable_default' => $empresa['responsable_id'],
               'enviar_correo'       => $eo['enviar_correo'],
               'fecha_inicio'        => $eo['fecha_inicio'],
               'fecha_fin'           => $eo['fecha_fin'],
               'notas'               => $eo['notas'],
            ],
         ];
      } catch (Throwable $e) {
         return [
            'ok'    => false,
            'error' => [
               'code'    => 'EXCEPTION',
               'message' => $e->getMessage(),
            ],
         ];
      }
   }

   /**
    * Crear/actualizar rutina para empresa + obligación.
    */
   public function guardarRutina(int $empresaId, int $obligacionId, array $payload, array $scope = []): array
   {
      try {
         $diaVenc      = isset($payload['dia_vencimiento']) ? (int)$payload['dia_vencimiento'] : null;
         $diasAnt      = isset($payload['dias_anticipacion']) ? (int)$payload['dias_anticipacion'] : 5;
         $offsetDias   = isset($payload['offset_dias']) ? (int)$payload['offset_dias'] : 0;
         $respId       = !empty($payload['responsable_id']) ? (int)$payload['responsable_id'] : null;
         $enviarCorreo = isset($payload['enviar_correo']) ? (int)$payload['enviar_correo'] : 1;
         $fechaInicio  = $payload['fecha_inicio'] ?? null;
         $fechaFin     = $payload['fecha_fin'] ?? null;
         $notas        = $payload['notas'] ?? null;
         $periodicidad = $payload['periodicidad'] ?? null;

         if ($diaVenc !== null && ($diaVenc < 1 || $diaVenc > 31)) {
            return [
               'ok'    => false,
               'error' => [
                  'code'    => 'VALIDATION',
                  'message' => 'El día objetivo debe estar entre 1 y 31.',
               ],
            ];
         }

         if ($diasAnt < 0) {
            return [
               'ok'    => false,
               'error' => [
                  'code'    => 'VALIDATION',
                  'message' => 'Los días de anticipación no pueden ser negativos.',
               ],
            ];
         }

         $validPeriodicidades = ['MENSUAL', 'BIMESTRAL', 'TRIMESTRAL', 'SEMESTRAL', 'ANUAL', 'EVENTUAL'];
         if ($periodicidad !== null && !in_array($periodicidad, $validPeriodicidades, true)) {
            return [
               'ok'    => false,
               'error' => [
                  'code'    => 'VALIDATION',
                  'message' => 'Periodicidad no válida.',
               ],
            ];
         }

         // Buscar con nombre global
         $existente = $this->eoRepo->findByEmpresaAndObligacion($empresaId, $obligacionId);

         if ($existente) {
            // Actualizar con método global
            $this->eoRepo->updateRutinaById((int)$existente['id'], [
               'dia_vencimiento'   => $diaVenc,
               'dias_anticipacion' => $diasAnt,
               'offset_dias'       => $offsetDias,
               'responsable_id'    => $respId,
               'enviar_correo'     => $enviarCorreo,
               'fecha_inicio'      => $fechaInicio,
               'fecha_fin'         => $fechaFin,
               'notas'             => $notas,
               'periodicidad'      => $periodicidad ?: $existente['periodicidad'],
            ]);

            return ['ok' => true];
         }

         // Crear nueva configuración usando createRutina()
         $this->eoRepo->createRutina([
            'empresa_id'        => $empresaId,
            'obligacion_id'     => $obligacionId,
            'periodicidad'      => $periodicidad ?: 'MENSUAL',
            'tipo_dias'         => 'NATURALES',
            'dia_vencimiento'   => $diaVenc,
            'offset_dias'       => $offsetDias,
            'dias_anticipacion' => $diasAnt,
            'fecha_inicio'      => $fechaInicio ?: date('Y-m-d'),
            'fecha_fin'         => $fechaFin,
            'responsable_id'    => $respId,
            'area_id'           => null,
            'enviar_correo'     => $enviarCorreo,
            'notas'             => $notas,
            'activo'            => 1,
         ]);

         return ['ok' => true];
      } catch (Throwable $e) {
         return [
            'ok'    => false,
            'error' => [
               'code'    => 'EXCEPTION',
               'message' => $e->getMessage(),
            ],
         ];
      }
   }
}
