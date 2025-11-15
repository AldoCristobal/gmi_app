<!-- resources/views/empresas/index.php -->
<div class="content-header">
   <div class="container-fluid">
      <div class="row mb-2 align-items-center">
         <div class="col-sm-6">
            <h1 class="m-0">Empresas</h1>
         </div>
         <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
               <li class="breadcrumb-item"><a href="#">Inicio</a></li>
               <li class="breadcrumb-item active">Empresas</li>
            </ol>
         </div>
      </div>
   </div>
</div>

<section>
   <div>
      <!-- Toolbar de filtros / búsqueda -->
      <div class="card card-dark">
         <div class="card-body">
            <div class="row g-2 align-items-end">
               <div class="col-md-3">
                  <label class="form-label small mb-1">Buscar</label>
                  <input id="emp-f-q" type="text" class="form-control form-control-sm" placeholder="Cliente, nombre, RFC…">
               </div>
               <div class="col-md-3">
                  <label class="form-label small mb-1">Área</label>
                  <select id="emp-f-area" class="custom-select custom-select-sm"></select>
               </div>
               <div class="col-md-2">
                  <label class="form-label small mb-1">Activo</label>
                  <select id="emp-f-activo" class="custom-select custom-select-sm">
                     <option value="">Todos</option>
                     <option value="1">Sí</option>
                     <option value="0">No</option>
                  </select>
               </div>

               <!-- Solo botón Buscar se queda aquí -->
               <div class="col-md-4 text-end">
                  <button id="emp-btn-search" class="btn btn-outline-secondary btn-sm">
                     <i class="fas fa-search"></i>
                     <span class="d-none d-sm-inline">Buscar</span>
                  </button>
               </div>
            </div>
         </div>
      </div>

      <!-- Grid + mini-toolbar AG Grid -->
      <div class="card card-dark">
         <!-- Mini-toolbar sobre el grid -->
         <div class="card-header py-2">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
               <small class="text-muted mb-1 mb-sm-0">
                  Empresas registradas
               </small>

               <div class="btn-toolbar mb-1 mb-sm-0" role="toolbar" aria-label="Acciones empresas">

                  <!-- Nuevo / Editar -->
                  <button id="emp-btn-new"
                     class="btn btn-outline-success btn-sm me-2"
                     data-perm="empresa.crear empresa.editar"
                     data-perm-any
                     data-perm-mode="disable"
                     title="Nueva empresa / Editar selección">
                     <i class="fas fa-pen-to-square"></i>
                  </button>

                  <!-- Eliminar -->
                  <button id="emp-btn-delete"
                     class="btn btn-outline-danger btn-sm me-2"
                     data-perm="empresa.borrar"
                     data-perm-mode="disable"
                     title="Eliminar empresa seleccionada">
                     <i class="fas fa-trash"></i>
                  </button>

                  <!-- Expediente -->
                  <button id="emp-btn-expediente"
                     class="btn btn-outline-info btn-sm me-2"
                     data-perm="empresa.expediente"
                     data-perm-mode="disable"
                     title="Expediente de la empresa">
                     <i class="fas fa-folder-open"></i>
                  </button>

                  <!-- Obligaciones -->
                  <button id="emp-btn-oblig"
                     class="btn btn-outline-warning btn-sm"
                     data-perm="empresa.obligacion.ver"
                     data-perm-mode="disable"
                     title="Obligaciones de la empresa">
                     <i class="fas fa-list-check"></i>
                  </button>

               </div>
            </div>
         </div>


         <div class="card-body p-0">
            <div id="gridEmpresas" class="ag-theme-alpine-dark" style="height: 62vh; width: 100%;"></div>
         </div>
      </div>
   </div>
</section>

