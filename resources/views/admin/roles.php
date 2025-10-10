<!-- resources/views/admin/roles.php -->
<div class="container-fluid" id="roles-page">
   <div class="row">
      <!-- Columna: Roles (Grid) -->
      <div class="col-lg-7">
         <div class="card shadow-sm">
            <div class="card-header d-flex align-items-center gap-2">
               <i class="fas fa-user-shield mr-2"></i>
               <strong>Roles</strong>
               <div class="ml-auto d-flex align-items-center gap-2">
                  <input id="r-q" class="form-control form-control-sm" placeholder="Buscar (nombre o slug)" style="width: 240px;">
                  <button id="r-btn-search" class="btn btn-sm btn-outline-secondary">
                     <i class="fas fa-search"></i>
                  </button>
                  <button id="r-btn-refresh" class="btn btn-sm btn-outline-secondary" title="Refrescar">
                     <i class="fas fa-sync"></i>
                  </button>
                  <div class="vr mx-2"></div>
                  <button id="r-btn-new" class="btn btn-sm btn-success" data-perm="admin.roles.crear" data-perm-mode="disable">
                     <i class="fas fa-plus"></i> Nuevo
                  </button>
                  <button id="r-btn-edit" class="btn btn-sm btn-warning" data-perm="admin.roles.editar" data-perm-mode="disable" disabled>
                     <i class="fas fa-pen"></i> Editar
                  </button>
                  <button id="r-btn-del" class="btn btn-sm btn-danger" data-perm="admin.roles.borrar" data-perm-mode="disable" disabled>
                     <i class="fas fa-trash"></i> Eliminar
                  </button>
               </div>
            </div>
            <div class="card-body p-0">
               <div id="gridRoles" style="height: 520px;" class="ag-theme-alpine-dark"></div>
            </div>
         </div>
      </div>

      <!-- Columna: Permisos (FancyTree) -->
      <div class="col-lg-5">
         <div class="card shadow-sm h-100">
            <div class="card-header d-flex align-items-center">
               <strong class="mr-2">Permisos del rol</strong>
               <small id="rp-rol-label" class="text-muted">—</small>
               <div class="ml-auto d-flex align-items-center">
                  <input id="rp-filter" type="text"
                     class="form-control form-control-sm mr-2"
                     placeholder="Buscar…" style="max-width:200px;">

                  <button id="rp-expand" class="btn btn-sm btn-outline-secondary mr-1" title="Expandir">
                     <i class="fas fa-plus-square"></i>
                  </button>
                  <button id="rp-collapse" class="btn btn-sm btn-outline-secondary mr-1" title="Contraer">
                     <i class="fas fa-minus-square"></i>
                  </button>
                  <button id="rp-select-all" class="btn btn-sm btn-outline-info mr-1" title="Marcar todos">
                     <i class="fas fa-check-square"></i>
                  </button>
                  <button id="rp-unselect-all" class="btn btn-sm btn-outline-warning mr-1" title="Limpiar">
                     <i class="fas fa-square"></i>
                  </button>
                  <button id="rp-save" class="btn btn-sm btn-primary" data-perm="admin.roles.permisos" data-perm-mode="disable" disabled title="Guardar">
                     <i class="fas fa-save"></i>
                  </button>
               </div>

            </div>
            <div class="card-body" style="padding: .5rem 1rem;">
               <div class="ft-dark">
                  <div id="rp-tree"></div>
               </div>
            </div>
         </div>
      </div>
   </div>
</div>

<!-- Modal: Crear/Editar Rol -->
<div class="modal fade" id="rol-modal" tabindex="-1" role="dialog" aria-hidden="true">
   <div class="modal-dialog modal-dialog-scrollable" role="document">
      <div class="modal-content">
         <div class="modal-header">
            <h5 id="rol-modal-title" class="modal-title">Nuevo rol</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span>&times;</span></button>
         </div>
         <div class="modal-body">
            <input type="hidden" id="rol-id">
            <div class="form-group">
               <label>Nombre *</label>
               <input id="rol-nombre" class="form-control" maxlength="60">
            </div>
            <div class="form-group">
               <label>Slug <small class="text-muted">(opcional; minúsculas y _)</small></label>
               <input id="rol-slug" class="form-control" maxlength="80">
            </div>
            <div class="form-group">
               <label>Descripción</label>
               <textarea id="rol-desc" class="form-control" rows="2" maxlength="255"></textarea>
            </div>
            <div class="form-row">
               <div class="form-group col-6">
                  <label>Prioridad</label>
                  <input id="rol-prio" type="number" class="form-control" value="100">
               </div>
               <div class="form-group col-6">
                  <label>Activo</label>
                  <select id="rol-activo" class="form-control">
                     <option value="1">Sí</option>
                     <option value="0">No</option>
                  </select>
               </div>
            </div>
            <small class="text-muted">Los campos marcados con * son obligatorios.</small>
         </div>
         <div class="modal-footer">
            <button class="btn btn-light" data-dismiss="modal">Cancelar</button>
            <button id="rol-save" class="btn btn-primary"><i class="fas fa-save"></i> Guardar</button>
         </div>
      </div>
   </div>
</div>