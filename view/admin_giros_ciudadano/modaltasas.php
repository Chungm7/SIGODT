<!-- Modal para desglose y gestión de tasas -->
<div id="modaltasas" class="modal modal-blur fade" tabindex="-1" aria-labelledby="nombreproced" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content shadow">
            <div class="modal-header">
                <h5 id="nombreproced" class="modal-title fw-bold">Tasas del Procedimiento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <input type="hidden" name="procedciudadano_id" id="procedciudadano_id" />
                <input type="hidden" class="estado_procedimiento" name="estado_procedimiento" id="estado_procedimiento" />

                <!-- Acciones Rápidas -->
                <div class="d-flex flex-wrap justify-content-end gap-2 mb-3">
                    <button type="button" id="IDpagarGrupo" onclick="pagargrupo()" class="btn btn-primary d-inline-flex align-items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-coin me-1" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" />
                            <path d="M14.8 9a2 2 0 0 0 -1.8 -1h-2a2 2 0 1 0 0 4h2a2 2 0 1 1 0 4h-2a2 2 0 0 1 -1.8 -1" />
                            <path d="M12 7v10" />
                        </svg>
                        <span>Girar en Grupo</span>
                    </button>

                    <button type="button" id="btnEditarComentario" onclick="editarComentario()" class="btn btn-outline-warning d-inline-flex align-items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-receipt me-1" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16l-3 -2l-2 2l-2 -2l-2 2l-2 -2l-3 2m4 -14h6m-6 4h6m-2 4h2" />
                        </svg>
                        <span>Ver Órdenes de Giro</span>
                    </button>
                </div>

                <div class="table-responsive">
                    <table id="data_tasa" class="table table-vcenter card-table table-striped table-hover w-100">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 5%;"></th>
                                <th style="width: 40%;">Tasa</th>
                                <th style="width: 15%;">Monto</th>
                                <th class="text-center" style="width: 15%;">Estado</th>
                                <th class="text-center" style="width: 12%;">Girar</th>
                                <th class="text-center" style="width: 13%;">Imprimir</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" onclick="recargarTabla()" class="btn btn-link link-secondary" data-bs-dismiss="modal">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>