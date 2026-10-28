<?php
require_once("../../config/conexion.php");

if (isset($_SESSION["usua_id_SIGODT"])) {
  $nombre_completo = $_SESSION["nombre_completo_SIGODT"] ?? "Usuario";
  $perfil = $_SESSION["rol_nombre_SIGODT"] ?? "Sin perfil";
  $dni = $_SESSION["usua_dni_SIGODT"] ?? "";
  $correo = $_SESSION["pers_emailm_SIGODT"] ?? "";
  $celular = $_SESSION["pers_celu01_SIGODT"] ?? "";
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
            <div class="container-xl">

              <!-- Encabezado con breadcrumbs -->
              <div class="page-header d-print-none mb-3">
                <div class="row align-items-center">
                  <div class="col">
                    <ol class="breadcrumb breadcrumb-arrows mb-3" aria-label="breadcrumbs">
                      <li class="breadcrumb-item"><a href="../inicio/">SIGODT</a></li>
                      <li class="breadcrumb-item active" aria-current="page">Configuración</li>
                    </ol>
                    <h2 class="page-title">Ajustes de Cuenta y Seguridad</h2>
                    <div class="text-secondary mt-2">Información del perfil institucional y actualización de credenciales de acceso</div>
                  </div>
                </div>
              </div>

              <!-- Tarjeta de Perfil Institucional con Franja Bicolor (Figma Nodo 64:687) -->
              <div class="card shadow-sm muni-stripe-top mb-4">
                <div class="row g-0">
                  <div class="col-12 col-md-3 border-end">
                    <div class="card-body">
                      <h4 class="subheader text-uppercase fw-bold" style="color: #64748B;">Configuración</h4>
                      <div class="list-group list-group-transparent">
                        <a href="../setting/" class="list-group-item list-group-item-action d-flex align-items-center active fw-semibold">
                          <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-user me-2" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" />
                            <path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" />
                          </svg>
                          Mi Perfil y Seguridad
                        </a>
                      </div>
                    </div>
                  </div>

                  <div class="col-12 col-md-9 d-flex flex-column">
                    <div class="card-body">
                      <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 pb-3 border-bottom">
                        <div class="d-flex align-items-center">
                          <span class="avatar avatar-xl rounded-circle me-3" style="background-image: url(../../public/img/perfil.jpeg); border: 2px solid #9CCAEC;"></span>
                          <div>
                            <h2 class="mb-1 fw-bold" style="color: #0F172A;"><?php echo htmlspecialchars($nombre_completo); ?></h2>
                            <div class="text-secondary d-flex align-items-center gap-2 flex-wrap">
                              <span class="muni-status-pill status-active"><?php echo htmlspecialchars($perfil); ?></span>
                              <span>DNI: <strong><?php echo htmlspecialchars($dni); ?></strong></span>
                            </div>
                          </div>
                        </div>
                        <img src="../../public/img/logo_mpch.png" alt="MPCH" height="52" class="d-none d-sm-block">
                      </div>

                      <h3 class="card-title mb-3">Información Personal</h3>
                      <div class="row g-3">
                        <div class="col-md-6">
                          <label class="form-label fw-medium">Nombre Completo</label>
                          <div class="input-group">
                            <span class="input-group-text" style="background-color: #F1F5F9;">
                              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" /><path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" /></svg>
                            </span>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($nombre_completo); ?>" readonly>
                          </div>
                        </div>
                        <div class="col-md-6">
                          <label class="form-label fw-medium">DNI</label>
                          <div class="input-group">
                            <span class="input-group-text" style="background-color: #F1F5F9;">
                              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 4m0 3a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v10a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3z" /><path d="M9 10m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M15 8l2 0" /><path d="M15 12l2 0" /><path d="M7 16l10 0" /></svg>
                            </span>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($dni); ?>" readonly>
                          </div>
                        </div>
                        <div class="col-md-6">
                          <label class="form-label fw-medium">Rol en el Sistema</label>
                          <div class="input-group">
                            <span class="input-group-text" style="background-color: #F1F5F9;">
                              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 3a12 12 0 0 0 8.5 3a12 12 0 0 1 -8.5 15a12 12 0 0 1 -8.5 -15a12 12 0 0 0 8.5 -3" /></svg>
                            </span>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($perfil); ?>" readonly>
                          </div>
                        </div>
                        <div class="col-md-6">
                          <label class="form-label fw-medium">Módulo / Sistema</label>
                          <div class="input-group">
                            <span class="input-group-text" style="background-color: #F1F5F9;">
                              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 21l18 0" /><path d="M5 21v-14l8 -4v18" /><path d="M19 21v-10l-6 -4" /></svg>
                            </span>
                            <input type="text" class="form-control" value="SIGODT: Órdenes de Derecho de Trámite" readonly>
                          </div>
                        </div>
                      </div>

                      <h3 class="card-title mt-4 mb-3">Datos de Contacto</h3>
                      <div class="row g-3">
                        <div class="col-md-6">
                          <label class="form-label fw-medium">Correo Electrónico</label>
                          <div class="input-group">
                            <span class="input-group-text" style="background-color: #F1F5F9;">
                              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 7a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v10a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-10z" /><path d="M3 7l9 6l9 -6" /></svg>
                            </span>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($correo ?: 'No registrado'); ?>" readonly>
                          </div>
                        </div>
                        <div class="col-md-6">
                          <label class="form-label fw-medium">Teléfono / Celular</label>
                          <div class="input-group">
                            <span class="input-group-text" style="background-color: #F1F5F9;">
                              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 4h4l2 5l-2.5 1.5a11 11 0 0 0 5 5l1.5 -2.5l5 2v4a2 2 0 0 1 -2 2a16 16 0 0 1 -15 -15a2 2 0 0 1 2 -2" /></svg>
                            </span>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($celular ?: 'No registrado'); ?>" readonly>
                          </div>
                        </div>
                      </div>

                      <!-- Sección de Cambio de Contraseña Segura (Figma Page 2 Nodo 64:687) -->
                      <div class="mt-4 pt-4 border-top">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                          <div>
                            <h3 class="card-title mb-1">Cambio de Contraseña Segura</h3>
                            <p class="text-secondary small mb-0">
                              La gestión de credenciales está centralizada en el Sistema de Seguridad Institucional (SIGUR) de la Municipalidad Provincial de Chiclayo.
                            </p>
                          </div>
                          <a href="https://www.munichiclayo.gob.pe/sisSeguridad/view/USURecuperacionContra/index.php?sistema=siagth" target="_blank" rel="noopener" class="btn btn-muni-security">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                              <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                              <path d="M5 13a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v6a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2v-6z" />
                              <path d="M11 16a1 1 0 1 0 2 0a1 1 0 0 0 -2 0" />
                              <path d="M8 11v-4a4 4 0 1 1 8 0v4" />
                            </svg>
                            <span>Actualizar Contraseña en SIGUR</span>
                          </a>
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
