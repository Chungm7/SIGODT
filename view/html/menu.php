<?php
$currentPage = $_SERVER['PHP_SELF'] ?? '';
$isInicioActive = (strpos($currentPage, '/inicio/') !== false);
$isDashboardActive = (strpos($currentPage, '/dashboard/') !== false);
$isProcesosActive = (strpos($currentPage, '/giros_ciudadano/') !== false || strpos($currentPage, '/admin_giros_ciudadano/') !== false);
$isConsultasActive = (strpos($currentPage, '/consultar_nombre/') !== false || strpos($currentPage, '/consultar_documento/') !== false || strpos($currentPage, '/consultar_og/') !== false || strpos($currentPage, '/consultar_mes/') !== false);
$isMntActive = (strpos($currentPage, '/mnt_') !== false);
$isReportActive = (strpos($currentPage, '/reporte_usuario/') !== false);
$isMntAllowed = false;
if (class_exists('Conectar') && method_exists('Conectar', 'esAdministrador')) {
    $isMntAllowed = Conectar::esAdministrador();
} elseif (isset($_SESSION["usua_id_SIGODT"])) {
    $roleId = filter_var($_SESSION["rol_id_SIGODT"] ?? null, FILTER_VALIDATE_INT);
    $roleName = $_SESSION["rol_nombre_SIGODT"] ?? '';
    $isMntAllowed = ($roleId === 9) || (is_string($roleName) && strcasecmp(trim($roleName), 'ADMINISTRADOR') === 0);
}
?>
<header class="navbar-expand-md">
  <div class="collapse navbar-collapse" id="navbar-menu">
    <div class="navbar muni-subnav">
      <div class="container-xl">
        <ul class="navbar-nav">
          <li class="nav-item <?php echo $isInicioActive ? 'active' : ''; ?>">
            <a class="nav-link" href="../inicio/">
              <span
                class="nav-link-icon d-md-none d-lg-inline-block">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24"
                  stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                  <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                  <path d="M5 12l-2 0l9 -9l9 9l-2 0" />
                  <path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-7" />
                  <path d="M9 21v-6a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v6" />
                </svg>
              </span>
              <span class="nav-link-title">
                Inicio
              </span>
            </a>
          </li>
          <li class="nav-item <?php echo $isDashboardActive ? 'active' : ''; ?>">
            <a class="nav-link" href="../dashboard/">
              <span
                class="nav-link-icon d-md-none d-lg-inline-block">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                  stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                  class="icon icon-tabler icons-tabler-outline icon-tabler-chart-histogram">
                  <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                  <path d="M3 3v18h18" />
                  <path d="M20 18v3" />
                  <path d="M16 16v5" />
                  <path d="M12 13v8" />
                  <path d="M8 16v5" />
                  <path d="M3 11c6 0 5 -5 9 -5s3 5 9 5" />
                </svg>
              </span>
              <span class="nav-link-title">
                Dashboard
              </span>
            </a>
          </li>
          <li class="nav-item dropdown <?php echo $isProcesosActive ? 'active' : ''; ?>">
            <a class="nav-link dropdown-toggle" href="#navbar-procesos" data-bs-toggle="dropdown"
              data-bs-auto-close="outside" role="button" aria-expanded="false">
              <span
                class="nav-link-icon d-md-none d-lg-inline-block">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24"
                  stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                  <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                  <path d="M12 3l8 4.5l0 9l-8 4.5l-8 -4.5l0 -9l8 -4.5" />
                  <path d="M12 12l8 -4.5" />
                  <path d="M12 12l0 9" />
                  <path d="M12 12l-8 -4.5" />
                  <path d="M16 5.25l-8 4.5" />
                </svg>
              </span>
              <span class="nav-link-title">
                Procesos
              </span>
            </a>
            <div class="dropdown-menu">
              <a href="../giros_ciudadano/" class="dropdown-item <?php echo (strpos($currentPage, '/giros_ciudadano/') !== false) ? 'active' : ''; ?>">
                <span class="dropdown-item-icon">
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-file-invoice">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                    <path d="M14 3v4a1 1 0 0 0 1 1h4" />
                    <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" />
                    <path d="M9 7l1 0" />
                    <path d="M9 13l6 0" />
                    <path d="M13 17l2 0" />
                  </svg>
                </span>
                Órdenes de Derecho de Trámite
              </a>
              <?php if ($isMntAllowed) { ?>
              <a href="../admin_giros_ciudadano/" class="dropdown-item <?php echo (strpos($currentPage, '/admin_giros_ciudadano/') !== false) ? 'active' : ''; ?>">
                <span class="dropdown-item-icon">
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-clipboard-check">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                    <path d="M9 5h-2a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-12a2 2 0 0 0 -2 -2h-2" />
                    <path d="M9 3m0 2a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v0a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2z" />
                    <path d="M9 14l2 2l4 -4" />
                  </svg>
                </span>
                Auditoría de Órdenes de Giro
              </a>
              <?php } ?>
            </div>
          </li>
          <li class="nav-item dropdown <?php echo $isConsultasActive ? 'active' : ''; ?>">
            <a class="nav-link dropdown-toggle" href="#navbar-consultas" data-bs-toggle="dropdown"
              data-bs-auto-close="outside" role="button" aria-expanded="false">
              <span
                class="nav-link-icon d-md-none d-lg-inline-block">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                  stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                  class="icon icon-tabler icons-tabler-outline icon-tabler-search">
                  <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                  <path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" />
                  <path d="M21 21l-6 -6" />
                </svg>
              </span>
              <span class="nav-link-title">
                Consultas
              </span>
            </a>
            <div class="dropdown-menu">
              <a href="../consultar_documento/" class="dropdown-item <?php echo (strpos($currentPage, '/consultar_documento/') !== false) ? 'active' : ''; ?>">
                <span class="dropdown-item-icon">
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-id">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                    <path d="M3 4m0 3a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v10a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3z" />
                    <path d="M9 10m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
                    <path d="M15 8l2 0" />
                    <path d="M15 12l2 0" />
                    <path d="M7 16l10 0" />
                  </svg>
                </span>
                Consulta por DNI/RUC/CEE/CPP
              </a>
              <a href="../consultar_nombre/" class="dropdown-item <?php echo (strpos($currentPage, '/consultar_nombre/') !== false) ? 'active' : ''; ?>">
                <span class="dropdown-item-icon">
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-user-search">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                    <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" />
                    <path d="M6 21v-2a4 4 0 0 1 4 -4h1.5" />
                    <path d="M18 18m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" />
                    <path d="M20.2 20.2l1.8 1.8" />
                  </svg>
                </span>
                Consulta por Nombre / Razón Social
              </a>
              <a href="../consultar_og/" class="dropdown-item <?php echo (strpos($currentPage, '/consultar_og/') !== false) ? 'active' : ''; ?>">
                <span class="dropdown-item-icon">
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-file-search">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                    <path d="M14 3v4a1 1 0 0 0 1 1h4" />
                    <path d="M12 21h-5a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v4.5" />
                    <path d="M16.5 17.5m-2.5 0a2.5 2.5 0 1 0 5 0a2.5 2.5 0 1 0 -5 0" />
                    <path d="M18.5 19.5l2.5 2.5" />
                  </svg>
                </span>
                Consulta por Orden de Giro
              </a>
            </div>
          </li>
          <li class="nav-item <?php echo $isReportActive ? 'active' : ''; ?>">
            <a class="nav-link" href="../reporte_usuario/">
              <span class="nav-link-icon d-md-none d-lg-inline-block">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icons-tabler-outline icon-tabler-file-analytics" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                  <path d="M14 3v4a1 1 0 0 0 1 1h4" />
                  <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" />
                  <path d="M9 17l0 -5" />
                  <path d="M12 17l0 -1" />
                  <path d="M15 17l0 -3" />
                </svg>
              </span>
              <span class="nav-link-title">
                Reportes
              </span>
            </a>
          </li>
          <?php if ($isMntAllowed): ?>
          <li class="nav-item dropdown <?php echo $isMntActive ? 'active' : ''; ?>">
            <a class="nav-link dropdown-toggle" href="#navbar-mantenimientos" data-bs-toggle="dropdown"
              data-bs-auto-close="outside" role="button" aria-expanded="false">
              <span
                class="nav-link-icon d-md-none d-lg-inline-block">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                  stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                  class="icon icon-tabler icons-tabler-outline icon-tabler-category-2">
                  <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                  <path d="M14 4h6v6h-6z" />
                  <path d="M4 14h6v6h-6z" />
                  <path d="M17 17m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" />
                  <path d="M7 7m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" />
                </svg>
              </span>
              <span class="nav-link-title">
                Mantenimientos
              </span>
            </a>
            <div class="dropdown-menu">
              <div class="dropdown-menu-columns">
                <div class="dropdown-menu-column">

                  <div class="dropend">
                    <a class="dropdown-item dropdown-toggle" href="#sidebar-authentication" data-bs-toggle="dropdown"
                      data-bs-auto-close="outside" role="button" aria-expanded="false">
                      <span class="dropdown-item-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-address-book">
                          <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                          <path d="M20 6v12a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2z" />
                          <path d="M10 16h6" />
                          <path d="M13 11m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
                          <path d="M4 8h3" />
                          <path d="M4 12h3" />
                          <path d="M4 16h3" />
                        </svg>
                      </span>
                      RENIEC / SUNAT
                    </a>
                    
                    <div class="dropdown-menu">
                      <a href="../mnt_ciudadano/" class="dropdown-item <?php echo (strpos($currentPage, '/mnt_ciudadano/') !== false) ? 'active' : ''; ?>">
                        <span class="dropdown-item-icon">
                          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-users">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M9 7m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0" />
                            <path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" />
                            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                            <path d="M21 21v-2a4 4 0 0 0 -3 -3.85" />
                          </svg>
                        </span>
                        Ciudadanos
                      </a>
                      <a href="../mnt_empresa/" class="dropdown-item <?php echo (strpos($currentPage, '/mnt_empresa/') !== false) ? 'active' : ''; ?>">
                        <span class="dropdown-item-icon">
                          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-building-Skyscraper">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M3 21l18 0" />
                            <path d="M5 21v-14l8 -4v18" />
                            <path d="M19 21v-10l-6 -4" />
                            <path d="M9 9l0 .01" />
                            <path d="M9 12l0 .01" />
                            <path d="M9 15l0 .01" />
                            <path d="M9 18l0 .01" />
                          </svg>
                        </span>
                        Empresas
                      </a>
                    </div> 
                  </div>
                  <div class="dropend">
                    <a class="dropdown-item dropdown-toggle" href="#sidebar-authentication" data-bs-toggle="dropdown"
                      data-bs-auto-close="outside" role="button" aria-expanded="false">
                      <span class="dropdown-item-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-Folders">
                          <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                          <path d="M9 4h3l2 2h5a2 2 0 0 1 2 2v7a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2v-9a2 2 0 0 1 2 -2" />
                          <path d="M17 17v2a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2v-9a2 2 0 0 1 2 -2h2" />
                        </svg>
                      </span>
                      Generales
                    </a>
                    
                    <div class="dropdown-menu">
                      <a href="../mnt_tupa/" class="dropdown-item <?php echo (strpos($currentPage, '/mnt_tupa/') !== false) ? 'active' : ''; ?>">
                        <span class="dropdown-item-icon">
                          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-books">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M5 4m0 1a1 1 0 0 1 1 -1h2a1 1 0 0 1 1 1v14a1 1 0 0 1 -1 1h-2a1 1 0 0 1 -1 -1z" />
                            <path d="M9 4m0 1a1 1 0 0 1 1 -1h2a1 1 0 0 1 1 1v14a1 1 0 0 1 -1 1h-2a1 1 0 0 1 -1 -1z" />
                            <path d="M5 8h4" />
                            <path d="M9 16h4" />
                            <path d="M13.803 4.56l2.184 -.53c.562 -.135 1.133 .19 1.282 .732l3.695 13.418a1.02 1.02 0 0 1 -.634 1.219l-.133 .041l-2.184 .53c-.562 .135 -1.133 -.19 -1.282 -.732l-3.695 -13.418a1.02 1.02 0 0 1 .634 -1.219l.133 -.041z" />
                            <path d="M14 9l4 -1" />
                            <path d="M16 16l3.923 -.98" />
                          </svg>
                        </span>
                        Documentos
                      </a>
                      <a href="../mnt_procedimiento/" class="dropdown-item <?php echo (strpos($currentPage, '/mnt_procedimiento/') !== false) ? 'active' : ''; ?>">
                        <span class="dropdown-item-icon">
                          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-list-check">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M3.5 5.5l1.5 1.5l2.5 -2.5" />
                            <path d="M3.5 11.5l1.5 1.5l2.5 -2.5" />
                            <path d="M3.5 17.5l1.5 1.5l2.5 -2.5" />
                            <path d="M11 6l9 0" />
                            <path d="M11 12l9 0" />
                            <path d="M11 18l9 0" />
                          </svg>
                        </span>
                        Procedimientos
                      </a>
                      <a href="../mnt_tasas/" class="dropdown-item <?php echo (strpos($currentPage, '/mnt_tasas/') !== false) ? 'active' : ''; ?>">
                        <span class="dropdown-item-icon">
                          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-coins">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M9 14c0 1.657 2.686 3 6 3s6 -1.343 6 -3s-2.686 -3 -6 -3s-6 1.343 -6 3z" />
                            <path d="M9 14v4c0 1.656 2.686 3 6 3s6 -1.344 6 -3v-4" />
                            <path d="M3 6c0 1.072 1.144 2.062 3 2.598s4.144 .536 6 0c1.856 -.536 3 -1.526 3 -2.598c0 -1.072 -1.144 -2.062 -3 -2.598s-4.144 -.536 -6 0c-1.856 .536 -3 1.526 -3 2.598z" />
                            <path d="M3 6v10c0 .888 .772 1.45 2 2" />
                            <path d="M3 11c0 .888 .772 1.45 2 2" />
                          </svg>
                        </span>
                        Tasas
                      </a>
                      <a href="../mnt_tributos/" class="dropdown-item <?php echo (strpos($currentPage, '/mnt_tributos/') !== false) ? 'active' : ''; ?>">
                        <span class="dropdown-item-icon">
                          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-receipt-tax">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M9 14l6 -6" />
                            <circle cx="9.5" cy="8.5" r=".5" fill="currentColor" />
                            <circle cx="14.5" cy="13.5" r=".5" fill="currentColor" />
                            <path d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16l-3 -2l-2 2l-2 -2l-2 2l-2 -2l-3 2" />
                          </svg>
                        </span>
                        Tributos
                      </a>
                    </div> 
                  </div>
                  <div class="dropend">
                    <a class="dropdown-item dropdown-toggle" href="#sidebar-authentication" data-bs-toggle="dropdown"
                      data-bs-auto-close="outside" role="button" aria-expanded="false">
                      <span class="dropdown-item-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-shield-lock">
                          <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                          <path d="M12 3a12 12 0 0 0 8.5 3a12 12 0 0 1 -8.5 15a12 12 0 0 1 -8.5 -15a12 12 0 0 0 8.5 -3" />
                          <path d="M12 11m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                          <path d="M12 12l0 2.5" />
                        </svg>
                      </span>
                      Permisos
                    </a>
                    
                    <div class="dropdown-menu">
                      <a href="../mnt_permisos/" class="dropdown-item <?php echo (strpos($currentPage, '/mnt_permisos/') !== false) ? 'active' : ''; ?>">
                        <span class="dropdown-item-icon">
                          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-user-cog">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" />
                            <path d="M6 21v-2a4 4 0 0 1 4 -4h2.5" />
                            <path d="M19.001 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
                            <path d="M19.001 15.5v1.5" />
                            <path d="M19.001 21v1.5" />
                            <path d="M22.032 17.25l-1.299 .75" />
                            <path d="M17.27 20l-1.3 .75" />
                            <path d="M15.97 17.25l1.3 .75" />
                            <path d="M20.733 20l1.3 .75" />
                          </svg>
                        </span>
                        Usuarios
                      </a>
                    </div> 
                  </div>
                  
                </div>
              </div>
            </div>
          </li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </div>
</header>