<!-- ================= Modal: Empresa (CRUD) ================= -->
<div id="empresa-modal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true" style="display:none;">
   <div class="modal-dialog modal-xl modal-dialog-scrollable">
      <div class="modal-content bg-dark">
         <div class="modal-header">
            <h5 class="modal-title" id="empresa-modal-title">Empresa</h5>
            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
               <span aria-hidden="true"><i class="fas fa-times"></i></span>
            </button>
         </div>
         <div class="modal-body">
            <input id="emp-id" type="hidden">

            <div class="row g-3">
               <div class="col-md-3">
                  <label class="form-label small">Cliente o Grupo</label>
                  <input id="emp-cliente_grupo" type="text" class="form-control form-control-sm">
               </div>
               <div class="col-md-3">
                  <label class="form-label small">Nombre</label>
                  <input id="emp-nombre" type="text" class="form-control form-control-sm">
               </div>
               <div class="col-md-2">
                  <label class="form-label small">RFC</label>
                  <input id="emp-rfc" type="text" class="form-control form-control-sm text-uppercase">
               </div>
               <div class="col-md-2">
                  <label class="form-label small">Tipo de Persona</label>
                  <select id="emp-tipo_persona" class="custom-select custom-select-sm">
                     <option value="FISICA">Física</option>
                     <option value="MORAL">Moral</option>
                  </select>
               </div>
               <div class="col-md-2">
                  <label class="form-label small">Activo</label>
                  <select id="emp-activo" class="custom-select custom-select-sm">
                     <option value="1">Sí</option>
                     <option value="0">No</option>
                  </select>
               </div>

               <div class="col-md-3">
                  <label class="form-label small">Área</label>
                  <select id="emp-area_id" class="custom-select custom-select-sm"></select>
               </div>
               <div class="col-md-3">
                  <label class="form-label small">Responsable</label>
                  <select id="emp-responsable_id" class="custom-select custom-select-sm"></select>
               </div>

               <!-- Campos extendidos -->
               <div class="col-md-3">
                  <label class="form-label small">Contrato servicios</label>
                  <input id="emp-contrato_servicios" type="text" class="form-control form-control-sm">
               </div>
               <div class="col-md-3">
                  <label class="form-label small">Nombre facturación</label>
                  <input id="emp-nombre_facturacion" type="text" class="form-control form-control-sm">
               </div>
               <div class="col-md-3">
                  <label class="form-label small">Tel. facturación</label>
                  <input id="emp-telefono_facturacion" type="text" class="form-control form-control-sm">
               </div>
               <div class="col-md-3">
                  <label class="form-label small">Correo facturación</label>
                  <input id="emp-correo_facturacion" type="email" class="form-control form-control-sm">
               </div>
               <div class="col-md-3">
                  <label class="form-label small">Tipo régimen</label>
                  <input id="emp-tipo_regimen" type="text" class="form-control form-control-sm">
               </div>
               <div class="col-md-3">
                  <label class="form-label small">Actividad principal</label>
                  <input id="emp-actividad_principal" type="text" class="form-control form-control-sm">
               </div>
               <div class="col-md-3">
                  <label class="form-label small">Estatus domicilio</label>
                  <select id="emp-estatus_domicilio" class="custom-select custom-select-sm">
                     <option value="LOCALIZADO">Localizado</option>
                     <option value="NO_LOCALIZADO">No localizado</option>
                  </select>
               </div>
            </div>
         </div>
         <div class="modal-footer">
            <button id="emp-save" type="button" class="btn btn-primary btn-sm">
               <i class="fas fa-save"></i> Guardar
            </button>
            <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cerrar</button>
         </div>
      </div>
   </div>
</div>

<!-- ============== Modal: Expediente (restaurado) ============== -->
<div id="expediente-modal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true" style="display:none;">
   <div class="modal-dialog modal-xl modal-dialog-scrollable">
      <div class="modal-content bg-dark">
         <div class="modal-header">
            <h5 class="modal-title" id="exp-empresa-title">Expediente</h5>
            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
               <span aria-hidden="true"><i class="fas fa-times"></i></span>
            </button>
         </div>
         <div class="modal-body">
            <input id="exp-up-empresa_id" type="hidden">
            <input id="exp-up-empresa_name" type="hidden">

            <div class="row g-2 mb-3">
               <div class="col-md-5">
                  <label class="form-label small mb-1">Ver últimas versiones por tipo</label>
                  <select id="exp-up-tipo_clave" class="custom-select custom-select-sm">
                     <option value="">Seleccione…</option>
                  </select>
               </div>
               <div class="col-md-7 d-flex align-items-end justify-content-end">
                  <a id="exp-link-full" href="#" class="btn btn-outline-light btn-sm">
                     <i class="fas fa-list"></i> Ver todos
                  </a>
               </div>
            </div>

            <div class="mb-2">
               <label class="form-label small">Seleccionar archivos</label>
               <input id="exp-files-input" type="file" multiple class="form-control form-control-sm">
            </div>

            <div class="table-responsive border rounded mb-3">
               <table class="table table-sm table-dark table-striped align-middle mb-0">
                  <thead>
                     <tr>
                        <th style="width:36px;">#</th>
                        <th>Archivo</th>
                        <th style="width:120px;">Tamaño</th>
                        <th style="width:280px;">Tipo de documento</th>
                        <th style="width:44px;"></th>
                     </tr>
                  </thead>
                  <tbody id="exp-files-tbody"></tbody>
               </table>
            </div>

            <div id="exp-last-list" class="mt-3" style="display:none;">
               <h6 class="mb-2">Últimas versiones</h6>
               <div class="table-responsive border rounded">
                  <table class="table table-sm table-dark table-hover align-middle mb-0">
                     <thead>
                        <tr>
                           <th>Versión</th>
                           <th>Archivo</th>
                           <th>Fecha</th>
                           <th class="text-right">Acciones</th>
                        </tr>
                     </thead>
                     <tbody id="exp-last-tbody"></tbody>
                  </table>
               </div>
            </div>
         </div>
         <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cerrar</button>
            <button type="button" class="btn btn-primary btn-sm" onclick="__expedienteQueueUpload()">
               <i class="fas fa-upload"></i> Subir
            </button>
         </div>
      </div>
   </div>
