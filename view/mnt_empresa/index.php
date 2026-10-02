<!-- index.php -->
<?php
require_once("../../config/conexion.php");
if (isset($_SESSION["usua_id_SIGODT"])) {
?>
    <!DOCTYPE html>
    <html lang="es">

    <head>
        <?php require_once("../html/mainHead.php"); ?>
        <title>SIGODT::Gestión de Empresas</title>
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
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="../inicio/">SIGODT</a></li>
                                <li class="breadcrumb-item"><a href="#">Mantenimientos</a></li>
                                <li class="breadcrumb-item active"><a href="#">Empresas</a></li>
                            </ol>
                            <h2 class="page-title my-3">Gestión de Empresas</h2>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="card">
                                        <div class="card-body text-center">
                                            <h4 class="card-title">Registrar</h4>
                                            <p class="text-muted">Agrega una nueva empresa.</p>
                                            <button class="btn btn-primary"
                                                data-bs-toggle="modal"
                                                data-bs-target="#empresaModal"
                                                onclick="nuevoRegistro()">
                                                <i class="fas fa-plus-square"></i> Nuevo Registro
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card mt-3">
                                        <div class="card-body">
                                            <h4 class="card-title text-center"><i class="fas fa-filter"></i> Filtros</h4>
                                            <form id="filterForm">
                                                <div class="mb-3">
                                                    <label class="form-label">Estado:</label>
                                                    <select class="form-select" id="select-estado">
                                                        <option value="A">Activos</option>
                                                        <option value="I">Inactivos</option>
                                                    </select>
                                                </div>
                                                <div class="text-center d-flex justify-content-center gap-2">
                                                    <a href="#" id="filterBtn" class="btn btn-blue">Filtrar</a>
                                                    <a href="#" id="resetBtn" class="btn btn-danger">Limpiar</a>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-9">
                                    <div class="card">
                                        <div class="card-body">
                                            <h4 class="card-title">Lista de Empresas</h4>
                                            <div class="row mb-3">
                                                <div class="col-md-12">
                                                    <div class="input-group">
                                                        <input type="text" id="search" class="form-control" placeholder="Buscar...">
                                                        <button class="btn btn-primary" type="button" id="searchBtn">
                                                            <i class="fas fa-search"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="table-responsive">
                                                <table id="tabla-empresa" class="table table-striped table-bordered" style="width:100%;">
                                                    <thead>
                                                        <tr>
                                                            <th>ID</th>
                                                            <th>RUC</th>
                                                            <th>Razón Social</th>
                                                            <th>Nombre Comercial</th>
                                                            <th>Dirección</th>
                                                            <th>Estado</th>
                                                            <th>Acción</th>
                                                        </tr>
                                                    </thead>
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

        <!-- Modal Registrar/Editar Empresa -->
        <div class="modal fade" id="empresaModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modal-title">Registrar Empresa</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="empresaForm">
                            <input type="hidden" id="empr_id" name="empr_id">
                            <div class="row g-3">

                                <div class="col-md-6">
                                    <label class="form-label">RUC</label>
                                    <input
                                        type="text"
                                        id="empr_ruc"
                                        name="empr_ruc"
                                        class="form-control"
                                        required
                                        oninput="this.value = this.value.replace(/\\D/g, '').slice(0,11)">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Razón Social</label>
                                    <input type="text" id="empr_razon_social" name="empr_razon_social" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Nombre Comercial</label>
                                    <input type="text" id="empr_nombre_comercial" name="empr_nombre_comercial" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Dirección</label>
                                    <input type="text" id="empr_direccion" name="empr_direccion" class="form-control">
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-success" id="btnGuardarEmpresa" onclick="guardar()">Guardar</button>
                    </div>
                </div>
            </div>
        </div>

        <?php require_once("../html/mainjs.php"); ?>
        <script src="empresa.js?v=<?php echo filemtime('empresa.js'); ?>"></script>
    </body>

    </html>
<?php
} else {
    header("Location:" . Conectar::ruta() . "view/404/");
}
?>