<div id="modalmantenimiento" class="modal modal-blur fade" tabindex="-1" role="dialog" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="lbltitulo" class="modal-title fw-bold text-primary mb-0">Nuevo Documento</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <!-- Formulario Mantenimiento -->
            <form method="post" id="tupa_form">
                <div class="modal-body">
                    <input type="hidden" name="tupa_id" id="tupa_id" />

                    <div class="mb-3">
                        <label for="tupa_nom" class="form-label fw-semibold">Denominación del Documento <span class="text-danger">*</span></label>
                        <input class="form-control text-uppercase" id="tupa_nom" type="text" name="tupa_nom" placeholder="Ej. TUPA 2026 - MUNICIPALIDAD DE CHICLAYO" data-autofocus required />
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="tupa_tipo" class="form-label fw-semibold">Tipo de Documento <span class="text-danger">*</span></label>
                            <select class="form-select" id="tupa_tipo" name="tupa_tipo" required>
                                <option value="TUPA" selected>TUPA (Procedimientos)</option>
                                <option value="TUSNE">TUSNE (Servicios no exclusivos)</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="tupa_año" class="form-label fw-semibold">Año de Vigencia <span class="text-danger">*</span></label>
                            <select class="form-select" id="tupa_año" name="tupa_año" required>
                                <?php
                                $año_actual = (int)date("Y");
                                for ($y = $año_actual; $y >= 2012; $y--) {
                                    $selected = ($y === $año_actual) ? 'selected' : '';
                                    echo "<option value=\"{$y}\" {$selected}>{$y}</option>";
                                }
                                ?>
                            </select>
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
                        Guardar Documento
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>