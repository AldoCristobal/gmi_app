<!-- resources/views/empresas/expediente-panel.php -->
<div class="container-fluid" id="emp-expediente-page">

   <!-- Filtros -->
   <div class="card shadow-sm mb-3">
      <div class="card-header py-2 d-flex align-items-center">
         <h3 class="card-title mb-0">
            <i class="fas fa-folder-open mr-2"></i>Expediente de empresas
         </h3>
         <div class="ml-auto d-flex align-items-center">
            <button id="ee-refresh" type="button" class="btn btn-sm btn-outline-secondary" title="Refrescar">
               <i class="fas fa-sync-alt"></i>
            </button>
         </div>
      </div>
      <div class="card-body pb-2">
         <form id="ee-filters" class="form-row">
            <div class="form-group col-md-4">
               <label for="ee-q" class="mb-1">Buscar</label>
               <input id="ee-q"
                  class="form-control form-control-sm"
                  placeholder="Nombre, RFC o grupo">
            </div>

            <div class="form-group col-md-3">
               <label for="ee-area" class="mb-1">Área</label>
               <select id="ee-area" class="form-control form-control-sm">
                  <option value="">Todas</option>
               </select>
            </div>

            <div class="form-group col-md-3">
               <label for="ee-responsable" class="mb-1">Responsable</label>
               <select id="ee-responsable" class="form-control form-control-sm">
                  <option value="">Todos</option>
               </select>
            </div>

            <div class="form-group col-md-2 d-flex align-items-end">
               <div class="btn-group btn-group-sm w-100" role="group">
                  <button id="ee-search" type="button" class="btn btn-primary" title="Buscar">
                     <i class="fas fa-search"></i>
                  </button>
                  <button id="ee-clear" type="button" class="btn btn-outline-secondary" title="Limpiar filtros">
                     <i class="fas fa-eraser"></i>
                  </button>
               </div>
            </div>

            <div class="form-group col-md-3 mt-2">
               <div class="custom-control custom-checkbox">
                  <input type="checkbox" class="custom-control-input" id="ee-only-docs">
                  <label class="custom-control-label" for="ee-only-docs">
                     Solo empresas con documentos
                  </label>
               </div>
            </div>
         </form>
      </div>
   </div>

   <!-- Tarjetas -->
   <div class="card shadow-sm">
      <div class="card-body">
         <div id="ee-cards" class="row">
            <!-- tarjetas generadas por JS -->
         </div>

         <div id="ee-empty" class="text-center text-muted py-3" style="display:none;">
            Sin empresas para mostrar.
         </div>

         <!-- Paginación simple -->
         <div class="d-flex justify-content-between align-items-center mt-3">
            <div id="ee-summary" class="small text-muted"></div>
            <div class="btn-group btn-group-sm" role="group">
               <button id="ee-page-prev" type="button" class="btn btn-outline-secondary">
                  <i class="fas fa-chevron-left"></i>
               </button>
               <button id="ee-page-next" type="button" class="btn btn-outline-secondary">
                  <i class="fas fa-chevron-right"></i>
               </button>
            </div>
         </div>
      </div>
   </div>

</div>

<!-- Modal detalle de expediente (solo consulta) -->
<div class="modal fade" id="emp-expediente-modal" tabindex="-1" aria-hidden="true" style="display:none;">
   <div class="modal-dialog modal-xl">
      <div class="modal-content">
         <div class="modal-header py-2">
            <h5 class="modal-title">
               <i class="fas fa-file-alt mr-2"></i>
               Expediente de empresa
            </h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">&times;</button>
         </div>
         <div class="modal-body">
            <div class="row">
               <!-- Columna izquierda: info general -->
               <div class="col-md-4">
                  <h6 class="mb-2">Datos de la empresa</h6>
                  <dl class="row mb-0 small" id="ee-detail-dl">
                     <!-- llenado por JS -->
                  </dl>
               </div>

               <!-- Columna derecha: documentos -->
               <div class="col-md-8">
                  <div class="d-flex justify-content-between align-items-center mb-2">
                     <h6 class="mb-0">Documentos</h6>
                     <span id="ee-docs-count" class="badge badge-info">0 documentos</span>
                  </div>

                  <div class="table-responsive" style="max-height: 380px; overflow-y: auto;">
                     <table class="table table-sm table-hover table-striped mb-0">
                        <thead class="thead-dark">
                           <tr>
                              <th style="width:40px;">#</th>
                              <th>Tipo</th>
                              <th>Nombre archivo</th>
                              <th style="width:90px;">Versión</th>
                              <th style="width:110px;">Tamaño</th>
                              <th style="width:150px;">Fecha</th>
                              <th style="width:60px;"></th>
                           </tr>
                        </thead>
                        <tbody id="ee-docs-tbody">
                           <!-- filas por JS -->
                        </tbody>
                     </table>
                  </div>
               </div>
            </div>
         </div>
         <div class="modal-footer py-2">
            <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">
               Cerrar
            </button>
         </div>
      </div>
   </div>
</div>
