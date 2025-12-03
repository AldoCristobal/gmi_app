<!-- resources/views/admin/menu_rol.php -->
<div class="container-fluid" id="menu-rol-page">
   <div class="row">

      <!-- Roles (izquierda) -->
      <div class="col-lg-4">
         <div class="card shadow-sm h-100">
            <div class="card-header d-flex align-items-center">
               <i class="fas fa-user-shield mr-2"></i>
               <strong>Roles</strong>
               <input id="mr-role-filter" class="form-control form-control-sm ml-3"
                  placeholder="Buscar rol…" style="max-width:200px;">
            </div>
            <div class="card-body p-0">
               <table class="table table-hover table-dark table-sm mb-0" id="mr-roles-table">
                  <thead>
                     <tr>
                        <th style="width:60px;">ID</th>
                        <th>Nombre</th>
                     </tr>
                  </thead>
                  <tbody></tbody>
               </table>
            </div>
         </div>
      </div>

      <!-- Menú asignado al rol (derecha) -->
      <div class="col-lg-8">
         <div class="card shadow-sm h-100">
            <div class="card-header d-flex align-items-center">
               <i class="fas fa-sitemap mr-2"></i>
               <strong>Menú asignado al rol</strong>
               <div class="ml-auto d-flex align-items-center">
                  <label for="mr-home" class="mb-0 mr-2 small text-muted">
                     Vista de inicio:
                  </label>
                  <select id="mr-home" class="form-control form-control-sm mr-2" style="min-width:220px;">
                     <option value="">(Sin vista de inicio)</option>
                  </select>

                  <button id="mr-save"
                     class="btn btn-primary btn-sm"
                     data-perm="admin.menu.asignar"
                     data-perm-mode="disable">
                     <i class="fas fa-save"></i>
                  </button>
               </div>
            </div>


            <div class="card-body">
               <div id="mr-tree" class="tree tree-dark" style="min-height:520px;"></div>
            </div>
         </div>
      </div>
   </div>
</div>