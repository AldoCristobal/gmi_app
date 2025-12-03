// public/assets/js/roles.js
// AG Grid v29.3.5 + FancyTree (solo claves de permisos)
// Backend esperado:
//   - GET  /api/v1/admin/roles?q=...
//   - POST /api/v1/admin/roles
//   - PUT  /api/v1/admin/roles
//   - DELETE /api/v1/admin/roles?id=...
//   - GET  /api/v1/admin/permisos
//   - GET  /api/v1/admin/roles/permisos?id=123        → data: string[] (claves)
//   - PATCH /api/v1/admin/roles/permisos              → body: { role_id, permisos: string[] }

(function () {
   const DEBUG = false;
   const log = (...a) => DEBUG && console.log('[roles]', ...a);

   if (typeof Api === 'undefined') { console.error('Api helper no cargado'); return; }
   if (typeof agGrid === 'undefined') { console.error('AG Grid no cargado'); return; }
   if (typeof window.jQuery === 'undefined') { console.error('jQuery no cargado (FancyTree requiere jQuery)'); return; }

   // -------- Helpers DOM --------
   const qs = (sel, ctx = document) => ctx.querySelector(sel);
   const $jq = window.jQuery;

   // -------- Notificaciones --------
   let _notyf = null;
   try {
      _notyf = new Notyf({
         duration: 2500,
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
      console.warn('Notyf no disponible, usando console.*');
   }
   const toast = {
      success: (m) => _notyf ? _notyf.open({ type: 'success', message: m }) : console.log(m),
      error: (m) => _notyf ? _notyf.open({ type: 'error', message: m }) : console.error(m),
      info: (m) => _notyf ? _notyf.open({ type: 'info', message: m }) : console.log(m),
      warning: (m) => _notyf ? _notyf.open({ type: 'warning', message: m }) : console.warn(m),
   };

   // -------- Refs UI --------
   const gridEl = qs('#gridRoles');
   const qEl = qs('#r-q');
   const btnSearch = qs('#r-btn-search');
   const btnRefresh = qs('#r-btn-refresh');
   const btnNew = qs('#r-btn-new');
   const btnEdit = qs('#r-btn-edit');
   const btnDel = qs('#r-btn-del');

   const modalEl = qs('#rol-modal');
   const modalTitle = qs('#rol-modal-title');
   const fId = qs('#rol-id');
   const fNombre = qs('#rol-nombre');
   const fSlug = qs('#rol-slug');
   const fDesc = qs('#rol-desc');
   const fPrio = qs('#rol-prio');
   const fActivo = qs('#rol-activo');
   const btnSave = qs('#rol-save');

   const permTree = qs('#rp-tree');
   const rpLabel = qs('#rp-rol-label');
   const rpFilter = qs('#rp-filter');
   const rpExpand = qs('#rp-expand');
   const rpCollapse = qs('#rp-collapse');
   const rpSelectAll = qs('#rp-select-all');
   const rpUnselectAll = qs('#rp-unselect-all');
   const rpSave = qs('#rp-save');

   if (!gridEl) { console.error('No existe #gridRoles en el DOM'); return; }
   if (!permTree) { console.error('No existe #rp-tree en el DOM'); return; }

   // -------- API --------
   const API = {
      roles: {
         list: '/api/v1/admin/roles',
         create: '/api/v1/admin/roles',
         update: '/api/v1/admin/roles',
         remove: (id) => `/api/v1/admin/roles?id=${encodeURIComponent(id)}`,
         permsOf: (id) => `/api/v1/admin/roles/permisos?id=${encodeURIComponent(id)}`,
         savePerms: '/api/v1/admin/roles/permisos',
      },
      permsCatalog: '/api/v1/admin/permisos',
   };

   // -------- Estado --------
   let _selectedRole = null;
   let _catalogLoaded = false;
   let _lastPermLoadToken = 0;
   let modalIsOpen = false;

   function getCsrfToken() {
      const m = document.querySelector('meta[name="csrf-token"]');
      return m?.getAttribute('content') || '';
   }

   function getTree() {
      return $jq.ui?.fancytree?.getTree(permTree);
   }

   // Spinner en botón Guardar Permisos
   let _rpSaveInner = '';
   function setPermBusy(flag) {
      if (!rpSave) return;
      if (flag) {
         if (!_rpSaveInner) _rpSaveInner = rpSave.innerHTML;
         rpSave.disabled = true;
         rpSave.innerHTML = '<span class="spinner-border spinner-border-sm mr-1"></span>';
      } else {
         rpSave.innerHTML = _rpSaveInner || '<i class="fas fa-save"></i>';
         rpSave.disabled = !_selectedRole;
      }
   }

   function setTreeDisabled(flag) {
      const $ct = $jq(permTree).find('.fancytree-container');
      if (flag) $ct.addClass('ft-busy');
      else $ct.removeClass('ft-busy');
   }

   // -------- AG Grid Roles --------
   const columnDefs = [
      { headerName: '#', valueGetter: 'node.rowIndex + 1', width: 70 },
      { headerName: 'Nombre', field: 'nombre', flex: 1, minWidth: 180 },
      { headerName: 'Slug', field: 'slug', width: 160 },
      { headerName: 'Prioridad', field: 'prioridad', width: 110 },
      { headerName: 'Activo', field: 'activo', width: 90, valueFormatter: p => p.value ? 'Sí' : 'No' },
   ];

   const gridOptions = {
      columnDefs,
      rowData: [],
      rowHeight: 42,
      animateRows: true,
      rowSelection: 'single',
      suppressRowClickSelection: true,
      onRowClicked: (e) => {
         const willSelect = !e.node.isSelected();
         e.node.setSelected(willSelect, true);
         updateButtons();
      },
   };

   new agGrid.Grid(gridEl, gridOptions);

   function getSelected() {
      const rows = gridOptions.api?.getSelectedRows?.() || [];
      return rows[0] || null;
   }

   async function loadRoles() {
      try {
         AppLoader?.show?.('Cargando roles…');
         const q = qEl?.value?.trim() || '';
         const url = q ? `${API.roles.list}?q=${encodeURIComponent(q)}` : API.roles.list;
         const res = await Api.get(url);
         const rows = res.data || [];
         gridOptions.api?.setRowData(rows);
         gridOptions.api?.deselectAll?.();
         _selectedRole = null;
         updateButtons();
         toast.info(`Roles cargados: ${rows.length}`);
      } catch (err) {
         console.error(err);
         toast.error('No se pudo cargar roles');
      } finally {
         AppLoader?.hide?.();
      }
   }

   function updateButtons() {
      _selectedRole = getSelected();

      if (btnEdit) btnEdit.disabled = !_selectedRole;
      if (btnDel) btnDel.disabled = !_selectedRole;
      if (rpSave) rpSave.disabled = !_selectedRole;

      if (rpLabel) {
         rpLabel.textContent = _selectedRole
            ? `ID ${_selectedRole.id} — ${_selectedRole.nombre}`
            : '—';
      }

      if (_selectedRole) {
         loadRolePerms(_selectedRole.id);
      } else {
         clearTreeSelection();
      }
   }

   // -------- Modal Nuevo/Editar Rol --------
   function showModal() {
      if (!modalEl || modalIsOpen) return;

      modalEl.style.display = 'block';
      modalEl.removeAttribute('aria-hidden');
      modalEl.setAttribute('aria-modal', 'true');

      let backdrop = document.querySelector('.modal-backdrop.roles-backdrop');
      if (!backdrop) {
         backdrop = document.createElement('div');
         backdrop.className = 'modal-backdrop fade roles-backdrop';
         document.body.appendChild(backdrop);
         requestAnimationFrame(() => {
            backdrop.classList.add('show');
         });
      }

      document.body.classList.add('modal-open');

      requestAnimationFrame(() => {
         modalEl.classList.add('show');
      });

      modalIsOpen = true;
   }

   function closeModal() {
      if (!modalEl || !modalIsOpen) return;

      const backdrop = document.querySelector('.modal-backdrop.roles-backdrop');

      modalEl.classList.remove('show');
      modalEl.setAttribute('aria-hidden', 'true');
      modalEl.removeAttribute('aria-modal');

      if (backdrop) backdrop.classList.remove('show');

      setTimeout(() => {
         modalEl.style.display = 'none';
         if (backdrop && backdrop.parentNode) backdrop.parentNode.removeChild(backdrop);
         document.body.classList.remove('modal-open');
      }, 150);

      modalIsOpen = false;
   }

   function openModal(create = true, row = null) {
      if (!modalEl) return;

      if (create) {
         modalTitle.textContent = 'Nuevo rol';
         fId.value = '';
         fNombre.value = '';
         fSlug.value = '';
         fDesc.value = '';
         fPrio.value = '100';
         fActivo.value = '1';
      } else if (row) {
         modalTitle.textContent = 'Editar rol';
         fId.value = row.id;
         fNombre.value = row.nombre || '';
         fSlug.value = row.slug || '';
         fDesc.value = row.descripcion || '';
         fPrio.value = row.prioridad ?? 100;
         fActivo.value = row.activo ? '1' : '0';
      }

      showModal();
   }

   if (modalEl) {
      const closeEls = modalEl.querySelectorAll('[data-dismiss="modal"], .close');
      closeEls.forEach(el => {
         el.addEventListener('click', (e) => {
            e.preventDefault();
            closeModal();
         });
      });
   }

   document.addEventListener('click', (e) => {
      if (!modalIsOpen || !modalEl) return;
      if (e.target === modalEl) closeModal();
   });

   document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && modalIsOpen) closeModal();
   });

   async function saveRole() {
      const id = fId.value ? parseInt(fId.value, 10) : 0;
      const payload = {
         nombre: (fNombre.value || '').trim(),
         slug: (fSlug.value || '').trim(),
         descripcion: (fDesc.value || '').trim() || null,
         prioridad: parseInt(fPrio.value || '100', 10),
         activo: parseInt(fActivo.value || '1', 10),
      };

      if (!payload.nombre) {
         toast.warning('El nombre es obligatorio');
         return;
      }

      try {
         AppLoader?.show?.('Guardando rol…');
         let res;
         if (!id) {
            res = await Api.post(API.roles.create, payload);
         } else {
            res = await Api.put(API.roles.update, { id, ...payload });
         }

         if (res.ok) {
            toast.success('Rol guardado');
            closeModal();
            await loadRoles();
         } else {
            toast.error(res.msg || 'No se pudo guardar el rol');
         }
      } catch (err) {
         console.error(err);
         const msg = err?.payload?.msg || err?.message || 'Error al guardar rol';
         toast.error(msg);
      } finally {
         AppLoader?.hide?.();
      }
   }

   // -------- FancyTree: catálogo de permisos --------
   async function loadPermCatalogOnce() {
      if (_catalogLoaded) return;
      try {
         AppLoader?.show?.('Cargando permisos…');
         const res = await Api.get(API.permsCatalog);
         const all = res.data || [];

         const grouped = {};
         for (const it of all) {
            const clave = String(it.clave || '').trim();
            if (!clave) continue;
            const desc = (it.descripcion || '').trim();
            const mod = clave.split('.')[0] || 'otros';
            (grouped[mod] ||= []).push({ clave, descripcion: desc });
         }

         Object.keys(grouped).forEach(m => {
            grouped[m].sort((a, b) => a.clave.localeCompare(b.clave));
         });

         const source = [];
         for (const [mod, perms] of Object.entries(grouped)) {
            source.push({
               key: `mod:${mod}`,
               title: `<span class="perm-mod">${mod}</span>`,
               folder: true,
               expanded: true,
               children: perms.map(p => ({
                  key: p.clave,
                  title:
                     `<span class="perm-item">
                        <span class="desc">${p.descripcion || p.clave}</span>
                        <span class="key">(${p.clave})</span>
                      </span>`,
                  icon: 'fas fa-key',
               })),
            });
         }

         $jq(permTree).fancytree({
            extensions: ['filter'],
            checkbox: true,
            selectMode: 3,
            titlesTabbable: true,
            quicksearch: true,
            glyph: false,
            source,
            filter: {
               mode: 'dimm',
               autoApply: true,
               counter: true,
               fuzzy: false,
               highlight: true,
            },
            select: function (event, data) {
               const node = data.node;
               if (node.folder) {
                  node.visit(n => { if (!n.folder) n.setSelected(node.isSelected()); });
               }
               if (rpSave) rpSave.disabled = !_selectedRole;
            },
         });

         _catalogLoaded = true;
      } catch (err) {
         console.error(err);
         toast.error('No se pudo cargar catálogo de permisos');
      } finally {
         AppLoader?.hide?.();
      }
   }

   function clearTreeSelection() {
      const tree = getTree();
      if (!tree) return;
      tree.visit(n => n.setSelected(false));
   }

   async function loadRolePerms(roleId) {
      if (!roleId) { clearTreeSelection(); return; }

      const myToken = ++_lastPermLoadToken;
      await loadPermCatalogOnce();
      if (myToken !== _lastPermLoadToken) return;

      setTreeDisabled(true);
      clearTreeSelection();

      try {
         const res = await Api.get(API.roles.permsOf(roleId));
         if (myToken !== _lastPermLoadToken) { setTreeDisabled(false); return; }

         const data = res.data || [];
         const claves = Array.isArray(data) ? data.map(String) : [];
         const set = new Set(claves);

         const tree = getTree();
         if (!tree) { setTreeDisabled(false); return; }

         // Limpia selección actual y marca solo las claves retornadas
         tree.visit(node => {
            if (node.folder) return;
            node.setSelected(set.has(node.key));
         });

         const root = tree.getRootNode();
         if (root?.fixSelection3FromEndNodes) root.fixSelection3FromEndNodes();
         tree.render(true);

         if (rpSave) rpSave.disabled = !_selectedRole;
      } catch (err) {
         console.error(err);
         toast.error('No se pudieron cargar permisos del rol');
      } finally {
         setTreeDisabled(false);
      }
   }

   async function savePerms() {
      const row = _selectedRole;
      if (!row) {
         toast.warning('Selecciona un rol');
         return;
      }
      const tree = getTree();
      if (!tree) {
         toast.error('Árbol de permisos no disponible');
         return;
      }

      setPermBusy(true);
      setTreeDisabled(true);

      try {
         const selectedClaves = [];
         tree.getRootNode().visit(node => {
            if (!node.folder && node.isSelected()) selectedClaves.push(node.key);
         });

         const payload = {
            role_id: row.id,
            permisos: selectedClaves,
         };

         const res = await Api.patch(
            API.roles.savePerms,
            payload,
            { headers: { 'X-CSRF-Token': getCsrfToken() } }
         );

         if (res.ok) {
            toast.success('Permisos guardados');
            // recarga desde backend para asegurar sync con DB
            await loadRolePerms(row.id);
         } else {
            toast.error(res.msg || 'No se pudieron guardar los permisos');
         }
      } catch (err) {
         console.error(err);
         const msg = err?.payload?.msg || err?.message || 'Error al guardar permisos';
         toast.error(msg);
      } finally {
         setPermBusy(false);
         setTreeDisabled(false);
      }
   }

   // -------- Filtros / botones del árbol --------
   rpExpand?.addEventListener('click', () => getTree()?.expandAll(true));
   rpCollapse?.addEventListener('click', () => getTree()?.expandAll(false));
   rpSelectAll?.addEventListener('click', () => {
      const tree = getTree(); if (!tree) return;
      tree.visit(n => { if (!n.folder) n.setSelected(true); });
      if (rpSave) rpSave.disabled = !_selectedRole;
   });
   rpUnselectAll?.addEventListener('click', () => {
      const tree = getTree(); if (!tree) return;
      tree.visit(n => n.setSelected(false));
      if (rpSave) rpSave.disabled = !_selectedRole;
   });

   if (rpFilter) {
      rpFilter.addEventListener('input', () => {
         const tree = getTree();
         if (!tree) return;
         const term = rpFilter.value.trim();
         if (!term) {
            tree.clearFilter();
         } else {
            tree.filterNodes(term);
         }
      });
   }

   // -------- Eventos Grid / Toolbar --------
   btnSearch?.addEventListener('click', loadRoles);
   btnRefresh?.addEventListener('click', () => {
      qEl && (qEl.value = '');
      loadRoles();
   });
   qEl?.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') {
         e.preventDefault();
         loadRoles();
      }
   });

   btnNew?.addEventListener('click', () => openModal(true));
   btnEdit?.addEventListener('click', () => {
      const row = getSelected();
      if (!row) {
         toast.warning('Selecciona un rol');
         return;
      }
      openModal(false, row);
   });
   btnDel?.addEventListener('click', async () => {
      const row = getSelected();
      if (!row) {
         toast.warning('Selecciona un rol');
         return;
      }
      if (!window.confirm(`¿Eliminar rol "${row.nombre}"?`)) return;
      try {
         AppLoader?.show?.('Eliminando rol…');
         const res = await Api.del(API.roles.remove(row.id));
         if (res.ok) {
            toast.success('Rol eliminado');
            await loadRoles();
         } else {
            toast.error(res.msg || 'No se pudo eliminar el rol');
         }
      } catch (err) {
         console.error(err);
         toast.error(err?.payload?.msg || 'Error al eliminar rol');
      } finally {
         AppLoader?.hide?.();
      }
   });
   btnSave?.addEventListener('click', saveRole);
   rpSave?.addEventListener('click', savePerms);

   // -------- Init --------
   (function init() {
      loadRoles();
      loadPermCatalogOnce();
      if (window.__applyGates) window.__applyGates(document);
   })();
})();
