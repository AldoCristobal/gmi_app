<?php
$title = 'Iniciar sesión';
$isLogin = true;   // <--- MUY IMPORTANTE
ob_start();
?>
<div class="login-box">
   <div class="login-logo"><b>ERP</b> GMI</div>
   <div class="card">
      <div class="card-body login-card-body">
         <p class="login-box-msg">Ingresa tus credenciales</p>
         <form id="login-form">
            <div class="input-group mb-3">
               <input type="email" id="lg-email" class="form-control" placeholder="Email" required>
               <div class="input-group-append">
                  <div class="input-group-text"><span class="fas fa-envelope"></span></div>
               </div>
            </div>
            <div class="input-group mb-3">
               <input type="password" id="lg-pass" class="form-control" placeholder="Contraseña" required>
               <div class="input-group-append">
                  <div class="input-group-text"><span class="fas fa-lock"></span></div>
               </div>
            </div>
            <button type="submit" id="btn-login" class="btn btn-primary btn-block">Entrar</button>
         </form>
      </div>
   </div>
</div>

<script src="/assets/js/login.js"></script>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
