<div class="modal modal-blur fade" id="empresaModal" tabindex="-1" role="dialog" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title fw-bold text-primary mb-0" id="modal-title">Registrar Empresa</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="empresaForm">
                <div class="modal-body">
                    <input type="hidden" id="empr_id" name="empr_id">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="empr_ruc" class="form-label fw-semibold d-flex justify-content-between align-items-center">
                                <span>RUC (11 dígitos) <span class="text-danger">*</span></span>
                                <span id="ruc_status" class="small"></span>
                            </label>
                            <input
                                type="text"
                                id="empr_ruc"
                                name="empr_ruc"
                                class="form-control"
                                placeholder="Ingrese 11 dígitos de RUC"
                                maxlength="11"
                                inputmode="numeric"
                                data-autofocus
                                required>
                        </div>

                        <div class="col-md-6">
                            <label for="empr_razon_social" class="form-label fw-semibold">Razón Social <span class="text-danger">*</span></label>
                            <input type="text" id="empr_razon_social" name="empr_razon_social" class="form-control text-uppercase" placeholder="Razón social según SUNAT" required>
                        </div>

                        <div class="col-md-6">
                            <label for="empr_nombre_comercial" class="form-label fw-semibold">Nombre Comercial</label>
                            <input type="text" id="empr_nombre_comercial" name="empr_nombre_comercial" class="form-control text-uppercase" placeholder="Nombre comercial (opcional)">
                        </div>

                        <div class="col-md-6">
                            <label for="empr_direccion" class="form-label fw-semibold">Dirección Fiscal</label>
                            <input type="text" id="empr_direccion" name="empr_direccion" class="form-control text-uppercase" placeholder="Dirección fiscal declarada en SUNAT">
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
                    <button type="button" class="btn btn-primary ms-auto" id="btnGuardarEmpresa" onclick="guardar()">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-check" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M5 12l5 5l10 -10" />
                        </svg>
                        Guardar Empresa
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
