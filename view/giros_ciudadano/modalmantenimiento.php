<div id="modalmantenimiento" class="modal modal-blur fade" aria-labelledby="modalmantenimientoLabel" tabindex="-1" aria-hidden="true"
    data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content bd-0">
            <div class="modal-header">
                <h6 id="lbltitulo" class="card-title mb-0">Nuevo Registro</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Formulario Mantenimiento -->
            <form method="post" id="ciudadanosdetalles_form">
                <div class="modal-body">
                    <input type="hidden" name="procedciudadano_id" id="procedciudadano_id" />
                    <input type="hidden" name="ciud_id" id="ciud_id" />
                    <input type="hidden" name="empr_id" id="empr_id" />
                    <input type="hidden" name="tipo" id="tipo" />
                    <input type="hidden" name="tiv_id" id="tiv_id" />
                    <input type="hidden" name="menor_edad" id="menor_edad" />
                    <input type="hidden" name="esCarnet" id="esCarnet" />
                    <input type="hidden" name="esCPP" id="esCPP" />
                    <input type="hidden" name="ruc_checkbox" id="ruc_checkbox" />
                    <input type="hidden" name="tipo_admin" id="tipo_admin" />
                    <div class="form-group">
                        <div id="info_procedimiento"></div>

                        <div class="ciud_container">
                            <div class="row">
                                <div class="col-lg-8">
                                    <div class="input-group mb-2">
                                        <button class="btn dropdown-toggle" id="name_select_tipo" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            DNI
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="#" onclick="seleccionarTipo('DNI', event)">DNI</a></li>
                                            <li><a class="dropdown-item" href="#" onclick="seleccionarTipo('CEE', event)">CEE</a></li>
                                            <li><a class="dropdown-item" href="#" onclick="seleccionarTipo('CPP', event)">CPP</a></li>
                                        </ul>
                                        <input type="number" name="ciudadano_doc" id="ciudadano_doc" class="form-control" placeholder="Ingresa el número de documento" oninput="limitabuscadni(this)" required>
                                    </div>
                                    <label class="form-label required" for="ciudadano_nombre">Nombre: </label>
                                    <div class="input-group mb-2">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        <input class="form-control tx-uppercase" id="ciudadano_nombre" type="text" name="ciudadano_nombre" readonly required>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label required" for="ciudadano_apep">Apellido Paterno: </label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-user-tag"></i></span>
                                                <input class="form-control tx-uppercase" id="ciudadano_apep" type="text" name="ciudadano_apep" readonly required>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label required" for="ciudadano_apem">Apellido Materno: </label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-user-tag"></i></span>
                                                <input class="form-control tx-uppercase" id="ciudadano_apem" type="text" name="ciudadano_apem" readonly required>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 d-flex justify-content-center align-items-center mb-2">
                                    <img id="imagen_ciudadano" src="../../public/img/perfil.jpeg" alt="Imagen" style="max-width: 150px; max-height: 150px;">
                                </div>
                                <div class="col-lg-12">
                                    <label id="ciud_mensaje" class="alert d-none" style="width: 100% !important;" role="alert">Correcto!</label>
                                </div>
                            </div>
                        </div>

                        <script>
                            function seleccionarTipo(tipo, e) {
                                e.preventDefault();
                                document.getElementById('name_select_tipo').innerText = tipo;
                            }
                        </script>
                        <div class="empr_container">
                            <div class="hr-text text-primary">Datos de Empresa</div>

                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <label id="label_ruc" class="form-label">Número de RUC:</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-building"></i></span>
                                        <input type="text" name="empr_ruc" id="empr_ruc" class="form-control" placeholder="Ingresa el RUC" oninput="limitarbuscarruc(this)">
                                    </div>
                                </div>

                                <div class="col-md-12 mb-2">
                                    <label id="label_empr_razon_social" class="form-label">Razón Social</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-building"></i>
                                        </span>
                                        <input type="text"
                                            id="empr_razon_social"
                                            class="form-control"
                                            placeholder="Razón Social"
                                            readonly>
                                    </div>
                                </div>


                                <div class="col-lg-12">
                                    <div id="mensaje_empresa" class="alert d-none" role="alert"></div>
                                </div>
                            </div>
                        </div>


                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger " data-bs-dismiss="modal"><i class="fa fa-close"></i> Cancelar</button>

                        <button type="submit" name="action" value="add" id="btnguardar" class="btn btn-primary"><i class="fa fa-check"></i> Guardar</button>
                    </div>
            </form>
        </div>
    </div>
</div>