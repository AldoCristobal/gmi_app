// roles.js (AG Grid v29.3.5 + FancyTree)
// - Catálogo con IDs y claves
// - Guardado con IDs + claves (y deltas add/remove)
// - Refresco del árbol (batch + fix tri-state + re-render)
// - Spinner en botón Guardar + deshabilitar árbol mientras guarda/carga
// - Dark-mode: estilos mínimos en app.css

(function () {
   const DEBUG = false;
   const log = (...a) => DEBUG && console.log('[roles]', ...a);

   // ---- Guardas básicas ----
   if (typeof Api === 'undefined') { console.error('Api helper no cargado'); return; }
   if (typeof agGrid === 'undefined') { console.error('AG Grid no cargado'); return; }
   if (typeof window.jQuery === 'undefined') { console.error('jQuery no cargado (FancyTree requiere jQuery)'); return; }

   // ---- Helpers DOM ----
   const qs = (sel, ctx = document) => ctx.querySelector(sel);
   const $jq = window.jQuery;

   // ---- Notificaciones ----
   const toast = typeof Notyf !== 'undefined'
      ? new Notyf({ duration: 2200, ripple: true, position: { x: 'right', y: 'top' }, dismissible: true })
      : { success: console.log, error: console.error };

   // ---- Refs UI ----
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

   // ---- API ----
   const API = {
      roles: {
         list: '/api/v1/admin/roles',
         create: '/api/v1/admin/roles',
         update: () => `/api/v1/admin/roles`,
         remove: (id) => `/api/v1/admin/roles?id=${id}`,
         permsOf: (id) => `/api/v1/admin/roles/permisos?id=${id}`,
         savePerms: () => `/api/v1/admin/roles/permisos`
      },
      permsCatalog: '/api/v1/admin/permisos'
   };

   // ---- Estado ----
   let _selectedRole = null;
   let _lastLoadToken = 0;           // anti-race
   const mapClaveToId = new Map();   // "usuarios.ver" -> 12
   const mapIdToClave = new Map();   // 12 -> "usuarios.ver"
   let _assignedIds = new Set();     // baseline persistida (ids)

   // ---- Utilidades ----
   function getCsrfToken() {
      const m = document.querySelector('meta[name="csrf-token"]');
      return m?.getAttribute('content') || '';
   }

   function getTree() {
      return $jq.ui?.fancytree?.getTree(permTree);
   }

   // Spinner y deshabilitar UI durante operaciones
   let _saveBtnHTML = '';
   function setBusySaving(flag) {
      if (!rpSave) return;
      if (flag) {
         if (!_saveBtnHTML) _saveBtnHTML = rpSave.innerHTML;
         rpSave.disabled = true;
         rpSave.innerHTML = '<span class="spinner-border spinner-border-sm mr-1"></span>';
      } else {
         rpSave.innerHTML = _saveBtnHTML || '<i class="fas fa-save"></i>';
         rpSave.disabled = !_selectedRole;
      }
   }
   function setTreeDisabled(flag) {
      // Clase CSS bloquea interacciones y baja opacidad
      const $ct = $jq(permTree).find('.fancytree-container');
      if (flag) $ct.addClass('ft-busy');
      else $ct.removeClass('ft-busy');
   }

   // 🔧 Refresca selección de FancyTree de forma segura (batch)
   function applySelectionsToTree(keysToMark) {
      const tree = getTree();
      if (!tree) return;

      const term = (rpFilter?.value || '').trim();
      const $ct = $jq(permTree).find('.fancytree-container');
      const prevScroll = $ct.scrollTop();

      if (term) tree.clearFilter();

      // limpiar selección actual (hojas)
      tree.visit(n => { if (!n.folder && n.isSelected()) n.setSelected(false); });

      // índice por key existente
      const existing = new Map();
      tree.getRootNode().visit(n => { if (!n.folder) existing.set(n.key, n); });

      const match = (k) => {
         if (existing.has(k)) return existing.get(k);
         const noAdmin = k.replace(/^admin\./, '');
         if (existing.has(noAdmin)) return existing.get(noAdmin);
         const parts = k.split('.');
         if (parts.length >= 2) {
            const last2 = parts.slice(-2).join('.');
            if (existing.has(last2)) return existing.get(last2);
         }
         const last1 = parts[parts.length - 1];
         if (existing.has(last1)) return existing.get(last1);
         return null;
      };

      for (const k of keysToMark) {
         const node = match(k);
         if (node) node.setSelected(true);
      }

      const root = tree.getRootNode();
      if (root && root.fixSelection3FromEndNodes) root.fixSelection3FromEndNodes();
      tree.render(true);

      if (term) tree.filterNodes(term);
      $ct.scrollTop(prevScroll);
   }

   // ---- Grid Roles (AG Grid v29.3.5) ----
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
         log('rowClicked -> selected?', willSelect, e.data);
      },
   };

   new agGrid.Grid(gridEl, gridOptions);

   function getSelected() {
      const rows = gridOptions.api?.getSelectedRows?.() || [];
      return rows[0] || null;
   }

   function updateButtons() {
      _selectedRole = getSelected();
      if (btnEdit) btnEdit.disabled = !_selectedRole;
      if (btnDel) btnDel.disabled = !_selectedRole;
      if (rpSave) rpSave.disabled = !_selectedRole;

      if (rpLabel) rpLabel.textContent = _selectedRole ? `ID ${_selectedRole.id} — ${_selectedRole.nombre}` : '—';

      if (_selectedRole) loadRolePerms(_selectedRole.id);
      else clearChecks();
   }

   // ---- Cargar Roles ----
   async function loadRoles() {
      try {
         AppLoader?.show('Cargando roles…');
         const q = qEl?.value?.trim() || '';
         const r = await Api.get(API.roles.list + (q ? ('?q=' + encodeURIComponent(q)) : ''));
         const rows = r.data || [];
         gridOptions.api?.setRowData(rows);
         gridOptions.api?.deselectAll?.();
         updateButtons();
      } catch (err) {
         console.error(err);
         toast.error('No se pudo cargar roles');
      } finally {
         AppLoader?.hide?.();
      }
   }

   // ---- Modal Nuevo / Editar ----
   function openModal(create = true, row = null) {
      if (create) {
         modalTitle.textContent = 'Nuevo rol';
         fId.value = ''; fNombre.value = ''; fSlug.value = ''; fDesc.value = '';
         fPrio.value = '100'; fActivo.value = '1';
      } else if (row) {
         modalTitle.textContent = 'Editar rol';
         fId.value = row.id;
         fNombre.value = row.nombre || '';
         fSlug.value = row.slug || '';
         fDesc.value = row.descripcion || '';
         fPrio.value = row.prioridad ?? 100;
         fActivo.value = row.activo ? '1' : '0';
      }
      modalEl.classList.add('show');
      modalEl.style.display = 'block';
      document.body.classList.add('modal-open');
      if (!document.querySelector('.modal-backdrop')) {
         const bd = document.createElement('div');
         bd.className = 'modal-backdrop fade show';
         document.body.appendChild(bd);
      }
   }
   function closeModal() {
      modalEl.classList.remove('show');
      modalEl.style.display = 'none';
      document.querySelector('.modal-backdrop')?.remove();
      document.body.classList.remove('modal-open');
   }
   modalEl?.querySelector('.close')?.addEventListener('click', closeModal);

   // ---- Guardar Rol ----
   async function saveRole() {
      const id = fId.value ? parseInt(fId.value, 10) : 0;
      const payload = {
         nombre: (fNombre.value || '').trim(),
         slug: (fSlug.value || '').trim(),
         descripcion: (fDesc.value || '').trim() || null,
         prioridad: parseInt(fPrio.value || '100', 10),
         activo: parseInt(fActivo.value || '1', 10),
      };
      if (!payload.nombre) { toast.error('Nombre es requerido'); return; }

      try {
         AppLoader?.show('Guardando…');
         let res;
         if (!id) res = await Api.post(API.roles.create, payload);
         else res = await Api.put(API.roles.update(), { id, ...payload });

         if (res.ok) {
            toast.success('Guardado');
            closeModal();
            await loadRoles();
         } else {
            toast.error(res.msg || 'No se pudo guardar');
         }
      } catch (err) {
         console.error(err);
         toast.error(err?.payload?.msg || err?.message || 'Error al guardar');
      } finally {
         AppLoader?.hide?.();
      }
   }

   // ---- FancyTree: catálogo e inicialización (una sola vez) ----
   async function loadPermCatalogOnce() {
      if ($jq(permTree).data('fancytree-initialized')) return;

      try {
         const r = await Api.get(API.permsCatalog);
         const all = r.data || [];

         // Agrupar y llenar mapas clave<->id
         const grouped = {};
         mapClaveToId.clear();
         mapIdToClave.clear();

         for (const it of all) {
            const id = Number(it.id ?? it.perm_id ?? it.permId);
            const clave = String(it.clave || '').trim();
            if (!clave) continue;

            if (Number.isFinite(id)) {
               mapClaveToId.set(clave, id);
               mapIdToClave.set(id, clave);
            }
            const mod = clave.split('.')[0] || 'otros';
            (grouped[mod] ||= []).push({
               id,
               clave,
               descripcion: (it.descripcion || '').trim()
            });
         }
         Object.keys(grouped).forEach(m => grouped[m].sort((a, b) => a.clave.localeCompare(b.clave)));

         // Construir source
         const source = [];
         for (const [mod, perms] of Object.entries(grouped)) {
            source.push({
               key: `mod:${mod}`,
               title: `<span class="perm-mod">${mod}</span>`,
               folder: true,
               expanded: true,
               children: perms.map(p => ({
                  key: p.clave, // usamos clave como key del nodo
                  title: `<span class="perm-item"><span class="desc">${p.descripcion || p.clave}</span> <span class="key">(${p.clave})</span></span>`,
                  icon: "fas fa-key",
                  data: { perm_id: Number.isFinite(p.id) ? p.id : null }
               }))
            });
         }

         // Inicializar FancyTree
         $jq(permTree).fancytree({
            extensions: ["filter"],
            checkbox: true,
            selectMode: 3,
            titlesTabbable: true,
            quicksearch: true,
            glyph: false,
            source,
            filter: {
               mode: "dimm",
               autoApply: true,
               counter: true,
               fuzzy: false,
               highlight: true
            },
            select: function (event, data) {
               const node = data.node;
               // Si es carpeta, propaga su estado a todas las hojas
               if (node.folder) node.visit(n => { if (!n.folder) n.setSelected(node.isSelected()); });
               if (rpSave) rpSave.disabled = !_selectedRole;
            }
         });

         $jq(permTree).data('fancytree-initialized', true);
      } catch (err) {
         console.error(err);
         toast.error('No se pudo cargar catálogo de permisos');
      }
   }

   // ---- Limpiar selección del árbol ----
   function clearChecks() {
      const tree = getTree();
      if (!tree) return;
      tree.visit(node => node.setSelected(false));
   }

   // ---- Cargar permisos del rol (acepta IDs o claves) ----
   async function loadRolePerms(roleId) {
      const myToken = ++_lastLoadToken;
      await loadPermCatalogOnce();
      if (myToken !== _lastLoadToken) return;

      setTreeDisabled(true);
      clearChecks();
      _assignedIds = new Set();
      if (!roleId) { setTreeDisabled(false); return; }

      try {
         const r = await Api.get(API.roles.permsOf(roleId));
         if (myToken !== _lastLoadToken) { setTreeDisabled(false); return; }

         // Normaliza respuesta a un array (ids o claves u objetos)
         let arr = [];
         const d = r.data;
         if (Array.isArray(d)) arr = d;
         else if (d && Array.isArray(d.permisos)) arr = d.permisos;
         else if (d && Array.isArray(d.permisos_ids)) arr = d.permisos_ids;

         // Convierte todo a claves para pintar + llena _assignedIds
         const keysToMark = [];
         for (const v of arr) {
            if (typeof v === 'number') {
               const clave = mapIdToClave.get(v);
               if (clave) { keysToMark.push(clave); _assignedIds.add(v); }
            } else if (typeof v === 'string') {
               keysToMark.push(v);
               const id = mapClaveToId.get(v);
               if (Number.isFinite(id)) _assignedIds.add(id);
            } else if (v && typeof v === 'object') {
               const id = Number(v.id ?? v.perm_id ?? v.permId);
               const clave = v.clave || (Number.isFinite(id) ? mapIdToClave.get(id) : null);
               if (clave) keysToMark.push(clave);
               if (Number.isFinite(id)) _assignedIds.add(id);
            }
         }

         applySelectionsToTree(keysToMark);
         if (rpSave) rpSave.disabled = !_selectedRole;
      } catch (err) {
         console.error(err);
         toast.error('No se pudieron cargar permisos del rol');
      } finally {
         setTreeDisabled(false);
      }
   }

   // ---- Guardar selección (envía claves e IDs + deltas) ----
   async function savePerms({ silent = false } = {}) {
      const row = _selectedRole;
      if (!row) { toast.error('Selecciona un rol'); return; }

      const tree = getTree();
      if (!tree) { toast.error('Árbol no disponible'); return; }

      // Bloquea UI local
      setBusySaving(true);
      setTreeDisabled(true);

      const selectedKeys = [];
      const nowIds = [];
      tree.getRootNode().visit(n => {
         if (!n.folder && n.isSelected()) {
            selectedKeys.push(n.key);
            const id = n.data?.perm_id ?? mapClaveToId.get(n.key) ?? null;
            if (Number.isFinite(id)) nowIds.push(id);
         }
      });

      // Deltas contra base persistida
      const nowSet = new Set(nowIds);
      const add_ids = [];
      const remove_ids = [];
      for (const id of nowSet) if (!_assignedIds.has(id)) add_ids.push(id);
      for (const id of _assignedIds) if (!nowSet.has(id)) remove_ids.push(id);

      const payload = {
         role_id: row.id,
         permisos: selectedKeys,          // por compatibilidad (claves)
         permisos_ids: nowIds,            // set completo actual (ids)
         add_ids,                         // delta altas
         remove_ids,                      // delta bajas
         mode: 'replace',                 // si el backend lo soporta
         clear_all: nowIds.length === 0   // si el backend lo soporta
      };

      try {
         const res = await Api.patch(
            API.roles.savePerms(),
            payload,
            { headers: { 'X-CSRF-Token': getCsrfToken() } }
         );

         if (res.ok) {
            const saved = Number(res.saved ?? add_ids.length + remove_ids.length);
            if (!silent) {
               if (saved === 0 && (add_ids.length || remove_ids.length)) {
                  toast.error('El servidor no aplicó cambios (revisa si usa add_ids/remove_ids o mode=replace)');
               } else {
                  toast.success('Permisos guardados');
               }
            }
            // baseline local y repinta desde backend
            _assignedIds = new Set(nowIds);
            await loadRolePerms(row.id);
         } else {
            toast.error(res.msg || 'No se pudieron guardar');
         }
      } catch (err) {
         console.error(err);
         toast.error(err?.payload?.msg || 'Error al guardar permisos');
      } finally {
         setBusySaving(false);
         setTreeDisabled(false);
      }
   }

   // ---- Eventos Grid/UI ----
   btnSearch?.addEventListener('click', loadRoles);
   btnRefresh?.addEventListener('click', loadRoles);
   qEl?.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); loadRoles(); } });

   btnNew?.addEventListener('click', () => openModal(true));
   btnEdit?.addEventListener('click', () => {
      const row = getSelected();
      if (!row) { toast.error('Selecciona un rol'); return; }
      openModal(false, row);
   });
   btnDel?.addEventListener('click', async () => {
      const row = getSelected();
      if (!row) { toast.error('Selecciona un rol'); return; }
      if (!confirm(`¿Eliminar rol "${row.nombre}"?`)) return;
      try {
         AppLoader?.show('Eliminando…');
         const res = await Api.del(API.roles.remove(row.id));
         if (res.ok) { toast.success('Eliminado'); await loadRoles(); }
         else { toast.error(res.msg || 'No se pudo eliminar'); }
      } catch (err) {
         console.error(err); toast.error(err?.payload?.msg || 'Error al eliminar');
      } finally {
         AppLoader?.hide?.();
      }
   });
   btnSave?.addEventListener('click', saveRole);

   rpSave?.addEventListener('click', () => savePerms({ silent: false }));
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

   // ---- Primera carga ----
   loadRoles();
   loadPermCatalogOnce();
})();
