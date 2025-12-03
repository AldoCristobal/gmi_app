<!-- resources/views/tareas/evaluacion.php -->
<div class="container-fluid" id="tareas-eval-page">

   <!-- Filtros -->
   <div class="card shadow-sm mb-3">
      <div class="card-header py-2 d-flex align-items-center">
         <h3 class="card-title mb-0">
            <i class="fas fa-clipboard-check mr-2"></i>Evaluación de tareas
         </h3>
         <div class="ml-auto">
            <button id="te-refresh" type="button" class="btn btn-sm btn-outline-secondary" title="Refrescar">
               <i class="fas fa-sync-alt"></i>
            </button>
         </div>
      </div>
      <div class="card-body pb-2">
         <form id="te-filters">
            <div class="form-row">
               <div class="form-group col-md-3">
                  <label for="te-q" class="mb-1">Buscar</label>
                  <input id="te-q"
                     class="form-control form-control-sm"
                     placeholder="Título, empresa o responsable (texto libre)">
               </div>

               <div class="form-group col-md-3">
                  <label for="te-empresa" class="mb-1">Empresa</label>
                  <select id="te-empresa" class="form-control form-control-sm">
                     <option value="">Todas</option>
                  </select>
               </div>

               <div class="form-group col-md-3">
                  <label for="te-responsable" class="mb-1">Responsable</label>
                  <select id="te-responsable" class="form-control form-control-sm">
                     <option value="">Todos</option>
                  </select>
               </div>

               <div class="form-group col-md-3 d-flex align-items-end">
                  <div class="btn-group btn-group-sm w-100" role="group">
                     <button id="te-search" type="button" class="btn btn-primary" title="Buscar">
                        <i class="fas fa-search"></i>
                     </button>
                     <button id="te-clear" type="button" class="btn btn-outline-secondary" title="Limpiar filtros">
                        <i class="fas fa-eraser"></i>
                     </button>
                  </div>
               </div>
            </div>

            <div class="form-row">
               <div class="form-group col-md-3">
                  <label for="te-tipo" class="mb-1">Tipo de tarea</label>
                  <select id="te-tipo" class="form-control form-control-sm">
                     <option value="">Todos</option>
                     <option value="OBLIGACION">Obligación</option>
                     <option value="EXTRAORDINARIA">Extraordinaria</option>
                  </select>
               </div>
               <div class="form-group col-md-3">
                  <label for="te-estado" class="mb-1">Estado</label>
                  <select id="te-estado" class="form-control form-control-sm">
                     <option value="">Todos</option>
                     <option value="PENDIENTE">Pendiente</option>
                     <option value="EN_REVISION">En revisión</option>
                     <option value="COMPLETA">Completa</option>
                     <option value="CANCELADA">Cancelada</option>
                     <option value="BLOQUEADA">Bloqueada</option>
                  </select>
               </div>
               <div class="form-group col-md-3">
                  <label for="te-desde" class="mb-1">Vencimiento desde</label>
                  <input id="te-desde" type="date" class="form-control form-control-sm">
               </div>
               <div class="form-group col-md-3">
                  <label for="te-hasta" class="mb-1">Vencimiento hasta</label>
                  <input id="te-hasta" type="date" class="form-control form-control-sm">
               </div>
            </div>
         </form>
      </div>
   </div>

   <!-- Grid -->
   <div class="card shadow-sm">
      <div class="card-body p-0">
         <div id="tareas-eval-grid" class="ag-theme-alpine-dark" style="height: 520px;"></div>
      </div>
   </div>

</div>

