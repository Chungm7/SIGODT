<?php
require_once("../../config/conexion.php");

if (isset($_SESSION["usua_id_SIGODT"])) {
?>
  <!DOCTYPE html>
  <html lang="es">

  <head>
    <?php require_once("../html/mainHead.php"); ?>
    <title>MPCH::Gestión de Giros</title>
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
                <li class="breadcrumb-item"><a href="../inicio/">Orden de Derecho de tramiten</a></li>
                <li class="breadcrumb-item active"><a href="#">Registros Ciudadanos</a></li>
              </ol>
              <input type="hidden" name="usua_dni_SIGODT" id="usua_dni_SIGODT"
                value="<?php echo $_SESSION["usua_dni_SIGODT"]; ?>" />
              <input type="hidden" name="usu_depe_id_SIGODT" id="usu_depe_id_SIGODT"
                value="<?php echo $_SESSION["usu_depe_id_SIGODT"]; ?>" />
              <input type="hidden" name="tupa_id" id="tupa_id" />

              <style>
                .card-sm {
                  margin-block: 5px;
                }
              </style>
              <div class="row mt-4">
                <div class="col-md-2">

                  <div class="card card-sm" style="margin-top: 0;">
                    <div class="card-body">
                      <div class="row">
                        <div class="col-auto">
                          <span class="avatar rounded" style="background-image: url(../../public/img/perfil.jpeg)"></span>
                        </div>
                        <div class="col" style="font-size: 12px;">
                          <div class="font-weight-medium"><?php echo $_SESSION['pers_nombre_SIGODT']; ?></div>
                          <div class="text-secondary"><?php echo $_SESSION['rol_nombre_SIGODT']; ?></div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="card card-sm">
                    <div class="card-body">
                      <div class="row align-items-center">
                        <div class="col-auto">
                          <span class="bg-primary text-white avatar">
                            <i class="fa fa-chart-pie"></i>
                          </span>
                        </div>
                        <div class="col" style="text-align: left;">
                          <div class="font-weight-medium" id="totalGeneral">
                            0
                          </div>
                          <div class="text-secondary">Total Ordenes</div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <!-- Total Hoy -->
                  <div class="col-md-12">
                    <div class="card card-sm">
                      <div class="card-body">
                        <div class="row align-items-center">
                          <div class="col-auto">
                            <span class="bg-success text-white avatar">
                              <i class="fa fa-sun"></i>
                            </span>
                          </div>
                          <div class="col" style="text-align: left;">
                            <div class="font-weight-medium" id="totalDia">0</div>
                            <div class="text-secondary">Total Hoy</div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <!-- Total Ayer -->
                  <div class="col-md-12">
                    <div class="card card-sm">
                      <div class="card-body">
                        <div class="row align-items-center">
                          <div class="col-auto">
                            <span class="bg-warning text-white avatar">
                              <i class="fa fa-history"></i>
                            </span>
                          </div>
                          <div class="col" style="text-align: left;">
                            <div class="font-weight-medium" id="totalAyer">0</div>
                            <div class="text-secondary">Total Ayer</div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <!-- Total Ayer -->
                  <div class="col-md-12">
                    <div class="card card-sm">
                      <div class="card-body">
                        <div class="row align-items-center">
                          <div class="col-auto">
                            <span class="bg-purple text-white avatar">
                              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round"
                                class="icon icon-tabler icons-tabler-outline icon-tabler-briefcase">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path
                                  d="M3 7m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v9a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z" />
                                <path d="M8 7v-2a2 2 0 0 1 2 -2h4a2 2 0 0 1 2 2v2" />
                                <path d="M12 12l0 .01" />
                                <path d="M3 13a20 20 0 0 0 18 0" />
                              </svg>
                            </span>
                          </div>
                          <div class="col" style="text-align: left;">
                            <div class="font-weight-medium"><strong id="tupa_nom"></strong> </div>
                          </div>

                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-12">
                    <div class="card card-sm">
                      <div class="card-body">
                        <div class="row align-items-center">
                          <div class="col-auto">
                            <span class="bg-danger text-white avatar">
                              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-file-text-shield">
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
                          <div class="col" style="text-align: left;">
                            <div class="font-weight-medium"><strong id="tusne_nom"></strong> </div>
                          </div>

                        </div>
                      </div>
                    </div>
                  </div>

                </div>
                <div class="col-md-10">

                  <div class="card">
                    <div class="card-header">
                      <!-- Sección inferior: subtítulo y formulario -->
                      <div class="row">
                        <div class="col-md-12">
                          <!-- Sección superior: título y acciones -->
                          <div class="d-flex align-items-center mb-3">
                            <h3 class="tx-gray-800">REGISTRAR ORDEN DE DERECHO DE TRAMITE</h3>
                            <div class="card-actions btn-actions">
                              <a href="#" class="btn-action" id="btnRecargar" title="Recargar datos"
                                onclick="recargarTabla(event)">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                                  viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                  stroke-linecap="round" stroke-linejoin="round">
                                  <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                  <path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4" />
                                  <path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4" />
                                </svg>
                              </a>
                            </div>
                          </div>

                        </div>

                        <div class="col-lg-5">
                          <label for="area_id" class="form-label required">Area:</label>
                          <select class="form-select select2" name="area_id" id="area_id" data-placeholder="Seleccione">
                            <option value='' label="Seleccione"></option>
                          </select>
                        </div>
                        <div class="col-lg-5">
                          <label for="proced_id" class="form-label required">Procedimiento: </label>
                          <select class="form-select select2" name="proced_id" id="proced_id"
                            data-placeholder="Seleccione">
                            <option value='' label="Seleccione"></option>
                          </select>
                          <small>
                            <a href="#" id="print_button" onclick="imprimirInformacion()">
                              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="icon">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M3.5 5.5l1.5 1.5l2.5 -2.5" />
                                <path d="M3.5 11.5l1.5 1.5l2.5 -2.5" />
                                <path d="M3.5 17.5l1.5 1.5l2.5 -2.5" />
                                <path d="M11 6l9 0" />
                                <path d="M11 12l9 0" />
                                <path d="M11 18l9 0" />
                              </svg>
                              Requisitos de este procedimiento?
                            </a>
                          </small>
                        </div>
                        <div class="col-md-2" style="margin-top: 1.7rem;">
                          <button class="btn btn-6 btn-primary w-100" id="add_button" onclick="nuevo()"
                            style="cursor: pointer;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                              stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                              class="icon">
                              <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                              <path d="M13 16.5v-7.5a1 1 0 0 1 1 -1h6a1 1 0 0 1 1 1v3.5" />
                              <path d="M18 8v-3a1 1 0 0 0 -1 -1h-13a1 1 0 0 0 -1 1v12a1 1 0 0 0 1 1h8" />
                              <path d="M16 9h2" />
                              <path d="M16 19h6" />
                              <path d="M19 16v6" />
                            </svg>
                            NUEVO
                          </button>
                        </div>
                      </div>
                    </div>
                    

                    <div class="card-body">
                      <div id="cardDetalleProcedimiento"></div>
                      <h3 class="card-title mb-0">Listado de Procedimientos Abiertos</h3>
                      <div class="table-responsive">

                        <table id="detalle_data" class="table table-striped table-bordered">
                          <thead>
                            <tr>
                              <th class="wd-15p text-center">Código</th>
                              <th class="wd-10p text-center">DNI</th>
                              <th class="wd-20p text-center">Ciudadano</th>
                              <th class="wd-10p text-center">Fecha</th>
                              <th class="wd-5p text-center">Estado</th>
                              <th class="wd-50p text-center">Progreso del Tramite</th>
                              <th>Tasas</th>
                              <th>Eliminar</th>
                            </tr>
                          </thead>
                          <tbody>
                            <!-- Aquí se cargarán los datos dinámicamente -->
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div>

                </div>
              </div>




            </div>
          </div>
          
        </div>
      </div>
      <?php require_once("../html/footer.php"); ?>
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
          const max_length = 8; // Límite estricto de 8 dígitos solo para DNI

          if (valor.length > max_length) {
            valor = valor.slice(0, max_length);
          }

          if (valor.length === max_length) {
            resetearCampos();
            buscarDNI(valor, tipo_documento);
            if (mensaje) {
              mensaje.className = "alert alert-success py-1 mb-2";
              mensaje.innerHTML = "✅ Buscando información...";
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
              mensaje.innerHTML = `ℹ️ Ingrese 8 dígitos (faltan ${faltantes})`;
              mensaje.classList.remove("d-none");
            }
          } else {
            resetearCampos();
            if (mensaje) mensaje.classList.add("d-none");
          }

          input.value = valor;
        } else {
          // CEE, CPP u otros documentos de extranjería (longitud variable, sin límite rígido de 8 dígitos)
          let valor = input.value.toString().replace(/[^a-zA-Z0-9]/g, '').toUpperCase();
          const max_length = 15;

          if (valor.length > max_length) {
            valor = valor.slice(0, max_length);
          }

          if (valor.length > 0) {
            if (mensaje) {
              mensaje.className = "alert alert-info py-1 mb-2";
              mensaje.innerHTML = `ℹ️ Presione <b>Enter</b> o la <b>lupa</b> para buscar`;
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
              mensaje.innerHTML = `⚠️ El DNI debe tener 8 dígitos (actual: ${valor.length})`;
              mensaje.classList.remove("d-none");
            }
            input.focus();
            return;
          }
        } else {
          if (valor.length < 3) {
            if (mensaje) {
              mensaje.className = "alert alert-warning py-1 mb-2";
              mensaje.innerHTML = `⚠️ Ingrese un número de documento válido`;
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
          mensaje.innerHTML = "✅ Buscando información...";
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

        // Limitar a 11 dígitos
        if (valor.length > 11) {
          valor = valor.slice(0, 11);
        }

        const mensaje = document.getElementById("mensaje_empresa");

        // Validación del RUC
        if (valor.length === 11) {
          $("#empr_razon_social").val('');
          $("#empr_nombre_comercial").val('');
          $("#empr_id").val('');
          buscaRUC(); // Llamada a la función de búsqueda
          mensaje.className = "alert alert-success";
          mensaje.innerHTML = "✅ RUC válido, buscando información...";
          mensaje.classList.remove("d-none");
        } else if (valor.length > 0 && valor.length < 11) {
          const faltantesRuc = 11 - valor.length;
          mensaje.className = "alert alert-info py-1 mb-2";
          mensaje.innerHTML = `ℹ️ Ingrese 11 dígitos de RUC (faltan ${faltantesRuc})`;
          mensaje.classList.remove("d-none");
        } else {
          mensaje.classList.add("d-none"); // Ocultar mensaje
        }

        input.value = valor;
      }
    </script>

  </body>

  </html>
<?php } else {
  header("Location:" . Conectar::ruta() . "view/404/");
} ?>