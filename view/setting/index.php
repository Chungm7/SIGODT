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
                    <h2 class="page-title">Ajustes de Cuenta</h2>
                    <div class="text-secondary mt-2">Información del perfil de usuario y credenciales activas del sistema</div>
                  </div>
                </div>
              </div>

              <div class="card shadow-sm">
                <div class="row g-0">
                  <div class="col-12 col-md-3 border-end">
                    <div class="card-body">
                      <h4 class="subheader">Opciones</h4>
                      <div class="list-group list-group-transparent">
                        <a href="../setting/" class="list-group-item list-group-item-action d-flex align-items-center active">
                          <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-user me-2" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" />
                            <path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" />
                          </svg>
                          Mi Perfil
                        </a>
                      </div>
                    </div>
                  </div>

                  <div class="col-12 col-md-9 d-flex flex-column">
                    <div class="card-body">
                      <div class="d-flex align-items-center mb-4">
                        <span class="avatar avatar-xl rounded me-3" style="background-image: url(../../public/img/perfil.jpeg)"></span>
                        <div>
                          <h2 class="mb-1"><?php echo htmlspecialchars($nombre_completo); ?></h2>
                          <div class="text-secondary"><?php echo htmlspecialchars($perfil); ?> &bull; DNI: <?php echo htmlspecialchars($dni); ?></div>
                        </div>
                      </div>

                      <h3 class="card-title mt-4">Información Personal</h3>
                      <div class="row g-3">
                        <div class="col-md-6">
                          <label class="form-label">Nombre Completo</label>
                          <input type="text" class="form-control" value="<?php echo htmlspecialchars($nombre_completo); ?>" readonly>
                        </div>
                        <div class="col-md-6">
                          <label class="form-label">DNI</label>
                          <input type="text" class="form-control" value="<?php echo htmlspecialchars($dni); ?>" readonly>
                        </div>
                        <div class="col-md-6">
                          <label class="form-label">Rol en el Sistema</label>
                          <input type="text" class="form-control" value="<?php echo htmlspecialchars($perfil); ?>" readonly>
                        </div>
                        <div class="col-md-6">
                          <label class="form-label">Módulo / Sistema</label>
                          <input type="text" class="form-control" value="SIGODT - Órdenes de Derecho de Trámite" readonly>
                        </div>
                      </div>

                      <h3 class="card-title mt-4">Datos de Contacto</h3>
                      <div class="row g-3">
                        <div class="col-md-6">
                          <label class="form-label">Correo Electrónico</label>
                          <input type="text" class="form-control" value="<?php echo htmlspecialchars($correo ?: 'No registrado'); ?>" readonly>
                        </div>
                        <div class="col-md-6">
                          <label class="form-label">Teléfono / Celular</label>
                          <input type="text" class="form-control" value="<?php echo htmlspecialchars($celular ?: 'No registrado'); ?>" readonly>
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