<!-- Modal: Evaluación de tarea -->
<div class="modal fade" id="tareas-eval-modal" tabindex="-1" aria-hidden="true" style="display:none;">
   <div class="modal-dialog modal-xl">
      <div class="modal-content">
         <div class="modal-header py-2">
            <h5 class="modal-title">
               <i class="fas fa-clipboard-check mr-2"></i>Evaluar tarea
            </h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
               &times;
            </button>
         </div>

         <div class="modal-body pt-2">
            <input type="hidden" id="te-id">

            <div class="row">
               <!-- Columna izquierda: detalle + cambio de estado + historial -->
               <div class="col-md-6 border-right">
                  <h6 class="mb-2">Detalle de la tarea</h6>

                  <dl class="row mb-2 small">
                     <dt class="col-sm-4">Empresa</dt>
                     <dd class="col-sm-8" id="te-empresa-label"></dd>

                     <dt class="col-sm-4">Título</dt>
                     <dd class="col-sm-8" id="te-titulo-label"></dd>

                     <dt class="col-sm-4">Responsable</dt>
                     <dd class="col-sm-8" id="te-responsable-label"></dd>

                     <dt class="col-sm-4">Periodo</dt>
                     <dd class="col-sm-8" id="te-periodo-label"></dd>

                     <dt class="col-sm-4">Vencimiento</dt>
                     <dd class="col-sm-8" id="te-vencimiento-label"></dd>

                     <dt class="col-sm-4">Estado actual</dt>
                     <dd class="col-sm-8" id="te-estado-actual-label"></dd>
                  </dl>

                  <!-- Toolbar de cambio de estado (solo íconos) -->
                  <div class="form-group mb-3">
                     <label class="mb-1 d-block">Cambiar estado</label>

                     <div id="te-status-toolbar"
                        class="btn-group btn-group-sm"
                        role="group"
                        data-perm="tareas.evaluacion.evaluar"
                        data-perm-mode="disable">

                        <button type="button"
                           class="btn btn-outline-light te-status-btn"
                           data-status="PENDIENTE"
                           title="Marcar como Pendiente"
                           data-toggle="tooltip">
                           <i class="far fa-clock"></i>
                        </button>

                        <button type="button"
                           class="btn btn-outline-info te-status-btn"
                           data-status="EN_REVISION"
                           title="Marcar como En revisión"
                           data-toggle="tooltip">
                           <i class="fas fa-eye"></i>
                        </button>

                        <button type="button"
                           class="btn btn-outline-success te-status-btn"
                           data-status="COMPLETA"
                           title="Marcar como Completa"
                           data-toggle="tooltip">
                           <i class="fas fa-check-circle"></i>
                        </button>

                        <button type="button"
                           class="btn btn-outline-warning te-status-btn"
                           data-status="CANCELADA"
                           title="Cancelar tarea"
                           data-toggle="tooltip">
                           <i class="fas fa-ban"></i>
                        </button>

                        <button type="button"
                           class="btn btn-outline-danger te-status-btn"
                           data-status="BLOQUEADA"
                           title="Bloquear tarea"
                           data-toggle="tooltip">
                           <i class="fas fa-lock"></i>
                        </button>

                     </div>
                  </div>

                  <div class="form-group mb-2">
                     <label for="te-comentario" class="mb-1">Comentario del evaluador (opcional)</label>
                     <textarea id="te-comentario"
                        class="form-control form-control-sm"
                        rows="3"
                        placeholder="Escribe aquí tus observaciones sobre la tarea..."></textarea>
                  </div>

                  <div class="mt-3">
                     <h6 class="mb-2">Historial de evaluaciones</h6>
                     <div id="te-historial"
                        class="border rounded p-2 small"
                        style="max-height: 220px; overflow-y: auto;">
                        <p class="text-muted mb-0">Sin evaluaciones previas.</p>
                     </div>
                  </div>
               </div>

               <!-- Columna derecha: Evidencias -->
               <div class="col-md-6">
                  <div class="d-flex justify-content-between align-items-center mb-2">
                     <h6 class="mb-0">Evidencias de la tarea</h6>
                     <small id="te-evid-resumen" class="text-muted">0 archivos</small>
                  </div>

                  <div class="table-responsive" style="max-height: 320px; overflow-y: auto;">
                     <table class="table table-sm table-hover mb-0">
                        <thead class="thead-dark">
                           <tr>
                              <th style="width:40px;">#</th>
                              <th>Archivo</th>
                              <th style="width:90px;">Tamaño</th>
                              <th style="width:130px;">Fecha</th>
                              <th style="width:80px;">Acciones</th>
                           </tr>
                        </thead>
                        <tbody id="te-evid-tbody">
                           <tr>
                              <td colspan="5" class="text-center text-muted">
                                 No hay evidencias cargadas.
                              </td>
                           </tr>
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
            <button id="te-save"
               type="button"
               class="btn btn-primary btn-sm"
               data-perm="tareas.evaluacion.evaluar"
               data-perm-mode="disable">
               Guardar evaluación
            </button>
         </div>
      </div>
   </div>
</div>

<style>
   #te-status-toolbar .btn {
      width: 36px;
      height: 32px;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 0;
   }

   #te-status-toolbar .btn.active {
      box-shadow: inset 0 0 6px rgba(255, 255, 255, 0.7);
      border-width: 2px;
   }
</style>