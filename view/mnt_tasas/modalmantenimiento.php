<div id="modalmantenimiento" class="modal fade" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <h3 id="lbltitulo" class="modal-title fw-bold text-primary mb-0"></h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <!-- Formulario de Mantenimiento -->
            <form method="post" id="tasa_form">
                <div class="modal-body">
                    <input type="hidden" name="tasa_id" id="tasa_id" />

                    <div class="mb-3">
                        <label for="tasa_nom" class="form-label fw-semibold">Nombre de la Tasa <span class="text-danger">*</span></label>
                        <input type="text" class="form-control text-uppercase" id="tasa_nom" name="tasa_nom" placeholder="Ej. Tasa por Derecho de Trámite" data-autofocus required />
                    </div>

                    <div class="mb-3">
                        <label for="tasa_tipo" class="form-label fw-semibold">Tipo de Cálculo <span class="text-danger">*</span></label>
                        <select class="form-select select2" id="tasa_tipo" name="tasa_tipo" data-placeholder="Seleccione tipo" style="width:100%" required>
                            <option value="0" selected>Simple (Monto fijo)</option>
                            <option value="1">Multiplica (Multiplicable por cantidad)</option>
                        </select>
                        <small class="form-hint">Determina si en ventanilla se puede ingresar una cantidad multiplicadora para este concepto.</small>
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
                        Guardar Tasa
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>