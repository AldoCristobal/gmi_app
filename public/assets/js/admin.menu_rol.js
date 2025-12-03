// /assets/js/admin.menu_rol.js
(function () {
   if (!document.querySelector('#menu-rol-page')) return;

   if (typeof Api === 'undefined') {
      console.error('Api helper no cargado');
      return;
   }
   if (!window.jQuery) {
      console.error('jQuery no cargado (FancyTree lo requiere)');
      return;
   }

   const $ = window.jQuery;

   const toast = typeof Notyf !== 'undefined'
      ? new Notyf({ duration: 2000, position: { x: 'right', y: 'top' }, dismissible: true })
      : { success: console.log, error: console.error };

   const API = {
      roles: '/api/v1/admin/roles',
      tree: '/api/v1/admin/menu-rol/tree',
      save: '/api/v1/admin/menu-rol/save'
   };

   const tableBody = document.querySelector('#mr-roles-table tbody');
   const filterInput = document.querySelector('#mr-role-filter');
   const treeEl = document.querySelector('#mr-tree');
   const btnSave = document.querySelector('#mr-save');
   const homeSelect = document.getElementById('mr-home');

   let _roles = [];
   let _activeRole = null;
   let _tree = null;

   // ---------------------
   // Roles
   // ---------------------
   async function loadRoles() {
      try {
         const r = await Api.get(API.roles);
         if (!r.ok) throw new Error(r.msg || 'Respuesta no OK');

         _roles = r.data || [];
         renderRolesTable(_roles);
      } catch (e) {
         toast.error('No se pudieron cargar los roles');
         console.error(e);
      }
   }

   function renderRolesTable(list) {
      tableBody.innerHTML = list.map(r => `
         <tr data-id="${r.id}" style="cursor:pointer;">
            <td>${r.id}</td>
            <td>${r.nombre}</td>
         </tr>
      `).join('');

      [...tableBody.querySelectorAll('tr')].forEach(tr => {
         tr.addEventListener('click', () => {
            const id = Number(tr.dataset.id);
            const role = _roles.find(x => x.id === id);
            if (role) selectRole(role);
         });
      });
   }

   function filterRoles() {
      const term = (filterInput.value || '').toLowerCase();
      const filtered = _roles.filter(r =>
         String(r.nombre || '').toLowerCase().includes(term) ||
         String(r.slug || '').toLowerCase().includes(term)
      );
      renderRolesTable(filtered);
   }

   filterInput?.addEventListener('input', filterRoles);

   // ---------------------
   // Árbol de menú del rol
   // ---------------------
   function mapTreeNode(n) {
      return {
         key: String(n.key),
         title: n.title,
         icon: n.icon || null,
         selected: !!n.selected,
         expanded: true,
         folder: !!n.folder,
         children: Array.isArray(n.children) ? n.children.map(mapTreeNode) : [],
         data: n   // guardamos la data original por si se requiere (extra.tipo, extra.vista, etc.)
      };
   }

   /**
    * Reconstruye las opciones del select de "Vista de inicio"
    * con base en los nodos del árbol.
    */
   function rebuildHomeViewSelectFromTree(source, selectedId) {
      if (!homeSelect) return;

      // Opción por defecto
      let html = '<option value="">Sin vista de inicio</option>';

      function walk(nodes) {
         nodes.forEach(n => {
            const orig = n.data || {};
            const extra = orig.extra || {};
            const vista = extra.vista || null;

            // Solo consideramos items con vista asignada
            if (vista) {
               const id = String(n.key);
               const label = orig.title || vista;
               html += `<option value="${id}">${label}</option>`;
            }

            if (Array.isArray(n.children) && n.children.length) {
               walk(n.children);
            }
         });
      }

      walk(source);

      homeSelect.innerHTML = html;

      if (selectedId) {
         homeSelect.value = String(selectedId);
      } else {
         homeSelect.value = '';
      }
   }


   async function loadTreeForRole(roleId) {
      if (!roleId || roleId <= 0) {
         if (_tree) _tree.reload([]);
         if (homeSelect) {
            homeSelect.innerHTML = '<option value="">Sin vista de inicio</option>';
            homeSelect.value = '';
         }
         return;
      }

      try {
         const url = `${API.tree}?rol_id=${encodeURIComponent(roleId)}`;
         const r = await Api.get(url);

         if (!r.ok) throw new Error(r.error?.message || r.msg || 'Respuesta no OK');

         const raw = r.data || [];
         const source = Array.isArray(raw) ? raw.map(mapTreeNode) : [];
         const homeMenuId = (r.home_menu_id != null)
            ? parseInt(r.home_menu_id, 10)
            : null;


         if (!_tree) {
            $(treeEl).fancytree({
               checkbox: true,
               selectMode: 3,
               titlesTabbable: true,
               clickFolderMode: 3,
               extensions: ['glyph'],
               glyph: { /* ... tu config actual ... */ },
               source,
               click: function (event, data) {
                  if (data.targetType === 'title' || data.targetType === 'icon') {
                     data.node.toggleSelected();
                     return false;
                  }
               }
            });
            _tree = $.ui.fancytree.getTree(treeEl);

            const $ct = $(treeEl).find('.fancytree-container');
            $ct.css({ 'max-height': '520px', 'overflow-y': 'auto', 'overscroll-behavior': 'contain' });
         } else {
            _tree.reload(source);
         }

         _tree.expandAll(true);

         // 👇 reconstruimos el select y marcamos la vista de inicio guardada
         rebuildHomeViewSelectFromTree(source, homeMenuId);

      } catch (e) {
         console.error(e);
         toast.error('No se pudo cargar el menú del rol');
      }
   }


   async function selectRole(role) {
      _activeRole = role;

      // Visual en tabla
      [...tableBody.querySelectorAll('tr')].forEach(tr => {
         const isActive = Number(tr.dataset.id) === role.id;
         tr.classList.toggle('table-active', isActive);          // dejamos esta por si acaso
         tr.classList.toggle('mr-role-selected', isActive);      // clase nueva
      });

      await loadTreeForRole(role.id);
   }

   // ---------------------
   // Guardar selección
   // ---------------------
   async function saveSelection() {
      if (!_activeRole) return toast.error('Selecciona un rol');

      const tree = $.ui.fancytree.getTree(treeEl);
      if (!tree) return toast.error('Árbol no inicializado');

      const selectedNodes = tree.getSelectedNodes();
      const menuIds = selectedNodes
         .map(n => Number(n.key))
         .filter(id => Number.isFinite(id) && id > 0);

      const homeMenuId = homeSelect && homeSelect.value
         ? parseInt(homeSelect.value, 10)
         : null;

      const payload = {
         rol_id: _activeRole.id,
         menu_ids: menuIds,
         home_menu_id: homeMenuId || null,
      };

      try {
         btnSave.disabled = true;
         btnSave.dataset._html = btnSave.innerHTML;
         btnSave.innerHTML = '<span class="spinner-border spinner-border-sm mr-1"></span>';

         const res = await Api.post(API.save, payload);

         if (res.ok) {
            toast.success('Asignación guardada');
         } else {
            toast.error(res.error?.message || res.msg || 'Error al guardar');
         }

      } catch (e) {
         console.error(e);
         toast.error('Error en el guardado');
      } finally {
         btnSave.disabled = false;
         btnSave.innerHTML = btnSave.dataset._html || '<i class="fas fa-save"></i> Guardar selección';
      }
   }

   btnSave?.addEventListener('click', saveSelection);

   // ---------------------
   // Primera carga
   // ---------------------
   loadRoles();
})();
