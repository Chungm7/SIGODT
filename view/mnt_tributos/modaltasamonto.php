<div id="modaltasamonto" class="modal modal-blur fade" tabindex="-1" role="dialog" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="lbltitulo" class="modal-title fw-bold text-primary mb-0">Configuración de Tasa</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form method="post" id="tasa_form">
                <div class="modal-body">
                    <input type="hidden" name="tasaproced_id" id="tasaproced_id" />
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tasa / Concepto</label>
                        <input class="form-control bg-light" id="tasa_nom" type="text" name="tasa_nom" readonly />
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold" for="tasaproced_pos">N° de Orden de Pago <span class="text-danger">*</span></label>
                            <input class="form-control" id="tasaproced_pos" type="number" name="tasaproced_pos" placeholder="Ej. 1" min="1" data-autofocus required />
                            <small class="form-hint">Prioridad en la que se cobrará la tasa en ventanilla.</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold" for="tasaproced_monto">Monto (S/) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text fw-bold">S/</span>
                                <input class="form-control" id="tasaproced_monto" type="number" step="0.01" min="0" placeholder="0.00" name="tasaproced_monto" required />
                            </div>
                            <small class="form-hint">Monto arancelario fijado según TUPA.</small>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="cod_ref">Código de Referencia <span class="text-danger">*</span></label>
                        <input class="form-control bg-light" id="cod_ref" type="text" name="cod_ref" required readonly />
                        <small class="form-hint">Código único de imputación presupuestal/contable.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="multiplicaCheckbox" name="is_multiplica" value="1">
                            <span class="form-check-label fw-semibold">Habilitar multiplicador de tasa</span>
                        </label>
                        <small class="form-hint">Si se activa, el cajero o ventanilla podrá ingresar una cantidad multiplicadora para este concepto.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="desc_tasa">Descripción Detallada <span class="text-danger">*</span></label>
                        <textarea class="form-control text-uppercase" id="desc_tasa" name="desc_tasa" required rows="3" placeholder="Ingrese detalle o especificación de la tasa"></textarea>
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
                        Guardar Configuración
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>