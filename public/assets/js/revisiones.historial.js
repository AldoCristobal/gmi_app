// public/assets/js/revisiones.historial.js
(function () {
   // ========= CSRF & HEADERS =========
   function readCookie(name) {
      var cookies = document.cookie ? document.cookie.split('; ') : [];
      for (var i = 0; i < cookies.length; i++) {
         var c = cookies[i];
         var idx = c.indexOf('=');
         var key = idx > -1 ? c.substring(0, idx) : c;
         if (key === name) {
            var val = idx > -1 ? c.substring(idx + 1) : '';
            try { return decodeURIComponent(val); } catch (e) { return val; }
         }
      }
      return '';
   }
   function getMetaCsrf() {
      var meta = document.querySelector('meta[name="csrf-token"]');
      return (meta && meta.content) ? meta.content : '';
   }
   function getCsrf() {
      return new Promise(function (resolve) {
         var meta = getMetaCsrf();
         if (meta) return resolve({ token: meta, source: 'meta' });
         var ls = localStorage.getItem('csrf_token');
         if (ls) return resolve({ token: ls, source: 'localStorage' });
         resolve({ token: '', source: 'none' });
      });
   }
   let csrfObj = { token: '' };
   function authHeaders({ json = false } = {}) {
      const h = {};
      if (csrfObj?.token) h['X-CSRF-Token'] = csrfObj.token;
      if (json) h['Content-Type'] = 'application/json';
      return h;
   }

   // ========= Inicialización =========
   let gridOptions = null;
   document.addEventListener('DOMContentLoaded', async () => {
      csrfObj = await getCsrf();
      initGrid();
      bindUI();
      await loadData();
      if (window.__applyGates) window.__applyGates(document);
   });

   // ========= UI mínima =========
   function bindUI() {
      const btn = document.getElementById('h-btn-search');
      const fq = document.getElementById('h-q');
      if (btn) btn.addEventListener('click', loadData);
      if (fq) fq.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); loadData(); } });
   }

   // ========= Grid (AG Grid) =========
   function initGrid() {
      const gridDiv = document.getElementById('historialGrid');
      const colDefs = [
         { headerName: 'Nombre', field: 'nombre', flex: 2 },
         { headerName: 'Área', field: 'area_nombre', flex: 1 },
         {
            headerName: 'Estatus',
            field: 'estatus',
            width: 120,
            cellRenderer: (p) => {
               const v = String(p.value || '').toLowerCase();
               if (v === 'completa') {
                  return `<span class="status-chip status-completa" title="Completa">
                      <i class="fas fa-check-circle"></i>
                    </span>`;
               }
               return `<span class="status-chip status-proceso" title="En proceso">
                    <i class="fas fa-hourglass-half"></i>
                  </span>`;
            },
            cellClass: 'text-center'
         },
         {
            headerName: 'Acción',
            width: 100,
            suppressMenu: true,
            cellRenderer: () => `
          <button class="btn btn-outline-info btn-xs btn-detalle" title="Ver detalle">
            <i class="fas fa-eye"></i>
          </button>`
         }
      ];

      gridOptions = {
         columnDefs: colDefs,
         rowData: [],
         rowSelection: 'single',
         suppressRowClickSelection: true,
         pagination: true,
         paginationPageSize: 25,
         onCellClicked: (e) => {
            if (e?.event?.target?.closest('.btn-detalle')) {
               const row = e.data;
               if (row) openDetalle(row.id, row.nombre);
            }
         },
         // 👉 Solo pintar si está EN PROCESO. Si es COMPLETA (u otro estatus), no pintamos.
         rowClassRules: {
            'row-overdue': p => {
               const est = String(p.data?.estatus || '').toLowerCase();
               const d = (p.data?.dias_restantes === null || p.data?.dias_restantes === undefined)
                  ? null : Number(p.data.dias_restantes);
               return est === 'en_proceso' && d !== null && d <= 0;
            },
            'row-due-soon': p => {
               const est = String(p.data?.estatus || '').toLowerCase();
               const d = (p.data?.dias_restantes === null || p.data?.dias_restantes === undefined)
                  ? null : Number(p.data.dias_restantes);
               return est === 'en_proceso' && d !== null && d > 0 && d <= 5;
            }
         }
      };
      new agGrid.Grid(gridDiv, gridOptions);
   }

   async function loadData() {
      try {
         const fq = document.getElementById('h-q');
         const url = new URL('/api/v1/revisiones', location.origin);
         if (fq?.value.trim()) url.searchParams.set('q', fq.value.trim());
         const res = await fetch(url, { headers: authHeaders(), credentials: 'same-origin' });
         const j = await res.json();
         if (!j.ok) throw new Error(j.error?.message || 'Error al cargar historial');
         gridOptions.api.setRowData(j.data || []);
      } catch (e) {
         console.error(e);
         window.notify('No se pudo cargar el historial', 'error');
      }
   }

   // ========= Modal de detalle (diseño 2 columnas) =========
   async function openDetalle(id, nombre) {
      try {
         const url = new URL('/api/v1/revisiones/show', location.origin);
         url.searchParams.set('id', id);
         const res = await fetch(url, { headers: authHeaders(), credentials: 'same-origin' });
         const j = await res.json();
         if (!j.ok) throw new Error(j.error?.message || 'No se pudo obtener el detalle');
         renderDetalleModal(j.data.revision, j.data.documentos);
         document.getElementById('historial-detalle-titulo').textContent = `Detalle: ${nombre || ('#' + id)}`;
         new bootstrap.Modal(document.getElementById('historial-detalle-modal')).show();
      } catch (e) {
         console.error(e);
         window.notify(e.message || 'Error al abrir el detalle', 'error');
      }
   }

   function renderDetalleModal(rev, docs) {
      const body = document.getElementById('historial-detalle-body');
      const inicial = docs?.inicial || null;
      const anexos = Array.isArray(docs?.anexos) ? docs.anexos : [];

      body.innerHTML = `
         <div class="row small no-gutters modal-detail-grid">
           <!-- Columna Izquierda: Información -->
           <div class="col-md-7 pr-md-3 border-right border-secondary modal-left-col">
             <div class="section-title">Información general</div>
             <dl class="row dl-tight mb-0">
               ${dl('Nombre', rev.nombre)}
               ${dl('Tipo de revisión', rev.tipo_revision)}
               ${dl('Dependencia', rev.dependencia)}
               ${dl('Área', rev.area_nombre || rev.area_id)}
               ${dl('Responsable', rev.responsable_nombre || rev.responsable_id)}
               ${dl('Fecha notificación', fmtDate(rev.fecha_notificacion))}
               ${dl('Fecha vencimiento', fmtDate(rev.fecha_vencimiento))}
               ${dl('Días restantes', rev.dias_restantes)}
               ${dl('Estatus', statusBadge(rev.estatus), true)}
               ${dl('Riesgo', capitalize(rev.riesgo))}
               ${dl('Número de Orden', rev.numero_orden)}
               ${dl('Número de Oficio', rev.numero_oficio)}
               ${dl('Ejercicio', rev.ejercicio)}
               ${dlBlock('Observaciones', rev.observaciones)}
             </dl>
           </div>

           <!-- Columna Derecha: Documentos -->
           <div class="col-md-5 pl-md-3 modal-right-col">
             <div class="section-title">Documentos</div>

             <div class="card bg-dark border-secondary mb-2 shadow-none">
               <div class="card-header py-2 px-3 card-head-compact">
                 <strong class="mr-1">Evidencia inicial</strong>
                 ${inicial ? `<span class="badge badge-primary">v${inicial.version || 1}</span>` : ''}
               </div>
               <div class="card-body py-2 px-2">
                 ${inicial ? docRowCompact(inicial, rev.id) : '<em class="text-muted">Sin evidencia inicial</em>'}
               </div>
             </div>

             <div class="card bg-dark border-secondary shadow-none">
               <div class="card-header py-2 px-3 card-head-compact">
                 <strong class="mr-1">Anexos</strong>
                 ${anexos.length ? `<span class="badge badge-secondary">${anexos.length}</span>` : ''}
               </div>
               <div class="card-body py-2 px-2">
                 ${anexos.length ? anexos.map(d => docRowCompact(d, rev.id)).join('') : '<em class="text-muted">Sin anexos</em>'}
               </div>
             </div>
           </div>
         </div>
      `;

      if (window.__applyGates) window.__applyGates(body);
   }

   // ========= Helpers =========
   function dl(label, value, isHtml = false) {
      let v;
      if (value == null || value === '') v = '<em class="text-muted">—</em>';
      else v = isHtml ? value : escapeHtml(value);

      return `
    <dt class="col-5 col-lg-4 text-muted dt-tight">${escapeHtml(label)}</dt>
    <dd class="col-7 col-lg-8 dd-tight">${v}</dd>
  `;
   }

   function dlBlock(label, value) {
      const v = (value == null || String(value).trim() === '')
         ? '<em class="text-muted">—</em>'
         : `<div class="text-wrap-preline">${escapeHtml(String(value))}</div>`;
      return `
    <dt class="col-12 text-muted dt-tight">${escapeHtml(label)}</dt>
    <dd class="col-12 dd-tight">${v}</dd>
  `;
   }

   function fmtDate(s) { return s ? String(s).substring(0, 10) : '—'; }
   function statusBadge(st) {
      const v = String(st || '').toLowerCase();
      if (v === 'completa')
         return `<span class="status-chip status-completa"><i class="fas fa-check-circle"></i> Completa</span>`;
      return `<span class="status-chip status-proceso"><i class="fas fa-hourglass-half"></i> En proceso</span>`;
   }
   function capitalize(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1) : s; }
   function escapeHtml(v) {
      return String(v).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
   }
   function prettySize(bytes) {
      const n = Number(bytes || 0);
      if (n >= 1024 * 1024) return (n / (1024 * 1024)).toFixed(1) + ' MB';
      if (n >= 1024) return (n / 1024).toFixed(1) + ' KB';
      return n + ' B';
   }
   function downloadUrl(revisionId, docId) {
      const u = new URL('/api/v1/revisiones/documentos/download', location.origin);
      u.searchParams.set('revision_id', revisionId);
      u.searchParams.set('doc_id', docId);
      return u.toString();
   }
   function docRowCompact(d, revisionId) {
      const name = escapeHtml(d.nombre_original || d.archivo_nombre || ('doc_' + d.id));
      const info = `${prettySize(d.size_bytes)} · ${fmtDate(d.creado_en || d.subido_en)} · ${escapeHtml(d.uploaded_by_nombre || d.subido_por_nombre || (d.uploaded_by || d.subido_por || '—'))}`;
      const url = downloadUrl(revisionId, d.id);
      const icon = fileIcon(d.nombre_original || d.archivo_nombre || '');

      return `
    <div class="doc-row d-flex align-items-center justify-content-between">
      <div class="d-flex align-items-center text-truncate">
        <span class="doc-icon">${icon}</span>
        <div class="text-truncate">
          <div class="doc-name text-truncate" title="${name}">${name}</div>
          <div class="doc-meta text-muted">${info}</div>
        </div>
      </div>
      <div class="ml-2 flex-shrink-0">
        <a href="${url}" target="_blank"
           class="btn btn-xs btn-outline-primary"
           data-perm="revisiones.descargar_archivo" data-perm-mode="hide"
           title="Descargar"><i class="fas fa-download"></i></a>
      </div>
    </div>
  `;
   }
   function fileIcon(filename) {
      const ext = String(filename || '').split('.').pop().toLowerCase();
      if (['pdf'].includes(ext)) return '<i class="far fa-file-pdf"></i>';
      if (['xls', 'xlsx', 'csv'].includes(ext)) return '<i class="far fa-file-excel"></i>';
      if (['doc', 'docx'].includes(ext)) return '<i class="far fa-file-word"></i>';
      if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'].includes(ext)) return '<i class="far fa-file-image"></i>';
      if (['zip', 'rar', '7z'].includes(ext)) return '<i class="far fa-file-archive"></i>';
      if (['txt', 'md', 'log'].includes(ext)) return '<i class="far fa-file-alt"></i>';
      return '<i class="far fa-file"></i>';
   }

})();
