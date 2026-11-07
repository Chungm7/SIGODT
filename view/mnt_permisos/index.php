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
                <span class="mpch-bc-cur">Permisos por Área</span>
              </div>

              <!-- Encabezado de la Página (Título y Descripción) -->
              <div class="page-header page-header-context d-print-none mb-3">
                <div class="row align-items-center">
                  <div class="col">
                    <h2 class="page-title text-uppercase">Asignación de Usuarios por Área</h2>
                    <div class="text-secondary mt-1" style="font-size: 14px;">Gestión y control de personal autorizado por dependencia municipal</div>
                  </div>
                </div>
              </div>

              <!-- Tarjeta principal -->
              <div class="card shadow-sm muni-stripe-top">
                <div class="card-body">
                  <!-- Selector de Área y Botón de Asignación -->
                  <div class="row g-3 align-items-end mb-3">
                    <div class="col-12 col-md-8 col-lg-6">
                      <label class="form-label fw-bold">Dependencia / Área: <span class="text-danger">*</span></label>
                      <select class="form-select select2" name="depe_select" id="depe_select" data-placeholder="Seleccione un área...">
                        <option label="Seleccione"></option>
                      </select>
                    </div>
                    <div class="col-12 col-md-4 col-lg-6 d-flex justify-content-md-end">
                      <button class="btn btn-muni-action btn-muni-inline" id="add_button" onclick="nuevo()">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-user-plus" width="20" height="20" viewBox="0 0 24 24" stroke-width="2.25" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                          <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                          <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" />
                          <path d="M6 21v-2a4 4 0 0 1 4 -4h4c.342 0 .674 .043 .99 .124" />
                          <path d="M16 19h6" />
                          <path d="M19 16v6" />
                        </svg>
                        <span>Asignar Usuarios</span>
                      </button>
                    </div>
                  </div>

                  <!-- Tabla de usuarios asignados -->
                  <div class="table-responsive">
                    <table id="detalle_data" class="table card-table table-vcenter table-hover datatable" style="width:100%">
                      <thead>
                        <tr>
                          <th class="text-center" style="width: 120px;">DNI</th>
                          <th>Nombres</th>
                          <th>Apellido Paterno</th>
                          <th>Apellido Materno</th>
                          <th class="text-center" style="width: 80px;">Acciones</th>
                        </tr>
                      </thead>
                      <tbody>
                        <!-- Datos cargados dinámicamente -->
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

    <?php require_once("../html/mainjs.php"); ?>
    <script src="adminmntusuarea.js"></script>
  </body>

  </html>
<?php
} else {
  header("Location:" . Conectar::ruta() . "view/404/");
  exit();
}
?>