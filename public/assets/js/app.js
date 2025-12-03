window.AppLoader = {
   el: null,
   ensure() { this.el = this.el || document.getElementById('app-loader'); },
   show(msg) {
      this.ensure();
      if (this.el) {
         this.el.classList.remove('d-none');
         if (msg) this.el.querySelector('.loader-text').textContent = msg;
      }
   },
   hide() {
      this.ensure();
      if (this.el) {
         this.el.classList.add('d-none');
      }
   }
};

/* ============================================================
      🔻 BLOQUE: Cargar info del usuario en el dropdown
   ============================================================ */
window.addEventListener('DOMContentLoaded', () => {
   const dropdown = document.getElementById('nav-user-dropdown');
   if (!dropdown) return; // si no existe el dropdown, no hacemos nada

   const nameShortEl = document.getElementById('nav-user-name');      // texto en el navbar
   const fullNameEl = document.getElementById('nav-user-fullname');  // dentro del dropdown
   const areaEl = document.getElementById('nav-user-area');
   const roleEl = document.getElementById('nav-user-role');

   const notify = window.notify || { error: console.error };

   const authHeaders = () => {
      const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
      return {
         'X-Requested-With': 'XMLHttpRequest',
         'X-CSRF-Token': token,
         'Accept': 'application/json'
      };
   };

   const setText = (el, text, prefix = '') => {
      if (!el) return;
      if (!text) {
         el.textContent = '';
      } else {
         el.textContent = prefix ? `${prefix}: ${text}` : text;
      }
   };

   // --- 1) Promesa: whoami ---
   const pWhoami = fetch('/api/v1/auth/whoami', {
      headers: authHeaders()
   })
      .then(r => r.ok ? r.json() : Promise.reject(r))
      .then(j => {
         if (!j || j.ok === false) {
            throw new Error(j?.error?.message || 'Error al obtener usuario');
         }
         return j.data || j; // devolvemos directamente el objeto usuario
      });

   // --- 2) Promesa: catálogo de áreas (para mostrar nombre de área) ---
   const pAreas = fetch('/api/v1/catalogos/areas', {
      headers: authHeaders()
   })
      .then(r => r.ok ? r.json() : Promise.reject(r))
      .then(j => {
         if (!j || j.ok === false) {
            throw new Error(j?.error?.message || 'Error al obtener áreas');
         }
         const map = {};
         (j.data || []).forEach(a => {
            map[String(a.id)] = a.nombre || '';
         });
         return map; // devolvemos un mapa id -> nombre
      })
      .catch(err => {
         console.error('[navbar user] error áreas:', err);
         return {}; // si falla, regresamos mapa vacío para no tronarnos
      });

   // --- 3) Resolver ambas en paralelo ---
   Promise.all([pWhoami, pAreas])
      .then(([u, areasMap]) => {
         const nombre = u.nombre || u.name || '';
         const areaId = u.area_id ?? null;
         const areaNom = areaId != null ? (areasMap[String(areaId)] || '') : '';

         // Rol principal: arreglo de strings ["Gerencia"]
         let rolNombre = '';
         if (Array.isArray(u.roles) && u.roles.length > 0) {
            // En tu caso: ["Gerencia"]
            rolNombre = String(u.roles[0] ?? '');
         }

         // Nombre corto en el navbar
         if (nameShortEl) {
            const parts = String(nombre).trim().split(/\s+/);
            nameShortEl.textContent = parts[0] || 'Usuario';
         }

         // Nombre completo en el dropdown
         setText(fullNameEl, nombre || 'Usuario');

         // Área (preferimos nombre; si no, mostramos el ID)
         const areaTexto = areaNom || (areaId != null ? `ID ${areaId}` : '');
         setText(areaEl, areaTexto, 'Área');

         // Rol (si no hay, mostramos "Sin rol")
         setText(roleEl, rolNombre || 'Sin rol', 'Rol');
      })
      .catch(err => {
         console.error('[navbar user] error whoami:', err);
         notify.error('No se pudo cargar la información del usuario.');
      });
});
/* ============================================================
      🔺 FIN BLOQUE
   ============================================================ */

/* ============================================================
      🔻 HANDLER ORIGINAL DE LOGOUT
   ============================================================ */
window.addEventListener('DOMContentLoaded', () => {
   const btn = document.getElementById('btn-logout');
   if (btn) {
      btn.addEventListener('click', async (e) => {
         e.preventDefault();
         try {
            window.AppLoader?.show('Cerrando sesión…');
            await Api.post('/api/logout');
            location.href = '/login';
         } catch (err) {
            alert('No se pudo cerrar sesión');
            console.error(err);
         } finally {
            window.AppLoader?.hide();
         }
      });
   }
});
/* ============================================================ */

