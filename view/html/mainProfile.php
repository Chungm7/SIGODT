<!-- Institutional shell adapted from sisIndicadores; SIGODT actions and session data retained. -->
<header class="navbar navbar-expand-md d-print-none app-global-header" data-bs-theme="dark">
  <div class="app-header-container">
    <h1 class="navbar-brand navbar-brand-autodark pe-0 pe-md-3 m-0">
      <a href="../inicio/" class="header-brand">
        <span class="header-icon-box" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-receipt-2">
            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
            <path d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16l-3 -2l-2 2l-2 -2l-2 2l-2 -2l-3 2" />
            <path d="M14 8h-2.5a1.5 1.5 0 0 0 0 3h1a1.5 1.5 0 0 1 0 3h-2.5m2 0v1.5m0 -9v1.5" />
          </svg>
        </span>
        <span class="header-brand-info">
          <span class="header-brand-title">
            <?php echo htmlspecialchars(mb_strtoupper($_ENV["SIS_NOM"] ?? "Sistema de Gestión de Órdenes de Derecho de Trámite")); ?>
          </span>
          <span class="header-brand-badge">SIGODT</span>
        </span>
      </a>
    </h1>
    <?php
    if (isset($_SESSION["usua_id_SIGODT"])) {
      $usuario = $_SESSION["nombre_completo_SIGODT"];
      $perfil = $_SESSION["rol_nombre_SIGODT"];
      if (!array_key_exists("usua_foto_SIGODT", $_SESSION)) {
        require_once __DIR__ . '/../../models/Usuario.php';
        $_SESSION["usua_foto_SIGODT"] = (new Usuario())->get_foto_persona($_SESSION["usua_id_SIGODT"]);
      }
      $avatar = !empty($_SESSION["usua_foto_SIGODT"]) ? $_SESSION["usua_foto_SIGODT"] : "../../public/img/perfil.jpeg";
    } else {
      $usuario = "Invitado";
      $perfil = "Sin perfil";
      $avatar = "../../public/img/perfil.jpeg";
    }
    ?>
    <div class="shell-header-controls">
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu"
        aria-controls="navbar-menu" aria-expanded="false" aria-label="Alternar navegación">
        <span class="navbar-toggler-icon"></span>
      </button>
    <div class="navbar-nav flex-row order-md-last shell-profile-actions">
      <div class="nav-item dropdown">
        <a href="#" class="nav-link user-profile-pill" data-bs-toggle="dropdown" aria-label="Abrir menú de usuario">
          <span class="avatar avatar-sm user-avatar-circle" style="background-image: url('<?php echo htmlspecialchars($avatar, ENT_QUOTES, 'UTF-8'); ?>')"></span>
          <div class="user-meta-info d-none d-sm-flex">
            <div class="user-full-name"><?php echo htmlspecialchars(mb_strtoupper($usuario)); ?></div>
            <div class="user-role-badge"><?php echo htmlspecialchars(mb_strtoupper($perfil)); ?></div>
          </div>
          <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm icon-tabler icon-tabler-chevron-down ms-1 user-chevron" width="20" height="20" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
            <path d="M6 9l6 6l6 -6" />
          </svg>
        </a>
        <div class="dropdown-menu dropdown-menu-end muni-profile-dropdown" data-bs-theme="light">
          <div class="profile-mobile-identity px-3 py-2 border-bottom d-sm-none">
            <div class="fw-bold text-dark small"><?php echo htmlspecialchars(mb_strtoupper($usuario)); ?></div>
            <div class="text-muted small"><?php echo htmlspecialchars(mb_strtoupper($perfil)); ?></div>
          </div>
          <a href="../setting/" class="dropdown-item">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-settings me-2">
              <path stroke="none" d="M0 0h24v24H0z" fill="none" />
              <path d="M10.325 4.317c.426 -1.756 2.924 -1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543 -.94 3.31 .826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756 .426 1.756 2.924 0 3.35a1.724 1.724 0 0 0 -1.066 2.573c.94 1.543 -.826 3.31 -2.37 2.37a1.724 1.724 0 0 0 -2.572 1.065c-.426 1.756 -2.924 1.756 -3.35 0a1.724 1.724 0 0 0 -2.573 -1.066c-1.543 .94 -3.31 -.826 -2.37 -2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756 -.426 -1.756 -2.924 0 -3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94 -1.543 .826 -3.31 2.37 -2.37c1 .608 2.296 .07 2.572 -1.065z" />
              <path d="M9 12a3 3 0 1 0 6 0a3 3 0 0 0 -6 0" />
            </svg>
            Configuración
          </a>
          <div class="dropdown-divider"></div>
          <a href="../../view/html/logout.php" class="dropdown-item muni-item-danger">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-logout me-2">
              <path stroke="none" d="M0 0h24v24H0z" fill="none" />
              <path d="M14 8v-2a2 2 0 0 0 -2 -2h-7a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h7a2 2 0 0 0 2 -2v-2" />
              <path d="M9 12h12l-3 -3" />
              <path d="M18 15l3 -3" />
            </svg>
            Cerrar Sesión
          </a>
        </div>
      </div>
    </div>
    </div>
  </div>
</header>
