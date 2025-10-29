// public/assets/js/revisiones.index.js
(function () {
   // =========================
   // CSRF HELPERS
   // =========================
   function readCookie(name) {
      var cookies = document.cookie ? document.cookie.split('; ') : [];
      for (var i = 0; i < cookies.length; i++) {
         var c = cookies[i];
         var idx = c.indexOf('=');
         var key = idx > -1 ? c.substring(0, idx) : c;
         if (key === name) {
            var val = idx > -1 ? c.substring(idx + 1) : '';
            try { return decodeURIComponent(val); } catch (e) { return val; }
         }
      }
      return '';
   }
   function getMetaCsrf() {
      var meta = document.querySelector('meta[name="csrf-token"]');
      return (meta && meta.content) ? meta.content : '';
   }
   function getCsrf() {
      return new Promise(function (resolve) {
         var meta = getMetaCsrf();
         if (meta) return resolve({ token: meta, source: 'meta' });

         var ls = localStorage.getItem('csrf_token');
         if (ls) return resolve({ token: ls, source: 'localStorage' });

         function trySanctum() {
            try {
               fetch('/sanctum/csrf-cookie', { credentials: 'same-origin' })
                  .then(function (r) {
                     if (!r.ok) return resolve({ token: '', source: 'none' });
                     var cookieToken = readCookie('XSRF-TOKEN');
                     if (cookieToken) return resolve({ token: cookieToken, source: 'cookie' });
                     resolve({ token: '', source: 'none' });
                  })
                  .catch(function () { resolve({ token: '', source: 'none' }); });
            } catch (e) { resolve({ token: '', source: 'none' }); }
         }

         try {
            fetch('/api/csrf', { credentials: 'same-origin' })
               .then(function (r) {
                  if (!r.ok) return trySanctum();
                  r.json().then(function (j) {
                     var token = (j && (j.csrf || j.token || j.data)) || '';
                     if (token) return resolve({ token: token, source: 'api' });
                     trySanctum();
                  }).catch(trySanctum);
               })
               .catch(trySanctum);
         } catch (e) { trySanctum(); }
      });
   }
   function buildCsrfHeaders(csrf) {
      if (!csrf || !csrf.token) return {};
      return { 'X-CSRF-Token': csrf.token };
   }

   // =========================
   // AUTH + HEADERS
   // =========================
   let csrfObj = { token: '' };
   function bearer() { return localStorage.getItem('token') || ''; }
   function authHeaders({ json = false } = {}) {
      const h = { ...buildCsrfHeaders(csrfObj) };
      const tk = bearer();
      if (tk) h['Authorization'] = `Bearer ${tk}`;
      if (json) h['Content-Type'] = 'application/json';
      return h;
   }

   // =========================
   // Permisos helpers (compat)
   // =========================
   async function canPerm(p) {
      if (typeof window.__canPerm === 'function') {
         try { return !!(await window.__canPerm(p)); } catch { return false; }
      }
      const P = window.__userPerms || [];
      return P.includes(p);
   }
   function isHidden(el) { return !el || el.offsetParent === null; }

   // =========================
   // Estado global
   // =========================
   let gridOptions = null;
   let tiposRevision = [];
   let areas = [];
   let responsables = [];
   let pondInst = null;   // FilePond (crear)
   let pondAnexos = null; // FilePond (anexos)

   let btnNueva = null;
   let btnCompletar = null;
   let btnAnexos = null;

   // =========================
   // Init
   // =========================
   document.addEventListener('DOMContentLoaded', async () => {
      csrfObj = await getCsrf();

      // tooltips
      if (window.$ && $.fn.tooltip) {
         $('[data-toggle="tooltip"]').tooltip({ container: 'body' });
      }

      await cargarCatalogos();
      initGrid();
      await loadRevisiones();

      btnNueva = document.getElementById('btn-nueva');
      btnCompletar = document.getElementById('btn-completar');
      btnAnexos = document.getElementById('btn-anexos');

      // Botón inteligente: Nueva/Editar
      if (btnNueva) {
         btnNueva.addEventListener('click', async () => {
            const sel = getSelectedRow();
            if (sel) {
               if (String(sel.estatus).toLowerCase() === 'completa') {
                  return window.notify('La revisión está completa; no se puede editar.', 'info');
               }
               if (!(await canPerm('revisiones.editar'))) {
                  return window.notify('No tienes permiso para editar revisiones.', 'warning');
               }
               openUpsert(sel); // editar
            } else {
               if (!(await canPerm('revisiones.crear'))) {
                  return window.notify('No tienes permiso para crear revisiones.', 'warning');
               }
               openUpsert(); // nueva
            }
         });
      }

      // Botón completar
      if (btnCompletar) {
         btnCompletar.addEventListener('click', marcarComoCompletaSeleccionada);
      }

      // Botón anexos
      if (btnAnexos) {
         btnAnexos.addEventListener('click', openAnexosModal);
      }

      // Filtro buscar
      const btnFiltrar = document.getElementById('btn-filtrar');
      if (btnFiltrar) btnFiltrar.addEventListener('click', loadRevisiones);
      const fQ = document.getElementById('filtro-q');
      if (fQ) fQ.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); loadRevisiones(); } });

      // ESC para deseleccionar
      document.addEventListener('keyup', (e) => {
         if (e.key === 'Escape' && gridOptions?.api) {
            gridOptions.api.deselectAll();
            updateButtonsUI();
         }
      });

      // Aplica gating por permisos
      if (window.applyGates) window.applyGates(document);
      else if (window.__applyGates) window.__applyGates(document);

      updateButtonsUI();
   });

   // =========================
   // Utilidades UI de botones (con permisos)
   // =========================
   async function updateButtonsUI() {
      const sel = getSelectedRow();
      const estatus = sel ? String(sel.estatus).toLowerCase() : null;
      const completa = estatus === 'completa';

      if (!isHidden(btnNueva)) {
         const puedeCrear = await canPerm('revisiones.crear');
         const puedeEditar = await canPerm('revisiones.editar');

         if (sel && !completa && puedeEditar) {
            btnNueva.disabled = false;
            btnNueva.classList.remove('btn-success', 'btn-secondary');
            btnNueva.classList.add('btn-primary');
            btnNueva.innerHTML = '<i class="fas fa-edit"></i><span class="d-none d-md-inline ml-1">Editar</span>';
            btnNueva.setAttribute('title', 'Editar');
         } else if (sel && (!puedeEditar || completa)) {
            btnNueva.classList.remove('btn-success', 'btn-primary');
            btnNueva.classList.add('btn-secondary');
            btnNueva.innerHTML = '<i class="fas fa-lock"></i><span class="d-none d-md-inline ml-1">Completa</span>';
            btnNueva.disabled = true;
            btnNueva.setAttribute('title', 'Edición bloqueada');
         } else {
            btnNueva.classList.remove('btn-primary', 'btn-secondary');
            btnNueva.classList.add('btn-success');
            btnNueva.innerHTML = '<i class="fas fa-plus"></i><span class="d-none d-md-inline ml-1">Nueva</span>';
            btnNueva.disabled = !puedeCrear;
            btnNueva.setAttribute('title', puedeCrear ? 'Nueva' : 'No autorizado para crear');
         }
         if (window.$ && $.fn.tooltip) $(btnNueva).tooltip('dispose').tooltip({ container: 'body' });
      }

      if (!isHidden(btnAnexos)) {
         const puedeAnexos = await canPerm('revisiones.subir_archivo');
         btnAnexos.disabled = !(sel && !completa && puedeAnexos);
         if (window.$ && $.fn.tooltip) $(btnAnexos).tooltip('dispose').tooltip({ container: 'body' });
      }

      if (!isHidden(btnCompletar)) {
         const puedeCompletar = await canPerm('revisiones.cambiar_estatus');
         btnCompletar.disabled = !(sel && !completa && puedeCompletar);
         if (window.$ && $.fn.tooltip) $(btnCompletar).tooltip('dispose').tooltip({ container: 'body' });
      }
   }

   // =========================
   // Grid
   // =========================
   function initGrid() {
      const gridDiv = document.querySelector('#gridRevisiones');
      const localeTextEs = {
         // Textos generales
         page: 'Página',
         more: 'Más',
         to: 'a',
         of: 'de',
         next: 'Siguiente',
         last: 'Última',
         first: 'Primera',
         previous: 'Anterior',
         loadingOoo: 'Cargando...',
         selectAll: '(Seleccionar todo)',
         searchOoo: 'Buscar...',
         blanks: '(vacíos)',
         filterOoo: 'Filtrar...',
         equals: 'Igual a',
         notEqual: 'Distinto de',
         lessThan: 'Menor que',
         greaterThan: 'Mayor que',
         lessThanOrEqual: 'Menor o igual que',
         greaterThanOrEqual: 'Mayor o igual que',
         inRange: 'Entre',
         contains: 'Contiene',
         notContains: 'No contiene',
         startsWith: 'Empieza con',
         endsWith: 'Termina con',
         noRowsToShow: 'No hay registros para mostrar',
         pinColumn: 'Fijar columna',
         autosizeThisColumn: 'Ajustar ancho de esta columna',
         autosizeAllColumns: 'Ajustar ancho de todas las columnas',
         resetColumns: 'Restablecer columnas',
         expandAll: 'Expandir todo',
         collapseAll: 'Contraer todo',
         copy: 'Copiar',
         paste: 'Pegar',
         export: 'Exportar',
         csvExport: 'Exportar CSV',
         excelExport: 'Exportar Excel',
         group: 'Agrupar',
         columns: 'Columnas',
         filters: 'Filtros',
         applyFilter: 'Aplicar filtro',
         clearFilter: 'Limpiar filtro',
         clearAllFilters: 'Limpiar todos los filtros',
      };


      const colDefs = [
         { headerName: 'ID', field: 'id', width: 80 },
         { headerName: 'Nombre', field: 'nombre', flex: 2 },
         { headerName: 'Número Orden', field: 'numero_orden', flex: 1 },
         { headerName: 'Número Oficio', field: 'numero_oficio', flex: 1 },
         { headerName: 'Ejercicio', field: 'ejercicio', width: 110 },
         { headerName: 'Tipo Revisión', field: 'tipo_revision', flex: 1 },
         { headerName: 'Dependencia', field: 'dependencia', flex: 1 },
         { headerName: 'Área', field: 'area_nombre', flex: 1 },
         {
            headerName: 'Responsable', field: 'responsable_nombre', flex: 1,
            tooltipValueGetter: p => {
               const n = p.data?.responsable_nombre || '';
               const e = p.data?.responsable_email || '';
               return e ? `${n} <${e}>` : n;
            }
         },

         {
            headerName: 'Vence en (días)',
            field: 'dias_restantes',
            width: 140,
            valueGetter: params => {
               const v = params.data?.dias_restantes;
               return (v === null || v === undefined) ? null : Number(v);
            },
            // 👉 ahora condicionamos al semáforo del backend (solo en_proceso)
            cellRenderer: params => {
               const d = params.data || {};
               const est = String(d.estatus || '').toLowerCase();

               if (est !== 'en_proceso') {
                  // Mostrar chip clara de que ya no aplica el vencimiento
                  if (est === 'completa') {
                     return `<span class="chip chip-done" title="Revisión completada"><i class="fas fa-check-circle"></i> </span>`;
                  }
                  if (est === 'cancelada') {
                     return `<span class="chip chip-cancel" title="Revisión cancelada"><i class="fas fa-ban"></i> Cancelada</span>`;
                  }
                  // Otros estatus posibles
                  return `<span class="chip chip-neutral" title="Sin alerta">${est || '—'}</span>`;
               }

               // en_proceso → seguimos mostrando los días (si aplica)
               const tag = d.semaforo_tag;   // 'vencida' | 'proxima' | 'ok' | 'sin_alerta'
               const val = (d.dias_restantes === null || d.dias_restantes === undefined) ? null : Number(d.dias_restantes);

               if (tag === 'vencida') return `<span class="due-num due-over">${Math.abs(val)}</span>`;
               if (tag === 'proxima') return `<span class="due-num due-soon">${val}</span>`;
               // ok/sin_alerta → sin color ni número si prefieres (dejo guion fino)
               return '<span class="text-muted">—</span>';
            },

            // 👉 Clases de color solo si está en_proceso (el backend ya lo condiciona con semaforo_color)
            cellClass: params => {
               const tag = params.data?.semaforo_tag;
               if (tag === 'vencida') return 'cell-vencida';
               if (tag === 'proxima') return 'cell-proxima';
               return '';
            },

            // Tooltip claro
            tooltipValueGetter: p => {
               const d = p.data || {};
               const est = String(d.estatus || '').toLowerCase();

               if (est !== 'en_proceso') {
                  if (est === 'completa') return 'Tarea completada';
                  if (est === 'cancelada') return 'Tarea cancelada';
                  return 'Sin alerta';
               }

               const tag = d.semaforo_tag;
               const val = (d.dias_restantes === null || d.dias_restantes === undefined) ? null : Number(d.dias_restantes);
               if (val === null) return 'Sin cálculo';
               if (tag === 'vencida') return `Vencida · ${Math.abs(val)} día(s) de retraso`;
               if (tag === 'proxima') return `Próxima · vence en ${val} día(s)`;
               return `Vence en ${val} día(s)`;
            }
         },

         {
            headerName: 'Estatus',
            field: 'estatus',
            width: 150,
            cellRenderer: params => {
               const v = String(params.value || '').toLowerCase();
               const map = {
                  'en_proceso': { cls: 'status-proceso', icon: 'fas fa-hourglass-half', title: 'En proceso' },
                  'completa': { cls: 'status-completa', icon: 'fas fa-check-circle', title: 'Completa' },
                  'cancelada': { cls: 'status-cancelada', icon: 'fas fa-ban', title: 'Cancelada' },
               };
               const cfg = map[v] || { cls: 'status-proceso', icon: 'fas fa-hourglass-half', title: v || 'Estatus' };
               return `<span class="status-chip ${cfg.cls}" title="${cfg.title}">
                         <i class="${cfg.icon}"></i>
                       </span>`;
            },
            cellClass: 'text-center'
         },
      ];

      gridOptions = {
         columnDefs: colDefs,
         rowData: [],
         rowSelection: 'single',
         suppressRowClickSelection: true, // para toggle manual
         rowDeselection: true,
         pagination: true,
         paginationPageSize: 20,
         localeText: localeTextEs,
         // 👉 Pintar fila según semáforo DEL BACKEND (solo en_proceso)
         rowClassRules: {
            'row-overdue': params => params.data?.semaforo_color === 'red',
            'row-due-soon': params => params.data?.semaforo_color === 'yellow'
         },

         // Toggle seleccionar/deseleccionar con un clic
         onRowClicked: (e) => {
            const api = gridOptions.api;
            const node = e.node;
            if (!api || !node) return;
            if (node.isSelected()) {
               api.deselectAll();
            } else {
               api.deselectAll();
               node.setSelected(true);
            }
            updateButtonsUI();
         },

         onSelectionChanged: () => { updateButtonsUI(); },
      };

      new agGrid.Grid(gridDiv, gridOptions);
   }

   function getSelectedRow() {
      if (!gridOptions || !gridOptions.api) return null;
      const sel = gridOptions.api.getSelectedRows();
      return sel && sel.length ? sel[0] : null;
   }

   async function loadRevisiones() {
      try {
         const url = new URL('/api/v1/revisiones', location.origin);
         const fq = document.querySelector('#filtro-q');
         const ft = document.querySelector('#filtro-tipo');
         const fe = document.querySelector('#filtro-estatus');
         const fr = document.querySelector('#filtro-riesgo');
         if (fq && fq.value.trim()) url.searchParams.set('q', fq.value.trim());
         if (ft && ft.value) url.searchParams.set('tipo_revision_id', ft.value);
         if (fe && fe.value) url.searchParams.set('estatus', fe.value.toLowerCase());
         if (fr && fr.value) url.searchParams.set('riesgo', fr.value.toLowerCase());

         const res = await fetch(url, { headers: authHeaders(), credentials: 'same-origin' });
         const json = await res.json();
         if (!json.ok) throw new Error(json.error?.message || 'Error al cargar');

         gridOptions.api.setRowData(json.data || []);
         gridOptions.api.deselectAll();
         updateButtonsUI();
      } catch (err) {
         console.error(err);
         window.notify('No se pudieron cargar las revisiones', 'error');
      }
   }

   // =========================
   // Catálogos
   // =========================
   async function cargarCatalogos() {
      try {
         // Tipos de revisión
         const resTipos = await fetch('/api/v1/catalogos/revision_tipos', { headers: authHeaders(), credentials: 'same-origin' });
         const jTipos = await resTipos.json();
         if (jTipos.ok) {
            tiposRevision = jTipos.data || [];
            const sel = document.querySelector('#filtro-tipo');
            if (sel) {
               tiposRevision.forEach(t => {
                  const opt = document.createElement('option');
                  opt.value = t.id;
                  opt.textContent = t.nombre;
                  sel.appendChild(opt);
               });
            }
         }
         // Áreas
         const resAreas = await fetch('/api/v1/catalogos/areas', { headers: authHeaders(), credentials: 'same-origin' });
         const jAreas = await resAreas.json();
         areas = jAreas.ok ? (jAreas.data || jAreas) : [];
         // Responsables
         const resResp = await fetch('/api/v1/catalogos/jefes', { headers: authHeaders(), credentials: 'same-origin' });
         const jResp = await resResp.json();
         responsables = jResp.ok ? (jResp.data || jResp) : [];
      } catch (e) {
         console.warn('No se pudieron cargar algunos catálogos', e);
      }
   }

   // =========================
   // Helpers select options
   // =========================
   function renderAreasOptions(sel) {
      sel.innerHTML = '<option value="">Seleccione...</option>';
      (areas || []).forEach(a => {
         const opt = document.createElement('option');
         opt.value = a.id;
         opt.textContent = a.nombre || a.area || (`Área ${a.id}`);
         sel.appendChild(opt);
      });
   }

   function renderResponsablesOptions(sel, areaId) {
      sel.innerHTML = '<option value="">Seleccione...</option>';
      (responsables || [])
         .filter(u => {
            if (!areaId) return true;
            if (typeof u.area_id === 'undefined' || u.area_id === null) return true;
            return String(u.area_id) === String(areaId);
         })
         .forEach(u => {
            const opt = document.createElement('option');
            opt.value = u.id;
            opt.textContent = u.nombre || u.fullname || u.email || (`Usuario ${u.id}`);
            sel.appendChild(opt);
         });
   }

   // =========================
   // Modal Upsert (crear / editar)
   // =========================
   function openUpsert(row) {
      if (row && String(row.estatus).toLowerCase() === 'completa') {
         return window.notify('La revisión está completa; no se puede editar.', 'info');
      }

      const modal = document.getElementById('modal-revision');
      const body = modal.querySelector('#modal-revision-body');

      if (row) {
         // === EDITAR ===
         body.innerHTML = generarFormularioEdicion(row);
         modal.querySelector('#modal-revision-titulo').textContent = 'Editar revisión';

         const selArea = body.querySelector('select[name="area_id"]');
         const selResp = body.querySelector('select[name="responsable_id"]');
         renderAreasOptions(selArea);
         selArea.value = row.area_id;
         renderResponsablesOptions(selResp, parseInt(row.area_id, 10));
         selResp.value = row.responsable_id;

         selArea.addEventListener('change', () => {
            renderResponsablesOptions(selResp, parseInt(selArea.value || '0', 10));
         });

         const bsModal = new bootstrap.Modal(modal);
         bsModal.show();

         body.querySelector('form').addEventListener('submit', async e => {
            e.preventDefault();
            await actualizarRevision(bsModal, row.id);
         });
      } else {
         // === CREAR ===
         body.innerHTML = generarFormularioNueva();
         modal.querySelector('#modal-revision-titulo').textContent = 'Registrar revisión';

         initFilePond(); // solo crear

         const selArea = body.querySelector('select[name="area_id"]');
         const selResp = body.querySelector('select[name="responsable_id"]');
         renderAreasOptions(selArea);
         renderResponsablesOptions(selResp, parseInt(selArea.value || '0', 10));
         selArea.addEventListener('change', () => {
            renderResponsablesOptions(selResp, parseInt(selArea.value || '0', 10));
         });

         const bsModal = new bootstrap.Modal(modal);
         bsModal.show();

         body.querySelector('form').addEventListener('submit', async e => {
            e.preventDefault();
            await guardarRevision(bsModal);
         });
      }
   }

   function generarFormularioNueva() {
      const tipos = (tiposRevision || []).map(t => `<option value="${t.id}">${t.nombre}</option>`).join('');
      return `
      <form id="form-revision">
        <div class="row g-2">
          <div class="col-md-6">
            <label class="form-label">Nombre</label>
            <input type="text" name="nombre" class="form-control form-control-sm" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Número de Orden</label>
            <input type="text" name="numero_orden" class="form-control form-control-sm" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Número de Oficio</label>
            <input type="text" name="numero_oficio" class="form-control form-control-sm">
          </div>

          <div class="col-md-3">
            <label class="form-label">Ejercicio</label>
            <input type="number" name="ejercicio" class="form-control form-control-sm" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Fecha Notificación</label>
            <input type="date" name="fecha_notificacion" class="form-control form-control-sm" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Fecha Vencimiento</label>
            <input type="date" name="fecha_vencimiento" class="form-control form-control-sm" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Tipo de Revisión</label>
            <select name="tipo_revision_id" class="custom-select custom-select-sm" required>
              <option value="">Seleccione...</option>
              ${tipos}
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label">Antecedente</label>
            <input type="text" name="antecedente" class="form-control form-control-sm">
          </div>
          <div class="col-md-3">
            <label class="form-label">Tipo de Impuesto</label>
            <input type="text" name="tipo_impuesto" class="form-control form-control-sm">
          </div>
          <div class="col-md-3">
            <label class="form-label">Dependencia</label>
            <input type="text" name="dependencia" class="form-control form-control-sm">
          </div>

          <div class="col-md-3">
            <label class="form-label">Riesgo</label>
            <select name="riesgo" class="custom-select custom-select-sm">
              <option value="bajo">Bajo</option>
              <option value="medio" selected>Medio</option>
              <option value="alto">Alto</option>
            </select>
          </div>
          <div class="col-md-9">
            <label class="form-label">Observaciones</label>
            <textarea name="observaciones" class="form-control form-control-sm" rows="2"></textarea>
          </div>

          <div class="col-md-3">
            <label class="form-label">Área</label>
            <select name="area_id" class="custom-select custom-select-sm" required id="sel-area"></select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Asesor / Responsable</label>
            <select name="responsable_id" class="custom-select custom-select-sm" required id="sel-responsable"></select>
          </div>

          <div class="col-md-12">
            <label class="form-label">Evidencia inicial (obligatoria)</label>
            <input type="file" id="file-evidencia" name="file" class="filepond" required />
          </div>

          <div class="col-12 text-end mt-3">
            <button class="btn btn-success btn-sm" type="submit">
              <i class="fas fa-save"></i> Guardar
            </button>
          </div>
        </div>
      </form>
    `;
   }

   function generarFormularioEdicion(row) {
      const tipos = (tiposRevision || []).map(t => {
         const sel = String(t.id) === String(row.tipo_revision_id) ? 'selected' : '';
         return `<option value="${t.id}" ${sel}>${t.nombre}</option>`;
      }).join('');
      return `
      <form id="form-revision-edit">
        <input type="hidden" name="id" value="${row.id}">
        <div class="row g-2">
          <div class="col-md-6">
            <label class="form-label">Nombre</label>
            <input type="text" name="nombre" class="form-control form-control-sm" required value="${escapeAttr(row.nombre)}">
          </div>
          <div class="col-md-3">
            <label class="form-label">Número de Orden</label>
            <input type="text" name="numero_orden" class="form-control form-control-sm" required value="${escapeAttr(row.numero_orden || '')}">
          </div>
          <div class="col-md-3">
            <label class="form-label">Número de Oficio</label>
            <input type="text" name="numero_oficio" class="form-control form-control-sm" value="${escapeAttr(row.numero_oficio || '')}">
          </div>

          <div class="col-md-3">
            <label class="form-label">Ejercicio</label>
            <input type="number" name="ejercicio" class="form-control form-control-sm" required value="${escapeAttr(row.ejercicio || '')}">
          </div>
          <div class="col-md-3">
            <label class="form-label">Fecha Notificación</label>
            <input type="date" name="fecha_notificacion" class="form-control form-control-sm" required value="${(row.fecha_notificacion || '').substring(0, 10)}">
          </div>
          <div class="col-md-3">
            <label class="form-label">Fecha Vencimiento</label>
            <input type="date" name="fecha_vencimiento" class="form-control form-control-sm" required value="${(row.fecha_vencimiento || '').substring(0, 10)}">
          </div>
          <div class="col-md-3">
            <label class="form-label">Tipo de Revisión</label>
            <select name="tipo_revision_id" class="custom-select custom-select-sm" required>
              <option value="">Seleccione...</option>
              ${tipos}
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label">Antecedente</label>
            <input type="text" name="antecedente" class="form-control form-control-sm" value="${escapeAttr(row.antecedente || '')}">
          </div>
          <div class="col-md-3">
            <label class="form-label">Tipo de Impuesto</label>
            <input type="text" name="tipo_impuesto" class="form-control form-control-sm" value="${escapeAttr(row.tipo_impuesto || '')}">
          </div>
          <div class="col-md-3">
            <label class="form-label">Dependencia</label>
            <input type="text" name="dependencia" class="form-control form-control-sm" value="${escapeAttr(row.dependencia || '')}">
          </div>

          <div class="col-md-3">
            <label class="form-label">Riesgo</label>
            <select name="riesgo" class="custom-select custom-select-sm">
              ${['bajo', 'medio', 'alto'].map(v => `<option value="${v}" ${String(row.riesgo).toLowerCase() === v ? 'selected' : ''}>${capitalize(v)}</option>`).join('')}
            </select>
          </div>
          <div class="col-md-9">
            <label class="form-label">Observaciones</label>
            <textarea name="observaciones" class="form-control form-control-sm" rows="2">${escapeHtml(row.observaciones || '')}</textarea>
          </div>

          <div class="col-md-3">
            <label class="form-label">Área</label>
            <select name="area_id" class="custom-select custom-select-sm" required id="sel-area-edit"></select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Asesor / Responsable</label>
            <select name="responsable_id" class="custom-select custom-select-sm" required id="sel-responsable-edit"></select>
          </div>

          <div class="col-12 text-end mt-3">
            <button class="btn btn-primary btn-sm" type="submit">
              <i class="fas fa-save"></i> Guardar cambios
            </button>
          </div>
        </div>
      </form>
    `;
   }

   function escapeAttr(v) { return String(v || '').replace(/"/g, '&quot;'); }
   function escapeHtml(v) {
      return String(v || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
   }
   function capitalize(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1) : s; }

   // =========================
   // FilePond (crear)
   // =========================
   function initFilePond() {
      FilePond.registerPlugin(FilePondPluginFileValidateType, FilePondPluginFileValidateSize);
      const el = document.querySelector('#file-evidencia');
      el.setAttribute('name', 'file');

      pondInst = FilePond.create(el, {
         instantUpload: true,
         allowMultiple: false,
         maxFileSize: '20MB',
         required: true,
         server: {
            url: '/api/v1/uploads/temp',
            process: {
               headers: { ...authHeaders() },
               withCredentials: true,
               ondata: (formData) => {
                  if (csrfObj?.token) formData.append('_csrf', csrfObj.token);
                  return formData;
               },
               onload: (res) => {
                  try {
                     const j = JSON.parse(res);
                     return j?.data?.token || '';
                  } catch { return res; }
               }
            }
         }
      });

      pondInst.on('processfile', (_err, file) => {
         if (file && file.serverId) window.notify('Evidencia subida', 'success');
      });
   }

   // =========================
   // Guardar (crear)
   // =========================
   async function guardarRevision(modal) {
      const form = document.querySelector('#form-revision');
      const data = Object.fromEntries(new FormData(form).entries());

      if (!pondInst) {
         window.notify('Inicializando cargador de archivos… intenta de nuevo.', 'warning');
         return;
      }
      const files = pondInst.getFiles();
      if (!files.length) {
         window.notify('Debe subir un archivo de evidencia antes de guardar.', 'warning');
         return;
      }
      const needsProcess = files.some(f => f.status !== 5);
      if (needsProcess) {
         try { await pondInst.processFiles(); } catch (e) {
            window.notify('No se pudo procesar la evidencia. Reintenta.', 'error');
            return;
         }
      }
      const file0 = pondInst.getFiles()[0];
      const tmpToken = file0 && file0.serverId ? file0.serverId : null;
      if (!tmpToken) {
         window.notify('No recibimos token del archivo de evidencia.', 'error');
         return;
      }

      const payload = {
         ...data,
         tipo_revision_id: parseInt(data.tipo_revision_id || data.tipo_revision || 0, 10) || undefined,
         area_id: parseInt(data.area_id || '0', 10) || undefined,
         responsable_id: parseInt(data.responsable_id || '0', 10) || undefined,
         riesgo: (data.riesgo || 'medio').toLowerCase(),
         evidencia_inicial_token: tmpToken
      };

      try {
         const res = await fetch('/api/v1/revisiones', {
            method: 'POST',
            headers: authHeaders({ json: true }),
            credentials: 'same-origin',
            body: JSON.stringify(payload)
         });
         const json = await res.json();
         if (!json.ok) throw new Error(json.error?.message || 'Error al guardar');

         window.notify('Revisión registrada', 'success');
         modal.hide();
         pondInst.removeFiles();
         await loadRevisiones();
      } catch (err) {
         console.error(err);
         window.notify(err.message || 'No se pudo registrar la revisión', 'error');
      }
   }

   // =========================
   // Actualizar (editar)
   // =========================
   async function actualizarRevision(modal, id) {
      const form = document.querySelector('#form-revision-edit');
      const data = Object.fromEntries(new FormData(form).entries());

      const payload = {
         id: parseInt(id, 10),
         nombre: data.nombre,
         numero_orden: data.numero_orden || null,
         numero_oficio: data.numero_oficio || null,
         ejercicio: data.ejercicio ? parseInt(data.ejercicio, 10) : null,
         fecha_notificacion: data.fecha_notificacion || null,
         fecha_vencimiento: data.fecha_vencimiento || null,
         tipo_revision_id: data.tipo_revision_id ? parseInt(data.tipo_revision_id, 10) : null,
         antecedente: data.antecedente || null,
         tipo_impuesto: data.tipo_impuesto || null,
         dependencia: data.dependencia || null,
         riesgo: (data.riesgo || 'medio').toLowerCase(),
         observaciones: data.observaciones || null,
         area_id: data.area_id ? parseInt(data.area_id, 10) : null,
         responsable_id: data.responsable_id ? parseInt(data.responsable_id, 10) : null
      };

      try {
         const res = await fetch('/api/v1/revisiones', {
            method: 'PUT',
            headers: authHeaders({ json: true }),
            credentials: 'same-origin',
            body: JSON.stringify(payload)
         });
         const json = await res.json();
         if (!json.ok) throw new Error(json.error?.message || 'No se pudo actualizar');

         window.notify('Revisión actualizada', 'success');
         modal.hide();
         await loadRevisiones();
      } catch (err) {
         console.error(err);
         window.notify(err.message || 'Error al actualizar la revisión', 'error');
      }
   }

   // =========================
   // Anexos (subida directa al endpoint de documentos)
   // =========================
   function openAnexosModal() {
      const sel = getSelectedRow();
      if (!sel) return window.notify('Selecciona una revisión primero.', 'warning');
      if (String(sel.estatus).toLowerCase() === 'completa') {
         return window.notify('La revisión está completa; no se pueden subir evidencias.', 'info');
      }

      const modal = document.getElementById('modal-anexos');
      const body = document.getElementById('modal-anexos-body');

      body.innerHTML = `
      <form id="form-anexos">
        <div class="mb-2">
          <label class="form-label">Selecciona uno o varios archivos</label>
          <input type="file" id="file-anexos" name="file" multiple class="filepond" />
        </div>
        <div class="text-end">
          <button type="submit" class="btn btn-info btn-sm">
            <i class="fas fa-upload"></i> Subir
          </button>
        </div>
      </form>
    `;

      FilePond.registerPlugin(FilePondPluginFileValidateType, FilePondPluginFileValidateSize);
      const el = body.querySelector('#file-anexos');
      el.setAttribute('name', 'file');

      pondAnexos = FilePond.create(el, {
         instantUpload: false, // sube al presionar "Subir"
         allowMultiple: true,
         maxFileSize: '20MB',
         server: {
            url: '/api/v1/revisiones/documentos',
            process: {
               method: 'POST',
               headers: { ...authHeaders() },
               withCredentials: true,
               ondata: (formData) => {
                  const r = getSelectedRow();
                  formData.append('revision_id', r?.id || '');
                  formData.append('is_inicial', '0'); // anexo
                  return formData;
               },
               onload: (res) => {
                  try {
                     const j = JSON.parse(res);
                     if (!j.ok) throw new Error(j.error?.message || 'Error al subir');
                     return j?.data?.doc_id || 'ok';
                  } catch {
                     return res;
                  }
               }
            }
         }
      });

      const bs = new bootstrap.Modal(modal);
      bs.show();

      body.querySelector('#form-anexos').addEventListener('submit', async (e) => {
         e.preventDefault();
         await guardarAnexos(bs);
      });
   }

   async function guardarAnexos(bsModal) {
      if (!pondAnexos) return window.notify('Inicializando cargador…', 'warning');
      const files = pondAnexos.getFiles();
      if (!files.length) return window.notify('Selecciona al menos un archivo.', 'warning');

      try {
         await pondAnexos.processFiles(); // envía 1 request por archivo con field 'file'
         window.notify('Evidencias añadidas', 'success');
         bsModal.hide();
         pondAnexos.removeFiles();
         await loadRevisiones();
      } catch (e) {
         console.error(e);
         window.notify('No se pudieron subir las evidencias', 'error');
      }
   }

   // =========================
   // Completar (PATCH estatus)
   // =========================
   async function marcarComoCompletaSeleccionada() {
      const row = getSelectedRow();
      if (!row) return window.notify('Selecciona una revisión primero.', 'warning');
      if (String(row.estatus).toLowerCase() === 'completa') {
         return window.notify('Esta revisión ya está completa.', 'info');
      }
      if (!(await canPerm('revisiones.cambiar_estatus'))) {
         return window.notify('No tienes permiso para cambiar estatus.', 'warning');
      }
      if (!confirm(`¿Marcar como COMPLETA la revisión "${row.nombre}"?`)) return;

      try {
         const res = await fetch('/api/v1/revisiones/estatus', {
            method: 'PATCH',
            headers: authHeaders({ json: true }),
            credentials: 'same-origin',
            body: JSON.stringify({ id: row.id, estatus: 'completa' })
         });
         const json = await res.json();
         if (!json.ok) throw new Error(json.error?.message || 'No se pudo actualizar el estatus');

         window.notify('Revisión marcada como COMPLETA', 'success');
         await loadRevisiones();
      } catch (e) {
         console.error(e);
         window.notify(e.message || 'Error al cambiar estatus', 'error');
      }
   }

})();
