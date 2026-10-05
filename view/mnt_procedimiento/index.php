<!-- index.php -->
<?php
require_once("../../config/conexion.php");
if (isset($_SESSION["usua_id_SIGODT"])) {
?>
    <!DOCTYPE html>
    <html lang="es">

    <head>
        <?php require_once("../html/mainHead.php"); ?>
        <title>SIGODT::Gestión de Procedimientos</title>
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

                            <div class="page-header d-print-none mb-3">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <ol class="breadcrumb breadcrumb-arrows mb-3" aria-label="breadcrumbs">
                                            <li class="breadcrumb-item"><a href="../inicio/">SIGODT</a></li>
                                            <li class="breadcrumb-item"><a href="#">Mantenimientos</a></li>
                                            <li class="breadcrumb-item active" aria-current="page">Procedimientos</li>
                                        </ol>
                                        <h2 class="page-title">Gestión de Procedimientos</h2>
                                        <div class="text-muted mt-2">Catálogo oficial de procedimientos administrativos y servicios prestados en exclusividad</div>
                                    </div>
                                    <div class="col-auto ms-auto d-print-none">
                                        <button id="btnnuevoproced" class="btn btn-primary d-inline-flex align-items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-plus" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                <line x1="12" y1="5" x2="12" y2="19" />
                                                <line x1="5" y1="12" x2="19" y2="12" />
                                            </svg>
                                            <span>Nuevo Procedimiento</span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="card shadow-sm mb-3">
                                <div class="card-body">
                                    <div class="row g-3 align-items-end">
                                        <div class="col-md-4">
                                            <label class="form-label fw-semibold" for="tupa_id">Documento / TUPA <span class="text-danger">*</span></label>
                                            <select class="form-select select2" name="tupa_id" id="tupa_id" data-placeholder="Seleccione Documento">
                                                <option label="Seleccione"></option>
                                            </select>
                                        </div>
                                        <div class="col-md-8">
                                            <label class="form-label fw-semibold" for="area_id">Área o Gerencia <span class="text-danger">*</span></label>
                                            <select class="form-select select2" name="area_id" id="area_id" data-placeholder="Seleccione Área">
                                                <option label="Seleccione"></option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card shadow-sm">
                                <div class="card-header">
                                    <h3 class="card-title">Listado de Procedimientos</h3>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table id="proceds_data" class="table table-vcenter table-striped table-hover mb-0" style="width:100%">
                                            <thead class="table-light">
                                                <tr>
                                                    <th class="w-1">ID</th>
                                                    <th>Código</th>
                                                    <th>Procedimiento</th>
                                                    <th>Fecha Creación</th>
                                                    <th>Tipo de Pago</th>
                                                    <th>Administrado</th>
                                                    <th>Individuo</th>
                                                    <th class="w-1 text-center">Editar</th>
                                                    <th class="w-1 text-center">Eliminar</th>
                                                </tr>
                                            </thead>
                                            <tbody></tbody>
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

        <?php require_once("modalmantenimiento.php"); ?>
        <?php require_once("../html/mainjs.php"); ?>
        <script src="main.js"></script>
    </body>

    </html>
<?php
} else {
    header("Location:" . Conectar::ruta() . "view/404/");
}
?>