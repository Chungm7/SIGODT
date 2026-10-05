<?php
require_once("../../config/conexion.php");

if (isset($_SESSION["usua_id_SIGODT"])) {
?>
  <!DOCTYPE html>
  <html lang="es">

  <head>
    <?php require_once("../html/mainHead.php"); ?>
    <title>SIGODT::Consulta por Documento</title>
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
                      <li class="breadcrumb-item"><a href="#">Consultas</a></li>
                      <li class="breadcrumb-item active" aria-current="page">Consulta por Documento</li>
                    </ol>
                    <h2 class="page-title">Consulta de Órdenes por Documento</h2>
                    <div class="text-secondary mt-2">Búsqueda histórica de órdenes de derecho de trámite por DNI, RUC, CEE o CPP</div>
                  </div>
                </div>
              </div>

              <!-- Card de Búsqueda -->
              <div class="card shadow-sm mb-3">
                <div class="card-header py-2">
                  <h3 class="card-title fw-bold mb-0">Criterio de Búsqueda</h3>
                </div>
                <div class="card-body">
                  <form id="searchForm">
                    <div class="row g-2 justify-content-center">
                      <div class="col-12 col-md-5 col-lg-4">
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
                          <input type="text" id="searchDocumento" class="form-control" placeholder="Ingrese DNI o RUC (ej. 45678901)" autofocus required>
                        </div>
                      </div>
                      <div class="col-12 col-md-auto">
                        <button class="btn btn-primary d-inline-flex align-items-center w-100" type="submit" id="searchBtn">
                          <span id="spinner" class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
                          <svg id="iconBuscar" xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-search me-1" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" />
                            <path d="M21 21l-6 -6" />
                          </svg>
                          <span>Buscar</span>
                        </button>
                      </div>
                    </div>
                  </form>
                </div>
              </div>

              <!-- Mensaje de estado -->
              <div id="message" class="alert d-none mb-3" role="alert"></div>

              <!-- Card de Resultados -->
              <div class="card shadow-sm">
                <div class="card-header py-2">
                  <h3 class="card-title fw-bold mb-0">Órdenes de Giro Registradas</h3>
                </div>
                <div class="table-responsive">
                  <table class="table table-vcenter card-table table-striped table-hover w-100" id="ordenGiroTable">
                    <thead>
                      <tr>
                        <th class="text-center" style="width: 5%;">#</th>
                        <th class="text-center" style="width: 10%;">Orden de Giro</th>
                        <th class="text-center" style="width: 10%;">Fecha</th>
                        <th class="text-center" style="width: 8%;">Hora</th>
                        <th style="width: 20%;">Ciudadano</th>
                        <th class="text-center" style="width: 10%;">Documento</th>
                        <th style="width: 20%;">Procedimiento</th>
                        <th class="text-end" style="width: 8%;">Importe</th>
                        <th class="text-center" style="width: 8%;">Estado</th>
                        <th class="text-center" style="width: 8%;">Acciones</th>
                      </tr>
                    </thead>
                    <tbody id="ordenGiroList">
                      <tr>
                        <td colspan="10" class="text-center text-muted py-4">
                          Ingrese un número de documento y haga clic en Buscar para consultar órdenes de giro.
                        </td>
                      </tr>
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

    <?php require_once("../html/mainjs.php"); ?>
    <script>
      document.getElementById("searchForm").addEventListener("submit", function (event) {
        event.preventDefault();

        let documento = document.getElementById("searchDocumento").value;
        let searchBtn = document.getElementById("searchBtn");
        let spinner = document.getElementById("spinner");
        let iconBuscar = document.getElementById("iconBuscar");
        let messageDiv = document.getElementById("message");

        if (documento.trim() === "") {
          messageDiv.classList.remove("d-none", "alert-success", "alert-warning");
          messageDiv.classList.add("alert-danger");
          messageDiv.textContent = "Ingrese un número de documento válido.";
          return;
        }

        searchBtn.disabled = true;
        spinner.classList.remove("d-none");
        if (iconBuscar) iconBuscar.classList.add("d-none");
        messageDiv.classList.add("d-none");

        fetch("../../controller/ordengiro.php?op=get_ordenes_giro", {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: "documento=" + encodeURIComponent(documento)
        })
          .then(response => response.json())
          .then(data => {
            let tbody = document.getElementById("ordenGiroList");
            tbody.innerHTML = "";
            if (!data || data.length === 0) {
              messageDiv.classList.remove("d-none", "alert-success", "alert-danger");
              messageDiv.classList.add("alert-warning");
              messageDiv.textContent = "No se encontraron órdenes de giro para el documento ingresado.";
              tbody.innerHTML = `<tr><td colspan="10" class="text-center text-muted py-4">No se encontraron resultados</td></tr>`;
            } else {
              messageDiv.classList.add("d-none");
              data.forEach((orden, index) => {
                let estadoData = getEstado(orden.orden_est);

                let row = `<tr>
                              <td class="text-center text-muted">${index + 1}</td>
                              <td class="text-center fw-bold">${orden.ogciud_id}</td>
                              <td class="text-center">${orden.fecha}</td>
                              <td class="text-center">${orden.hora}</td>
                              <td>${orden.ciud_nombre}</td>
                              <td class="text-center">${orden.ciudadano_doc}</td>
                              <td>${orden.proced_nom}</td>
                              <td class="text-end fw-bold">S/ ${parseFloat(orden.importe).toFixed(2)}</td>
                              <td class="text-center"><span class="badge bg-${estadoData.color_barra} text-${estadoData.color_barra}-fg">${estadoData.estado}</span></td>
                              <td class="text-center">
                                <button class="btn btn-outline-primary btn-sm d-inline-flex align-items-center" onclick="imprimirGiro('${orden.ogciud_id}')" title="Ver e imprimir orden">
                                  <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-printer me-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M17 17h2a2 2 0 0 0 2 -2v-4a2 2 0 0 0 -2 -2h-14a2 2 0 0 0 -2 2v4a2 2 0 0 0 2 2h2" />
                                    <path d="M17 9v-4a2 2 0 0 0 -2 -2h-6a2 2 0 0 0 -2 2v4" />
                                    <path d="M7 13m0 2a2 2 0 0 1 2 -2h6a2 2 0 0 1 2 2v4a2 2 0 0 1 -2 2h-6a2 2 0 0 1 -2 -2z" />
                                  </svg>
                                  <span>Ver</span>
                                </button>
                              </td>
                            </tr>`;
                tbody.innerHTML += row;
              });
            }
          })
          .catch(error => {
            messageDiv.classList.remove("d-none", "alert-success", "alert-warning");
            messageDiv.classList.add("alert-danger");
            messageDiv.textContent = "Error al consultar los datos: " + error;
          })
          .finally(() => {
            searchBtn.disabled = false;
            spinner.classList.add("d-none");
            if (iconBuscar) iconBuscar.classList.remove("d-none");
          });
      });

      function imprimirGiro(ogciud_id) {
        redirect_by_post(
          "../../controller/rc.php?op=imprimirxid",
          { ogciud_id: ogciud_id },
          true
        );
      }

      function redirect_by_post(purl, pparameters, in_new_tab) {
        pparameters = typeof pparameters == "undefined" ? {} : pparameters;
        in_new_tab = typeof in_new_tab == "undefined" ? true : in_new_tab;

        var form = document.createElement("form");
        $(form)
          .attr("id", "reg-form")
          .attr("name", "reg-form")
          .attr("action", purl)
          .attr("method", "post")
          .attr("enctype", "multipart/form-data");
        if (in_new_tab) {
          $(form).attr("target", "_blank");
        }
        $.each(pparameters, function (key) {
          $(form).append(
            '<input type="text" name="' + key + '" value="' + this + '" />'
          );
        });
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);

        return false;
      }

      function getEstado(est) {
        let estado, color_barra;
        switch (parseInt(est)) {
          case 0:
            estado = "Anulado";
            color_barra = "danger";
            break;
          case 1:
            estado = "Pendiente";
            color_barra = "warning";
            break;
          case 2:
            estado = "Girado";
            color_barra = "success";
            break;
          case 3:
            estado = "Improcedente";
            color_barra = "danger";
            break;
          case 4:
            estado = "Pagado";
            color_barra = "primary";
            break;
          case 5:
            estado = "Usado";
            color_barra = "purple";
            break;
          default:
            estado = "Extornado";
            color_barra = "secondary";
            break;
        }
        return { estado, color_barra };
      }
    </script>
  </body>

  </html>
<?php
} else {
  header("Location:" . Conectar::ruta() . "view/404/");
}
?>