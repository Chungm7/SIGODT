<?php
require_once("../../config/conexion.php");

if (isset($_SESSION["usua_id_SIGODT"])) {
?>
  <!DOCTYPE html>
  <html lang="es">

  <head>
    <?php require_once("../html/mainHead.php"); ?>
    <title>SIGODT::Auditoría de Órdenes de Giro</title>
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
                      <li class="breadcrumb-item"><a href="#">Procesos</a></li>
                      <li class="breadcrumb-item active" aria-current="page">Auditoría de Órdenes de Giro</li>
                    </ol>
                    <h2 class="page-title">Auditoría de Órdenes de Giro</h2>
                    <div class="text-secondary mt-2">Control, fiscalización y supervisión administrativa de procedimientos y órdenes de recaudación</div>
                  </div>
                </div>
              </div>

              <!-- Card 1: Filtros de Auditoría y Búsqueda -->
              <div class="card shadow-sm mb-3">
                <div class="card-header py-2">
                  <h3 class="card-title fw-bold mb-0">Filtros de Auditoría y Búsqueda</h3>
                </div>
                <div class="card-body">
                  <div class="row g-3 align-items-start mb-3">
                    <div class="col-12 col-md-3">
                      <label for="tupa_id" class="form-label fw-bold">TUPA: <span class="text-danger">*</span></label>
                      <select class="form-select select2" name="tupa_id" id="tupa_id" data-placeholder="Seleccione TUPA...">
                        <option value="" label="Seleccione"></option>
                      </select>
                    </div>

                    <div class="col-12 col-md-4">
                      <label for="area_id" class="form-label fw-bold">Área / Dependencia: <span class="text-danger">*</span></label>
                      <select class="form-select select2" name="area_id" id="area_id" data-placeholder="Seleccione un área...">
                        <option value="" label="Seleccione"></option>
                      </select>
                    </div>

                    <div class="col-12 col-md-5">
                      <label for="proced_id" class="form-label fw-bold">Procedimiento: <span class="text-danger">*</span></label>
                      <select class="form-select select2" name="proced_id" id="proced_id" data-placeholder="Seleccione un procedimiento...">
                        <option value="" label="Seleccione"></option>
                      </select>
                    </div>
                  </div>

                  <!-- Acciones Operativas -->
                  <div class="d-flex flex-wrap justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary d-inline-flex align-items-center" id="print_button" onclick="imprimirInformacion()">
                      <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-printer me-1" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <path d="M17 17h2a2 2 0 0 0 2 -2v-4a2 2 0 0 0 -2 -2h-14a2 2 0 0 0 -2 2v4a2 2 0 0 0 2 2h2" />
                        <path d="M17 9v-4a2 2 0 0 0 -2 -2h-6a2 2 0 0 0 -2 2v4" />
                        <path d="M7 13m0 2a2 2 0 0 1 2 -2h6a2 2 0 0 1 2 2v4a2 2 0 0 1 -2 2h-6a2 2 0 0 1 -2 -2z" />
                      </svg>
                      <span>Imprimir Requisitos</span>
                    </button>

                    <button type="button" class="btn btn-primary d-inline-flex align-items-center" id="add_button" onclick="nuevo()">
                      <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-plus me-1" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <path d="M12 5l0 14" />
                        <path d="M5 12l14 0" />
                      </svg>
                      <span>Abrir Procedimiento</span>
                    </button>
                  </div>
                </div>
              </div>

              <!-- Card 2: Listado de Procedimientos -->
              <div class="card shadow-sm">
                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                  <h3 class="card-title fw-bold mb-0">Listado de Procedimientos Abiertos</h3>
                  <div class="card-actions">
                    <button type="button" class="btn btn-outline-primary btn-icon" id="btnRecargar" onclick="recargarTabla()" title="Recargar tabla">
                      <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-refresh" width="22" height="22" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -5v5h5" />
                        <path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 5v-5h-5" />
                      </svg>
                    </button>
                  </div>
                </div>

                <div class="table-responsive">
                  <table id="detalle_data" class="table table-vcenter card-table table-striped table-hover w-100">
                    <thead>
                      <tr>
                        <th class="text-center" style="width: 7%;">Código</th>
                        <th class="text-center" style="width: 10%;">DNI / Doc</th>
                        <th style="width: 20%;">Ciudadano</th>
                        <th class="text-center" style="width: 13%;">Fecha Apertura</th>
                        <th class="text-center" style="width: 10%;">Estado</th>
                        <th class="text-center" style="width: 12%;">Progreso</th>
                        <th class="text-center" style="width: 7%;">Procedencia</th>
                        <th class="text-center" style="width: 7%;">Editar</th>
                        <th class="text-center" style="width: 7%;">Eliminar</th>
                        <th class="text-center" style="width: 7%;">Tasas</th>
                      </tr>
                    </thead>
                    <tbody>
                    </tbody>
                  </table>
                </div>
              </div>

              <div id="errorMessage" class="alert alert-danger d-none text-center mt-3"></div>

            </div>
          </div>
          <?php require_once("../html/footer.php"); ?>
        </div>
      </div>
    </div>

    <?php require_once("modalmantenimiento.php"); ?>
    <?php require_once("modaltasas.php"); ?>
    <?php require_once("modaleditcomentario.php"); ?>

    <?php require_once("../html/mainjs.php"); ?>
    <script type="text/javascript" src="admindetalleciudadano.js"></script>

  </body>

  </html>
<?php } else {
  header("Location:" . Conectar::ruta() . "view/404/");
} ?>