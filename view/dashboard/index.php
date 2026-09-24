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
                    :root {
                        margin-left: 0 !important;
                        margin-right: 0 !important;
                    }

                    :host {
                        margin-left: 0 !important;
                        margin-right: 0 !important;
                    }

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
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="../inicio/">SIGODT</a></li>
                                <li class="breadcrumb-item active">Dashboard</li>
                            </ol>
                            <h2 class="page-title mb-3">
                                <span class="text-truncate">Dashboard de Gestión y Recaudación</span>
                            </h2>

                            <div class="row mb-3">
                                <!-- Filtros de fecha -->
                                <div class="col-md-2">
                                    <input type="date" class="form-control" id="fechaIni">
                                </div>
                                <div class="col-md-2">
                                    <input type="date" class="form-control" id="fechaFin">
                                </div>
                                <div class="col-md-2">
                                    <button class="btn btn-primary" id="filtrarBtn"><i class="ti ti-search"></i> Filtrar</button>
                                </div>
                                <div class="col-md-6 d-flex align-items-center" style="border-left: 2px solid #066fd1;">
                                    <label class="me-2">Estados:</label>
                                    <div class="d-flex align-items-center gap-2" id="badgesEstados">
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
                                                <span class="icon bg-primary text-white avatar me-3"><i class="ti ti-cash"></i></span>
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
                                                <span class="icon bg-yellow text-white avatar me-3">
                                                    <i class="ti ti-clock-hour-3"></i>
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
                                                <span class="icon bg-green text-white avatar me-3"><i class="ti ti-archive"></i></span>
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
                                                <span class="icon bg-orange text-white avatar me-3"><i class="ti ti-list"></i></span>
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
                                                <span class="icon bg-red text-white avatar me-3"><i class="ti ti-users"></i></span>
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