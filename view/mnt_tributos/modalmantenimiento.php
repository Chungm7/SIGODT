<div id="modalmantenimiento" class="modal modal-blur fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Seleccionar tasas :</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <input type="hidden" name="proced_id" id="proced_id"/>
            <div class="modal-body">
                <table id="tasa_data" class="table table-vcenter">
                    <thead>
                        <tr>
                            <th>Seleccionar</th>
                            <th>Tasa</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Contenido dinámico -->
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" name="action" onclick="registrardetalle()" class="btn btn-primary">
                    <i class="fa fa-check"></i> Guardar
                </button>
                <button type="reset" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fa fa-close"></i> Cancelar
                </button>
            </div>
        </div>
    </div>
</div>