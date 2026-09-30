<?php
/* Llamamos al archivo de conexion.php */
require_once("../../config/conexion.php");

if (isset($_SESSION["usua_id_SIGODT"])) {
    ?>
    <!DOCTYPE html>
    <html lang="es">

    <head>
        <?php require_once("../html/mainHead.php"); ?>
        <title>MPCH::Órdenes de Giro del Mes</title>
        <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
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
                                    <a href="#">Consultar Mensual</a>
                                </li>
                            </ol>
                            <h2 class="page-title" style="margin: 20px;">
                                <span class="text-truncate"> Consulta Órdenes de Giro por Número de Documento.</span>
                            </h2>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="card">
                                        <div class="card-body">
                                            <!-- Buscador -->
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <input type="text" id="searchInput" class="form-control"
                                                        placeholder="Buscar...">
                                                </div>
                                            </div>

                                            <table class="table card-table table-striped table-hover">
                                                <thead class="table-dark">
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Orden de Giro</th>
                                                        <th>Fecha</th>
                                                        <th>Hora</th>
                                                        <th>Ciudadano</th>
                                                        <th>Documento</th>
                                                        <th>Procedimiento</th>
                                                        <th>Importe</th>
                                                        <th>Acciones</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="ordenesMesList" style="font-size: 12px;">
                                                    <!-- Aquí se llenarán los datos dinámicamente -->
                                                </tbody>
                                            </table>
                                            <!-- Paginación -->
                                            <ul class="pagination justify-content-end" id="pagination"
                                                style="margin: 20px;"></ul>


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
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
        <script>
            function imprimirGiro(ogciud_id) {
                redirect_by_post(
                    "https://www.munichiclayo.gob.pe/SIGODT/controller/rc.php?op=imprimirxid",
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

                            data.data.forEach((orden, index) => {
                                let estadoData = getEstado(orden.orden_est);
                                tbody.innerHTML += `<tr>
                                    <td>${(page - 1) * limit + index + 1}</td>
                                    <td>${orden.ogciud_id}</td>
                                    <td>${orden.fecha}</td>
                                    <td>${orden.hora}</td>
                                    <td>${orden.ciud_nombre}</td>
                                    <td>${orden.ciudadano_doc}</td>
                                    <td>${orden.proced_nom}</td>
                                    <td>S/ ${orden.importe}</td>
                                    <td><span class="badge bg-${estadoData.color_barra} text-${estadoData.color_barra}-fg ">${estadoData.estado}</span></td>
                                    <td><button class="btn btn-info btn-sm" onclick="imprimirGiro('${orden.ogciud_id}')">Ver</button></td>
                                </tr>`;
                            });

                            totalPages = Math.ceil(data.total / limit);
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
                                    <a class="page-link page-nav" href="#" data-page="${currentPage - 1}">Prev</a>
                                </li>`;

                        for (let i = 1; i <= totalPages; i++) {
                            let activeClass = i === currentPage ? "active" : "";
                            pagination.innerHTML += `
                                    <li class="page-item ${activeClass}">
                                        <a class="page-link page-num" href="#" data-page="${i}">${i}</a>
                                    </li>`;
                        }

                        pagination.innerHTML += `
                                <li class="page-item ${nextDisabled}">
                                    <a class="page-link page-nav" href="#" data-page="${currentPage + 1}">Next</a>
                                </li>`;
                    }

                    // Capturar eventos dinámicos para paginación
                    document.querySelectorAll(".page-link").forEach(link => {
                        link.addEventListener("click", function (event) {
                            event.preventDefault();
                            let newPage = parseInt(this.getAttribute("data-page"));
                            if (newPage >= 1 && newPage <= totalPages) {
                                fetchOrdenes(newPage, document.getElementById("searchInput").value);
                            }
                        });
                    });

                }

                // ✅ Escuchar cambios en el input y hacer la búsqueda en tiempo real
                document.getElementById("searchInput").addEventListener("input", function () {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => {
                        fetchOrdenes(1, this.value);
                    }, 500); // ⏳ Retraso de 500ms para evitar spam de solicitudes
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