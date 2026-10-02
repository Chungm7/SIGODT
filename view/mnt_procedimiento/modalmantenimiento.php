<div id="modalmantenimiento" class="modal fade" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="lbltitulo" class="modal-title fw-bold text-primary mb-0"></h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <!-- Formulario -->
            <form method="post" id="proced_form" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="proced_id" id="proced_id" />

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="proced_cod" class="form-label fw-semibold">Código del Procedimiento <span class="text-danger">*</span></label>
                            <input type="text" class="form-control text-uppercase" id="proced_cod" name="proced_cod" placeholder="Ej. P01" data-autofocus required />
                        </div>
                        <div class="col-md-8 mb-3">
                            <label for="proced_nom" class="form-label fw-semibold">Denominación del Procedimiento <span class="text-danger">*</span></label>
                            <textarea class="form-control text-uppercase" id="proced_nom" name="proced_nom" rows="2" placeholder="Ingrese nombre o descripción del procedimiento" required></textarea>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-4 mb-3">
                            <label for="tipo_campo" class="form-label fw-semibold">Tipo de Pago <span class="text-danger">*</span></label>
                            <select class="form-select select2" name="tipo_campo" id="tipo_campo" data-placeholder="Seleccione" style="width: 100%;">
                                <option value="1">No Obligatorios</option>
                                <option value="2" selected>Obligatorios</option>
                                <option value="3">Obligatorios (Sin unidad)</option>
                            </select>
                            <small class="form-hint">Regla de pago de tasas asociadas</small>
                        </div>

                        <div class="col-lg-4 mb-3">
                            <label for="tipo_administrado" class="form-label fw-semibold">Tipo de Administrado <span class="text-danger">*</span></label>
                            <select class="form-select select2" name="tipo_administrado" id="tipo_administrado" data-placeholder="Seleccione" style="width: 100%;">
                                <option value="C">Ciudadano</option>
                                <option value="E" selected>Ciudadano y Empresa</option>
                                <option value="D">Ambos (Empresa opcional)</option>
                            </select>
                            <small class="form-hint">Quién puede realizar el trámite</small>
                        </div>

                        <div class="col-lg-4 mb-3">
                            <label for="proced_tipoindvasc" class="form-label fw-semibold">Tipo de Individuo <span class="text-danger">*</span></label>
                            <select class="form-select select2" name="proced_tipoindvasc" id="proced_tipoindvasc" data-placeholder="Seleccione" style="width: 100%;">
                                <option value="C" selected>Ciudadano</option>
                                <option value="V">Vehículo</option>
                            </select>
                            <small class="form-hint">Sujeto u objeto del trámite</small>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-x" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <line x1="18" y1="6" x2="6" y2="18" />
                            <line x1="6" y1="6" x2="18" y2="18" />
                        </svg>
                        Cancelar
                    </button>
                    <button type="submit" name="action" value="add" class="btn btn-primary ms-auto">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-check" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M5 12l5 5l10 -10" />
                        </svg>
                        Guardar Procedimiento
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>