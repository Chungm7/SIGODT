<!-- Modal para consultar y editar comentarios de Órdenes de Giro -->
<div id="modaleditcomentario" class="modal modal-blur fade" tabindex="-1" aria-labelledby="modalEditComentarioLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content shadow">
            <div class="modal-header">
                <h5 id="modalEditComentarioLabel" class="modal-title fw-bold">Registro de Órdenes de Giro</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="procedciud_id" id="procedciud_id" />
                <div class="table-responsive">
                    <table id="data_ordenes" class="table table-vcenter card-table table-striped table-hover w-100">
                        <thead>
                            <tr>
                                <th style="width: 15%;">Orden de Giro</th>
                                <th style="width: 20%;">Fecha</th>
                                <th style="width: 35%;">Comentario</th>
                                <th class="text-center" style="width: 15%;">Editar</th>
                                <th class="text-center" style="width: 15%;">Imprimir</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>