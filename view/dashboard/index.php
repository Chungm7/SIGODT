<?php
require_once("../../config/conexion.php");
if (isset($_SESSION["usua_id_SIGODT"])) {
?>
    <!DOCTYPE html>
    <html lang="es">

    <head>
        <?php require_once("../html/mainHead.php"); ?>
        <title>Dashboard de Recaudación y Procedimientos</title>
    </head>

    <body>


        <div class="page">
            <div class="wrapper">
                <?php require_once("../html/mainProfile.php"); ?>
                <?php require_once("../html/menu.php"); ?>
                <style>
                    .badge-estado {
                        transition: background 0.15s, color 0.15s;
                        border: 1px solid transparent;
                        user-select: none;
                    }

                    .badge-estado:hover,
                    .badge-estado:focus {
                        filter: brightness(0.90);
                        border: 1px solid #495057;
                        cursor: pointer;
                    }

                    .badge-estado.active {
                        box-shadow: 0 0 0 2px #228be6 inset;
                        border: 1px solid #228be6;
                        font-weight: 600;
                        filter: none;
                    }
                </style>
                <div class="page-wrapper">
                    <div class="page-body">
                        <div class="container-xl">

                            <!-- Encabezado con breadcrumbs -->
                            <div class="page-header d-print-none mb-3">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <ol class="breadcrumb breadcrumb-arrows mb-3" aria-label="breadcrumbs">
                                            <li class="breadcrumb-item"><a href="../inicio/">SIGODT</a></li>
                                            <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
                                        </ol>
                                        <h2 class="page-title">Dashboard de Gestión y Recaudación</h2>
                                        <div class="text-secondary mt-2">Métricas globales, recaudación y estadísticas operativas de órdenes de giro</div>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3 align-items-center mb-3">
                                <!-- Filtros de fecha -->
                                <div class="col-12 col-sm-6 col-md-2">
                                    <input type="date" class="form-control" id="fechaIni">
                                </div>
                                <div class="col-12 col-sm-6 col-md-2">
                                    <input type="date" class="form-control" id="fechaFin">
                                </div>
                                <div class="col-12 col-sm-6 col-md-2">
                                    <button class="btn btn-primary d-inline-flex align-items-center w-100" id="filtrarBtn">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-search me-1" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" />
                                            <path d="M21 21l-6 -6" />
                                        </svg>
                                        <span>Filtrar</span>
                                    </button>
                                </div>
                                <div class="col-12 col-md-6 d-flex flex-wrap align-items-center ps-md-3 border-start-md">
                                    <label class="me-2 fw-bold text-secondary">Estados:</label>
                                    <div class="d-flex flex-wrap align-items-center gap-1" id="badgesEstados">
                                        <span class="badge bg-secondary-lt p-2 cursor-pointer badge-estado active" data-value="all">Todos</span>
                                        <span class="badge bg-danger-lt p-2 cursor-pointer badge-estado active" data-value="0">Anulado</span>
                                        <span class="badge bg-info-lt p-2 cursor-pointer badge-estado active" data-value="1">Girado</span>
                                        <span class="badge bg-warning-lt p-2 cursor-pointer badge-estado active" data-value="3">Improcedente</span>
                                        <span class="badge bg-success-lt p-2 cursor-pointer badge-estado active" data-value="4">Pagado</span>
                                        <span class="badge bg-purple-lt p-2 cursor-pointer badge-estado active" data-value="5">Usado</span>
                                        <span class="badge bg-yellow-lt p-2 cursor-pointer badge-estado active" data-value="6">Extornado</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Grid cards principales -->
                            <div class="row g-2 mb-4">
                                <div class="col-sm-6 col-lg-3">
                                    <div class="card card-sm">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center">
                                                <span class="bg-primary text-white avatar me-3">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-coin" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                        <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" />
                                                        <path d="M14.8 9a2 2 0 0 0 -1.8 -1h-2a2 2 0 1 0 0 4h2a2 2 0 1 1 0 4h-2a2 2 0 0 1 -1.8 -1" />
                                                        <path d="M12 7v10" />
                                                    </svg>
                                                </span>
                                                <div>
                                                    <div class="fs-2 fw-bold" id="totalRecaudado">S/ --</div>
                                                    <div class="text-secondary">Total Recaudado</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-lg-3">
                                    <div class="card card-sm">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center">
                                                <span class="bg-warning text-white avatar me-3">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-clock" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                        <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" />
                                                        <path d="M12 7v5l3 3" />
                                                    </svg>
                                                </span>
                                                <div>
                                                    <div class="fs-2 fw-bold" id="pendientePorCobrar">S/ --</div>
                                                    <div class="text-secondary">Pendiente por Cobrar</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-sm-6 col-lg-2">
                                    <div class="card card-sm">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center">
                                                <span class="bg-success text-white avatar me-3">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-archive" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                        <path d="M3 4m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v0a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z" />
                                                        <path d="M5 8v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-10" />
                                                        <path d="M10 12l4 0" />
                                                    </svg>
                                                </span>
                                                <div>
                                                    <div class="fs-2 fw-bold" id="procedimientosIniciados">--</div>
                                                    <div class="text-secondary">Procedimientos</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-lg-2">
                                    <div class="card card-sm">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center">
                                                <span class="bg-orange text-white avatar me-3">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-receipt" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                        <path d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16l-3 -2l-2 2l-2 -2l-2 2l-2 -2l-3 2m4 -14h6m-6 4h6m-2 4h2" />
                                                    </svg>
                                                </span>
                                                <div>
                                                    <div class="fs-2 fw-bold" id="ordenesPagadas">--</div>
                                                    <div class="text-secondary">Órdenes Generadas</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-lg-2">
                                    <div class="card card-sm">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center">
                                                <span class="bg-danger text-white avatar me-3">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-users" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                        <path d="M9 7m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0" />
                                                        <path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" />
                                                        <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                                                        <path d="M21 21v-2a4 4 0 0 0 -3 -3.85" />
                                                    </svg>
                                                </span>
                                                <div>
                                                    <div class="fs-2 fw-bold" id="usuariosActivos">--</div>
                                                    <div class="text-secondary">Usuarios con Giros</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Grid gráficos -->
                            <div class="row g-2">
                                <div class="col-lg-12">
                                    <div class="card mt-4">
                                        <div class="card-header"><b>Reporte de Órdenes por Dependencia y Estado</b></div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-sm table-striped align-middle">
                                                    <thead>
                                                        <tr>
                                                            <th>Dependencia</th>
                                                            <th>Progreso</th>
                                                            <th>Total</th>
                                                            <th>Hoy</th>
                                                            <th>Semana</th>
                                                            <th>Resumen</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="tablaOrdenesDependencia"></tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Modal resumen -->
                                    <div class="modal fade" id="modalResumenDependencia" tabindex="-1">
                                        <div class="modal-dialog modal-xl">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="tituloModalResumen">Resumen del Área</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body" id="contenidoModalResumen">
                                                    <!-- Aquí se cargan los gráficos dinámicamente -->
                                                </div>
                                            </div>
                                        </div>
                                    </div>


                                </div>
                                <div class="col-md-6">
                                    <div class="card h-100">
                                        <div class="card-header"><b>Recaudación por Área</b></div>
                                        <div class="card-body">
                                            <div id="chartRecaudacionArea" style="height:320px"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card h-100">
                                        <div class="card-header"><b>Órdenes de Giro por Estado</b></div>
                                        <div class="card-body">
                                            <div id="chartOrdenesEstado" style="height:320px"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-12 mt-2">
                                    <div class="card h-100">
                                        <div class="card-header"><b>Evolución Mensual de Recaudación</b></div>
                                        <div class="card-body">
                                            <div id="chartEvolucionRecaudacion" style="height:320px"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-12 mt-2">
                                    <div class="card h-100">
                                        <div class="card-header"><b>Anulación por Errores por Usuario y Dependencia</b></div>
                                        <div class="card-body">
                                            <div id="chartErroresUD"></div>
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
        <!-- ApexCharts CDN -->
        <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
        <script src="dashboard.js"></script>
    </body>

    </html>
<?php
} else {
    header("Location:" . Conectar::ruta() . "view/404/");
}
?>