<?php
require_once("../../config/conexion.php");

if (isset($_SESSION["usua_id_SIGODT"])) {
  $nombre_completo = $_SESSION["nombre_completo_SIGODT"] ?? "Usuario";
  $perfil = $_SESSION["rol_nombre_SIGODT"] ?? "Sin perfil";
  $dni = $_SESSION["usua_dni_SIGODT"] ?? "";
  $correo = trim($_SESSION["pers_emailm_SIGODT"] ?? "");
  $celular = trim($_SESSION["pers_celu01_SIGODT"] ?? "");
?>
  <!DOCTYPE html>
  <html lang="es">

  <head>
    <?php require_once("../html/mainHead.php"); ?>
    <title>SIGODT::Configuración de Cuenta</title>
  </head>

  <body>
    <script src="../../public/tabler/js/demo-theme.min.js"></script>
    <div class="page">
      <div class="wrapper">
        <?php require_once("../html/mainProfile.php"); ?>
        <?php require_once("../html/menu.php"); ?>

        <div class="page-wrapper">
          <div class="page-body">
            <div class="container-xl py-3">

              <!-- Encabezado con breadcrumbs y título estandarizado (Figma Nodo 103:240) -->
              <div class="mb-4">
                <ol class="breadcrumb breadcrumb-arrows mb-2" aria-label="breadcrumbs">
                  <li class="breadcrumb-item"><a href="../inicio/">SIGODT</a></li>
                  <li class="breadcrumb-item active" aria-current="page">Configuración</li>
                </ol>
                <h2 class="muni-settings-page-title">Ajustes de Cuenta</h2>
                <div class="text-secondary mt-1">Información del perfil de usuario y credenciales activas del sistema</div>
              </div>

              <!-- Panel de Configuración con estilo municipal (Figma Nodo 103:241) -->
              <div class="card muni-settings-panel">
                <div class="row g-0">

                  <!-- Navegación lateral de Ajustes (Figma Nodo 103:242) -->
                  <div class="col-12 col-md-3 muni-settings-sidebar">
                    <div class="muni-settings-nav-group">
                      <span class="muni-settings-nav-header">CONFIGURACIÓN</span>
                    </div>
                    <a href="../setting/" class="muni-settings-nav-item active">
                      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" />
                        <path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" />
                      </svg>
                      <span>Mi Perfil</span>
                    </a>
                  </div>

                  <!-- Contenido Mi Perfil con los campos propios de SIGODT -->
                  <div class="col-12 col-md-9">
                    <div class="muni-settings-body">

                      <h3 class="muni-settings-section-title">Mi Perfil</h3>

                      <!-- Bloque 1: Información Personal -->
                      <h4 class="muni-settings-subtitle mb-3">Información Personal</h4>
                      <div class="row g-3 mb-4">
                        <div class="col-12 col-md-6">
                          <label class="muni-field-label" for="perfil_nombre_completo">Nombre Completo</label>
                          <div class="muni-field-box is-readonly">
                            <span class="muni-field-prefix" aria-hidden="true">
                              <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" />
                                <path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" />
                              </svg>
                            </span>
                            <input type="text" id="perfil_nombre_completo" class="muni-field-input" value="<?php echo htmlspecialchars($nombre_completo); ?>" readonly>
                          </div>
                        </div>

                        <div class="col-12 col-md-6">
                          <label class="muni-field-label" for="perfil_dni">DNI</label>
                          <div class="muni-field-box is-readonly">
                            <span class="muni-field-prefix" aria-hidden="true">
                              <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M3 4m0 3a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v10a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3z" />
                                <path d="M9 10m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
                                <path d="M15 8l2 0" />
                                <path d="M15 12l2 0" />
                                <path d="M7 16l10 0" />
                              </svg>
                            </span>
                            <input type="text" id="perfil_dni" class="muni-field-input" value="<?php echo htmlspecialchars($dni); ?>" readonly>
                          </div>
                        </div>

                        <div class="col-12 col-md-6">
                          <label class="muni-field-label" for="perfil_rol">Rol en el Sistema</label>
                          <div class="muni-field-box is-readonly">
                            <span class="muni-field-prefix" aria-hidden="true">
                              <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M12 17.75l-6.172 3.245l1.179 -6.873l-5 -4.867l6.9 -1l3.086 -6.253l3.086 6.253l6.9 1l-5 4.867l1.179 6.873z" />
                              </svg>
                            </span>
                            <input type="text" id="perfil_rol" class="muni-field-input" value="<?php echo htmlspecialchars($perfil); ?>" readonly>
                          </div>
                        </div>

                        <div class="col-12 col-md-6">
                          <label class="muni-field-label" for="perfil_modulo">Módulo / Sistema</label>
                          <div class="muni-field-box is-readonly">
                            <span class="muni-field-prefix" aria-hidden="true">
                              <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16l-3 -2l-2 2l-2 -2l-2 2l-2 -2l-3 2" />
                                <path d="M14 8h-2.5a1.5 1.5 0 0 0 0 3h1a1.5 1.5 0 0 1 0 3h-2.5m2 0v1.5m0 -9v1.5" />
                              </svg>
                            </span>
                            <input type="text" id="perfil_modulo" class="muni-field-input" value="SIGODT - Órdenes de Derecho de Trámite" readonly>
                          </div>
                        </div>
                      </div>

                      <!-- Bloque 2: Datos de Contacto -->
                      <h4 class="muni-settings-subtitle mb-3">Datos de Contacto</h4>
                      <div class="row g-3">
                        <div class="col-12 col-md-6">
                          <label class="muni-field-label" for="perfil_correo">Correo Electrónico</label>
                          <div class="muni-field-box is-readonly">
                            <span class="muni-field-prefix" aria-hidden="true">
                              <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M12 12m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0" />
                                <path d="M16 12v1.5a2.5 2.5 0 0 0 5 0v-1.5a9 9 0 1 0 -5.5 8.28" />
                              </svg>
                            </span>
                            <input type="text" id="perfil_correo" class="muni-field-input" value="<?php echo htmlspecialchars($correo !== '' ? $correo : 'No registrado'); ?>" readonly style="text-transform: none;">
                          </div>
                          <?php if ($correo === ""): ?>
                            <div class="muni-field-hint-error">No cuenta con correo electrónico registrado</div>
                          <?php endif; ?>
                        </div>

                        <div class="col-12 col-md-6">
                          <label class="muni-field-label" for="perfil_celular">Teléfono / Celular</label>
                          <div class="muni-field-box is-readonly">
                            <span class="muni-field-prefix muni-field-prefix-text">+51</span>
                            <input type="text" id="perfil_celular" class="muni-field-input" value="<?php echo htmlspecialchars($celular !== '' ? $celular : 'No registrado'); ?>" readonly>
                          </div>
                          <?php if ($celular === ""): ?>
                            <div class="muni-field-hint-error">No cuenta con número de teléfono registrado</div>
                          <?php endif; ?>
                        </div>
                      </div>

                    </div>
                  </div>

                </div>
              </div>

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
