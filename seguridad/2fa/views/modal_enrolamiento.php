<!-- ========================================================
     MODAL DE ENROLAMIENTO Y CONFIGURACIÓN DE 2FA (TABLER UI)
     Rediseño Aprobado en Figma - Municipalidad Provincial de Chiclayo
     ======================================================== -->

<div class="modal modal-blur fade" id="modal2FAEnroll" tabindex="-1" role="dialog" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-xl" role="document" style="max-width: 960px;">
        <div class="modal-content border-0 shadow-lg overflow-hidden" style="border-radius: 16px;">
            
            <!-- Franja Azul (82%) y Amarillo (18%) -->
            <div style="height: 8px; display: flex; width: 100%;">
                <div style="background-color: #0066CC; width: 82%; height: 100%;"></div>
                <div style="background-color: #FBC02D; width: 18%; height: 100%;"></div>
            </div>

            <!-- Cabecera del Modal -->
            <div class="modal-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3">
                    <img src="<?= Conectar::ruta() ?>public/static/illustrations/logomuni3.png" width="44" height="auto" draggable="false" alt="Escudo MPCH">
                    <div>
                        <div class="text-uppercase fw-bold text-dark" style="font-size: 11px; letter-spacing: 0.8px;">MUNICIPALIDAD PROVINCIAL DE CHICLAYO</div>
                        <h4 class="modal-title fw-bold text-primary mb-0 d-flex align-items-center gap-2" style="font-size: 1.15rem; color: #066ed0 !important;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M12 3a12 12 0 0 0 8.5 3a12 12 0 0 1 -8.5 15a12 12 0 0 1 -8.5 -15a12 12 0 0 0 8.5 -3" />
                                <path d="M9 12l2 2l4 -4" />
                            </svg>
                            Configuración de Doble Factor de Autenticación (2FA)
                        </h4>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Cuerpo Dividido en 2 Secciones Horizontales -->
            <div class="modal-body p-0">
                <div class="row g-0">
                    
                    <!-- Columna Izquierda: Paso 1 y Paso 2 -->
                    <div class="col-12 col-lg-6 p-4 p-md-4" style="border-right: 1px solid #f1f5f9;">
                        
                        <!-- Paso 1 -->
                        <div class="mb-3 pb-3" style="border-bottom: 1px solid #f1f5f9;">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="badge text-white rounded-circle fs-4" style="background-color: #066ed0; width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700;">1</span>
                                <h5 class="fw-bold text-dark mb-0" style="font-size: 15px;">Descarga la Aplicación</h5>
                            </div>
                            <p class="text-muted small mb-2" style="font-size: 13px;">
                                Instala gratis en tu teléfono <strong>Google Authenticator</strong> o <strong>Microsoft Authenticator</strong>:
                            </p>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="store-badge-pill" style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 20px; font-size: 12px; font-weight: 600; color: #334155;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <path d="M4 10l0 6" /><path d="M20 10l0 6" />
                                        <path d="M7 9h10v8a1 1 0 0 1 -1 1h-8a1 1 0 0 1 -1 -1v-8a5 5 0 0 1 10 0" />
                                        <path d="M8 3l1 2" /><path d="M16 3l-1 2" />
                                        <path d="M9 18l0 3" /><path d="M15 18l0 3" />
                                    </svg>
                                    Google Play Store (Android)
                                </span>
                                <span class="store-badge-pill" style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 20px; font-size: 12px; font-weight: 600; color: #334155;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <path d="M8.286 7.008c-3.216 0 -4.286 3.23 -4.286 5.92c0 3.229 2.143 8.072 4.286 8.072c1.165 -.05 1.799 -.911 3.286 -.911c1.487 0 2.03 .911 3.286 .911c2.143 0 4.286 -4.843 4.286 -8.072c0 -2.69 -1.07 -5.92 -4.286 -5.92c-2 0 -2.464 1.303 -3.286 1.303c-.822 0 -1.286 -1.303 -3.286 -1.303z" />
                                        <path d="M12 4a2 2 0 0 0 2 -2a2 2 0 0 0 -2 2" />
                                    </svg>
                                    App Store (iPhone)
                                </span>
                            </div>
                        </div>

                        <!-- Paso 2 -->
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="badge text-white rounded-circle fs-4" style="background-color: #066ed0; width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700;">2</span>
                                <h5 class="fw-bold text-dark mb-0" style="font-size: 15px;">Escanea el Código QR</h5>
                            </div>
                            <p class="text-muted small mb-2" style="font-size: 13px;">
                                Abre la aplicación en tu celular, pulsa en <strong>(+)</strong> y selecciona <strong>Escanear código QR</strong>:
                            </p>

                            <!-- QR Centrado -->
                            <div class="text-center my-2">
                                <div class="qr-wrapper shadow-sm border p-2 bg-white rounded-3 d-inline-block">
                                    <div id="qrContainer2FA">
                                        <div class="spinner-border text-primary my-4" role="status"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Clave Manual Alternativa -->
                            <div class="text-center mt-2">
                                <a class="small text-primary fw-semibold text-decoration-none d-inline-flex align-items-center gap-1" data-bs-toggle="collapse" href="#collapseManualSecret" role="button" style="color: #066ed0 !important;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <path d="M2 5m0 2a2 2 0 0 1 2 -2h16a2 2 0 0 1 2 2v10a2 2 0 0 1 -2 2h-16a2 2 0 0 1 -2 -2z" />
                                        <path d="M6 10l0 .01" /><path d="M10 10l0 .01" /><path d="M14 10l0 .01" /><path d="M18 10l0 .01" />
                                        <path d="M6 14l0 .01" /><path d="M18 14l0 .01" /><path d="M10 14l4 0" />
                                    </svg>
                                    ¿No puedes escanear con la cámara? Configurar con clave manual &darr;
                                </a>
                                <div class="collapse mt-2" id="collapseManualSecret">
                                    <div class="manual-secret-box text-start p-2 rounded" style="background: #f1f5f9; border: 1px solid #cbd5e1; display: flex; align-items: center; justify-content: space-between;">
                                        <span id="txtManualSecret" class="font-monospace text-dark fw-bold">—</span>
                                        <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2" onclick="TwoFactorApp.copyToClipboard(document.getElementById('txtManualSecret').innerText, 'Clave secreta copiada')">
                                            Copiar
                                        </button>
                                    </div>
                                </div>
                            </div>

                        </div>

                    </div>

                    <!-- Columna Derecha: Paso 3 -->
                    <div class="col-12 col-lg-6 p-4 p-md-4 text-center d-flex flex-column justify-content-center align-items-center bg-white">
                        
                        <div class="mb-3">
                            <div class="mb-3" style="width: 56px; height: 56px; background-color: #e0f0fe; color: #066ed0; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M6 5a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-8a2 2 0 0 1 -2 -2v-14z" />
                                    <path d="M11 4h2" />
                                    <path d="M12 17v.01" />
                                </svg>
                            </div>
                            <div class="d-inline-flex align-items-center gap-2 mb-1">
                                <span class="badge text-white rounded-circle fs-4" style="background-color: #066ed0; width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700;">3</span>
                                <h4 class="fw-bold text-dark mb-0" style="font-size: 16px;">Verificación y Activación</h4>
                            </div>
                            <p class="text-muted small mt-1 mb-3" style="font-size: 13px;">
                                Ingresa el código dinámico de 6 dígitos que muestra tu teléfono:
                            </p>
                        </div>

                        <!-- Formulario -->
                        <div class="w-100 px-md-3" style="max-width: 320px;">
                            <label class="form-label fw-bold text-uppercase text-muted small text-start d-block mb-1" style="font-size: 11px; letter-spacing: 0.5px;">
                                Código de 6 Dígitos
                            </label>

                            <input 
                                type="text" 
                                id="codigo_confirmacion_2fa" 
                                class="form-control text-center mb-2" 
                                placeholder="0 0 0 0 0 0" 
                                maxlength="6" 
                                inputmode="numeric" 
                                pattern="[0-9]*" 
                                autocomplete="one-time-code"
                                style="font-size: 28px; font-weight: 700; letter-spacing: 10px; height: 54px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-family: monospace;"
                            />

                            <div class="d-flex align-items-center justify-content-center gap-1 text-muted small mb-3" style="font-size: 12px;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" />
                                    <path d="M12 7v5l3 3" />
                                </svg>
                                <span>El código numérico se renueva cada 30 segundos</span>
                            </div>

                            <div id="alertConfirm2FA" class="alert alert-danger small d-none mb-3" role="alert"></div>

                            <button type="button" id="btnConfirmar2FA" class="btn w-100 py-2 fw-bold" style="background-color: #fed84d; color: #0f172a; height: 48px; border-radius: 8px; font-size: 15px; border: none; display: inline-flex; align-items: center; justify-content: center; gap: 8px;" onclick="confirmarActivacion2FA()">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M12 3a12 12 0 0 0 8.5 3a12 12 0 0 1 -8.5 15a12 12 0 0 1 -8.5 -15a12 12 0 0 0 8.5 -3" />
                                    <path d="M9 12l2 2l4 -4" />
                                </svg>
                                Activar 2FA
                            </button>

                            <div class="mt-4 pt-2 text-muted small d-flex align-items-center justify-content-center gap-1" style="font-size: 12px;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M5 11m0 2a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v6a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2z" />
                                    <path d="M8 11v-4a4 4 0 1 1 8 0v4" />
                                </svg>
                                Protegido mediante algoritmo estándar RFC 6238
                            </div>
                        </div>

                    </div>

                </div>
            </div>

            <!-- Pie del Modal -->
            <div class="modal-footer bg-white border-top py-3 px-4 justify-content-between" style="font-size: 13px;">
                <span class="text-muted">Municipalidad Provincial de Chiclayo</span>
                <button type="button" class="btn btn-link link-primary p-0 text-decoration-none fw-medium" data-bs-dismiss="modal" style="color: #066ed0 !important;">Cancelar</button>
            </div>

        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#modal2FAEnroll').on('shown.bs.modal', function() {
        $('#codigo_confirmacion_2fa').val('').focus();
    });

    $('#codigo_confirmacion_2fa').on('input', function() {
        this.value = this.value.replace(/\D/g, '');
        if (this.value.length === 6) {
            setTimeout(function() {
                confirmarActivacion2FA();
            }, 300);
        }
    });

    $('#codigo_confirmacion_2fa').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            confirmarActivacion2FA();
        }
    });
});

