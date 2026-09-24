<?php
require_once("../../config/conexion.php");

if (isset($_SESSION["usua_id_SIGODT"])) {
  ?>
  <!DOCTYPE html>
  <html lang="es">

  <head>
    <?php require_once("../html/mainHead.php"); ?>
    <title>MPCH::Registrar Orden De derecho de Tramite</title>
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
              <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../inicio/">SIGODT</a></li>
                <li class="breadcrumb-item"><a href="../inicio/">Procesos</a></li>
                <li class="breadcrumb-item active"><a href="#">Registrar Orden de Derecho de Tramite Ciudadanos</a></li>
              </ol>

              <div class="row">
                <div class="col-lg-8">
                  <h6 class="tx-gray-800 tx-uppercase tx-bold tx-14 mg-b-5">Detalle Ordenes de GIRO</h6>
                  <p class="mg-b-30 tx-gray-600" id="fecha">Listado de Ordenes del día: </p>
                </div>
                <div class="col-lg-4" style="text-align: right;">
                  <div class="btn-group" role="group" aria-label="Recargar tabla">
                    <button type="button" class="btn btn-outline-primary pd-x-25" style="border-radius: 20px;"
                      onclick="recargarTabla()" onmouseover="showText()" onmouseout="hideText()">
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
                          <select class="form-control select2" name="proced_id" id="proced_id"
                            data-placeholder="Seleccione">
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
                        <button class="btn btn-outline-secondary form-control" style="width: 400px" id="print_button"
                          onclick="imprimirInformacion()">
                          <i class="fa fa-print mg-r-10"></i> Imprimir Requisitos
                        </button>
                      </div>
                    </div>
                    <div class="col-lg-6">
                      <div class="form-group">
                        <label class="form-control-label">&nbsp;</label>
                        <button class="btn btn-outline-primary form-control" style="width: 400px" id="add_button"
                          onclick="nuevo()">
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


              <div id="errorMessage" class="alert alert-danger d-none text-center mt-3"></div>
            </div>
          </div>
          <?php require_once("../html/footer.php"); ?>
        </div>
      </div>
    </div>

    <?php require_once("../html/mainjs.php"); ?>

  </body>

  </html>
<?php } else {
  header("Location:" . Conectar::ruta() . "view/404/");
} ?>