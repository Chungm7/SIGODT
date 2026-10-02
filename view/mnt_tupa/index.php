<!-- index.php -->
<?php
require_once("../../config/conexion.php");
if (isset($_SESSION["usua_id_SIGODT"])) {
?>
  <!DOCTYPE html>
  <html lang="es">

  <head>
    <?php require_once("../html/mainHead.php"); ?>
    <title>SIGODT::Gestión de Documentos (TUPA / TUSNE)</title>
  </head>

  <body>
    <script src="../../public/tabler/js/demo-theme.min.js"></script>

    <div class="page">
      <div class="wrapper">
        <?php
        require_once("../html/mainProfile.php");
        require_once("../html/menu.php");
        ?>
        <div class="page-wrapper">
          <div class="page-body">
            <div class="container-xl">

              <!-- Encabezado con breadcrumbs y botón de acción -->
              <div class="page-header d-print-none mb-3">
                <div class="row align-items-center">
                  <div class="col">
                    <ol class="breadcrumb breadcrumb-arrows mb-1" aria-label="breadcrumbs">
                      <li class="breadcrumb-item"><a href="../inicio/">SIGODT</a></li>
                      <li class="breadcrumb-item"><a href="#">Mantenimientos</a></li>
                      <li class="breadcrumb-item active" aria-current="page">Documentos</li>
                    </ol>
                    <h2 class="page-title">Gestión de Documentos Normativos</h2>
                    <div class="text-muted mt-1">Administración de textos únicos de procedimientos (TUPA) y servicios no exclusivos (TUSNE)</div>
                  </div>
                  <div class="col-auto ms-auto d-print-none">
                    <button class="btn btn-primary d-inline-flex align-items-center" id="add_button" onclick="nuevo()">
                      <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-plus" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <line x1="12" y1="5" x2="12" y2="19" />
                        <line x1="5" y1="12" x2="19" y2="12" />
                      </svg>
                      <span>Nuevo Documento</span>
                    </button>
                  </div>
                </div>
              </div>

              <!-- Contenedor dinámico de tarjetas de documentos -->
              <div id="lista_tupa" class="row row-cards">
                <!-- Se renderiza mediante cargarListaTupa() -->
              </div>

            </div>
          </div>
        </div>

        <?php require_once("../html/footer.php"); ?>
      </div>
    </div>

    <?php require_once("modalmantenimiento.php"); ?>
    <?php require_once("../html/mainjs.php"); ?>
    <script src="main.js"></script>
  </body>

  </html>
<?php
} else {
  header("Location:" . Conectar::ruta() . "view/404/");
}
?>