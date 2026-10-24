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
      <div class="nav-item dropdown">
        <a href="#" class="nav-link d-flex lh-1 text-reset p-0 align-items-center" data-bs-toggle="dropdown" aria-label="Open user menu">
          <span class="avatar avatar-sm" style="background-image: url(../../public/img/perfil.jpeg)"></span>
          <div class="d-none d-xl-block ps-2">
            <div><?php echo htmlspecialchars($usuario); ?></div>
            <div class="mt-1 small text-secondary"><?php echo htmlspecialchars($perfil); ?></div>
          </div>
          <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm icon-tabler icon-tabler-chevron-down ms-1 text-secondary" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
            <path d="M6 9l6 6l6 -6" />
          </svg>
        </a>
        <div class="dropdown-menu dropdown-menu-end">

          <a href="../setting/" class="dropdown-item">Configuración</a>
          <a href="../../view/html/logout.php" class="dropdown-item">Cerrar Sesión</a>
        </div>
      </div>
    </div>
  </div>
</header>