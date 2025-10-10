(function () {
   const form = document.getElementById('login-form');
   const email = document.getElementById('lg-email');
   const pass = document.getElementById('lg-pass');
   const btn = document.getElementById('btn-login');

   form.addEventListener('submit', async (e) => {
      e.preventDefault();
      btn.disabled = true;
      btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Entrando...';

      try {
         const res = await fetch('/api/login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ email: email.value.trim(), password: pass.value })
         });

         const text = await res.text(); // lee como texto primero
         let j; try { j = JSON.parse(text); } catch { j = null; }

         if (!res.ok || !j || j.ok === false) {
            const msg = (j && j.error && (j.error.message || j.error.code)) || (`HTTP ${res.status} - ${text.slice(0, 200)}`);
            alert('Login fallido: ' + msg);
            btn.disabled = false;
            btn.innerHTML = 'Entrar';
            return;
         }

         if (j.data && j.data.csrf_token) localStorage.setItem('csrf_token', j.data.csrf_token);
         location.href = '/admin/usuarios';
      } catch (err) {
         alert('Error de red o CORS');
         btn.disabled = false;
         btn.innerHTML = 'Entrar';
      }
   });
})();
