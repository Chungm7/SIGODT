<!-- index.php -->
<?php
require_once("../../config/conexion.php");
if (isset($_SESSION["usua_id_SIGODT"])) {
?>
  <!DOCTYPE html>
  <html lang="es">

  <head>
    <?php require_once("../html/mainHead.php"); ?>
    <title>SIGODT::Gestión de Empresas</title>
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
                      <li class="breadcrumb-item active" aria-current="page">Empresas</li>
                    </ol>
                    <h2 class="page-title">Padrón de Empresas</h2>
                    <div class="text-muted mt-1">Administración de personas jurídicas, consulta y sincronización SUNAT / PIDE</div>
                  </div>
                  <div class="col-auto ms-auto d-print-none">
                    <button class="btn btn-primary d-inline-flex align-items-center" onclick="nuevoRegistro()">
                      <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-plus" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <line x1="12" y1="5" x2="12" y2="19" />
                        <line x1="5" y1="12" x2="19" y2="12" />
                      </svg>
                      <span>Nueva Empresa</span>
                    </button>
                  </div>
                </div>
              </div>

              <!-- Tarjeta principal con tabla a ancho completo -->
              <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center py-2">
                  <h3 class="card-title fw-bold mb-0">Listado General de Empresas</h3>
                  <div class="card-actions">
                    <div class="input-icon">
                      <span class="input-icon-addon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-search" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                          <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                          <circle cx="10" cy="10" r="7" />
                          <line x1="21" y1="21" x2="15" y2="15" />
                        </svg>
                      </span>
                      <input type="text" id="search" class="form-control" placeholder="Buscar por RUC o Razón Social..." aria-label="Buscar empresa">
                    </div>
                  </div>
                </div>

                <div class="table-responsive">
                  <table id="tabla-empresa" class="table card-table table-vcenter text-nowrap datatable" style="width:100%;">
                    <thead>
                      <tr>
                        <th class="w-1">ID</th>
                        <th>RUC</th>
                        <th>Razón Social</th>
                        <th>Nombre Comercial</th>
                        <th>Dirección</th>
                        <th>Estado</th>
                        <th class="text-center w-1">Acciones</th>
                      </tr>
                    </thead>
                    <tbody></tbody>
                  </table>
                </div>
              </div>

            </div>
          </div>
        </div>

        <?php require_once("../html/footer.php"); ?>
      </div>
    </div>

    <?php require_once("modalmantenimiento.php"); ?>
    <?php require_once("../html/mainjs.php"); ?>
    <script src="empresa.js?v=<?php echo filemtime('empresa.js'); ?>"></script>
  </body>

  </html>
<?php
} else {
  header("Location:" . Conectar::ruta() . "view/404/");
}
?>