/**
 * MIS TAREAS
 * - Lista tareas desde /api/v1/tareas
 * - Permite filtrar por estado y tipo
 * - Permite cambiar estado PENDIENTE <-> EN_REVISION
 * - Integra evidencias (listar / subir / borrar / descargar)
 * - Usa AG Grid (alpine-dark)
 * - Incluye fallback para authHeaders() si no existe
 */

// -----------------------------------------------------------
// 1. FALLBACK PARA authHeaders()
// -----------------------------------------------------------
if (typeof authHeaders !== 'function') {
   console.warn('[tareas.mis] authHeaders() no existe — usando fallback mínimo');

   function authHeaders(extra) {
      extra = extra || {};
      var base = {
         'X-CSRF-Token': localStorage.getItem('csrf_token') || ''
      };
      for (var k in extra) {
         if (Object.prototype.hasOwnProperty.call(extra, k)) {
            base[k] = extra[k];
         }
      }
      return base;
   }
}

// -----------------------------------------------------------
// 2. IIFE PRINCIPAL
// -----------------------------------------------------------
(function () {
   'use strict';

   const gridEl = document.getElementById('misTareasGrid');
   if (!gridEl) {
      console.warn('[tareas.mis] No se encontró #misTareasGrid, saliendo.');
      return;
   }

   const filtroBuscarEl = document.getElementById('t-q');
   const filtroEstadoEl = document.getElementById('t-estado');
   const filtroTipoEl = document.getElementById('t-tipo');

   const btnRefresh = document.getElementById('t-btn-refresh');
   const btnEnviarRev = document.getElementById('t-btn-enviar-revision');
   const btnReabrir = document.getElementById('t-btn-reabrir');
   const btnEvidencias = document.getElementById('t-btn-evidencias');

   const notify = window.notify || {
      success: (m) => alert(m),
      error: (m) => alert(m)
   };

   // Modal evidencias
   const evidModal = $('#tarea-evidencias-modal');
   const evidTituloEl = document.getElementById('tarea-evidencias-titulo');
   const evidInfoEl = document.getElementById('t-evid-info');
   const evidResumenEl = document.getElementById('t-evid-resumen');
   const evidForm = document.getElementById('t-evid-form');
   const evidFilesInput = document.getElementById('t-evid-files');
   const evidTareaIdInp = document.getElementById('t-evid-tarea-id');
   const evidTbody = document.getElementById('t-evid-tbody');

   // --------------------------------------------------------
   // 3. Columnas del AG Grid
   // --------------------------------------------------------
   const columnDefs = [
      {
         headerName: '#',
         valueGetter: 'node.rowIndex + 1',
         width: 60,
         suppressMenu: true,
         sortable: false,
         resizable: false,
         cellClass: 'text-muted'
      },
      {
         headerName: 'Tipo',
         field: 'tipo_tarea',
         width: 120,
         valueFormatter: p => p.value === 'EXTRAORDINARIA' ? 'Extraordinaria' : 'Obligación'
      },
      {
         headerName: 'Título',
         field: 'titulo',
         flex: 2,
         minWidth: 220
      },
      {
         headerName: 'Empresa',
         field: 'empresa_nombre',
         flex: 2,
         minWidth: 220
      },
      {
         headerName: 'Responsable',
         field: 'responsable_nombre',
         flex: 1.2,
         minWidth: 160
      },
      {
         headerName: 'Objetivo',
         field: 'fecha_objetivo',
         width: 110,
         valueFormatter: dateFormatter
      },
      {
         headerName: 'Vencimiento',
         field: 'fecha_vencimiento',
         width: 120,
         valueFormatter: dateFormatter,
         cellClass: params => {
            if (!params.value) return null;
            const hoy = new Date();
            const fv = new Date(params.value + 'T00:00:00');
            const diff = (fv - hoy) / (1000 * 60 * 60 * 24);
            if (diff < 0) return 'text-danger';
            if (diff <= 3) return 'text-warning';
            return null;
         }
      },
      {
         headerName: 'Estado',
         field: 'estado',
         width: 140,
         cellRenderer: estadoCellRenderer
      }
   ];

   const gridOptions = {
      columnDefs,
      rowData: [],
      rowSelection: 'single',
      animateRows: true,
      suppressCellFocus: true,
      getRowId: params => String(params.data.id),
      defaultColDef: {
         sortable: true,
         resizable: true,
         filter: false
      },
      onSelectionChanged: updateActionButtonsState
   };

   new agGrid.Grid(gridEl, gridOptions);

   if (typeof window.__applyGates === 'function') {
      window.__applyGates();
   }

   // --------------------------------------------------------
   // 4. Helpers
   // --------------------------------------------------------
   function dateFormatter(params) {
      const v = params.value;
      if (!v) return '';
      const [y, m, d] = v.split('-');
      if (!y || !m || !d) return v;
      return `${d}/${m}/${y}`;
   }

   function estadoCellRenderer(params) {
      const raw = (params.value || '').toString().toUpperCase();
      let label = raw;
      let cls = 'badge-estado';

      switch (raw) {
         case 'PENDIENTE':
            label = 'Pendiente';
            cls += ' badge-estado-pendiente';
            break;
         case 'EN_REVISION':
            label = 'En revisión';
            cls += ' badge-estado-en_revision';
            break;
         case 'COMPLETA':
            label = 'Completa';
            cls += ' badge-estado-completa';
            break;
         case 'CANCELADA':
            label = 'Cancelada';
            cls += ' badge-estado-cancelada';
            break;
         case 'BLOQUEADA':
            label = 'Bloqueada';
            cls += ' badge-estado-bloqueada';
            break;
      }

      return `
         <span class="${cls}">
            <span class="dot"></span>
            ${label}
         </span>
      `;
   }

   function getSelectedTarea() {
      const sel = gridOptions.api.getSelectedRows();
      return (sel && sel.length > 0) ? sel[0] : null;
   }

   function buildQueryString() {
      const params = new URLSearchParams();
      const estado = filtroEstadoEl ? filtroEstadoEl.value : '';
      const tipo = filtroTipoEl ? filtroTipoEl.value : '';

      if (estado) params.set('estado', estado);
      if (tipo) params.set('tipo_tarea', tipo);

      return params.toString();
   }

   function applyQuickFilter() {
      if (!filtroBuscarEl) return;
      const text = (filtroBuscarEl.value || '').trim();
      gridOptions.api.setQuickFilter(text);
   }

   function updateActionButtonsState() {
      const row = getSelectedTarea();
      const est = row ? (row.estado || '').toUpperCase() : '';
      const tieneEvid = row ? (parseInt(row.tiene_evidencia, 10) === 1) : false;

      if (btnEvidencias) {
         btnEvidencias.disabled = !row;
      }

      if (btnEnviarRev) {
         btnEnviarRev.disabled = true;
      }

      if (btnReabrir) {
         btnReabrir.disabled = true;
      }

      if (!row) return;

      // Enviar a revisión: solo si PENDIENTE y tiene evidencias
      if (est === 'PENDIENTE' && tieneEvid) {
         btnEnviarRev && (btnEnviarRev.disabled = false);
      }

      // Reabrir: solo si EN_REVISION
      if (est === 'EN_REVISION') {
         btnReabrir && (btnReabrir.disabled = false);
      }
   }

   // --------------------------------------------------------
   // 5. Cargar tareas
   // --------------------------------------------------------
   async function loadTareas() {
      try {
         const qs = buildQueryString();
         const url = qs ? `/api/v1/tareas?${qs}` : '/api/v1/tareas';

         const resp = await fetch(url, {
            method: 'GET',
            headers: authHeaders()
         });

         if (!resp.ok) {
            const txt = await resp.text();
            console.error('[tareas.mis] HTTP error', resp.status, txt);
            notify.error('Error al cargar tareas');
            return;
         }

         const json = await resp.json();

         if (!json.ok) {
            console.error('[tareas.mis] Backend error', json);
            notify.error(json.error?.message || 'No se pudieron cargar las tareas');
            return;
         }

         // Backend: data: { items: [...], total: n }
         const items = (json.data && Array.isArray(json.data.items)) ? json.data.items : [];

         gridOptions.api.setRowData(items);
         gridOptions.api.sizeColumnsToFit();
         updateActionButtonsState();
         applyQuickFilter();
      } catch (err) {
         console.error('[tareas.mis] Excepción al cargar tareas', err);
         notify.error('Error inesperado al cargar tareas');
      }
   }

   // --------------------------------------------------------
   // 6. Cambio de estado PENDIENTE <-> EN_REVISION
   // --------------------------------------------------------
   async function cambiarEstadoSeleccionado(nuevoEstado) {
      const row = getSelectedTarea();
      if (!row) {
         notify.error('Selecciona una tarea primero');
         return;
      }

      const id = row.id;
      const estadoActual = (row.estado || '').toUpperCase();
      const destino = nuevoEstado.toUpperCase();

      if (estadoActual === destino) {
         notify.error('La tarea ya está en ese estado');
         return;
      }

      // Regla extra en front: no enviar a revisión sin evidencia
      const tieneEvid = parseInt(row.tiene_evidencia, 10) === 1;
      if (destino === 'EN_REVISION' && !tieneEvid) {
         notify.error('Primero sube al menos un archivo de evidencia.');
         return;
      }

      if (destino === 'EN_REVISION' && estadoActual !== 'PENDIENTE') {
         notify.error('Solo puedes enviar a revisión tareas en estado pendiente');
         return;
      }

      if (destino === 'PENDIENTE' && estadoActual !== 'EN_REVISION') {
         notify.error('Solo puedes reabrir tareas en revisión');
         return;
      }

      if (!confirm('¿Confirmas el cambio de estado?')) {
         return;
      }

      try {
         const headers = authHeaders({ 'Content-Type': 'application/json' });

         const resp = await fetch('/api/v1/tareas/estado', {
            method: 'PATCH',
            headers,
            body: JSON.stringify({ id, estado: destino })
         });

         const json = await resp.json().catch(() => ({}));

         if (!resp.ok || !json.ok) {
            console.error('[tareas.mis] Error al cambiar estado', json);
            notify.error(json.error?.message || 'No se pudo cambiar el estado');
            return;
         }

         notify.success('Estado actualizado');
         await loadTareas();
      } catch (err) {
         console.error('[tareas.mis] Excepción al cambiar estado', err);
         notify.error('Error inesperado al cambiar el estado');
      }
   }

   // --------------------------------------------------------
   // 7. Evidencias: abrir modal
   // --------------------------------------------------------
   function renderTareaInfoEnModal(t) {
      if (!evidInfoEl) return;

      const objetivo = t.fecha_objetivo ? dateFormatter({ value: t.fecha_objetivo }) : '-';
      const venc = t.fecha_vencimiento ? dateFormatter({ value: t.fecha_vencimiento }) : '-';

      evidInfoEl.innerHTML = `
         <dt class="col-sm-4">Título</dt>
         <dd class="col-sm-8">${escapeHtml(t.titulo || '')}</dd>

         <dt class="col-sm-4">Empresa</dt>
         <dd class="col-sm-8">${escapeHtml(t.empresa_nombre || '')}</dd>

         <dt class="col-sm-4">Tipo</dt>
         <dd class="col-sm-8">${t.tipo_tarea === 'EXTRAORDINARIA' ? 'Extraordinaria' : 'Obligación'}</dd>

         <dt class="col-sm-4">Objetivo</dt>
         <dd class="col-sm-8">${objetivo}</dd>

         <dt class="col-sm-4">Vencimiento</dt>
         <dd class="col-sm-8">${venc}</dd>

         <dt class="col-sm-4">Estado</dt>
         <dd class="col-sm-8">${escapeHtml(t.estado || '')}</dd>
      `;
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

   async function openEvidenciasModal() {
      const row = getSelectedTarea();
      if (!row) {
         notify.error('Selecciona una tarea primero');
         return;
      }

      if (evidTareaIdInp) evidTareaIdInp.value = row.id;
      if (evidTituloEl) evidTituloEl.textContent = `Evidencias: ${row.titulo || ''}`;

      // Reset file input label
      if (evidFilesInput) {
         evidFilesInput.value = '';
         const label = evidFilesInput.nextElementSibling;
         if (label && label.classList.contains('custom-file-label')) {
            label.textContent = 'Seleccionar archivos…';
         }
      }

      renderTareaInfoEnModal(row);
      await loadEvidencias(row.id);

      if (typeof window.__applyGates === 'function') {
         window.__applyGates();
      }

      evidModal && evidModal.modal('show');
   }

   // --------------------------------------------------------
   // 8. Evidencias: cargar lista
   // --------------------------------------------------------
   async function loadEvidencias(tareaId) {
      if (!evidTbody) return;

      evidTbody.innerHTML = `
         <tr>
            <td colspan="6" class="text-center text-muted">
               Cargando evidencias...
            </td>
         </tr>
      `;

      try {
         const url = `/api/v1/tareas/documentos?tarea_id=${encodeURIComponent(tareaId)}`;
         const resp = await fetch(url, {
            headers: authHeaders()
         });

         if (!resp.ok) {
            evidTbody.innerHTML = `
               <tr>
                  <td colspan="6" class="text-center text-danger">
                     Error al cargar evidencias
                  </td>
               </tr>
            `;
            return;
         }

         const json = await resp.json();

         if (!json.ok) {
            evidTbody.innerHTML = `
               <tr>
                  <td colspan="6" class="text-center text-danger">
                     ${escapeHtml(json.error?.message || 'No se pudieron cargar las evidencias')}
                  </td>
               </tr>
            `;
            return;
         }

         const docs = Array.isArray(json.data) ? json.data : [];
         renderEvidenciasTable(docs);
      } catch (err) {
         console.error('[tareas.mis] Error al cargar evidencias', err);
         evidTbody.innerHTML = `
            <tr>
               <td colspan="6" class="text-center text-danger">
                  Error inesperado al cargar evidencias
               </td>
            </tr>
         `;
      }
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

   function formatDateTime(dt) {
      if (!dt) return '-';
      // Asumimos 'YYYY-MM-DD HH:MM:SS'
      const [date, time] = String(dt).split(' ');
      if (!date) return dt;
      const [y, m, d] = date.split('-');
      if (!y || !m || !d) return dt;
      if (!time) return `${d}/${m}/${y}`;
      const hm = time.substring(0, 5);
      return `${d}/${m}/${y} ${hm}`;
   }

   function renderEvidenciasTable(docs) {
      if (!evidTbody) return;

      if (!docs.length) {
         evidTbody.innerHTML = `
            <tr>
               <td colspan="6" class="text-center text-muted">
                  No hay evidencias cargadas.
               </td>
            </tr>
         `;
         if (evidResumenEl) {
            evidResumenEl.textContent = '0 archivos';
         }
         return;
      }

      let html = '';
      docs.forEach((d, idx) => {
         const id = d.id;
         const name = d.nombre_original || '(sin nombre)';
         const size = formatSize(d.size_bytes);
         const fecha = formatDateTime(d.creado_en);
         const user = d.subido_por_nombre || '-';

         html += `
            <tr>
               <td>${idx + 1}</td>
               <td>${escapeHtml(name)}</td>
               <td>${size}</td>
               <td>${fecha}</td>
               <td>${escapeHtml(user)}</td>
               <td>
                  <div class="btn-group btn-group-sm" role="group">
                     <a href="/api/v1/tareas/documentos/download?id=${encodeURIComponent(id)}"
                        class="btn btn-outline-light"
                        title="Descargar"
                        data-perm="tareas.evidencias.descargar"
                        data-perm-mode="disable">
                        <i class="fas fa-download"></i>
                     </a>
                     <button type="button"
                             class="btn btn-outline-danger t-evid-del-btn"
                             data-id="${id}"
                             title="Eliminar"
                             data-perm="tareas.evidencias.borrar"
                             data-perm-mode="disable">
                        <i class="fas fa-trash-alt"></i>
                     </button>
                  </div>
               </td>
            </tr>
         `;
      });

      evidTbody.innerHTML = html;
      if (evidResumenEl) {
         evidResumenEl.textContent = `${docs.length} archivo(s)`;
      }

      if (typeof window.__applyGates === 'function') {
         window.__applyGates();
      }
   }

   // --------------------------------------------------------
   // 9. Evidencias: subir archivos
   // --------------------------------------------------------
   if (evidFilesInput) {
      evidFilesInput.addEventListener('change', function () {
         const files = evidFilesInput.files;
         const label = evidFilesInput.nextElementSibling;
         if (!label || !label.classList.contains('custom-file-label')) return;

         if (!files || files.length === 0) {
            label.textContent = 'Seleccionar archivos…';
         } else if (files.length === 1) {
            label.textContent = files[0].name;
         } else {
            label.textContent = files.length + ' archivos seleccionados';
         }
      });
   }

   if (evidForm) {
      evidForm.addEventListener('submit', async function (ev) {
         ev.preventDefault();
         const tareaId = evidTareaIdInp ? parseInt(evidTareaIdInp.value, 10) : 0;
         if (!tareaId) {
            notify.error('No se encontró la tarea asociada.');
            return;
         }

         if (!evidFilesInput || !evidFilesInput.files || evidFilesInput.files.length === 0) {
            notify.error('Selecciona al menos un archivo.');
            return;
         }

         const fd = new FormData();
         fd.append('tarea_id', String(tareaId));
         const files = evidFilesInput.files;
         for (let i = 0; i < files.length; i++) {
            fd.append('files[]', files[i], files[i].name);
         }

         try {
            const headers = authHeaders();
            // NO establecer Content-Type para que el browser ponga el boundary

            const resp = await fetch('/api/v1/tareas/documentos', {
               method: 'POST',
               headers,
               body: fd
            });

            const json = await resp.json().catch(() => ({}));

            if (!resp.ok || !json.ok) {
               console.error('[tareas.mis] Error al subir evidencias', json);
               notify.error(json.error?.message || 'No se pudieron subir las evidencias');
               return;
            }

            notify.success('Evidencias subidas correctamente');

            // limpiar input
            evidFilesInput.value = '';
            const label = evidFilesInput.nextElementSibling;
            if (label && label.classList.contains('custom-file-label')) {
               label.textContent = 'Seleccionar archivos…';
            }

            // recargar evidencias y tareas (para actualizar tiene_evidencia)
            await loadEvidencias(tareaId);
            await loadTareas();
         } catch (err) {
            console.error('[tareas.mis] Excepción al subir evidencias', err);
            notify.error('Error inesperado al subir evidencias');
         }
      });
   }

   // --------------------------------------------------------
   // 10. Evidencias: eliminar
   // --------------------------------------------------------
   document.addEventListener('click', async function (ev) {
      const btn = ev.target.closest('.t-evid-del-btn');
      if (!btn) return;

      const id = parseInt(btn.getAttribute('data-id') || '0', 10);
      if (!id) return;

      if (!confirm('¿Eliminar esta evidencia? Esta acción no se puede deshacer.')) {
         return;
      }

      try {
         const headers = authHeaders();
         const url = '/api/v1/tareas/documentos?id=' + encodeURIComponent(id);

         const resp = await fetch(url, {
            method: 'DELETE',
            headers
         });

         const json = await resp.json().catch(() => ({}));

         if (!resp.ok || !json.ok) {
            console.error('[tareas.mis] Error al eliminar evidencia', json);
            notify.error(json.error?.message || 'No se pudo eliminar la evidencia');
            return;
         }

         notify.success('Evidencia eliminada');

         const tareaId = evidTareaIdInp ? parseInt(evidTareaIdInp.value, 10) : 0;
         if (tareaId) {
            await loadEvidencias(tareaId);
         }
         await loadTareas();
      } catch (err) {
         console.error('[tareas.mis] Excepción al eliminar evidencia', err);
         notify.error('Error inesperado al eliminar la evidencia');
      }
   });

   // --------------------------------------------------------
   // 11. Eventos de UI generales
   // --------------------------------------------------------
   btnRefresh && btnRefresh.addEventListener('click', () => loadTareas());
   filtroEstadoEl && filtroEstadoEl.addEventListener('change', () => loadTareas());
   filtroTipoEl && filtroTipoEl.addEventListener('change', () => loadTareas());
   filtroBuscarEl && filtroBuscarEl.addEventListener('input', () => applyQuickFilter());

   btnEnviarRev && btnEnviarRev.addEventListener('click', () => {
      cambiarEstadoSeleccionado('EN_REVISION');
   });

   btnReabrir && btnReabrir.addEventListener('click', () => {
      cambiarEstadoSeleccionado('PENDIENTE');
   });

   btnEvidencias && btnEvidencias.addEventListener('click', () => {
      openEvidenciasModal();
   });

   // --------------------------------------------------------
   // 12. Init
   // --------------------------------------------------------
   document.addEventListener('DOMContentLoaded', () => {
      loadTareas();
   });

})();
