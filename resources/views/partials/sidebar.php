<!-- CSS mínimo para colapso/expansión sin plugin -->
<style>
   .nav-sidebar .nav-treeview {
      display: none;
   }

   .nav-sidebar .menu-open>.nav-treeview {
      display: block !important;
   }

   .nav-sidebar .has-treeview>a {
      cursor: pointer;
   }

   /* Indentación consistente por niveles */
   .nav-sidebar .nav-treeview>.nav-item>.nav-link {
      padding-left: 2rem !important;
   }

   .nav-sidebar .nav-treeview .nav-treeview>.nav-item>.nav-link {
      padding-left: 2.75rem !important;
   }

   .nav-sidebar .nav-treeview .nav-treeview .nav-treeview>.nav-item>.nav-link {
      padding-left: 3.5rem !important;
   }

   /* Tono un pelín más pequeño/ligero para hijos (opcional) */
   .nav-sidebar .nav-item.level-1>.nav-link {
      font-size: .94rem;
      opacity: .95;
   }

   .nav-sidebar .nav-item.level-2>.nav-link {
      font-size: .92rem;
      opacity: .9;
   }

   /* Asegura que el submenú se oculte/muestre con nuestro toggle manual */
   .nav-sidebar .nav-treeview {
      display: none;
   }

   .nav-sidebar .menu-open>.nav-treeview {
      display: block !important;
   }
</style>

<aside class="main-sidebar sidebar-dark-primary elevation-4">
   <!-- Brand -->
   <a href="/" class="brand-link">
      <i class="fas fa-sitemap brand-image img-circle elevation-3" style="opacity:.8"></i>
      <span class="brand-text font-weight-light">ERP GMI</span>
   </a>

   <!-- Sidebar -->
   <div class="sidebar">
      <!-- Sidebar Menu -->
      <nav class="mt-2">
         <!-- Nota: quitamos data-widget="treeview" -->
         <ul id="sidebar-menu"
            class="nav nav-pills nav-sidebar flex-column"
            role="menu"
            data-accordion="true"><!-- dinámico --></ul>
      </nav>
   </div>
</aside>

