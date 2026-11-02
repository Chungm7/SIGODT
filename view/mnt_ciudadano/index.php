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

              <!-- Encabezado con breadcrumbs y botón de acción -->
              <div class="page-header d-print-none mb-3">
                <div class="row align-items-center">
                  <div class="col">
                    <ol class="breadcrumb breadcrumb-arrows mb-3" aria-label="breadcrumbs">
                      <li class="breadcrumb-item"><a href="../inicio/">SIGODT</a></li>
                      <li class="breadcrumb-item"><a href="#">Mantenimientos</a></li>
                      <li class="breadcrumb-item active" aria-current="page">Ciudadanos</li>
                    </ol>
                    <h2 class="page-title">Padrón de Ciudadanos</h2>
                    <div class="text-muted mt-2">Administración de personas naturales, consulta y sincronización RENIEC / PIDE</div>
                  </div>
                  <div class="col-auto ms-auto d-print-none">
                    <button class="btn btn-muni-action" onclick="nuevoRegistro()">
                      <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-plus" width="20" height="20" viewBox="0 0 24 24" stroke-width="2.25" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <line x1="12" y1="5" x2="12" y2="19" />
                        <line x1="5" y1="12" x2="19" y2="12" />
                      </svg>
                      <span>NUEVO CIUDADANO</span>
                    </button>
                  </div>
                </div>
              </div>

              <!-- Card de Búsqueda -->
              <div class="card shadow-sm muni-stripe-top mb-3">
                <div class="card-header py-2">
                  <h3 class="card-title fw-bold mb-0">Criterio de Búsqueda</h3>
                </div>
                <div class="card-body">
                  <form id="formSearchCiudadano">
                    <div class="row g-2 align-items-center">
                      <div class="col">
                        <div class="input-group">
                          <span class="input-group-text">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-id" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                              <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                              <path d="M3 4m0 3a3 3 0 0 1 3 -3h12a3 3 0 0 1 2 2v10a3 3 0 0 1 -2 2h-12a3 3 0 0 1 -3 -3z" />
                              <path d="M9 10m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
                              <path d="M15 8l2 0" />
                              <path d="M15 12l2 0" />
                              <path d="M7 16l10 0" />
                            </svg>
                          </span>
                          <input type="text" id="search" class="form-control" placeholder="Buscar por DNI o nombres..." aria-label="Buscar ciudadano">
                        </div>
                      </div>
                      <div class="col-12 col-md-auto">
                        <button class="btn btn-muni-action btn-search-action w-100" type="submit" id="btnBuscarCiudadano">
                          <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-search me-1" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" />
                            <path d="M21 21l-6 -6" />
                          </svg>
                          <span>BUSCAR</span>
                        </button>
                      </div>
                    </div>
                  </form>
                </div>
              </div>

              <!-- Card de Resultados -->
              <div class="card shadow-sm mb-3">
                <div class="card-header py-2">
                  <h3 class="card-title fw-bold mb-0">Listado General de Administrados</h3>
                </div>

                <div class="table-responsive">
                  <table id="tabla-ciudadano" class="table card-table table-vcenter text-nowrap datatable" style="width:100%;">
                    <thead>
                      <tr>
                        <th class="w-1">ID</th>
                        <th>Tipo Doc</th>
                        <th>N° Documento</th>
                        <th>Primer Apellido</th>
                        <th>Segundo Apellido</th>
                        <th>Nombres</th>
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
    <script src="ciud.js?v=<?php echo filemtime('ciud.js'); ?>"></script>
  </body>

  </html>
<?php
} else {
  header("Location:" . Conectar::ruta() . "view/404/");
  exit();
}
?>