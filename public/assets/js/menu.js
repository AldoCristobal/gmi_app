// public/assets/js/menu.js
(function () {
   const DEBUG = false;
   const log = (...a) => DEBUG && console.log('[menu]', ...a);

   if (!window.jQuery) { console.error('jQuery no cargado'); return; }
   const $ = window.jQuery;

   if (typeof Api === 'undefined') { console.error('Api helper no cargado'); return; }
   if (!document.querySelector('#menu-page')) return; // no ejecutar si no es la vista

   // ------- API endpoints -------
   const API = {
      list: (ns) => `/api/v1/admin/menu?namespace=${encodeURIComponent(ns || 'sidebar')}`,
      create: `/api/v1/admin/menu`,
      update: (id) => `/api/v1/admin/menu?id=${id}`,
      remove: (id, mode = 'cascade') => `/api/v1/admin/menu?id=${id}&mode=${mode}`,
      reorder: `/api/v1/admin/menu/reorder`,
   };

   // ------- Notificaciones -------
   const toast = typeof Notyf !== 'undefined'
      ? new Notyf({ duration: 2200, position: { x: 'right', y: 'top' }, dismissible: true })
      : { success: console.log, error: console.error };

   // ------- Refs UI -------
   const treeEl = document.querySelector('#menu-tree');
   const filterEl = document.querySelector('#menu-filter');
   const btnAddRoot = document.querySelector('#menu-add-root');
   const btnAddChild = document.querySelector('#menu-add-child');
   const btnDel = document.querySelector('#menu-del');
   const btnExpand = document.querySelector('#menu-expand');
   const btnCollapse = document.querySelector('#menu-collapse');
   const btnSaveOrder = document.querySelector('#menu-save-order');

   const fId = document.querySelector('#m-id');
   const fEtiqueta = document.querySelector('#m-etiqueta');
   const fTipo = document.querySelector('#m-tipo');
   const fVista = document.querySelector('#m-vista');
   const fSlug = document.querySelector('#m-slug');
   const fUrl = document.querySelector('#m-url');
   const fTarget = document.querySelector('#m-target');
   const fIcono = document.querySelector('#m-icono');
   const fPerm = document.querySelector('#m-perm');
   const fBadgeText = document.querySelector('#m-badge-text');
   const fBadgeVar = document.querySelector('#m-badge-variant');
   const fVisible = document.querySelector('#m-visible');
   const fNamespace = document.querySelector('#m-namespace');
   const fOrden = document.querySelector('#m-orden');
   const btnReset = document.querySelector('#m-reset');
   const btnSave = document.querySelector('#m-save');

   // ------- Estado -------
   let _orderDirty = false;
   let _selectedNode = null;
   let _lastJsonSnapshot = null; // para "Deshacer"
   let _loading = false;

   // ------- Util -------
   function setBusyTree(flag) {
      const $ct = $(treeEl).find('.fancytree-container');
      if (flag) $ct.addClass('ft-busy'); else $ct.removeClass('ft-busy');
   }
   function setBusyButtonSave(flag) {
      if (!btnSave) return;
      if (flag) {
         btnSave.disabled = true;
         if (!btnSave.dataset._html) btnSave.dataset._html = btnSave.innerHTML;
         btnSave.innerHTML = '<span class="spinner-border spinner-border-sm mr-1"></span>';
      } else {
         btnSave.innerHTML = btnSave.dataset._html || '<i class="fas fa-save"></i> Guardar';
         btnSave.disabled = false;
      }
   }
   function setBusyOrder(flag) {
      if (!btnSaveOrder) return;
      btnSaveOrder.disabled = !!flag;
      btnSaveOrder.classList.toggle('d-none', !_orderDirty && !flag);
      if (flag) {
         if (!btnSaveOrder.dataset._html) btnSaveOrder.dataset._html = btnSaveOrder.innerHTML;
         btnSaveOrder.innerHTML = '<span class="spinner-border spinner-border-sm mr-1"></span>';
      } else if (btnSaveOrder.dataset._html) {
         btnSaveOrder.innerHTML = btnSaveOrder.dataset._html;
      }
   }

   function valStr(v) { return (v ?? '').toString().trim(); }
   function intOrNull(v) { const n = parseInt(v, 10); return Number.isFinite(n) ? n : null; }

   function normalizeForForm(nodeData) {
      const d = nodeData || {};
      return {
         id: d.id ?? null,
         parent_id: d.parent_id ?? null,
         etiqueta: valStr(d.etiqueta),
         tipo: d.tipo || 'item',
         vista: valStr(d.vista),
         slug: valStr(d.slug),
         url_externa: valStr(d.url_externa),
         target: d.target || '_self',
         icono: valStr(d.icono),
         requiere_permiso: valStr(d.requiere_permiso),
         badge_text: valStr(d.badge_text),
         badge_variant: valStr(d.badge_variant),
         visible: (d.visible == null ? 1 : Number(d.visible)) ? 1 : 0,
         namespace: d.namespace || 'sidebar',
         orden: d.orden ?? 0
      };
   }

   function fillForm(d) {
      const n = normalizeForForm(d);
      fId.value = n.id || '';
      fEtiqueta.value = n.etiqueta;
      fTipo.value = n.tipo;
      fVista.value = n.vista;
      fSlug.value = n.slug;
      fUrl.value = n.url_externa;
      fTarget.value = n.target;
      fIcono.value = n.icono;
      fPerm.value = n.requiere_permiso;
      fBadgeText.value = n.badge_text;
      fBadgeVar.value = n.badge_variant;
      fVisible.value = String(n.visible);
      fNamespace.value = n.namespace;
      fOrden.value = n.orden;

      toggleFieldsByTipo(n.tipo);
      _lastJsonSnapshot = JSON.stringify(n);
   }

   function readForm() {
      return {
         id: intOrNull(fId.value),
         etiqueta: valStr(fEtiqueta.value),
         tipo: fTipo.value,
         vista: valStr(fVista.value),
         slug: valStr(fSlug.value),
         url_externa: valStr(fUrl.value),
         target: fTarget.value,
         icono: valStr(fIcono.value),
         requiere_permiso: valStr(fPerm.value),
         badge_text: valStr(fBadgeText.value),
         badge_variant: valStr(fBadgeVar.value),
         visible: Number(fVisible.value),
         namespace: valStr(fNamespace.value),
         // parent_id y orden se gestionan vía árbol (DnD/crear hijo), no desde el form directo
      };
   }

   function toggleFieldsByTipo(tipo) {
      const showItem = (tipo === 'item');
      const showExt = (tipo === 'external');

      // Vista+Slug están en la misma fila (fVista.closest('.form-row'))
      const vistaRow = fVista?.closest('.form-row');
      const urlRow = fUrl?.closest('.form-row');

      if (vistaRow) vistaRow.style.display = showItem ? '' : 'none';
      if (urlRow) urlRow.style.display = showExt ? '' : 'none';

      if (tipo === 'header' || tipo === 'divider') {
         if (vistaRow) vistaRow.style.display = 'none';
         if (urlRow) urlRow.style.display = 'none';
      }
   }

   function slugify(s) {
      return String(s || '')
         .toLowerCase()
         .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
         .replace(/[^a-z0-9]+/g, '-')
         .replace(/(^-|-$)/g, '')
         .slice(0, 80);
   }

   // ------- FancyTree helpers -------
   function getTree() { return $.ui.fancytree.getTree(treeEl); }

   function rebuildTreeFromFlat(flat) {
      // flat: array con registros {id,parent_id, ...}
      const byId = new Map();
      const roots = [];
      flat.forEach(it => {
         const c = { ...it, key: it.id, title: renderNodeTitle(it), folder: true, children: [] };
         byId.set(it.id, c);
      });
      flat.forEach(it => {
         const n = byId.get(it.id);
         if (it.parent_id == null) roots.push(n);
         else {
            const p = byId.get(it.parent_id);
            if (p) p.children.push(n);
            else roots.push(n); // si parent roto, súbelo como raíz
         }
      });
      // ordenar por orden en cada nivel
      function sortChildren(nodeArr) {
         nodeArr.sort((a, b) => (a.orden ?? 0) - (b.orden ?? 0) || String(a.etiqueta).localeCompare(b.etiqueta));
         nodeArr.forEach(n => n.children && sortChildren(n.children));
      }
      sortChildren(roots);
      return roots;
   }

   function renderNodeTitle(d) {
      const icon = d.icono ? `<i class="${d.icono} mr-1"></i>` : `<i class="far fa-circle mr-1"></i>`;
      const badge = d.badge_text ? `<span class="badge badge-${d.badge_variant || 'info'} ml-2">${d.badge_text}</span>` : '';
      const dim = Number(d.visible ?? 1) ? '' : ' <span class="text-muted">(oculto)</span>';
      return `${icon}<span>${escapeHtml(d.etiqueta || '(sin etiqueta)')}</span>${badge}${dim}`;
   }

   function escapeHtml(s) {
      return (s || '').replace(/[&<>"']/g, (m) => ({
         '&': '&amp;',
         '<': '&lt;',
         '>': '&gt;',
         '"': '&quot;',
         "'": '&#39;'
      }[m]));
   }

   function collectTreeOrder() {
      const tree = getTree();
      const out = [];
      function walk(node, parentId) {
         const children = node.getChildren() || [];
         children.forEach((ch, idx) => {
            const d = ch.data || {};
            out.push({
               id: d.id,
               parent_id: parentId,
               orden: idx,
               namespace: d.namespace || 'sidebar'
            });
            walk(ch, d.id);
         });
      }
      walk(tree.getRootNode(), null);
      return out;
   }

   function markOrderDirty(flag) {
      _orderDirty = !!flag;
      if (btnSaveOrder) btnSaveOrder.classList.toggle('d-none', !_orderDirty);
   }

   // ------- Carga árbol -------
   async function loadMenu(selectId = null) {
      try {
         _loading = true;
         setBusyTree(true);

         const ns = fNamespace.value || 'sidebar';
         const r = await Api.get(API.list(ns));
         let data = r.data || [];

         // Acepta nested (con children) o flat
         let source;
         if (Array.isArray(data) && data.length && data[0] && Array.isArray(data[0].children)) {
            // nested
            source = data.map(n => mapNodeForTree(n));
         } else {
            // flat
            source = rebuildTreeFromFlat(data);
         }

         const exists = $(treeEl).data('fancytree-initialized');
         if (!exists) {
            $(treeEl).fancytree({
               extensions: ["filter", "dnd5"],
               quicksearch: true,
               titlesTabbable: true,
               glyph: false,
               source,
               filter: {
                  mode: "dimm",
                  autoApply: true,
                  counter: true,
                  highlight: true
               },
               activate: (ev, data) => {
                  _selectedNode = data.node;
                  fillForm(data.node.data);
               },
               dnd5: {
                  preventVoidMoves: true,
                  preventRecursive: true, // <- nombre correcto de la opción
                  dragStart: (node, data) => true,
                  dragEnter: (node, data) => {
                     const targetType = node.data?.tipo || 'item';
                     // No permitir soltar "over" sobre divisores: solo before/after
                     if (targetType === 'divider') return ['before', 'after'];
                     return true; // para item/external/header se permiten before/after/over
                  },
                  dragDrop: (node, data) => {
                     data.otherNode.moveTo(node, data.hitMode);
                     data.otherNode.setTitle(renderNodeTitle(data.otherNode.data));
                     markOrderDirty(true);
                  }
               }
            });
            $(treeEl).data('fancytree-initialized', true);

            const $ct = $(treeEl).find('.fancytree-container');
            $ct.css({
               'max-height': '520px',
               'overflow-y': 'auto',
               'overscroll-behavior': 'contain'
            });
         } else {
            getTree().reload(source);
         }

         // Expandir un poco y seleccionar
         const tree = getTree();
         if (tree) {
            tree.expandAll(true);
            if (selectId) {
               const node = tree.getNodeByKey(String(selectId));
               node && node.setActive();
            } else {
               const first = tree.getFirstChild();
               first && first.setActive();
            }
         }

         markOrderDirty(false);
      } catch (err) {
         console.error(err);
         toast.error('No se pudo cargar el menú');
      } finally {
         _loading = false;
         setBusyTree(false);
      }
   }

   function mapNodeForTree(n) {
      return {
         key: String(n.id),
         title: renderNodeTitle(n),
         folder: true,
         children: Array.isArray(n.children) ? n.children.map(mapNodeForTree) : [],
         data: { ...n }
      };
   }

   // ------- Acciones: Crear / Eliminar -------
   async function createItem(parentId = null) {
      try {
         setBusyTree(true);
         const payload = {
            parent_id: parentId,
            etiqueta: 'Nuevo ítem',
            tipo: 'header',
            namespace: fNamespace.value || 'sidebar',
            visible: 1
         };
         const res = await Api.post(API.create, payload);
         if (res.ok && res.data?.id) {
            toast.success('Ítem creado');
            await loadMenu(res.data.id);
         } else {
            toast.error(res.msg || 'No se pudo crear');
         }
      } catch (err) {
         console.error(err);
         toast.error(err?.payload?.msg || 'Error al crear');
      } finally {
         setBusyTree(false);
      }
   }

   async function deleteItem() {
      const node = _selectedNode;
      if (!node) return toast.error('Selecciona un ítem');
      const d = node.data || {};
      if (!confirm(`¿Eliminar "${d.etiqueta}"?\n\nSi tiene hijos, se eliminarán también.`)) return;
      try {
         setBusyTree(true);
         const res = await Api.del(API.remove(d.id, 'cascade'));
         if (res.ok) {
            toast.success('Eliminado');
            await loadMenu();
         } else {
            toast.error(res.msg || 'No se pudo eliminar');
         }
      } catch (err) {
         console.error(err);
         toast.error(err?.payload?.msg || 'Error al eliminar');
      } finally {
         setBusyTree(false);
      }
   }

   // ------- Guardar detalle -------
   function validateForm(data) {
      if (!data.etiqueta) return 'La etiqueta es obligatoria';
      if (data.tipo === 'item') {
         if (!data.vista) return 'Para tipo "item", la vista es obligatoria';
         if (data.url_externa) return 'No mezcles vista con URL externa';
      }
      if (data.tipo === 'external') {
         if (!data.url_externa) return 'Para tipo "external", la URL es obligatoria';
         if (data.vista) return 'No mezcles URL externa con vista';
      }
      return null;
   }

   async function saveDetail() {
      const node = _selectedNode;
      if (!node) return toast.error('Selecciona un ítem');
      const d = readForm();
      d.id = Number(node.data.id);
      if (!d.slug) d.slug = slugify(d.etiqueta);

      const err = validateForm(d);
      if (err) return toast.error(err);

      try {
         setBusyButtonSave(true);
         setBusyTree(true);
         const res = await Api.put(API.update(d.id), d);
         if (res.ok) {
            toast.success('Guardado');
            node.data = { ...node.data, ...d };
            node.setTitle(renderNodeTitle(node.data));
            _lastJsonSnapshot = JSON.stringify(normalizeForForm(node.data));
         } else {
            toast.error(res.msg || 'No se pudo guardar');
         }
      } catch (e) {
         console.error(e);
         toast.error(e?.payload?.msg || 'Error al guardar');
      } finally {
         setBusyButtonSave(false);
         setBusyTree(false);
      }
   }

   function resetDetail() {
      if (!_selectedNode) return;
      const base = normalizeForForm(_selectedNode.data);
      const snap = _lastJsonSnapshot ? JSON.parse(_lastJsonSnapshot) : base;
      fillForm({ ..._selectedNode.data, ...snap });
   }

   // ------- Guardar orden (reorder/move) -------
   async function saveOrder() {
      try {
         setBusyOrder(true);
         setBusyTree(true);
         const changes = collectTreeOrder();
         const res = await Api.patch(API.reorder, changes);
         if (res.ok) {
            toast.success('Orden guardado');
            markOrderDirty(false);
            await loadMenu(_selectedNode?.data?.id);
         } else {
            toast.error(res.msg || 'No se pudo guardar el orden');
         }
      } catch (err) {
         console.error(err);
         toast.error(err?.payload?.msg || 'Error al guardar el orden');
      } finally {
         setBusyOrder(false);
         setBusyTree(false);
      }
   }

   // ------- Filtro -------
   let _filterTimer = null;
   filterEl?.addEventListener('input', () => {
      const tree = getTree(); if (!tree) return;
      const term = (filterEl.value || '').trim();
      if (_filterTimer) clearTimeout(_filterTimer);
      _filterTimer = setTimeout(() => {
         if (term) tree.filterNodes(term);
         else tree.clearFilter();
      }, 150);
   });

   // ------- Eventos toolbar -------
   btnAddRoot?.addEventListener('click', () => createItem(null));
   btnAddChild?.addEventListener('click', () => {
      const node = _selectedNode;
      const pid = node?.data?.id ?? null;
      createItem(pid);
   });
   btnDel?.addEventListener('click', deleteItem);
   btnExpand?.addEventListener('click', () => getTree()?.expandAll(true));
   btnCollapse?.addEventListener('click', () => getTree()?.expandAll(false));
   btnSaveOrder?.addEventListener('click', saveOrder);

   // ------- Eventos formulario -------
   fTipo?.addEventListener('change', () => toggleFieldsByTipo(fTipo.value));
   btnSave?.addEventListener('click', saveDetail);
   btnReset?.addEventListener('click', resetDetail);

   // Autogenerar slug si está vacío mientras escribes la etiqueta
   fEtiqueta?.addEventListener('input', () => {
      if (!fSlug.value.trim()) fSlug.value = slugify(fEtiqueta.value);
   });

   // ------- Primera carga -------
   loadMenu();

   // Aplicar gating de permisos en esta vista
   if (window.__applyGates) {
      window.__applyGates(document);
   }

})();
