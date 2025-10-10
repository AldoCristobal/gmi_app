<?php
// resources/views/admin/empresas.php
// Asume que tu layout ya inyecta AdminLTE / estilos base y que en routes/web.php
// pasas ['title'=>'Empresas','script'=>'empresas.js'] como en otros módulos.
?>
<div class="content-wrapper">
   <section class="content-header">
      <h1>Empresas <small>Administración</small></h1>
      <ol class="breadcrumb">
         <li><a href="/"><i class="fa fa-dashboard"></i> Home</a></li>
         <li class="active">Empresas</li>
      </ol>
   </section>

   <section class="content">

      <!-- Filtros -->
      <div class="box box-default">
         <div class="box-header with-border">
            <h3 class="box-title">Filtros</h3>
            <div class="box-tools">
               <button id="btnNuevaEmpresa" class="btn btn-primary btn-sm">
                  <i class="fa fa-plus"></i> Nueva empresa
               </button>
            </div>
         </div>
         <div class="box-body">
            <form id="frmFiltros" class="form-inline">
               <div class="form-group">
                  <label for="f_q">Buscar</label>
                  <input type="text" id="f_q" class="form-control input-sm" placeholder="Nombre / RFC / Cliente" style="width:260px;">
               </div>
               <div class="form-group">
                  <label for="f_activo">Estatus</label>
                  <select id="f_activo" class="form-control input-sm">
                     <option value="">Todos</option>
                     <option value="1" selected>Activos</option>
                     <option value="0">Inactivos</option>
                  </select>
               </div>
               <button type="submit" class="btn btn-default btn-sm">
                  <i class="fa fa-search"></i> Aplicar
               </button>
               <button type="button" id="btnLimpiar" class="btn btn-default btn-sm">
                  <i class="fa fa-eraser"></i> Limpiar
               </button>
            </form>
         </div>
      </div>

      <!-- Tabla -->
      <div class="box">
         <div class="box-header with-border">
            <h3 class="box-title">Listado</h3>
         </div>
         <div class="box-body table-responsive">
            <table class="table table-striped table-hover" id="tblEmpresas">
               <thead>
                  <tr>
                     <th>ID</th>
                     <th>Cliente</th>
                     <th>Nombre</th>
                     <th>RFC</th>
                     <th>Tipo</th>
                     <th>Área</th>
                     <th>Responsable</th>
                     <th>Activo</th>
                     <th>Acciones</th>
                  </tr>
               </thead>
               <tbody><!-- rows dinámicas --></tbody>
            </table>
         </div>
         <div class="box-footer clearfix">
            <div class="pull-left" id="lblTotal"></div>
            <ul class="pagination pagination-sm no-margin pull-right" id="paginador"><!-- páginas --></ul>
         </div>
      </div>

   </section>
</div>

