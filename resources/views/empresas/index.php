<!-- resources/views/empresas/index.php (o .html) -->
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

<section class="">
   <div class="">

      <!-- Toolbar de filtros / acciones -->
      <div class="card card-dark">
         <div class="card-body">
            <div class="row g-2 align-items-end">
               <div class="col-md-3">
                  <label class="form-label small mb-1">Buscar</label>
                  <input id="emp-f-q" type="text" class="form-control form-control-sm" placeholder="Cliente, nombre, RFC…">
               </div>
               <div class="col-md-3">
                  <label class="form-label small mb-1">Área</label>
                  <select id="emp-f-area" class="form-select form-select-sm"></select>
               </div>
               <div class="col-md-2">
                  <label class="form-label small mb-1">Activo</label>
                  <select id="emp-f-activo" class="form-select form-select-sm">
                     <option value="">Todos</option>
                     <option value="1">Sí</option>
                     <option value="0">No</option>
                  </select>
               </div>

               <div class="col-md-4 text-end">
                  <!-- Botonera / Toolbar -->
                  <button id="emp-btn-search" class="btn btn-outline-secondary btn-sm me-2">
                     <i class="fas fa-search"></i> Buscar
                  </button>

                  <!-- Nuevo / Editar (botón combinado) -->
                  <button id="emp-btn-new"
                     class="btn btn-success btn-sm me-2"
                     data-perm="empresa.crear empresa.editar"
                     data-perm-any
                     data-perm-mode="disable">
                     <i class="fas fa-plus"></i> Nuevo
                  </button>

                  <button id="emp-btn-delete"
                     class="btn btn-danger btn-sm me-2"
                     data-perm="empresa.borrar"
                     data-perm-mode="disable">
                     <i class="fas fa-trash"></i> Eliminar
                  </button>

                  <button id="emp-btn-expediente"
                     class="btn btn-outline-info btn-sm me-2"
                     data-perm="empresa.expediente"
                     data-perm-mode="disable">
                     <i class="fas fa-folder-open"></i> Expediente
                  </button>
                  <button id="emp-btn-oblig" class="btn btn-outline-warning btn-sm me-2"
                     data-perm="empresa.obligacion.ver"
                     data-perm-mode="disable"
                     title="Ver y asignar obligaciones a la empresa seleccionada">
                     <i class="fas fa-list-check"></i> Obligaciones
                  </button>
               </div>
            </div>
         </div>
      </div>

      <!-- Grid -->
      <div class="card card-dark">
         <div class="card-body p-0">
            <!-- Tema dark compatible con v29 -->
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
                  <select id="emp-tipo_persona" class="form-select form-select-sm">
                     <option value="FISICA">Física</option>
                     <option value="MORAL">Moral</option>
                  </select>
               </div>
               <div class="col-md-2">
                  <label class="form-label small">Activo</label>
                  <select id="emp-activo" class="form-select form-select-sm">
                     <option value="1">Sí</option>
                     <option value="0">No</option>
                  </select>
               </div>

               <div class="col-md-3">
                  <label class="form-label small">Área</label>
                  <select id="emp-area_id" class="form-select form-select-sm"></select>
               </div>
               <div class="col-md-3">
                  <label class="form-label small">Responsable</label>
                  <select id="emp-responsable_id" class="form-select form-select-sm"></select>
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
                  <select id="emp-estatus_domicilio" class="form-select form-select-sm">
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

<!-- ============== Modal: Expediente (tabla previa + versiones) ============== -->
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

            <!-- Hidden para JS -->
            <input id="exp-up-empresa_id" type="hidden">
            <input id="exp-up-empresa_name" type="hidden">

            <!-- Buscador de versiones por tipo (global, no se usa para subir) -->
            <div class="row g-2 mb-3">
               <div class="col-md-5">
                  <label class="form-label small mb-1">Ver últimas versiones por tipo</label>
                  <select id="exp-up-tipo_clave" class="form-select form-select-sm">
                     <option value="">Seleccione…</option>
                  </select>
               </div>
               <div class="col-md-7 d-flex align-items-end justify-content-end">
                  <a id="exp-link-full" href="#" class="btn btn-outline-light btn-sm">
                     <i class="fas fa-list"></i> Ver todos
                  </a>
               </div>
            </div>

            <!-- Input múltiple + Tabla previa (cola) -->
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

            <!-- Últimas versiones -->
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

