<style>
    @media (min-width: 768px) {
        .modal-col-foto {
            border-right: 1px solid var(--tblr-border-color, #e6e7e9);
            padding-right: 1.5rem;
        }
    }
    @media (max-width: 767.98px) {
        .modal-col-foto {
            border-bottom: 1px solid var(--tblr-border-color, #e6e7e9);
            padding-bottom: 1.5rem;
            margin-bottom: 1rem;
        }
    }
    .foto-box {
        width: 175px;
        height: 215px;
        background-color: var(--tblr-bg-surface-secondary, #f8fafc);
        border: 1px solid var(--tblr-border-color, #e6e7e9) !important;
        position: relative;
        overflow: hidden;
    }
    #previewFoto {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    #previewFotoDefault {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 100%;
        text-align: center;
    }
</style>

<div class="modal modal-blur fade" id="registerModal" tabindex="-1" role="dialog" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title fw-bold text-primary mb-0" id="modal-title">Registrar Ciudadano</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="registerForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" id="ciud_id" name="ciud_id">

                    <div class="row g-4 align-items-start">
                        <!-- Columna Izquierda: Fotografía -->
                        <div class="col-12 col-md-4 modal-col-foto text-center">
                            <label class="form-label fw-semibold text-start mb-2 w-100">Fotografía</label>
                            
                            <div class="foto-box border rounded mx-auto mb-3 position-relative overflow-hidden">
                                <img id="previewFoto" src="" alt="Foto Ciudadano" style="display: none;">
                                <div id="previewFotoDefault" class="text-muted p-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-user text-secondary opacity-50" width="64" height="64" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" />
                                        <path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" />
                                    </svg>
                                    <span class="small text-muted mt-2">Sin fotografía</span>
                                </div>
                            </div>

                            <div class="mx-auto" style="max-width: 195px;">
                                <input type="file" id="ciud_foto_file" name="ciud_foto_file" accept="image/*" class="form-control form-control-sm">
                                <small class="text-muted d-block mt-1">RENIEC o archivo local</small>
                            </div>
                        </div>

                        <!-- Columna Derecha: Datos del Ciudadano -->
                        <div class="col-12 col-md-8">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="tido_id" class="form-label fw-semibold">Tipo de Documento <span class="text-danger">*</span></label>
                                    <select id="tido_id" name="tido_id" class="form-select" data-autofocus required>
                                        <option value="">Seleccione...</option>
                                        <!-- Se carga dinámicamente -->
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="ciud_numero_documento" class="form-label fw-semibold d-flex justify-content-between align-items-center">
                                        <span>N° Documento <span class="text-danger">*</span></span>
                                        <span id="doc_status" class="small"></span>
                                    </label>
                                    <input type="text" id="ciud_numero_documento" name="ciud_numero_documento" class="form-control" autocomplete="off" placeholder="Ingrese documento" required>
                                </div>

                                <div class="col-md-6">
                                    <label for="ciud_primer_apellido" class="form-label fw-semibold">Primer Apellido <span class="text-danger">*</span></label>
                                    <input type="text" id="ciud_primer_apellido" name="ciud_primer_apellido" class="form-control text-uppercase" placeholder="Apellido paterno" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="ciud_segundo_apellido" class="form-label fw-semibold">Segundo Apellido</label>
                                    <input type="text" id="ciud_segundo_apellido" name="ciud_segundo_apellido" class="form-control text-uppercase" placeholder="Apellido materno">
                                </div>

                                <div class="col-12">
                                    <label for="ciud_nombre" class="form-label fw-semibold">Nombres <span class="text-danger">*</span></label>
                                    <input type="text" id="ciud_nombre" name="ciud_nombre" class="form-control text-uppercase" placeholder="Nombres completos" required>
                                </div>

                                <div class="col-md-6">
                                    <label for="ciud_sexo" class="form-label fw-semibold">Sexo <span class="text-danger">*</span></label>
                                    <select id="ciud_sexo" name="ciud_sexo" class="form-select" required>
                                        <option value="">Seleccione...</option>
                                        <option value="M">Masculino</option>
                                        <option value="F">Femenino</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="ciud_fecha_nac" class="form-label fw-semibold">Fecha de Nacimiento</label>
                                    <input type="date" id="ciud_fecha_nac" name="ciud_fecha_nac" class="form-control">
                                </div>
                            </div>
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
                    <button type="button" class="btn btn-primary ms-auto" id="btnGuardarCiudadano" onclick="guardar()">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-check" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M5 12l5 5l10 -10" />
                        </svg>
                        Guardar Ciudadano
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
