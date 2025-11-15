// public/assets/js/empresas.index.js
(function () {
   var USE_DETAIL_FALLBACK = true;

   // ===== Notificaciones (Notyf) =====
   var _notyf = null;
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
      if (_notyf) _notyf.open({ type: type, message: message });
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
      var status = (err && (err.status || err.code)) || null;

      if (status === 401) toast('warning', 'Sesión expirada o no autenticado (401).');
      else if (status === 403 || status === 'FORBIDDEN') {
         var missing = (err && err.payload && err.payload.error && err.payload.error.message) ? err.payload.error.message : '';
         toast('error', 'No cuentas con permisos (403). ' + missing);
      } else if (status === 404) toast('warning', 'Recurso no encontrado (404).');
      else if (status === 409) toast('warning', 'Conflicto de datos (409).');
      else if (status === 422) toast('warning', 'Datos inválidos o incompletos (422).');
      else {
         var extra = (err && err.message) ? (': ' + err.message) : '';
         toast('error', fallbackMsg + extra);
      }
   }

   // --- CSRF helpers ---
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

   function init() {
      if (typeof Api === 'undefined') { console.error('Api.js no cargado'); return; }
      if (typeof agGrid === 'undefined') { console.error('AG Grid no cargado'); return; }

      // Helpers DOM
      function $(sel, ctx) { return (ctx || document).querySelector(sel); }
      function setEnabled(el, enabled) {
         if (!el) return;
         el.disabled = !enabled;
         el.classList.toggle('disabled', !enabled);
         if (enabled) el.removeAttribute('aria-disabled');
         else el.setAttribute('aria-disabled', 'true');
      }

      // ---- Controles / Contenedores ----
      var gridEl = $('#gridEmpresas');
      var fQ = $('#emp-f-q');
      var fArea = $('#emp-f-area');
      var fActivo = $('#emp-f-activo');

      var btnSearch = $('#emp-btn-search');
      var btnNew = $('#emp-btn-new');
      var btnEdit = $('#emp-btn-edit'); // puede no existir
      var btnDelete = $('#emp-btn-delete');
      var btnExp = $('#emp-btn-expediente');
      var btnObl = $('#emp-btn-oblig');

      if (!gridEl) { console.error('Falta #gridEmpresas'); return; }

      // ---- Modal refs ----
      var modalEl = $('#empresa-modal');
      var modalTitle = $('#empresa-modal-title');
      var btnSave = $('#emp-save');

      var fId = $('#emp-id');
      var fCli = $('#emp-cliente_grupo');
      var fNom = $('#emp-nombre');
      var fRfc = $('#emp-rfc');
      var fTipo = $('#emp-tipo_persona');
      var fAreaId = $('#emp-area_id');
      var fRespId = $('#emp-responsable_id');
      var fActv = $('#emp-activo');

      var fCont = $('#emp-contrato_servicios');
      var fNFac = $('#emp-nombre_facturacion');
      var fTFact = $('#emp-telefono_facturacion');
      var fCFact = $('#emp-correo_facturacion');
      var fReg = $('#emp-tipo_regimen');
      var fAct = $('#emp-actividad_principal');
      var fEdoDom = $('#emp-estatus_domicilio');

      // Modal Expediente refs (restaurado)
      var modalExpEl = document.getElementById('expediente-modal');
      var expEmpresaId = document.getElementById('exp-up-empresa_id');
      var expEmpresaNm = document.getElementById('exp-up-empresa_name');
      var expEmpresaTitle = document.getElementById('exp-empresa-title');
      var expTipoSel = document.getElementById('exp-up-tipo_clave');
      var expFilesInput = document.getElementById('exp-files-input');
      var expFilesTbody = document.getElementById('exp-files-tbody');
      var expLastWrap = document.getElementById('exp-last-list');
      var expLastBody = document.getElementById('exp-last-tbody');

      var _tipos = []; // catálogo tipos documento
      var _tipoByClave = new Map();

      // ---- Catálogos ----
      var _areas = [], _jefes = [];

      function fillSelect(selectEl, items, valueField, labelField, opts) {
         opts = opts || {};
         if (!selectEl) return;
         selectEl.innerHTML = '';
         if (opts.includeEmpty) {
            var opt0 = document.createElement('option');
            opt0.value = '';
            opt0.textContent = opts.emptyText || 'Seleccione…';
            selectEl.appendChild(opt0);
         }
         var list = items || [];
         for (var i = 0; i < list.length; i++) {
            var it = list[i];
            var opt = document.createElement('option');
            opt.value = String((it && it[valueField]) != null ? it[valueField] : '');
            opt.textContent = String((it && it[labelField]) != null ? it[labelField] : '');
            selectEl.appendChild(opt);
         }
      }
      window.__emp_fillSelect = fillSelect;

      function loadCatalogs() {
         return new Promise(function (resolve) {
            try { if (window.AppLoader && AppLoader.show) AppLoader.show('Cargando catálogos…'); } catch (e) { }
            Promise.all([
               Api.get('/api/v1/catalogos/areas'),
               Api.get('/api/v1/catalogos/jefes')
            ]).then(function (arr) {
               var jAreas = arr[0] || {};
               var jJefes = arr[1] || {};
               _areas = jAreas.data || [];
               _jefes = jJefes.data || [];

               window.__emp_areas = _areas;
               window.__emp_jefes = _jefes;

               fillSelect(fArea, _areas, 'id', 'nombre', { includeEmpty: true, emptyText: 'Todas' });
               fillSelect(fAreaId, _areas, 'id', 'nombre', { includeEmpty: true, emptyText: 'Seleccione…' });
               fillSelect(fRespId, _jefes, 'id', 'nombre', { includeEmpty: true, emptyText: 'Seleccione…' });
               resolve();
            }).catch(function (err) {
               handleApiError(err, 'No se pudieron cargar catálogos');
               resolve();
            }).finally(function () {
               try { if (window.AppLoader && AppLoader.hide) AppLoader.hide(); } catch (e) { }
            });
         });
      }

      // === Expediente (solo restaurado) ===
      function loadTiposDocumento() {
         return Api.get('/api/v1/catalogos/empresa_documento_tipos').then(function (res) {
            _tipos = (res && res.data) || [];
            _tipoByClave.clear();
            if (expTipoSel) expTipoSel.innerHTML = '<option value="">Seleccione…</option>';
            for (var i = 0; i < _tipos.length; i++) {
               var t = _tipos[i];
               var opt = document.createElement('option');
               opt.value = t.clave;
               opt.textContent = t.nombre;
               opt.dataset.id = t.id;
               opt.dataset.maxMb = (t.max_mb != null ? t.max_mb : '');

               var exts = [];
               try {
                  if (Array.isArray(t.acepta_ext)) exts = t.acepta_ext;
                  else if (typeof t.acepta_ext === 'string' && t.acepta_ext.trim().charAt(0) === '[') exts = JSON.parse(t.acepta_ext);
                  else if (typeof t.acepta_ext === 'string') {
                     exts = t.acepta_ext.split(',').map(function (s) { return s.trim(); });
                  }
               } catch (e) { exts = []; }

               opt.dataset.accept = exts.join(',');
               if (expTipoSel) expTipoSel.appendChild(opt);

               _tipoByClave.set(t.clave, {
                  id: t.id, clave: t.clave, nombre: t.nombre, max_mb: t.max_mb, acepta_ext: exts
               });
            }
         }).catch(function (err) {
            handleApiError(err, 'No se pudo cargar tipos de documento');
         });
      }

      var fileQueue = new Map();
      var _uidSeq = 1;
      function genUid() { return 'f_' + Date.now() + '_' + (_uidSeq++); }
      function bytesToSize(n) {
         if (n === undefined || n === null) return '';
         var kb = 1024, mb = kb * 1024;
         if (n >= mb) return (n / mb).toFixed(2) + ' MB';
         if (n >= kb) return (n / kb).toFixed(2) + ' KB';
         return n + ' B';
      }
      function isExtAllowedForType(ext, tipoClave) {
         var t = _tipoByClave.get(tipoClave);
         if (!t) return true;
         var list = (t.acepta_ext || []).map(function (s) { return String(s).replace(/^\./, '').toLowerCase(); });
         return list.length ? list.indexOf(ext.toLowerCase()) !== -1 : true;
      }
      function renderQueue() {
         if (!expFilesTbody) return;
         expFilesTbody.innerHTML = '';
         var idx = 1;
         fileQueue.forEach(function (rec, uid) {
            var f = rec.file;
            var ext = (f.name.split('.').pop() || '').toUpperCase();
            var tr = document.createElement('tr');
            tr.setAttribute('data-uid', uid);
            tr.innerHTML =
               '<td class="text-muted">' + (idx++) + '</td>' +
               '<td><span class="badge bg-secondary me-2">' + (ext || 'FILE') + '</span><span class="text-break">' + f.name + '</span></td>' +
               '<td>' + bytesToSize(f.size) + '</td>' +
               '<td><select class="form-select form-select-sm sel-tipo">' +
               (function () {
                  var parts = ['<option value="">— Selecciona tipo —</option>'];
                  for (var i = 0; i < _tipos.length; i++) {
                     var t = _tipos[i];
                     parts.push('<option value="' + t.clave + '">' + t.nombre + ' (' + t.clave + ')</option>');
                  }
                  return parts.join('');
               })() +
               '</select></td>' +
               '<td class="text-end"><button class="btn btn-sm btn-outline-danger btn-del" title="Quitar"><i class="fas fa-times"></i></button></td>';
            expFilesTbody.appendChild(tr);
            var sel = tr.querySelector('.sel-tipo');
            if (sel) {
               sel.value = rec.tipo_clave || '';
               sel.addEventListener('change', function () { rec.tipo_clave = sel.value || ''; });
            }
            var btnDel = tr.querySelector('.btn-del');
            if (btnDel) {
               btnDel.addEventListener('click', function () { fileQueue.delete(uid); renderQueue(); });
            }
         });
      }
      if (expFilesInput) {
         expFilesInput.addEventListener('change', function (e) {
            var files = Array.prototype.slice.call(e.target.files || []);
            if (!files.length) return;
            for (var i = 0; i < files.length; i++) {
               var f = files[i];
               var uid = genUid();
               fileQueue.set(uid, { file: f, tipo_clave: '' });
            }
            expFilesInput.value = '';
            renderQueue();
         });
      }

      window.__expedienteQueueUpload = function () {
         return new Promise(function (resolve) {
            var empresaId = parseInt((expEmpresaId && expEmpresaId.value) ? expEmpresaId.value : '0', 10);
            if (!empresaId) { toast('warning', 'Selecciona una empresa'); return resolve(false); }
            var okExp = canPerm('empresa.expediente');
            if (!okExp) { toast('error', 'No tienes permiso para expediente'); return resolve(false); }

            var missingTipo = [];
            fileQueue.forEach(function (rec) { if (!rec.tipo_clave) missingTipo.push(rec.file.name); });
            if (missingTipo.length) {
               var msgList = missingTipo.slice(0, 3).join(', ') + (missingTipo.length > 3 ? '…' : '');
               toast('warning', 'Selecciona tipo para: ' + msgList);
               return resolve(false);
            }
            var invalid = null;
            fileQueue.forEach(function (rec) {
               if (invalid) return;
               var ext = (rec.file.name.split('.').pop() || '').toLowerCase();
               if (!isExtAllowedForType(ext, rec.tipo_clave)) {
                  var t = _tipoByClave.get(rec.tipo_clave);
                  var lista = t && t.acepta_ext ? t.acepta_ext.join(', ') : '';
                  invalid = ' .' + ext + ' no permitido para tipo ' + rec.tipo_clave + '. Permitidas: ' + lista;
               }
            });
            if (invalid) { toast('error', invalid); return resolve(false); }

            getCsrf().then(function (csrf) {
               var headers = buildCsrfHeaders(csrf);
               try { if (window.AppLoader && AppLoader.show) AppLoader.show('Subiendo archivos…'); } catch (e) { }
               var ok = 0, fail = 0;
               (function uploadNext(iter) {
                  var it = iter || fileQueue.entries();
                  var step = it.next ? it.next() : null;
                  if (!step || step.done) {
                     try { if (window.AppLoader && AppLoader.hide) AppLoader.hide(); } catch (e) { }
                     if (ok && !fail) toast('success', 'Subida completa (' + ok + ')');
                     else if (ok && fail) toast('warning', 'Subidos ' + ok + ', fallaron ' + fail);
                     else toast('error', 'No se subió ningún archivo');
                     renderQueue();
                     return resolve(ok && !fail);
                  }
                  var uid = step.value[0];
                  var rec = step.value[1];
                  var fd = new FormData();
                  fd.append('empresa_id', String(empresaId));
                  fd.append('tipo_clave', rec.tipo_clave);
                  fd.append('file', rec.file, rec.file.name);
                  fetch('/api/v1/empresas/expediente', {
                     method: 'POST',
                     credentials: 'same-origin',
                     headers: headers,
                     body: fd
                  }).then(function (resp) {
                     if (!resp.ok) {
                        (resp.json ? resp.json() : Promise.resolve({})).then(function (j) {
                           var msg = (j && j.error && j.error.message) ? j.error.message : 'Error al subir archivo';
                           toast('error', rec.file.name + ': ' + msg);
                           fail++;
                           uploadNext(it);
                        }).catch(function () {
                           toast('error', rec.file.name + ': Error al subir archivo');
                           fail++; uploadNext(it);
                        });
                        return;
                     }
                     ok++;
                     fileQueue.delete(uid);
                     if (expFilesTbody) {
                        var tr = expFilesTbody.querySelector('tr[data-uid="' + uid + '"]');
                        if (tr && tr.parentNode) tr.parentNode.removeChild(tr);
                     }
                     var selClave = (expTipoSel && expTipoSel.value) ? expTipoSel.value : '';
                     if (selClave && selClave === rec.tipo_clave) {
                        var opt = (expTipoSel && expTipoSel.selectedOptions && expTipoSel.selectedOptions[0]) ? expTipoSel.selectedOptions[0] : null;
                        var tipoId = opt ? parseInt(opt.dataset.id || '0', 10) : 0;
                        if (empresaId && tipoId) {
                           loadLatestVersions(empresaId, tipoId, 5, false).then(function () { uploadNext(it); });
                           return;
                        }
                     }
                     uploadNext(it);
                  }).catch(function () {
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
         return new Promise(function (resolve) {
            try { if (window.AppLoader && AppLoader.show) AppLoader.show('Cargando versiones…'); } catch (e) { }
            var params = new URLSearchParams({ empresa_id: String(empresaId), tipo_id: String(tipoId) });
            if (!showAll && limit) params.set('limit', String(limit));
            Api.get('/api/v1/empresas/expediente/versions?' + params.toString())
               .then(function (j) {
                  var rows = (j && j.data) ? j.data : [];
                  renderLastList(rows);
                  resolve();
               })
               .catch(function (err) { handleApiError(err, 'No se pudieron cargar versiones'); resolve(); })
               .finally(function () { try { if (window.AppLoader && AppLoader.hide) AppLoader.hide(); } catch (e) { } });
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
         for (var i = 0; i < rows.length; i++) {
            var r = rows[i];
            var tr = document.createElement('tr');
            var fecha = r.creado_en || r.subido_en || '';
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
         expLastBody.addEventListener('click', function (e) {
            var btn = e.target && e.target.closest ? e.target.closest('button[data-act="exp-dl"]') : null;
            if (!btn) return;
            var docId = parseInt(btn.getAttribute('data-id') || '0', 10);
            var empresaId = parseInt((expEmpresaId && expEmpresaId.value) ? expEmpresaId.value : '0', 10);
            if (!empresaId || !docId) return;
            window.open('/api/v1/empresas/expediente/download?empresa_id=' + empresaId + '&doc_id=' + docId + '&stream=1', '_blank');
         });
      }
      if (expTipoSel) {
         expTipoSel.addEventListener('change', function () {
            var empresaId = parseInt((expEmpresaId && expEmpresaId.value) ? expEmpresaId.value : '0', 10);
            var opt = (expTipoSel.selectedOptions && expTipoSel.selectedOptions[0]) ? expTipoSel.selectedOptions[0] : null;
            var tipoId = opt ? parseInt(opt.dataset.id || '0', 10) : 0;
            if (empresaId && tipoId) loadLatestVersions(empresaId, tipoId, 5, false);
         });
      }

      // ---- AG Grid ----
      var columnDefs = [
         { headerName: '#', valueGetter: 'node.rowIndex + 1', width: 70 },
         { headerName: 'Cliente', field: 'cliente_grupo', flex: 1 },
         { headerName: 'Nombre', field: 'nombre', flex: 1.2, minWidth: 180 },
         { headerName: 'RFC', field: 'rfc', width: 160, cellRenderer: function (p) { return '<code>' + (p.value || '') + '</code>'; } },
         { headerName: 'Tipo', field: 'tipo_persona', width: 110 },
         { headerName: 'Área', field: 'area_id', width: 100 },
         { headerName: 'Responsable', field: 'responsable_id', width: 120 },
         { headerName: 'Activo', field: 'activo', width: 100, valueFormatter: function (p) { return p.value ? 'Sí' : 'No'; } }
      ];
      var gridOptions = {
         columnDefs: columnDefs,
         rowData: [],
         animateRows: true,
         rowHeight: 42,
         pagination: true,
         paginationPageSize: 20,
         rowSelection: 'single',
         suppressRowClickSelection: true,
         onRowClicked: function (e) {
            var wasSelected = e.node.isSelected();
            e.node.setSelected(!wasSelected, true);
            updateActionButtons();
         },
         onSelectionChanged: function () { updateActionButtons(); }
      };
      var gridApiOrInstance = (typeof agGrid.Grid === 'function')
         ? new agGrid.Grid(gridEl, gridOptions)
         : (agGrid.createGrid ? agGrid.createGrid(gridEl, gridOptions) : null);

      function getSelectedRow() {
         var api = gridOptions.api || (gridApiOrInstance && gridApiOrInstance.api) || gridApiOrInstance;
         var sel = (api && typeof api.getSelectedRows === 'function') ? api.getSelectedRows() : null;
         return (sel && sel[0]) ? sel[0] : null;
      }
      window.__emp_getSelected = getSelectedRow;
      window.__emp_canPerm = canPerm;
      Object.defineProperty(window, '__emp_catalogs', {
         get: function () { return { areas: _areas, jefes: _jefes }; }
      });

      // ---- Carga de datos ----
      function loadData() {
         return new Promise(function (resolve) {
            try { if (window.AppLoader && AppLoader.show) AppLoader.show('Cargando empresas…'); } catch (e) { }
            var params = new URLSearchParams({ page: '1', size: '200' });
            var q = (fQ && fQ.value ? fQ.value : '').trim();
            var activo = (fActivo && (fActivo.value !== undefined && fActivo.value !== null)) ? fActivo.value : '';
            var areaId = (fArea && fArea.value) ? fArea.value : '';
            if (q) params.set('q', q);
            if (activo !== '') params.set('activo', activo);
            if (areaId) params.set('area_id', areaId);
            Api.get('/api/v1/empresas?' + params.toString()).then(function (j) {
               var rows = j.data || [];
               var api = gridOptions.api || (gridApiOrInstance && gridApiOrInstance.api) || gridApiOrInstance;
               if (api && typeof api.setRowData === 'function') api.setRowData(rows);
               if (api && typeof api.deselectAll === 'function') api.deselectAll();
               updateActionButtons();
               toast('info', 'Empresas cargadas: ' + rows.length);
               resolve();
            }).catch(function (err) {
               handleApiError(err, 'No se pudo cargar empresas');
               resolve();
            }).finally(function () {
               try { if (window.AppLoader && AppLoader.hide) AppLoader.hide(); } catch (e) { }
            });
         });
      }

      // ---- Modal Empresa (CRUD) ----
      var hasBsModal = (window.jQuery && jQuery.fn && typeof jQuery.fn.modal === 'function');
      var modalIsOpen = false;

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
            // Usar Bootstrap: animación, backdrop, ESC, click fuera
            jQuery(modalEl).modal({
               backdrop: true,
               keyboard: true,
               show: true
            });
            modalIsOpen = true;
            return;
         }

         // Fallback manual (sin Bootstrap)
         modalEl.classList.add('show');
         modalEl.style.display = 'block';
         modalEl.removeAttribute('aria-hidden');
         modalEl.setAttribute('aria-modal', 'true');

         var bd = document.querySelector('.modal-backdrop');
         if (!bd) {
            bd = document.createElement('div');
            bd.className = 'modal-backdrop fade show';
            bd.addEventListener('click', function () {
               hideModal();
            });
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

         // Fallback manual (sin Bootstrap)
         modalEl.classList.remove('show');
         modalEl.style.display = 'none';
         modalEl.setAttribute('aria-hidden', 'true');
         modalEl.removeAttribute('aria-modal');

         var bds = document.querySelectorAll('.modal-backdrop');
         bds.forEach(function (bd) {
            if (bd && bd.parentNode) bd.parentNode.removeChild(bd);
         });

         document.body.classList.remove('modal-open');
         modalIsOpen = false;
      }

      // Cierre con botones (X, Cerrar)
      if (modalEl) {
         var closeButtons = modalEl.querySelectorAll('[data-dismiss="modal"], [data-bs-dismiss="modal"], .close');
         closeButtons.forEach(function (btn) {
            btn.addEventListener('click', function (e) {
               e.preventDefault();
               hideModal();
            });
         });
      }

      // Cierre con ESC para Empresa
      document.addEventListener('keydown', function (e) {
         if (e.key === 'Escape' && modalIsOpen) hideModal();
      });

      function openCreate() {
         return new Promise(function (resolve) {
            var ok = canPerm('empresa.crear');
            if (!ok) { toast('error', 'No tienes permiso para crear'); return resolve(false); }
            resetForm();
            if (modalTitle) modalTitle.textContent = 'Nueva empresa';
            showModal();
            resolve(true);
         });
      }
      function openEdit(row) {
         return new Promise(function (resolve) {
            if (!row) return resolve(false);
            var ok = canPerm('empresa.editar');
            if (!ok) { toast('error', 'No tienes permiso para editar'); return resolve(false); }
            resetForm();
            if (modalTitle) modalTitle.textContent = 'Editar empresa #' + row.id;
            var data = row;
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
               Api.get('/api/v1/empresas/show?id=' + encodeURIComponent(row.id))
                  .then(function (det) { if (det && det.ok && det.data) data = det.data; fillAndShow(); })
                  .catch(function (err) { handleApiError(err, 'No se pudo cargar el detalle de la empresa'); fillAndShow(); })
                  .finally(function () { try { if (window.AppLoader && AppLoader.hide) AppLoader.hide(); } catch (e) { } });
            } else {
               fillAndShow();
            }
         });
      }

      // Guardar empresa
      if (btnSave) {
         btnSave.addEventListener('click', function () {
            var payload = {
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
            if (!payload.id) {
               var okCreate = canPerm('empresa.crear');
               if (!okCreate) { toast('error', 'No tienes permiso para crear'); try { AppLoader.hide(); } catch (e) { } return; }
               Api.post('/api/v1/empresas', payload).then(function (j) {
                  if (j.ok) { toast('success', 'Empresa creada'); hideModal(); loadData(); }
                  else toast('warning', (j.error && j.error.message) || 'No se pudo crear la empresa');
               }).catch(function (err) {
                  handleApiError(err, 'Error al guardar empresa');
               }).finally(function () { try { if (window.AppLoader && AppLoader.hide) AppLoader.hide(); } catch (e) { } });
            } else {
               var okEdit = canPerm('empresa.editar');
               if (!okEdit) { toast('error', 'No tienes permiso para editar'); try { AppLoader.hide(); } catch (e) { } return; }
               Api.put('/api/v1/empresas', payload).then(function (j) {
                  if (j.ok) { toast('success', 'Empresa actualizada'); hideModal(); loadData(); }
                  else toast('warning', (j.error && j.error.message) || 'No se pudo actualizar la empresa');
               }).catch(function (err) {
                  handleApiError(err, 'Error al guardar empresa');
               }).finally(function () { try { if (window.AppLoader && AppLoader.hide) AppLoader.hide(); } catch (e) { } });
            }
         });
      }

      // Eliminar
      function delEmpresa(row) {
         return new Promise(function (resolve) {
            if (!row) { toast('warning', 'Selecciona una empresa'); return resolve(false); }
            var okDel = canPerm('empresa.borrar');
            if (!okDel) { toast('error', 'No tienes permiso para eliminar'); return resolve(false); }
            if (!window.confirm('¿Eliminar (baja lógica) esta empresa?')) return resolve(false);
            try { if (window.AppLoader && AppLoader.show) AppLoader.show('Eliminando…'); } catch (e) { }
            Api.del('/api/v1/empresas?id=' + encodeURIComponent(row.id))
               .then(function (j) {
                  if (j.ok) { toast('success', 'Empresa eliminada'); return loadData().then(function () { resolve(true); }); }
                  else { toast('warning', (j.error && j.error.message) || 'No se pudo eliminar la empresa'); resolve(false); }
               })
               .catch(function (err) { handleApiError(err, 'Error al eliminar empresa'); resolve(false); })
               .finally(function () { try { if (window.AppLoader && AppLoader.hide) AppLoader.hide(); } catch (e) { } });
         });
      }

      // Expediente: abrir modal
      if (btnExp) btnExp.addEventListener('click', function () {
         var row = getSelectedRow();
         if (!row) { toast('warning', 'Selecciona una empresa'); return; }
         var ok = canPerm('empresa.expediente');
         if (!ok) { toast('error', 'No tienes permiso para expediente'); return; }

         if (expEmpresaId) expEmpresaId.value = String(row.id);
         if (expEmpresaNm) expEmpresaNm.value = row.nombre != null ? row.nombre : '';
         if (expEmpresaTitle) expEmpresaTitle.textContent = 'Expediente de: ' + (row.nombre != null ? row.nombre : '(Sin nombre)') + ' (ID ' + row.id + ')';

         if (expTipoSel) expTipoSel.value = '';
         if (expLastBody) expLastBody.innerHTML = '';
         fileQueue.clear();
         renderQueue();

         var openModal = function () {
            if (!modalExpEl) return;

            if (hasBsModal) {
               jQuery(modalExpEl).modal({
                  backdrop: true,
                  keyboard: true,
                  show: true
               });
               return;
            }

            // Fallback manual
            modalExpEl.classList.add('show');
            modalExpEl.style.display = 'block';
            modalExpEl.removeAttribute('aria-hidden');
            modalExpEl.setAttribute('aria-modal', 'true');

            if (!document.querySelector('.modal-backdrop')) {
               var bd2 = document.createElement('div');
               bd2.className = 'modal-backdrop fade show';
               bd2.addEventListener('click', function () {
                  // cerrar expediente en fallback
                  modalExpEl.classList.remove('show');
                  modalExpEl.style.display = 'none';
                  modalExpEl.setAttribute('aria-hidden', 'true');
                  modalExpEl.removeAttribute('aria-modal');
                  var bd = document.querySelector('.modal-backdrop');
                  if (bd && bd.parentNode) bd.parentNode.removeChild(bd);
                  document.body.classList.remove('modal-open');
               });
               document.body.appendChild(bd2);
            }
            document.body.classList.add('modal-open');
         };

         if (!_tipos || _tipos.length === 0) {
            loadTiposDocumento().then(function () { openModal(); });
         } else {
            openModal();
         }
      });

      // ESC para modal de expediente (solo en fallback, con Bootstrap él lo maneja solo)
      document.addEventListener('keydown', function (e) {
         if (e.key !== 'Escape') return;
         if (!modalExpEl) return;
         if (hasBsModal) {
            // si está visible, Bootstrap ya lo cierra, no hacemos nada extra
            return;
         }
         if (modalExpEl.classList.contains('show')) {
            modalExpEl.classList.remove('show');
            modalExpEl.style.display = 'none';
            modalExpEl.setAttribute('aria-hidden', 'true');
            modalExpEl.removeAttribute('aria-modal');
            var bd = document.querySelector('.modal-backdrop');
            if (bd && bd.parentNode) bd.parentNode.removeChild(bd);
            document.body.classList.remove('modal-open');
         }
      });

      if (btnSearch) btnSearch.addEventListener('click', function () { loadData(); });
      if (btnNew) btnNew.addEventListener('click', function (e) {
         e.preventDefault();
         var selected = getSelectedRow();
         if (selected) openEdit(selected);
         else openCreate();
      });
      if (btnEdit) btnEdit.addEventListener('click', function (e) {
         e.preventDefault();
         var selected = getSelectedRow();
         openEdit(selected);
      });
      if (btnDelete) btnDelete.addEventListener('click', function () {
         var selected = getSelectedRow();
         delEmpresa(selected);
      });
      if (fQ) {
         fQ.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); loadData(); }
         });
      }

      // Primera carga
      (function () {
         loadCatalogs()
            .then(loadTiposDocumento)
            .then(loadData)
            .then(function () { if (window.__applyGates) window.__applyGates(document); })
            .then(updateActionButtons);
      })();

      // Toolbar estado
      function updateActionButtons() {
         return new Promise(function (resolve) {
            var selected = getSelectedRow();
            var canCreate = !!canPerm('empresa.crear');
            var canEdit = !!canPerm('empresa.editar');
            var canDelete = !!canPerm('empresa.borrar');
            var canExp = !!canPerm('empresa.expediente');
            var canOblVer = !!canPerm('empresa.obligacion.ver');

            if (selected) {
               if (btnNew) { btnNew.classList.remove('btn-success'); btnNew.classList.add('btn-warning'); btnNew.innerHTML = '<i class="fas fa-pen"></i> Editar'; btnNew.title = canEdit ? 'Editar empresa' : 'No autorizado para editar'; setEnabled(btnNew, canEdit); }
               if (btnEdit) { btnEdit.title = canEdit ? 'Editar empresa' : 'No autorizado para editar'; setEnabled(btnEdit, canEdit); }
               if (btnDelete) { btnDelete.title = canDelete ? 'Eliminar empresa' : 'No autorizado para eliminar'; setEnabled(btnDelete, canDelete); }
               setEnabled(btnExp, canExp);
               setEnabled(btnObl, canOblVer === true);
            } else {
               if (btnNew) { btnNew.classList.remove('btn-warning'); btnNew.classList.add('btn-success'); btnNew.innerHTML = '<i class="fas fa-plus"></i> Nuevo'; btnNew.title = canCreate ? 'Registrar nueva empresa' : 'No autorizado para crear'; setEnabled(btnNew, canCreate); }
               setEnabled(btnEdit, false);
               if (btnDelete) { setEnabled(btnDelete, false); btnDelete.title = 'Selecciona una empresa para eliminar'; }
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
   var toast = window.__emp_toast || function (t, m) { return (t === 'error' ? console.error(m) : console.log(m)); };

   var openBtn = document.getElementById('emp-btn-oblig');
   var drawer = document.getElementById('oblig-drawer');
   var closeBtn = document.getElementById('oblig-close');
   var lblEmpresa = document.getElementById('oblig-empresa-label');

   var inpSearch = document.getElementById('oblig-search');
   var countCat = document.getElementById('oblig-count-cat');
   var countAsg = document.getElementById('oblig-count-asg');
   var btnRefresh = document.getElementById('oblig-refresh');
   var btnExpandAll = document.getElementById('oblig-expand-all');
   var btnCollapseAll = document.getElementById('oblig-collapse-all');
   var btnSelectAll = document.getElementById('oblig-select-all');
   var btnDeselectAll = document.getElementById('oblig-deselect-all');
   var btnSync = document.getElementById('oblig-btn-sync');

   var treeEl = document.getElementById('oblig-tree');

   if (!openBtn || !drawer) return;

   var _catalogo = [];
   var _asignadas = [];
   var _empresaSel = null;
   var _tree = null;

   function canPermLocal(p) {
      if (typeof window.__emp_canPerm === 'function') return window.__emp_canPerm(p);
      if (typeof window.__canPerm === 'function') return window.__canPerm(p);
      return false;
   }

   function openDrawer() {
      if (drawer && drawer.parentNode !== document.body) { document.body.appendChild(drawer); }
      var bd = document.querySelector('.oblig-backdrop');
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
      var bd = document.querySelector('.oblig-backdrop');
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
         .then(function (j) {
            _catalogo = (j && j.data) ? j.data : [];
            if (countCat) countCat.textContent = String(_catalogo.length);
            return _catalogo;
         })
         .catch(function (err) { toast('error', 'No se pudo cargar el catálogo de obligaciones'); return _catalogo; })
         .finally(function () { try { if (window.AppLoader && AppLoader.hide) AppLoader.hide(); } catch (e) { } });
   }

   function loadAsignadas(empresaId) {
      try { if (window.AppLoader && AppLoader.show) AppLoader.show('Cargando obligaciones asignadas…'); } catch (e) { }
      return Api.get('/api/v1/empresas/obligaciones?empresa_id=' + encodeURIComponent(empresaId))
         .then(function (j) {
            _asignadas = (j && j.data) ? j.data : [];
            if (countAsg) countAsg.textContent = String(_asignadas.length);
            //renderAsignadas();
            markAssignedOnTree();
         })
         .catch(function (err) { toast('error', 'No se pudieron cargar las asignadas'); })
         .finally(function () { try { if (window.AppLoader && AppLoader.hide) AppLoader.hide(); } catch (e) { } });
   }

   function groupByOrganismo(items) {
      var map = {}, res = [];
      for (var i = 0; i < items.length; i++) {
         var org = (items[i].organismo || 'OTROS').toString();
         if (!map[org]) map[org] = [];
         map[org].push(items[i]);
      }
      for (var k in map) if (Object.prototype.hasOwnProperty.call(map, k)) res.push([k, map[k]]);
      return res;
   }

   function buildTreeSource(items) {
      var byOrg = groupByOrganismo(items);
      var res = [];
      for (var i = 0; i < byOrg.length; i++) {
         var org = byOrg[i][0];
         var list = byOrg[i][1];
         res.push({
            title: org,
            folder: true,
            expanded: false,
            checkbox: true,
            select: false,
            unselectable: false,
            children: list.map(function (o) {
               return {
                  key: String(o.id),
                  title: o.descripcion + ' <span class="badge bg-secondary ms-2">' + o.clave + '</span>' + (o.activo ? '' : ' <span class="badge bg-danger ms-1">inactiva</span>'),
                  tooltip: (o.organismo || '') + ' • ' + o.clave,
                  extraClasses: o.activo ? '' : 'text-muted',
                  data: o,
                  checkbox: true
               };
            })
         });
      }
      return res.sort(function (a, b) { return a.title.localeCompare(b.title); });
   }

   function initTree() {
      if (!window.jQuery || !jQuery.fn.fancytree) { console.warn('FancyTree no disponible'); return; }
      // destruye solo si ya estaba inicializado
      try { if (jQuery(treeEl).data('ui-fancytree')) jQuery(treeEl).fancytree('destroy'); } catch (e) { }
      jQuery(treeEl).fancytree({
         extensions: ['filter', 'glyph', 'wide'],
         quicksearch: true,
         checkbox: true,
         selectMode: 3,
         filter: { autoExpand: true, highlight: true, mode: 'hide' },
         source: buildTreeSource(_catalogo),
         // cascada inmediata en folders
         select: function (ev, data) {
            var node = data.node;
            if (node.folder) {
               node.setExpanded(true);
               node.visit(function (n) { if (!n.folder) n.setSelected(node.isSelected()); });
            }
         }
      });
      _tree = jQuery.ui.fancytree.getTree(treeEl);
   }

   function markAssignedOnTree() {
      if (!_tree) return;
      var assignedIds = {};
      for (var i = 0; i < _asignadas.length; i++) assignedIds[String(_asignadas[i].obligacion_id)] = true;

      var root = _tree.getRootNode();
      root.visit(function (node) {
         if (node.folder) {
            var all = true, any = false;
            node.children && node.children.forEach(function (c) {
               if (c.folder) return;
               var sel = !!assignedIds[c.key];
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
      var match = text ? String(text).trim() : '';
      if (!match) {
         _tree.clearFilter();
         _tree.visit(function (n) { n.setExpanded(false); });
         return;
      }
      var re = new RegExp(match.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'i');
      _tree.filterNodes(function (node) {
         if (node.folder) return re.test(node.title);
         var d = node.data || {};
         return re.test(d.clave || '') || re.test(d.descripcion || '') || re.test(node.title || '');
      }, { autoExpand: true });
   }
   if (inpSearch) inpSearch.addEventListener('input', function (e) { filterTree(e.target.value || ''); });


   // Guardar selección (sync)
   if (btnSync) {
      btnSync.addEventListener('click', function () {
         if (!_empresaSel || !_empresaSel.id) { toast('warning', 'Selecciona una empresa'); return; }
         var can = canPermLocal('empresa.obligacion.editar') || canPermLocal('empresa.obligacion.asignar') || canPermLocal('empresa.obligacion.borrar');
         if (!can) { toast('error', 'No tienes permisos para guardar selección'); return; }
         if (!_tree) { toast('warning', 'Árbol no inicializado'); return; }

         var selected = [];
         _tree.getRootNode().visit(function (n) { if (!n.folder && n.isSelected()) selected.push(parseInt(n.key, 10)); });

         try { if (window.AppLoader && AppLoader.show) AppLoader.show('Guardando selección…'); } catch (e) { }
         Api.post('/api/v1/empresas/obligaciones', {
            empresa_id: _empresaSel.id,
            obligacion_ids: selected
         }).then(function (j) {
            if (j && j.ok) {
               toast('success', 'Selección guardada');
               return loadAsignadas(_empresaSel.id);
            } else {
               toast('warning', (j && j.error && j.error.message) || 'No se pudo guardar la selección');
            }
         }).catch(function (err) {
            console.error(err);
            toast('error', (err && err.message) || 'Error al guardar selección');
         }).finally(function () { try { if (window.AppLoader && AppLoader.hide) AppLoader.hide(); } catch (e) { } });
      });
   }

   if (btnRefresh) btnRefresh.addEventListener('click', function () {
      if (!_empresaSel || !_empresaSel.id) return;
      loadAsignadas(_empresaSel.id);
   });

   // Expandir todo
   if (btnExpandAll) {
      btnExpandAll.addEventListener('click', function () {
         if (!_tree) return;
         var root = _tree.getRootNode();
         root.visit(function (node) {
            if (node.children && node.children.length) {
               node.setExpanded(true);
            }
         });
      });
   }

   // Contraer todo
   if (btnCollapseAll) {
      btnCollapseAll.addEventListener('click', function () {
         if (!_tree) return;
         var root = _tree.getRootNode();
         root.visit(function (node) {
            if (node.children && node.children.length) {
               node.setExpanded(false);
            }
         });
      });
   }

   // Marcar todas las obligaciones
   if (btnSelectAll) {
      btnSelectAll.addEventListener('click', function () {
         if (!_tree) return;
         var root = _tree.getRootNode();
         root.visit(function (node) {
            if (!node.folder) {
               node.setSelected(true);
            }
         });
      });
   }

   // Desmarcar todas las obligaciones
   if (btnDeselectAll) {
      btnDeselectAll.addEventListener('click', function () {
         if (!_tree) return;
         _tree.getRootNode().visit(function (node) {
            node.setSelected(false);
         });
      });
   }


   // Abrir drawer desde toolbar
   if (openBtn) openBtn.addEventListener('click', function () {
      var getSel = window.__emp_getSelected;
      var row = (typeof getSel === 'function') ? getSel() : null;
      if (!row) { toast('warning', 'Selecciona una empresa'); return; }

      var canVer = canPermLocal('empresa.obligacion.ver');
      if (!canVer) { toast('error', 'No tienes permiso para ver obligaciones'); return; }

      _empresaSel = row;
      if (lblEmpresa) lblEmpresa.textContent = (row.nombre || '(Sin nombre)') + ' • ID ' + row.id;

      openDrawer();
      loadCatalogo()
         .then(function () { initTree(); })
         .then(function () { return loadAsignadas(row.id); })
         .then(function () { if (window.__applyGates) window.__applyGates(drawer); });
   });
})();
