<!-- resources/views/empresas/rutinas.php -->
<section class="content" id="rutinas-page">
   <div class="container-fluid">

      <div class="row mb-3">
         <div class="col-md-12 d-flex align-items-center flex-wrap">
            <h4 class="mb-2 mr-3">
               <i class="fas fa-sync-alt mr-1"></i> Rutinas / Obligaciones por empresa
            </h4>
            <div class="form-inline mb-2">
               <label class="mr-2 mb-0">Empresa:</label>
               <select id="rt-empresa" class="form-control form-control-sm" style="min-width: 260px;">
                  <option value="">Seleccione una empresa...</option>
                  <!-- Opciones se cargan vía JS -->
               </select>
            </div>
         </div>
      </div>

      <div class="row" id="rt-empty-state">
         <div class="col-12">
            <div class="alert alert-info mb-0">
               Selecciona una empresa para configurar sus rutinas de obligaciones.
            </div>
         </div>
      </div>

      <div class="row d-none" id="rt-content">
         <div class="col-md-4">
            <div class="card card-dark">
               <div class="card-header">
                  <h3 class="card-title">
                     <i class="fas fa-stream mr-1"></i> Obligaciones asignadas
                  </h3>
               </div>
               <div class="card-body p-2 rt-tree-wrapper" style="max-height: 480px; overflow-y: auto;">
                  <div id="rt-obligaciones-tree"></div>
               </div>
            </div>
         </div>

         <div class="col-md-8">
            <div class="card card-dark" id="rt-form-card">
               <div class="card-header">
                  <h3 class="card-title" id="rt-form-title">
                     Detalle de rutina
                  </h3>
               </div>
               <div class="card-body">
                  <div id="rt-no-obligacion" class="text-muted">
                     Selecciona una obligación del panel izquierdo para ver su configuración.
                  </div>

                  <form id="rt-form" class="d-none">
                     <input type="hidden" id="rt-empresa-id">
                     <input type="hidden" id="rt-obligacion-id">

                     <div class="form-row">
                        <div class="form-group col-md-6">
                           <label>Obligación</label>
                           <input type="text" id="rt-obligacion-desc" class="form-control form-control-sm" readonly>
                        </div>
                        <div class="form-group col-md-3">
                           <label>Clave</label>
                           <input type="text" id="rt-obligacion-clave" class="form-control form-control-sm" readonly>
                        </div>
                        <div class="form-group col-md-3">
                           <label>Periodicidad</label>
                           <select id="rt-periodicidad" class="form-control form-control-sm">
                              <option value="MENSUAL">Mensual</option>
                              <option value="BIMESTRAL">Bimestral</option>
                              <option value="TRIMESTRAL">Trimestral</option>
                              <option value="SEMESTRAL">Semestral</option>
                              <option value="ANUAL">Anual</option>
                              <option value="EVENTUAL">Eventual</option>
                           </select>
                        </div>
                     </div>

                     <hr class="my-2">

                     <div class="form-row">
                        <div class="form-group col-md-3">
                           <label>Día objetivo</label>
                           <input type="number" min="1" max="31" id="rt-dia-venc" class="form-control form-control-sm">
                           <small class="form-text text-muted">
                              Día objetivo del periodo.
                           </small>
                        </div>
                        <div class="form-group col-md-3">
                           <label>Días anticipación</label>
                           <input type="number" min="0" id="rt-dias-anticipacion" class="form-control form-control-sm">
                           <small class="form-text text-muted">
                              Antes del vencimiento.
                           </small>
                        </div>
                        <div class="form-group col-md-3">
                           <label>Offset fijo (días)</label>
                           <input type="number" id="rt-offset-dias" class="form-control form-control-sm">
                           <small class="form-text text-muted">
                              Ajuste adicional.
                           </small>
                        </div>
                        <div class="form-group col-md-3">
                           <label>Enviar correo</label>
                           <div class="custom-control custom-switch mt-1">
                              <input type="checkbox" class="custom-control-input" id="rt-enviar-correo">
                              <label class="custom-control-label" for="rt-enviar-correo">
                                 Sí
                              </label>
                           </div>
                        </div>
                     </div>

                     <div class="form-row">
                        <div class="form-group col-md-6">
                           <label>Responsable</label>
                           <select id="rt-responsable-id" class="form-control form-control-sm">
                              <!-- opciones por JS -->
                           </select>
                           <small class="form-text text-muted">
                              Por defecto, el responsable de la empresa.
                           </small>
                        </div>
                        <div class="form-group col-md-3">
                           <label>Vigente desde</label>
                           <input type="date" id="rt-fecha-inicio" class="form-control form-control-sm">
                        </div>
                        <div class="form-group col-md-3">
                           <label>Vigente hasta</label>
                           <input type="date" id="rt-fecha-fin" class="form-control form-control-sm">
                        </div>
                     </div>

                     <div class="form-group">
                        <label>Notas</label>
                        <textarea id="rt-notas" rows="3" class="form-control form-control-sm"></textarea>
                     </div>

                     <div class="text-right">
                        <button type="button" id="rt-btn-guardar" class="btn btn-sm btn-primary">
                           <i class="fas fa-save mr-1"></i> Guardar rutina
                        </button>
                     </div>
                  </form>
               </div>
            </div>
         </div>
      </div>

   </div>
</section>