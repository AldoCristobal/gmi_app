window.AppLoader = {
   el: null,
   ensure() { this.el = this.el || document.getElementById('app-loader'); },
   show(msg) { this.ensure(); if (this.el) { this.el.classList.remove('d-none'); if (msg) this.el.querySelector('.loader-text').textContent = msg; } },
   hide() { this.ensure(); if (this.el) { this.el.classList.add('d-none'); } }
};

// --- añade esto al final de public/assets/js/app.js ---
window.addEventListener('DOMContentLoaded', () => {
   // handler para el botón de salir del navbar
   const btn = document.getElementById('btn-logout');
   if (btn) {
      btn.addEventListener('click', async (e) => {
         e.preventDefault();
         try {
            window.AppLoader?.show('Cerrando sesión…');
            // Api.js debe cargarse ANTES que app.js en el layout
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