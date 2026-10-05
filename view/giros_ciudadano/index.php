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

              <!-- Encabezado con breadcrumbs -->
              <div class="page-header d-print-none mb-3">
                <div class="row align-items-center">
                  <div class="col">
                    <ol class="breadcrumb breadcrumb-arrows mb-3" aria-label="breadcrumbs">
                      <li class="breadcrumb-item"><a href="../inicio/">SIGODT</a></li>
                      <li class="breadcrumb-item"><a href="#">Procesos</a></li>
                      <li class="breadcrumb-item active" aria-current="page">Órdenes de Derecho de Trámite</li>
                    </ol>
                    <h2 class="page-title">Gestión de Órdenes de Giro</h2>
                    <div class="text-muted mt-2">Emisión, liquidación y control de trámites por administrado</div>
                  </div>
                </div>
              </div>

              <!-- Inputs ocultos requeridos por la lógica operativa -->
              <input type="hidden" name="usua_dni_SIGODT" id="usua_dni_SIGODT" value="<?php echo $_SESSION["usua_dni_SIGODT"]; ?>" />
              <input type="hidden" name="usu_depe_id_SIGODT" id="usu_depe_id_SIGODT" value="<?php echo $_SESSION["usu_depe_id_SIGODT"]; ?>" />
              <input type="hidden" name="tupa_id" id="tupa_id" />

              <div class="row g-3">
                <!-- Barra lateral izquierda: Perfil y Métricas -->
                <div class="col-12 col-md-4 col-xl-2">

                  <!-- Tarjeta de Perfil Operador -->
                  <div class="card card-sm mb-2">
                    <div class="card-body py-2">
                      <div class="row align-items-center">
                        <div class="col-auto">
                          <span class="avatar rounded" style="background-image: url(../../public/img/perfil.jpeg)"></span>
                        </div>
                        <div class="col text-truncate">
                          <div class="fw-bold text-truncate" title="<?php echo $_SESSION['pers_nombre_SIGODT']; ?>"><?php echo $_SESSION['pers_nombre_SIGODT']; ?></div>
                          <div class="text-secondary small text-truncate"><?php echo $_SESSION['rol_nombre_SIGODT']; ?></div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Métricas: Total Órdenes -->
                  <div class="card card-sm mb-2">
                    <div class="card-body py-2">
                      <div class="row align-items-center">
                        <div class="col-auto">
                          <span class="bg-primary text-white avatar">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-clipboard-list" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                              <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                              <path d="M9 5h-2a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-12a2 2 0 0 0 -2 -2h-2" />
                              <path d="M9 3m0 2a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v0a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2z" />
                              <path d="M9 12l.01 0" />
                              <path d="M13 12l2 0" />
                              <path d="M9 16l.01 0" />
                              <path d="M13 16l2 0" />
                            </svg>
                          </span>
                        </div>
                        <div class="col">
                          <div class="fw-bold fs-3" id="totalGeneral">0</div>
                          <div class="text-secondary small">Total Órdenes</div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Métricas: Total Hoy -->
                  <div class="card card-sm mb-2">
                    <div class="card-body py-2">
                      <div class="row align-items-center">
                        <div class="col-auto">
                          <span class="bg-success text-white avatar">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-sun" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                              <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                              <path d="M12 12m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0" />
                              <path d="M3 12h1m8 -9v1m8 8h1m-9 8v1m-6.4 -15.4l.7 .7m12.1 -.7l-.7 .7m0 11.4l.7 .7m-12.1 -.7l-.7 .7" />
                            </svg>
                          </span>
                        </div>
                        <div class="col">
                          <div class="fw-bold fs-3" id="totalDia">0</div>
                          <div class="text-secondary small">Total Hoy</div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Métricas: Total Ayer -->
                  <div class="card card-sm mb-2">
                    <div class="card-body py-2">
                      <div class="row align-items-center">
                        <div class="col-auto">
                          <span class="bg-warning text-white avatar">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-history" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                              <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                              <path d="M12 8l0 4l2 2" />
                              <path d="M3.05 11a9 9 0 1 1 .5 4m-.5 5v-5h5" />
                            </svg>
                          </span>
                        </div>
                        <div class="col">
                          <div class="fw-bold fs-3" id="totalAyer">0</div>
                          <div class="text-secondary small">Total Ayer</div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Normativa: TUPA Vigente -->
                  <div class="card card-sm mb-2">
                    <div class="card-body py-2">
                      <div class="row align-items-center">
                        <div class="col-auto">
                          <span class="bg-purple text-white avatar">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-briefcase" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                              <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                              <path d="M3 7m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v9a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z" />
                              <path d="M8 7v-2a2 2 0 0 1 2 -2h4a2 2 0 0 1 2 2v2" />
                              <path d="M12 12l0 .01" />
                              <path d="M3 13a20 20 0 0 0 18 0" />
                            </svg>
                          </span>
                        </div>
                        <div class="col text-truncate">
                          <div class="fw-bold text-truncate" id="tupa_nom">-</div>
                          <div class="text-secondary small">TUPA Vigente</div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Normativa: TUSNE Vigente -->
                  <div class="card card-sm mb-2">
                    <div class="card-body py-2">
                      <div class="row align-items-center">
                        <div class="col-auto">
                          <span class="bg-danger text-white avatar">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-file-text-shield" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                              <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                              <path d="M13 3v4a.997 .997 0 0 0 1 1h4" />
                              <path d="M11 21h-5a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v3.5" />
                              <path d="M8 9h1" />
                              <path d="M8 12.994l3 0" />
                              <path d="M8 16.997l2 0" />
                              <path d="M21 15.994c0 4 -2.5 6 -3.5 6s-3.5 -2 -3.5 -6c1 0 2.5 -.5 3.5 -1.5c1 1 2.5 1.5 3.5 1.5" />
                            </svg>
                          </span>
                        </div>
                        <div class="col text-truncate">
                          <div class="fw-bold text-truncate" id="tusne_nom">-</div>
                          <div class="text-secondary small">TUSNE Vigente</div>
                        </div>
                      </div>
                    </div>
                  </div>

                </div>

                <!-- Columna principal: Selección y Tabla de Procedimientos -->
                <div class="col-12 col-md-8 col-xl-10">

                  <div class="card shadow-sm">
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