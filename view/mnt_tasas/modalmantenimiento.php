<div id="modalmantenimiento" class="modal fade" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <h3 id="lbltitulo" class="modal-title text-uppercase fw-bold text-primary mb-0"></h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <!-- Formulario de Mantenimiento -->
            <form method="post" id="tasa_form">
                <div class="modal-body">
                    <input type="hidden" name="tasa_id" id="tasa_id" />
                    <div class="row">
                        <div class="col-lg-8 mb-3">
                            <label for="tasa_nom" class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control text-uppercase" id="tasa_nom" name="tasa_nom" required />
                        </div>

                        <div class="col-lg-4 mb-3">
                            <label for="tasa_tipo" class="form-label">Tipo <span class="text-danger">*</span></label>
                            <select class="form-select select2" id="tasa_tipo" name="tasa_tipo" data-placeholder="Seleccione" style="width:100%" required >
                                <option value="" label="Seleccione"></option>
                                <option value="1">Multiplica</option>
                                <option value="0" selected>Simple</option>
                            </select>
                        </div>

                    </div>

                </div>

                <div class="modal-footer">
                    <button type="submit" name="action" value="add" class="btn btn-primary">
                        <i class="fa fa-check me-1"></i> Guardar
                    </button>
                    <button type="reset" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fa fa-times me-1"></i> Cancelar
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>