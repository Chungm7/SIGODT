<!-- index.php -->
<?php
require_once("../../config/conexion.php");
if (isset($_SESSION["usua_id_SIGODT"])) {
?>
  <!DOCTYPE html>
  <html lang="es">

  <head>
    <?php require_once("../html/mainHead.php"); ?>
    <title>SIGODT::Gestión de Procedimientoss</title>
  </head>

  <body>
    <script src="../../public/tabler/js/demo-theme.min.js"></script>

    <div class="page">
      <div class="wrapper">
        <?php
        require_once("../html/mainProfile.php");
        require_once("../html/menu.php");
        ?>
        <style>
          .accion-icon {
            transition: transform 0.2s ease, color 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
          }

          .accion-icon:hover {
            color: #0d6efd !important;
            /* azul Bootstrap por defecto */
            transform: scale(1.2);
          }
        </style>
        <div class="page-wrapper">
          <div class="page-body">
            <div class="container-xl">

              <!-- Encabezado -->
              <div class="page-header">
                <div>
                  <ol class="breadcrumb breadcrumb-arrow text-muted mb-1">
                    <li class="breadcrumb-item"><a href="../inicio/">SIGODT</a></li>
                    <li class="breadcrumb-item"><a href="#">Mantenimientos</a></li>
                    <li class="breadcrumb-item active">Documento</li>
                  </ol>
                  <h2 class="page-title">Gestión del Documento</h2>
                </div>
              </div>

              <!-- Card principal -->
              <div class="card shadow-sm">
                <div class="card-body">
                  <!-- Título y descripción -->
                  <h5 class="card-title">Listado de Documentos</h5>
                  <p class="mg-b-30 text-muted">Administración de los Documentos registrados en el sistema.</p>

                  <!-- Botón para nuevo registro -->
                  <button class="btn btn-outline-primary mb-3" id="add_button" style="width: 100%; cursor: pointer;" onclick="nuevo()">
                    <i class="fa fa-plus-square me-2"></i> Nuevo Registro
                  </button>


                  <!-- Lista de TUPA -->
                  <div id="lista_tupa" class="row gy-3"></div>

                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <?php require_once("../html/footer.php"); ?>
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