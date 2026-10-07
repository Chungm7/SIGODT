<?php
require_once("../../config/conexion.php");

if (isset($_SESSION["usua_id_SIGODT"])) {
?>
  <!DOCTYPE html>
  <html lang="es">

  <head>
    <?php require_once("../html/mainHead.php"); ?>
    <title>SIGODT::Órdenes de Derecho de Trámite</title>
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

              <!-- Encabezado con breadcrumbs estilo sisGitse -->
              <nav class="breadcrumb mb-3">
                <a href="../inicio/">SIGODT</a>
                <svg class="breadcrumb-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10.1 16.3">
                  <path fill="currentColor" d="M0,14.4l6.2-6.2L0,1.9L2,0l8.1,8.1L2,16.3L0,14.4z" />
                </svg>
                <span class="breadcrumb-item active">Procesos</span>
                <svg class="breadcrumb-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10.1 16.3">
                  <path fill="currentColor" d="M0,14.4l6.2-6.2L0,1.9L2,0l8.1,8.1L2,16.3L0,14.4z" />
                </svg>
                <span>Órdenes de Derecho de Trámite</span>
              </nav>

              <!-- Module-card Header estilo sisGitse con page-title y text-muted mt-2 de SIGODT -->
              <div class="card border-0 mb-3" style="box-shadow: 0 10px 30px rgba(16, 24, 40, 0.06)">
                <div class="module-card d-flex flex-column flex-md-row align-items-center g-3 px-3 py-2 mb-0">
                  <!-- Ícono / Animación -->
                  <div class="text-center mb-3 mb-md-0">
                    <img src="../../public/static/gif/mensaje.gif" alt="Animación Header" style="width:60px; height:auto;">
                  </div>
                  <!-- Contenedor de título y descripción -->
                  <div class="content-wrapper flex-fill ms-md-3 text-center text-md-start">
                    <h2 class="page-title mb-1">Gestión de Órdenes de Giro</h2>
                    <div class="text-muted mt-2">Emisión, liquidación y control de trámites por administrado</div>
                  </div>
                </div>
              </div>

              <!-- Inputs ocultos requeridos por la lógica operativa -->
              <input type="hidden" name="usua_dni_SIGODT" id="usua_dni_SIGODT" value="<?php echo $_SESSION["usua_dni_SIGODT"]; ?>" />
              <input type="hidden" name="usu_depe_id_SIGODT" id="usu_depe_id_SIGODT" value="<?php echo $_SESSION["usu_depe_id_SIGODT"]; ?>" />
              <input type="hidden" name="tupa_id" id="tupa_id" />

              <div class="card shadow-sm mb-3">
                <div class="card-header py-2">
                  <h3 class="card-title fw-bold mb-0">Registrar Orden de Derecho de Trámite</h3>
                </div>

                    <div class="card-body">
                      <!-- Filtros normativos en cascada -->
                      <div class="row g-3 align-items-start mb-3">
                        <div class="col-12 col-md-5">
                          <label for="area_id" class="form-label fw-bold">Área / Dependencia: <span class="text-danger">*</span></label>
                          <select class="form-select select2" name="area_id" id="area_id" data-placeholder="Seleccione un área...">
                            <option value="" label="Seleccione"></option>
                          </select>
                        </div>

                        <div class="col-12 col-md-5">
                          <label for="proced_id" class="form-label fw-bold">
                            Procedimiento: <span class="text-danger">*</span>
                            <span class="form-label-description">
                              <a href="#" id="print_button" onclick="imprimirInformacion()" class="text-muted d-inline-flex align-items-center" title="Ver requisitos">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-checklist me-1" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                  <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                  <path d="M3.5 5.5l1.5 1.5l2.5 -2.5" />
                                  <path d="M3.5 11.5l1.5 1.5l2.5 -2.5" />
                                  <path d="M3.5 17.5l1.5 1.5l2.5 -2.5" />
                                  <path d="M11 6l9 0" />
                                  <path d="M11 12l9 0" />
                                  <path d="M11 18l9 0" />
                                </svg>
                                ¿Requisitos?
                              </a>
                            </span>
                          </label>
                          <select class="form-select select2" name="proced_id" id="proced_id" data-placeholder="Seleccione un procedimiento...">
                            <option value="" label="Seleccione"></option>
                          </select>
                        </div>

                        <div class="col-12 col-md-2">
                          <label class="form-label d-none d-md-block">&nbsp;</label>
                          <button class="btn btn-primary w-100 d-inline-flex align-items-center justify-content-center" id="add_button" onclick="nuevo()">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-plus me-1" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                              <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                              <line x1="12" y1="5" x2="12" y2="19" />
                              <line x1="5" y1="12" x2="19" y2="12" />
                            </svg>
                            <span>Nuevo</span>
                          </button>
                        </div>
                      </div>

                      <!-- Detalle del procedimiento seleccionado (cargado dinámicamente) -->
                      <div id="cardDetalleProcedimiento"></div>
                    </div>
                  </div>

                  <!-- Tarjeta 2: Listado de Procedimientos Abiertos -->
                  <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center py-2">
                      <h3 class="card-title fw-bold mb-0">Listado de Procedimientos Abiertos</h3>
                      <div class="card-actions">
                        <button type="button" class="btn btn-icon btn-outline-secondary" id="btnRecargar" title="Actualizar listado" onclick="recargarTabla(event)">
                          <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-refresh" width="22" height="22" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4" />
                            <path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4" />
                          </svg>
                        </button>
                      </div>
                    </div>

                    <div class="table-responsive">
                      <table id="detalle_data" class="table card-table table-vcenter table-hover datatable" style="width:100%">
                        <thead>
                          <tr>
                            <th class="text-center" style="width: 110px;">Código</th>
                            <th class="text-center" style="width: 100px;">DNI / Doc</th>
                            <th>Ciudadano / Razón Social</th>
                            <th class="text-center" style="width: 140px;">Fecha</th>
                            <th class="text-center" style="width: 110px;">Estado</th>
                            <th class="text-center" style="width: 160px;">Progreso del Trámite</th>
                            <th class="text-center" style="width: 70px;">Tasas</th>
                            <th class="text-center" style="width: 70px;">Eliminar</th>
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

              <?php require_once("../html/footer.php"); ?>
            </div>
          </div>
        </div>

    <?php require_once("modaltasas.php"); ?>
    <?php require_once("modalmantenimiento.php"); ?>
    <?php require_once("modal_pago.php"); ?>

    <?php require_once("../html/mainjs.php"); ?>
    <script type="text/javascript" src="usudetalleciudadano.js?v=<?php echo filemtime('usudetalleciudadano.js'); ?>"></script>

    <script>
      function limitabuscadni(input) {
        const tipo_documento = (document.getElementById('name_select_tipo')?.innerText || 'DNI').trim();
        const mensaje = document.getElementById("ciud_mensaje");

        if (tipo_documento === "DNI") {
          let valor = input.value.toString().replace(/\D/g, '');
          const max_length = 8;

          if (valor.length > max_length) {
            valor = valor.slice(0, max_length);
          }

          if (valor.length === max_length) {
            resetearCampos();
            buscarDNI(valor, tipo_documento);
            if (mensaje) {
              mensaje.className = "alert alert-success py-1 mb-2";
              mensaje.innerHTML = "Buscando información...";
              mensaje.classList.remove("d-none");
            }
          } else if (valor.length > 0) {
            resetearCampos();
            $("input[name='ciud_sex']").prop("disabled", true);
            $("#dateMask").removeAttr("readonly");
            $("#ciud_sex").prop("disabled", false);

            const faltantes = max_length - valor.length;
            if (mensaje) {
              mensaje.className = "alert alert-info py-1 mb-2";
              mensaje.innerHTML = `Ingrese 8 dígitos (faltan ${faltantes})`;
              mensaje.classList.remove("d-none");
            }
          } else {
            resetearCampos();
            if (mensaje) mensaje.classList.add("d-none");
          }

          input.value = valor;
        } else {
          let valor = input.value.toString().replace(/[^a-zA-Z0-9]/g, '').toUpperCase();
          const max_length = 15;

          if (valor.length > max_length) {
            valor = valor.slice(0, max_length);
          }

          if (valor.length > 0) {
            if (mensaje) {
              mensaje.className = "alert alert-info py-1 mb-2";
              mensaje.innerHTML = `Presione <b>Enter</b> o el botón buscar`;
              mensaje.classList.remove("d-none");
            }
          } else {
            resetearCampos();
            if (mensaje) mensaje.classList.add("d-none");
          }

          input.value = valor;
        }
      }

      function ejecutarBusquedaDoc() {
        const input = document.getElementById("ciudadano_doc");
        if (!input) return;
        const tipo_documento = (document.getElementById('name_select_tipo')?.innerText || 'DNI').trim();
        const valor = input.value.trim();
        const mensaje = document.getElementById("ciud_mensaje");

        if (!valor) {
          input.focus();
          return;
        }

        if (tipo_documento === "DNI") {
          if (valor.length !== 8) {
            if (mensaje) {
              mensaje.className = "alert alert-warning py-1 mb-2";
              mensaje.innerHTML = `El DNI debe tener 8 dígitos (actual: ${valor.length})`;
              mensaje.classList.remove("d-none");
            }
            input.focus();
            return;
          }
        } else {
          if (valor.length < 3) {
            if (mensaje) {
              mensaje.className = "alert alert-warning py-1 mb-2";
              mensaje.innerHTML = `Ingrese un número de documento válido`;
              mensaje.classList.remove("d-none");
            }
            input.focus();
            return;
          }
        }

        resetearCampos();
        buscarDNI(valor, tipo_documento);
        if (mensaje) {
          mensaje.className = "alert alert-success py-1 mb-2";
          mensaje.innerHTML = "Buscando información...";
          mensaje.classList.remove("d-none");
        }
      }

      function resetearCampos() {
        $("#ciudadano_nombre").val('');
        $("#ciudadano_apep").val('');
        $("#ciudadano_apem").val('');
        $("#ciud_id").val('');
        $("#imagen_ciudadano").attr("src", '../../public/img/perfil.jpeg');
      }

      function limitarbuscarruc(input) {
        let valor = input.value.toString().replace(/\D/g, '');

        if (valor.length > 11) {
          valor = valor.slice(0, 11);
        }

        const mensaje = document.getElementById("mensaje_empresa");

        if (valor.length === 11) {
          $("#empr_razon_social").val('');
          $("#empr_nombre_comercial").val('');
          $("#empr_id").val('');
          buscaRUC();
          mensaje.className = "alert alert-success";
          mensaje.innerHTML = "RUC válido, buscando información...";
          mensaje.classList.remove("d-none");
        } else if (valor.length > 0 && valor.length < 11) {
          const faltantesRuc = 11 - valor.length;
          mensaje.className = "alert alert-info py-1 mb-2";
          mensaje.innerHTML = `Ingrese 11 dígitos de RUC (faltan ${faltantesRuc})`;
          mensaje.classList.remove("d-none");
        } else {
          mensaje.classList.add("d-none");
        }

        input.value = valor;
      }
    </script>
  </body>

  </html>
<?php } else {
  header("Location:" . Conectar::ruta() . "view/404/");
} ?>