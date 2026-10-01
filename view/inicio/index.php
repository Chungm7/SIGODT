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
            <div class="container-xl">
              <div class="row">
                <div class="col-12">
                  <div class="card">
                    <div class="card-header">
                      <h3 class="card-title">Bienvenido al Sistema de Gestión de Órdenes de Derecho de Trámite de la Municipalidad Provincial de Chiclayo, <?php echo htmlspecialchars($nombre_completo); ?>!</h3>
                    </div>
                    <div class="card-body">
                      <p class="text-muted">
                        Usted ha iniciado sesión como <strong><?php echo htmlspecialchars($perfil); ?></strong>.
                        Este sistema le permite gestionar y consultar las órdenes de giro creadas por las distintas unidades orgánicas
                        de la Municipalidad Provincial de Chiclayo.
                      </p>
                      <div class="row">
                        <div class="col-md-4">
                          <div class="card text-center">
                            <div class="card-body">
                              <h4 class="card-title">Registrar Órdenes de Giro</h4>
                              <p class="text-muted">Registra y gestiona las órdenes de giro.</p>
                              <a href="../giros_ciudadano" class="btn btn-warning">Registrar Nuevo</a>
                            </div>
                          </div>
                        </div>
                        <div class="col-md-4">
                          <div class="card text-center">
                            <div class="card-body">
                              <h4 class="card-title">Consulta por Documento</h4>
                              <p class="text-muted">Consulta por DNI, RUC, CEE o CPP.</p>
                              <a href="../consultar_documento" class="btn btn-primary">Ir a Consultas</a>
                            </div>
                          </div>
                        </div>
                        <div class="col-md-4">
                          <div class="card text-center">
                            <div class="card-body">
                              <h4 class="card-title">Consulta por Orden de Giro</h4>
                              <p class="text-muted">Valide los datos de una orden de giro existente.</p>
                              <a href="../consultar_og/" class="btn btn-success">Validar Orden</a>
                            </div>
                          </div>
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