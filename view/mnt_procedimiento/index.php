<!-- index.php -->
<?php
require_once("../../config/conexion.php");
if (isset($_SESSION["usua_id_SIGODT"])) {
?>
    <!DOCTYPE html>
    <html lang="es">

    <head>
        <?php require_once("../html/mainHead.php"); ?>
        <title>SIGODT::Gestión de Procedimientoss</title>
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

                            <div class="border-bottom pb-3 mb-4 d-flex justify-content-between align-items-center">
                                <div>
                                    <ol class="breadcrumb breadcrumb-arrow text-muted">
                                        <li class="breadcrumb-item"><a href="../inicio/">SIGODT</a></li>
                                        <li class="breadcrumb-item"><a href="#">Mantenimientos</a></li>
                                        <li class="breadcrumb-item active">Procedimientos</li>
                                    </ol>
                                    <h1 class="page-title mt-1 mb-0">Gestión de Procedimientos</h1>
                                </div>

                            </div>
                            <div class="card">
                                <div class="card-header row" style="display: flex; align-items: flex-end;">

                                    <div class="col-md-3">
                                        <label class="form-label">TUPA <span class="text-danger">*</span></label>
                                        <select class="form-control select2" name="tupa_id" id="tupa_id" data-placeholder="Seleccione">
                                            <option label="Seleccione"></option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Área <span class="text-danger">*</span></label>
                                        <select class="form-control select2" name="area_id" id="area_id" data-placeholder="Seleccione">
                                            <option label="Seleccione"></option>
                                        </select>
                                    </div>
                                    <div class="col-md-3" style="display: flex;align-items: flex-end;">
                                        <button id="btnnuevoproced" class="btn btn-outline-success d-flex align-items-center">
                                            <i class="fa fa-plus me-2"></i> <span>Agregar Procedimientos</span>
                                        </button>
                                    </div>

                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table id="proceds_data" class="table table-striped table-bordered">
                                            <thead class="bg-light text-dark">
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Código</th>
                                                    <th>Procedimientos</th>
                                                    <th>Fecha Creación</th>
                                                    <th>Tipo</th>
                                                    <th>Administrado</th>
                                                    <th>Individuo</th>
                                                    <th>Editar</th>
                                                    <th>Eliminar</th>
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