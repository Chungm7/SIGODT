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
        <div class="page-wrapper">
          <div class="page-body">
            <div class="container-xl">

              <!-- Encabezado -->
              <div class="page-header">
                <div>
                  <ol class="breadcrumb breadcrumb-arrow text-muted mb-1">
                    <li class="breadcrumb-item"><a href="../inicio/">SIGODT</a></li>
                    <li class="breadcrumb-item"><a href="#">Mantenimientos</a></li>
                    <li class="breadcrumb-item active">Permisos</li>
                  </ol>
                  <h2 class="page-title">Gestión del Permisos</h2>
                </div>
              </div>
              <!-- Card principal -->
              <div class="card shadow-sm">
                <div class="card-body">
                  <!-- Título y descripción -->
                  <div class="form-layout mb-3">
                    <div class="row">
                      <div class="col-lg-7">
                        <div class="form-group">
                          <label class="form-label">Área: <span class="text-danger">*</span></label>
                          <select class="form-select select2" name="depe_select" id="depe_select" data-placeholder="Seleccione">
                            <option label="Seleccione"></option>
                          </select>
                        </div>
                      </div>
                      <div class="col-lg-4">
                        <label class="form-label">&nbsp;</label>
                        <button class="btn btn-outline-primary w-100" id="add_button" onclick="nuevo()">
                          <i class="fa fa-plus-square me-2"></i> Agregar Usuarios
                        </button>
                      </div>
                    </div>
                  </div>

                  <!-- Tabla de datos -->
                  <div class="table-responsive">
                    <table id="detalle_data" class="table table-striped table-bordered" style="width:100%">
                      <thead>
                        <tr>
                          <th class="wd-15p text-center">DNI</th>
                          <th class="wd-15p text-center">Nombre</th>
                          <th class="wd-15p text-center">Ape. Pat</th>
                          <th class="wd-10p text-center">Ape. Mat</th>
                          <th class="wd-5p text-center">Acciones</th>
                        </tr>
                      </thead>
                      <tbody>
                        <!-- Aquí van los datos dinámicos -->
                      </tbody>
                    </table>
                  </div>

                </div>
              </div>



            </div>
          </div>

          <?php require_once("../html/footer.php"); ?>
        </div>
      </div>
    </div>

    <?php require_once("modalmantenimiento.php"); ?>
    <?php require_once("modalusu.php"); ?>




    <?php require_once("../html/mainjs.php"); ?>
    <script src="adminmntusuarea.js"></script>
  </body>

  </html>
<?php
} else {
  header("Location:" . Conectar::ruta() . "view/404/");
}
?>