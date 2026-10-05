<!-- Modal para registrar administrado (Ciudadano / Empresa) -->
<div id="modalmantenimiento" class="modal modal-blur fade" tabindex="-1" aria-labelledby="lbltitulo" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content shadow">
            <div class="modal-header">
                <h5 id="lbltitulo" class="modal-title fw-bold">Nuevo Trámite</h5>
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

                    <div id="info_procedimiento"></div>

                    <!-- Contenedor Ciudadano -->
                    <div class="ciud_container">
                        <div class="row g-3">
                            <div class="col-lg-8">
                                <label class="form-label required">Documento de Identidad:</label>
                                <div class="input-group mb-2">
                                    <button class="btn dropdown-toggle" id="name_select_tipo" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        DNI
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="#" onclick="seleccionarTipo('DNI', event)">DNI</a></li>
                                        <li><a class="dropdown-item" href="#" onclick="seleccionarTipo('CEE', event)">CEE</a></li>
                                        <li><a class="dropdown-item" href="#" onclick="seleccionarTipo('CPP', event)">CPP</a></li>
                                    </ul>
                                    <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="8" name="ciudadano_doc" id="ciudadano_doc" class="form-control" placeholder="Ingresa el DNI (8 dígitos)" oninput="limitabuscadni(this)" required>
                                    <button class="btn btn-outline-secondary" type="button" id="btn_buscar_doc" onclick="ejecutarBusquedaDoc()" title="Buscar documento">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-search" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" />
                                            <path d="M21 21l-6 -6" />
                                        </svg>
                                    </button>
                                </div>

                                <label class="form-label required" for="ciudadano_nombre">Nombres:</label>
                                <div class="input-group mb-2">
                                    <span class="input-group-text">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-user" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" />
                                            <path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" />
                                        </svg>
                                    </span>
                                    <input class="form-control text-uppercase" id="ciudadano_nombre" type="text" name="ciudadano_nombre" readonly required>
                                </div>

                                <div class="row g-2">
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label required" for="ciudadano_apep">Apellido Paterno:</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-id" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                    <path d="M3 4m0 3a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v10a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3z" />
                                                    <path d="M9 10m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
                                                    <path d="M15 8l2 0" />
                                                    <path d="M15 12l2 0" />
                                                    <path d="M7 16l10 0" />
                                                </svg>
                                            </span>
                                            <input class="form-control text-uppercase" id="ciudadano_apep" type="text" name="ciudadano_apep" readonly required>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label required" for="ciudadano_apem">Apellido Materno:</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-id" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                    <path d="M3 4m0 3a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v10a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3z" />
                                                    <path d="M9 10m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
                                                    <path d="M15 8l2 0" />
                                                    <path d="M15 12l2 0" />
                                                    <path d="M7 16l10 0" />
                                                </svg>
                                            </span>
                                            <input class="form-control text-uppercase" id="ciudadano_apem" type="text" name="ciudadano_apem" readonly required>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4 d-flex flex-column align-items-center justify-content-center">
                                <div class="text-center p-2 border rounded bg-light">
                                    <img id="imagen_ciudadano" src="../../public/img/perfil.jpeg" alt="Fotografía" class="rounded img-fluid" style="width: 140px; height: 140px; object-fit: cover;">
                                    <div class="text-secondary small mt-1">Fotografía RENIEC</div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div id="ciud_mensaje" class="alert d-none py-1 mb-0" role="alert"></div>
                            </div>
                        </div>
                    </div>

                    <script>
                        function seleccionarTipo(tipo, e) {
                            if (e && e.preventDefault) e.preventDefault();
                            var btnTipo = document.getElementById('name_select_tipo');
                            if (btnTipo) btnTipo.innerText = tipo;
                            var docInput = document.getElementById('ciudadano_doc');
                            var mensaje = document.getElementById('ciud_mensaje');
                            if (docInput) {
                                if (tipo === 'DNI') {
                                    docInput.setAttribute('maxlength', '8');
                                    docInput.setAttribute('inputmode', 'numeric');
                                    docInput.setAttribute('pattern', '[0-9]*');
                                    docInput.setAttribute('placeholder', 'Ingresa el DNI (8 dígitos)');
                                } else {
                                    docInput.setAttribute('maxlength', '15');
                                    docInput.setAttribute('inputmode', 'text');
                                    docInput.removeAttribute('pattern');
                                    docInput.setAttribute('placeholder', 'Ingresa el N° de ' + tipo);
                                }
                                if (mensaje) mensaje.classList.add('d-none');
                                docInput.focus();
                                if (typeof limitabuscadni === 'function' && docInput.value) {
                                    limitabuscadni(docInput);
                                }
                            }
                        }
                    </script>

                    <!-- Contenedor Empresa -->
                    <div class="empr_container mt-3">
                        <div class="hr-text text-primary fw-bold">Datos de la Empresa / Persona Jurídica</div>

                        <div class="row g-2">
                            <div class="col-md-6 mb-2">
                                <label id="label_ruc" class="form-label">Número de RUC:</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-building" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path d="M3 21l18 0" />
                                            <path d="M9 8l1 0" />
                                            <path d="M9 12l1 0" />
                                            <path d="M9 16l1 0" />
                                            <path d="M14 8l1 0" />
                                            <path d="M14 12l1 0" />
                                            <path d="M14 16l1 0" />
                                            <path d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16" />
                                        </svg>
                                    </span>
                                    <input type="text" name="empr_ruc" id="empr_ruc" class="form-control" placeholder="Ingresa el RUC (11 dígitos)" oninput="limitarbuscarruc(this)">
                                </div>
                            </div>

                            <div class="col-md-12 mb-2">
                                <label id="label_empr_razon_social" class="form-label">Razón Social:</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-briefcase" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path d="M3 7m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v9a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z" />
                                            <path d="M8 7v-2a2 2 0 0 1 2 -2h4a2 2 0 0 1 2 2v2" />
                                            <path d="M12 12l0 .01" />
                                            <path d="M3 13a20 20 0 0 0 18 0" />
                                        </svg>
                                    </span>
                                    <input type="text" id="empr_razon_social" class="form-control" placeholder="Razón Social" readonly>
                                </div>
                            </div>

                            <div class="col-12">
                                <div id="mensaje_empresa" class="alert d-none py-1 mb-0" role="alert"></div>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="submit" name="action" value="add" id="btnguardar" class="btn btn-primary d-inline-flex align-items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-check me-1" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M5 12l5 5l10 -10" />
                        </svg>
                        <span>Guardar</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>