function abrirModalEnrolamiento2FA() {
    $('#modal2FAEnroll').modal('show');
    $('#qrContainer2FA').html('<div class="spinner-border text-primary my-4" role="status"></div>');
    $('#alertConfirm2FA').addClass('d-none');
    $('#codigo_confirmacion_2fa').val('');

    $.ajax({
        url: '<?= Conectar::ruta() ?>seguridad/2fa/controllers/TwoFactorController.php?op=iniciar_enrolamiento',
        type: 'POST',
        dataType: 'json',
        success: function(resp) {
            if (resp.success) {
                $('#txtManualSecret').text(resp.secret_fmt || resp.secret);
                TwoFactorApp.renderLocalQr('qrContainer2FA', resp.otp_uri, 190);
                setTimeout(function() {
                    $('#codigo_confirmacion_2fa').focus();
                }, 400);
            } else {
                $('#qrContainer2FA').html('<div class="text-danger small p-3">' + resp.message + '</div>');
            }
        },
        error: function() {
            $('#qrContainer2FA').html('<div class="text-danger small p-3">Error al generar clave de vinculación.</div>');
        }
    });
}

function confirmarActivacion2FA() {
    const code = $('#codigo_confirmacion_2fa').val().trim();
    if (code.length !== 6) {
        $('#alertConfirm2FA').text('Ingresa los 6 dígitos que muestra tu teléfono.').removeClass('d-none');
        $('#codigo_confirmacion_2fa').focus();
        return;
    }

    const btn = $('#btnConfirmar2FA');
    if (btn.data('loading')) return;
    btn.data('loading', true).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Validando...');

    $.ajax({
        url: '<?= Conectar::ruta() ?>seguridad/2fa/controllers/TwoFactorController.php?op=confirmar_enrolamiento',
        type: 'POST',
        dataType: 'json',
        data: { codigo_confirmacion: code },
        success: function(resp) {
            if (resp.success) {
                btn.html('¡Vinculación Exitosa!');
                $('#modal2FAEnroll').modal('hide');
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Vinculación Exitosa!',
                        text: resp.message || 'Dispositivo vinculado correctamente.',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = resp.redirect || '<?= Conectar::ruta() ?>view/inicio/';
                    });
                } else {
                    window.location.href = resp.redirect || '<?= Conectar::ruta() ?>view/inicio/';
                }
            } else {
                btn.data('loading', false);
                btn.prop('disabled', false).html('<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 3a12 12 0 0 0 8.5 3a12 12 0 0 1 -8.5 15a12 12 0 0 1 -8.5 -15a12 12 0 0 0 8.5 -3" /><path d="M9 12l2 2l4 -4" /></svg> Activar 2FA');
                $('#alertConfirm2FA').text(resp.message).removeClass('d-none');
                $('#codigo_confirmacion_2fa').val('').focus();
            }
        },
        error: function(xhr) {
            btn.data('loading', false);
            btn.prop('disabled', false).html('<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 3a12 12 0 0 0 8.5 3a12 12 0 0 1 -8.5 15a12 12 0 0 1 -8.5 -15a12 12 0 0 0 8.5 -3" /><path d="M9 12l2 2l4 -4" /></svg> Activar 2FA');
            let msg = 'Error de conexión al activar 2FA.';
            try {
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                } else if (xhr.responseText) {
                    const parsed = JSON.parse(xhr.responseText);
                    if (parsed.message) msg = parsed.message;
                }
            } catch (e) {}
            $('#alertConfirm2FA').text(msg).removeClass('d-none');
        }
    });
}
</script>