<!-- Modal: Crear/Editar -->
<div class="modal fade" id="mdlEmpresa" tabindex="-1" role="dialog" aria-hidden="true">
   <div class="modal-dialog modal-lg">
      <form id="frmEmpresa" class="modal-content">
         <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            <h4 class="modal-title" id="mdlEmpresaTitle">Nueva empresa</h4>
         </div>
         <div class="modal-body">
            <input type="hidden" id="e_id">
            <div class="row">
               <div class="col-sm-4">
                  <div class="form-group">
                     <label>Cliente</label>
                     <input id="e_cliente_grupo" class="form-control" required>
                  </div>
               </div>
               <div class="col-sm-5">
                  <div class="form-group">
                     <label>Nombre empresa</label>
                     <input id="e_nombre" class="form-control" required>
                  </div>
               </div>
               <div class="col-sm-3">
                  <div class="form-group">
                     <label>RFC</label>
                     <input id="e_rfc" class="form-control" maxlength="13" style="text-transform:uppercase" required>
                  </div>
               </div>
            </div>

            <div class="row">
               <div class="col-sm-3">
                  <div class="form-group">
                     <label>Tipo de empresa</label>
                     <select id="e_tipo_persona" class="form-control" required>
                        <option value="FISICA">Física</option>
                        <option value="MORAL">Moral</option>
                     </select>
                  </div>
               </div>
               <div class="col-sm-3">
                  <div class="form-group">
                     <label>Área</label>
                     <input id="e_area_id" type="number" class="form-control" placeholder="ID área" required>
                  </div>
               </div>
               <div class="col-sm-3">
                  <div class="form-group">
                     <label>Responsable (usuario ID)</label>
                     <input id="e_responsable_id" type="number" class="form-control" placeholder="ID usuario" required>
                  </div>
               </div>
               <div class="col-sm-3">
                  <div class="form-group">
                     <label>Activo</label>
                     <select id="e_activo" class="form-control">
                        <option value="1" selected>Sí</option>
                        <option value="0">No</option>
                     </select>
                  </div>
               </div>
            </div>

            <!-- Datos de facturación básicos -->
            <div class="row">
               <div class="col-sm-4">
                  <div class="form-group">
                     <label>Contrato de servicios</label>
                     <input id="e_contrato_servicios" class="form-control">
                  </div>
               </div>
               <div class="col-sm-4">
                  <div class="form-group">
                     <label>Nombre facturación</label>
                     <input id="e_nombre_facturacion" class="form-control">
                  </div>
               </div>
               <div class="col-sm-4">
                  <div class="form-group">
                     <label>Correo facturación</label>
                     <input id="e_correo_facturacion" type="email" class="form-control">
                  </div>
               </div>
            </div>

            <div class="row">
               <div class="col-sm-4">
                  <div class="form-group">
                     <label>Teléfono facturación</label>
                     <input id="e_telefono_facturacion" class="form-control">
                  </div>
               </div>
               <div class="col-sm-4">
                  <div class="form-group">
                     <label>Tipo régimen</label>
                     <input id="e_tipo_regimen" class="form-control" placeholder="(texto libre)">
                  </div>
               </div>
               <div class="col-sm-4">
                  <div class="form-group">
                     <label>Actividad principal</label>
                     <input id="e_actividad_principal" class="form-control">
                  </div>
               </div>
            </div>

            <div class="row">
               <div class="col-sm-4">
                  <div class="form-group">
                     <label>Estatus domicilio</label>
                     <select id="e_estatus_domicilio" class="form-control">
                        <option value="LOCALIZADO" selected>LOCALIZADO</option>
                        <option value="NO_LOCALIZADO">NO_LOCALIZADO</option>
                     </select>
                  </div>
               </div>
            </div>

         </div>
         <div class="modal-footer">
            <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Guardar</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
         </div>
      </form>
   </div>
</div>

<!-- Modal: Expediente / CIF -->
<div class="modal fade" id="mdlExpediente" tabindex="-1" role="dialog" aria-hidden="true">
   <div class="modal-dialog">
      <form id="frmExpediente" class="modal-content" enctype="multipart/form-data">
         <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            <h4 class="modal-title">Expediente de empresa</h4>
         </div>
         <div class="modal-body">
            <input type="hidden" id="x_empresa_id">
            <div class="form-group">
               <label>Tipo de documento</label>
               <select id="x_tipo_clave" class="form-control">
                  <option value="cif">Constancia de Situación Fiscal</option>
                  <option value="acta_constitutiva">Acta Constitutiva</option>
                  <option value="comprobante_domicilio">Comprobante Domicilio</option>
                  <option value="fiel">FIEL</option>
                  <option value="sellos">Sellos</option>
               </select>
            </div>
            <div class="form-group">
               <label>Archivos</label>
               <input type="file" id="x_files" name="files[]" class="form-control" multiple>
            </div>
            <div class="help-block">Para CIF puedes subir un solo PDF/JPG/PNG. Para FIEL/Sellos admite ZIP/PFX/KEY/CER.</div>
         </div>
         <div class="modal-footer">
            <button type="submit" class="btn btn-primary"><i class="fa fa-upload"></i> Subir</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
         </div>
      </form>
   </div>
</div>