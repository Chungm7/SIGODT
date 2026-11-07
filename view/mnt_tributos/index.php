<!-- index.php -->
<?php
require_once("../../config/conexion.php");
if (Conectar::esAdministrador()) {
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
                <?php
                require_once("../html/mainProfile.php");
                require_once("../html/menu.php");
                ?>
                <div class="page-wrapper">
                    <div class="page-body">
                        <div class="container-xl">

                            <!-- Breadcrumb Contextual -->
                            <div class="mpch-subsys-breadcrumb">
                                <a href="../inicio/" class="mpch-bc-sys text-decoration-none" title="Ir al Inicio">SIGODT</a>
                                <span class="mpch-bc-sep">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 6l6 6l-6 6" /></svg>
                                </span>
                                <span class="mpch-bc-sec">Mantenimientos</span>
                                <span class="mpch-bc-sep">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 6l6 6l-6 6" /></svg>
                                </span>
                                <span class="mpch-bc-cur">Tributos</span>
                            </div>

                            <!-- Encabezado de la Página (Título y Descripción) -->
                            <div class="page-header page-header-context d-print-none mb-3">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <h2 class="page-title text-uppercase">Asignación de Tributos y Tasas</h2>
                                        <div class="text-secondary mt-1" style="font-size: 14px;">Configuración de conceptos arancelarios, importes y códigos contables por procedimiento</div>
                                    </div>
                                    <div class="col-auto ms-auto d-print-none">
                                        <button id="botonRegistrarNuevo" onclick="nuevo()" class="btn btn-muni-action">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-plus" width="20" height="20" viewBox="0 0 24 24" stroke-width="2.25" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                <line x1="12" y1="5" x2="12" y2="19" />
                                                <line x1="5" y1="12" x2="19" y2="12" />
                                                </svg>
                                            <span>ASIGNAR TASA</span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Card de filtros -->
                            <div class="card shadow-sm muni-stripe-top mb-3">
                                <div class="card-header py-2">
                                    <h3 class="card-title fw-bold mb-0">Filtros de consulta</h3>
                                </div>
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
        <script src="main.js?v=<?php echo filemtime('main.js'); ?>"></script>
    </body>

    </html>
<?php
} else {
    header("Location:" . Conectar::ruta() . "view/404/");
    exit();
}
?>