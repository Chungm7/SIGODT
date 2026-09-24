<div id="modaltasamonto" class="modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="lbltitulo" class="modal-title">Configuración de Tasa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" id="tasa_form">
                <div class="modal-body">
                    <input type="hidden" name="tasaproced_id" id="tasaproced_id" />
                    <div class="mb-3">
                        <label class="form-label">Tasa-Categoría</label>
                        <input class="form-control" id="tasa_nom" type="text" name="tasa_nom" readonly />
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">N° de Orden <span class="form-label-description">Requerido</span></label>
                            <input class="form-control" id="tasaproced_pos" type="number" name="tasaproced_pos" required />
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Monto <span class="form-label-description">Requerido</span></label>
                            <div class="input-group">
                                <span class="input-group-text">S/</span>
                                <input class="form-control" id="tasaproced_monto" type="number" step="0.01" name="tasaproced_monto" required />
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Código de Referencia <span class="form-label-description">Requerido</span></label>
                        <input class="form-control" id="cod_ref" type="text" name="cod_ref" required oninput="this.value = this.value.toUpperCase()" readonly />
                        <small class="form-hint">Código único para identificar el pago en sistemas externos.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="multiplicaCheckbox" name="is_multiplica" value="1">
                            <span class="form-check-label">Habilitar multiplicador de tasa</span>
                        </label>
                        <small class="form-hint">Si se activa, el monto base podrá ser multiplicado por un valor en el momento del cobro.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción <span class="form-label-description">Requerido</span></label>
                        <textarea class="form-control text-uppercase" id="desc_tasa" name="desc_tasa" required rows="3" oninput="this.value = this.value.toUpperCase()"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="reset" class="btn btn-secondary" data-bs-dismiss="modal">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M18 6l-12 12" />
                            <path d="M6 6l12 12" />
                        </svg>
                        Cancelar
                    </button>
                    <button type="submit" name="action" value="add" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M5 12l5 5l10 -10" />
                        </svg>
                        Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>