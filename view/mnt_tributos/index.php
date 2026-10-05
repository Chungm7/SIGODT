<!-- index.php -->
<?php
require_once("../../config/conexion.php");
if (isset($_SESSION["usua_id_SIGODT"])) {
?>
    <!DOCTYPE html>
    <html lang="es">

    <head>
        <?php require_once("../html/mainHead.php"); ?>
        <title>SIGODT::Gestión de Tributos y Tasas por Procedimiento</title>
    </head>

    <body>
        <script src="../../public/tabler/js/demo-theme.min.js"></script>
        <div class="page">
            <div class="wrapper">
                <?php
                require_once("../html/mainProfile.php");
                require_once("../html/menu.php");
                ?>
                <div class="page-wrapper">
                    <div class="page-body">
                        <div class="container-xl">

                            <!-- Encabezado con breadcrumbs y botón de acción -->
                            <div class="page-header d-print-none mb-3">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <ol class="breadcrumb breadcrumb-arrows mb-3" aria-label="breadcrumbs">
                                            <li class="breadcrumb-item"><a href="../inicio/">SIGODT</a></li>
                                            <li class="breadcrumb-item"><a href="#">Mantenimientos</a></li>
                                            <li class="breadcrumb-item active" aria-current="page">Tributos</li>
                                        </ol>
                                        <h2 class="page-title">Asignación de Tributos y Tasas</h2>
                                        <div class="text-muted mt-2">Configuración de conceptos arancelarios, importes y códigos contables por procedimiento</div>
                                    </div>
                                    <div class="col-auto ms-auto d-print-none">
                                        <button id="botonRegistrarNuevo" onclick="nuevo()" class="btn btn-primary d-inline-flex align-items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-plus" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                <line x1="12" y1="5" x2="12" y2="19" />
                                                <line x1="5" y1="12" x2="19" y2="12" />
                                            </svg>
                                            <span>Asignar Tasa</span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Card de filtros -->
                            <div class="card shadow-sm mb-3">
                                <div class="card-body">
                                    <div class="row g-3 align-items-end">
                                        <div class="col-md-3">
                                            <label for="tupa_id" class="form-label fw-semibold">Documento / TUPA <span class="text-danger">*</span></label>
                                            <select class="form-select select2" name="tupa_id" id="tupa_id" data-placeholder="Seleccione Documento">
                                                <option value="">Seleccione</option>
                                            </select>
                                        </div>

                                        <div class="col-md-4">
                                            <label for="area_id" class="form-label fw-semibold">Área o Gerencia <span class="text-danger">*</span></label>
                                            <select class="form-select select2" name="area_id" id="area_id" data-placeholder="Seleccione Área">
                                                <option value="">Seleccione</option>
                                            </select>
                                        </div>

                                        <div class="col-md-5">
                                            <label for="proced_id" class="form-label fw-semibold">Procedimiento <span class="text-danger">*</span></label>
                                            <select class="form-select select2" name="proced_id" id="proced_id" data-placeholder="Seleccione Procedimiento">
                                                <option value="">Seleccione</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Card de tabla -->
                            <div class="card shadow-sm">
                                <div class="card-header">
                                    <h3 class="card-title">Tasas Configuradas para el Procedimiento</h3>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table id="detalle_data" class="table table-vcenter table-striped table-hover mb-0" style="width:100%">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Tasa / Concepto</th>
                                                    <th>Descripción</th>
                                                    <th class="w-1 text-center">N° Orden</th>
                                                    <th class="w-10 text-end">Monto</th>
                                                    <th class="w-10 text-center">Código Referencia</th>
                                                    <th class="w-1 text-center">Multiplica</th>
                                                    <th class="w-1 text-center">Editar</th>
                                                    <th class="w-1 text-center">Eliminar</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Contenido dinámico -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <?php require_once("../html/footer.php"); ?>
                </div>
            </div>
        </div>

        <?php require_once("modalmantenimiento.php"); ?>
        <?php require_once("modaltasamonto.php"); ?>
        <?php require_once("../html/mainjs.php"); ?>
        <script src="main.js"></script>
    </body>

    </html>
<?php
} else {
    header("Location:" . Conectar::ruta() . "view/404/");
}
?>