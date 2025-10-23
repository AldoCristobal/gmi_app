<?php

/** @var string $title */
?>
<div class="container-fluid">

   <!-- Encabezado mínimo con búsqueda -->
   <div class="row align-items-end mb-2">
      <div class="col-md-4">
         <label class="form-label">Buscar</label>
         <input type="text" id="h-q" class="form-control form-control-sm" placeholder="Nombre, orden, oficio, dependencia…">
      </div>
      <div class="col-md-2 d-flex align-items-end">
         <button id="h-btn-search" class="btn btn-outline-light btn-sm w-100"
            data-toggle="tooltip" title="Buscar"
            data-perm="revisiones.ver" data-perm-mode="hide">
            <i class="fas fa-search"></i>
            <span class="d-none d-md-inline ml-1">Buscar</span>
         </button>
      </div>
   </div>

   <!-- Tabla (AG Grid) mínima: Nombre, Área, Estatus, Acción -->
   <div id="historialGrid" class="ag-theme-alpine-dark" style="width:100%;height:60vh;"></div>

</div>

<!-- Modal: Detalle de Revisión (solo lectura) -->
<div class="modal fade" id="historial-detalle-modal" tabindex="-1" aria-hidden="true">
   <div class="modal-dialog modal-xl modal-dialog-scrollable">
      <div class="modal-content">
         <div class="modal-header">
            <h5 id="historial-detalle-titulo" class="modal-title">Detalle de revisión</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
               <span aria-hidden="true">&times;</span>
            </button>
         </div>

         <div id="historial-detalle-body" class="modal-body p-3">
            <!-- Se rellena por JS: info general + documentos (descarga) -->
         </div>
      </div>
   </div>
</div>