<!-- Modal para desglose y liquidación de tasas -->
<div id="modaltasas" class="modal modal-blur fade" tabindex="-1" aria-labelledby="nombreproced_modal" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content shadow">
            <!-- Header -->
            <div class="modal-header">
                <h5 id="nombreproced_modal" class="modal-title fw-bold">
                    Cargando...
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Body -->
            <div class="modal-body">
                <!-- Spinner de carga -->
                <div id="modalSpinner" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                </div>

                <!-- Contenido del modal, inicialmente oculto -->
                <div id="modalContent" style="display: none;">
                    <input type="hidden" name="procedciudadano_id" id="procedciudadano_id">
                    <input type="hidden" class="estado_procedimiento" name="estado_procedimiento" id="estado_procedimiento">

                    <!-- Sección de Datos del Procedimiento -->
                    <div class="mb-3" id="procedimientoData">
                        <div class="row g-3 align-items-center">
                            <div class="col-md-3 text-center">
                                <img id="ciudadano_foto" src="" alt="Fotografía" class="img-fluid rounded border p-1" style="max-height: 150px; object-fit: cover;">
                            </div>
                            <div class="col-md-9">
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered mb-0">
                                        <tbody>
                                            <tr>
                                                <th class="bg-light text-muted small text-uppercase" style="width: 30%;">Código</th>
                                                <td id="pc_cod" class="fw-bold"></td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light text-muted small text-uppercase">Procedimiento</th>
                                                <td id="pc_proced"></td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light text-muted small text-uppercase">Administrado</th>
                                                <td id="ciudadano_nombre_card" class="fw-bold"></td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light text-muted small text-uppercase">DNI / Doc</th>
                                                <td id="ciudadano_dni"></td>
                                            </tr>
                                            <tr id="fila_ruc">
                                                <th class="bg-light text-muted small text-uppercase">RUC</th>
                                                <td id="ruc"></td>
                                            </tr>
                                            <tr id="fila_empresa">
                                                <th class="bg-light text-muted small text-uppercase">Empresa</th>
                                                <td id="empresa"></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Acciones del Modal -->
                    <div class="d-flex justify-content-end gap-2 mb-3">
                        <button type="button" id="btnEditarComentario" onclick="editarComentario()" class="btn btn-outline-success d-inline-flex align-items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-receipt me-1" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16l-3 -2l-2 2l-2 -2l-2 2l-2 -2l-3 2m4 -14h6m-6 4h6m-2 4h2" />
                            </svg>
                            <span>Ver Órdenes de Giro</span>
                        </button>
                    </div>

                    <!-- Listado de Giros y Tasas -->
                    <div class="mb-3">
                        <!-- Listado de Tasas -->
                        <div id="tasaListContainer" class="list-group list-group-flush" style="display: block;">
                            <!-- Items cargados dinámicamente -->
                        </div>
                        <!-- Listado de Giros -->
                        <div id="girosListContainer" class="list-group list-group-flush" style="display: none;">
                            <!-- Items cargados dinámicamente -->
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button id="IDpagarGrupo" onclick="pagargrupo()" class="btn btn-primary d-inline-flex align-items-center" style="display: none;">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-coin me-1" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" />
                                <path d="M14.8 9a2 2 0 0 0 -1.8 -1h-2a2 2 0 1 0 0 4h2a2 2 0 1 1 0 4h-2a2 2 0 0 1 -1.8 -1" />
                                <path d="M12 7v10" />
                            </svg>
                            <span>Girar en Grupo</span>
                        </button>
                    </div>
                </div>

                <!-- Alerta en caso de error -->
                <div id="modalAlert" class="alert alert-danger" style="display: none;">
                    Error al cargar los datos del procedimiento.
                </div>
            </div>

            <!-- Footer -->
            <div class="modal-footer">
                <button type="button" onclick="recargarTabla()" class="btn btn-link link-secondary" data-bs-dismiss="modal">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>