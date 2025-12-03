<nav class="main-header navbar navbar-expand navbar-dark">
   <!-- Left navbar links -->
   <ul class="navbar-nav">
      <li class="nav-item">
         <a class="nav-link" data-widget="pushmenu" href="#" role="button">
            <i class="fas fa-bars"></i>
         </a>
      </li>
      <li class="nav-item d-none d-sm-inline-block">
         <a href="/" class="nav-link">Inicio</a>
      </li>
   </ul>

   <!-- Right navbar -->
   <ul class="navbar-nav ml-auto" id="top-right-nav">
      <!-- Dropdown de usuario (se rellena por JS) -->
      <li class="nav-item dropdown" id="nav-user-dropdown">
         <a class="nav-link dropdown-toggle d-flex align-items-center"
            href="#"
            role="button"
            data-toggle="dropdown"
            aria-haspopup="true"
            aria-expanded="false">
            <span class="nav-user-avatar mr-2 d-flex align-items-center justify-content-center">
               <i class="fas fa-user"></i>
            </span>
            <span class="d-none d-sm-inline" id="nav-user-name">Usuario</span>
         </a>
         <div class="dropdown-menu dropdown-menu-right shadow-sm p-2">
            <div class="px-2 pb-2 border-bottom small text-muted">
               <div id="nav-user-fullname" class="font-weight-bold">
                  <!-- Nombre completo -->
               </div>
               <div id="nav-user-area">
                  <!-- Área -->
               </div>
               <div id="nav-user-role">
                  <!-- Rol -->
               </div>
            </div>

            <button class="dropdown-item text-danger mt-1" id="btn-logout">
               <i class="fas fa-right-from-bracket mr-2"></i> Salir
            </button>
         </div>
      </li>
   </ul>
</nav>