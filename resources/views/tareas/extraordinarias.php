<!-- resources/views/tareas/extraordinarias.php -->
<div class="container-fluid" id="tareas-extra-page">

   <!-- Filtros -->
   <div class="card shadow-sm mb-3">
      <div class="card-header py-2 d-flex align-items-center">
         <h3 class="card-title mb-0">
            <i class="fas fa-tasks mr-2"></i>Tareas extraordinarias
         </h3>
         <span class="ml-2 badge badge-info">Manual</span>
         <div class="ml-auto">
            <button id="tx-refresh" type="button" class="btn btn-sm btn-outline-secondary" title="Refrescar">
               <i class="fas fa-sync-alt"></i>
            </button>
         </div>
      </div>
      <div class="card-body pb-2">
         <form id="tx-filters" class="form-row">
            <div class="form-group col-md-4">
               <label for="tx-q" class="mb-1">Buscar</label>
               <input id="tx-q"
                  class="form-control form-control-sm"
                  placeholder="Título, empresa o responsable (filtro rápido)">
            </div>

            <div class="form-group col-md-3">
               <label for="tx-estado" class="mb-1">Estado</label>
               <select id="tx-estado" class="form-control form-control-sm">
                  <option value="">Todos</option>
                  <option value="PENDIENTE">Pendiente</option>
                  <option value="EN_REVISION">En revisión</option>
                  <option value="COMPLETA">Completa</option>
                  <option value="CANCELADA">Cancelada</option>
                  <option value="BLOQUEADA">Bloqueada</option>
               </select>
            </div>

            <div class="form-group col-md-3">
               <label for="tx-empresa" class="mb-1">Empresa</label>
               <select id="tx-empresa" class="form-control form-control-sm">
                  <option value="">Todas</option>
               </select>
            </div>

            <div class="form-group col-md-2 d-flex align-items-end">
               <div class="btn-group btn-group-sm w-100" role="group">
                  <button id="tx-search" type="button" class="btn btn-primary">
                     <i class="fas fa-search"></i>
                  </button>
                  <button id="tx-new" type="button"
                     class="btn btn-success"
                     data-perm="tareas.extra.crear tareas.extra.editar"
                     data-perm-any
                     data-perm-mode="disable">
                     <i class="fas fa-plus"></i>
                  </button>
                  <button id="tx-delete" type="button"
                     class="btn btn-danger"
                     data-perm="tareas.extra.borrar"
                     data-perm-mode="disable"
                     disabled>
                     <i class="fas fa-trash"></i>
                  </button>
               </div>
            </div>
         </form>
      </div>
   </div>

   <!-- Grid -->
   <div class="card shadow-sm">
      <div class="card-body p-0">
         <div id="tareas-extra-grid" class="ag-theme-alpine-dark" style="height: 520px;"></div>
      </div>
   </div>

</div>

<!-- Modal: crear / editar tarea extraordinaria -->
<div class="modal fade" id="tareas-extra-modal" tabindex="-1" aria-hidden="true" style="display:none;">
   <div class="modal-dialog modal-lg">
      <div class="modal-content">
         <div class="modal-header py-2">
            <h5 id="tx-modal-title" class="modal-title">Tarea extraordinaria</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">&times;</button>
         </div>

         <div class="modal-body pt-2">
            <input type="hidden" id="tx-id">

            <div class="form-row">
               <div class="form-group col-md-6">
                  <label class="mb-1" for="tx-titulo">Título</label>
                  <input id="tx-titulo" class="form-control form-control-sm">
               </div>
               <div class="form-group col-md-6">
                  <label class="mb-1" for="tx-empresa-modal">Empresa</label>
                  <select id="tx-empresa-modal" class="form-control form-control-sm">
                     <option value="">Seleccione…</option>
                  </select>
               </div>
            </div>

            <div class="form-row">
               <div class="form-group col-md-4">
                  <label class="mb-1" for="tx-responsable">Responsable</label>
                  <select id="tx-responsable" class="form-control form-control-sm">
                     <option value="">Seleccione…</option>
                  </select>
               </div>
               <div class="form-group col-md-4">
                  <label class="mb-1" for="tx-periodo-inicio">Periodo inicio</label>
                  <input id="tx-periodo-inicio" type="date" class="form-control form-control-sm">
               </div>
               <div class="form-group col-md-4">
                  <label class="mb-1" for="tx-periodo-fin">Periodo fin</label>
                  <input id="tx-periodo-fin" type="date" class="form-control form-control-sm">
               </div>
            </div>

            <div class="form-row">
               <div class="form-group col-md-4">
                  <label class="mb-1" for="tx-fecha-objetivo">Fecha objetivo</label>
                  <input id="tx-fecha-objetivo" type="date" class="form-control form-control-sm">
               </div>
               <div class="form-group col-md-4">
                  <label class="mb-1" for="tx-fecha-vencimiento">Fecha vencimiento</label>
                  <input id="tx-fecha-vencimiento" type="date" class="form-control form-control-sm">
               </div>
               <div class="form-group col-md-4">
                  <label class="mb-1">Estado</label>
                  <input id="tx-estado-label"
                     class="form-control form-control-sm"
                     readonly
                     value="Pendiente (se gestiona en Mis tareas)">
               </div>
            </div>

            <div class="form-row">
               <div class="form-group col-md-12">
                  <label class="mb-1" for="tx-observaciones">Observaciones</label>
                  <textarea id="tx-observaciones"
                     rows="3"
                     class="form-control form-control-sm"></textarea>
               </div>
            </div>
         </div>

         <div class="modal-footer py-2">
            <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancelar</button>
            <button id="tx-save" type="button" class="btn btn-primary btn-sm">
               Guardar
            </button>
         </div>
      </div>
   </div>
</div>