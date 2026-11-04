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
    <link href="../../public/css/institutional-settings.css" rel="stylesheet" />
  </head>

  <body>
    <script src="../../public/tabler/js/demo-theme.min.js"></script>
    <div class="page">
      <div class="wrapper">
        <?php require_once("../html/mainProfile.php"); ?>
        <?php require_once("../html/menu.php"); ?>

        <div class="page-wrapper">
          <main id="institutional-settings" aria-labelledby="settings-title">
            <nav class="settings-breadcrumb" aria-label="Ruta de navegación">
              <a href="../inicio/">SIGODT</a>
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M9 6l6 6l-6 6" /></svg>
              <span aria-current="page">Configuración</span>
            </nav>
            <header class="settings-page-header">
              <h1 id="settings-title">CONFIGURACIÓN</h1>
              <p>Información del perfil de usuario y datos registrados en el sistema</p>
            </header>

            <!-- SIINDI visual organization; only existing SIGODT readonly capabilities. -->
            <div class="settings-card-panel">
              <aside class="settings-sidebar" aria-label="Configuración de cuenta">
                <nav>
                  <a href="../setting/" class="settings-nav-item active" aria-current="page">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" /></svg>
                    <span>Cuenta</span>
                  </a>
                </nav>
              </aside>

              <div class="settings-content-area">
                <section class="mpch-settings-card" aria-labelledby="settings-profile-title">
                  <header class="mpch-settings-header">
                    <div class="mpch-settings-icon-box" aria-hidden="true">
                      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" /></svg>
                    </div>
                    <div>
                      <h2 id="settings-profile-title" class="mpch-settings-title">Mi Perfil</h2>
                      <p class="mpch-settings-subtitle">Datos de identificación personal y nombres registrados en el sistema</p>
                    </div>
                  </header>
                  <div class="mpch-settings-body settings-field-grid">
                    <div class="form-field-group">
                      <label class="form-field-label" for="perfil_nombre_completo">Nombre Completo</label>
                      <div class="custom-input-box is-readonly">
                        <span class="input-addon-icon" aria-hidden="true"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" /></svg></span>
                        <input type="text" id="perfil_nombre_completo" class="custom-native-input" value="<?php echo htmlspecialchars($nombre_completo); ?>" readonly>
                      </div>
                    </div>
                    <div class="form-field-group">
                      <label class="form-field-label" for="perfil_dni">DNI</label>
                      <div class="custom-input-box is-readonly">
                        <span class="input-addon-icon" aria-hidden="true"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="3" /><circle cx="9" cy="10" r="2" /><path d="M15 8h2M15 12h2M7 16h10" /></svg></span>
                        <input type="text" id="perfil_dni" class="custom-native-input" value="<?php echo htmlspecialchars($dni); ?>" readonly>
                      </div>
                    </div>
                  </div>
                </section>

                <section class="mpch-settings-card" aria-labelledby="settings-contact-title">
                  <header class="mpch-settings-header">
                    <div class="mpch-settings-icon-box" aria-hidden="true">
                      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 4h4l2 5l-2.5 1.5a11 11 0 0 0 5 5l1.5 -2.5l5 2v4a2 2 0 0 1 -2 2a16 16 0 0 1 -15 -15a2 2 0 0 1 2 -2" /></svg>
                    </div>
                    <div>
                      <h2 id="settings-contact-title" class="mpch-settings-title">Contacto y Cargo Institucional</h2>
                      <p class="mpch-settings-subtitle">Número de teléfono, rol y módulo asignados en el sistema</p>
                    </div>
                  </header>
                  <div class="mpch-settings-body settings-field-grid">
                    <div class="form-field-group">
                      <label class="form-field-label" for="perfil_celular">Teléfono / Celular</label>
                      <div class="custom-input-box is-readonly">
                        <span class="input-addon-icon" aria-hidden="true">+51</span>
                        <input type="text" id="perfil_celular" class="custom-native-input" value="<?php echo htmlspecialchars($celular !== '' ? $celular : 'No registrado'); ?>" readonly aria-describedby="perfil_celular_feedback">
                      </div>
                      <div id="perfil_celular_feedback" class="field-feedback-text">
                        <?php if ($celular === ""): ?>
                          <span class="field-feedback-error">No cuenta con número de teléfono registrado</span>
                        <?php endif; ?>
                      </div>
                    </div>
                    <div class="form-field-group">
                      <label class="form-field-label" for="perfil_rol">Rol en el Sistema</label>
                      <div class="custom-input-box is-readonly">
                        <span class="input-addon-icon" aria-hidden="true"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 17.75l-6.172 3.245l1.179 -6.873l-5 -4.867l6.9 -1l3.086 -6.253l3.086 6.253l6.9 1l-5 4.867l1.179 6.873z" /></svg></span>
                        <input type="text" id="perfil_rol" class="custom-native-input" value="<?php echo htmlspecialchars($perfil); ?>" readonly>
                      </div>
                    </div>
                    <div class="form-field-group settings-field-wide">
                      <label class="form-field-label" for="perfil_modulo">Módulo / Sistema</label>
                      <div class="custom-input-box is-readonly">
                        <span class="input-addon-icon" aria-hidden="true"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16l-3 -2l-2 2l-2 -2l-2 2l-2 -2l-3 2M14 8h-2.5a1.5 1.5 0 0 0 0 3h1a1.5 1.5 0 0 1 0 3h-2.5m2 0v1.5m0 -9v1.5" /></svg></span>
                        <input type="text" id="perfil_modulo" class="custom-native-input" value="SIGODT - Órdenes de Derecho de Trámite" readonly>
                      </div>
                    </div>
                  </div>
                </section>

                <section class="mpch-settings-card" aria-labelledby="settings-email-title">
                  <header class="mpch-settings-header">
                    <div class="mpch-settings-icon-box" aria-hidden="true">
                      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2" /><path d="M3 7l9 6l9 -6" /></svg>
                    </div>
                    <div>
                      <h2 id="settings-email-title" class="mpch-settings-title">Correo Electrónico</h2>
                      <p class="mpch-settings-subtitle">Correo electrónico registrado en el sistema</p>
                    </div>
                  </header>
                  <div class="mpch-settings-body settings-field-grid">
                    <div class="form-field-group settings-field-wide">
                      <label class="form-field-label" for="perfil_correo">Correo Electrónico</label>
                      <div class="custom-input-box is-readonly">
                        <span class="input-addon-icon" aria-hidden="true">@</span>
                        <input type="text" id="perfil_correo" class="custom-native-input" value="<?php echo htmlspecialchars($correo !== '' ? $correo : 'No registrado'); ?>" readonly aria-describedby="perfil_correo_feedback">
                      </div>
                      <div id="perfil_correo_feedback" class="field-feedback-text">
                        <?php if ($correo === ""): ?>
                          <span class="field-feedback-error">No cuenta con correo electrónico registrado</span>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                </section>
              </div>
            </div>
          </main>
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
