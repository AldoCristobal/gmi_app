<?php

/** @var string $title */
/** Este es el index de Revisiones con toolbar, modales y contenedor del grid */
?>
<div class="container-fluid">

   <!-- Filtros superiores (opcional; puedes ajustar los ids si ya los tienes) -->
   <div class="row align-items-end mb-2">
      <div class="col-md-2">
         <label class="form-label">Buscar</label>
         <input type="text" id="filtro-q" class="form-control form-control-sm" placeholder="Nombre, orden, oficio, dependencia…">
      </div>
      <div class="col-md-2">
         <label class="form-label">Tipo de Revisión</label>
         <select id="filtro-tipo" class="custom-select custom-select-sm">
            <option value="">Todos</option>
            <!-- opciones se cargan por JS -->
         </select>
      </div>
      <div class="col-md-2">
         <label class="form-label">Estatus</label>
         <select id="filtro-estatus" class="custom-select custom-select-sm">
            <option value="">Todos</option>
            <option value="en_proceso">En proceso</option>
            <option value="completa">Completa</option>
         </select>
      </div>
      <div class="col-md-2">
         <label class="form-label">Riesgo</label>
         <select id="filtro-riesgo" class="custom-select custom-select-sm">
            <option value="">Todos</option>
            <option value="bajo">Bajo</option>
            <option value="medio">Medio</option>
            <option value="alto">Alto</option>
         </select>
      </div>
      <div class="col-md-1 d-flex align-items-end">
         <button id="btn-filtrar" class="btn btn-outline-light btn-sm w-100"
            data-toggle="tooltip" title="Buscar"
            data-perm="revisiones.ver" data-perm-mode="hide">
            <i class="fas fa-search"></i>
            <span class="d-none d-md-inline ml-1">Buscar</span>
         </button>
      </div>
   </div>

   <!-- Toolbar de acciones -->
   <div class="toolbar d-flex flex-wrap align-items-end gap-2 mb-2">

      <!-- Nueva / Editar (mismo botón, cambia dinámicamente) -->
      <button id="btn-nueva" type="button"
         class="btn btn-success btn-sm"
         data-toggle="tooltip" title="Nueva / Editar (según selección)"
         data-perm="revisiones.crear,revisiones.editar" data-perm-mode="hide">
         <i class="fas fa-plus"></i>
         <span class="d-none d-md-inline ml-1">Nueva</span>
      </button>

      <!-- Añadir evidencias (requiere selección y permisos) -->
      <button id="btn-anexos" type="button"
         class="btn btn-outline-info btn-sm"
         data-toggle="tooltip" title="Añadir evidencias"
         data-perm="revisiones.subir_archivo" data-perm-mode="hide"
         disabled>
         <i class="fas fa-paperclip"></i>
         <span class="d-none d-md-inline ml-1">Añadir evidencias</span>
      </button>

      <!-- Marcar completa (requiere selección y permisos) -->
      <button id="btn-completar" type="button"
         class="btn btn-outline-primary btn-sm"
         data-toggle="tooltip" title="Marcar como completa"
         data-perm="revisiones.cambiar_estatus" data-perm-mode="hide"
         disabled>
         <i class="fas fa-check"></i>
         <span class="d-none d-md-inline ml-1">Marcar completa</span>
      </button>

   </div>

   <!-- Grid -->
   <div id="gridRevisiones" class="ag-theme-alpine-dark" style="width:100%;height:60vh;"></div>

</div>

<!-- Modal principal (crear/editar) -->
<div class="modal fade" id="modal-revision" tabindex="-1" aria-hidden="true">
   <div class="modal-dialog modal-xl modal-dialog-scrollable">
      <div class="modal-content">
         <div class="modal-header">
            <h5 id="modal-revision-titulo" class="modal-title">Registrar revisión</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
               <span aria-hidden="true">&times;</span>
            </button>
         </div>
         <div id="modal-revision-body" class="modal-body"></div>
      </div>
   </div>
</div>

<!-- Modal anexos -->
<div class="modal fade" id="modal-anexos" tabindex="-1" aria-hidden="true">
   <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title"><i class="fas fa-paperclip"></i> Añadir evidencias</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
               <span aria-hidden="true">&times;</span>
            </button>
         </div>
         <div id="modal-anexos-body" class="modal-body"></div>
      </div>
   </div>
</div>