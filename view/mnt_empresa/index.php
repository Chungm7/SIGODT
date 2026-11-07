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
                <span class="mpch-bc-cur">Empresas</span>
              </div>

              <!-- Encabezado de la Página (Título y Descripción) -->
              <div class="page-header page-header-context d-print-none mb-3">
                <div class="row align-items-center">
                  <div class="col">
                    <h2 class="page-title text-uppercase">Padrón de Empresas</h2>
                    <div class="text-secondary mt-1" style="font-size: 14px;">Administración de personas jurídicas, consulta y sincronización SUNAT / PIDE</div>
                  </div>
                  <div class="col-auto ms-auto d-print-none">
                    <button class="btn btn-muni-action" onclick="nuevoRegistro()">
                      <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-plus" width="20" height="20" viewBox="0 0 24 24" stroke-width="2.25" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <line x1="12" y1="5" x2="12" y2="19" />
                        <line x1="5" y1="12" x2="19" y2="12" />
                      </svg>
                      <span>NUEVA EMPRESA</span>
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
                  <form id="formSearchEmpresa">
                    <div class="row g-2 align-items-center">
                      <div class="col">
                        <div class="input-group">
                          <span class="input-group-text">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-building" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                              <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                              <path d="M3 21l18 0" />
                              <path d="M9 8l1 0" />
                              <path d="M9 12l1 0" />
                              <path d="M9 16l1 0" />
                              <path d="M14 8l1 0" />
                              <path d="M14 12l1 0" />
                              <path d="M14 16l1 0" />
                              <path d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16" />
                            </svg>
                          </span>
                          <input type="text" id="search" class="form-control" placeholder="Buscar por RUC o Razón Social..." aria-label="Buscar empresa">
                        </div>
                      </div>
                      <div class="col-12 col-md-auto">
                        <button class="btn btn-muni-action btn-search-action w-100" type="submit" id="btnBuscarEmpresa">
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
                  <h3 class="card-title fw-bold mb-0">Listado General de Empresas</h3>
                </div>

                <div class="table-responsive">
                  <table id="tabla-empresa" class="table card-table table-vcenter datatable" style="width:100%;">
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
  exit();
}
?>