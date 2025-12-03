// public/assets/js/empresas.expediente-panel.js
(function () {
   'use strict';

   const pageEl = document.getElementById('emp-expediente-page');
   if (!pageEl) return;

   // Elementos UI
   const inpQ = document.getElementById('ee-q');
   const selArea = document.getElementById('ee-area');
   const selResp = document.getElementById('ee-responsable');
   const chkOnlyDocs = document.getElementById('ee-only-docs');

   const btnSearch = document.getElementById('ee-search');
   const btnClear = document.getElementById('ee-clear');
   const btnRefresh = document.getElementById('ee-refresh');
   const cardsContainer = document.getElementById('ee-cards');
   const emptyEl = document.getElementById('ee-empty');
   const summaryEl = document.getElementById('ee-summary');
   const btnPrev = document.getElementById('ee-page-prev');
   const btnNext = document.getElementById('ee-page-next');

   // Modal
   const modalEl = $('#emp-expediente-modal');
   const detailDl = document.getElementById('ee-detail-dl');
   const docsTbody = document.getElementById('ee-docs-tbody');
   const docsCountEl = document.getElementById('ee-docs-count');

   let currentPage = 1;
   let pageSize = 12;
   let totalItems = 0;
   let currentEmpresaId = null;

   // Cache en memoria de la info de la empresa que viene en las tarjetas
   const empresaCache = {};

   const notify = window.notify || {
      success: (m) => alert(m),
      error: (m) => alert(m)
   };

   function authHeaders(extra) {
      extra = extra || {};
      const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
      return Object.assign({
         'X-Requested-With': 'XMLHttpRequest',
         'X-CSRF-Token': token
      }, extra);
   }

   // -------------------- Filtros --------------------

   function loadAreas() {
      if (!selArea) return;
      fetch('/api/v1/catalogos/areas', { headers: authHeaders() })
         .then(r => r.json())
         .then(j => {
            if (!j.ok) return;
            j.data.forEach(a => {
               const opt = document.createElement('option');
               opt.value = a.id;
               opt.textContent = a.nombre;
               selArea.appendChild(opt);
            });
         })
         .catch(console.error);
   }

   function loadResponsables() {
      if (!selResp) return;
      fetch('/api/v1/catalogos/jefes', { headers: authHeaders() })
         .then(r => r.json())
         .then(j => {
            if (!j.ok) return;
            j.data.forEach(u => {
               const opt = document.createElement('option');
               opt.value = u.id;
               opt.textContent = u.nombre;
               selResp.appendChild(opt);
            });
         })
         .catch(console.error);
   }

   function buildQuery() {
      const params = new URLSearchParams();
      params.set('page', String(currentPage));
      params.set('size', String(pageSize));

      if (inpQ && inpQ.value.trim()) {
         params.set('q', inpQ.value.trim());
      }
      if (selArea && selArea.value) {
         params.set('area_id', selArea.value);
      }
      if (selResp && selResp.value) {
         params.set('responsable_id', selResp.value);
      }
      if (chkOnlyDocs && chkOnlyDocs.checked) {
         params.set('solo_con_docs', '1');
      }

      return '?' + params.toString();
   }

   // -------------------- Carga de tarjetas --------------------

   function formatDateTime(dt) {
      if (!dt) return '-';
      const s = String(dt);
      const parts = s.split(' ');
      const date = parts[0] || '';
      const time = parts[1] || '';

      if (!date) return s;
      const [y, m, d] = date.split('-');
      if (!y || !m || !d) return s;

      const timeShort = time ? time.substring(0, 5) : '';
      return timeShort ? `${d}/${m}/${y} ${timeShort}` : `${d}/${m}/${y}`;
   }

   function renderCards(items) {
      if (!cardsContainer) return;

      cardsContainer.innerHTML = '';
      // limpiamos cache para que no crezca infinito
      Object.keys(empresaCache).forEach(k => delete empresaCache[k]);

      if (!items || !items.length) {
         if (emptyEl) emptyEl.style.display = '';
         return;
      }

      if (emptyEl) emptyEl.style.display = 'none';

      items.forEach(e => {
         const col = document.createElement('div');
         col.className = 'col-lg-4 col-md-6 mb-3';

         const docsCount = parseInt(e.docs_count, 10) || 0;
         const hasDocs = docsCount > 0;
         const lastDoc = e.last_doc_at ? formatDateTime(e.last_doc_at) : 'Sin registros';

         const tipoLabel = e.tipo_persona === 'FISICA'
            ? 'Física'
            : (e.tipo_persona === 'MORAL' ? 'Moral' : (e.tipo_persona || '-'));

         col.innerHTML = `
            <div class="card h-100 border-secondary">
               <div class="card-header py-2 d-flex align-items-center">
                  <div class="mr-2">
                     <i class="fas fa-building"></i>
                  </div>
                  <div class="flex-fill">
                     <div class="font-weight-bold text-truncate" title="${escapeHtml(e.nombre || '')}">
                        ${escapeHtml(e.nombre || '')}
                     </div>
                     <div class="small text-muted">
                        RFC: ${escapeHtml(e.rfc || '-')}
                     </div>
                  </div>
               </div>
               <div class="card-body py-2 small">
                  <div class="mb-1">
                     <span class="badge badge-info mr-1">${tipoLabel}</span>
                     ${e.cliente_grupo ? `<span class="badge badge-secondary">${escapeHtml(e.cliente_grupo)}</span>` : ''}
                  </div>
                  <div class="mb-1">
                     <strong>Área:</strong> ${escapeHtml(e.area_nombre || '-')}
                  </div>
                  <div class="mb-1">
                     <strong>Responsable:</strong> ${escapeHtml(e.responsable_nombre || '-')}
                  </div>
                  <div class="mb-1">
                     <strong>Documentos:</strong>
                     ${hasDocs ? `${docsCount}` : 'Sin documentos'}
                  </div>
                  <div class="text-muted">
                     <strong>Último doc:</strong> ${lastDoc}
                  </div>
               </div>
               <div class="card-footer py-2 d-flex justify-content-between align-items-center">
                  <button type="button"
                          class="btn btn-sm btn-outline-primary ee-btn-ver"
                          data-id="${e.id}"
                          title="Ver expediente"
                          data-perm="empresa.expediente"
                          data-perm-mode="disable">
                     <i class="fas fa-folder-open"></i>
                  </button>
                  ${hasDocs ? `
                     <span class="badge badge-success">Con documentos</span>
                  ` : `
                     <span class="badge badge-light">Sin documentos</span>
                  `}
               </div>
            </div>
         `;

         cardsContainer.appendChild(col);

         // guardamos la info de la empresa para usarla en el modal
         if (e.id != null) {
            empresaCache[String(e.id)] = e;
         }
      });

      if (typeof window.__applyGates === 'function') {
         window.__applyGates();
      }
   }

   function escapeHtml(str) {
      if (str == null) return '';
      return String(str)
         .replace(/&/g, '&amp;')
         .replace(/</g, '&lt;')
         .replace(/>/g, '&gt;')
         .replace(/"/g, '&quot;')
         .replace(/'/g, '&#039;');
   }

   function updateSummary() {
      if (!summaryEl) return;

      if (!totalItems) {
         summaryEl.textContent = '0 registros';
         return;
      }

      const start = (currentPage - 1) * pageSize + 1;
      const end = Math.min(currentPage * pageSize, totalItems);
      summaryEl.textContent = `Mostrando ${start}–${end} de ${totalItems}`;
   }

   function updatePaginationButtons() {
      const totalPages = totalItems ? Math.ceil(totalItems / pageSize) : 1;
      if (btnPrev) btnPrev.disabled = currentPage <= 1;
      if (btnNext) btnNext.disabled = currentPage >= totalPages;
   }

   function loadPanel() {
      const q = buildQuery();
      const url = '/api/v1/empresas/expediente-panel' + q;

      if (cardsContainer) {
         cardsContainer.innerHTML = `
            <div class="col-12 text-center text-muted py-3">
               Cargando empresas...
            </div>
         `;
      }
      if (emptyEl) emptyEl.style.display = 'none';

      fetch(url, { headers: authHeaders() })
         .then(r => r.json())
         .then(j => {
            if (!j.ok) {
               console.error(j);
               notify.error(j.error?.message || 'Error al cargar expediente');
               return;
            }

            const items = Array.isArray(j.data) ? j.data : [];
            totalItems = j.meta?.total ?? items.length;

            renderCards(items);
            updateSummary();
            updatePaginationButtons();
         })
         .catch(err => {
            console.error(err);
            notify.error('Error de red al cargar expediente');
         });
   }

   // -------------------- Modal detalle --------------------

   function renderEmpresaDetail(empresa) {
      if (!detailDl) return;
      detailDl.innerHTML = '';

      const rows = [
         ['Nombre', empresa.nombre || ''],
         ['RFC', empresa.rfc || ''],
         ['Tipo persona', empresa.tipo_persona || ''],
         ['Grupo', empresa.cliente_grupo || ''],
         ['Área', empresa.area_nombre || (empresa.area_id || '')],
         ['Responsable', empresa.responsable_nombre || (empresa.responsable_id || '')],
         ['Contrato', empresa.contrato_servicios || ''],
         ['Actividad', empresa.actividad_principal || ''],
         ['Estatus domicilio', empresa.estatus_domicilio || ''],
      ];

      rows.forEach(([label, value]) => {
         const dt = document.createElement('dt');
         dt.className = 'col-sm-5';
         dt.textContent = label;

         const dd = document.createElement('dd');
         dd.className = 'col-sm-7';
         dd.textContent = value || '-';

         detailDl.appendChild(dt);
         detailDl.appendChild(dd);
      });
   }

   function formatSize(bytes) {
      if (!bytes || isNaN(bytes)) return '-';
      const b = Number(bytes);
      if (b < 1024) return b + ' B';
      const kb = b / 1024;
      if (kb < 1024) return kb.toFixed(1) + ' KB';
      const mb = kb / 1024;
      return mb.toFixed(1) + ' MB';
   }

   function renderDocsTable(empresaId, tipos, docs) {
      if (!docsTbody) return;

      docs = Array.isArray(docs) ? docs : [];
      tipos = Array.isArray(tipos) ? tipos : [];

      if (!docs.length) {
         docsTbody.innerHTML = `
            <tr>
               <td colspan="7" class="text-center text-muted">
                  Sin documentos en el expediente.
               </td>
            </tr>
         `;
         if (docsCountEl) docsCountEl.textContent = '0 documentos';
         return;
      }

      let html = '';
      docs.forEach((d, idx) => {
         const tipo = tipos.find(t => String(t.id) === String(d.tipo_id)) || null;
         const tipoNombre = tipo ? (tipo.nombre || tipo.clave || '') : (d.tipo_id || '');

         html += `
            <tr>
               <td>${idx + 1}</td>
               <td>${escapeHtml(tipoNombre)}</td>
               <td>${escapeHtml(d.archivo_nombre || '')}</td>
               <td class="text-center">${d.version || 1}</td>
               <td>${formatSize(d.size_bytes)}</td>
               <td>${formatDateTime(d.subido_en || d.creado_en)}</td>
               <td class="text-right">
                  <a href="/api/v1/empresas/expediente/download?empresa_id=${encodeURIComponent(empresaId)}&doc_id=${encodeURIComponent(d.id)}&stream=1"
                     class="btn btn-sm btn-outline-light"
                     title="Descargar archivo"
                     data-perm="empresa.expediente"
                     data-perm-mode="disable">
                     <i class="fas fa-download"></i>
                  </a>
               </td>
            </tr>
         `;
      });

      docsTbody.innerHTML = html;
      if (docsCountEl) docsCountEl.textContent = `${docs.length} documento(s)`;

      if (typeof window.__applyGates === 'function') {
         window.__applyGates();
      }
   }

   function openExpedienteModal(empresaId) {
      if (!empresaId) return;
      currentEmpresaId = empresaId;

      if (docsTbody) {
         docsTbody.innerHTML = `
         <tr>
            <td colspan="7" class="text-center text-muted">
               Cargando expediente...
            </td>
         </tr>
      `;
      }
      if (detailDl) detailDl.innerHTML = '';

      const url = '/api/v1/empresas/expediente-panel?empresa_id=' + encodeURIComponent(empresaId);

      fetch(url, { headers: authHeaders() })
         .then(r => r.json())
         .then(j => {
            if (!j.ok) {
               console.error(j);
               notify.error(j.error?.message || 'No se pudo obtener el expediente');
               return;
            }

            const data = j.data || {};
            const empresa = data.empresa || {};
            const tipos = data.tipos || [];
            const docs = data.documentos_ultima_version || [];

            renderEmpresaDetail(empresa);
            renderDocsTable(empresaId, tipos, docs);

            if (typeof window.__applyGates === 'function') {
               window.__applyGates();
            }

            modalEl.modal('show');
         })
         .catch(err => {
            console.error(err);
            notify.error('Error de red al obtener expediente');
         });
   }



   // -------------------- Eventos --------------------

   btnSearch && btnSearch.addEventListener('click', function () {
      currentPage = 1;
      loadPanel();
   });

   btnClear && btnClear.addEventListener('click', function () {
      if (inpQ) inpQ.value = '';
      if (selArea) selArea.value = '';
      if (selResp) selResp.value = '';
      if (chkOnlyDocs) chkOnlyDocs.checked = false;
      currentPage = 1;
      loadPanel();
   });

   btnRefresh && btnRefresh.addEventListener('click', function () {
      loadPanel();
   });

   btnPrev && btnPrev.addEventListener('click', function () {
      if (currentPage <= 1) return;
      currentPage--;
      loadPanel();
   });

   btnNext && btnNext.addEventListener('click', function () {
      const totalPages = totalItems ? Math.ceil(totalItems / pageSize) : 1;
      if (currentPage >= totalPages) return;
      currentPage++;
      loadPanel();
   });

   // Delegado: click en "Ver expediente"
   document.addEventListener('click', function (ev) {
      const btn = ev.target.closest('.ee-btn-ver');
      if (!btn) return;

      const id = parseInt(btn.getAttribute('data-id') || '0', 10);
      if (!id) return;

      openExpedienteModal(id);
   });

   // -------------------- Init --------------------
   document.addEventListener('DOMContentLoaded', function () {
      loadAreas();
      loadResponsables();
      loadPanel();
   });

})();
