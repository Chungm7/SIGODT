<!-- Navbar -->
<header class="navbar navbar-expand-md sticky-top d-print-none" data-bs-theme="dark">
  <div class="container-xl">
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu"
      aria-controls="navbar-menu" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <h1 class="navbar-brand navbar-brand-autodark pe-0 pe-md-3">
      <a href="../inicio/" class="d-flex align-items-center" style="text-decoration: none;">
        <img src="../../public/img/sis_logo.png" width="42px" alt="SIGODT" class="navbar-brand-image me-2">
        <div class="d-none d-md-block" style="text-align: left;">
          <div class="fs-5" style="white-space: break-spaces;"><?php echo mb_strtoupper($_ENV["SIS_NOM"] ?? "Sistema de Gestión de Órdenes de Derecho de Trámite"); ?></div>
        </div>
      </a>
    </h1>
    <?php
    // Validación de sesión
    if (isset($_SESSION["usua_id_SIGODT"])) {
      $usuario = $_SESSION["nombre_completo_SIGODT"];
      $perfil = $_SESSION["rol_nombre_SIGODT"];
    } else {
      $usuario = "Invitado";
      $perfil = "Sin perfil";
    }
    ?>
    <div class="navbar-nav flex-row order-md-last">
      <div class="nav-item d-none d-md-flex me-3">
        <div class="btn-list">


        </div>
      </div>
      <div class="d-none d-md-flex">
        <a href="?theme=dark" class="nav-link px-0 hide-theme-dark" title="Enable dark mode" data-bs-toggle="tooltip"
          data-bs-placement="bottom">
          <!-- Download SVG icon from http://tabler-icons.io/i/moon -->
          <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24"
            stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
            <path d="M12 3c.132 0 .263 0 .393 0a7.5 7.5 0 0 0 7.92 12.446a9 9 0 1 1 -8.313 -12.454z" />
          </svg>
        </a>
        <a href="?theme=light" class="nav-link px-0 hide-theme-light" title="Enable light mode" data-bs-toggle="tooltip"
          data-bs-placement="bottom">
          <!-- Download SVG icon from http://tabler-icons.io/i/sun -->
          <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24"
            stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
            <path d="M12 12m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0" />
            <path d="M3 12h1m8 -9v1m8 8h1m-9 8v1m-6.4 -15.4l.7 .7m12.1 -.7l-.7 .7m0 11.4l.7 .7m-12.1 -.7l-.7 .7" />
          </svg>
        </a>

      </div>
      <div class="nav-item dropdown d-none d-md-flex me-3">
        <a href="#" class="nav-link px-0" data-bs-toggle="dropdown" tabindex="-1" aria-label="Show app menu" data-bs-auto-close="outside" aria-expanded="true">
          <!-- Download SVG icon from http://tabler.io/icons/icon/apps -->
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
            <path d="M4 4m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z"></path>
            <path d="M4 14m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z"></path>
            <path d="M14 14m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z"></path>
            <path d="M14 7l6 0"></path>
            <path d="M17 4l0 6"></path>
          </svg>
        </a>
        <div class="d-none d-md-flex">
          <div class="dropdown-menu dropdown-menu-arrow dropdown-menu-end dropdown-menu-card" data-bs-popper="static">
            <div class="card "> <!-- bg-white text-dark -->
              <div class="card-header">
                <div class="card-title">Mis Sistemas</div>
              </div>
              <div class="input-group input-group-sm" style="width: 100%; padding-inline: 5px;">
                <span class="input-group-text">
                  <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-search" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                    <path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" />
                    <path d="M21 21l-6 -6" />
                  </svg>
                </span>
                <input type="text"
                  id="searchSistemas"
                  class="form-control"
                  placeholder="Buscar...">
              </div>
              <div class="card-body scroll-y p-2" style="max-height: 50vh; width: 300px;">
                <div class="row g-0" id="sistemasContainer">

                </div>
              </div>
            </div>
          </div>
        </div>

      </div>
      <div class="nav-item dropdown">
        <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown" aria-label="Open user menu">
          <span class="avatar avatar-sm" style="background-image: url(../../public/img/perfil.jpeg)"></span>
          <div class="d-none d-xl-block ps-2">
            <div><?php echo htmlspecialchars($usuario); ?></div>
            <div class="mt-1 small text-secondary"><?php echo htmlspecialchars($perfil); ?></div>
          </div>
        </a>
        <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">

          <a href="../setting/" class="dropdown-item">Configuración</a>
          <a href="../../view/html/logout.php" class="dropdown-item">Cerrar Sesión</a>
        </div>
      </div>
    </div>
  </div>
</header>