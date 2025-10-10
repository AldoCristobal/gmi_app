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
            { type: 'error', background: '#EF4444', icon: false },
         ],
      });
   } catch (e) {
      console.warn('Notyf no encontrado; se usarán logs de consola.');
   }
   const toast = (type, message) => {
      if (_notyf) _notyf.open({ type, message });
      else console[type === 'error' ? 'error' : 'log'](message);
   };
   // Exponer toast para otros módulos
   window.__emp_toast = toast;

   function handleApiError(err, fallbackMsg = 'Error al procesar la solicitud') {
      console.error(err);
      const status = err?.status ?? err?.code;
      if (status === 401) toast('warning', 'Sesión expirada o no autenticado (401).');
      else if (status === 403 || status === 'FORBIDDEN') {
         const missing = err?.payload?.error?.message || '';
         toast('error', `No cuentas con permisos (403). ${missing}`);
      } else if (status === 404) toast('warning', 'Recurso no encontrado (404).');
      else if (status === 409) toast('warning', 'Conflicto de datos (409).');
      else if (status === 422) toast('warning', 'Datos inválidos o incompletos (422).');
      else toast('error', `${fallbackMsg}${err?.message ? `: ${err.message}` : ''}`);
   }

   // --- CSRF helpers ---
   function readCookie(name) {
      const cookies = document.cookie ? document.cookie.split('; ') : [];
      for (const c of cookies) {
         const idx = c.indexOf('=');
         const key = idx > -1 ? c.substring(0, idx) : c;
         if (key === name) {
            const val = idx > -1 ? c.substring(idx + 1) : '';
            try { return decodeURIComponent(val); } catch { return val; }
         }
      }
      return '';
   }
   function getMetaCsrf() {
      const meta = document.querySelector('meta[name="csrf-token"]');
      return (meta && meta.content) ? meta.content : '';
   }
   async function getCsrf() {
      const meta = getMetaCsrf();
      if (meta) return { token: meta, source: 'meta' };

      const ls = localStorage.getItem('csrf_token');
      if (ls) return { token: ls, source: 'localStorage' };

      try {
         const r = await fetch('/api/csrf', { credentials: 'same-origin' });
         if (r.ok) {
            const j = await r.json().catch(() => ({}));
            const token = j?.csrf || j?.token || j?.data || '';
            if (token) return { token, source: 'api' };
         }
      } catch { }

      try {
         const r = await fetch('/sanctum/csrf-cookie', { credentials: 'same-origin' });
         if (r.ok) {
            const cookieToken = readCookie('XSRF-TOKEN');
            if (cookieToken) return { token: cookieToken, source: 'cookie' };
         }
      } catch { }
      return { token: '', source: 'none' };
   }
   function buildCsrfHeaders(csrf) {
      if (!csrf?.token) return {};
      return { 'X-CSRF-Token': csrf.token };
   }

   function init() {
      if (typeof Api === 'undefined') { console.error('Api.js no cargado'); return; }
      if (typeof agGrid === 'undefined') { console.error('AG Grid no cargado'); return; }

      // ---- Helpers DOM ----
      const $ = (sel, ctx = document) => ctx.querySelector(sel);
      const setEnabled = (el, enabled) => {
         if (!el) return;
         el.disabled = !enabled;
         el.classList.toggle('disabled', !enabled);
         if (enabled) el.removeAttribute('aria-disabled');
         else el.setAttribute('aria-disabled', 'true');
      };

      // ---- Controles / Contenedores ----
      const gridEl = $('#gridEmpresas');
      const fQ = $('#emp-f-q');
      const fArea = $('#emp-f-area');
      const fActivo = $('#emp-f-activo');

      const btnSearch = $('#emp-btn-search');
      const btnNew = $('#emp-btn-new');
      const btnEdit = $('#emp-btn-edit');
      const btnDelete = $('#emp-btn-delete');
      const btnExp = $('#emp-btn-expediente');
      const btnObl = $('#emp-btn-oblig'); // botón de Obligaciones (en toolbar)

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

      let _tipos = []; // catálogo de tipos doc
      const _tipoByClave = new Map();

      // ---- Catálogos ----
      let _areas = [], _jefes = [];

      function fillSelect(selectEl, items, valueField, labelField, { includeEmpty, emptyText } = {}) {
         if (!selectEl) return;
         selectEl.innerHTML = '';
         if (includeEmpty) {
            const opt = document.createElement('option');
            opt.value = '';
            opt.textContent = emptyText || 'Seleccione…';
            selectEl.appendChild(opt);
         }
         for (const it of (items || [])) {
            const opt = document.createElement('option');
            opt.value = String(it[valueField] ?? '');
            opt.textContent = String(it[labelField] ?? '');
            selectEl.appendChild(opt);
         }
      }
      // Exponer helpers / catálogos para el módulo de Obligaciones
      window.__emp_fillSelect = fillSelect;

      async function loadCatalogs() {
         try {
            AppLoader?.show('Cargando catálogos…');
            const [jAreas, jJefes] = await Promise.all([
               Api.get('/api/v1/catalogos/areas'),
               Api.get('/api/v1/catalogos/jefes'),
            ]);
            _areas = jAreas?.data || [];
            _jefes = jJefes?.data || [];

            // exponer global para Obligaciones
            window.__emp_areas = _areas;
            window.__emp_jefes = _jefes;

            fillSelect(fArea, _areas, 'id', 'nombre', { includeEmpty: true, emptyText: 'Todas' });
            fillSelect(fAreaId, _areas, 'id', 'nombre', { includeEmpty: true, emptyText: 'Seleccione…' });
            fillSelect(fRespId, _jefes, 'id', 'nombre', { includeEmpty: true, emptyText: 'Seleccione…' });
         } catch (err) {
            handleApiError(err, 'No se pudieron cargar catálogos');
         } finally {
            AppLoader?.hide();
         }
      }

      async function loadTiposDocumento() {
         try {
            const res = await Api.get('/api/v1/catalogos/empresa_documento_tipos');
            _tipos = res?.data || [];
            _tipoByClave.clear();
            if (expTipoSel) expTipoSel.innerHTML = '<option value="">Seleccione…</option>';

            for (const t of _tipos) {
               const opt = document.createElement('option');
               opt.value = t.clave;
               opt.textContent = t.nombre;
               opt.dataset.id = t.id;
               opt.dataset.maxMb = t.max_mb ?? '';

               let exts = [];
               try {
                  if (Array.isArray(t.acepta_ext)) exts = t.acepta_ext;
                  else if (typeof t.acepta_ext === 'string' && t.acepta_ext.trim().startsWith('[')) exts = JSON.parse(t.acepta_ext);
                  else if (typeof t.acepta_ext === 'string') exts = t.acepta_ext.split(',').map(s => s.trim());
               } catch { exts = []; }

               opt.dataset.accept = exts.join(',');
               expTipoSel?.appendChild(opt);

               _tipoByClave.set(t.clave, { id: t.id, clave: t.clave, nombre: t.nombre, max_mb: t.max_mb, acepta_ext: exts });
            }
         } catch (err) {
            handleApiError(err, 'No se pudo cargar tipos de documento');
         }
      }

      // ---- AG Grid (v29.x compatible) ----
      const columnDefs = [
         { headerName: '#', valueGetter: 'node.rowIndex + 1', width: 70 },
         { headerName: 'Cliente', field: 'cliente_grupo', flex: 1 },
         { headerName: 'Nombre', field: 'nombre', flex: 1.2, minWidth: 180 },
         { headerName: 'RFC', field: 'rfc', width: 160, cellRenderer: p => `<code>${p.value || ''}</code>` },
         { headerName: 'Tipo', field: 'tipo_persona', width: 110 },
         { headerName: 'Área', field: 'area_id', width: 100 },
         { headerName: 'Responsable', field: 'responsable_id', width: 120 },
         { headerName: 'Activo', field: 'activo', width: 100, valueFormatter: p => p.value ? 'Sí' : 'No' },
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
         onRowClicked: (e) => {
            const wasSelected = e.node.isSelected();
            e.node.setSelected(!wasSelected, true);
            updateActionButtons();
         },
         onSelectionChanged: () => updateActionButtons(),
      };

      const gridApiOrInstance = (typeof agGrid.Grid === 'function')
         ? new agGrid.Grid(gridEl, gridOptions)
         : agGrid.createGrid?.(gridEl, gridOptions);

      function getSelectedRow() {
         const api = gridOptions.api || gridApiOrInstance?.api || gridApiOrInstance;
         const sel = api?.getSelectedRows?.();
         return (sel && sel[0]) ? sel[0] : null;
      }
      // Exportar helpers para el módulo de Obligaciones
      window.__emp_getSelected = getSelectedRow;
      window.__emp_toast = toast;
      window.__emp_canPerm = canPerm;
      window.__emp_fillSelect = fillSelect;
      // catálogos para selects del drawer
      Object.defineProperty(window, '__emp_catalogs', {
         get() { return { areas: _areas, jefes: _jefes }; }
      });


      // ===== Permisos =====
      const canPerm = (p) => window.__canPerm ? window.__canPerm(p) : Promise.resolve(false);

      // ---- Carga de datos ----
      async function loadData() {
         try {
            AppLoader?.show('Cargando empresas…');
            const params = new URLSearchParams({ page: '1', size: '200' });
            const q = (fQ?.value || '').trim();
            const activo = (fActivo?.value ?? '');
            const areaId = (fArea?.value || '');

            if (q) params.set('q', q);
            if (activo !== '') params.set('activo', activo);
            if (areaId) params.set('area_id', areaId);

            const j = await Api.get('/api/v1/empresas?' + params.toString());
            const rows = j.data || [];

            const api = gridOptions.api || gridApiOrInstance?.api || gridApiOrInstance;
            api?.setRowData?.(rows);
            api?.deselectAll?.();

            updateActionButtons();
            toast('info', `Empresas cargadas: ${rows.length}`);
         } catch (err) {
            handleApiError(err, 'No se pudo cargar empresas');
         } finally {
            AppLoader?.hide();
         }
      }

      // ---- Modal de Empresa ----
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
         modalEl.classList.add('show');
         modalEl.style.display = 'block';
         modalEl.removeAttribute('aria-hidden');
         modalEl.setAttribute('aria-modal', 'true');
         if (!document.querySelector('.modal-backdrop')) {
            const bd = document.createElement('div');
            bd.className = 'modal-backdrop fade show';
            document.body.appendChild(bd);
         }
         document.body.classList.add('modal-open');
         modalIsOpen = true;
      }
      function hideModal() {
         modalEl.classList.remove('show');
         modalEl.style.display = 'none';
         modalEl.setAttribute('aria-hidden', 'true');
         modalEl.removeAttribute('aria-modal');
         document.querySelector('.modal-backdrop')?.remove();
         document.body.classList.remove('modal-open');
         modalIsOpen = false;
      }
      modalEl?.querySelector('[data-dismiss="modal"], .close')?.addEventListener('click', (e) => {
         e.preventDefault();
         hideModal();
      });
      document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && modalIsOpen) hideModal(); });

      async function openCreate() {
         const ok = await canPerm('empresa.crear');
         if (!ok) { toast('error', 'No tienes permiso para crear'); return; }
         resetForm();
         modalTitle.textContent = 'Nueva empresa';
         showModal();
      }
      async function openEdit(row) {
         if (!row) return;
         const ok = await canPerm('empresa.editar');
         if (!ok) { toast('error', 'No tienes permiso para editar'); return; }

         resetForm();
         modalTitle.textContent = `Editar empresa #${row.id}`;
         let data = { ...row };

         if (USE_DETAIL_FALLBACK) {
            try {
               AppLoader?.show('Cargando detalle…');
               const det = await Api.get('/api/v1/empresas/show?id=' + encodeURIComponent(row.id));
               if (det?.ok && det.data) data = det.data;
            } catch (err) {
               handleApiError(err, 'No se pudo cargar el detalle de la empresa');
            } finally {
               AppLoader?.hide();
            }
         }

         fId.value = data.id ?? '';
         fCli.value = data.cliente_grupo ?? '';
         fNom.value = data.nombre ?? '';
         fRfc.value = (data.rfc ?? '').toUpperCase();
         fTipo.value = data.tipo_persona ?? 'FISICA';
         fAreaId.value = (data.area_id ?? '').toString();
         fRespId.value = (data.responsable_id ?? '').toString();
         fActv.value = data.activo ? '1' : '0';

         fCont.value = data.contrato_servicios ?? '';
         fNFac.value = data.nombre_facturacion ?? '';
         fCFact.value = data.correo_facturacion ?? '';
         fTFact.value = data.telefono_facturacion ?? '';
         fReg.value = data.tipo_regimen ?? '';
         fAct.value = data.actividad_principal ?? '';
         fEdoDom.value = data.estatus_domicilio ?? 'LOCALIZADO';

         showModal();
      }

      // ---- Guardar (create/update) ----
      btnSave?.addEventListener('click', async () => {
         const payload = {
            id: fId?.value ? parseInt(fId.value, 10) : undefined,
            cliente_grupo: (fCli?.value || '').trim(),
            nombre: (fNom?.value || '').trim(),
            rfc: (fRfc?.value || '').trim().toUpperCase(),
            tipo_persona: fTipo?.value || 'FISICA',
            area_id: fAreaId?.value ? parseInt(fAreaId.value, 10) : null,
            responsable_id: fRespId?.value ? parseInt(fRespId.value, 10) : null,
            activo: fActv?.value ? parseInt(fActv.value, 10) : 1,
            contrato_servicios: (fCont?.value || '').trim(),
            nombre_facturacion: (fNFac?.value || '').trim(),
            telefono_facturacion: (fTFact?.value || '').trim(),
            correo_facturacion: (fCFact?.value || '').trim(),
            tipo_regimen: (fReg?.value || '').trim(),
            actividad_principal: (fAct?.value || '').trim(),
            estatus_domicilio: fEdoDom?.value || 'LOCALIZADO'
         };

         if (!payload.nombre || !payload.rfc) { toast('warning', 'Nombre y RFC son requeridos'); return; }

         try {
            AppLoader?.show('Guardando…');
            if (!payload.id) {
               const ok = await canPerm('empresa.crear');
               if (!ok) { toast('error', 'No tienes permiso para crear'); return; }
               const j = await Api.post('/api/v1/empresas', payload);
               if (j.ok) { toast('success', 'Empresa creada'); hideModal(); await loadData(); }
               else toast('warning', j.error?.message || 'No se pudo crear la empresa');
            } else {
               const ok = await canPerm('empresa.editar');
               if (!ok) { toast('error', 'No tienes permiso para editar'); return; }
               const j = await Api.put('/api/v1/empresas', payload);
               if (j.ok) { toast('success', 'Empresa actualizada'); hideModal(); await loadData(); }
               else toast('warning', j.error?.message || 'No se pudo actualizar la empresa');
            }
         } catch (err) {
            handleApiError(err, 'Error al guardar empresa');
         } finally {
            AppLoader?.hide();
         }
      });

      // ---- Eliminar ----
      async function delEmpresa(row) {
         if (!row) { toast('warning', 'Selecciona una empresa'); return; }
         const ok = await canPerm('empresa.borrar');
         if (!ok) { toast('error', 'No tienes permiso para eliminar'); return; }
         if (!window.confirm('¿Eliminar (baja lógica) esta empresa?')) return;

         try {
            AppLoader?.show('Eliminando…');
            const j = await Api.del('/api/v1/empresas?id=' + encodeURIComponent(row.id));
            if (j.ok) { toast('success', 'Empresa eliminada'); await loadData(); }
            else toast('warning', j.error?.message || 'No se pudo eliminar la empresa');
         } catch (err) {
            handleApiError(err, 'Error al eliminar empresa');
         } finally {
            AppLoader?.hide();
         }
      }

      // ===================== EXPEDIENTE =====================
      function buildTipoOptionsHtml() {
         return `<option value="">— Selecciona tipo —</option>` + _tipos
            .map(t => `<option value="${t.clave}">${t.nombre} (${t.clave})</option>`)
            .join('');
      }
      const fileQueue = new Map();
      let _uidSeq = 1;
      const genUid = () => `f_${Date.now()}_${_uidSeq++}`;

      function bytesToSize(n) {
         if (!n && n !== 0) return '';
         const kb = 1024, mb = kb * 1024;
         if (n >= mb) return (n / mb).toFixed(2) + ' MB';
         if (n >= kb) return (n / kb).toFixed(2) + ' KB';
         return n + ' B';
      }
      function isExtAllowedForType(ext, tipoClave) {
         const t = _tipoByClave.get(tipoClave);
         if (!t) return true;
         const list = (t.acepta_ext || []).map(s => String(s).replace(/^\./, '').toLowerCase());
         return list.length ? list.includes(ext.toLowerCase()) : true;
      }
      function renderQueue() {
         if (!expFilesTbody) return;
         expFilesTbody.innerHTML = '';
         let idx = 1;
         for (const [uid, rec] of fileQueue.entries()) {
            const f = rec.file;
            const ext = (f.name.split('.').pop() || '').toUpperCase();
            const tr = document.createElement('tr');
            tr.dataset.uid = uid;
            tr.innerHTML = `
          <td class="text-muted">${idx++}</td>
          <td>
            <span class="badge bg-secondary me-2">${ext || 'FILE'}</span>
            <span class="text-break">${f.name}</span>
          </td>
          <td>${bytesToSize(f.size)}</td>
          <td>
            <select class="form-select form-select-sm sel-tipo">${buildTipoOptionsHtml()}</select>
          </td>
          <td class="text-end">
            <button class="btn btn-sm btn-outline-danger btn-del" title="Quitar">
              <i class="fas fa-times"></i>
            </button>
          </td>`;
            expFilesTbody.appendChild(tr);
            const sel = tr.querySelector('.sel-tipo');
            if (sel) {
               sel.value = rec.tipo_clave || '';
               sel.addEventListener('change', () => {
                  rec.tipo_clave = sel.value || '';
               });
            }
            tr.querySelector('.btn-del')?.addEventListener('click', () => {
               fileQueue.delete(uid);
               renderQueue();
            });
         }
      }
      expFilesInput?.addEventListener('change', (e) => {
         const files = Array.from(e.target.files || []);
         if (!files.length) return;
         for (const f of files) {
            const uid = genUid();
            fileQueue.set(uid, { file: f, tipo_clave: '' });
         }
         expFilesInput.value = '';
         renderQueue();
      });

      window.__expedienteQueueUpload = async function () {
         const empresaId = parseInt(expEmpresaId?.value || '0', 10);
         if (!empresaId) return toast('warning', 'Selecciona una empresa');
         const okExp = await canPerm('empresa.expediente');
         if (!okExp) { toast('error', 'No tienes permiso para expediente'); return; }

         const missingTipo = [];
         for (const [, rec] of fileQueue.entries()) {
            if (!rec.tipo_clave) missingTipo.push(rec.file.name);
         }
         if (missingTipo.length) {
            toast('warning', `Selecciona tipo para: ${missingTipo.slice(0, 3).join(', ')}${missingTipo.length > 3 ? '…' : ''}`);
            return;
         }
         for (const [, rec] of fileQueue.entries()) {
            const ext = (rec.file.name.split('.').pop() || '').toLowerCase();
            if (!isExtAllowedForType(ext, rec.tipo_clave)) {
               const t = _tipoByClave.get(rec.tipo_clave);
               const lista = (t?.acepta_ext || []).join(', ');
               toast('error', `.${ext} no permitido para tipo ${rec.tipo_clave}. Permitidas: ${lista}`);
               return;
            }
         }
         const csrf = await getCsrf();
         const headers = buildCsrfHeaders(csrf);

         AppLoader?.show('Subiendo archivos…');
         let ok = 0, fail = 0;
         for (const [uid, rec] of fileQueue.entries()) {
            const fd = new FormData();
            fd.append('empresa_id', String(empresaId));
            fd.append('tipo_clave', rec.tipo_clave);
            fd.append('file', rec.file, rec.file.name);
            try {
               const resp = await fetch('/api/v1/empresas/expediente', {
                  method: 'POST',
                  credentials: 'same-origin',
                  headers,
                  body: fd
               });
               if (!resp.ok) {
                  let msg = 'Error al subir archivo';
                  try { const j = await resp.json(); msg = j?.error?.message || msg; } catch { }
                  toast('error', `${rec.file.name}: ${msg}`);
                  fail++; continue;
               }
               ok++;
               fileQueue.delete(uid);
               const tr = expFilesTbody?.querySelector(`tr[data-uid="${uid}"]`);
               tr?.remove();

               const selClave = expTipoSel?.value || '';
               if (selClave && selClave === rec.tipo_clave) {
                  const opt = expTipoSel?.selectedOptions?.[0];
                  const tipoId = opt ? parseInt(opt.dataset.id || '0', 10) : 0;
                  if (empresaId && tipoId) await loadLatestVersions(empresaId, tipoId, 5, false);
               }
            } catch (e) {
               console.error(e);
               toast('error', `${rec.file.name}: Error de red`);
               fail++;
            }
         }
         AppLoader?.hide();
         if (ok && !fail) toast('success', `Subida completa (${ok})`);
         else if (ok && fail) toast('warning', `Subidos ${ok}, fallaron ${fail}`);
         else toast('error', 'No se subió ningún archivo');

         renderQueue();
      };

      async function loadLatestVersions(empresaId, tipoId, limit = 5, showAll = false) {
         try {
            AppLoader?.show('Cargando versiones…');
            const params = new URLSearchParams({ empresa_id: String(empresaId), tipo_id: String(tipoId) });
            if (!showAll && limit) params.set('limit', String(limit));
            const j = await Api.get('/api/v1/empresas/expediente/versions?' + params.toString());
            const rows = j?.data || [];
            renderLastList(rows);
         } catch (err) {
            handleApiError(err, 'No se pudieron cargar versiones');
         } finally {
            AppLoader?.hide();
         }
      }
      function renderLastList(rows) {
         if (!expLastWrap || !expLastBody) return;
         expLastBody.innerHTML = '';
         if (!rows || rows.length === 0) {
            expLastWrap.style.display = 'none';
            return;
         }
         expLastWrap.style.display = '';
         for (const r of rows) {
            const tr = document.createElement('tr');
            const fecha = r.creado_en || r.subido_en || '';
            tr.innerHTML = `
          <td>${r.version ?? ''}</td>
          <td>${r.archivo_nombre ?? ''}</td>
          <td><small>${fecha}</small></td>
          <td class="text-right">
            <button class="btn btn-xs btn-outline-primary" data-act="exp-dl" data-id="${r.id}"><i class="fas fa-download"></i></button>
            <a class="btn btn-xs btn-outline-secondary" href="${(r.archivo_path || '#')}" target="_blank" rel="noopener" title="Abrir"><i class="fas fa-external-link-alt"></i></a>
          </td>`;
            expLastBody.appendChild(tr);
         }
      }
      expLastBody?.addEventListener('click', (e) => {
         const btn = e.target.closest('button[data-act="exp-dl"]');
         if (!btn) return;
         const docId = parseInt(btn.getAttribute('data-id') || '0', 10);
         const empresaId = parseInt(expEmpresaId.value || '0', 10);
         if (!empresaId || !docId) return;
         window.open(`/api/v1/empresas/expediente/download?empresa_id=${empresaId}&doc_id=${docId}&stream=1`, '_blank');
      });
      expTipoSel?.addEventListener('change', () => {
         const empresaId = parseInt(expEmpresaId?.value || '0', 10);
         const opt = expTipoSel?.selectedOptions?.[0];
         const tipoId = opt ? parseInt(opt.dataset.id || '0', 10) : 0;
         if (empresaId && tipoId) loadLatestVersions(empresaId, tipoId, 5, false);
      });

      // ---- Toolbar: estado según selección + permisos ----
      async function updateActionButtons() {
         const selected = getSelectedRow();
         const [canCreate, canEdit, canDelete, canExp, canOblVer] = await Promise.all([
            canPerm('empresa.crear'),
            canPerm('empresa.editar'),
            canPerm('empresa.borrar'),
            canPerm('empresa.expediente'),
            canPerm('empresa.obligacion.ver'),
         ]);

         if (selected) {
            btnNew?.classList.remove('btn-success');
            btnNew?.classList.add('btn-warning');
            if (btnNew) {
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

            // Obligaciones: habilitado SOLO con selección + permiso ver
            setEnabled(btnObl, canOblVer === true);
         } else {
            btnNew?.classList.remove('btn-warning');
            btnNew?.classList.add('btn-success');
            if (btnNew) {
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

            // Obligaciones: deshabilitado sin selección
            setEnabled(btnObl, false);
         }
      }

      // ---- Eventos UI ----
      btnSearch?.addEventListener('click', loadData);
      btnNew?.addEventListener('click', async (e) => {
         e.preventDefault();
         const selected = getSelectedRow();
         if (selected) await openEdit(selected);
         else await openCreate();
      });
      btnEdit?.addEventListener('click', async (e) => {
         e.preventDefault();
         const selected = getSelectedRow();
         await openEdit(selected);
      });
      btnDelete?.addEventListener('click', async () => {
         const selected = getSelectedRow();
         await delEmpresa(selected);
      });
      btnExp?.addEventListener('click', async () => {
         const row = getSelectedRow();
         if (!row) { toast('warning', 'Selecciona una empresa'); return; }
         // abrir modal expediente
         const ok = await canPerm('empresa.expediente');
         if (!ok) { toast('error', 'No tienes permiso para expediente'); return; }

         expEmpresaId.value = String(row.id);
         expEmpresaNm.value = row.nombre ?? '';
         if (expEmpresaTitle) expEmpresaTitle.textContent = `Expediente de: ${row.nombre ?? '(Sin nombre)'} (ID ${row.id})`;

         if (expTipoSel) expTipoSel.value = '';
         // limpiar lista versiones y cola
         expLastBody.innerHTML = '';
         fileQueue.clear();
         renderQueue();

         if (!_tipos || _tipos.length === 0) await loadTiposDocumento();

         // mostrar modal
         if (!modalExpEl) return;
         modalExpEl.classList.add('show');
         modalExpEl.style.display = 'block';
         modalExpEl.removeAttribute('aria-hidden');
         modalExpEl.setAttribute('aria-modal', 'true');
         if (!document.querySelector('.modal-backdrop')) {
            const bd = document.createElement('div');
            bd.className = 'modal-backdrop fade show';
            document.body.appendChild(bd);
         }
         document.body.classList.add('modal-open');
      });
      document.addEventListener('keydown', (e) => {
         if (e.key === 'Escape' && modalExpEl?.classList.contains('show')) {
            modalExpEl.classList.remove('show');
            modalExpEl.style.display = 'none';
            modalExpEl.setAttribute('aria-hidden', 'true');
            modalExpEl.removeAttribute('aria-modal');
            document.querySelector('.modal-backdrop')?.remove();
            document.body.classList.remove('modal-open');
         }
      });

      fQ?.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); loadData(); } });

      // ---- Primera carga ----
      (async () => {
         await loadCatalogs();
         await loadTiposDocumento();
         await loadData();
         if (window.__applyGates) window.__applyGates(document);
         updateActionButtons();
      })();
   }

   if (document.readyState === 'loading') window.addEventListener('DOMContentLoaded', init);
   else init();
})();


