// public/assets/js/users.js
(function () {
   // ===== Config =====
   const USE_DETAIL_FALLBACK = true; // si no viene roles_ids en la fila, hace GET /api/v1/admin/usuarios/{id}

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

   // Manejo central de errores de tu capa Api (que lanza err con .status/.code)
   function handleApiError(err, fallbackMsg = 'Error al procesar la solicitud') {
      console.error(err);
      const status = err?.status ?? err?.code;
      if (status === 401) toast('warning', 'Sesión expirada o no autenticado (401).');
      else if (status === 403 || status === 'FORBIDDEN') {
         const missing = err?.payload?.error?.message || '';
         toast('error', `No cuentas con permisos para realizar esta acción (403). ${missing}`);
      } else if (status === 404) toast('warning', 'Recurso no encontrado (404).');
      else if (status === 409) toast('warning', 'Conflicto de datos (409).');
      else if (status === 422) toast('warning', 'Datos inválidos o incompletos (422).');
      else toast('error', `${fallbackMsg}${err?.message ? `: ${err.message}` : ''}`);
   }

   // ===== Init =====
   function init() {
      if (typeof Api === 'undefined') { console.error('Api.js no cargado'); return; }
      if (typeof agGrid === 'undefined') { console.error('AG Grid no cargado'); return; }

      // ---- Helpers DOM ----
      const $ = (sel, ctx = document) => ctx.querySelector(sel);

      function setEnabled(el, enabled) {
         if (!el) return;
         el.disabled = !enabled;
         el.classList.toggle('disabled', !enabled);          // por si el gating dejó la clase
         if (enabled) el.removeAttribute('aria-disabled');
         else el.setAttribute('aria-disabled', 'true');
      }

      // ---- Contenedores / Controles ----
      const gridEl = $('#gridUsuarios');
      if (!gridEl) { console.error('Falta el contenedor del grid: #gridUsuarios'); return; }

      const fQ = $('#f-q');
      const fActivo = $('#f-activo');
      const btnSearch = $('#btn-search');
      const btnNew = $('#btn-new');       // Nuevo / Editar (dinámico)
      const btnDelete = $('#btn-delete'); // Eliminar
      const btnClear = $('#btn-clear');   // Limpiar selección (opcional si existe)

      // ---- Modal refs ----
      const modalEl = $('#user-modal');
      const modalTitle = $('#user-modal-title');
      const btnSave = $('#user-save');

      const fId = $('#u-id');
      const fNombre = $('#u-nombre');
      const fEmail = $('#u-email');
      const fArea = $('#u-area_id');
      const fJefe = $('#u-jefe_id');
      const fPwd = $('#u-password');
      const fPwd2 = $('#u-password2');
      const fRoles = $('#u-roles');
      const pwdGroup = $('#pwd-group');

      if (!modalEl || !modalTitle) { console.error('Falta el modal o su título'); return; }

      // ---- Catálogos ----
      let _areas = [], _jefes = [], _roles = [];
      let _catalogsLoaded = false;

      async function fetchCatalog(url) {
         const res = await Api.get(url);
         return res.data || [];
      }
      function fillSelect(selectEl, items, valueField, labelField, { includeEmpty, emptyText } = {}) {
         if (!selectEl) return;
         selectEl.innerHTML = '';
         if (includeEmpty) {
            const opt = document.createElement('option');
            opt.value = '';
            opt.textContent = emptyText || 'Seleccione…';
            selectEl.appendChild(opt);
         }
         for (const it of items) {
            const opt = document.createElement('option');
            opt.value = String(it[valueField] ?? '');
            opt.textContent = String(it[labelField] ?? '');
            selectEl.appendChild(opt);
         }
      }
      async function loadCatalogs() {
         [_areas, _jefes, _roles] = await Promise.all([
            fetchCatalog('/api/v1/catalogos/areas'),
            fetchCatalog('/api/v1/catalogos/jefes'),
            fetchCatalog('/api/v1/catalogos/roles'),
         ]);
         fillSelect(fArea, _areas, 'id', 'nombre', { includeEmpty: true, emptyText: 'Seleccione…' });
         fillSelect(fJefe, _jefes, 'id', 'nombre', { includeEmpty: true, emptyText: '(sin jefe)' });
         fillSelect(fRoles, _roles, 'id', 'nombre', { includeEmpty: false });
      }
      function setRolesSelected(selectEl, selectedIds = []) {
         if (!selectEl) return;
         const set = new Set((selectedIds || []).map(x => String(x)));
         for (const opt of selectEl.options) opt.selected = set.has(opt.value);
      }
      function getSelectedValues(selectEl) {
         return Array.from(selectEl?.selectedOptions || [])
            .map(o => parseInt(o.value, 10))
            .filter(Number.isInteger);
      }

      // ---- AG Grid ----
      const columnDefs = [
         { headerName: '#', valueGetter: 'node.rowIndex + 1', width: 70 },
         { headerName: 'Nombre', field: 'nombre', flex: 1 },
         { headerName: 'Email', field: 'email', flex: 1 },
         { headerName: 'Área', field: 'area_id', width: 100 },
         { headerName: 'Jefe', field: 'jefe_id', width: 100 },
         { headerName: 'Activo', field: 'activo', width: 100, valueFormatter: p => p.value ? 'Sí' : 'No' },
      ];

      const gridOptions = {
         columnDefs,
         rowData: [],
         animateRows: true,
         rowHeight: 42,
         // Nota: en AG Grid v29, normalmente rowSelection es 'single'/'multiple'.
         // Dejas este patrón si ya te funciona en tu versión.
         rowSelection: { mode: 'singleRow', enableClickSelection: false },
         onRowClicked: (e) => {
            e.node.setSelected(!e.node.isSelected(), true); // toggle; true = limpia otras filas
            updateActionButtons(); // async (no esperamos)
         },
         onSelectionChanged: () => updateActionButtons(), // async
         onRowDoubleClicked: () => { /* intencionalmente vacío */ },
      };

      const gridApi = (typeof agGrid.createGrid === 'function')
         ? agGrid.createGrid(gridEl, gridOptions)
         : new agGrid.Grid(gridEl, gridOptions);

      function getSelectedRow() {
         if (gridOptions.api?.getSelectedRows) {
            const sel = gridOptions.api.getSelectedRows();
            return (sel && sel[0]) ? sel[0] : null;
         }
         if (gridApi?.getSelectedRows) {
            const sel = gridApi.getSelectedRows?.();
            return (sel && sel[0]) ? sel[0] : null;
         }
         return null;
      }

      // ===== Permisos (helper) =====
      const canPerm = (p) => {
         if (window.__canPerm) return window.__canPerm(p);
         return Promise.resolve(false); // ⬅️ estricto
      };

      // ---- Botones estado según selección + permisos ----
      async function updateActionButtons() {
         const selected = getSelectedRow();

         // si tienes el helper global; si no, es permisivo (true)
         const canPerm = (p) => window.__canPerm ? window.__canPerm(p) : Promise.resolve(false);

         const canCreate = await canPerm('admin.users.crear');
         const canEdit = await canPerm('admin.users.editar');
         const canDelete = await canPerm('admin.users.borrar');

         if (selected) {
            // Modo EDITAR
            btnNew?.classList.remove('btn-success');
            btnNew?.classList.add('btn-warning');
            if (btnNew) btnNew.innerHTML = '<i class="fas fa-pen"></i> Editar';
            setEnabled(btnNew, !!canEdit);
            if (btnNew) btnNew.title = canEdit ? 'Editar usuario seleccionado' : 'No autorizado para editar';

            setEnabled(btnDelete, !!canDelete);
            if (btnDelete) btnDelete.title = canDelete ? 'Eliminar usuario seleccionado' : 'No autorizado para eliminar';
         } else {
            // Modo NUEVO
            btnNew?.classList.remove('btn-warning');
            btnNew?.classList.add('btn-success');
            if (btnNew) btnNew.innerHTML = '<i class="fas fa-plus"></i> Nuevo';
            setEnabled(btnNew, !!canCreate);
            if (btnNew) btnNew.title = canCreate ? 'Registrar nuevo usuario' : 'No autorizado para crear';

            // Sin selección no se debe borrar
            if (btnDelete) {
               setEnabled(btnDelete, false);
               btnDelete.title = 'Selecciona un usuario para eliminar';
            }
         }
      }

      // ---- Modal robusto ----
      let modalIsOpen = false;

      async function openModal(create = true, data = null) {
         // evitar doble apertura
         if (modalIsOpen) closeModal();

         // cargar catálogos una vez
         if (!_catalogsLoaded) {
            try { AppLoader?.show('Cargando catálogos…'); await loadCatalogs(); }
            finally { AppLoader?.hide(); _catalogsLoaded = true; }
         }

         if (create) {
            modalTitle.textContent = 'Nuevo usuario';
            fId.value = ''; fNombre.value = ''; fEmail.value = '';
            fArea.value = ''; fJefe.value = '';
            fPwd.value = ''; fPwd2.value = '';
            setRolesSelected(fRoles, []);
            if (pwdGroup) pwdGroup.style.display = '';
         } else if (data) {
            modalTitle.textContent = 'Editar usuario';
            fId.value = data.id ?? '';
            fNombre.value = data.nombre ?? '';
            fEmail.value = data.email ?? '';
            fArea.value = (data.area_id ?? '').toString();
            fJefe.value = (data.jefe_id ?? '').toString();
            setRolesSelected(fRoles, Array.isArray(data.roles_ids) ? data.roles_ids : []);
            fPwd.value = ''; fPwd2.value = '';
            if (pwdGroup) pwdGroup.style.display = 'none';
         }

         // mostrar modal (bootstrap manual)
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

      function closeModal() {
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
         closeModal();
      });
      document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && modalIsOpen) closeModal(); });

      // ---- Flujo centralizado: Nuevo/Editar segun selección + permisos ----
      async function openEdit(row) {
         if (!row) return;
         await openModal(false, row);

         // fallback para roles si no llegaron en la fila
         if (USE_DETAIL_FALLBACK && (!Array.isArray(row.roles_ids) || row.roles_ids.length === 0)) {
            try {
               AppLoader?.show('Cargando detalle…');
               const det = await Api.get(`/api/v1/admin/usuarios/${row.id}`);
               setRolesSelected(fRoles, det.data?.roles_ids || []);
            } catch (err) {
               handleApiError(err, 'No se pudo cargar el detalle de usuario');
            } finally {
               AppLoader?.hide();
            }
         }
      }

      async function openEditSelected() {
         const selected = getSelectedRow();
         if (selected) {
            const ok = await canPerm('admin.users.editar');
            if (!ok) { toast('error', 'No tienes permiso para editar'); return; }
            await openEdit(selected);
         } else {
            const ok = await canPerm('admin.users.crear');
            if (!ok) { toast('error', 'No tienes permiso para crear'); return; }
            await openModal(true);
         }
      }

      // ---- API Usuarios ----
      async function loadData() {
         try {
            AppLoader?.show('Cargando usuarios…');
            const params = new URLSearchParams({
               page: '1', size: '100',
               q: (fQ?.value || ''), activo: (fActivo?.value || '')
            });
            const j = await Api.get('/api/v1/admin/usuarios?' + params.toString());
            const rows = j.data || [];

            if (gridOptions.api) gridOptions.api.setRowData(rows);
            else if (gridApi?.setGridOption) gridApi.setGridOption('rowData', rows);

            // limpiar selección tras recargar
            gridOptions.api?.deselectAll?.();
            gridApi?.deselectAll?.();

            updateActionButtons(); // async (no esperamos)
            toast('info', `Usuarios cargados: ${rows.length}`);
         } catch (err) {
            handleApiError(err, 'No se pudo cargar usuarios');
         } finally {
            AppLoader?.hide();
         }
      }

      async function resetPwd(row) {
         const pwd = prompt('Nuevo password (mín. 6 caracteres):');
         if (!pwd) return;
         if (pwd.length < 6) { toast('warning', 'El password debe tener al menos 6 caracteres'); return; }
         try {
            AppLoader?.show('Actualizando password…');
            const j = await Api.patch('/api/v1/admin/usuarios/password', { id: row.id, password: pwd });
            if (j?.ok) toast('success', 'Password actualizado');
            else toast('warning', 'No se pudo actualizar el password');
         } catch (err) {
            handleApiError(err, 'Error al actualizar password');
         } finally { AppLoader?.hide(); }
      }

      async function delUser(row) {
         if (!row) { toast('warning', 'Selecciona una fila para eliminar'); return; }
         const canDel = await canPerm('admin.users.borrar');
         if (!canDel) { toast('error', 'No tienes permiso para eliminar'); return; }
         if (!window.confirm('¿Borrar (baja lógica) este usuario?')) return;
         try {
            AppLoader?.show('Eliminando usuario…');
            const j = await Api.del('/api/v1/admin/usuarios?id=' + encodeURIComponent(row.id));
            if (j.ok) {
               toast('success', 'Usuario eliminado');
               await loadData();
            } else {
               toast('warning', 'No se pudo borrar el usuario');
            }
         } catch (err) {
            handleApiError(err, 'Error al borrar usuario');
         } finally { AppLoader?.hide(); }
      }

      async function saveUser() {
         const payload = {
            id: fId?.value ? parseInt(fId.value, 10) : undefined,
            nombre: (fNombre?.value || '').trim(),
            email: (fEmail?.value || '').trim(),
            area_id: fArea?.value ? parseInt(fArea.value, 10) : null,
            jefe_id: fJefe?.value ? parseInt(fJefe.value, 10) : null,
            roles: getSelectedValues(fRoles),
         };
         if (!payload.nombre || !payload.email) { toast('warning', 'Nombre y email son requeridos'); return; }

         try {
            AppLoader?.show('Guardando…');
            if (!payload.id) {
               const canCreate = await canPerm('admin.users.crear');
               if (!canCreate) { toast('error', 'No tienes permiso para crear'); return; }

               if (!fPwd?.value || fPwd.value.length < 6 || fPwd.value !== fPwd2?.value) {
                  toast('warning', 'Password inválido o no coincide'); return;
               }
               payload.password = fPwd.value;
               const j = await Api.post('/api/v1/admin/usuarios', payload);
               if (j.ok) { toast('success', 'Usuario creado'); closeModal(); await loadData(); }
               else toast('warning', 'No se pudo crear el usuario');
            } else {
               const canEdit = await canPerm('admin.users.editar');
               if (!canEdit) { toast('error', 'No tienes permiso para editar'); return; }

               const j = await Api.put('/api/v1/admin/usuarios', payload);
               if (j.ok) { toast('success', 'Usuario actualizado'); closeModal(); await loadData(); }
               else toast('warning', 'No se pudo actualizar el usuario');
            }
         } catch (err) {
            handleApiError(err, 'Error al guardar usuario');
         } finally {
            AppLoader?.hide();
         }
      }

      // ---- Eventos UI ----
      btnSearch?.addEventListener('click', loadData);

      btnNew?.addEventListener('click', async (e) => {
         e.preventDefault();
         await openEditSelected(); // sin selección → nuevo; con selección → editar (con permisos)
      });

      btnDelete?.addEventListener('click', async () => {
         const selected = getSelectedRow();
         await delUser(selected);
      });

      btnClear?.addEventListener('click', () => {
         gridOptions.api?.deselectAll?.();
         gridApi?.deselectAll?.();
         updateActionButtons();
      });

      btnSave?.addEventListener('click', saveUser);

      fQ?.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); loadData(); } });

      // ---- Primera carga ----
      loadData();

      // Aplica gating si usas el helper global (opcional; por si hay otros botones con data-perm)
      if (window.__applyGates) { window.__applyGates(document); }
      // Inicializa estado del botón nuevo/editar segun permisos actuales
      updateActionButtons();
   }

   if (document.readyState === 'loading') window.addEventListener('DOMContentLoaded', init);
   else init();
})();
