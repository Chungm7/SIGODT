/**
 * Lógica Frontend para el Módulo de Doble Factor de Autenticación (2FA / TOTP)
 * Municipalidad Provincial de Chiclayo (MPCH)
 * 100% Local - Sin dependencias externas ni llamadas a CDNs.
 */

const TwoFactorApp = {
    // URL base hacia el controlador 2FA (dinámica o fallback relativo)
    get apiEndpoint() {
        return window.TWO_FACTOR_API_URL || './../controllers/TwoFactorController.php';
    },

    initChallenge() {
        const input = document.getElementById('codigo_2fa');
        const form = document.getElementById('form2FAChallenge');

        if (input) {
            input.focus();

            // Filtrar solo dígitos numéricos y limitar a 6
            input.addEventListener('input', function(e) {
                this.value = this.value.replace(/\D/g, '').slice(0, 6);
                
                // Si completó los 6 dígitos, auto-enviar el formulario tras 200ms
                if (this.value.length === 6 && form) {
                    setTimeout(() => {
                        form.requestSubmit();
                    }, 200);
                }
            });

            // Prevenir pegado de texto no numérico
            input.addEventListener('paste', function(e) {
                e.preventDefault();
                const pastedData = (e.clipboardData || window.clipboardData).getData('text');
                const numericOnly = pastedData.replace(/\D/g, '').slice(0, 6);
                this.value = numericOnly;
                if (this.value.length === 6 && form) {
                    setTimeout(() => {
                        form.requestSubmit();
                    }, 200);
                }
            });
        }

        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                TwoFactorApp.submitChallenge();
            });
        }
    },

    submitChallenge() {
        const input = document.getElementById('codigo_2fa');
        const btn = document.getElementById('btnSubmit2FA');
        const alertBox = document.getElementById('alert2FA');
        const code = input ? input.value.trim() : '';

        if (code.length !== 6) {
            TwoFactorApp.showAlert('Ingresa los 6 dígitos que aparecen en tu aplicación.', 'warning');
            input && input.focus();
            return;
        }

        // Bloquear botón durante el envío
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Verificando...';
        }

        $.ajax({
            url: TwoFactorApp.apiEndpoint + '?op=verificar_desafio',
            type: 'POST',
            dataType: 'json',
            data: { codigo_2fa: code },
            success: function(resp) {
                if (resp.success) {
                    TwoFactorApp.showAlert(resp.message, 'success');
                    setTimeout(() => {
                        window.location.href = resp.redirect || './../../view/inicio/';
                    }, 600);
                } else {
                    TwoFactorApp.showAlert(resp.message, 'danger');
                    if (input) {
                        input.value = '';
                        input.focus();
                    }
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = 'Verificar y Acceder';
                    }
                }
            },
            error: function() {
                TwoFactorApp.showAlert('Error al conectar con el servidor. Intenta nuevamente.', 'danger');
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = 'Verificar y Acceder';
                }
            }
        });
    },

    initRecovery() {
        const input = document.getElementById('codigo_respaldo');
        const form = document.getElementById('form2FARecovery');

        if (input) {
            input.focus();
            // Formatear automáticamente como XXXX-XXXX
            input.addEventListener('input', function(e) {
                let v = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 8);
                if (v.length > 4) {
                    v = v.slice(0, 4) + '-' + v.slice(4);
                }
                this.value = v;
            });
        }

        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                TwoFactorApp.submitRecovery();
            });
        }
    },

    submitRecovery() {
        const input = document.getElementById('codigo_respaldo');
        const btn = document.getElementById('btnSubmitRecovery');
        const code = input ? input.value.trim() : '';

        if (code.length < 8) {
            TwoFactorApp.showAlert('Ingresa un código de respaldo válido (ej: 8492-3105).', 'warning');
            return;
        }

        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Validando...';
        }

        $.ajax({
            url: TwoFactorApp.apiEndpoint + '?op=verificar_recuperacion',
            type: 'POST',
            dataType: 'json',
            data: { codigo_respaldo: code },
            success: function(resp) {
                if (resp.success) {
                    TwoFactorApp.showAlert(resp.message, 'success');
                    setTimeout(() => {
                        window.location.href = resp.redirect || './../../view/inicio/';
                    }, 600);
                } else {
                    TwoFactorApp.showAlert(resp.message, 'danger');
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = 'Validar Código de Respaldo';
                    }
                }
            },
            error: function() {
                TwoFactorApp.showAlert('Error al procesar el código de respaldo.', 'danger');
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = 'Validar Código de Respaldo';
                }
            }
        });
    },

    showAlert(message, type) {
        const box = document.getElementById('alert2FA');
        if (box) {
            box.className = `alert alert-${type} alert-dismissible fade show`;
            box.innerHTML = `<div>${message}</div><button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
            box.classList.remove('d-none');
        } else if (window.Swal) {
            Swal.fire({
                icon: type === 'danger' ? 'error' : type,
                title: type === 'danger' ? 'Atención' : 'Información',
                text: message,
                confirmButtonColor: '#066fd1'
            });
        }
    },

    copyToClipboard(text, successMsg) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(() => {
                TwoFactorApp.showToast(successMsg || 'Copiado al portapapeles');
            });
        } else {
            const temp = document.createElement('textarea');
            temp.value = text;
            document.body.appendChild(temp);
            temp.select();
            document.execCommand('copy');
            document.body.removeChild(temp);
            TwoFactorApp.showToast(successMsg || 'Copiado al portapapeles');
        }
    },

    downloadTextFile(filename, text) {
        const element = document.createElement('a');
        element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(text));
        element.setAttribute('download', filename);
        element.style.display = 'none';
        document.body.appendChild(element);
        element.click();
        document.body.removeChild(element);
    },

    showToast(message) {
        if (window.toastr) {
            toastr.success(message);
        } else if (window.Swal) {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2500,
                timerProgressBar: true
            });
            Toast.fire({ icon: 'success', title: message });
        } else {
            alert(message);
        }
    },

    /**
     * Renderizador local de Códigos QR estándar (100% Funcional y escaneable por Google Authenticator).
     * Utiliza la librería local qrcode.min.js con corrección de errores nivel M.
     */
    renderLocalQr(containerId, qrText, size = 190) {
        const container = document.getElementById(containerId);
        if (!container) return;

        container.innerHTML = '';
        if (typeof QRCode !== 'undefined') {
            new QRCode(container, {
                text: qrText,
                width: size,
                height: size,
                colorDark: "#000000",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.M
            });
        } else {
            console.error('[TwoFactor] QRCode library is not loaded.');
            container.innerHTML = '<div class="alert alert-danger small p-2">Error al cargar el generador QR local.</div>';
        }
    }
};

window.TwoFactorApp = TwoFactorApp;