<!-- ============== Drawer: Obligaciones (slide-over) ============== -->
<div id="oblig-drawer" class="position-fixed top-0 end-0 h-100 bg-dark border-start border-secondary shadow-lg"
   style="width: 720px; max-width: 95vw; transform: translateX(100%); transition: transform .25s ease; z-index: 1060;"
   aria-hidden="true">
   <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom border-secondary">
      <div class="d-flex flex-column">
         <h5 class="mb-0">Obligaciones</h5>
         <small id="oblig-empresa-label" class="text-muted"></small>
      </div>
      <div>
         <button id="oblig-close" class="btn btn-sm btn-outline-light"><i class="fas fa-times"></i></button>
      </div>
   </div>

   <div class="p-3 overflow-auto h-100" style="padding-bottom: 6rem !important;">
      <!-- Barra tipo AG Grid -->
      <div class="ag-theme-alpine-dark rounded mb-3 p-2">
         <div class="d-flex flex-wrap gap-2 align-items-end">
            <div class="me-2">
               <label class="form-label small mb-1">Buscar</label>
               <input id="oblig-search" type="text" class="form-control form-control-sm" placeholder="Buscar obligación (clave, descripción)">
            </div>
            <div class="me-2">
               <label class="form-label small mb-1">Catálogo</label><br>
               <span class="badge bg-secondary" id="oblig-count-cat">0</span>
            </div>
            <div class="me-2">
               <label class="form-label small mb-1">Asignadas</label><br>
               <span class="badge bg-info" id="oblig-count-asg">0</span>
            </div>
            <div class="ms-auto">
               <button id="oblig-refresh" class="btn btn-outline-light btn-sm" title="Refrescar asignadas">
                  <i class="fas fa-rotate"></i>
               </button>
            </div>
         </div>
      </div>

      <div class="row g-3">
         <!-- Columna izquierda: Árbol + Asignadas -->
         <div class="col-lg-6">
            <div class="card card-dark mb-3">
               <div class="card-header py-2"><strong>Catálogo</strong></div>
               <div class="card-body p-2">
                  <div id="oblig-tree" class="fancytree-dark" style="min-height: 260px;"></div>
               </div>
            </div>

            <div class="card card-dark">
               <div class="card-header py-2"><strong>Asignadas a la empresa</strong></div>
               <div class="card-body p-0">
                  <div class="table-responsive">
                     <table class="table table-sm table-dark table-striped align-middle mb-0">
                        <thead>
                           <tr>
                              <th>Obligación</th>
                              <th style="width: 140px;">Periodicidad</th>
                              <th style="width: 44px;"></th>
                           </tr>
                        </thead>
                        <tbody id="oblig-asignadas-tbody"></tbody>
                     </table>
                  </div>
               </div>
            </div>
         </div>

         <!-- Columna derecha: Formulario detalle -->
         <div class="col-lg-6">
            <div class="card card-dark">
               <div class="card-header py-2">
                  <strong id="oblig-form-title">Detalle de asignación</strong>
               </div>
               <div class="card-body">
                  <input type="hidden" id="oblig-form-id"> <!-- id empresa_obligacion cuando existe -->
                  <input type="hidden" id="oblig-form-empresa_id">
                  <input type="hidden" id="oblig-form-obligacion_id">

                  <div class="mb-2">
                     <label class="form-label small mb-1">Obligación</label>
                     <div id="oblig-form-obligacion-resumen" class="small text-muted">—</div>
                  </div>

                  <div class="row g-2">
                     <div class="col-6">
                        <label class="form-label small mb-1">Periodicidad</label>
                        <select id="oblig-form-periodicidad" class="form-select form-select-sm">
                           <option value="MENSUAL">MENSUAL</option>
                           <option value="BIMESTRAL">BIMESTRAL</option>
                           <option value="TRIMESTRAL">TRIMESTRAL</option>
                           <option value="SEMESTRAL">SEMESTRAL</option>
                           <option value="ANUAL">ANUAL</option>
                           <option value="EVENTUAL">EVENTUAL</option>
                        </select>
                     </div>
                     <div class="col-6">
                        <label class="form-label small mb-1">Tipo de días</label>
                        <select id="oblig-form-tipo_dias" class="form-select form-select-sm">
                           <option value="NATURALES">NATURALES</option>
                           <option value="HABILES">HÁBILES</option>
                           <option value="INHABILES">INHÁBILES</option>
                        </select>
                     </div>
                     <div class="col-4">
                        <label class="form-label small mb-1">Día venc.</label>
                        <input id="oblig-form-dia_venc" type="number" min="1" max="31" class="form-control form-control-sm" placeholder="1-31">
                     </div>
                     <div class="col-4">
                        <label class="form-label small mb-1">Offset (± días)</label>
                        <input id="oblig-form-offset" type="number" class="form-control form-control-sm" value="0">
                     </div>
                     <div class="col-4">
                        <label class="form-label small mb-1">Activo</label>
                        <select id="oblig-form-activo" class="form-select form-select-sm">
                           <option value="1">Sí</option>
                           <option value="0">No</option>
                        </select>
                     </div>

                     <div class="col-6">
                        <label class="form-label small mb-1">Fecha inicio</label>
                        <input id="oblig-form-inicio" type="date" class="form-control form-control-sm">
                     </div>
                     <div class="col-6">
                        <label class="form-label small mb-1">Fecha fin</label>
                        <input id="oblig-form-fin" type="date" class="form-control form-control-sm">
                     </div>

                     <div class="col-6">
                        <label class="form-label small mb-1">Responsable</label>
                        <select id="oblig-form-responsable" class="form-select form-select-sm">
                           <!-- se llena desde catálogo de jefes/usuarios -->
                        </select>
                     </div>
                     <div class="col-6">
                        <label class="form-label small mb-1">Área</label>
                        <select id="oblig-form-area" class="form-select form-select-sm">
                           <!-- se llena desde catálogo de áreas -->
                        </select>
                     </div>
                     <div class="col-12">
                        <label class="form-label small mb-1">Notas</label>
                        <textarea id="oblig-form-notas" class="form-control form-control-sm" rows="2" placeholder="Opcional"></textarea>
                     </div>
                  </div>
               </div>
               <div class="card-footer d-flex gap-2">
                  <button id="oblig-btn-asignar" class="btn btn-success btn-sm"
                     data-perm="empresa.obligacion.asignar" data-perm-mode="disable">
                     <i class="fas fa-plus"></i> Asignar
                  </button>
                  <button id="oblig-btn-guardar" class="btn btn-primary btn-sm"
                     data-perm="empresa.obligacion.editar" data-perm-mode="disable">
                     <i class="fas fa-save"></i> Guardar cambios
                  </button>
                  <button id="oblig-btn-quitar" class="btn btn-danger btn-sm ms-auto"
                     data-perm="empresa.obligacion.borrar" data-perm-mode="disable">
                     <i class="fas fa-trash"></i> Desasignar
                  </button>
               </div>
            </div>
         </div>
      </div>

   </div>
</div>