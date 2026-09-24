<!-- index.php -->
<?php
require_once("../../config/conexion.php");
if (isset($_SESSION["usua_id_SIGODT"])) {
?>
    <!DOCTYPE html>
    <html lang="es">

    <head>
        <?php require_once("../html/mainHead.php"); ?>
        <title>SIGODT::Gestión de Ciudadanos</title>
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
                                <li class="breadcrumb-item active"><a href="#">Ciudadanos</a></li>
                            </ol>
                            <h2 class="page-title my-3">Gestión de Ciudadanos</h2>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="card">
                                        <div class="card-body text-center">
                                            <h4 class="card-title">Registrar</h4>
                                            <p class="text-muted">Agrega un nuevo ciudadano.</p>
                                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#registerModal" onclick="nuevoRegistro()">
                                                <i class="fas fa-plus-square"></i> Nuevo Registro
                                            </button>
                                        </div>
                                    </div>
                                   
                                </div>

                                <div class="col-md-9">
                                    <div class="card">
                                        <div class="card-body">
                                            <h4 class="card-title">Lista de Ciudadanos</h4>
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
                                                <table id="tabla-ciudadano" class="table table-striped table-bordered" style="width:100%;">
                                                    <thead>
                                                        <tr>
                                                            <th>ID</th>
                                                            <th>Tipo Doc</th>
                                                            <th>Documento</th>
                                                            <th>Apellido P.</th>
                                                            <th>Apellido M.</th>
                                                            <th>Nombre</th>
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

        <!-- Modal Registrar/Editar -->
        <!-- Modal Registrar/Editar Ciudadano Mejorado -->
        <div class="modal fade" id="registerModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modal-title">Registrar Ciudadano</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="registerForm" enctype="multipart/form-data">
                            <input type="hidden" id="ciud_id" name="ciud_id">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Tipo Documento</label>
                                    <select id="tido_id" name="tido_id" class="form-select" required>
                                        <option value="">Seleccione...</option>
                                        <!-- Opciones cargadas dinámicamente -->
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">N° Documento</label>
                                    <input type="text" id="ciud_numero_documento" name="ciud_numero_documento" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Primer Apellido</label>
                                    <input type="text" id="ciud_primer_apellido" name="ciud_primer_apellido" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Segundo Apellido</label>
                                    <input type="text" id="ciud_segundo_apellido" name="ciud_segundo_apellido" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Nombre</label>
                                    <input type="text" id="ciud_nombre" name="ciud_nombre" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Sexo</label>
                                    <select id="ciud_sexo" name="ciud_sexo" class="form-select" required>
                                        <option value="">Seleccione...</option>
                                        <option value="M">Masculino</option>
                                        <option value="F">Femenino</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Fecha de Nacimiento</label>
                                    <input type="date" id="ciud_fecha_nac" name="ciud_fecha_nac" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Foto (opcional)</label>
                                    <input type="file"
                                        id="ciud_foto_file"
                                        name="ciud_foto_file"
                                        accept="image/*"
                                        class="form-control">
                                    <div class="mt-2 text-center">
                                        <img id="previewFoto"
                                            src=""
                                            alt="Previsualización"
                                            class="img-fluid rounded"
                                            style="max-height:150px; display:none;">
                                    </div>
                                </div>

                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button class="btn btn-success" onclick="guardar()">Guardar</button>
                    </div>
                </div>
            </div>
        </div>




        <?php require_once("../html/mainjs.php"); ?>
        <script src="ciud.js"></script>
    </body>

    </html>
<?php
} else {
    header("Location:" . Conectar::ruta() . "view/404/");
}
?>