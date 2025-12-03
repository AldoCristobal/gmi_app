// public/assets/js/tareas.extra.js
(function () {
   'use strict';

   // ========== authHeaders fallback (por si no existe global) ==========
   if (typeof window.authHeaders !== 'function') {
      console.warn('[tareas.extra] authHeaders() not found, using minimal fallback.');
      window.authHeaders = function authHeaders(extra) {
         extra = extra || {};
         var base = {
            'X-CSRF-Token': (localStorage.getItem('csrf_token') || '')
         };
         for (var k in extra) {
            if (Object.prototype.hasOwnProperty.call(extra, k)) {
               base[k] = extra[k];
            }
         }
         return base;
      };
   }

   const MODAL_TRANSITION_MS = 150;

   // ========== Notyf ==========
   let notyf = null;
   try {
      notyf = new Notyf({
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
      console.warn('[tareas.extra] Notyf not available, using console logs.');
   }
   function showToast(type, message) {
      if (notyf) {
         notyf.open({ type, message });
      } else {
         console[type === 'error' ? 'error' : 'log'](message);
      }
   }

   function handleApiError(err, fallbackMsg) {
      fallbackMsg = fallbackMsg || 'Error while processing request';
      console.error('[tareas.extra] API error', err);

      const status = err?.status ?? err?.code;

      if (status === 401) {
         showToast('warning', 'Sesión expirada o no autenticado (401).');
      } else if (status === 403 || status === 'FORBIDDEN') {
         const msg = err?.payload?.error?.message || '';
         showToast('error', `No cuentas con permisos para realizar esta acción (403). ${msg}`);
      } else if (status === 404) {
         showToast('warning', 'Recurso no encontrado (404).');
      } else if (status === 422) {
         showToast('warning', 'Datos inválidos o incompletos (422).');
      } else {
         showToast('error', `${fallbackMsg}${err?.message ? `: ${err.message}` : ''}`);
      }
   }

   // ===================================================================
   // INIT
   // ===================================================================
   function init() {
      if (typeof Api === 'undefined') {
         console.error('[tareas.extra] Api.js not loaded.');
         return;
      }
      if (typeof agGrid === 'undefined') {
         console.error('[tareas.extra] AG Grid not loaded.');
         return;
      }

      const $ = (sel, ctx) => (ctx || document).querySelector(sel);

      // DOM references
      const gridEl = $('#tareas-extra-grid');
      if (!gridEl) {
         console.warn('[tareas.extra] #tareas-extra-grid not found, aborting.');
         return;
      }

      const inputSearch = $('#tx-q');
      const selectEstado = $('#tx-estado');
      const selectEmpresa = $('#tx-empresa');
      const btnSearch = $('#tx-search');
      const btnRefresh = $('#tx-refresh');
      const btnNew = $('#tx-new');
      const btnDelete = $('#tx-delete');

      // Modal
      const modalEl = $('#tareas-extra-modal');
      const modalTitleEl = $('#tx-modal-title');
      const btnSave = $('#tx-save');

      const fieldId = $('#tx-id');
      const fieldTitulo = $('#tx-titulo');
      const fieldEmpresaModal = $('#tx-empresa-modal');
      const fieldResponsable = $('#tx-responsable');
      const fieldPeriodoInicio = $('#tx-periodo-inicio');
      const fieldPeriodoFin = $('#tx-periodo-fin');
      const fieldFechaObjetivo = $('#tx-fecha-objetivo');
      const fieldFechaVencimiento = $('#tx-fecha-vencimiento');
      const fieldObservaciones = $('#tx-observaciones');
      const fieldEstadoLabel = $('#tx-estado-label');

      if (!modalEl || !modalTitleEl) {
         console.error('[tareas.extra] Modal or title not found.');
         return;
      }

      // ===== Catalogs =====
      let catalogsLoaded = false;
      let empresasCatalog = [];
      let responsablesCatalog = [];
      // Mapa empresa → responsable asignado en BD
      let empresaResponsableMap = {};

      async function fetchCatalog(url) {
         const res = await Api.get(url);
         return res.data || [];
      }

      function fillSelect(selectEl, items, valueField, labelField, options) {
         options = options || {};
         if (!selectEl) return;
         selectEl.innerHTML = '';

         if (options.includeEmpty) {
            const opt = document.createElement('option');
            opt.value = '';
            opt.textContent = options.emptyText || 'Seleccione…';
            selectEl.appendChild(opt);
         }

         for (const item of items) {
            const opt = document.createElement('option');
            opt.value = String(item[valueField] ?? '');
            opt.textContent = String(item[labelField] ?? '');
            selectEl.appendChild(opt);
         }
      }

      async function loadCatalogs() {
         const [empresas, jefes] = await Promise.all([
            fetchCatalog('/api/v1/catalogos/empresas'),
            fetchCatalog('/api/v1/catalogos/jefes') // usados como responsables
         ]);

         empresasCatalog = empresas;
         responsablesCatalog = jefes;

         // construir mapa empresa → responsable
         empresaResponsableMap = {};
         for (const e of empresasCatalog) {
            if (e && e.id != null && e.responsable_id != null) {
               empresaResponsableMap[String(e.id)] = e.responsable_id;
            }
         }

         fillSelect(selectEmpresa, empresasCatalog, 'id', 'nombre', {
            includeEmpty: true,
            emptyText: 'Todas'
         });

         fillSelect(fieldEmpresaModal, empresasCatalog, 'id', 'nombre', {
            includeEmpty: true,
            emptyText: 'Seleccione…'
         });

         fillSelect(fieldResponsable, responsablesCatalog, 'id', 'nombre', {
            includeEmpty: true,
            emptyText: 'Seleccione…'
         });

         catalogsLoaded = true;
      }

      function setSelectValueById(selectEl, idValue) {
         if (!selectEl) return;
         const idStr = idValue != null ? String(idValue) : '';
         let found = false;
         for (const opt of selectEl.options) {
            if (opt.value === idStr) {
               opt.selected = true;
               found = true;
               break;
            }
         }
         if (!found) {
            selectEl.value = '';
         }
      }

      // Cuando cambia la empresa en el modal, preseleccionar responsable asignado
      function handleEmpresaModalChange() {
         if (!fieldEmpresaModal || !fieldResponsable) return;
         const empresaId = fieldEmpresaModal.value || '';
         if (!empresaId) {
            // si no hay empresa, dejar responsable como esté (o limpio)
            return;
         }

         const respId = empresaResponsableMap[String(empresaId)];
         if (respId != null) {
            setSelectValueById(fieldResponsable, respId);
         }
      }

      // ===== Permissions helper =====
      const canPerm = (perm) => {
         if (window.__canPerm) return window.__canPerm(perm);
         return Promise.resolve(false);
      };

      // ===== AG Grid setup =====
      function formatDate(isoDate) {
         if (!isoDate) return '';
         const parts = String(isoDate).split('-');
         if (parts.length !== 3) return isoDate;
         return parts[2] + '/' + parts[1] + '/' + parts[0];
      }

      const columnDefs = [
         {
            headerName: '#',
            valueGetter: 'node.rowIndex + 1',
            width: 60
         },
         {
            headerName: 'Título',
            field: 'titulo',
            flex: 2,
            minWidth: 180
         },
         {
            headerName: 'Empresa',
            field: 'empresa_nombre',
            flex: 2,
            minWidth: 180
         },
         {
            headerName: 'Responsable',
            field: 'responsable_nombre',
            flex: 1.5,
            minWidth: 140
         },
         {
            headerName: 'Periodo',
            valueGetter: params => {
               const pi = params.data?.periodo_inicio || '';
               const pf = params.data?.periodo_fin || '';
               if (!pi && !pf) return '';
               return formatDate(pi) + ' - ' + formatDate(pf);
            },
            flex: 2,
            minWidth: 190
         },
         {
            headerName: 'Objetivo',
            field: 'fecha_objetivo',
            width: 110,
            valueFormatter: p => formatDate(p.value)
         },
         {
            headerName: 'Vencimiento',
            field: 'fecha_vencimiento',
            width: 120,
            valueFormatter: p => formatDate(p.value),
            cellClass: params => {
               const v = params.value;
               if (!v) return null;
               const today = new Date();
               const d = new Date(v + 'T00:00:00');
               const diffDays = (d - today) / (1000 * 60 * 60 * 24);
               if (diffDays < 0) return 'text-danger';
               if (diffDays <= 3) return 'text-warning';
               return null;
            }
         },
         {
            headerName: 'Estado',
            field: 'estado',
            width: 130,
            cellRenderer: params => {
               const raw = (params.value || '').toString().toUpperCase();
               let label = raw;
               let cls = 'badge badge-pill';

               switch (raw) {
                  case 'PENDIENTE':
                     label = 'Pendiente';
                     cls += ' badge-secondary';
                     break;
                  case 'EN_REVISION':
                     label = 'En revisión';
                     cls += ' badge-info';
                     break;
                  case 'COMPLETA':
                     label = 'Completa';
                     cls += ' badge-success';
                     break;
                  case 'CANCELADA':
                     label = 'Cancelada';
                     cls += ' badge-dark';
                     break;
                  case 'BLOQUEADA':
                     label = 'Bloqueada';
                     cls += ' badge-warning';
                     break;
               }

               return '<span class="' + cls + '">' + label + '</span>';
            }
         }
      ];

      const gridOptions = {
         columnDefs,
         rowData: [],
         animateRows: true,
         rowSelection: { mode: 'singleRow', enableClickSelection: false },
         rowHeight: 42,
         onRowClicked: (event) => {
            event.node.setSelected(!event.node.isSelected(), true);
            updateActionButtons();
         },
         onSelectionChanged: () => {
            updateActionButtons();
         },
         onRowDoubleClicked: () => {
            openEditSelected();
         }
      };

      const gridApi = (typeof agGrid.createGrid === 'function')
         ? agGrid.createGrid(gridEl, gridOptions)
         : new agGrid.Grid(gridEl, gridOptions);

      function getSelectedRow() {
         if (gridOptions.api?.getSelectedRows) {
            const rows = gridOptions.api.getSelectedRows();
            return (rows && rows[0]) || null;
         }
         if (gridApi?.getSelectedRows) {
            const rows = gridApi.getSelectedRows();
            return (rows && rows[0]) || null;
         }
         return null;
      }

      // ===== Quick filter (ajustado para usar gridApi también) =====
      function applyQuickFilter() {
         const text = (inputSearch?.value || '').trim();
         if (gridOptions.api && typeof gridOptions.api.setQuickFilter === 'function') {
            gridOptions.api.setQuickFilter(text);
         } else if (gridApi && typeof gridApi.setQuickFilter === 'function') {
            gridApi.setQuickFilter(text);
         }
      }

      // ===== Buttons state =====
      async function updateActionButtons() {
         const selected = getSelectedRow();

         const canCreate = await canPerm('tareas.extra.crear');
         const canEdit = await canPerm('tareas.extra.editar');
         const canDelete = await canPerm('tareas.extra.borrar');

         if (selected) {
            // EDIT mode
            btnNew?.classList.remove('btn-success');
            btnNew?.classList.add('btn-warning');
            if (btnNew) {
               btnNew.innerHTML = '<i class="fas fa-pen"></i>';
               btnNew.title = canEdit ? 'Editar tarea seleccionada' : 'Sin permiso para editar';
               btnNew.disabled = !canEdit;
            }
            if (btnDelete) {
               btnDelete.disabled = !canDelete;
               btnDelete.title = canDelete ? 'Eliminar tarea seleccionada' : 'Sin permiso para eliminar';
            }
         } else {
            // NEW mode
            btnNew?.classList.remove('btn-warning');
            btnNew?.classList.add('btn-success');
            if (btnNew) {
               btnNew.innerHTML = '<i class="fas fa-plus"></i>';
               btnNew.title = canCreate ? 'Crear nueva tarea' : 'Sin permiso para crear';
               btnNew.disabled = !canCreate;
            }
            if (btnDelete) {
               btnDelete.disabled = true;
               btnDelete.title = 'Selecciona una tarea para eliminar';
            }
         }
      }

      // ===== Modal logic (sin jQuery, con fade) =====
      let modalOpen = false;

      function createBackdrop() {
         let bd = document.querySelector('.modal-backdrop.tareas-extra-backdrop');
         if (!bd) {
            bd = document.createElement('div');
            bd.className = 'modal-backdrop fade tareas-extra-backdrop';
            bd.addEventListener('click', () => {
               if (modalOpen) closeModal();
            });
            document.body.appendChild(bd);
            void bd.offsetWidth;
            bd.classList.add('show');
         }
      }

      async function openModal(createMode, data) {
         if (modalOpen) closeModal();

         if (!catalogsLoaded) {
            try {
               window.AppLoader?.show('Cargando catálogos…');
               await loadCatalogs();
            } finally {
               window.AppLoader?.hide();
            }
         }

         if (createMode) {
            modalTitleEl.textContent = 'Nueva tarea extraordinaria';
            if (fieldId) fieldId.value = '';
            if (fieldTitulo) fieldTitulo.value = '';
            if (fieldEmpresaModal) fieldEmpresaModal.value = '';
            if (fieldResponsable) fieldResponsable.value = '';
            if (fieldPeriodoInicio) fieldPeriodoInicio.value = '';
            if (fieldPeriodoFin) fieldPeriodoFin.value = '';
            if (fieldFechaObjetivo) fieldFechaObjetivo.value = '';
            if (fieldFechaVencimiento) fieldFechaVencimiento.value = '';
            if (fieldObservaciones) fieldObservaciones.value = '';
            if (fieldEstadoLabel) fieldEstadoLabel.value = 'Pendiente (se gestiona en Mis tareas)';
         } else if (data) {
            modalTitleEl.textContent = 'Editar tarea extraordinaria';
            if (fieldId) fieldId.value = data.id || '';
            if (fieldTitulo) fieldTitulo.value = data.titulo || '';
            if (fieldPeriodoInicio) fieldPeriodoInicio.value = data.periodo_inicio || '';
            if (fieldPeriodoFin) fieldPeriodoFin.value = data.periodo_fin || '';
            if (fieldFechaObjetivo) fieldFechaObjetivo.value = data.fecha_objetivo || '';
            if (fieldFechaVencimiento) fieldFechaVencimiento.value = data.fecha_vencimiento || '';
            if (fieldObservaciones) fieldObservaciones.value = data.observaciones || '';

            setSelectValueById(fieldEmpresaModal, data.empresa_id);
            setSelectValueById(fieldResponsable, data.responsable_id);

            if (fieldEstadoLabel) {
               const raw = (data.estado || '').toString().toUpperCase();
               let label = raw;
               switch (raw) {
                  case 'PENDIENTE': label = 'Pendiente'; break;
                  case 'EN_REVISION': label = 'En revisión'; break;
                  case 'COMPLETA': label = 'Completa'; break;
                  case 'CANCELADA': label = 'Cancelada'; break;
                  case 'BLOQUEADA': label = 'Bloqueada'; break;
               }
               fieldEstadoLabel.value = label + ' (se gestiona en Mis tareas)';
            }
         }

         modalEl.style.display = 'block';
         modalEl.removeAttribute('aria-hidden');
         modalEl.setAttribute('aria-modal', 'true');
         void modalEl.offsetWidth;
         modalEl.classList.add('show');

         createBackdrop();
         document.body.classList.add('modal-open');
         modalOpen = true;
      }

      function closeModal() {
         if (!modalOpen) return;
         modalOpen = false;

         const bd = document.querySelector('.modal-backdrop.tareas-extra-backdrop');

         modalEl.classList.remove('show');
         if (bd) bd.classList.remove('show');
         document.body.classList.remove('modal-open');

         setTimeout(() => {
            modalEl.style.display = 'none';
            modalEl.setAttribute('aria-hidden', 'true');
            modalEl.removeAttribute('aria-modal');

            if (bd && bd.parentNode) {
               bd.parentNode.removeChild(bd);
            }
         }, MODAL_TRANSITION_MS);
      }

      const closeButtons = modalEl.querySelectorAll('[data-dismiss="modal"], .close');
      closeButtons.forEach(btn => {
         btn.addEventListener('click', function (evt) {
            evt.preventDefault();
            closeModal();
         });
      });

      document.addEventListener('keydown', function (evt) {
         if (evt.key === 'Escape' && modalOpen) {
            closeModal();
         }
      });

      modalEl.addEventListener('mousedown', function (evt) {
         if (!modalOpen) return;
         if (evt.target === modalEl) {
            evt.preventDefault();
            closeModal();
         }
      });

      // ===== API calls =====
      async function loadTasks() {
         try {
            window.AppLoader?.show('Cargando tareas…');

            const params = new URLSearchParams();
            // backend filters: estado, empresa_id
            if (selectEstado && selectEstado.value) {
               params.set('estado', selectEstado.value);
            }
            if (selectEmpresa && selectEmpresa.value) {
               params.set('empresa_id', selectEmpresa.value);
            }

            const url = '/api/v1/tareas/extra' + (params.toString() ? ('?' + params.toString()) : '');
            const j = await Api.get(url);

            if (!j || !j.ok) {
               showToast('warning', 'No se pudieron cargar las tareas');
               return;
            }

            let items = [];
            let total = 0;

            if (Array.isArray(j.data)) {
               // Forma A
               items = j.data;
               total = (j.meta && typeof j.meta.total === 'number')
                  ? j.meta.total
                  : items.length;
            } else if (j.data && Array.isArray(j.data.items)) {
               // Forma B (la del service que te pasé)
               items = j.data.items;
               total = typeof j.data.total === 'number'
                  ? j.data.total
                  : (j.meta && typeof j.meta.total === 'number'
                     ? j.meta.total
                     : items.length);
            }

            if (gridOptions.api && typeof gridOptions.api.setRowData === 'function') {
               gridOptions.api.setRowData(items);
               gridOptions.api.deselectAll();
            } else if (gridApi && typeof gridApi.setGridOption === 'function') {
               gridApi.setGridOption('rowData', items);
            }

            applyQuickFilter();
            updateActionButtons();
            showToast('info', `Tareas cargadas: ${total}`);
         } catch (err) {
            handleApiError(err, 'No se pudo cargar tareas');
         } finally {
            window.AppLoader?.hide();
         }
      }


      async function saveTask() {
         const id = fieldId?.value ? parseInt(fieldId.value, 10) : 0;
         const payload = {
            id: id || undefined,
            titulo: (fieldTitulo?.value || '').trim(),
            empresa_id: fieldEmpresaModal?.value ? parseInt(fieldEmpresaModal.value, 10) : 0,
            responsable_id: fieldResponsable?.value ? parseInt(fieldResponsable.value, 10) : 0,
            periodo_inicio: fieldPeriodoInicio?.value || '',
            periodo_fin: fieldPeriodoFin?.value || '',
            fecha_objetivo: fieldFechaObjetivo?.value || null,
            fecha_vencimiento: fieldFechaVencimiento?.value || ''
         };

         payload.observaciones = (fieldObservaciones?.value || '').trim() || null;

         if (!payload.titulo) {
            showToast('warning', 'El título es requerido');
            return;
         }
         if (!payload.empresa_id) {
            showToast('warning', 'La empresa es requerida');
            return;
         }
         if (!payload.responsable_id) {
            showToast('warning', 'El responsable es requerido');
            return;
         }
         if (!payload.periodo_inicio || !payload.periodo_fin || !payload.fecha_vencimiento) {
            showToast('warning', 'Periodo inicio, periodo fin y fecha vencimiento son requeridos');
            return;
         }

         try {
            window.AppLoader?.show('Guardando tarea…');

            if (!id) {
               const canCreate = await canPerm('tareas.extra.crear');
               if (!canCreate) {
                  showToast('error', 'No tienes permiso para crear');
                  return;
               }

               const j = await Api.post('/api/v1/tareas/extra', payload);
               if (j && j.ok) {
                  showToast('success', 'Tarea creada');
                  closeModal();
                  await loadTasks();
               } else {
                  showToast('warning', j?.error?.message || 'No se pudo crear la tarea');
               }
            } else {
               const canEdit = await canPerm('tareas.extra.editar');
               if (!canEdit) {
                  showToast('error', 'No tienes permiso para editar');
                  return;
               }

               const j = await Api.put('/api/v1/tareas/extra', payload);
               if (j && j.ok) {
                  showToast('success', 'Tarea actualizada');
                  closeModal();
                  await loadTasks();
               } else {
                  showToast('warning', j?.error?.message || 'No se pudo actualizar la tarea');
               }
            }
         } catch (err) {
            handleApiError(err, 'Error al guardar tarea');
         } finally {
            window.AppLoader?.hide();
         }
      }

      async function deleteTask(row) {
         if (!row) {
            showToast('warning', 'Selecciona una tarea para eliminar');
            return;
         }

         const canDelete = await canPerm('tareas.extra.borrar');
         if (!canDelete) {
            showToast('error', 'No tienes permiso para eliminar');
            return;
         }

         if (!window.confirm('¿Eliminar definitivamente esta tarea extraordinaria?')) {
            return;
         }

         try {
            window.AppLoader?.show('Eliminando tarea…');

            const resp = await fetch('/api/v1/tareas/extra', {
               method: 'DELETE',
               headers: window.authHeaders({ 'Content-Type': 'application/json' }),
               body: JSON.stringify({ id: row.id })
            });

            let json = {};
            try {
               json = await resp.json();
            } catch (_) { }

            if (!resp.ok || !json.ok) {
               console.error('[tareas.extra] delete error', json);
               const msg = json?.error?.message || 'No se pudo eliminar la tarea';
               showToast('warning', msg);
               return;
            }

            showToast('success', 'Tarea eliminada');
            await loadTasks();
         } catch (err) {
            handleApiError(err, 'Error al eliminar tarea');
         } finally {
            window.AppLoader?.hide();
         }
      }

      // ===== Flow: open edit / new =====
      async function openEdit(row) {
         if (!row) return;
         await openModal(false, row);
      }

      async function openEditSelected() {
         const selected = getSelectedRow();
         if (selected) {
            const okEdit = await canPerm('tareas.extra.editar');
            if (!okEdit) {
               showToast('error', 'No tienes permiso para editar');
               return;
            }
            await openEdit(selected);
         } else {
            const okCreate = await canPerm('tareas.extra.crear');
            if (!okCreate) {
               showToast('error', 'No tienes permiso para crear');
               return;
            }
            await openModal(true, null);
         }
      }

      // ===== UI events =====
      btnSearch?.addEventListener('click', function () {
         loadTasks();
      });

      btnRefresh?.addEventListener('click', function () {
         // reset filters (except search quick filter)
         if (selectEstado) selectEstado.value = '';
         if (selectEmpresa) selectEmpresa.value = '';
         loadTasks();
      });

      btnNew?.addEventListener('click', function (evt) {
         evt.preventDefault();
         openEditSelected();
      });

      btnDelete?.addEventListener('click', function () {
         const row = getSelectedRow();
         deleteTask(row);
      });

      btnSave?.addEventListener('click', function () {
         saveTask();
      });

      inputSearch?.addEventListener('keydown', function (evt) {
         if (evt.key === 'Enter') {
            evt.preventDefault();
            applyQuickFilter();
         }
      });

      inputSearch?.addEventListener('input', function () {
         applyQuickFilter();
      });

      selectEstado?.addEventListener('change', function () {
         loadTasks();
      });

      selectEmpresa?.addEventListener('change', function () {
         loadTasks();
      });

      // cambio de empresa en el modal → actualizar responsable según BD
      if (fieldEmpresaModal) {
         fieldEmpresaModal.addEventListener('change', handleEmpresaModalChange);
      }

      // ===== First load =====
      loadCatalogs()
         .catch(err => console.error('[tareas.extra] error loading catalogs', err))
         .finally(() => {
            loadTasks();
         });

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
