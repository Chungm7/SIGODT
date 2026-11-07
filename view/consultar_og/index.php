<?php
require_once("../../config/conexion.php");

if (isset($_SESSION["usua_id_SIGODT"])) {
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
        <?php require_once("../html/mainProfile.php"); ?>
        <?php require_once("../html/menu.php"); ?>

        <div class="page-wrapper">
          <div class="page-body">
            <div class="container-xl">

              <!-- Breadcrumb Contextual -->
              <div class="mpch-subsys-breadcrumb">
                <a href="../inicio/" class="mpch-bc-sys text-decoration-none" title="Ir al Inicio">SIGODT</a>
                <span class="mpch-bc-sep">
                  <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 6l6 6l-6 6" /></svg>
                </span>
                <span class="mpch-bc-sec">Consultas</span>
                <span class="mpch-bc-sep">
                  <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 6l6 6l-6 6" /></svg>
                </span>
                <span class="mpch-bc-cur">Consulta por Orden de Giro</span>
              </div>

              <!-- Encabezado de la Página (Título y Descripción) -->
              <div class="page-header page-header-context d-print-none mb-3">
                <div class="row align-items-center">
                  <div class="col">
                    <h2 class="page-title text-uppercase">Consulta por Número de Orden de Giro</h2>
                    <div class="text-secondary mt-1" style="font-size: 14px;">Búsqueda directa, verificación y visualización de expedientes y recibos emitidos</div>
                  </div>
                </div>
              </div>

              <!-- Card de Búsqueda -->
              <div class="card shadow-sm muni-stripe-top mb-3">
                <div class="card-header py-2">
                  <h3 class="card-title fw-bold mb-0">Criterio de Búsqueda</h3>
                </div>
                <div class="card-body">
                  <form id="ordenGiroForm">
                    <div class="row g-2 justify-content-center align-items-end">
                      <div class="col-12 col-sm-5 col-md-4 col-lg-3">
                        <label for="ordenNumero" class="form-label required fw-medium">Número de Orden</label>
                        <div class="input-group">
                          <span class="input-group-text">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-hash" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                              <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                              <path d="M5 9l14 0" />
                              <path d="M5 15l14 0" />
                              <path d="M11 4l-4 16" />
                              <path d="M17 4l-4 16" />
                            </svg>
                          </span>
                          <input type="text" id="ordenNumero" class="form-control font-monospace"
                            placeholder="Ej: 002616" autofocus required>
                        </div>
                      </div>
                      <div class="col-12 col-sm-4 col-md-3 col-lg-2">
                        <label for="ordenAnio" class="form-label required fw-medium">Año</label>
                        <div class="input-group">
                          <span class="input-group-text">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-calendar" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                              <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                              <path d="M4 7a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12z" />
                              <path d="M16 3v4" />
                              <path d="M8 3v4" />
                              <path d="M4 11h16" />
                              <path d="M11 15h1" />
                              <path d="M12 15v3" />
                            </svg>
                          </span>
                          <input type="number" id="ordenAnio" class="form-control font-monospace"
                            value="<?php echo date('Y'); ?>" required>
                        </div>
                      </div>
                      <div class="col-12 col-sm-auto">
                        <button type="submit" id="btnBuscarOrden" class="btn btn-muni-action btn-search-action w-100">
                          <span id="spinnerBuscar" class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
                          <svg id="iconBuscar" xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-search me-1" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" />
                            <path d="M21 21l-6 -6" />
                          </svg>
                          <span id="textBuscar">BUSCAR</span>
                        </button>
                      </div>
                    </div>
                  </form>
                </div>
              </div>

              <!-- Mensaje de Error -->
              <div id="errorMessage" class="alert alert-danger d-none text-center mb-3" role="alert"></div>

              <!-- Resultados en dos columnas -->
              <div class="row g-3 d-none" id="ordenGiroInfo">
                <!-- Columna de Datos -->
                <div class="col-12 col-lg-5">
                  <div class="card shadow-sm h-100">
                    <div class="card-header py-2">
                      <h3 class="card-title fw-bold mb-0">Detalles de la Orden</h3>
                    </div>
                    <div class="table-responsive">
                      <table class="table table-vcenter card-table">
                        <tbody>
                          <tr>
                            <th class="text-muted small text-uppercase" style="width: 35%;">ID Orden</th>
                            <td id="ogciud_id" class="fw-bold fs-3 text-primary"></td>
                          </tr>
                          <tr>
                            <th class="text-muted small text-uppercase">Fecha y Hora</th>
                            <td><span id="fecha"></span> <span id="hora" class="text-secondary small"></span></td>
                          </tr>
                          <tr>
                            <th class="text-muted small text-uppercase">Girador / Operador</th>
                            <td id="nombre_girador"></td>
                          </tr>
                          <tr>
                            <th class="text-muted small text-uppercase">Ciudadano / Razón</th>
                            <td id="ciud_nombre" class="fw-bold"></td>
                          </tr>
                          <tr>
                            <th class="text-muted small text-uppercase">Documento</th>
                            <td id="ciudadano_doc"></td>
                          </tr>
                          <tr>
                            <th class="text-muted small text-uppercase">Procedimiento</th>
                            <td id="proced_nom"></td>
                          </tr>
                          <tr>
                            <th class="text-muted small text-uppercase">Importe Total</th>
                            <td id="importe" class="fw-bold fs-3 text-success"></td>
                          </tr>
                          <tr>
                            <th class="text-muted small text-uppercase">Estado</th>
                            <td id="estado"></td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>

                <!-- Columna de PDF -->
                <div class="col-12 col-lg-7">
                  <div class="card shadow-sm h-100">
                    <div class="card-header py-2 d-flex justify-content-between align-items-center">
                      <h3 class="card-title fw-bold mb-0">Vista Previa del Documento</h3>
                      <button class="btn btn-outline-primary btn-sm d-inline-flex align-items-center" id="openPdfBtn" title="Abrir en pestaña nueva">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-external-link me-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                          <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                          <path d="M12 6h-6a2 2 0 0 0 -2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-6" />
                          <path d="M11 13l9 -9" />
                          <path d="M15 4h5v5" />
                        </svg>
                        <span>Abrir PDF</span>
                      </button>
                    </div>
                    <div class="card-body p-0">
                      <iframe id="ordenGiroPdf" class="w-100 border-0" style="height: 550px;"></iframe>
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
      var currentPdfBase64 = "";

      function formatearNumero(value) {
        let valor = value.replace(/\D/g, '');
        valor = valor.padStart(6, "0");
        return valor;
      }

      document.getElementById("ordenGiroForm").addEventListener("submit", function (event) {
        event.preventDefault();

        let inputNumero = document.getElementById("ordenNumero");
        let ordenNumero = inputNumero.value.trim();
        let ordenAnio = document.getElementById("ordenAnio").value.trim();

        if (ordenNumero === "" || ordenAnio === "") {
          mostrarError("Ingrese un número de Orden de Giro y año válidos.");
          return;
        }

        let formateado = formatearNumero(ordenNumero);
        inputNumero.value = formateado;
        let ordenGiroId = `${formateado}-${ordenAnio}`;

        let btnBuscar = document.getElementById("btnBuscarOrden");
        let spinnerBuscar = document.getElementById("spinnerBuscar");
        let iconBuscar = document.getElementById("iconBuscar");
        let textBuscar = document.getElementById("textBuscar");

        if (btnBuscar) btnBuscar.disabled = true;
        if (spinnerBuscar) spinnerBuscar.classList.remove("d-none");
        if (iconBuscar) iconBuscar.classList.add("d-none");
        if (textBuscar) textBuscar.textContent = "Buscando...";

        fetch("../../controller/ordengiro.php?op=get_orden_giro", {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: "ogciud_id=" + encodeURIComponent(ordenGiroId)
        })
          .then(response => response.json())
          .then(data => {
            if (data.status === "success" && data.data) {
              let orden = data.data;
              let estadoData = getEstado(orden.orden_est);

              document.getElementById("ogciud_id").textContent = orden.ogciud_id;
              document.getElementById("fecha").textContent = orden.fecha;
              document.getElementById("hora").textContent = orden.hora;
              document.getElementById("nombre_girador").textContent = orden.nombre_girador;
              document.getElementById("ciud_nombre").textContent = orden.ciud_nombre;
              document.getElementById("ciudadano_doc").textContent = orden.ciudadano_doc;
              document.getElementById("proced_nom").textContent = orden.proced_nom;
              document.getElementById("importe").textContent = "S/ " + parseFloat(orden.importe).toFixed(2);
              document.getElementById("estado").innerHTML = `<span class="muni-status-pill ${estadoData.pill_class} badge bg-${estadoData.color_barra}-lt">${estadoData.estado}</span>`;

              currentPdfBase64 = orden.pdf_base64 || "";
              let openPdfBtn = document.getElementById("openPdfBtn");
              if (currentPdfBase64 && currentPdfBase64.startsWith("JVBERi")) {
                if (window.currentPdfBlobUrl) {
                  URL.revokeObjectURL(window.currentPdfBlobUrl);
                }
                var blob = base64ToBlob(currentPdfBase64, 'application/pdf');
                window.currentPdfBlobUrl = URL.createObjectURL(blob);
                document.getElementById("ordenGiroPdf").src = window.currentPdfBlobUrl;
                if (openPdfBtn) openPdfBtn.classList.remove("disabled");
              } else {
                currentPdfBase64 = "";
                if (window.currentPdfBlobUrl) {
                  URL.revokeObjectURL(window.currentPdfBlobUrl);
                  window.currentPdfBlobUrl = null;
                }
                document.getElementById("ordenGiroPdf").src = "about:blank";
                if (openPdfBtn) openPdfBtn.classList.add("disabled");
              }

              mostrarResultados();
            } else {
              mostrarError(data.message || "No se encontró la Orden de Giro especificada.");
            }
          })
          .catch(error => {
            console.error("Error en fetch:", error);
            mostrarError("Ocurrió un error al procesar la búsqueda.");
          })
          .finally(() => {
            if (btnBuscar) btnBuscar.disabled = false;
            if (spinnerBuscar) spinnerBuscar.classList.add("d-none");
            if (iconBuscar) iconBuscar.classList.remove("d-none");
            if (textBuscar) textBuscar.textContent = "BUSCAR";
          });
      });

      document.getElementById("openPdfBtn").addEventListener("click", function() {
        if (!window.currentPdfBlobUrl && !currentPdfBase64) return;
        if (!window.currentPdfBlobUrl) {
          var blob = base64ToBlob(currentPdfBase64, 'application/pdf');
          window.currentPdfBlobUrl = URL.createObjectURL(blob);
        }
        window.open(window.currentPdfBlobUrl, '_blank');
      });

      function base64ToBlob(base64, mimeType) {
        var byteCharacters = atob(base64);
        var byteNumbers = new Array(byteCharacters.length);
        for (var i = 0; i < byteCharacters.length; i++) {
          byteNumbers[i] = byteCharacters.charCodeAt(i);
        }
        var byteArray = new Uint8Array(byteNumbers);
        return new Blob([byteArray], { type: mimeType });
      }

      function mostrarResultados() {
        document.getElementById("ordenGiroInfo").classList.remove("d-none");
        document.getElementById("errorMessage").classList.add("d-none");
      }

      function mostrarError(mensaje) {
        let errorDiv = document.getElementById("errorMessage");
        errorDiv.textContent = mensaje;
        errorDiv.classList.remove("d-none");
        document.getElementById("ordenGiroInfo").classList.add("d-none");
      }

      function getEstado(est) {
        let estado, color_barra, pill_class;
        switch (parseInt(est)) {
          case 0:
            estado = "Anulado";
            color_barra = "danger";
            pill_class = "status-danger";
            break;
          case 1:
            estado = "Pendiente";
            color_barra = "warning";
            pill_class = "status-warning";
            break;
          case 2:
            estado = "Girado";
            color_barra = "success";
            pill_class = "status-active";
            break;
          case 3:
            estado = "Improcedente";
            color_barra = "danger";
            pill_class = "status-danger";
            break;
          case 4:
            estado = "Pagado";
            color_barra = "success";
            pill_class = "status-active";
            break;
          case 5:
            estado = "Usado";
            color_barra = "info";
            pill_class = "status-info";
            break;
          default:
            estado = "Extornado";
            color_barra = "danger";
            pill_class = "status-danger";
            break;
        }
        return { estado, color_barra, pill_class };
      }
    </script>
  </body>

  </html>
<?php
} else {
  header("Location:" . Conectar::ruta() . "view/404/");
}
?>
