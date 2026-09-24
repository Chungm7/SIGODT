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

                            <!-- Encabezado -->
                            <div class="page-header">
                                <div>
                                    <ol class="breadcrumb breadcrumb-arrow text-muted mb-1">
                                        <li class="breadcrumb-item"><a href="../inicio/">SIGODT</a></li>
                                        <li class="breadcrumb-item"><a href="#">Mantenimientos</a></li>
                                        <li class="breadcrumb-item active">Tributos</li>
                                    </ol>
                                    <h2 class="page-title">Gestión de Tributos</h2>
                                </div>
                            </div>

                            <!-- Card principal -->
                            <div class="card shadow-sm">
                                <div class="card-body">

                                    <!-- Filtros y botón -->
                                    <div class="row g-4 align-items-end">
                                        <div class="col-md-3">
                                            <label for="tupa_id" class="form-label">TUPA <span class="text-danger">*</span></label>
                                            <select class="form-select select2" name="tupa_id" id="tupa_id" data-placeholder="Seleccione">
                                                <option value="">Seleccione</option>
                                            </select>
                                        </div>

                                        <div class="col-md-5">
                                            <label for="area_id" class="form-label">Área <span class="text-danger">*</span></label>
                                            <select class="form-select select2" name="area_id" id="area_id" data-placeholder="Seleccione">
                                                <option value="">Seleccione</option>
                                            </select>
                                        </div>

                                        <div class="col-md-4">
                                            <label for="proced_id" class="form-label">Procedimiento <span class="text-danger">*</span></label>
                                            <select class="form-select select2" name="proced_id" id="proced_id" data-placeholder="Seleccione">
                                                <option value="">Seleccione</option>
                                            </select>
                                        </div>

                                        <div class="col-12 text-end">
                                            <button id="botonRegistrarNuevo" onclick="nuevo()" class="btn btn-outline-primary">
                                                <i class="fa fa-plus me-1"></i> Agregar Registro
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Tabla de tributos -->
                                    <div class="table-responsive mt-5">
                                        <table id="detalle_data" class="table table-bordered table-hover text-center align-middle">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Tasa-Categoría</th>
                                                    <th>Descripcion</th>
                                                    <th>N° Orden</th>
                                                    <th>Monto</th>
                                                    <th>Código Referencia</th>
                                                    <th>Multiplica?</th>
                                                    <th></th>
                                                    <th></th>
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


                </div>
            </div>

            <?php require_once("../html/footer.php"); ?>
        </div>


        <?php require_once("modalmantenimiento.php"); ?>
        <?php require_once("modaltasamonto.php"); ?>




        <?php require_once("../html/mainjs.php"); ?>
        <script src="main.js"></script>

        <script>
            // Lógica para mostrar/ocultar el campo multiplicador y la descripción cuando el checkbox esté marcado
            $("#multiplicaCheckbox").on("change", function() {
                if ($(this).prop("checked")) {
                 
                    $("#multiplicaDescription").show();
                } else {
               
                    $("#multiplicaDescription").hide();
                }
            });
        </script>
    </body>

    </html>
<?php
} else {
    header("Location:" . Conectar::ruta() . "view/404/");
}
?>