// public/assets/js/empresas.index.js
(function () {
   const USE_DETAIL_FALLBACK = true;

   // ===== Notificaciones (Notyf) =====
   let _notyf = null;
   try {
      _notyf = new Notyf({
         duration: 3000,
         ripple: true,
         dismissible: true,
         position: { x: 'right', y: 'top' },
         types: [
            { type: 'info', background: '#3B82F6', icon: false },
            { type: 'warning', background: '#F59E0B', icon: false },
            { type: 'success', background: '#10B981', icon: false },
            { type: 'error', background: '#EF4444', icon: false }
         ]
      });
   } catch (e) {
      console.warn('Notyf no encontrado; se usarán logs de consola.');
   }

   function toast(type, message) {
      if (_notyf) _notyf.open({ type, message });
      else (type === 'error' ? console.error : console.log)(message);
   }
   window.__emp_toast = toast;

   function canPerm(p) {
      return (typeof window !== 'undefined' && typeof window.__canPerm === 'function')
         ? window.__canPerm(p)
         : false;
   }

   function handleApiError(err, fallbackMsg) {
      if (!fallbackMsg) fallbackMsg = 'Error al procesar la solicitud';
      console.error(err);
      const status = (err && (err.status || err.code)) || null;

      if (status === 401) toast('warning', 'Sesión expirada o no autenticado (401).');
      else if (status === 403 || status === 'FORBIDDEN') {
         const missing = (err && err.payload && err.payload.error && err.payload.error.message) ? err.payload.error.message : '';
         toast('error', 'No cuentas con permisos (403). ' + missing);
      } else if (status === 404) toast('warning', 'Recurso no encontrado (404).');
      else if (status === 409) toast('warning', 'Conflicto de datos (409).');
      else if (status === 422) toast('warning', 'Datos inválidos o incompletos (422).');
      else {
         const extra = (err && err.message) ? (': ' + err.message) : '';
         toast('error', fallbackMsg + extra);
      }
   }

   // --- CSRF helpers ---
   function readCookie(name) {
      const cookies = document.cookie ? document.cookie.split('; ') : [];
      for (let i = 0; i < cookies.length; i++) {
         const c = cookies[i];
         const idx = c.indexOf('=');
         const key = idx > -1 ? c.substring(0, idx) : c;
         if (key === name) {
            const val = idx > -1 ? c.substring(idx + 1) : '';
            try { return decodeURIComponent(val); } catch (e) { return val; }
         }
      }
      return '';
   }

   function getMetaCsrf() {
      const meta = document.querySelector('meta[name="csrf-token"]');
      return (meta && meta.content) ? meta.content : '';
   }

   function getCsrf() {
      return new Promise((resolve) => {
         const meta = getMetaCsrf();
         if (meta) return resolve({ token: meta, source: 'meta' });

         const ls = localStorage.getItem('csrf_token');
         if (ls) return resolve({ token: ls, source: 'localStorage' });

         function trySanctum() {
            try {
               fetch('/sanctum/csrf-cookie', { credentials: 'same-origin' })
                  .then((r) => {
                     if (!r.ok) return resolve({ token: '', source: 'none' });
                     const cookieToken = readCookie('XSRF-TOKEN');
                     if (cookieToken) return resolve({ token: cookieToken, source: 'cookie' });
                     resolve({ token: '', source: 'none' });
                  })
                  .catch(() => resolve({ token: '', source: 'none' }));
            } catch (e) { resolve({ token: '', source: 'none' }); }
         }

         try {
            fetch('/api/csrf', { credentials: 'same-origin' })
               .then((r) => {
                  if (!r.ok) return trySanctum();
                  r.json().then((j) => {
                     const token = (j && (j.csrf || j.token || j.data)) || '';
                     if (token) return resolve({ token, source: 'api' });
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

   // ===================== API (alineado a admin) =====================
   const API = {
      // CRUD Empresas
      index: (params) => `/api/v1/empresas?${params.toString()}`,
      show: (id) => `/api/v1/empresas/show?id=${encodeURIComponent(id)}`,
      store: '/api/v1/empresas',
      update: '/api/v1/empresas',
      destroy: (id) => `/api/v1/empresas?id=${encodeURIComponent(id)}`,

      // Catálogos
      catalogAreas: '/api/v1/catalogos/areas',
      catalogJefes: '/api/v1/catalogos/jefes',
      catalogDocTipos: '/api/v1/catalogos/empresa_documento_tipos',

      // Expediente
      expedienteUpload: '/api/v1/empresas/expediente',
      expedienteVersions: '/api/v1/empresas/expediente/versions',
      expedienteDownload: '/api/v1/empresas/expediente/download',

      // CIF
      cifUpload: '/api/v1/empresas/cif/upload',
      cifParse: '/api/v1/empresas/cif/parse',
      cifApply: '/api/v1/empresas/cif/apply',

      // Obligaciones
      obligCatalogo: '/api/v1/catalogos/obligaciones',
      obligEmpresa: (empresaId) => `/api/v1/empresas/obligaciones?empresa_id=${encodeURIComponent(empresaId)}`,
      obligSave: '/api/v1/empresas/obligaciones'
   };

   // ===================== INIT PRINCIPAL (Empresas) =====================
   function init() {
      if (typeof Api === 'undefined') { console.error('Api.js no cargado'); return; }
      if (typeof agGrid === 'undefined') { console.error('AG Grid no cargado'); return; }
      if (!document.querySelector('#gridEmpresas')) return;

      // Helpers DOM
      const $ = (sel, ctx) => (ctx || document).querySelector(sel);
      function setEnabled(el, enabled) {
         if (!el) return;
         el.disabled = !enabled;
         el.classList.toggle('disabled', !enabled);
         if (enabled) el.removeAttribute('aria-disabled');
         else el.setAttribute('aria-disabled', 'true');
      }

      // ---- Controles / Contenedores ----
      const gridEl = $('#gridEmpresas');
      const fQ = $('#emp-f-q');
      const fArea = $('#emp-f-area');
      const fActivo = $('#emp-f-activo');

      const btnSearch = $('#emp-btn-search');
      const btnNew = $('#emp-btn-new');
      const btnEdit = $('#emp-btn-edit'); // por si llega a existir
      const btnDelete = $('#emp-btn-delete');
      const btnExp = $('#emp-btn-expediente');
      const btnObl = $('#emp-btn-oblig');

      if (!gridEl) { console.error('Falta #gridEmpresas'); return; }

      // ---- Modal refs ----
      const modalEl = $('#empresa-modal');
      const modalTitle = $('#empresa-modal-title');
      const btnSave = $('#emp-save');

      const fId = $('#emp-id');
      const fCli = $('#emp-cliente_grupo');
      const fNom = $('#emp-nombre');
      const fRfc = $('#emp-rfc');
      const fTipo = $('#emp-tipo_persona');
      const fAreaId = $('#emp-area_id');
      const fRespId = $('#emp-responsable_id');
      const fActv = $('#emp-activo');

      const fCont = $('#emp-contrato_servicios');
      const fNFac = $('#emp-nombre_facturacion');
      const fTFact = $('#emp-telefono_facturacion');
      const fCFact = $('#emp-correo_facturacion');
      const fReg = $('#emp-tipo_regimen');
      const fAct = $('#emp-actividad_principal');
      const fEdoDom = $('#emp-estatus_domicilio');

      // Modal Expediente refs
      const modalExpEl = document.getElementById('expediente-modal');
      const expEmpresaId = document.getElementById('exp-up-empresa_id');
      const expEmpresaNm = document.getElementById('exp-up-empresa_name');
      const expEmpresaTitle = document.getElementById('exp-empresa-title');
      const expTipoSel = document.getElementById('exp-up-tipo_clave');
      const expFilesInput = document.getElementById('exp-files-input');
      const expFilesTbody = document.getElementById('exp-files-tbody');
      const expLastWrap = document.getElementById('exp-last-list');
      const expLastBody = document.getElementById('exp-last-tbody');

      let _tipos = []; // catálogo tipos documento
      const _tipoByClave = new Map();

      // ---- Catálogos ----
      let _areas = [], _jefes = [];

      function fillSelect(selectEl, items, valueField, labelField, opts) {
         opts = opts || {};
         if (!selectEl) return;
         selectEl.innerHTML = '';
         if (opts.includeEmpty) {
            const opt0 = document.createElement('option');
            opt0.value = '';
            opt0.textContent = opts.emptyText || 'Seleccione…';
            selectEl.appendChild(opt0);
         }
         const list = items || [];
         for (let i = 0; i < list.length; i++) {
            const it = list[i];
            const opt = document.createElement('option');
            opt.value = String((it && it[valueField]) != null ? it[valueField] : '');
            opt.textContent = String((it && it[labelField]) != null ? it[labelField] : '');
            selectEl.appendChild(opt);
         }
      }
      window.__emp_fillSelect = fillSelect;

      function loadCatalogs() {
         return new Promise((resolve) => {
            try { if (window.AppLoader && AppLoader.show) AppLoader.show('Cargando catálogos…'); } catch (e) { }
            Promise.all([
               Api.get(API.catalogAreas),
               Api.get(API.catalogJefes)
            ]).then((arr) => {
               const jAreas = arr[0] || {};
               const jJefes = arr[1] || {};
               _areas = jAreas.data || [];
               _jefes = jJefes.data || [];

               window.__emp_areas = _areas;
               window.__emp_jefes = _jefes;

               fillSelect(fArea, _areas, 'id', 'nombre', { includeEmpty: true, emptyText: 'Todas' });
               fillSelect(fAreaId, _areas, 'id', 'nombre', { includeEmpty: true, emptyText: 'Seleccione…' });
               fillSelect(fRespId, _jefes, 'id', 'nombre', { includeEmpty: true, emptyText: 'Seleccione…' });
               resolve();
            }).catch((err) => {
               handleApiError(err, 'No se pudieron cargar catálogos');
               resolve();
            }).finally(() => {
               try { if (window.AppLoader && AppLoader.hide) AppLoader.hide(); } catch (e) { }
            });
         });
      }

      // === Expediente (documento tipos) ===
      function loadTiposDocumento() {
         return Api.get(API.catalogDocTipos).then((res) => {
            _tipos = (res && res.data) || [];
            _tipoByClave.clear();
            if (expTipoSel) expTipoSel.innerHTML = '<option value="">Seleccione…</option>';
            for (let i = 0; i < _tipos.length; i++) {
               const t = _tipos[i];
               const opt = document.createElement('option');
               opt.value = t.clave;
               opt.textContent = t.nombre;
               opt.dataset.id = t.id;
               opt.dataset.maxMb = (t.max_mb != null ? t.max_mb : '');

               let exts = [];
               try {
                  if (Array.isArray(t.acepta_ext)) exts = t.acepta_ext;
                  else if (typeof t.acepta_ext === 'string' && t.acepta_ext.trim().charAt(0) === '[') exts = JSON.parse(t.acepta_ext);
                  else if (typeof t.acepta_ext === 'string') {
                     exts = t.acepta_ext.split(',').map((s) => s.trim());
                  }
               } catch (e) { exts = []; }

               opt.dataset.accept = exts.join(',');
               if (expTipoSel) expTipoSel.appendChild(opt);

               _tipoByClave.set(t.clave, {
                  id: t.id, clave: t.clave, nombre: t.nombre, max_mb: t.max_mb, acepta_ext: exts
               });
            }
         }).catch((err) => {
            handleApiError(err, 'No se pudo cargar tipos de documento');
         });
      }

      // ======= Expediente: cola de archivos =======
      const fileQueue = new Map();
      let _uidSeq = 1;
      function genUid() { return 'f_' + Date.now() + '_' + (_uidSeq++); }
      function bytesToSize(n) {
         if (n === undefined || n === null) return '';
         const kb = 1024, mb = kb * 1024;
         if (n >= mb) return (n / mb).toFixed(2) + ' MB';
         if (n >= kb) return (n / kb).toFixed(2) + ' KB';
         return n + ' B';
      }
      function isExtAllowedForType(ext, tipoClave) {
         const t = _tipoByClave.get(tipoClave);
         if (!t) return true;
         const list = (t.acepta_ext || []).map((s) => String(s).replace(/^\./, '').toLowerCase());
         return list.length ? list.indexOf(ext.toLowerCase()) !== -1 : true;
      }

      function renderQueue() {
         if (!expFilesTbody) return;
         expFilesTbody.innerHTML = '';
         let idx = 1;
         fileQueue.forEach((rec, uid) => {
            const f = rec.file;
            const ext = (f.name.split('.').pop() || '').toUpperCase();
            const tr = document.createElement('tr');
            tr.setAttribute('data-uid', uid);
            tr.innerHTML =
               '<td class="text-muted">' + (idx++) + '</td>' +
               '<td><span class="badge bg-secondary me-2">' + (ext || 'FILE') + '</span><span class="text-break">' + f.name + '</span></td>' +
               '<td>' + bytesToSize(f.size) + '</td>' +
               '<td><select class="form-select form-select-sm sel-tipo">' +
               (function () {
                  const parts = ['<option value="">— Selecciona tipo —</option>'];
                  for (let i = 0; i < _tipos.length; i++) {
                     const t = _tipos[i];
                     parts.push('<option value="' + t.clave + '">' + t.nombre + ' (' + t.clave + ')</option>');
                  }
                  return parts.join('');
               })() +
               '</select></td>' +
               '<td class="text-end"><button class="btn btn-sm btn-outline-danger btn-del" title="Quitar"><i class="fas fa-times"></i></button></td>';
            expFilesTbody.appendChild(tr);
            const sel = tr.querySelector('.sel-tipo');
            if (sel) {
               sel.value = rec.tipo_clave || '';
               sel.addEventListener('change', () => { rec.tipo_clave = sel.value || ''; });
            }
            const btnDel = tr.querySelector('.btn-del');
            if (btnDel) {
               btnDel.addEventListener('click', () => { fileQueue.delete(uid); renderQueue(); });
            }
         });
      }

      if (expFilesInput) {
         expFilesInput.addEventListener('change', (e) => {
            const files = Array.prototype.slice.call(e.target.files || []);
            if (!files.length) return;
            for (let i = 0; i < files.length; i++) {
               const f = files[i];
               const uid = genUid();
               fileQueue.set(uid, { file: f, tipo_clave: '' });
            }
            expFilesInput.value = '';
            renderQueue();
         });
      }

      window.__expedienteQueueUpload = function () {
         return new Promise((resolve) => {
            const empresaId = parseInt((expEmpresaId && expEmpresaId.value) ? expEmpresaId.value : '0', 10);
            if (!empresaId) { toast('warning', 'Selecciona una empresa'); return resolve(false); }
            const okExp = canPerm('empresa.expediente');
            if (!okExp) { toast('error', 'No tienes permiso para expediente'); return resolve(false); }

            const missingTipo = [];
            fileQueue.forEach((rec) => { if (!rec.tipo_clave) missingTipo.push(rec.file.name); });
            if (missingTipo.length) {
               const msgList = missingTipo.slice(0, 3).join(', ') + (missingTipo.length > 3 ? '…' : '');
               toast('warning', 'Selecciona tipo para: ' + msgList);
               return resolve(false);
            }

            let invalid = null;
            fileQueue.forEach((rec) => {
               if (invalid) return;
               const ext = (rec.file.name.split('.').pop() || '').toLowerCase();
               if (!isExtAllowedForType(ext, rec.tipo_clave)) {
                  const t = _tipoByClave.get(rec.tipo_clave);
                  const lista = t && t.acepta_ext ? t.acepta_ext.join(', ') : '';
                  invalid = ' .' + ext + ' no permitido para tipo ' + rec.tipo_clave + '. Permitidas: ' + lista;
               }
            });
            if (invalid) { toast('error', invalid); return resolve(false); }

            getCsrf().then((csrf) => {
               const headers = buildCsrfHeaders(csrf);
               try { if (window.AppLoader && AppLoader.show) AppLoader.show('Subiendo archivos…'); } catch (e) { }
               let ok = 0, fail = 0;
               (function uploadNext(iter) {
                  const it = iter || fileQueue.entries();
                  const step = it.next ? it.next() : null;
                  if (!step || step.done) {
                     try { if (window.AppLoader && AppLoader.hide) AppLoader.hide(); } catch (e) { }
                     if (ok && !fail) toast('success', 'Subida completa (' + ok + ')');
                     else if (ok && fail) toast('warning', 'Subidos ' + ok + ', fallaron ' + fail);
                     else toast('error', 'No se subió ningún archivo');
                     renderQueue();
                     return resolve(ok && !fail);
                  }
                  const uid = step.value[0];
                  const rec = step.value[1];
                  const fd = new FormData();
                  fd.append('empresa_id', String(empresaId));
                  fd.append('tipo_clave', rec.tipo_clave);
                  fd.append('file', rec.file, rec.file.name);
                  fetch(API.expedienteUpload, {
                     method: 'POST',
                     credentials: 'same-origin',
                     headers,
                     body: fd
                  }).then((resp) => {
                     if (!resp.ok) {
                        (resp.json ? resp.json() : Promise.resolve({})).then((j) => {
                           const msg = (j && j.error && j.error.message) ? j.error.message : 'Error al subir archivo';
                           toast('error', rec.file.name + ': ' + msg);
                           fail++;
                           uploadNext(it);
                        }).catch(() => {
                           toast('error', rec.file.name + ': Error al subir archivo');
                           fail++; uploadNext(it);
                        });
                        return;
                     }
                     ok++;
                     fileQueue.delete(uid);
                     if (expFilesTbody) {
                        const tr = expFilesTbody.querySelector('tr[data-uid="' + uid + '"]');
                        if (tr && tr.parentNode) tr.parentNode.removeChild(tr);
                     }
                     const selClave = (expTipoSel && expTipoSel.value) ? expTipoSel.value : '';
                     if (selClave && selClave === rec.tipo_clave) {
                        const opt = (expTipoSel && expTipoSel.selectedOptions && expTipoSel.selectedOptions[0]) ? expTipoSel.selectedOptions[0] : null;
                        const tipoId = opt ? parseInt(opt.dataset.id || '0', 10) : 0;
                        if (empresaId && tipoId) {
                           loadLatestVersions(empresaId, tipoId, 5, false).then(() => { uploadNext(it); });
                           return;
                        }
                     }
                     uploadNext(it);
                  }).catch(() => {
                     toast('error', rec.file.name + ': Error de red');
                     fail++; uploadNext(it);
                  });
               })(fileQueue.entries());
            });
         });
      };

      function loadLatestVersions(empresaId, tipoId, limit, showAll) {
         if (limit == null) limit = 5;
         if (showAll == null) showAll = false;
         return new Promise((resolve) => {
            try { if (window.AppLoader && AppLoader.show) AppLoader.show('Cargando versiones…'); } catch (e) { }
            const params = new URLSearchParams({ empresa_id: String(empresaId), tipo_id: String(tipoId) });
            if (!showAll && limit) params.set('limit', String(limit));
            Api.get(API.expedienteVersions + '?' + params.toString())
               .then((j) => {
                  const rows = (j && j.data) ? j.data : [];
                  renderLastList(rows);
                  resolve();
               })
               .catch((err) => { handleApiError(err, 'No se pudieron cargar versiones'); resolve(); })
               .finally(() => { try { if (window.AppLoader && AppLoader.hide) AppLoader.hide(); } catch (e) { } });
         });
      }

      function renderLastList(rows) {
         if (!expLastWrap || !expLastBody) return;
         expLastBody.innerHTML = '';
         if (!rows || rows.length === 0) {
            expLastWrap.style.display = 'none';
            return;
         }
         expLastWrap.style.display = '';
         for (let i = 0; i < rows.length; i++) {
            const r = rows[i];
            const tr = document.createElement('tr');
            const fecha = r.creado_en || r.subido_en || '';
            tr.innerHTML =
               '<td>' + (r.version != null ? r.version : '') + '</td>' +
               '<td>' + (r.archivo_nombre != null ? r.archivo_nombre : '') + '</td>' +
               '<td><small>' + fecha + '</small></td>' +
               '<td class="text-right">' +
               '  <button class="btn btn-xs btn-outline-primary" data-act="exp-dl" data-id="' + r.id + '"><i class="fas fa-download"></i></button> ' +
               '  <a class="btn btn-xs btn-outline-secondary" href="' + ((r.archivo_path || '#')) + '" target="_blank" rel="noopener" title="Abrir"><i class="fas fa-external-link-alt"></i></a>' +
               '</td>';
            expLastBody.appendChild(tr);
         }
      }

      if (expLastBody) {
         expLastBody.addEventListener('click', (e) => {
            const btn = e.target && e.target.closest ? e.target.closest('button[data-act="exp-dl"]') : null;
            if (!btn) return;
            const docId = parseInt(btn.getAttribute('data-id') || '0', 10);
            const empresaId = parseInt((expEmpresaId && expEmpresaId.value) ? expEmpresaId.value : '0', 10);
            if (!empresaId || !docId) return;
            window.open(API.expedienteDownload + '?empresa_id=' + empresaId + '&doc_id=' + docId + '&stream=1', '_blank');
         });
      }

      if (expTipoSel) {
         expTipoSel.addEventListener('change', () => {
            const empresaId = parseInt((expEmpresaId && expEmpresaId.value) ? expEmpresaId.value : '0', 10);
            const opt = (expTipoSel.selectedOptions && expTipoSel.selectedOptions[0]) ? expTipoSel.selectedOptions[0] : null;
            const tipoId = opt ? parseInt(opt.dataset.id || '0', 10) : 0;
            if (empresaId && tipoId) loadLatestVersions(empresaId, tipoId, 5, false);
         });
      }

      // ---- AG Grid ----
      const columnDefs = [
         { headerName: '#', valueGetter: 'node.rowIndex + 1', width: 70 },
         { headerName: 'Cliente', field: 'cliente_grupo', flex: 1 },
         { headerName: 'Nombre', field: 'nombre', flex: 1.2, minWidth: 180 },
         { headerName: 'RFC', field: 'rfc', width: 160, cellRenderer: (p) => '<code>' + (p.value || '') + '</code>' },
         { headerName: 'Tipo', field: 'tipo_persona', width: 110 },
         { headerName: 'Área', field: 'area_id', width: 100 },
         { headerName: 'Responsable', field: 'responsable_id', width: 120 },
         { headerName: 'Activo', field: 'activo', width: 100, valueFormatter: (p) => p.value ? 'Sí' : 'No' }
      ];

      const gridOptions = {
         columnDefs,
         rowData: [],
         animateRows: true,
         rowHeight: 42,
         pagination: true,
         paginationPageSize: 20,
         rowSelection: 'single',
         suppressRowClickSelection: true,
         onRowClicked: function (e) {
            const wasSelected = e.node.isSelected();
            e.node.setSelected(!wasSelected, true);
            updateActionButtons();
         },
         onSelectionChanged: function () { updateActionButtons(); }
      };

      const gridApiOrInstance = (typeof agGrid.Grid === 'function')
         ? new agGrid.Grid(gridEl, gridOptions)
         : (agGrid.createGrid ? agGrid.createGrid(gridEl, gridOptions) : null);

      function getSelectedRow() {
         const api = gridOptions.api || (gridApiOrInstance && gridApiOrInstance.api) || gridApiOrInstance;
         const sel = (api && typeof api.getSelectedRows === 'function') ? api.getSelectedRows() : null;
         return (sel && sel[0]) ? sel[0] : null;
      }
      window.__emp_getSelected = getSelectedRow;
      window.__emp_canPerm = canPerm;
      Object.defineProperty(window, '__emp_catalogs', {
         get: function () { return { areas: _areas, jefes: _jefes }; }
      });

      // ---- Carga de datos (index) ----
      function loadData() {
         return new Promise((resolve) => {
            try { if (window.AppLoader && AppLoader.show) AppLoader.show('Cargando empresas…'); } catch (e) { }
            const params = new URLSearchParams({ page: '1', size: '200' });
            const q = (fQ && fQ.value ? fQ.value : '').trim();
            const activo = (fActivo && (fActivo.value !== undefined && fActivo.value !== null)) ? fActivo.value : '';
            const areaId = (fArea && fArea.value) ? fArea.value : '';
            if (q) params.set('q', q);
            if (activo !== '') params.set('activo', activo);
            if (areaId) params.set('area_id', areaId);

            Api.get(API.index(params)).then((j) => {
               const rows = j.data || [];
               const api = gridOptions.api || (gridApiOrInstance && gridApiOrInstance.api) || gridApiOrInstance;
               if (api && typeof api.setRowData === 'function') api.setRowData(rows);
               if (api && typeof api.deselectAll === 'function') api.deselectAll();
               updateActionButtons();
               toast('info', 'Empresas cargadas: ' + rows.length);
               resolve();
            }).catch((err) => {
               handleApiError(err, 'No se pudo cargar empresas');
               resolve();
            }).finally(() => {
               try { if (window.AppLoader && AppLoader.hide) AppLoader.hide(); } catch (e) { }
            });
         });
      }

      // ---- Modal Empresa (CRUD) ----
      const hasBsModal = (window.jQuery && jQuery.fn && typeof jQuery.fn.modal === 'function');
      let modalIsOpen = false;

      function resetForm() {
         if (fId) fId.value = '';
         if (fCli) fCli.value = '';
         if (fNom) fNom.value = '';
         if (fRfc) fRfc.value = '';
         if (fTipo) fTipo.value = 'FISICA';
         if (fAreaId) fAreaId.value = '';
         if (fRespId) fRespId.value = '';
         if (fActv) fActv.value = '1';
         if (fCont) fCont.value = '';
         if (fNFac) fNFac.value = '';
         if (fTFact) fTFact.value = '';
         if (fCFact) fCFact.value = '';
         if (fReg) fReg.value = '';
         if (fAct) fAct.value = '';
         if (fEdoDom) fEdoDom.value = 'LOCALIZADO';
      }

      function showModal() {
         if (!modalEl) return;

         if (hasBsModal) {
            jQuery(modalEl).modal({
               backdrop: true,
               keyboard: true,
               show: true
            });
            modalIsOpen = true;
            return;
         }

         modalEl.classList.add('show');
         modalEl.style.display = 'block';
         modalEl.removeAttribute('aria-hidden');
         modalEl.setAttribute('aria-modal', 'true');

         let bd = document.querySelector('.modal-backdrop');
         if (!bd) {
            bd = document.createElement('div');
            bd.className = 'modal-backdrop fade show';
            bd.addEventListener('click', () => { hideModal(); });
            document.body.appendChild(bd);
         }
         document.body.classList.add('modal-open');
         modalIsOpen = true;
      }

      function hideModal() {
         if (!modalEl) return;

         if (hasBsModal) {
            jQuery(modalEl).modal('hide');
            modalIsOpen = false;
            return;
         }

         modalEl.classList.remove('show');
         modalEl.style.display = 'none';
         modalEl.setAttribute('aria-hidden', 'true');
         modalEl.removeAttribute('aria-modal');

         const bds = document.querySelectorAll('.modal-backdrop');
         bds.forEach((bd) => {
            if (bd && bd.parentNode) bd.parentNode.removeChild(bd);
         });

         document.body.classList.remove('modal-open');
         modalIsOpen = false;
      }

      // Cierre con botones (X, Cerrar)
      if (modalEl) {
         const closeButtons = modalEl.querySelectorAll('[data-dismiss="modal"], [data-bs-dismiss="modal"], .close');
         closeButtons.forEach((btn) => {
            btn.addEventListener('click', (e) => {
               e.preventDefault();
               hideModal();
            });
         });
      }

      // Cierre con ESC (Empresa)
      document.addEventListener('keydown', (e) => {
         if (e.key === 'Escape' && modalIsOpen) hideModal();
      });

      // ------ Crear / Editar ------
      function openCreate() {
         return new Promise((resolve) => {
            const ok = canPerm('empresa.crear');
            if (!ok) { toast('error', 'No tienes permiso para crear'); return resolve(false); }
            resetForm();
            if (modalTitle) modalTitle.textContent = 'Nueva empresa';
            showModal();
            resolve(true);
         });
      }

      function openEdit(row) {
         return new Promise((resolve) => {
            if (!row) return resolve(false);
            const ok = canPerm('empresa.editar');
            if (!ok) { toast('error', 'No tienes permiso para editar'); return resolve(false); }
            resetForm();
            if (modalTitle) modalTitle.textContent = 'Editar empresa #' + row.id;
            let data = row;

            function fillAndShow() {
               if (fId) fId.value = (data.id != null ? data.id : '');
               if (fCli) fCli.value = data.cliente_grupo != null ? data.cliente_grupo : '';
               if (fNom) fNom.value = data.nombre != null ? data.nombre : '';
               if (fRfc) fRfc.value = (data.rfc != null ? String(data.rfc).toUpperCase() : '');
               if (fTipo) fTipo.value = data.tipo_persona != null ? data.tipo_persona : 'FISICA';
               if (fAreaId) fAreaId.value = (data.area_id != null ? String(data.area_id) : '');
               if (fRespId) fRespId.value = (data.responsable_id != null ? String(data.responsable_id) : '');
               if (fActv) fActv.value = data.activo ? '1' : '0';
               if (fCont) fCont.value = data.contrato_servicios != null ? data.contrato_servicios : '';
               if (fNFac) fNFac.value = data.nombre_facturacion != null ? data.nombre_facturacion : '';
               if (fCFact) fCFact.value = data.correo_facturacion != null ? data.correo_facturacion : '';
               if (fTFact) fTFact.value = data.telefono_facturacion != null ? data.telefono_facturacion : '';
               if (fReg) fReg.value = data.tipo_regimen != null ? data.tipo_regimen : '';
               if (fAct) fAct.value = data.actividad_principal != null ? data.actividad_principal : '';
               if (fEdoDom) fEdoDom.value = data.estatus_domicilio != null ? data.estatus_domicilio : 'LOCALIZADO';
               showModal(); resolve(true);
            }

            if (USE_DETAIL_FALLBACK) {
               try { if (window.AppLoader && AppLoader.show) AppLoader.show('Cargando detalle…'); } catch (e) { }
               Api.get(API.show(row.id))
                  .then((det) => { if (det && det.ok && det.data) data = det.data; fillAndShow(); })
                  .catch((err) => { handleApiError(err, 'No se pudo cargar el detalle de la empresa'); fillAndShow(); })
                  .finally(() => { try { if (window.AppLoader && AppLoader.hide) AppLoader.hide(); } catch (e) { } });
            } else {
               fillAndShow();
            }
         });
      }

      // Guardar empresa (store/update)
      if (btnSave) {
         btnSave.addEventListener('click', () => {
            const payload = {
               id: (fId && fId.value) ? parseInt(fId.value, 10) : undefined,
               cliente_grupo: (fCli && fCli.value) ? String(fCli.value).trim() : '',
               nombre: (fNom && fNom.value) ? String(fNom.value).trim() : '',
               rfc: (fRfc && fRfc.value) ? String(fRfc.value).trim().toUpperCase() : '',
               tipo_persona: (fTipo && fTipo.value) ? fTipo.value : 'FISICA',
               area_id: (fAreaId && fAreaId.value) ? parseInt(fAreaId.value, 10) : null,
               responsable_id: (fRespId && fRespId.value) ? parseInt(fRespId.value, 10) : null,
               activo: (fActv && fActv.value) ? parseInt(fActv.value, 10) : 1,
               contrato_servicios: (fCont && fCont.value) ? String(fCont.value).trim() : '',
               nombre_facturacion: (fNFac && fNFac.value) ? String(fNFac.value).trim() : '',
               telefono_facturacion: (fTFact && fTFact.value) ? String(fTFact.value).trim() : '',
               correo_facturacion: (fCFact && fCFact.value) ? String(fCFact.value).trim() : '',
               tipo_regimen: (fReg && fReg.value) ? String(fReg.value).trim() : '',
               actividad_principal: (fAct && fAct.value) ? String(fAct.value).trim() : '',
               estatus_domicilio: (fEdoDom && fEdoDom.value) ? fEdoDom.value : 'LOCALIZADO'
            };
            if (!payload.nombre || !payload.rfc) { toast('warning', 'Nombre y RFC son requeridos'); return; }

            try { if (window.AppLoader && AppLoader.show) AppLoader.show('Guardando…'); } catch (e) { }

            // store
            if (!payload.id) {
               const okCreate = canPerm('empresa.crear');
               if (!okCreate) { toast('error', 'No tienes permiso para crear'); try { AppLoader.hide(); } catch (e) { } return; }
               Api.post(API.store, payload).then((j) => {
                  if (j.ok) { toast('success', 'Empresa creada'); hideModal(); loadData(); }
                  else toast('warning', (j.error && j.error.message) || 'No se pudo crear la empresa');
               }).catch((err) => {
                  handleApiError(err, 'Error al guardar empresa');
               }).finally(() => { try { if (window.AppLoader && AppLoader.hide) AppLoader.hide(); } catch (e) { } });
            } else {
               // update
               const okEdit = canPerm('empresa.editar');
               if (!okEdit) { toast('error', 'No tienes permiso para editar'); try { AppLoader.hide(); } catch (e) { } return; }
               Api.put(API.update, payload).then((j) => {
                  if (j.ok) { toast('success', 'Empresa actualizada'); hideModal(); loadData(); }
                  else toast('warning', (j.error && j.error.message) || 'No se pudo actualizar la empresa');
               }).catch((err) => {
                  handleApiError(err, 'Error al guardar empresa');
               }).finally(() => { try { if (window.AppLoader && AppLoader.hide) AppLoader.hide(); } catch (e) { } });
            }
         });
      }

      // Eliminar (destroy)
      function delEmpresa(row) {
         return new Promise((resolve) => {
            if (!row) { toast('warning', 'Selecciona una empresa'); return resolve(false); }
            const okDel = canPerm('empresa.borrar');
            if (!okDel) { toast('error', 'No tienes permiso para eliminar'); return resolve(false); }
            if (!window.confirm('¿Eliminar (baja lógica) esta empresa?')) return resolve(false);
            try { if (window.AppLoader && AppLoader.show) AppLoader.show('Eliminando…'); } catch (e) { }
            Api.del(API.destroy(row.id))
               .then((j) => {
                  if (j.ok) {
                     toast('success', 'Empresa eliminada');
                     return loadData().then(() => { resolve(true); });
                  } else {
                     toast('warning', (j.error && j.error.message) || 'No se pudo eliminar la empresa');
                     resolve(false);
                  }
               })
               .catch((err) => { handleApiError(err, 'Error al eliminar empresa'); resolve(false); })
               .finally(() => { try { if (window.AppLoader && AppLoader.hide) AppLoader.hide(); } catch (e) { } });
         });
      }

      // Expediente: abrir modal
      if (btnExp) btnExp.addEventListener('click', () => {
         const row = getSelectedRow();
         if (!row) { toast('warning', 'Selecciona una empresa'); return; }
         const ok = canPerm('empresa.expediente');
         if (!ok) { toast('error', 'No tienes permiso para expediente'); return; }

         if (expEmpresaId) expEmpresaId.value = String(row.id);
         if (expEmpresaNm) expEmpresaNm.value = row.nombre != null ? row.nombre : '';
         if (expEmpresaTitle) expEmpresaTitle.textContent = 'Expediente de: ' + (row.nombre != null ? row.nombre : '(Sin nombre)') + ' (ID ' + row.id + ')';

         if (expTipoSel) expTipoSel.value = '';
         if (expLastBody) expLastBody.innerHTML = '';
         fileQueue.clear();
         renderQueue();

         function openModalExp() {
            if (!modalExpEl) return;

            if (hasBsModal) {
               jQuery(modalExpEl).modal({
                  backdrop: true,
                  keyboard: true,
                  show: true
               });
               return;
            }

            modalExpEl.classList.add('show');
            modalExpEl.style.display = 'block';
            modalExpEl.removeAttribute('aria-hidden');
            modalExpEl.setAttribute('aria-modal', 'true');

            if (!document.querySelector('.modal-backdrop')) {
               const bd2 = document.createElement('div');
               bd2.className = 'modal-backdrop fade show';
               bd2.addEventListener('click', () => {
                  modalExpEl.classList.remove('show');
                  modalExpEl.style.display = 'none';
                  modalExpEl.setAttribute('aria-hidden', 'true');
                  modalExpEl.removeAttribute('aria-modal');
                  const bd = document.querySelector('.modal-backdrop');
                  if (bd && bd.parentNode) bd.parentNode.removeChild(bd);
                  document.body.classList.remove('modal-open');
               });
               document.body.appendChild(bd2);
            }
            document.body.classList.add('modal-open');
         }

         if (!_tipos || _tipos.length === 0) {
            loadTiposDocumento().then(() => { openModalExp(); });
         } else {
            openModalExp();
         }
      });

      // ESC para modal de expediente (fallback)
      document.addEventListener('keydown', (e) => {
         if (e.key !== 'Escape') return;
         if (!modalExpEl) return;
         if (hasBsModal) return;
         if (modalExpEl.classList.contains('show')) {
            modalExpEl.classList.remove('show');
            modalExpEl.style.display = 'none';
            modalExpEl.setAttribute('aria-hidden', 'true');
            modalExpEl.removeAttribute('aria-modal');
            const bd = document.querySelector('.modal-backdrop');
            if (bd && bd.parentNode) bd.parentNode.removeChild(bd);
            document.body.classList.remove('modal-open');
         }
      });

      // Eventos básicos
      if (btnSearch) btnSearch.addEventListener('click', () => { loadData(); });
      if (btnNew) btnNew.addEventListener('click', (e) => {
         e.preventDefault();
         const selected = getSelectedRow();
         if (selected) openEdit(selected);
         else openCreate();
      });
      if (btnEdit) btnEdit.addEventListener('click', (e) => {
         e.preventDefault();
         const selected = getSelectedRow();
         openEdit(selected);
      });
      if (btnDelete) btnDelete.addEventListener('click', () => {
         const selected = getSelectedRow();
         delEmpresa(selected);
      });
      if (fQ) {
         fQ.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') { e.preventDefault(); loadData(); }
         });
      }

      // Primera carga
      (function () {
         loadCatalogs()
            .then(loadTiposDocumento)
            .then(loadData)
            .then(() => { if (window.__applyGates) window.__applyGates(document); })
            .then(updateActionButtons);
      })();

      // Toolbar estado
      function updateActionButtons() {
         return new Promise((resolve) => {
            const selected = getSelectedRow();
            const canCreate = !!canPerm('empresa.crear');
            const canEdit = !!canPerm('empresa.editar');
            const canDelete = !!canPerm('empresa.borrar');
            const canExp = !!canPerm('empresa.expediente');
            const canOblVer = !!canPerm('empresa.obligacion.ver');

            if (selected) {
               if (btnNew) {
                  btnNew.classList.remove('btn-success');
                  btnNew.classList.add('btn-warning');
                  btnNew.innerHTML = '<i class="fas fa-pen"></i> Editar';
                  btnNew.title = canEdit ? 'Editar empresa' : 'No autorizado para editar';
                  setEnabled(btnNew, canEdit);
               }
               if (btnEdit) {
                  btnEdit.title = canEdit ? 'Editar empresa' : 'No autorizado para editar';
                  setEnabled(btnEdit, canEdit);
               }
               if (btnDelete) {
                  btnDelete.title = canDelete ? 'Eliminar empresa' : 'No autorizado para eliminar';
                  setEnabled(btnDelete, canDelete);
               }
               setEnabled(btnExp, canExp);
               setEnabled(btnObl, canOblVer === true);
            } else {
               if (btnNew) {
                  btnNew.classList.remove('btn-warning');
                  btnNew.classList.add('btn-success');
                  btnNew.innerHTML = '<i class="fas fa-plus"></i> Nuevo';
                  btnNew.title = canCreate ? 'Registrar nueva empresa' : 'No autorizado para crear';
                  setEnabled(btnNew, canCreate);
               }
               setEnabled(btnEdit, false);
               if (btnDelete) {
                  setEnabled(btnDelete, false);
                  btnDelete.title = 'Selecciona una empresa para eliminar';
               }
               setEnabled(btnExp, false);
               setEnabled(btnObl, false);
            }
            resolve();
         });
      }
   }

   if (document.readyState === 'loading') window.addEventListener('DOMContentLoaded', init);
   else init();
})();


// ==================== OBLIGACIONES (Drawer minimal) ====================
(function ObligacionesModule() {
   const toast = window.__emp_toast || function (t, m) { return (t === 'error' ? console.error(m) : console.log(m)); };

   const openBtn = document.getElementById('emp-btn-oblig');
   const drawer = document.getElementById('oblig-drawer');
   const closeBtn = document.getElementById('oblig-close');
   const lblEmpresa = document.getElementById('oblig-empresa-label');

   const inpSearch = document.getElementById('oblig-search');
   const countCat = document.getElementById('oblig-count-cat');
   const countAsg = document.getElementById('oblig-count-asg');
   const btnRefresh = document.getElementById('oblig-refresh');
   const btnExpandAll = document.getElementById('oblig-expand-all');
   const btnCollapseAll = document.getElementById('oblig-collapse-all');
   const btnSelectAll = document.getElementById('oblig-select-all');
   const btnDeselectAll = document.getElementById('oblig-deselect-all');
   const btnSync = document.getElementById('oblig-btn-sync');

   const treeEl = document.getElementById('oblig-tree');

   if (!openBtn || !drawer) return;

   let _catalogo = [];
   let _asignadas = [];
   let _empresaSel = null;
   let _tree = null;

   function canPermLocal(p) {
      if (typeof window.__emp_canPerm === 'function') return window.__emp_canPerm(p);
      if (typeof window.__canPerm === 'function') return window.__canPerm(p);
      return false;
   }

   function openDrawer() {
      if (drawer && drawer.parentNode !== document.body) { document.body.appendChild(drawer); }
      let bd = document.querySelector('.oblig-backdrop');
      if (!bd) {
         bd = document.createElement('div');
         bd.className = 'oblig-backdrop';
         bd.addEventListener('click', closeDrawer);
         document.body.appendChild(bd);
      }
      drawer.classList.add('open');
      drawer.setAttribute('aria-hidden', 'false');
      document.documentElement.style.overflow = 'hidden';
      document.body.style.overflow = 'hidden';
   }

   function closeDrawer() {
      drawer.classList.remove('open');
      drawer.setAttribute('aria-hidden', 'true');
      const bd = document.querySelector('.oblig-backdrop');
      if (bd && bd.parentNode) bd.parentNode.removeChild(bd);
      document.documentElement.style.overflow = '';
      document.body.style.overflow = '';
   }

   if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
   document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && drawer.getAttribute('aria-hidden') === 'false') closeDrawer();
   });

   function loadCatalogo() {
      if (_catalogo.length) return Promise.resolve(_catalogo);
      try { if (window.AppLoader && AppLoader.show) AppLoader.show('Cargando catálogo de obligaciones…'); } catch (e) { }
      return Api.get('/api/v1/catalogos/obligaciones')
         .then((j) => {
            _catalogo = (j && j.data) ? j.data : [];
            if (countCat) countCat.textContent = String(_catalogo.length);
            return _catalogo;
         })
         .catch((err) => { toast('error', 'No se pudo cargar el catálogo de obligaciones'); return _catalogo; })
         .finally(() => { try { if (window.AppLoader && AppLoader.hide) AppLoader.hide(); } catch (e) { } });
   }

   function loadAsignadas(empresaId) {
      try { if (window.AppLoader && AppLoader.show) AppLoader.show('Cargando obligaciones asignadas…'); } catch (e) { }
      return Api.get('/api/v1/empresas/obligaciones?empresa_id=' + encodeURIComponent(empresaId))
         .then((j) => {
            _asignadas = (j && j.data) ? j.data : [];
            if (countAsg) countAsg.textContent = String(_asignadas.length);
            markAssignedOnTree();
         })
         .catch(() => { toast('error', 'No se pudieron cargar las asignadas'); })
         .finally(() => { try { if (window.AppLoader && AppLoader.hide) AppLoader.hide(); } catch (e) { } });
   }

   function groupByOrganismo(items) {
      const map = {}, res = [];
      for (let i = 0; i < items.length; i++) {
         const org = (items[i].organismo || 'OTROS').toString();
         if (!map[org]) map[org] = [];
         map[org].push(items[i]);
      }
      for (const k in map) if (Object.prototype.hasOwnProperty.call(map, k)) res.push([k, map[k]]);
      return res;
   }

   function buildTreeSource(items) {
      const byOrg = groupByOrganismo(items);
      const res = [];
      for (let i = 0; i < byOrg.length; i++) {
         const org = byOrg[i][0];
         const list = byOrg[i][1];
         res.push({
            title: org,
            folder: true,
            expanded: false,
            checkbox: true,
            select: false,
            unselectable: false,
            children: list.map((o) => ({
               key: String(o.id),
               title: o.descripcion + ' <span class="badge bg-secondary ms-2">' + o.clave + '</span>' + (o.activo ? '' : ' <span class="badge bg-danger ms-1">inactiva</span>'),
               tooltip: (o.organismo || '') + ' • ' + o.clave,
               extraClasses: o.activo ? '' : 'text-muted',
               data: o,
               checkbox: true
            }))
         });
      }
      return res.sort((a, b) => a.title.localeCompare(b.title));
   }

   function initTree() {
      if (!window.jQuery || !jQuery.fn.fancytree) { console.warn('FancyTree no disponible'); return; }
      try { if (jQuery(treeEl).data('ui-fancytree')) jQuery(treeEl).fancytree('destroy'); } catch (e) { }
      jQuery(treeEl).fancytree({
         extensions: ['filter', 'glyph', 'wide'],
         quicksearch: true,
         checkbox: true,
         selectMode: 3,
         filter: { autoExpand: true, highlight: true, mode: 'hide' },
         source: buildTreeSource(_catalogo),
         select: function (ev, data) {
            const node = data.node;
            if (node.folder) {
               node.setExpanded(true);
               node.visit((n) => { if (!n.folder) n.setSelected(node.isSelected()); });
            }
         }
      });
      _tree = jQuery.ui.fancytree.getTree(treeEl);
   }

   function markAssignedOnTree() {
      if (!_tree) return;
      const assignedIds = {};
      for (let i = 0; i < _asignadas.length; i++) assignedIds[String(_asignadas[i].obligacion_id)] = true;

      const root = _tree.getRootNode();
      root.visit((node) => {
         if (node.folder) {
            let all = true, any = false;
            node.children && node.children.forEach((c) => {
               if (c.folder) return;
               const sel = !!assignedIds[c.key];
               if (sel) any = true;
               if (!sel) all = false;
               c.setSelected(sel);
            });
            if (node.children && node.children.length) node.setSelected(all ? true : false);
         } else {
            node.setSelected(!!assignedIds[node.key]);
            node.renderTitle();
         }
      });
   }

   function filterTree(text) {
      if (!_tree) return;
      const match = text ? String(text).trim() : '';
      if (!match) {
         _tree.clearFilter();
         _tree.visit((n) => { n.setExpanded(false); });
         return;
      }
      const re = new RegExp(match.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'i');
      _tree.filterNodes((node) => {
         if (node.folder) return re.test(node.title);
         const d = node.data || {};
         return re.test(d.clave || '') || re.test(d.descripcion || '') || re.test(node.title || '');
      }, { autoExpand: true });
   }

   if (inpSearch) inpSearch.addEventListener('input', (e) => { filterTree(e.target.value || ''); });

   // Guardar selección (sync)
   if (btnSync) {
      btnSync.addEventListener('click', () => {
         if (!_empresaSel || !_empresaSel.id) { toast('warning', 'Selecciona una empresa'); return; }
         const can = canPermLocal('empresa.obligacion.editar') || canPermLocal('empresa.obligacion.asignar') || canPermLocal('empresa.obligacion.borrar');
         if (!can) { toast('error', 'No tienes permisos para guardar selección'); return; }
         if (!_tree) { toast('warning', 'Árbol no inicializado'); return; }

         const selected = [];
         _tree.getRootNode().visit((n) => { if (!n.folder && n.isSelected()) selected.push(parseInt(n.key, 10)); });

         try { if (window.AppLoader && AppLoader.show) AppLoader.show('Guardando selección…'); } catch (e) { }
         Api.post('/api/v1/empresas/obligaciones', {
            empresa_id: _empresaSel.id,
            obligacion_ids: selected
         }).then((j) => {
            if (j && j.ok) {
               toast('success', 'Selección guardada');
               return loadAsignadas(_empresaSel.id);
            } else {
               toast('warning', (j && j.error && j.error.message) || 'No se pudo guardar la selección');
            }
         }).catch((err) => {
            console.error(err);
            toast('error', (err && err.message) || 'Error al guardar selección');
         }).finally(() => { try { if (window.AppLoader && AppLoader.hide) AppLoader.hide(); } catch (e) { } });
      });
   }

   if (btnRefresh) btnRefresh.addEventListener('click', () => {
      if (!_empresaSel || !_empresaSel.id) return;
      loadAsignadas(_empresaSel.id);
   });

   if (btnExpandAll) {
      btnExpandAll.addEventListener('click', () => {
         if (!_tree) return;
         const root = _tree.getRootNode();
         root.visit((node) => {
            if (node.children && node.children.length) {
               node.setExpanded(true);
            }
         });
      });
   }

   if (btnCollapseAll) {
      btnCollapseAll.addEventListener('click', () => {
         if (!_tree) return;
         const root = _tree.getRootNode();
         root.visit((node) => {
            if (node.children && node.children.length) {
               node.setExpanded(false);
            }
         });
      });
   }

   if (btnSelectAll) {
      btnSelectAll.addEventListener('click', () => {
         if (!_tree) return;
         const root = _tree.getRootNode();
         root.visit((node) => {
            if (!node.folder) node.setSelected(true);
         });
      });
   }

   if (btnDeselectAll) {
      btnDeselectAll.addEventListener('click', () => {
         if (!_tree) return;
         _tree.getRootNode().visit((node) => { node.setSelected(false); });
      });
   }

   // Abrir drawer desde toolbar
   if (openBtn) openBtn.addEventListener('click', () => {
      const getSel = window.__emp_getSelected;
      const row = (typeof getSel === 'function') ? getSel() : null;
      if (!row) { toast('warning', 'Selecciona una empresa'); return; }

      const canVer = canPermLocal('empresa.obligacion.ver');
      if (!canVer) { toast('error', 'No tienes permiso para ver obligaciones'); return; }

      _empresaSel = row;
      if (lblEmpresa) lblEmpresa.textContent = (row.nombre || '(Sin nombre)') + ' • ID ' + row.id;

      openDrawer();
      loadCatalogo()
         .then(() => { initTree(); })
         .then(() => loadAsignadas(row.id))
         .then(() => { if (window.__applyGates) window.__applyGates(drawer); });
   });
})();
