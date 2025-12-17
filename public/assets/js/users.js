// public/assets/js/users.js
(function () {
   const USE_DETAIL_FALLBACK = false;
   const MODAL_TRANSITION_MS = 150;

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
      console.warn('Notyf not found; console fallback');
   }
   const toast = (type, message) => {
      if (_notyf) _notyf.open({ type, message });
      else console[type === 'error' ? 'error' : 'log'](message);
   };

   function handleApiError(err, fallbackMsg = 'Error al procesar la solicitud') {
      console.error(err);
      const status = err?.status ?? err?.code;
      if (status === 401) toast('warning', 'Sesión expirada o no autenticado (401).');
      else if (status === 403 || status === 'FORBIDDEN') {
         const msg = err?.payload?.error?.message || '';
         toast('error', `No cuentas con permisos para realizar esta acción (403). ${msg}`);
      } else if (status === 404) toast('warning', 'Recurso no encontrado (404).');
      else if (status === 409) toast('warning', 'Conflicto de datos (409).');
      else if (status === 422) toast('warning', 'Datos inválidos o incompletos (422).');
      else toast('error', `${fallbackMsg}${err?.message ? `: ${err.message}` : ''}`);
   }

   function init() {
      if (typeof Api === 'undefined') { console.error('Api.js no cargado'); return; }
      if (typeof agGrid === 'undefined') { console.error('AG Grid no cargado'); return; }

      const $ = (sel, ctx = document) => ctx.querySelector(sel);

      function setEnabled(el, enabled) {
         if (!el) return;
         el.disabled = !enabled;
         el.classList.toggle('disabled', !enabled);
         if (enabled) el.removeAttribute('aria-disabled');
         else el.setAttribute('aria-disabled', 'true');
      }

      const gridEl = $('#gridUsuarios');
      if (!gridEl) { console.error('Missing #gridUsuarios'); return; }

      const fQ = $('#f-q');
      const fActivo = $('#f-activo');
      const btnSearch = $('#btn-search');
      const btnNew = $('#btn-new');
      const btnDelete = $('#btn-delete');
      const btnClear = $('#btn-clear'); // opcional

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

      if (!modalEl || !modalTitle) { console.error('Modal missing'); return; }

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
            fetchCatalog('/api/v1/catalogos/jefes-usuarios'),
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

      const columnDefs = [
         { headerName: '#', valueGetter: 'node.rowIndex + 1', width: 70 },
         { headerName: 'Nombre', field: 'nombre', flex: 1 },
         { headerName: 'Email', field: 'email', flex: 1 },
         { headerName: 'Área', field: 'area_name', flex: 0.8, minWidth: 120 },
         { headerName: 'Jefe', field: 'jefe_name', flex: 0.8, minWidth: 120 },
         {
            headerName: 'Activo',
            field: 'activo',
            width: 100,
            valueFormatter: p => (String(p.value) === '1' || p.value === 1 ? 'Sí' : 'No'),
         },
      ];

      const gridOptions = {
         columnDefs,
         rowData: [],
         animateRows: true,
         rowHeight: 42,
         rowSelection: { mode: 'singleRow', enableClickSelection: false },
         onRowClicked: (e) => {
            e.node.setSelected(!e.node.isSelected(), true);
            updateActionButtons();
         },
         onSelectionChanged: () => updateActionButtons(),
      };

      let gridApi;
      if (typeof agGrid.createGrid === 'function') {
         gridApi = agGrid.createGrid(gridEl, gridOptions);
      } else {
         new agGrid.Grid(gridEl, gridOptions);
         gridApi = gridOptions.api;
      }

      function getSelectedRow() {
         if (!gridApi || !gridApi.getSelectedRows) return null;
         const sel = gridApi.getSelectedRows();
         return (sel && sel[0]) ? sel[0] : null;
      }

      const canPerm = (p) => {
         if (window.__canPerm) return window.__canPerm(p);
         return Promise.resolve(false);
      };

      async function updateActionButtons() {
         const selected = getSelectedRow();
         const canPermLocal = (p) => window.__canPerm ? window.__canPerm(p) : Promise.resolve(false);

         // FIX: permisos reales del backend
         const canCreate = await canPermLocal('admin.usuarios.crear');
         const canEdit = await canPermLocal('admin.usuarios.editar');
         const canDelete = await canPermLocal('admin.usuarios.borrar');

         if (selected) {
            btnNew?.classList.remove('btn-success');
            btnNew?.classList.add('btn-warning');
            if (btnNew) btnNew.innerHTML = '<i class="fas fa-pen"></i> Editar';
            setEnabled(btnNew, !!canEdit);
            if (btnNew) btnNew.title = canEdit ? 'Editar usuario seleccionado' : 'No autorizado para editar';

            setEnabled(btnDelete, !!canDelete);
            if (btnDelete) btnDelete.title = canDelete ? 'Eliminar usuario seleccionado' : 'No autorizado para eliminar';
         } else {
            btnNew?.classList.remove('btn-warning');
            btnNew?.classList.add('btn-success');
            if (btnNew) btnNew.innerHTML = '<i class="fas fa-plus"></i> Nuevo';
            setEnabled(btnNew, !!canCreate);
            if (btnNew) btnNew.title = canCreate ? 'Registrar nuevo usuario' : 'No autorizado para crear';

            if (btnDelete) {
               setEnabled(btnDelete, false);
               btnDelete.title = 'Selecciona un usuario para eliminar';
            }
         }
      }

      let modalIsOpen = false;

      function createBackdrop() {
         let bd = document.querySelector('.modal-backdrop.user-modal-backdrop');
         if (!bd) {
            bd = document.createElement('div');
            bd.className = 'modal-backdrop fade user-modal-backdrop';
            bd.addEventListener('click', () => {
               if (modalIsOpen) closeModal();
            });
            document.body.appendChild(bd);
            void bd.offsetWidth;
            bd.classList.add('show');
         }
      }

      async function openModal(create = true, data = null) {
         if (modalIsOpen) closeModal();

         if (!_catalogsLoaded) {
            try { AppLoader?.show('Cargando catálogos…'); await loadCatalogs(); }
            finally { AppLoader?.hide(); _catalogsLoaded = true; }
         }

         if (create) {
            modalTitle.textContent = 'Nuevo usuario';
            fId.value = '';
            fNombre.value = '';
            fEmail.value = '';
            fArea.value = '';
            fJefe.value = '';
            fPwd.value = '';
            fPwd2.value = '';
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
            fPwd.value = '';
            fPwd2.value = '';
            if (pwdGroup) pwdGroup.style.display = 'none';
         }

         modalEl.style.display = 'block';
         modalEl.removeAttribute('aria-hidden');
         modalEl.setAttribute('aria-modal', 'true');
         void modalEl.offsetWidth;
         modalEl.classList.add('show');

         createBackdrop();
         document.body.classList.add('modal-open');
         modalIsOpen = true;
      }

      function closeModal() {
         if (!modalIsOpen) return;
         modalIsOpen = false;

         const bd = document.querySelector('.modal-backdrop.user-modal-backdrop');

         modalEl.classList.remove('show');
         if (bd) bd.classList.remove('show');
         document.body.classList.remove('modal-open');

         setTimeout(() => {
            modalEl.style.display = 'none';
            modalEl.setAttribute('aria-hidden', 'true');
            modalEl.removeAttribute('aria-modal');
            if (bd && bd.parentNode) bd.parentNode.removeChild(bd);
         }, MODAL_TRANSITION_MS);
      }

      const closeBtns = modalEl.querySelectorAll('[data-dismiss="modal"], .close');
      closeBtns.forEach(btn => {
         btn.addEventListener('click', (e) => {
            e.preventDefault();
            closeModal();
         });
      });

      document.addEventListener('keydown', (e) => {
         if (e.key === 'Escape' && modalIsOpen) closeModal();
      });

      modalEl.addEventListener('mousedown', (e) => {
         if (!modalIsOpen) return;
         if (e.target === modalEl) {
            e.preventDefault();
            closeModal();
         }
      });

      async function openEdit(row) {
         if (!row) return;
         await openModal(false, row);
      }

      async function openEditSelected() {
         const selected = getSelectedRow();
         if (selected) {
            const ok = await canPerm('admin.usuarios.editar');
            if (!ok) { toast('error', 'No tienes permiso para editar'); return; }
            await openEdit(selected);
         } else {
            const ok = await canPerm('admin.usuarios.crear');
            if (!ok) { toast('error', 'No tienes permiso para crear'); return; }
            await openModal(true);
         }
      }

      function applyQuickFilter() {
         if (!gridApi || !gridApi.setQuickFilter) return;
         const text = (fQ?.value || '').trim();
         gridApi.setQuickFilter(text);
      }

      async function loadData() {
         try {
            AppLoader?.show('Cargando usuarios…');

            const params = new URLSearchParams({
               page: '1',
               size: '200',
            });

            const qText = (fQ?.value || '').trim();
            if (qText !== '') params.set('q', qText);

            if (fActivo && fActivo.value !== '') {
               params.set('activo', fActivo.value);
            }

            const j = await Api.get('/api/v1/admin/usuarios?' + params.toString());
            const rows = j.data || [];

            if (gridApi && gridApi.setRowData) {
               gridApi.setRowData(rows);
               if (gridApi.deselectAll) gridApi.deselectAll();
            }

            updateActionButtons();
            toast('info', `Usuarios cargados: ${rows.length}`);
         } catch (err) {
            handleApiError(err, 'No se pudo cargar usuarios');
         } finally {
            AppLoader?.hide();
         }
      }

      async function delUser(row) {
         if (!row) {
            toast('warning', 'Selecciona una fila para eliminar');
            return;
         }
         const canDel = await canPerm('admin.usuarios.borrar');
         if (!canDel) {
            toast('error', 'No tienes permiso para eliminar');
            return;
         }
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
         } finally {
            AppLoader?.hide();
         }
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

         if (!payload.nombre || !payload.email) {
            toast('warning', 'Nombre y email son requeridos');
            return;
         }

         try {
            AppLoader?.show('Guardando…');

            if (!payload.id) {
               const canCreate = await canPerm('admin.usuarios.crear');
               if (!canCreate) {
                  toast('error', 'No tienes permiso para crear');
                  return;
               }

               if (!fPwd?.value || fPwd.value.length < 6 || fPwd.value !== fPwd2?.value) {
                  toast('warning', 'Password inválido o no coincide');
                  return;
               }
               payload.password = fPwd.value;

               const j = await Api.post('/api/v1/admin/usuarios', payload);
               if (j.ok) {
                  toast('success', 'Usuario creado');
                  closeModal();
                  await loadData();
               } else {
                  toast('warning', 'No se pudo crear el usuario');
               }
            } else {
               const canEdit = await canPerm('admin.usuarios.editar');
               if (!canEdit) {
                  toast('error', 'No tienes permiso para editar');
                  return;
               }

               const j = await Api.put('/api/v1/admin/usuarios', payload);
               if (j.ok) {
                  toast('success', 'Usuario actualizado');
                  closeModal();
                  await loadData();
               } else {
                  toast('warning', 'No se pudo actualizar el usuario');
               }
            }
         } catch (err) {
            handleApiError(err, 'Error al guardar usuario');
         } finally {
            AppLoader?.hide();
         }
      }

      btnSearch?.addEventListener('click', loadData);

      btnNew?.addEventListener('click', async (e) => {
         e.preventDefault();
         await openEditSelected();
      });

      btnDelete?.addEventListener('click', async () => {
         const selected = getSelectedRow();
         await delUser(selected);
      });

      btnClear?.addEventListener('click', () => {
         if (gridApi && gridApi.deselectAll) gridApi.deselectAll();
         updateActionButtons();
      });

      btnSave?.addEventListener('click', saveUser);

      fQ?.addEventListener('keydown', (e) => {
         if (e.key === 'Enter') {
            e.preventDefault();
            loadData();
         }
      });

      fQ?.addEventListener('input', () => {
         //applyQuickFilter();
      });

      fActivo?.addEventListener('change', () => {
         loadData();
      });

      loadData();

      if (window.__applyGates) {
         window.__applyGates(document);
      }
      updateActionButtons();
   }

   if (document.readyState === 'loading') {
      window.addEventListener('DOMContentLoaded', init);
   } else {
      init();
   }
})();
