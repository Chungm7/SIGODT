<!-- index.php -->
<?php
require_once("../../config/conexion.php");
if (Conectar::esAdministrador()) {
?>
    <!DOCTYPE html>
    <html lang="es">

    <head>
        <?php require_once("../html/mainHead.php"); ?>
        <title>SIGODT::Gestión de Tasas</title>
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
                            <div class="page-header d-print-none mb-3">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <ol class="breadcrumb breadcrumb-arrows mb-3" aria-label="breadcrumbs">
                                            <li class="breadcrumb-item"><a href="../inicio/">SIGODT</a></li>
                                            <li class="breadcrumb-item"><a href="#">Mantenimientos</a></li>
                                            <li class="breadcrumb-item active" aria-current="page">Tasas</li>
                                        </ol>
                                        <h2 class="page-title">Gestión de Tasas</h2>
                                        <div class="text-muted mt-2">Catálogo maestro de tasas y conceptos tributarios de trámite</div>
                                    </div>
                                    <div class="col-auto ms-auto d-print-none">
                                        <button id="add_button" onclick="nuevo()" class="btn btn-muni-action">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-plus" width="20" height="20" viewBox="0 0 24 24" stroke-width="2.25" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                <line x1="12" y1="5" x2="12" y2="19" />
                                                <line x1="5" y1="12" x2="19" y2="12" />
                                            </svg>
                                            <span>NUEVA TASA</span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Card de Búsqueda -->
                            <div class="card shadow-sm muni-stripe-top mb-3">
                                <div class="card-header py-2">
                                    <h3 class="card-title fw-bold mb-0">Criterio de Búsqueda</h3>
                                </div>
                                <div class="card-body">
                                    <form id="formSearchTasa">
                                        <div class="row g-2 align-items-center">
                                            <div class="col">
                                                <div class="input-group">
                                                    <span class="input-group-text">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-coin" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                            <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" />
                                                            <path d="M14.8 9a2 2 0 0 0 -1.8 -1h-2a2 2 0 1 0 0 4h2a2 2 0 1 1 0 4h-2a2 2 0 0 1 -1.8 -1" />
                                                            <path d="M12 7v10" />
                                                        </svg>
                                                    </span>
                                                    <input type="text" id="searchTasa" class="form-control" placeholder="Buscar por denominación o tipo de cálculo..." aria-label="Buscar tasa">
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-auto">
                                                <button class="btn btn-muni-action btn-search-action w-100" type="submit" id="btnBuscarTasa">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-search me-1" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                        <path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" />
                                                        <path d="M21 21l-6 -6" />
                                                    </svg>
                                                    <span>BUSCAR</span>
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- Card de Resultados -->
                            <div class="card shadow-sm mb-3">
                                <div class="card-header py-2">
                                    <h3 class="card-title fw-bold mb-0">Listado de Tasas Registradas</h3>
                                </div>
                                <div class="table-responsive">
                                    <table id="tasa_data" class="table table-vcenter table-striped table-hover mb-0" style="width:100%">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Denominación de la Tasa</th>
                                                <th class="w-15">Tipo de Cálculo</th>
                                                <th class="w-1 text-center">Editar</th>
                                                <th class="w-1 text-center">Eliminar</th>
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
    exit();
}
?>