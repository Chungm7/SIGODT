<div id="modalmantenimiento" class="modal fade" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="lbltitulo" class="modal-title text-uppercase fw-bold text-primary mb-0"></h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <!-- Formulario -->
            <form method="post" id="proced_form" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="proced_id" id="proced_id" />

                    <div class="mb-3">
                        <label for="proced_cod" class="form-label">Proced Código <span class="text-danger">*</span></label>
                        <input type="text" class="form-control text-uppercase" id="proced_cod" name="proced_cod" data-autofocus required />
                    </div>

                    <div class="mb-3">
                        <label for="proced_nom" class="form-label">Procedimiento <span class="text-danger">*</span></label>
                        <textarea class="form-control text-uppercase" id="proced_nom" name="proced_nom" rows="3" required></textarea>
                    </div>
                    <div class="row">

                        <div class="col-lg-4 mb-3">
                            <label for="tipo_campo" class="form-label">Tipo de Pago <span class="text-danger">*</span></label>
                            <select class="form-select select2" name="tipo_campo" id="tipo_campo" data-placeholder="Seleccione" style="width: 100%;">
                                <option value="1">No Obligatorios</option>
                                <option value="2"selected>Obligatorios</option>
                                <option value="3">Obligatorios (Sin unidad)</option>
                            </select>
                        </div>

                        <div class="col-lg-4 mb-3">
                            <label for="tipo_administrado" class="form-label">Tipo de Administrado <span class="text-danger">*</span></label>
                            <select class="form-select select2" name="tipo_administrado" id="tipo_administrado" data-placeholder="Seleccione" style="width: 100%;">
                                <option value="C">Ciudadano</option>
                                <option value="E"selected>Ciudadano y Empresa</option>
                                <option value="D">Ambos Pero Empresa es opcional </option>
                            </select>
                        </div>

                        <div class=" col-lg-4 mb-3">
                            <label for="proced_tipoindvasc" class="form-label">Tipo de Individuo <span class="text-danger">*</span></label>
                            <select class="form-select select2" name="proced_tipoindvasc" id="proced_tipoindvasc" data-placeholder="Seleccione" style="width: 100%;">
                                <option value="C" selected>Ciudadano</option>
                                <option value="V">Vehículo</option>
                            </select>
                        </div>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="submit" name="action" value="add" class="btn btn-primary">
                        <i class="fa fa-check me-1"></i> Guardar
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fa fa-times me-1"></i> Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>