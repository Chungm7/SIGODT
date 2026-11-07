<!-- index.php -->
<?php
require_once("../../config/conexion.php");
if (Conectar::esAdministrador()) {
?>
  <!DOCTYPE html>
  <html lang="es">

  <head>
    <?php require_once("../html/mainHead.php"); ?>
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

              <!-- Breadcrumb Contextual -->
              <div class="mpch-subsys-breadcrumb">
                <a href="../inicio/" class="mpch-bc-sys text-decoration-none" title="Ir al Inicio">SIGODT</a>
                <span class="mpch-bc-sep">
                  <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 6l6 6l-6 6" /></svg>
                </span>
                <span class="mpch-bc-sec">Mantenimientos</span>
                <span class="mpch-bc-sep">
                  <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 6l6 6l-6 6" /></svg>
                </span>
                <span class="mpch-bc-cur">Documentos</span>
              </div>

              <!-- Encabezado de la Página (Título y Descripción) -->
              <div class="page-header page-header-context d-print-none mb-3">
                <div class="row align-items-center">
                  <div class="col">
                    <h2 class="page-title text-uppercase">Gestión de Documentos Normativos</h2>
                    <div class="text-secondary mt-1" style="font-size: 14px;">Administración de textos únicos de procedimientos (TUPA) y servicios no exclusivos (TUSNE)</div>
                  </div>
                  <div class="col-auto ms-auto d-print-none">
                    <button class="btn btn-muni-action" id="add_button" onclick="nuevo()">
                      <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-plus" width="20" height="20" viewBox="0 0 24 24" stroke-width="2.25" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <line x1="12" y1="5" x2="12" y2="19" />
                        <line x1="5" y1="12" x2="19" y2="12" />
                      </svg>
                      <span>NUEVO DOCUMENTO</span>
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
  exit();
}
?>