<?php
$currentPage = $_SERVER['PHP_SELF'] ?? '';
$isInicioActive = (strpos($currentPage, '/inicio/') !== false);
$isDashboardActive = (strpos($currentPage, '/dashboard/') !== false);
$isProcesosActive = (strpos($currentPage, '/giros_ciudadano/') !== false || strpos($currentPage, '/admin_giros_ciudadano/') !== false);
$isConsultasActive = (strpos($currentPage, '/consultar_documento/') !== false || strpos($currentPage, '/consultar_og/') !== false);
$isMntActive = (strpos($currentPage, '/mnt_') !== false);
?>
<header class="navbar-expand-md">
  <div class="collapse navbar-collapse" id="navbar-menu">
    <div class="navbar">
      <div class="container-xl">
        <ul class="navbar-nav">
          <li class="nav-item <?php echo $isInicioActive ? 'active' : ''; ?>">
            <a class="nav-link" href="../inicio/">
              <span
                class="nav-link-icon d-md-none d-lg-inline-block"><!-- Download SVG icon from http://tabler-icons.io/i/home -->
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
                class="nav-link-icon d-md-none d-lg-inline-block"><!-- Download SVG icon from http://tabler-icons.io/i/package -->
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
              <a href="../giros_ciudadano/" class="dropdown-item <?php echo $isProcesosActive ? 'active' : ''; ?>">
                Órdenes de Derecho de Trámite
              </a>
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
                Consulta por DNI/RUC/CEE/CPP
              </a>
              <a href="../consultar_og/" class="dropdown-item <?php echo (strpos($currentPage, '/consultar_og/') !== false) ? 'active' : ''; ?>">
                Consulta por Orden de Giro
              </a>
            </div>
          </li>
          <li class="nav-item dropdown <?php echo $isMntActive ? 'active' : ''; ?>">
            <a class="nav-link dropdown-toggle" href="#navbar-mantenimientos" data-bs-toggle="dropdown"
              data-bs-auto-close="outside" role="button" aria-expanded="false">
              <span
                class="nav-link-icon d-md-none d-lg-inline-block"><!-- Download SVG icon from http://tabler-icons.io/i/package -->
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
                      RENIEC / SUNAT
                    </a>
                    
                    <div class="dropdown-menu">
                      <a href="../mnt_ciudadano/" class="dropdown-item <?php echo (strpos($currentPage, '/mnt_ciudadano/') !== false) ? 'active' : ''; ?>">
                        Ciudadanos
                      </a>
                      <a href="../mnt_empresa/" class="dropdown-item <?php echo (strpos($currentPage, '/mnt_empresa/') !== false) ? 'active' : ''; ?>">
                        Empresas
                      </a>
                    </div> 
                  </div>
                  <div class="dropend">
                    <a class="dropdown-item dropdown-toggle" href="#sidebar-authentication" data-bs-toggle="dropdown"
                      data-bs-auto-close="outside" role="button" aria-expanded="false">
                      Generales
                    </a>
                    
                    <div class="dropdown-menu">
                      <a href="../mnt_tupa/" class="dropdown-item <?php echo (strpos($currentPage, '/mnt_tupa/') !== false) ? 'active' : ''; ?>">
                        Documentos
                      </a>
                      <a href="../mnt_procedimiento/" class="dropdown-item <?php echo (strpos($currentPage, '/mnt_procedimiento/') !== false) ? 'active' : ''; ?>">
                        Procedimientos
                      </a>
                      <a href="../mnt_tasas/" class="dropdown-item <?php echo (strpos($currentPage, '/mnt_tasas/') !== false) ? 'active' : ''; ?>">
                        Tasas
                      </a>
                      <a href="../mnt_tributos/" class="dropdown-item <?php echo (strpos($currentPage, '/mnt_tributos/') !== false) ? 'active' : ''; ?>">
                        Tributos
                      </a>
                    </div> 
                    <a class="dropdown-item dropdown-toggle" href="#sidebar-authentication" data-bs-toggle="dropdown"
                      data-bs-auto-close="outside" role="button" aria-expanded="false">
                      Permisos
                    </a>
                    
                    <div class="dropdown-menu">
                      <a href="../mnt_permisos/" class="dropdown-item <?php echo (strpos($currentPage, '/mnt_permisos/') !== false) ? 'active' : ''; ?>">
                        Usuarios
                      </a>
                    </div> 
                  </div>
                  
                </div>
              </div>
            </div>
          </li>
        </ul>
      </div>
    </div>
  </div>
</header>