</div>

<!-- ============== Drawer: Obligaciones (minimal, sin formulario de rutinas) ============== -->
<div id="oblig-drawer"
   class="position-fixed top-0 end-0 bg-dark border-start border-secondary shadow-lg"
   style="width: 720px; max-width: 95vw; transition: transform .25s ease; z-index: 2000;"
   aria-hidden="true">

   <div class="drawer-header d-flex align-items-center justify-content-between px-3 py-2">
      <div class="d-flex flex-column">
         <h5 class="mb-0">Obligaciones</h5>
         <small id="oblig-empresa-label" class="text-muted"></small>
      </div>
      <div>
         <button id="oblig-close" class="btn btn-sm btn-outline-light"><i class="fas fa-times"></i></button>
      </div>
   </div>

   <div class="drawer-body p-3">
      <!-- Barra superior: búsqueda + mini-toolbar -->
      <div class="ag-theme-alpine-dark rounded mb-3 p-2">
         <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">

            <!-- Buscador -->
            <div class="input-group flex-grow-1 mb-2 mb-md-0">
               <span class="input-group-text bg-dark border-0">
                  <i class="fas fa-search text-muted"></i>
               </span>
               <input id="oblig-search"
                  type="text"
                  class="form-control form-control-sm bg-dark text-light"
                  placeholder="Buscar..."
                  style="border: none;">
            </div>

            <!-- Mini toolbar -->
            <div class="btn-toolbar ms-md-2" role="toolbar" aria-label="Acciones árbol">
               <div class="btn-group btn-group-sm me-1 mb-1" role="group" aria-label="Expandir / Contraer">
                  <button id="oblig-expand-all"
                     type="button"
                     class="btn btn-outline-light"
                     title="Expandir todo">
                     <i class="fas fa-plus-square"></i>
                  </button>
                  <button id="oblig-collapse-all"
                     type="button"
                     class="btn btn-outline-light"
                     title="Contraer todo">
                     <i class="fas fa-minus-square"></i>
                  </button>
               </div>

               <div class="btn-group btn-group-sm me-1 mb-1" role="group" aria-label="Marcar / Desmarcar">
                  <button id="oblig-select-all"
                     type="button"
                     class="btn btn-outline-light"
                     title="Marcar todas las obligaciones">
                     <i class="fas fa-check-square"></i>
                  </button>
                  <button id="oblig-deselect-all"
                     type="button"
                     class="btn btn-outline-light"
                     title="Desmarcar todas las obligaciones">
                     <i class="far fa-square"></i>
                  </button>
               </div>

               <div class="btn-group btn-group-sm mb-1" role="group" aria-label="Refrescar / Guardar">
                  <button id="oblig-refresh"
                     type="button"
                     class="btn btn-outline-secondary"
                     title="Actualizar lista">
                     <i class="fas fa-rotate-right"></i>
                  </button>

                  <button id="oblig-btn-sync"
                     type="button"
                     class="btn btn-primary"
                     title="Guardar selección"
                     data-perm="empresa.obligacion.editar empresa.obligacion.asignar empresa.obligacion.borrar"
                     data-perm-any
                     data-perm-mode="disable">
                     <i class="fas fa-save"></i>
                  </button>
               </div>
            </div>
         </div>
      </div>

      <div class="row g-3">
         <div class="col-12">
            <div class="card card-dark mb-3">
               <div class="card-header py-2"><strong>Catálogo</strong></div>
               <div class="card-body p-2">
                  <div id="oblig-tree" class="fancytree-dark" style="min-height: 320px;"></div>
               </div>
            </div>
         </div>
      </div>
   </div>
</div>