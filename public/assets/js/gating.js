// public/assets/js/gating.js
(function () {
   // null = aún no cargados; [] = cargados sin permisos; Array = permisos
   let __PERMS = null;

   async function fetchPerms() {
      try {
         const res = await fetch('/api/v1/auth/whoami', { headers: { 'Accept': 'application/json' } });
         const j = await res.json();
         return Array.isArray(j?.data?.permisos) ? j.data.permisos : [];
      } catch {
         return []; // estricto: si falla, negar
      }
   }

   async function loadPermsOnce() {
      if (Array.isArray(__PERMS)) return __PERMS;
      __PERMS = await fetchPerms();
      return __PERMS;
   }

   function canOneLocal(perm) {
      if (!Array.isArray(__PERMS)) return false;      // estricto
      if (__PERMS.includes(perm)) return true;
      const parts = perm.split('.');
      for (let i = parts.length; i > 0; i--) {
         const wc = parts.slice(0, i).join('.') + '.*';
         if (__PERMS.includes(wc)) return true;
      }
      return false;
   }

   function checkRequired(list, anyMode) {
      const req = list.filter(Boolean);
      if (!req.length) return true;
      return anyMode ? req.some(canOneLocal) : req.every(canOneLocal);
   }

   async function applyGates(root = document) {
      await loadPermsOnce();
      root.querySelectorAll('[data-perm]').forEach(el => {
         const raw = el.getAttribute('data-perm') || '';
         const any = el.hasAttribute('data-perm-any');
         const mode = (el.getAttribute('data-perm-mode') || 'disable').toLowerCase();
         const req = raw.split(/[,\s]+/).filter(Boolean);

         const ok = checkRequired(req, any);
         if (ok) {
            el.classList.remove('d-none', 'disabled');
            el.removeAttribute('aria-disabled');
            el.disabled = false;
            return;
         }
         if (mode === 'hide') {
            el.classList.add('d-none');
         } else {
            el.disabled = true;
            el.classList.add('disabled');
            if (!el.title) el.title = 'No autorizado';
            el.setAttribute('aria-disabled', 'true');
         }
      });
   }

   // Recargar permisos desde /whoami y re-aplicar gating
   async function reloadPerms(root = document) {
      __PERMS = await fetchPerms();
      await applyGates(root);
   }

   // Helpers de depuración (opcionales)
   window.__permsRaw = () => (Array.isArray(__PERMS) ? [...__PERMS] : null);
   window.__whyPerm = async (perm) => {
      await loadPermsOnce();
      const list = Array.isArray(__PERMS) ? __PERMS : [];
      const direct = list.includes(perm);
      const parts = perm.split('.');
      const wildcards = [];
      for (let i = parts.length; i > 0; i--) wildcards.push(parts.slice(0, i).join('.') + '.*');
      const wcMatch = wildcards.find(w => list.includes(w)) || null;
      return { perm, direct, wcMatch, list };
   };

   // Export
   window.__applyGates = applyGates;
   window.__reloadPerms = reloadPerms;
   window.__canPerm = async (p) => { await loadPermsOnce(); return canOneLocal(p); };

   document.addEventListener('DOMContentLoaded', () => { applyGates(); });
})();
