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
                            <!-- Encabezado con breadcrumb -->
                            <div class="page-header">
                                <div>
                                    <ol class="breadcrumb breadcrumb-arrow text-muted mb-1">
                                        <li class="breadcrumb-item"><a href="../inicio/">SIGODT</a></li>
                                        <li class="breadcrumb-item"><a href="#">Mantenimientos</a></li>
                                        <li class="breadcrumb-item active">Tasas</li>
                                    </ol>
                                    <h2 class="page-title">Gestión de Tasas</h2>
                                </div>

                            </div>

                            <!-- Tarjeta principal -->
                            <div class="card shadow-sm">
                                <div class="card-body border-top">
                                    <div style="display: flex; justify-content: flex-end;">
                                        <button id="add_button" onclick="nuevo()" class="btn btn-primary">
                                            <i class="fa fa-plus me-1"></i> Nuevo Registro
                                        </button>
                                    </div>
                                    <div class="table-responsive">
                                        <table id="tasa_data" class="table table-striped table-hover table-bordered mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width: 40%;">Tasa</th>
                                                    <th style="width: 40%;">Tipo</th>
                                                    <th style="width: 10%;"></th>
                                                    <th style="width: 10%;"></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Datos dinámicos -->
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