<div id="modaltasas" class="modal modal-blur fade" tabindex="-1" aria-labelledby="modaltasasLabel" aria-hidden="true"
    data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content bd-0">
            <!-- Header -->
            <div class="modal-header" style="background: #0866c6;">
                <h5 id="nombreproced_modal" class="card-title mb-0"
                    style="color: #ffffff; text-align: center; width: 100%;">
                    Cargando...
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <!-- Body -->
            <div class="modal-body">
                <!-- Spinner de carga -->
                <div id="modalSpinner" style="text-align: center; padding: 20px;">
                    <i class="fa fa-spinner fa-spin" style="font-size: 24px;"></i>
                </div>
                <!-- Contenido del modal, inicialmente oculto -->
                <div id="modalContent" style="display: none;">
                    <input type="hidden" name="procedciudadano_id" id="procedciudadano_id">
                    <input type="hidden" class="estado_procedimiento" name="estado_procedimiento"
                        id="estado_procedimiento">

                    <!-- Sección de Datos del Procedimiento -->
                    <div class="mb-3" id="procedimientoData">
                        <div class="row">
                            <div class="col-md-4 text-center">
                                <img id="ciudadano_foto" src="" alt="Foto" class="img-fluid rounded"
                                    style="max-height: 170px;">
                            </div>
                            <div class="col-md-8">
                                <table class="table table-bordered mb-0">
                                    <tbody>
                                        <tr>
                                            <th style="background-color: #0866c6; color: #fff; width: 35%; padding: 5px">Código</th>
                                            <td id="pc_cod" style="padding: 5px"></td>
                                        </tr>
                                        <tr>
                                            <th style="background-color: #0866c6; color: #fff; padding: 5px">Procedimiento</th>
                                            <td id="pc_proced" style="padding: 5px"></td>
                                        </tr>
                                        <tr>
                                            <th style="background-color: #0866c6; color: #fff; padding: 5px">Ciudadano</th>
                                            <td id="ciudadano_nombre_card" style="padding: 5px"></td>
                                        </tr>
                                        <tr>
                                            <th style="background-color: #0866c6; color: #fff; padding: 5px">DNI</th>
                                            <td id="ciudadano_dni" style="padding: 5px"></td>
                                        </tr>
                                        <tr id="fila_ruc">
                                            <th style="background-color: #5b737f; color: #fff; padding: 5px">RUC</th>
                                            <td id="ruc" style="padding: 5px"></td>
                                        </tr>
                                        <tr id="fila_empresa">
                                            <th style="background-color: #5b737f; color: #fff; padding: 5px">Empresa</th>
                                            <td id="empresa" style="padding: 5px"></td>
                                        </tr>

                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <!-- Acciones del Modal -->
                    <div class="mb-3" style="display: flex; justify-content: flex-end; gap: 5px;">

                        <button type="button" id="btnEditarComentario" onclick="editarComentario()"
                            class="btn btn-success tx-12 tx-uppercase pd-y-10 pd-x-20">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-cash">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M7 15h-3a1 1 0 0 1 -1 -1v-8a1 1 0 0 1 1 -1h12a1 1 0 0 1 1 1v3" />
                                <path d="M7 9m0 1a1 1 0 0 1 1 -1h12a1 1 0 0 1 1 1v8a1 1 0 0 1 -1 1h-12a1 1 0 0 1 -1 -1z" />
                                <path d="M12 14a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />
                            </svg> </i> Ver Ordenes de Giros
                        </button>
                    </div>
                    <!-- Listado de Giros y Tasas -->
                    <div class="pd-x-15 pd-b-15">
                        <!-- Listado de Tasas -->
                        <div id="tasaListContainer" class="list-group list-group-flush" style="display: block;">
                            <!-- Los items se cargarán dinámicamente -->
                        </div>
                        <!-- Listado de Giros -->
                        <div id="girosListContainer" class="list-group list-group-flush" style="display: none;">
                            <!-- Los items se cargarán dinámicamente -->
                        </div>

                    </div>
                    <button id="IDpagarGrupo" onclick="pagargrupo()"
                        class="btn btn-primary tx-12 tx-uppercase pd-y-10 pd-x-20" style="display: none;">
                        <i class="fa fa-dollar mr-2"></i> Girar en Grupo
                    </button>
                </div>
                <!-- Alerta en caso de error -->
                <div id="modalAlert" class="alert alert-danger" style="display: none;">
                    Error al cargar los datos.
                </div>
            </div>
            <!-- Footer -->
            <div class="modal-footer">
                <button type="button" onclick="recargarTabla()"
                    class="btn btn-secondary tx-12 pd-y-10 pd-x-20" data-bs-dismiss="modal">
                    <i class="fa fa-times me-1"></i> Cerrar
                </button>
            </div>
        </div>
    </div>
</div>