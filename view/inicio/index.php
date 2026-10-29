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
    <title>SIGODT::Bienvenido</title>
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
                <div class="row align-items-center g-4">
                  <div class="col-12 col-md-9">
                    <h2 class="muni-welcome-title">
                      ¡Bienvenido al Sistema, <span class="muni-welcome-user" style="color: #93C5FD;"><?php echo htmlspecialchars($nombre_completo); ?></span>!
                    </h2>
                      <br>
                    <p class="muni-welcome-subtitle">
                        Usted ha iniciado sesión con el rol: <strong class="muni-welcome-role" style="color: #FBC02D; font-weight: 600;"><?php echo htmlspecialchars($perfil); ?></strong>.
                    </p>
                  </div>
                  <div class="col-12 col-md-3">
                    <div class="muni-welcome-year-box">
                      <span class="muni-welcome-year-label">AÑO DE GESTIÓN</span>
                      <span class="muni-welcome-year-value"><?php echo date('Y'); ?></span>
                    </div>
                  </div>
                </div>
              </section>

              <!-- Grilla de Módulos Operativos (Figma Nodo 49:982) -->
              <section aria-label="Módulos principales del sistema">
                <div class="row row-cards g-4 mt-1">

                  <!-- Módulo 1: Registrar Órdenes de Giro -->
                  <div class="col-12 col-md-6 col-lg-4">
                    <article class="muni-module-card">
                      <div>
                        <div class="d-flex align-items-center justify-content-between">
                          <span class="muni-module-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                              <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                              <path d="M14 3v4a1 1 0 0 0 1 1h4" />
                              <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" />
                              <path d="M12 11l0 6" />
                              <path d="M9 14l6 0" />
                            </svg>
                          </span>
                        </div>
                        <h3 class="muni-module-title">Registrar Órdenes de Giro</h3>
                        <p class="muni-module-desc">
                          Registra y gestiona las órdenes de derecho de trámite para ciudadanos y empresas según el catálogo TUPA vigente.
                        </p>
                      </div>
                      <div class="muni-module-footer">
                        <a href="../giros_ciudadano" class="btn btn-muni-action w-100">
                          <span>REGISTRAR NUEVO</span>
                          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M5 12l14 0" />
                            <path d="M13 18l6 -6" />
                            <path d="M13 6l6 6" />
                          </svg>
                        </a>
                      </div>
                    </article>
                  </div>

                  <!-- Módulo 2: Consulta por Documento -->
                  <div class="col-12 col-md-6 col-lg-4">
                    <article class="muni-module-card">
                      <div>
                        <div class="d-flex align-items-center justify-content-between">
                          <span class="muni-module-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                              <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                              <path d="M3 4m0 3a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v10a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3z" />
                              <path d="M9 10m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
                              <path d="M15 8l2 0" />
                              <path d="M15 12l2 0" />
                              <path d="M7 16l10 0" />
                            </svg>
                          </span>
                        </div>
                        <h3 class="muni-module-title">Consulta por Documento</h3>
                        <p class="muni-module-desc">
                          Realiza búsquedas históricas de órdenes de giro emitidas filtrando por número de DNI, RUC, CEE o CPP.
                        </p>
                      </div>
                      <div class="muni-module-footer">
                        <a href="../consultar_documento" class="btn btn-muni-action w-100">
                          <span>IR A CONSULTAS</span>
                          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M5 12l14 0" />
                            <path d="M13 18l6 -6" />
                            <path d="M13 6l6 6" />
                          </svg>
                        </a>
                      </div>
                    </article>
                  </div>

                  <!-- Módulo 3: Consulta por Orden de Giro -->
                  <div class="col-12 col-md-6 col-lg-4">
                    <article class="muni-module-card">
                      <div>
                        <div class="d-flex align-items-center justify-content-between">
                          <span class="muni-module-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                              <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                              <path d="M14 3v4a1 1 0 0 0 1 1h4" />
                              <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" />
                              <path d="M9 15l2 2l4 -4" />
                            </svg>
                          </span>
                        </div>
                        <h3 class="muni-module-title">Consulta por Orden de Giro</h3>
                        <p class="muni-module-desc">
                          Valida los datos, estado de pago y comprobante PDF de una orden de giro existente mediante su número y año.
                        </p>
                      </div>
                      <div class="muni-module-footer">
                        <a href="../consultar_og/" class="btn btn-muni-action w-100">
                          <span>VALIDAR ORDEN</span>
                          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M5 12l14 0" />
                            <path d="M13 18l6 -6" />
                            <path d="M13 6l6 6" />
                          </svg>
                        </a>
                      </div>
                    </article>
                  </div>

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