// ==================== OBLIGACIONES (Drawer) ====================
(function ObligacionesModule() {
   const toast = window.__emp_toast || ((t, m) => (t === 'error' ? console.error(m) : console.log(m)));

   const openBtn = document.getElementById('emp-btn-oblig');
   const drawer = document.getElementById('oblig-drawer');
   const closeBtn = document.getElementById('oblig-close');
   const lblEmpresa = document.getElementById('oblig-empresa-label');

   const inpSearch = document.getElementById('oblig-search');
   const countCat = document.getElementById('oblig-count-cat');
   const countAsg = document.getElementById('oblig-count-asg');
   const btnRefresh = document.getElementById('oblig-refresh');

   const treeEl = document.getElementById('oblig-tree');
   const asgTbody = document.getElementById('oblig-asignadas-tbody');

   // Form detalle
   const fId = document.getElementById('oblig-form-id');
   const fEmpId = document.getElementById('oblig-form-empresa_id');
   const fOblId = document.getElementById('oblig-form-obligacion_id');
   const fTitle = document.getElementById('oblig-form-title');
   const fResumen = document.getElementById('oblig-form-obligacion-resumen');

   const fPer = document.getElementById('oblig-form-periodicidad');
   const fTipoD = document.getElementById('oblig-form-tipo_dias');
   const fDia = document.getElementById('oblig-form-dia_venc');
   const fOff = document.getElementById('oblig-form-offset');
   const fAct = document.getElementById('oblig-form-activo');
   const fIni = document.getElementById('oblig-form-inicio');
   const fFin = document.getElementById('oblig-form-fin');
   const fResp = document.getElementById('oblig-form-responsable');
   const fArea = document.getElementById('oblig-form-area');
   const fNotas = document.getElementById('oblig-form-notas');

   const btnAsignar = document.getElementById('oblig-btn-asignar');
   const btnGuardar = document.getElementById('oblig-btn-guardar');
   const btnQuitar = document.getElementById('oblig-btn-quitar');

   if (!openBtn || !drawer) return; // no está esta UI en la vista

   // estado
   let _catalogo = [];
   let _asignadas = [];
   let _empresaSel = null;
   let _tree = null;

   const canPerm = (p) => window.__canPerm ? window.__canPerm(p) : Promise.resolve(false);

   function openDrawer() {
      drawer.style.transform = 'translateX(0)';
      drawer.setAttribute('aria-hidden', 'false');
   }
   function closeDrawer() {
      drawer.style.transform = 'translateX(100%)';
      drawer.setAttribute('aria-hidden', 'true');
   }
   closeBtn?.addEventListener('click', closeDrawer);
   document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && drawer.getAttribute('aria-hidden') === 'false') closeDrawer();
   });

   // selects de responsable / área usando catálogos globales o fallback
   async function ensureFormCatalogs() {
      const fillSelect = window.__emp_fillSelect;
      let areas = window.__emp_areas;
      let jefes = window.__emp_jefes;
      if (!areas || !jefes) {
         try {
            const [jAreas, jJefes] = await Promise.all([
               Api.get('/api/v1/catalogos/areas'),
               Api.get('/api/v1/catalogos/jefes'),
            ]);
            areas = jAreas?.data || [];
            jefes = jJefes?.data || [];
         } catch { areas = []; jefes = []; }
      }
      if (typeof fillSelect === 'function') {
         fillSelect(fResp, jefes, 'id', 'nombre', { includeEmpty: true, emptyText: '(sin responsable)' });
         fillSelect(fArea, areas, 'id', 'nombre', { includeEmpty: true, emptyText: '(sin área)' });
      }
   }

   async function loadCatalogo() {
      if (_catalogo.length) return _catalogo;
      try {
         AppLoader?.show('Cargando catálogo de obligaciones…');
         const j = await Api.get('/api/v1/catalogos/obligaciones');
         _catalogo = j?.data || [];
         if (countCat) countCat.textContent = String(_catalogo.length);
      } catch (err) {
         toast('error', 'No se pudo cargar el catálogo de obligaciones');
         console.error(err);
      } finally {
         AppLoader?.hide();
      }
      return _catalogo;
   }
   async function loadAsignadas(empresaId) {
      try {
         AppLoader?.show('Cargando obligaciones asignadas…');
         const j = await Api.get('/api/v1/empresas/obligaciones?empresa_id=' + encodeURIComponent(empresaId));
         _asignadas = j?.data || [];
         if (countAsg) countAsg.textContent = String(_asignadas.length);
         renderAsignadas();
         markAssignedOnTree();
      } catch (err) {
         toast('error', 'No se pudieron cargar las asignadas');
         console.error(err);
      } finally {
         AppLoader?.hide();
      }
   }

   function groupByOrganismo(items) {
      const map = new Map();
      for (const it of items) {
         const org = (it.organismo || 'OTROS').toString();
         if (!map.has(org)) map.set(org, []);
         map.get(org).push(it);
      }
      return map;
   }
   function buildTreeSource(items) {
      const byOrg = groupByOrganismo(items);
      const res = [];
      for (const [org, list] of byOrg.entries()) {
         res.push({
            title: org,
            folder: true,
            expanded: false,
            extraClasses: 'text-info',
            children: list.map(o => ({
               key: String(o.id),
               title: `${o.descripcion} <span class="badge bg-secondary ms-2">${o.clave}</span>${o.activo ? '' : ' <span class="badge bg-danger ms-1">inactiva</span>'}`,
               tooltip: `${o.organismo || ''} • ${o.clave}`,
               extraClasses: o.activo ? '' : 'text-muted',
               data: o
            }))
         });
      }
      return res.sort((a, b) => a.title.localeCompare(b.title));
   }
   function initTree() {
      if (!window.jQuery || !jQuery.fn.fancytree) {
         console.warn('FancyTree no disponible');
         return;
      }
      jQuery(treeEl).fancytree({
         extensions: ['filter'],
         quicksearch: true,
         filter: { autoExpand: true, highlight: true, mode: 'hide' },
         source: buildTreeSource(_catalogo),
         activate: (ev, data) => {
            const node = data.node;
            if (!node || node.folder) return;
            const obl = node.data;
            const empresaId = _empresaSel?.id;
            if (!empresaId) return;

            const asg = _asignadas.find(x => x.obligacion_id === obl.id);
            if (asg) {
               fillFormFromAsignada(asg);
               modeEdit();
            } else {
               fillFormForNew(empresaId, obl);
               modeCreate();
            }
         }
      });
      // obtener instancia con API nueva (sin warning)
      _tree = $.ui.fancytree.getTree(treeEl);
   }

   function markAssignedOnTree() {
      if (!_tree) return;
      const assignedIds = new Set(_asignadas.map(x => String(x.obligacion_id)));

      // ANTES llamábamos _tree.visit(...);  -> ahora visitamos desde la raíz:
      const root = _tree.getRootNode();
      root.visit(node => {
         if (node.folder) return;
         node.extraClasses = assignedIds.has(node.key)
            ? 'fw-bold'
            : (node.data?.activo ? '' : 'text-muted');
         node.renderTitle();
      });
   }

   function filterTree(text) {
      if (!_tree) return;
      const match = text?.trim();
      if (!match) {
         _tree.clearFilter();
         _tree.visit(n => n.setExpanded(false));
         return;
      }
      const re = new RegExp(match.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'i');
      _tree.filterNodes((node) => {
         if (node.folder) return re.test(node.title);
         const d = node.data || {};
         return re.test(d.clave || '') || re.test(d.descripcion || '') || re.test(node.title || '');
      }, { autoExpand: true });
   }
   inpSearch?.addEventListener('input', (e) => filterTree(e.target.value || ''));

   function renderAsignadas() {
      if (!asgTbody) return;
      asgTbody.innerHTML = '';
      if (!_asignadas.length) {
         const tr = document.createElement('tr');
         tr.innerHTML = `<td colspan="3" class="text-center text-muted small py-3">Sin obligaciones asignadas</td>`;
         asgTbody.appendChild(tr);
         return;
      }
      for (const r of _asignadas) {
         const tr = document.createElement('tr');
         const per = r.periodicidad || '';
         const tag = r.activo ? '<span class="badge bg-success ms-1">activa</span>' : '<span class="badge bg-secondary ms-1">inactiva</span>';
         tr.innerHTML = `
        <td>
          <div class="small fw-bold">${r.obligacion?.descripcion || ''}</div>
          <div class="text-muted small">${r.obligacion?.organismo || ''} • <code>${r.obligacion?.clave || ''}</code> ${tag}</div>
        </td>
        <td>${per}</td>
        <td class="text-end">
          <button class="btn btn-xs btn-outline-light" data-act="pick" title="Editar"><i class="fas fa-pen"></i></button>
        </td>`;
         tr.querySelector('[data-act="pick"]')?.addEventListener('click', () => {
            fillFormFromAsignada(r);
            modeEdit();
            if (_tree) {
               const node = _tree.getNodeByKey(String(r.obligacion_id));
               node?.makeVisible();
               node?.setActive();
            }
         });
         asgTbody.appendChild(tr);
      }
   }

   function modeCreate() {
      fTitle.textContent = 'Asignar obligación';
      fId.value = '';
      btnAsignar.style.display = '';
      btnGuardar.style.display = 'none';
      btnQuitar.style.display = 'none';
      applyGates();
   }
   function modeEdit() {
      fTitle.textContent = 'Editar asignación';
      btnAsignar.style.display = 'none';
      btnGuardar.style.display = '';
      btnQuitar.style.display = '';
      applyGates();
   }
   function fillFormForNew(empresaId, obl) {
      fEmpId.value = empresaId;
      fOblId.value = obl.id;
      fResumen.innerHTML = `${obl.descripcion} <span class="badge bg-secondary ms-2">${obl.clave}</span> <span class="text-muted">• ${obl.organismo || '—'}</span>`;
      fPer.value = 'MENSUAL';
      fTipoD.value = 'NATURALES';
      fDia.value = '';
      fOff.value = '0';
      fAct.value = '1';
      const today = new Date();
      fIni.value = today.toISOString().slice(0, 10);
      fFin.value = '';
      fResp.value = '';
      fArea.value = '';
      fNotas.value = '';
   }
   function fillFormFromAsignada(asg) {
      fId.value = asg.id || '';
      fEmpId.value = asg.empresa_id || '';
      fOblId.value = asg.obligacion_id || '';
      const o = asg.obligacion || {};
      fResumen.innerHTML = `${o.descripcion || ''} <span class="badge bg-secondary ms-2">${o.clave || ''}</span> <span class="text-muted">• ${o.organismo || '—'}</span>`;

      fPer.value = asg.periodicidad || 'MENSUAL';
      fTipoD.value = asg.tipo_dias || 'NATURALES';
      fDia.value = (asg.dia_vencimiento ?? '') === null ? '' : String(asg.dia_vencimiento);
      fOff.value = String(asg.offset_dias ?? 0);
      fAct.value = String(asg.activo ? 1 : 0);
      fIni.value = asg.fecha_inicio || '';
      fFin.value = asg.fecha_fin || '';
      fResp.value = asg.responsable_id ? String(asg.responsable_id) : '';
      fArea.value = asg.area_id ? String(asg.area_id) : '';
      fNotas.value = asg.notas || '';
   }

   function validateForm(forCreate) {
      const empresaId = parseInt(fEmpId.value || '0', 10);
      const oblId = parseInt(fOblId.value || '0', 10);
      const per = fPer.value;
      const tipoD = fTipoD.value;
      const dia = fDia.value ? parseInt(fDia.value, 10) : null;
      const off = fOff.value ? parseInt(fOff.value, 10) : 0;
      const ini = fIni.value || '';
      const fin = fFin.value || '';

      if (empresaId <= 0 || oblId <= 0) { toast('warning', 'Empresa y obligación son requeridos'); return null; }
      if (!ini) { toast('warning', 'Fecha inicio es requerida'); return null; }
      if (fin && fin < ini) { toast('warning', 'Fecha fin no puede ser menor que inicio'); return null; }
      if (per !== 'EVENTUAL') {
         if (!dia || dia < 1 || dia > 31) { toast('warning', 'Día de vencimiento debe estar entre 1 y 31'); return null; }
      }
      const payload = {
         empresa_id: empresaId,
         obligacion_id: oblId,
         periodicidad: per,
         tipo_dias: tipoD,
         dia_vencimiento: per === 'EVENTUAL' ? null : dia,
         offset_dias: off ?? 0,
         fecha_inicio: ini,
         fecha_fin: fin || null,
         responsable_id: fResp.value ? parseInt(fResp.value, 10) : null,
         area_id: fArea.value ? parseInt(fArea.value, 10) : null,
         activo: fAct.value ? parseInt(fAct.value, 10) : 1,
         notas: (fNotas.value || '').trim() || null
      };
      if (!forCreate) {
         const id = parseInt(fId.value || '0', 10);
         if (!id) { toast('warning', 'ID de asignación inválido'); return null; }
         payload.id = id;
      }
      return payload;
   }

   btnAsignar?.addEventListener('click', async () => {
      const can = await canPerm('empresa.obligacion.asignar');
      if (!can) { toast('error', 'No tienes permiso para asignar'); return; }
      const data = validateForm(true);
      if (!data) return;
      try {
         AppLoader?.show('Asignando obligación…');
         const j = await Api.post('/api/v1/empresas/obligaciones', data);
         if (j?.ok) {
            toast('success', 'Obligación asignada');
            await loadAsignadas(data.empresa_id);
            const found = _asignadas.find(x => x.obligacion_id === data.obligacion_id);
            if (found) fillFormFromAsignada(found);
            modeEdit();
         } else {
            toast('warning', 'No se pudo asignar la obligación');
         }
      } catch (err) {
         console.error(err);
         toast('error', err?.message || 'Error al asignar');
      } finally {
         AppLoader?.hide();
      }
   });

   btnGuardar?.addEventListener('click', async () => {
      const can = await canPerm('empresa.obligacion.editar');
      if (!can) { toast('error', 'No tienes permiso para editar'); return; }
      const data = validateForm(false);
      if (!data) return;
      try {
         AppLoader?.show('Guardando cambios…');
         const j = await Api.put('/api/v1/empresas/obligaciones', data);
         if (j?.ok) {
            toast('success', 'Asignación actualizada');
            await loadAsignadas(data.empresa_id);
         } else {
            toast('warning', 'No se pudo actualizar la asignación');
         }
      } catch (err) {
         console.error(err);
         toast('error', err?.message || 'Error al guardar');
      } finally {
         AppLoader?.hide();
      }
   });

   btnQuitar?.addEventListener('click', async () => {
      const can = await canPerm('empresa.obligacion.borrar');
      if (!can) { toast('error', 'No tienes permiso para desasignar'); return; }
      const id = parseInt(fId.value || '0', 10);
      const empId = parseInt(fEmpId.value || '0', 10);
      if (!id || !empId) return;
      if (!window.confirm('¿Desasignar esta obligación?')) return;
      try {
         AppLoader?.show('Desasignando…');
         const j = await Api.del('/api/v1/empresas/obligaciones?id=' + encodeURIComponent(id));
         if (j?.ok) {
            toast('success', 'Obligación desasignada');
            await loadAsignadas(empId);
            const node = _tree?.getActiveNode();
            if (node && !node.folder) {
               fillFormForNew(empId, node.data);
               modeCreate();
            } else {
               fId.value = ''; fOblId.value = ''; fResumen.textContent = '—';
               modeCreate();
            }
         } else {
            toast('warning', 'No se pudo desasignar');
         }
      } catch (err) {
         console.error(err);
         toast('error', err?.message || 'Error al desasignar');
      } finally {
         AppLoader?.hide();
      }
   });

   btnRefresh?.addEventListener('click', async () => {
      if (!_empresaSel?.id) return;
      await loadAsignadas(_empresaSel.id);
   });

   // Abrir drawer desde toolbar
   openBtn.addEventListener('click', async () => {
      const getSel = window.__emp_getSelectedRow;
      const row = typeof getSel === 'function' ? getSel() : null;
      if (!row) { toast('warning', 'Selecciona una empresa'); return; }

      const canVer = await canPerm('empresa.obligacion.ver');
      if (!canVer) { toast('error', 'No tienes permiso para ver obligaciones'); return; }

      _empresaSel = row;
      if (lblEmpresa) lblEmpresa.textContent = `${row.nombre || '(Sin nombre)'} • ID ${row.id}`;
      fEmpId.value = String(row.id);

      await ensureFormCatalogs();
      openDrawer();

      await loadCatalogo();
      if (!_tree) initTree(); else {
         _tree.reload(buildTreeSource(_catalogo));
      }
      await loadAsignadas(row.id);

      applyGates();
   });

   function applyGates() {
      if (window.__applyGates) window.__applyGates(drawer);
   }
})();
