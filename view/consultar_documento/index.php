<?php
/* Llamamos al archivo de conexion.php */
require_once("../../config/conexion.php");

if (isset($_SESSION["usua_id_SIGODT"])) {
  ?>
  <!DOCTYPE html>
  <html lang="es">

  <head>
    <?php require_once("../html/mainHead.php"); ?>
    <title>MPCH::Órdenes de Giro</title>
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
                <li class="breadcrumb-item">
                  <a href="../inicio/">SIGODT</a>
                </li>
                <li class="breadcrumb-item">
                  <a href="../inicio/">Reportes</a>
                </li>
                <li class="breadcrumb-item active">
                  <a href="#">Consultar por Documento</a>
                </li>
              </ol>
              <h2 class="page-title" style="margin: 20px;">
                <span class="text-truncate"> Lista mensual de Órdenes de Giro.</span>
              </h2>
              <div class="row">
                <div class="col-md-12">
                  <div class="card">
                    <div class="card-body">
                    
                      <form id="searchForm" style="margin-block: 30px;">
                        <div class="row mb-3 d-flex justify-content-center">
                          <div class="col-md-4">
                            <input type="text" id="searchDocumento" class="form-control text-center"
                              placeholder="Ingrese DNI o RUC">
                          </div>
                          <div class="col-md-2 text-left">
                            <button class="btn btn-primary" type="submit" id="searchBtn">
                              <span id="spinner" class="spinner-border spinner-border-sm d-none" role="status"
                                aria-hidden="true"></span>
                              <i class="fas fa-search"></i> Buscar
                            </button>
                          </div>
                        </div>
                      </form>

                      <div id="message" class="alert d-none"></div>
                      <table class="table table-vcenter" id="ordenGiroTable">
                        <thead class="text-secondary">
                          <tr>
                            <th>#</th>
                            <th>Orden de Giro</th>
                            <th>Fecha</th>
                            <th>Hora</th>
                            <th>Nombre Ciudadano</th>
                            <th>Documento</th>
                            <th>Procedimiento</th>
                            <th>Importe</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                          </tr>
                        </thead>
                        <tbody id="ordenGiroList">
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
    <?php require_once("../html/mainjs.php"); ?>
    <script>
      document.getElementById("searchForm").addEventListener("submit", function (event) {
        event.preventDefault(); // Evita que el formulario recargue la página

        let documento = document.getElementById("searchDocumento").value;
        let searchBtn = document.getElementById("searchBtn");
        let spinner = document.getElementById("spinner");
        let messageDiv = document.getElementById("message");

        if (documento.trim() === "") {
          messageDiv.classList.remove("d-none", "alert-success");
          messageDiv.classList.add("alert-danger");
          messageDiv.textContent = "Ingrese un documento válido.";
          return;
        }

        searchBtn.disabled = true;
        spinner.classList.remove("d-none");
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
            if (data.length === 0) {
              messageDiv.classList.remove("d-none", "alert-success");
              messageDiv.classList.add("alert-warning");
              messageDiv.textContent = "No se encontraron registros para el documento ingresado.";
            } else {
              messageDiv.classList.add("d-none");
              data.forEach((orden, index) => {
                let estadoData = getEstado(orden.orden_est);

                let row = `<tr>
                              <td>${index + 1}</td>
                              <td>${orden.ogciud_id}</td>
                              <td>${orden.fecha}</td>
                              <td>${orden.hora}</td>
                              <td>${orden.ciud_nombre}</td>
                              <td>${orden.ciudadano_doc}</td>
                              <td>${orden.proced_nom}</td>
                              <td>S/ ${orden.importe}</td>
                              <td><span class="badge bg-${estadoData.color_barra} text-${estadoData.color_barra}-fg ">${estadoData.estado}</span></td>
                              <td>
                                <button class="btn btn-info btn-sm" onclick="imprimirGiro('${orden.ogciud_id}')">
                                  <i class="fas fa-eye"></i> Ver
                                </button>
                              </td>
                            </tr>`;
                tbody.innerHTML += row;
              });
            }
          })
          .catch(error => {
            messageDiv.classList.remove("d-none", "alert-success");
            messageDiv.classList.add("alert-danger");
            messageDiv.textContent = "Error al obtener datos: " + error;
          })
          .finally(() => {
            searchBtn.disabled = false;
            spinner.classList.add("d-none");
          });
      });

      function imprimirGiro(ogciud_id) {
        redirect_by_post(
          "http://10.10.10.16/SIGODT/controller/rc.php?op=imprimirxid",
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
            estado = "Completado";
            color_barra = "purple";
            break;
          default:
            estado = "Extornado";
            color_barra = "warning";
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