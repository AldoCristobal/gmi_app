<div class="container-fluid" id="mis-tareas-page">

   <!-- Filtros + Toolbar -->
   <div class="row align-items-end mb-2">

      <div class="col-md-3">
         <label class="form-label">Buscar</label>
         <input type="text"
            id="t-q"
            class="form-control form-control-sm"
            placeholder="Empresa, título, responsable…">
      </div>

      <div class="col-md-2">
         <label class="form-label">Estado</label>
         <select id="t-estado" class="form-control form-control-sm">
            <option value="">Todos</option>
            <option value="PENDIENTE">Pendiente</option>
            <option value="EN_REVISION">En revisión</option>
            <option value="COMPLETA">Completa</option>
            <option value="CANCELADA">Cancelada</option>
            <option value="BLOQUEADA">Bloqueada</option>
         </select>
      </div>

      <div class="col-md-2">
         <label class="form-label">Tipo</label>
         <select id="t-tipo" class="form-control form-control-sm">
            <option value="">Todos</option>
            <option value="OBLIGACION">Obligación</option>
            <option value="EXTRAORDINARIA">Extraordinaria</option>
         </select>
      </div>

      <div class="col-md-2 d-flex align-items-end">
         <button id="t-btn-evidencias"
            class="btn btn-outline-info btn-sm w-100 mr-1"
            data-perm="tareas.evidencias.ver"
            data-perm-mode="disable"
            title="Ver y gestionar evidencias de la tarea seleccionada"
            disabled>
            <i class="fas fa-paperclip"></i>
            <span class="d-none d-md-inline ml-1">Evidencias</span>
         </button>
      </div>

      <div class="col-md-2 d-flex align-items-end mb-1">
         <button id="t-btn-enviar-revision"
            class="btn btn-outline-warning btn-sm w-100 mr-1"
            data-perm="tareas.cambiar_estado"
            data-perm-mode="disable"
            title="Enviar a revisión la tarea seleccionada"
            disabled>
            <i class="fas fa-paper-plane"></i>
            <span class="d-none d-md-inline ml-1">Enviar a revisión</span>
         </button>
      </div>

      <div class="col-md-1 d-flex align-items-end mb-1">
         <button id="t-btn-reabrir"
            class="btn btn-outline-light btn-sm w-100 mr-1"
            data-perm="tareas.cambiar_estado"
            data-perm-mode="disable"
            title="Reabrir tarea (volver a pendiente)"
            disabled>
            <i class="fas fa-undo"></i>
         </button>
      </div>

      <div class="col-md-1 d-flex align-items-end mb-1">
         <button id="t-btn-refresh"
            class="btn btn-outline-light btn-sm w-100"
            title="Refrescar listado">
            <i class="fas fa-sync-alt"></i>
         </button>
      </div>
   </div>

   <!-- Grid -->
   <div id="misTareasGrid" class="ag-theme-alpine-dark" style="width:100%;height:60vh;"></div>

</div>

<!-- Modal: Evidencias de tarea -->
<div class="modal fade" id="tarea-evidencias-modal" tabindex="-1" aria-hidden="true">
   <div class="modal-dialog modal-xl modal-dialog-scrollable">
      <div class="modal-content">
         <div class="modal-header">
            <h5 id="tarea-evidencias-titulo" class="modal-title">Evidencias de la tarea</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
               <span aria-hidden="true">&times;</span>
            </button>
         </div>

         <div class="modal-body">
            <div class="row">
               <!-- Columna: info de la tarea -->
               <div class="col-md-4">
                  <h6>Información de la tarea</h6>
                  <dl id="t-evid-info" class="row mb-3" style="font-size:0.9rem;">
                     <!-- Se rellena por JS -->
                  </dl>

                  <div class="alert alert-secondary p-2" style="font-size:0.8rem;">
                     <i class="fas fa-info-circle"></i>
                     Puedes subir evidencias mientras la tarea esté en estado
                     <strong>PENDIENTE</strong>.
                  </div>
               </div>

               <!-- Columna: evidencias y subida -->
               <div class="col-md-8">
                  <div class="d-flex justify-content-between align-items-center mb-2">
                     <h6 class="mb-0">Archivos de evidencia</h6>
                     <small id="t-evid-resumen" class="text-muted"></small>
                  </div>

                  <!-- Upload -->
                  <form id="t-evid-form" class="mb-3">
                     <input type="hidden" id="t-evid-tarea-id" name="tarea_id" value="">
                     <div class="form-row">
                        <div class="col-md-8">
                           <div class="custom-file">
                              <input type="file"
                                 class="custom-file-input"
                                 id="t-evid-files"
                                 name="files[]"
                                 multiple
                                 data-perm="tareas.evidencias.subir"
                                 data-perm-mode="disable">
                              <label class="custom-file-label" for="t-evid-files">Seleccionar archivos…</label>
                           </div>
                        </div>
                        <div class="col-md-4">
                           <button type="submit"
                              class="btn btn-outline-success btn-sm btn-block mt-2 mt-md-0"
                              data-perm="tareas.evidencias.subir"
                              data-perm-mode="disable">
                              <i class="fas fa-upload"></i>
                              <span class="d-none d-md-inline ml-1">Subir evidencias</span>
                           </button>
                        </div>
                     </div>
                  </form>

                  <!-- Tabla de evidencias -->
                  <div class="table-responsive" style="max-height:50vh; overflow-y:auto;">
                     <table class="table table-sm table-striped table-hover mb-0">
                        <thead class="thead-dark">
                           <tr>
                              <th style="width:3rem;">#</th>
                              <th>Archivo</th>
                              <th style="width:6rem;">Tamaño</th>
                              <th style="width:8rem;">Fecha</th>
                              <th style="width:8rem;">Subido por</th>
                              <th style="width:7rem;">Acciones</th>
                           </tr>
                        </thead>
                        <tbody id="t-evid-tbody">
                           <!-- Se llena por JS -->
                        </tbody>
                     </table>
                  </div>
               </div>
            </div>
         </div>

      </div>
   </div>
</div>