<?php
/* Llamamos al archivo de conexion.php */
require_once("../../config/conexion.php");
if (isset($_SESSION["usua_id_SIGODT"])) {
?>
  <!DOCTYPE html>
  <html lang="es">

  <head>
    <?php require_once("../html/mainHead.php"); ?>
    <link rel="stylesheet" href="css/style.css">


    <title>MPCH::Detalle proced ciudadanos</title>
  </head>

  <body>

    <?php require_once("../html/menu.php"); ?>

    <?php require_once("../html/mainProfile.php"); ?>

    <div class="br-mainpanel">
      <div class="br-pageheader pd-y-15 pd-l-20">
        <nav class="breadcrumb pd-0 mg-0 tx-12">
          <a class="breadcrumb-item" href="#">Detalle ciudadano</a>
        </nav>
      </div>
      <div class="pd-x-20 pd-sm-x-30 pd-t-20 pd-sm-t-30">
        <h4 class="tx-gray-800 mg-b-5">Detalle ciudadano</h4>
        <p class="mg-b-0">Mantenimiento</p>
      </div>
      <div class="br-pagebody" style="font-size: 14px;">
        <div class="br-section-wrapper">
          <div class="row">
            <div class="col-lg-8">
              <h6 class="tx-gray-800 tx-uppercase tx-bold tx-14 mg-b-5">Detalle Ordenes de GIRO</h6>
              <p class="mg-b-30 tx-gray-600" id="fecha">Listado de Ordenes del día: </p>
            </div>
            <div class="col-lg-4" style="text-align: right;">
              <div class="btn-group" role="group" aria-label="Recargar tabla">
                <button type="button" class="btn btn-outline-primary pd-x-25" style="border-radius: 20px;" onclick="recargarTabla()" onmouseover="showText()" onmouseout="hideText()">
                  <i class="fa fa-refresh"></i> <!-- Se muestra inicialmente -->
                </button>
              </div>
            </div>
          </div>
          <div class="form-layout">
            <div class="row">
              <div class="col-lg-12">
                <div class="row">
                  <div class="col-lg-2">
                    <div class="form-group">
                      <label class="form-control-label">TUPA: <span class="tx-danger">*</span></label>
                      <select class="form-control select2" name="tupa_id" id="tupa_id" data-placeholder="Seleccione">
                        <option value='' label="Seleccione"></option>
                      </select>
                    </div>
                  </div>
                  <div class="col-lg-4">
                    <div class="form-group">
                      <label class="form-control-label">Area: <span class="tx-danger">*</span></label>
                      <select class="form-control select2" name="area_id" id="area_id" data-placeholder="Seleccione">
                        <option value='' label="Seleccione"></option>
                      </select>
                    </div>
                  </div>
                  <div class="col-lg-6">
                    <div class="form-group">
                      <label class="form-control-label">Procedimiento: <span class="tx-danger">*</span></label>
                      <select class="form-control select2" name="proced_id" id="proced_id" data-placeholder="Seleccione">
                        <option value='' label="Seleccione"></option>
                      </select>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="cont  d-flex justify-content-center" style="margin-top:10px;">
              <div class="row">
                <div class="col-lg-6">
                  <div class="form-group">
                    <label class="form-control-label">&nbsp;</label>
                    <button class="btn btn-outline-secondary form-control" style="width: 400px" id="print_button" onclick="imprimirInformacion()">
                      <i class="fa fa-print mg-r-10"></i> Imprimir Requisitos
                    </button>
                  </div>
                </div>
                <div class="col-lg-6">
                  <div class="form-group">
                    <label class="form-control-label">&nbsp;</label>
                    <button class="btn btn-outline-primary form-control" style="width: 400px" id="add_button" onclick="nuevo()">
                      <i class="fa fa-plus-square mg-r-10"></i> Abrir Procedimiento
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="table-wrapper">
            <table id="detalle_data" class="table display responsive">
              <thead>
                <tr>
                  <th class="wd-5p" style="text-align: center; ">Codigo</th>
                  <th class="wd-10p" style="text-align: center; ">DNI</th>
                  <th class="wd-20p" style="text-align: center; ">Ciudadano</th>
                  <th class="wd-20p" style="text-align: center; ">Fecha apertura</th>
                  <th class="wd-5p" style="text-align: center; ">Estado</th>
                  <th class="wd-50p" style="text-align: center; ">Progreso</th>
                  <th class="wd-10p" style="text-align: center; ">Anular?</th>
                  <th class="wd-10p" style="text-align: center; ">Editar</th>
                  <th class="wd-10p" style="text-align: center; ">Eliminar</th>
                  <th class="wd-10p" style="text-align: center; ">Tasas</th>
                </tr>
              </thead>
              <tbody>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    <?php require_once("modaleditcomentario.php");  ?>
    <?php require_once("modaltasas.php");  ?>
    <?php require_once("modalmantenimiento.php");  ?>
  

    <?php require_once("../html/mainjs.php"); ?>
    <script type="text/javascript" src="admindetalleciudadano.js"></script>
    <script>
      function showText() {
        var button = document.querySelector('.btn-group button');;
        button.style.transform = 'scale(1.1)'; // Aumenta un poco el tamaño
        button.style.transition = 'transform 0.5s'; // Ajusta la duración de la transición
      }

      function hideText() {
        var button = document.querySelector('.btn-group button');
        button.innerHTML = '<i class="fa fa-refresh"></i>'; // Vuelve al icono solo
        button.style.transform = 'scale(1)'; // Restaura el tamaño original
      }
    </script>

  </body>

  </html>
<?php
} else {
  /* Si no a iniciado sesion se redireccionada a la ventana principal */
  header("Location:" . Conectar::ruta() . "views/404/");
}
?>