<script>
   (() => {
      if (window.__SIDEBAR_BUILT__) return;
      window.__SIDEBAR_BUILT__ = true;

      const BUILD_TAG = 'sidebar-build-v6';
      const OPEN_KEY = 'sidebarOpen';
      console.log('[sidebar]', BUILD_TAG, 'init');

      const esc = (s) => String(s ?? '').replace(/[&<>"']/g, m => ({
         '&': '&amp;',
         '<': '&lt;',
         '>': '&gt;',
         '"': '&quot;',
         "'": '&#39;'
      } [m]));
      const aside = document.querySelector('aside.main-sidebar');
      const ul = aside?.querySelector('#sidebar-menu') || aside?.querySelector('ul.nav-sidebar');
      if (!ul) {
         console.warn('[sidebar] UL no encontrado');
         return;
      }

      // Helpers visuales
      const makeBadge = (it) => it.badge_text ? `<span class="right badge badge-${esc(it.badge_variant || 'info')}">${esc(it.badge_text)}</span>` : '';
      const linkHref = (it) => (it.tipo === 'external' && it.url_externa) ? it.url_externa : (it.vista ? '/' + String(it.vista).replace(/^\/+/, '') : '#');
      const linkTarget = (it) => (it.tipo === 'external' && it.target === '_blank') ? ' target="_blank" rel="noopener"' : '';

      // Persistencia
      const getOpen = () => {
         try {
            return new Set(JSON.parse(localStorage.getItem(OPEN_KEY) || '[]'));
         } catch {
            return new Set();
         }
      };
      const setOpen = (s) => {
         try {
            localStorage.setItem(OPEN_KEY, JSON.stringify([...s]));
         } catch {}
      };

      // Encuentra nodo activo y ancestros en el árbol JSON
      function findActive(tree, path) {
         let activeId = null;
         const ancestors = new Set();

         function dfs(nodes, trail) {
            for (const n of nodes) {
               const vista = String(n.vista || '');
               const isActive = vista && (path === vista || path.startsWith(vista + '/'));
               if (isActive) {
                  activeId = n.id ?? null;
                  trail.forEach(id => ancestors.add(String(id)));
                  return true;
               }
               if (n.children && n.children.length) {
                  if (dfs(n.children, [...trail, n.id ?? null].filter(x => x != null))) return true;
               }
            }
            return false;
         }
         dfs(tree, []);
         return {
            activeId,
            ancestors
         };
      }

      // Construcción DOM
      function buildNode(it, level = 0) {
         // headers/dividers
         if (it.tipo === 'header') {
            const li = document.createElement('li');
            li.className = 'nav-header';
            li.textContent = it.etiqueta || '';
            return li;
         }
         if (it.tipo === 'divider') {
            const li = document.createElement('li');
            li.className = 'nav-header p-1';
            li.innerHTML = '<hr class="m-0">';
            return li;
         }

         const hasChildren = Array.isArray(it.children) && it.children.length > 0;
         const li = document.createElement('li');
         li.className = 'nav-item' + (hasChildren ? ' has-treeview' : '');
         if (it.id != null) li.dataset.mid = String(it.id);
         li.classList.add(`level-${level}`);

         // icono: raíz usa el icono propio; hijos usan “bullet”
         const iconHtml = level > 0 ? '<i class="far fa-circle nav-icon"></i>' : `<i class="nav-icon ${esc(it.icono || 'fas fa-circle')}"></i>`;
         const href = linkHref(it);
         const target = linkTarget(it);
         const arrow = hasChildren ? '<i class="right fas fa-angle-left"></i>' : '';

         li.innerHTML =
            `<a class="nav-link" href="${esc(href)}"${target}${hasChildren?' aria-haspopup="true" aria-expanded="false"':''}>` +
            `${iconHtml}` +
            `<p>${esc(it.etiqueta || '')}${makeBadge(it)}${arrow}</p>` +
            `</a>`;

         if (hasChildren) {
            const ulc = document.createElement('ul');
            ulc.className = 'nav nav-treeview pl-3';
            it.children.forEach(ch => ulc.appendChild(buildNode(ch, level + 1)));
            li.appendChild(ulc);
         }

         // activo SOLO el item cuya 'vista' coincide con la ruta
         const path = location.pathname.replace(/^\//, '');
         const myVista = String(it.vista || '');
         if (myVista && (path === myVista || path.startsWith(myVista + '/'))) {
            li.querySelector(':scope > a.nav-link')?.classList.add('active');
         }
         return li;
      }

      // Abre ancestros de un <a.nav-link.active> ya pintado
      function openActiveAncestors(rootUl) {
         const activeLink = rootUl.querySelector('a.nav-link.active');
         if (!activeLink) return;
         let li = activeLink.closest('li.nav-item')?.parentElement?.closest('li.nav-item');
         while (li) {
            li.classList.add('menu-open');
            li.querySelector(':scope > ul.nav-treeview')?.style && (li.querySelector(':scope > ul.nav-treeview').style.display = 'block');
            li.querySelector(':scope > a.nav-link')?.classList.remove('active'); // padre no activo
            li.querySelector(':scope > a.nav-link')?.setAttribute('aria-expanded', 'true');
            li = li.parentElement?.closest('li.nav-item');
         }
      }

      // Restaura abiertos desde storage
      function restoreOpenFromStorage(rootUl) {
         const openSet = getOpen();
         openSet.forEach(id => {
            const li = rootUl.querySelector(`li.nav-item[data-mid="${CSS.escape(String(id))}"]`);
            if (li) {
               li.classList.add('menu-open');
               li.querySelector(':scope > ul.nav-treeview')?.style && (li.querySelector(':scope > ul.nav-treeview').style.display = 'block');
               li.querySelector(':scope > a.nav-link')?.setAttribute('aria-expanded', 'true');
            }
         });
      }

      // Toggle manual
      function attachManualToggle(rootUl) {
         const accordion = rootUl.getAttribute('data-accordion') !== 'false'; // default true
         rootUl.addEventListener('click', (e) => {
            const link = e.target.closest('a.nav-link');
            if (!link || !rootUl.contains(link)) return;

            const sub = link.nextElementSibling;
            if (sub && sub.classList.contains('nav-treeview')) {
               e.preventDefault();
               e.stopPropagation();

               const li = link.parentElement;
               const open = !li.classList.contains('menu-open');

               if (accordion) {
                  const sibs = li.parentElement?.querySelectorAll(':scope > li.menu-open') || [];
                  sibs.forEach(sib => {
                     if (sib !== li) {
                        sib.classList.remove('menu-open');
                        const s = sib.querySelector(':scope > ul.nav-treeview');
                        if (s) s.style.display = 'none';
                        sib.querySelector(':scope > a.nav-link')?.setAttribute('aria-expanded', 'false');
                        const idS = sib.dataset.mid;
                        if (idS) {
                           const st = getOpen();
                           st.delete(idS);
                           setOpen(st);
                        }
                     }
                  });
               }

               li.classList.toggle('menu-open', open);
               sub.style.display = open ? 'block' : 'none';
               link.setAttribute('aria-expanded', open ? 'true' : 'false');

               const id = li.dataset.mid;
               if (id) {
                  const st = getOpen();
                  if (open) st.add(id);
                  else st.delete(id);
                  setOpen(st);
               }
            }
         });
      }

      async function render() {
         ul.innerHTML = '';
         ul.setAttribute('data-built', BUILD_TAG);

         const res = await fetch('/api/v1/menu/tree?t=' + Date.now(), {
            headers: {
               'Accept': 'application/json'
            },
            cache: 'no-store'
         });
         const j = await res.json();
         if (!j?.ok || !Array.isArray(j.data)) {
            console.warn('[sidebar] resp inesperada', j);
            return;
         }

         // 1) Detecta el activo y sus ancestros en el JSON ANTES de construir
         const path = location.pathname.replace(/^\//, '');
         const {
            activeId,
            ancestors
         } = findActive(j.data, path);

         // 2) Une los ancestros activos al set persistido (garantiza que SIEMPRE queden abiertos tras navegar)
         if (ancestors.size) {
            const st = getOpen();
            ancestors.forEach(id => st.add(String(id)));
            setOpen(st);
         }

         // 3) Construye DOM
         const frag = document.createDocumentFragment();
         j.data.forEach(it => frag.appendChild(buildNode(it, 0)));
         ul.appendChild(frag);

         // 4) Restaura abiertos desde storage y abre ancestros del activo
         restoreOpenFromStorage(ul);
         openActiveAncestors(ul);

         // 5) Activa toggle manual
         attachManualToggle(ul);

         console.log('[sidebar]', BUILD_TAG, 'ok. items:', ul.querySelectorAll(':scope > li').length, 'activeId:', activeId, 'ancestors:', [...ancestors]);
      }

      render();
      window.__SIDEBAR_REBUILD__ = render;
   })();
</script>