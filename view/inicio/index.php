<?php
/* Llamamos al archivo de conexion.php */
require_once("../../config/conexion.php");
if (isset($_SESSION["usua_id_SIGODT"])) {
  $nombre_completo = $_SESSION["nombre_completo_SIGODT"] ?? "Usuario";
  $perfil = $_SESSION["rol_nombre_SIGODT"] ?? "Sin perfil";
?>
  <!DOCTYPE html>
  <html lang="es">

  <head>
    <?php require_once("../html/mainHead.php"); ?>
  </head>

  <body>
    <script src="../../public/tabler/js/demo-theme.min.js?1692870487"></script>
    <div class="page">
      <div class="wrapper">
        <?php require_once("../html/mainProfile.php"); ?>
        <?php require_once("../html/menu.php"); ?>
        <div class="page-wrapper">
          <div class="page-body">
            <div class="container-xl py-3">

              <!-- Panel Hero de Bienvenida (Figma Nodo 49:665) -->
              <section class="muni-welcome-hero" aria-label="Bienvenida institucional">
                <div class="row align-items-center g-4 justify-content-between">
                  <div class="col-12 col-md">
                    <h2 class="muni-welcome-title">
                      <span class="d-block muni-welcome-greeting">¡Bienvenido al sistema,</span>
                      <span class="d-block muni-welcome-user"><?php echo htmlspecialchars(mb_strtoupper($nombre_completo)); ?>!</span>
                    </h2>
                    <p class="muni-welcome-subtitle">
                      Usted ha iniciado sesión con el rol: <strong class="muni-welcome-role"><?php echo htmlspecialchars(mb_strtoupper($perfil)); ?></strong>.
                    </p>
                  </div>
                  <div class="col-12 col-md-auto ms-md-auto">
                    <div class="muni-welcome-year-box">
                      <span class="muni-welcome-year-label">AÑO DE GESTIÓN</span>
                      <span class="muni-welcome-year-value"><?php echo date('Y'); ?></span>
                    </div>
                  </div>
                </div>
              </section>

              <!-- Grilla de Módulos Operativos (Figma Nodo 156:27) -->
              <section aria-label="Módulos principales del sistema">
                <div class="row row-cards g-4 mt-1">

                  <!-- Módulo 1: Registrar Órdenes de Giro -->
                  <div class="col-12 col-md-6">
                    <article class="muni-module-card">
                      <div class="muni-module-header">
                        <div class="d-flex align-items-center gap-3">
                          <span class="muni-module-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                              <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                              <path d="M14 3v4a1 1 0 0 0 1 1h4" />
                              <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" />
                              <path d="M12 11l0 6" />
                              <path d="M9 14l6 0" />
                            </svg>
                          </span>
                          <div class="muni-module-info">
                            <span class="muni-module-eyebrow">OPCIÓN</span>
                            <h3 class="muni-module-title">Registrar Órdenes de Giro</h3>
                          </div>
                        </div>
                        <span class="muni-status-pill status-active flex-shrink-0">Activo</span>
                      </div>
                      <div class="muni-module-body">
                        <p class="muni-module-desc">
                          Registra y gestiona las órdenes de derecho de trámite para ciudadanos y empresas según el catálogo TUPA vigente.
                        </p>
                      </div>
                      <div class="muni-module-footer">
                        <a href="../giros_ciudadano/" class="btn btn-muni-card-action">
                          <span>REGISTRAR NUEVO</span>
                          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M5 12l14 0" />
                            <path d="M13 18l6 -6" />
                            <path d="M13 6l6 6" />
                          </svg>
                        </a>
                      </div>
                    </article>
                  </div>

                  <?php if (Conectar::esAdministrador()) { ?>
                  <!-- Módulo 2: Auditoría de Órdenes de Giro (Solo Administrador) -->
                  <div class="col-12 col-md-6">
                    <article class="muni-module-card">
                      <div class="muni-module-header">
                        <div class="d-flex align-items-center gap-3">
                          <span class="muni-module-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-clipboard-check">
                              <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                              <path d="M9 5h-2a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-12a2 2 0 0 0 -2 -2h-2" />
                              <path d="M9 3m0 2a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v0a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2z" />
                              <path d="M9 14l2 2l4 -4" />
                            </svg>
                          </span>
                          <div class="muni-module-info">
                            <span class="muni-module-eyebrow">OPCIÓN</span>
                            <h3 class="muni-module-title">Auditoría de Órdenes de Giro</h3>
                          </div>
                        </div>
                        <span class="muni-status-pill status-active flex-shrink-0">Activo</span>
                      </div>
                      <div class="muni-module-body">
                        <p class="muni-module-desc">
                          Control, fiscalización y supervisión administrativa de procedimientos y órdenes de recaudación emitidas.
                        </p>
                      </div>
                      <div class="muni-module-footer">
                        <a href="../admin_giros_ciudadano/" class="btn btn-muni-card-action">
                          <span>IR A AUDITORÍA</span>
                          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M5 12l14 0" />
                            <path d="M13 18l6 -6" />
                            <path d="M13 6l6 6" />
                          </svg>
                        </a>
                      </div>
                    </article>
                  </div>
                  <?php } ?>

                </div>
              </section>

            </div>
          </div>
          <?php require_once("../html/footer.php"); ?>
        </div>
      </div>
    </div>
    <?php require_once("../html/mainjs.php"); ?>
  </body>

  </html>
<?php
} else {
  header("Location:" . Conectar::ruta() . "view/404/");
}
?>
