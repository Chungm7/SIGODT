<?php
require_once("../../config/conexion.php");

if (isset($_SESSION["usua_id_SIGODT"])) {
?>
  <!DOCTYPE html>
  <html lang="es">

  <head>
    <?php require_once("../html/mainHead.php"); ?>
    <title>SIGODT::Auditoría Mensual de Órdenes</title>
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
                      <li class="breadcrumb-item active" aria-current="page">Auditoría Mensual</li>
                    </ol>
                    <h2 class="page-title">Auditoría Mensual de Órdenes de Giro</h2>
                    <div class="text-secondary mt-2">Monitoreo cronológico, supervisión y control de emisiones del mes</div>
                  </div>
                </div>
              </div>

              <!-- Card de Búsqueda y Resultados -->
              <div class="card shadow-sm">
                <div class="card-header py-2 d-flex flex-wrap justify-content-between align-items-center gap-2">
                  <h3 class="card-title fw-bold mb-0">Órdenes Emitidas en el Mes</h3>
                  <div class="col-12 col-sm-6 col-md-4">
                    <div class="input-group">
                      <span class="input-group-text">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-search" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                          <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                          <path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" />
                          <path d="M21 21l-6 -6" />
                        </svg>
                      </span>
                      <input type="text" id="searchInput" class="form-control" placeholder="Buscar por documento o nombre...">
                    </div>
                  </div>
                </div>

                <div class="table-responsive">
                  <table class="table table-vcenter card-table table-striped table-hover w-100">
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
                    <tbody id="ordenesMesList">
                      <!-- Datos cargados dinámicamente -->
                    </tbody>
                  </table>
                </div>

                <div class="card-footer d-flex align-items-center justify-content-between">
                  <span class="text-secondary small" id="infoPaginacion">Mostrando página 1</span>
                  <ul class="pagination m-0 ms-auto" id="pagination"></ul>
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
      function imprimirGiro(ogciud_id) {
        redirect_by_post(
          "../../controller/rc.php?op=imprimirxid",
          { ogciud_id: ogciud_id },
          true
        );
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

      $(document).ready(function () {
        let currentPage = 1;
        let limit = 10;
        let totalPages = 1;
        let searchTimeout = null;

        function fetchOrdenes(page = 1, search = "") {
          currentPage = page;
          let body = `page=${page}&limit=${limit}&search=${encodeURIComponent(search)}`;

          fetch("../../controller/ordengiro.php?op=get_ordenes_mes", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: body
          })
            .then(response => response.json())
            .then(data => {
              let tbody = document.getElementById("ordenesMesList");
              tbody.innerHTML = "";

              if (!data.data || data.data.length === 0) {
                tbody.innerHTML = `<tr><td colspan="10" class="text-center text-muted py-4">No se encontraron órdenes registradas en este período</td></tr>`;
                document.getElementById("infoPaginacion").textContent = "Sin registros";
                document.getElementById("pagination").innerHTML = "";
                return;
              }

              data.data.forEach((orden, index) => {
                let estadoData = getEstado(orden.orden_est);
                let printBtn = `<button class="btn btn-outline-primary btn-sm d-inline-flex align-items-center" onclick="imprimirGiro('${orden.ogciud_id}')" title="Imprimir orden">
                                  <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-printer me-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M17 17h2a2 2 0 0 0 2 -2v-4a2 2 0 0 0 -2 -2h-14a2 2 0 0 0 -2 2v4a2 2 0 0 0 2 2h2" />
                                    <path d="M17 9v-4a2 2 0 0 0 -2 -2h-6a2 2 0 0 0 -2 2v4" />
                                    <path d="M7 13m0 2a2 2 0 0 1 2 -2h6a2 2 0 0 1 2 2v4a2 2 0 0 1 -2 2h-6a2 2 0 0 1 -2 -2z" />
                                  </svg>
                                  <span>Ver</span>
                                </button>`;

                tbody.innerHTML += `<tr>
                    <td class="text-center text-muted">${(page - 1) * limit + index + 1}</td>
                    <td class="text-center fw-bold">${orden.ogciud_id}</td>
                    <td class="text-center">${orden.fecha}</td>
                    <td class="text-center">${orden.hora}</td>
                    <td>${orden.ciud_nombre}</td>
                    <td class="text-center">${orden.ciudadano_doc}</td>
                    <td>${orden.proced_nom}</td>
                    <td class="text-end fw-bold">S/ ${parseFloat(orden.importe).toFixed(2)}</td>
                    <td class="text-center"><span class="badge bg-${estadoData.color_barra} text-${estadoData.color_barra}-fg">${estadoData.estado}</span></td>
                    <td class="text-center">${printBtn}</td>
                </tr>`;
              });

              totalPages = Math.ceil(data.total / limit) || 1;
              document.getElementById("infoPaginacion").textContent = `Mostrando página ${currentPage} de ${totalPages} (Total: ${data.total} registros)`;
              updatePagination();
            })
            .catch(error => console.error("Error al obtener datos:", error));
        }

        function updatePagination() {
          let pagination = document.getElementById("pagination");
          pagination.innerHTML = "";

          if (totalPages > 1) {
            let prevDisabled = currentPage === 1 ? "disabled" : "";
            let nextDisabled = currentPage === totalPages ? "disabled" : "";

            pagination.innerHTML += `
                    <li class="page-item ${prevDisabled}">
                        <a class="page-link page-nav" href="#" data-page="${currentPage - 1}">Anterior</a>
                    </li>`;

            let startPage = Math.max(1, currentPage - 2);
            let endPage = Math.min(totalPages, currentPage + 2);

            for (let i = startPage; i <= endPage; i++) {
              let activeClass = i === currentPage ? "active" : "";
              pagination.innerHTML += `
                      <li class="page-item ${activeClass}">
                          <a class="page-link page-num" href="#" data-page="${i}">${i}</a>
                      </li>`;
            }

            pagination.innerHTML += `
                    <li class="page-item ${nextDisabled}">
                        <a class="page-link page-nav" href="#" data-page="${currentPage + 1}">Siguiente</a>
                    </li>`;
          }
        }

        $("#searchInput").on("input", function () {
          clearTimeout(searchTimeout);
          let search = $(this).val().trim();
          searchTimeout = setTimeout(() => {
            fetchOrdenes(1, search);
          }, 300);
        });

        $(document).on("click", ".page-num, .page-nav", function (e) {
          e.preventDefault();
          let page = parseInt($(this).data("page"));
          if (!isNaN(page) && page >= 1 && page <= totalPages && page !== currentPage) {
            fetchOrdenes(page, $("#searchInput").val().trim());
          }
        });

        fetchOrdenes();
      });
    </script>
  </body>

  </html>
<?php
} else {
  header("Location:" . Conectar::ruta() . "view/404/");
}
?>