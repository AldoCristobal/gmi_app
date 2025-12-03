// public/assets/js/tareas.evaluacion.js
(function () {
   const gridDiv = document.getElementById('tareas-eval-grid');
   if (!gridDiv) return;

   // -----------------------------------------------------
   // Helpers de notificación y headers
   // -----------------------------------------------------
   function notifySuccess(msg) {
      if (window.notyf) {
         window.notyf.success(msg);
      } else {
         alert(msg);
      }
   }

   function notifyError(msg) {
      if (window.notyf) {
         window.notyf.error(msg);
      } else {
         alert(msg);
      }
   }

   function authHeaders(extra) {
      extra = extra || {};
      const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
      const base = {
         'X-Requested-With': 'XMLHttpRequest',
         'X-CSRF-Token': token
      };
      return Object.assign(base, extra);
   }

   // -----------------------------------------------------
   // AG Grid
   // -----------------------------------------------------
   const columnDefs = [
      { headerName: '#', valueGetter: 'node.rowIndex + 1', width: 60 },
      { headerName: 'Empresa', field: 'empresa_nombre', flex: 1.4, minWidth: 160 },
      { headerName: 'Título', field: 'titulo', flex: 1.6, minWidth: 180 },
      { headerName: 'Responsable', field: 'responsable_nombre', flex: 1.2, minWidth: 140 },
      {
         headerName: 'Tipo',
         field: 'tipo_tarea',
         width: 120,
         valueFormatter: p => p.value === 'EXTRAORDINARIA' ? 'Extraordinaria' : 'Obligación'
      },
      {
         headerName: 'Estado',
         field: 'estado',
         width: 150,
         cellRenderer: estadoCellRenderer
      },
      { headerName: 'Vencimiento', field: 'fecha_vencimiento', width: 130 },
      { headerName: '%', field: 'progreso', width: 80 },
      {
         headerName: '',
         width: 80,
         cellRenderer: function () {
            return '<button type="button" class="btn btn-xs btn-outline-info btn-evaluar" title="Evaluar"><i class="fas fa-clipboard-check"></i></button>';
         }
      }
   ];

   const gridOptions = {
      columnDefs,
      rowData: [],
      rowSelection: 'single',
      suppressCellFocus: true,
      animateRows: false,
      onGridReady: function () {
         loadFilters();
         loadGrid();
      },
      onRowClicked: function (event) {
         const target = event.event.target;
         if (!target) return;

         const isButton = target.closest && target.closest('.btn-evaluar');
         if (isButton) {
            openEvaluationModal(event.data);
         }
      }
   };

   new agGrid.Grid(gridDiv, gridOptions);

   // -----------------------------------------------------
   // Filtros
   // -----------------------------------------------------
   const inpQ = document.getElementById('te-q');
   const selEmpresa = document.getElementById('te-empresa');
   const selResponsable = document.getElementById('te-responsable');
   const selTipo = document.getElementById('te-tipo');
   const selEstado = document.getElementById('te-estado');
   const inpDesde = document.getElementById('te-desde');
   const inpHasta = document.getElementById('te-hasta');

   const btnBuscar = document.getElementById('te-search');
   const btnLimpiar = document.getElementById('te-clear');
   const btnRefresh = document.getElementById('te-refresh');

   btnBuscar && btnBuscar.addEventListener('click', function () {
      loadGrid();
   });

   btnRefresh && btnRefresh.addEventListener('click', function () {
      loadGrid();
   });

   btnLimpiar && btnLimpiar.addEventListener('click', function () {
      if (inpQ) inpQ.value = '';
      if (selEmpresa) selEmpresa.value = '';
      if (selResponsable) selResponsable.value = '';
      if (selTipo) selTipo.value = '';
      if (selEstado) selEstado.value = '';
      if (inpDesde) inpDesde.value = '';
      if (inpHasta) inpHasta.value = '';
      loadGrid();
   });

   function loadFilters() {
      // Empresas
      fetch('/api/v1/catalogos/empresas', {
         headers: authHeaders()
      })
         .then(r => r.json())
         .then(j => {
            if (!j.ok || !selEmpresa) return;
            (j.data || []).forEach(e => {
               const opt = document.createElement('option');
               opt.value = e.id;
               opt.textContent = e.nombre;
               selEmpresa.appendChild(opt);
            });
         })
         .catch(console.error);

      // Responsables (jefes/usuarios) - ya respeta scope
      fetch('/api/v1/catalogos/jefes', {
         headers: authHeaders()
      })
         .then(r => r.json())
         .then(j => {
            if (!j.ok || !selResponsable) return;
            (j.data || []).forEach(u => {
               const opt = document.createElement('option');
               opt.value = u.id;
               opt.textContent = u.nombre;
               selResponsable.appendChild(opt);
            });
         })
         .catch(console.error);
   }

   function buildQuery() {
      const params = new URLSearchParams();

      if (inpQ && inpQ.value) params.append('q', inpQ.value);
      if (selEmpresa && selEmpresa.value) params.append('empresa_id', selEmpresa.value);
      if (selResponsable && selResponsable.value) params.append('responsable_id', selResponsable.value);
      if (selTipo && selTipo.value) params.append('tipo_tarea', selTipo.value);
      if (selEstado && selEstado.value) params.append('estado', selEstado.value);
      if (inpDesde && inpDesde.value) params.append('desde', inpDesde.value);
      if (inpHasta && inpHasta.value) params.append('hasta', inpHasta.value);

      return params.toString() ? '?' + params.toString() : '';
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


   function loadGrid() {
      const q = buildQuery();
      fetch('/api/v1/tareas/evaluacion' + q, {
         headers: authHeaders()
      })
         .then(r => r.json())
         .then(j => {
            if (!j.ok) {
               notifyError(j.error?.message || 'Error al cargar tareas');
               return;
            }
            gridOptions.api.setRowData(j.data || []);
         })
         .catch(err => {
            console.error(err);
            notifyError('Error de red al cargar tareas');
         });
   }

   // -----------------------------------------------------
   // Modal de evaluación
   // -----------------------------------------------------
   const modalEl = $('#tareas-eval-modal');
   const inputTareaId = document.getElementById('te-id');
   const spanEmpresa = document.getElementById('te-empresa-label');
   const spanTitulo = document.getElementById('te-titulo-label');
   const spanResponsable = document.getElementById('te-responsable-label');
   const spanPeriodo = document.getElementById('te-periodo-label');
   const spanVencimiento = document.getElementById('te-vencimiento-label');
   const spanEstadoActual = document.getElementById('te-estado-actual-label');
   const txtComentario = document.getElementById('te-comentario');
   const contHistorial = document.getElementById('te-historial');
   const btnGuardarEval = document.getElementById('te-save');

   // Evidencias (solo lectura)
   const evidTbody = document.getElementById('te-evid-tbody');
   const evidResumen = document.getElementById('te-evid-resumen');

   // Toolbar de estados (iconitos)
   const statusToolbar = document.getElementById('te-status-toolbar');
   let selectedStatus = '';

   function setToolbarStatus(status) {
      selectedStatus = status || '';
      if (!statusToolbar) return;

      const buttons = statusToolbar.querySelectorAll('button[data-status]');
      buttons.forEach(btn => {
         const s = btn.getAttribute('data-status') || '';
         btn.classList.toggle('active', s === selectedStatus);
      });
   }

   if (statusToolbar) {
      statusToolbar.addEventListener('click', function (e) {
         const btn = e.target.closest('button[data-status]');
         if (!btn) return;

         const status = btn.getAttribute('data-status') || '';
         setToolbarStatus(status);
      });
   }

   function formatDate(dateStr) {
      if (!dateStr) return '';
      const parts = String(dateStr).split('-');
      if (parts.length !== 3) return dateStr;
      return `${parts[2]}/${parts[1]}/${parts[0]}`;
   }

   function openEvaluationModal(row) {
      if (!row || !row.id) {
         notifyError('Fila inválida');
         return;
      }

      const url = '/api/v1/tareas/evaluacion/show?tarea_id=' + encodeURIComponent(row.id);

      fetch(url, { headers: authHeaders() })
         .then(r => r.json())
         .then(j => {
            if (!j.ok) {
               notifyError(j.error?.message || 'No se pudo obtener detalle');
               return;
            }

            const t = j.data.tarea;
            const h = j.data.evaluacion || [];

            if (inputTareaId) inputTareaId.value = t.id;
            if (spanEmpresa) spanEmpresa.textContent = t.empresa_nombre || '';
            if (spanTitulo) spanTitulo.textContent = t.titulo || '';
            if (spanResponsable) spanResponsable.textContent = t.responsable_nombre || '';
            if (spanPeriodo) spanPeriodo.textContent = (t.periodo_inicio || '') + ' al ' + (t.periodo_fin || '');
            if (spanVencimiento) spanVencimiento.textContent = t.fecha_vencimiento || '';
            if (spanEstadoActual) spanEstadoActual.textContent = t.estado || '';

            if (txtComentario) txtComentario.value = '';

            // Preseleccionar el estado actual en el toolbar
            setToolbarStatus(t.estado || '');

            // Historial
            if (contHistorial) {
               contHistorial.innerHTML = '';
               if (!h.length) {
                  contHistorial.innerHTML = '<p class="text-muted mb-0">Sin evaluaciones previas.</p>';
               } else {
                  h.forEach(ev => {
                     const div = document.createElement('div');
                     div.className = 'border-bottom py-1 small';
                     div.innerHTML = `
                        <div class="d-flex justify-content-between">
                           <strong>${ev.evaluador_nombre || 'N/D'}</strong>
                           <span class="text-muted">${ev.creado_en}</span>
                        </div>
                        <div>Estado: <span class="text-info">${ev.estado_anterior}</span> → <span class="text-success">${ev.estado_nuevo}</span></div>
                        ${ev.comentario ? `<div class="text-muted">${ev.comentario}</div>` : ''}
                     `;
                     contHistorial.appendChild(div);
                  });
               }
            }

            // Cargar evidencias de esta tarea
            if (t.id) {
               loadEvidencias(t.id);
            }

            if (window.__applyGates) {
               window.__applyGates();
            }

            // Tooltips para iconos
            if (window.$ && $.fn.tooltip) {
               $('[data-toggle="tooltip"]').tooltip();
            }

            modalEl.modal('show');
         })
         .catch(err => {
            console.error(err);
            notifyError('Error de red al obtener detalle');
         });
   }

   // -----------------------------------------------------
   // Evidencias (solo lectura) dentro del modal
   // -----------------------------------------------------
   function formatSize(bytes) {
      const b = Number(bytes || 0);
      if (!b || isNaN(b)) return '-';
      if (b < 1024) return b + ' B';
      const kb = b / 1024;
      if (kb < 1024) return kb.toFixed(1) + ' KB';
      const mb = kb / 1024;
      return mb.toFixed(1) + ' MB';
   }

   function formatDateTime(dt) {
      if (!dt) return '-';
      const [date, time] = String(dt).split(' ');
      if (!date) return dt;
      const [y, m, d] = date.split('-');
      if (!y || !m || !d) return dt;
      const hhmm = (time || '').substring(0, 5);
      return `${d}/${m}/${y}${hhmm ? ' ' + hhmm : ''}`;
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

   function renderEvidenciasTable(docs) {
      if (!evidTbody) return;

      if (!docs.length) {
         evidTbody.innerHTML = `
            <tr>
               <td colspan="5" class="text-center text-muted">
                  No hay evidencias cargadas.
               </td>
            </tr>
         `;
         if (evidResumen) evidResumen.textContent = '0 archivos';
         return;
      }

      let html = '';
      docs.forEach((d, idx) => {
         const id = d.id;
         const name = d.nombre_original || d.archivo_nombre || '(sin nombre)';
         const size = formatSize(d.size_bytes);
         const fecha = formatDateTime(d.creado_en);
         const url = '/api/v1/tareas/documentos/download?id=' + encodeURIComponent(id);

         html += `
            <tr>
               <td>${idx + 1}</td>
               <td>${escapeHtml(name)}</td>
               <td>${size}</td>
               <td>${fecha}</td>
               <td>
                  <a href="${url}"
                     class="btn btn-sm btn-outline-light"
                     title="Descargar"
                     data-perm="tareas.evidencias.descargar"
                     data-perm-mode="disable">
                     <i class="fas fa-download"></i>
                  </a>
               </td>
            </tr>
         `;
      });

      evidTbody.innerHTML = html;
      if (evidResumen) {
         evidResumen.textContent = `${docs.length} archivo(s)`;
      }

      if (window.__applyGates) {
         window.__applyGates();
      }
   }

   function loadEvidencias(tareaId) {
      if (!evidTbody) return;

      evidTbody.innerHTML = `
         <tr>
            <td colspan="5" class="text-center text-muted">
               Cargando evidencias...
            </td>
         </tr>
      `;

      fetch('/api/v1/tareas/documentos?tarea_id=' + encodeURIComponent(tareaId), {
         headers: authHeaders()
      })
         .then(r => r.json())
         .then(j => {
            if (!j.ok) {
               evidTbody.innerHTML = `
                  <tr>
                     <td colspan="5" class="text-center text-danger">
                        ${escapeHtml(j.error?.message || 'Error al cargar evidencias')}
                     </td>
                  </tr>
               `;
               if (evidResumen) evidResumen.textContent = '0 archivos';
               return;
            }
            const docs = Array.isArray(j.data) ? j.data : [];
            renderEvidenciasTable(docs);
         })
         .catch(err => {
            console.error(err);
            evidTbody.innerHTML = `
               <tr>
                  <td colspan="5" class="text-center text-danger">
                     Error de red al cargar evidencias
                  </td>
               </tr>
            `;
            if (evidResumen) evidResumen.textContent = '0 archivos';
         });
   }

   // -----------------------------------------------------
   // Guardar evaluación
   // -----------------------------------------------------
   btnGuardarEval && btnGuardarEval.addEventListener('click', function () {
      const tareaId = parseInt(inputTareaId?.value || '0', 10);
      const comentario = txtComentario?.value || '';
      const estadoNuevo = selectedStatus || '';

      if (!tareaId) {
         notifyError('Tarea inválida.');
         return;
      }

      if (!estadoNuevo) {
         notifyError('Selecciona un estado en la barra de botones.');
         return;
      }

      const formData = new FormData();
      formData.append('tarea_id', String(tareaId));
      formData.append('estado_nuevo', estadoNuevo);
      formData.append('comentario', comentario);

      fetch('/api/v1/tareas/evaluacion', {
         method: 'POST',
         headers: authHeaders(),
         body: formData
      })
         .then(r => r.json())
         .then(j => {
            if (!j.ok) {
               notifyError(j.error?.message || 'No se pudo guardar la evaluación');
               return;
            }
            notifySuccess('Evaluación guardada');
            modalEl.modal('hide');
            loadGrid();
         })
         .catch(err => {
            console.error(err);
            notifyError('Error de red al guardar evaluación');
         });
   });

   // -----------------------------------------------------
   // Init
   // -----------------------------------------------------
   document.addEventListener('DOMContentLoaded', function () {
      if (typeof window.__applyGates === 'function') {
         window.__applyGates();
      }
      // grid ya se inicializa en onGridReady
   });

})();
