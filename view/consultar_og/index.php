<?php
require_once("../../config/conexion.php");

if (isset($_SESSION["usua_id_SIGODT"])) {
?>
    <!DOCTYPE html>
    <html lang="es">

    <head>
        <?php require_once("../html/mainHead.php"); ?>
        <title>MPCH::Consultar Orden de Giro</title>
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
                                <li class="breadcrumb-item"><a href="../inicio/">Reportes</a></li>
                                <li class="breadcrumb-item active"><a href="#">Consultar por Número de Orden</a></li>
                            </ol>
                            
                            <!-- Formulario -->
                            <div class="row justify-content-center mt-4">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-body">
                                            <h4 class="card-title text-center">Ingrese los datos de la Orden de Giro</h4>
                                            
                                            <form id="ordenGiroForm">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <label for="ordenNumero">Número de Orden</label>
                                                        <input type="text" id="ordenNumero" class="form-control text-center"
                                                            placeholder="Ej: 2616" >
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label for="ordenAnio">Año</label>
                                                        <input type="number" id="ordenAnio" class="form-control text-center"
                                                            value="<?php echo date('Y'); ?>" readonly>
                                                    </div>
                                                </div>
                                                <div class="text-center mt-3">
                                                    <button type="submit" id="btnBuscarOrden" class="btn btn-primary">
                                                        <span id="spinnerBuscar" class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
                                                        <i class="fas fa-search me-1" id="iconBuscar"></i><span id="textBuscar">Buscar</span>
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Resultados en dos columnas -->
                            <div class="row mt-4 d-none" id="ordenGiroInfo">
                                <!-- Columna de Datos -->
                                <div class="col-lg-6">
                                    <div class="card">
                                        <div class="card-body">
                                            <h4 class="card-title text-center">Detalles de la Orden de Giro</h4>
                                            <table class="table">
                                                <tbody>
                                                    <tr><th>ID Orden:</th> <td id="ogciud_id"></td></tr>
                                                    <tr><th>Fecha:</th> <td id="fecha"></td></tr>
                                                    <tr><th>Hora:</th> <td id="hora"></td></tr>
                                                    <tr><th>Nombre Girador:</th> <td id="nombre_girador"></td></tr>
                                                    <tr><th>Ciudadano:</th> <td id="ciud_nombre"></td></tr>
                                                    <tr><th>Documento:</th> <td id="ciudadano_doc"></td></tr>
                                                    <tr><th>Procedimiento:</th> <td id="proced_nom"></td></tr>
                                                    <tr><th>Importe:</th> <td id="importe"></td></tr>
                                                    <tr><th>Estado:</th> <td id="estado"></td></tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <!-- Columna de PDF -->
                                <div class="col-lg-6">
                                    <div class="card">
                                        <div class="card-body">
                                            <h4 class="card-title text-center">Vista previa del documento</h4>
                                            <iframe id="ordenGiroPdf" class="w-100" height="500px"></iframe>
                                            <div class="text-center mt-3">
                                                <button class="btn btn-info" id="openPdfBtn">Abrir PDF</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div id="errorMessage" class="alert alert-danger d-none text-center mt-3"></div>
                        </div>
                    </div>
                    <?php require_once("../html/footer.php"); ?>
                </div>
            </div>
        </div>

        <?php require_once("../html/mainjs.php"); ?>
        <script>
            function formatearNumeroOrden() {
                let input = document.getElementById("ordenNumero");
                let valor = input.value.replace(/\D/g, ''); // Elimina caracteres no numéricos
                valor = valor.padStart(6, "0"); // Completa con ceros a la izquierda
                input.value = valor;
            }
            function formatearNumero(value) {
                let valor = value.replace(/\D/g, ''); // Elimina caracteres no numéricos
                valor = valor.padStart(6, "0"); // Completa con ceros a la izquierda
                return valor;
            }

            document.getElementById("ordenGiroForm").addEventListener("submit", function (event) {
                event.preventDefault(); // Evita recarga de página
                formatearNumeroOrden()
                let ordenNumero = document.getElementById("ordenNumero").value.trim();
                let ordenAnio = document.getElementById("ordenAnio").value.trim();

                if (ordenNumero === "" || ordenAnio === "") {
                    mostrarError("Ingrese un número de Orden de Giro válido.");
                    return;
                }

                let ordenGiroId = `${formatearNumero(ordenNumero)}-${ordenAnio}`;
                console.log("🔍 Buscando orden:", ordenGiroId);

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
                    
                    if (data.status === "success") {
                        let orden = data.data;
                        let estadoData = getEstado(orden.orden_est);

                        document.getElementById("ogciud_id").textContent = orden.ogciud_id;
                        document.getElementById("fecha").textContent = orden.fecha;
                        document.getElementById("hora").textContent = orden.hora;
                        document.getElementById("nombre_girador").textContent = orden.nombre_girador;
                        document.getElementById("ciud_nombre").textContent = orden.ciud_nombre;
                        document.getElementById("ciudadano_doc").textContent = orden.ciudadano_doc;
                        document.getElementById("proced_nom").textContent = orden.proced_nom;
                        document.getElementById("importe").textContent = "S/ " + orden.importe;
                        document.getElementById("estado").innerHTML = `<span class="badge bg-${estadoData.color_barra} text-${estadoData.color_barra}-fg ">${estadoData.estado}</span>`;

                        let pdfBase64 = orden.pdf_base64;
                        let pdfUrl = "data:application/pdf;base64," + pdfBase64;
                        document.getElementById("ordenGiroPdf").src = pdfUrl;

                        mostrarResultados();
                    } else {
                        mostrarError(data.message);
                    }
                })
                .catch(error => {
                    console.error("🚨 Error en fetch:", error);
                    mostrarError("Ocurrió un error en la búsqueda.");
                })
                .finally(() => {
                    if (btnBuscar) btnBuscar.disabled = false;
                    if (spinnerBuscar) spinnerBuscar.classList.add("d-none");
                    if (iconBuscar) iconBuscar.classList.remove("d-none");
                    if (textBuscar) textBuscar.textContent = "Buscar";
                });
            });

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
                let estado, color_barra;
                switch (parseInt(est)) {
                case 0:
                    estado = "Anulado";
                    color_barra = "danger";
                    break;
                case 1:
/*                     estado = "Pendiente";
                    color_barra = "warning";
                    break; */
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
<?php } else {
    header("Location:" . Conectar::ruta() . "view/404/");
} ?>
