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
    <title>MPCH::Ordenes de Giro</title>
  </head>

  <body>

    <?php require_once("../html/menu.php"); ?>

    <?php require_once("../html/mainProfile.php"); ?>

    <div class="br-mainpanel">
    <input type="hidden" name="usua_dni_SIGODT" id="usua_dni_SIGODT" value="<?php echo $_SESSION["usua_dni_SIGODT"]; ?>" />
    <input type="hidden" name="usu_depe_id_SIGODT" id="usu_depe_id_SIGODT" value="<?php echo $_SESSION["usu_depe_id_SIGODT"]; ?>" />
      <input type="hidden" name="tupa_id" id="tupa_id" />
      <div class="pd-x-20  pd-t-20 pd-sm-t-30" style=" padding-left:45px; padding-right: 45px;">

        <div class="row" style="background-color: white; padding: 20px; border-radius: 10px; ">
          <div class="col-lg-4 d-flex justify-content-center align-items-center" style="margin-bottom: 10px;">
            <div class="card-client" style="width: 250px;">
              <div class="user-picture">
                <svg viewBox="0 0 448 512" xmlns="http://www.w3.org/2000/svg">
                  <path d="M224 256c70.7 0 128-57.31 128-128s-57.3-128-128-128C153.3 0 96 57.31 96 128S153.3 256 224 256zM274.7 304H173.3C77.61 304 0 381.6 0 477.3c0 19.14 15.52 34.67 34.66 34.67h378.7C432.5 512 448 496.5 448 477.3C448 381.6 370.4 304 274.7 304z"></path>
                </svg>
              </div>
              <p class="name-client"> <?php echo $_SESSION["nombre_completo_SIGODT"] ?>
                <span><?php echo $_SESSION["rol_nombre_SIGODT"]; ?>
                </span>
              </p>
              <style>
                .Data-Girador {
                  margin-top: 10px;
                  /* Ajusta el margen superior según sea necesario */
                }

                .Data-Girador div span {
                  display: inline;
                  margin-left:5px ;
                }
              </style>
              <div class="Data-Girador">
                <div>

                </div>

              </div>
            </div>
          </div>
          <div class="col-lg-6">
            <h4 class="tx-gray-800 mg-b-5">ORDENES DE GIROS</h4>
            <p class="mg-b-10 tx-gray-600" id="fecha">Listado de Ordenes del día: </p>

            <div class="form-group">
              <label class="form-control-label">Area: <span class="tx-danger">*</span></label>
              <select class="form-control select2" name="area_id" id="area_id" data-placeholder="Seleccione">
                <option value='' label="Seleccione"></option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-control-label">Procedimiento: <span class="tx-danger">*</span></label>
              <select class="form-control select2" name="proced_id" id="proced_id" data-placeholder="Seleccione">
                <option value='' label="Seleccione"></option>
              </select>
            </div>
          </div>
          <div class="col-lg-2 text-center">
            <div class="btn-group" role="group" aria-label="Recargar tabla">
              <button type="button" class="btn btn-outline-primary pd-x-25" style="border-radius: 20px; cursor: pointer;" onclick="recargarTabla()" onmouseover="showText()" onmouseout="hideText()">
                <i class="fa fa-refresh"></i> <!-- Se muestra inicialmente -->
              </button>
            </div>
            <div class="form-group" style="padding-top: 20px;">
              <label class="form-control-label">&nbsp;</label>
              <button class="btn btn-outline-secondary form-control" id="print_button" onclick="imprimirInformacion()" style="cursor: pointer;">
                <i class="fa fa-print mg-r-10"></i> REQUISITOS
              </button>
            </div>
            <div class="form-group">
              <label class="form-control-label">&nbsp;</label>
              <button class="btn btn-outline-primary form-control" id="add_button" onclick="nuevo()" style="cursor: pointer;">
                <i class="fa fa-plus-square mg-r-10"></i> NUEVO
              </button>
            </div>
          </div>
        </div>
      </div>
      <div class="br-pagebody" style="font-size: 14px;">
        <div class="br-section-wrapper">
          <div class="row">
            <div class="col-lg-8">
              <h6 class="tx-gray-800 tx-uppercase tx-bold tx-14 mg-b-5">Detalle Ordenes de GIRO</h6>
              <div id="tupa_info">
                <h6 id="tupa_nom"></h6>
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
                  <th class="wd-10p" style="text-align: center; ">Tasas</th>
                  <th class="wd-10p" style="text-align: center; ">Eliminar</th>
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
    <script type="text/javascript" src="usudetalleciudadano.js"></script>
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