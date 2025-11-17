// public/assets/js/empresas.rutinas.js

(function () {
   const $empresaSelect = document.getElementById('rt-empresa');
   const $emptyState = document.getElementById('rt-empty-state');
   const $content = document.getElementById('rt-content');
   const $treeContainer = $('#rt-obligaciones-tree');
   const $form = document.getElementById('rt-form');
   const $noObligacion = document.getElementById('rt-no-obligacion');

   const $empresaIdInput = document.getElementById('rt-empresa-id');
   const $obligacionIdInp = document.getElementById('rt-obligacion-id');
   const $obligDesc = document.getElementById('rt-obligacion-desc');
   const $obligClave = document.getElementById('rt-obligacion-clave');
   const $periodicidad = document.getElementById('rt-periodicidad');
   const $diaVenc = document.getElementById('rt-dia-venc');
   const $diasAnt = document.getElementById('rt-dias-anticipacion');
   const $offsetDias = document.getElementById('rt-offset-dias');
   const $enviarCorreo = document.getElementById('rt-enviar-correo');
   const $responsableSel = document.getElementById('rt-responsable-id');
   const $fechaInicio = document.getElementById('rt-fecha-inicio');
   const $fechaFin = document.getElementById('rt-fecha-fin');
   const $notas = document.getElementById('rt-notas');
   const $btnGuardar = document.getElementById('rt-btn-guardar');
   const $formTitle = document.getElementById('rt-form-title');

   const notify = window.notify || new Notyf();

   let currentEmpresa = null;
   let currentObligacion = null;
   let responsablesCache = [];

   function authHeaders() {
      const tokenMeta = document.querySelector('meta[name="csrf-token"]');
      const token = tokenMeta ? tokenMeta.getAttribute('content') : '';
      return {
         'Content-Type': 'application/json',
         'Accept': 'application/json',
         'X-CSRF-Token': token
      };
   }

   async function loadEmpresas() {
      try {
         const res = await fetch('/api/v1/empresas?size=1000&page=1');
         const json = await res.json();
         if (!json.ok) throw new Error(json.error?.message || 'Error al cargar empresas');

         let empresas = [];

         if (Array.isArray(json.data)) {
            empresas = json.data;
         } else if (json.data && Array.isArray(json.data.rows)) {
            empresas = json.data.rows;
         } else if (Array.isArray(json.empresas)) {
            empresas = json.empresas;
         }

         empresas.forEach(e => {
            const opt = document.createElement('option');
            opt.value = e.id;
            opt.textContent = `${e.nombre} (${e.rfc || 'SIN RFC'})`;
            $empresaSelect.appendChild(opt);
         });
      } catch (e) {
         console.error(e);
         notify.error('No se pudieron cargar las empresas');
      }
   }

   async function loadResponsables() {
      try {
         const res = await fetch('/api/v1/catalogos/jefes');
         const json = await res.json();
         if (!json.ok) throw new Error(json.error?.message || 'Error al cargar responsables');

         const lista = json.data || json.jefes || [];
         responsablesCache = lista;

         $responsableSel.innerHTML = '';
         const optEmpty = document.createElement('option');
         optEmpty.value = '';
         optEmpty.textContent = 'Seleccione un responsable...';
         $responsableSel.appendChild(optEmpty);

         lista.forEach(u => {
            const opt = document.createElement('option');
            opt.value = u.id;
            opt.textContent = u.nombre || u.nombre_completo || `Usuario #${u.id}`;
            $responsableSel.appendChild(opt);
         });
      } catch (e) {
         console.error(e);
         notify.error('No se pudieron cargar los responsables');
      }
   }

   async function loadRutinasEmpresa(empresaId) {
      try {
         const res = await fetch(`/api/v1/empresas/obligaciones/rutinas?empresa_id=${encodeURIComponent(empresaId)}`);
         const json = await res.json();
         if (!json.ok) throw new Error(json.error?.message || 'Error al cargar rutinas');

         currentEmpresa = json.data.empresa;
         const obligaciones = json.data.obligaciones || [];

         console.log('Obligaciones rutinas:', obligaciones);

         $emptyState.classList.add('d-none');
         $content.classList.remove('d-none');

         $empresaIdInput.value = currentEmpresa.id;

         const grupos = {};

         obligaciones.forEach(o => {
            const orgRaw =
               (o.obligacion_organismo ?? '') ||
               (o.organismo ?? '') ||
               '';

            const org = orgRaw.toString().trim() !== '' ? orgRaw.toString().trim() : 'OTRAS DEPENDENCIAS';

            if (!grupos[org]) {
               grupos[org] = {
                  key: `grp-${org}`,
                  title: org,
                  folder: true,
                  expanded: true,
                  unselectable: true,
                  extraClasses: `grupo-${org.replace(/\s+/g, '-').toLowerCase()}`,
                  data: { tipo: 'grupo', organismo: org },
                  children: []
               };
            }

            grupos[org].children.push({
               key: String(o.obligacion_id),
               title: `${o.obligacion_clave || ''} - ${o.obligacion_desc || ''}`.trim(),
               data: o
            });
         });

         const treeData = Object.values(grupos);

         try {
            if ($treeContainer.data('ui-fancytree') || $treeContainer.data('fancytree')) {
               $treeContainer.fancytree('destroy');
            }
         } catch (e) {
            console.warn('Fancytree destroy warning:', e);
         }

         $treeContainer.fancytree({
            source: treeData,

            // Selección / des-selección por clic en el título
            click: function (event, data) {
               if (data.targetType !== 'title') return;

               const node = data.node;

               // Si ya está activo y vuelves a hacer clic, lo des-activa
               if (node.isActive()) {
                  node.setActive(false);
                  onSelectObligacion(null);
                  return;
               }

               // Activar nuevo nodo
               node.setActive(true);

               // Solo reaccionamos si es obligación (hoja, no carpeta)
               if (!node.folder && node.data && node.data.obligacion_id) {
                  onSelectObligacion(node.data);
               } else {
                  onSelectObligacion(null);
               }
            },

            renderNode: function (event, data) {
               const node = data.node;
               const $span = $(node.span);
               const $icon = $span.find('.fancytree-icon');

               $icon.attr('class', 'fancytree-icon');

               if (node.folder) {
                  // Grupo / organismo
                  $icon.addClass('fas fa-landmark text-info');
               } else {
                  // Obligación
                  $icon.addClass('fas fa-file-alt text-warning');
               }
            }
         });
      } catch (e) {
         console.error(e);
         notify.error('No se pudieron cargar las obligaciones de la empresa');
      }
   }

   function onSelectObligacion(data) {
      if (!data) {
         currentObligacion = null;
         $form.classList.add('d-none');
         $noObligacion.classList.remove('d-none');
         $formTitle.textContent = 'Detalle de rutina';
         return;
      }

      currentObligacion = data;

      $noObligacion.classList.add('d-none');
      $form.classList.remove('d-none');

      $obligacionIdInp.value = data.obligacion_id;
      $obligDesc.value = data.obligacion_desc || '';
      $obligClave.value = data.obligacion_clave || '';

      $periodicidad.value = data.periodicidad || 'MENSUAL';

      $diaVenc.value = data.dia_vencimiento != null ? data.dia_vencimiento : '';
      $diasAnt.value = data.dias_anticipacion != null ? data.dias_anticipacion : 5;
      $offsetDias.value = data.offset_dias != null ? data.offset_dias : 0;
      $enviarCorreo.checked = data.enviar_correo != null ? !!data.enviar_correo : true;
      $fechaInicio.value = data.fecha_inicio || '';
      $fechaFin.value = data.fecha_fin || '';
      $notas.value = data.notas || '';

      const respId = data.responsable_id || currentEmpresa.responsable_id || '';
      $responsableSel.value = respId ? String(respId) : '';

      $formTitle.textContent = `Rutina: ${data.obligacion_desc || ''}`;
   }

   async function guardarRutina() {
      if (!currentEmpresa || !currentObligacion) {
         notify.error('Primero selecciona una empresa y una obligación');
         return;
      }

      const empresaId = currentEmpresa.id;
      const obligacionId = currentObligacion.obligacion_id;

      const body = {
         empresa_id: empresaId,
         obligacion_id: obligacionId,
         dia_vencimiento: $diaVenc.value ? parseInt($diaVenc.value, 10) : null,
         dias_anticipacion: $diasAnt.value ? parseInt($diasAnt.value, 10) : 5,
         offset_dias: $offsetDias.value ? parseInt($offsetDias.value, 10) : 0,
         responsable_id: $responsableSel.value ? parseInt($responsableSel.value, 10) : null,
         enviar_correo: $enviarCorreo.checked ? 1 : 0,
         fecha_inicio: $fechaInicio.value || null,
         fecha_fin: $fechaFin.value || null,
         notas: $notas.value || null,
         periodicidad: $periodicidad.value || null
      };

      try {
         const res = await fetch('/api/v1/empresas/obligaciones/rutina', {
            method: 'PUT',
            headers: authHeaders(),
            body: JSON.stringify(body)
         });
         const json = await res.json();
         if (!json.ok) throw new Error(json.error?.message || 'No se pudo guardar la rutina');

         notify.success('Rutina guardada correctamente');
         await loadRutinasEmpresa(empresaId);
      } catch (e) {
         console.error(e);
         notify.error(e.message || 'Error al guardar la rutina');
      }
   }

   function bindEvents() {
      if (!$empresaSelect) return;

      $empresaSelect.addEventListener('change', async function () {
         const empresaId = this.value;
         if (!empresaId) {
            currentEmpresa = null;
            currentObligacion = null;
            $content.classList.add('d-none');
            $emptyState.classList.remove('d-none');
            $form.classList.add('d-none');
            $noObligacion.classList.remove('d-none');
            $formTitle.textContent = 'Detalle de rutina';
            return;
         }

         await loadResponsables();
         await loadRutinasEmpresa(empresaId);
      });

      if ($btnGuardar) {
         $btnGuardar.addEventListener('click', guardarRutina);
      }
   }

   function init() {
      const page = document.getElementById('rutinas-page');
      if (!page) return;

      bindEvents();
      loadEmpresas();
   }

   document.addEventListener('DOMContentLoaded', init);
})();
