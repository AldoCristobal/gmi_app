<!-- Filtros -->
<!-- Card de filtros -->
<div class="card shadow-sm mb-3">
   <div class="card-header py-2">
      <h3 class="card-title"><i class="fas fa-filter mr-2"></i>Filtros de búsqueda</h3>
   </div>
   <div class="card-body">
      <form id="form-filtros" class="form-row">
         <div class="form-group col-md-4">
            <label for="f-q" class="mb-1">Buscar</label>
            <input id="f-q" class="form-control form-control-sm" placeholder="Nombre o email...">
         </div>
         <div class="form-group col-md-4">
            <label for="f-activo" class="mb-1">Estado</label>
            <select id="f-activo" class="form-control form-control-sm">
               <option value="">Todos</option>
               <option value="1">Activos</option>
               <option value="0">Inactivos</option>
            </select>
         </div>
         <div class="form-group col-md-4 d-flex align-items-end">
            <button id="btn-search" type="button" class="btn btn-primary btn-sm mr-2">
               <i class="fas fa-search"></i> Buscar
            </button>
            <button id="btn-new" type="button" data-perm="admin.users.crear admin.users.editar"
               data-perm-any
               data-perm-mode="disable" class="btn btn-success btn-sm mr-2">
               <i class="fas fa-plus"></i> Nuevo
            </button>
            <button id="btn-delete" type="button" class="btn btn-danger btn-sm" data-perm="admin.users.borrar" data-perm-mode="disable" disabled>
               <i class="fas fa-trash"></i> Eliminar
            </button>
         </div>
      </form>
   </div>
</div>

<!-- Grid -->
<div id="gridUsuarios" class="ag-theme-alpine-dark" style="height:500px;"></div>

<!-- Modal compacto 3x3 -->
<div class="modal fade" id="user-modal" tabindex="-1" aria-hidden="true" style="display:none;">
   <div class="modal-dialog modal-lg"><!-- modal-lg para tener 3 cols cómodas -->
      <div class="modal-content">
         <div class="modal-header py-2">
            <h5 id="user-modal-title" class="modal-title">Usuario</h5>
            <button type="button" class="close" aria-label="Close" data-dismiss="modal">&times;</button>
         </div>

         <div class="modal-body pt-2">
            <input type="hidden" id="u-id">

            <div class="form-row"><!-- fila 1 -->
               <div class="form-group col-md-4">
                  <label class="mb-1">Nombre</label>
                  <input id="u-nombre" class="form-control form-control-sm">
               </div>
               <div class="form-group col-md-4">
                  <label class="mb-1">Email</label>
                  <input id="u-email" type="email" class="form-control form-control-sm">
               </div>
               <div class="form-group col-md-4">
                  <label class="mb-1">Área</label>
                  <select id="u-area_id" class="form-control form-control-sm">
                     <option value="">Seleccione…</option>
                  </select>
               </div>
            </div>

            <div class="form-row"><!-- fila 2 -->
               <div class="form-group col-md-4">
                  <label class="mb-1">Jefe</label>
                  <select id="u-jefe_id" class="form-control form-control-sm">
                     <option value="">(sin jefe)</option>
                  </select>
               </div>
               <div class="form-group col-md-8" id="pwd-group">
                  <div class="form-row">
                     <div class="col-md-6">
                        <label class="mb-1">Password</label>
                        <input id="u-password" type="password" class="form-control form-control-sm">
                     </div>
                     <div class="col-md-6">
                        <label class="mb-1">Repite password</label>
                        <input id="u-password2" type="password" class="form-control form-control-sm">
                     </div>
                  </div>
               </div>
            </div>

            <div class="form-row"><!-- fila 3 -->
               <div class="form-group col-md-12">
                  <label class="mb-1">Roles</label>
                  <!-- multiple para elegir varios; size=6 para altura cómoda -->
                  <select id="u-roles" class="form-control form-control-sm" multiple size="6"></select>
                  <small class="text-muted">Ctrl/Cmd + clic para seleccionar múltiples.</small>
               </div>
            </div>
         </div>

         <div class="modal-footer py-2">
            <button class="btn btn-secondary btn-sm" data-dismiss="modal">Cancelar</button>
            <button id="user-save" class="btn btn-primary btn-sm">Guardar</button>
         </div>
      </div>
   </div>
</div>