<!-- resources/views/admin/menu.php -->
<div class="container-fluid" id="menu-page">
   <div class="row">
      <!-- Árbol (izquierda) -->
      <div class="col-lg-5">
         <div class="card shadow-sm h-100">
            <div class="card-header d-flex align-items-center">
               <i class="fas fa-sitemap mr-2"></i>
               <strong>Menú (Sidebar)</strong>
               <input id="menu-filter" class="form-control form-control-sm ml-3" placeholder="Buscar…" style="max-width:220px;">
               <div class="ml-auto btn-group">
                  <button id="menu-add-root" class="btn btn-sm btn-outline-success" data-perm="admin.menu.crear" data-perm-mode="hide" title="Nuevo ítem raíz">
                     <i class="fas fa-plus"></i>
                  </button>
                  <button id="menu-add-child" class="btn btn-sm btn-outline-success" data-perm="admin.menu.crear" data-perm-mode="disable" title="Nuevo hijo">
                     <i class="fas fa-level-down-alt fa-rotate-90"></i>
                  </button>
                  <button id="menu-del" class="btn btn-sm btn-outline-danger" data-perm="admin.menu.borrar" data-perm-mode="disable" title="Eliminar">
                     <i class="fas fa-trash"></i>
                  </button>
                  <button id="menu-expand" class="btn btn-sm btn-outline-secondary" title="Expandir">
                     <i class="fas fa-plus-square"></i>
                  </button>
                  <button id="menu-collapse" class="btn btn-sm btn-outline-secondary" title="Contraer">
                     <i class="fas fa-minus-square"></i>
                  </button>
                  <button id="menu-save-order" class="btn btn-sm btn-primary d-none" data-perm="admin.menu.reordenar" data-perm-mode="hide" title="Guardar orden">
                     <i class="fas fa-save"></i>
                  </button>
               </div>
            </div>
            <div class="card-body">
               <div id="menu-tree" class="tree tree-dark" style="min-height:520px;"></div>
            </div>
         </div>
      </div>

      <!-- Detalle (derecha) -->
      <div class="col-lg-7">
         <div class="card shadow-sm h-100">
            <div class="card-header">
               <strong>Detalle del ítem</strong>
            </div>
            <div class="card-body">
               <input type="hidden" id="m-id">
               <div class="form-row">
                  <div class="form-group col-md-8">
                     <label>Etiqueta *</label>
                     <input id="m-etiqueta" class="form-control" maxlength="80">
                  </div>
                  <div class="form-group col-md-4">
                     <label>Tipo</label>
                     <select id="m-tipo" class="form-control">
                        <option value="item">Item</option>
                        <option value="external">Enlace externo</option>
                        <option value="header">Header</option>
                        <option value="divider">Divider</option>
                     </select>
                  </div>
               </div>

               <div class="form-row">
                  <div class="form-group col-md-8">
                     <label>Vista (ruta interna)</label>
                     <input id="m-vista" class="form-control" maxlength="100" placeholder="p.ej. admin/usuarios">
                  </div>
                  <div class="form-group col-md-4">
                     <label>Slug</label>
                     <input id="m-slug" class="form-control" maxlength="80" placeholder="opcional, único por padre">
                  </div>
               </div>

               <div class="form-row">
                  <div class="form-group col-md-8">
                     <label>URL externa</label>
                     <input id="m-url" class="form-control" maxlength="255" placeholder="https://…">
                  </div>
                  <div class="form-group col-md-4">
                     <label>Target</label>
                     <select id="m-target" class="form-control">
                        <option value="_self">_self</option>
                        <option value="_blank">_blank</option>
                     </select>
                  </div>
               </div>

               <div class="form-row">
                  <div class="form-group col-md-6">
                     <label>Icono</label>
                     <input id="m-icono" class="form-control" maxlength="60" placeholder="fa-solid fa-user">
                  </div>
                  <div class="form-group col-md-6">
                     <label>Permiso requerido</label>
                     <input id="m-perm" class="form-control" maxlength="120" placeholder="admin.usuarios.ver">
                  </div>
               </div>

               <div class="form-row">
                  <div class="form-group col-md-6">
                     <label>Badge (texto)</label>
                     <input id="m-badge-text" class="form-control" maxlength="30" placeholder="Nuevo">
                  </div>
                  <div class="form-group col-md-6">
                     <label>Badge variante</label>
                     <input id="m-badge-variant" class="form-control" maxlength="20" placeholder="info/success/warning…">
                  </div>
               </div>

               <div class="form-row">
                  <div class="form-group col-md-4">
                     <label>Visible</label>
                     <select id="m-visible" class="form-control">
                        <option value="1">Sí</option>
                        <option value="0">No</option>
                     </select>
                  </div>
                  <div class="form-group col-md-4">
                     <label>Namespace</label>
                     <input id="m-namespace" class="form-control" maxlength="30" value="sidebar">
                  </div>
                  <div class="form-group col-md-4">
                     <label>Orden (solo lectura)</label>
                     <input id="m-orden" class="form-control" disabled>
                  </div>
               </div>
            </div>
            <div class="card-footer d-flex">
               <button id="m-reset" class="btn btn-light mr-2">Deshacer</button>
               <button id="m-save" data-perm="admin.menu.editar" data-perm-mode="disable" class="btn btn-primary ml-auto"><i class="fas fa-save"></i> Guardar</button>
            </div>
         </div>
      </div>
   </div>
</div>