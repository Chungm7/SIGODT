<?php
/**
 * Vista de Desafío 2FA en el Inicio de Sesión
 * Rediseño Aprobado en Figma - Municipalidad Provincial de Chiclayo (MPCH)
 */

if (file_exists(__DIR__ . '/../../../config/conexion.php')) {
    require_once __DIR__ . '/../../../config/conexion.php';
    require_once __DIR__ . '/../../../config/jwt.php';
} else {
    require_once __DIR__ . '/../../config/conexion.php';
    require_once __DIR__ . '/../../config/jwt.php';
}
require_once __DIR__ . '/../models/TwoFactorModel.php';

$jwt = new JwtHandler();
$pendingData = null;

if (!empty($_COOKIE['2fa_pending'])) {
    $dec = $jwt->decrypt($_COOKIE['2fa_pending']);
    if (!empty($dec->data)) {
        $pendingData = $dec->data;
    }
}

// Si no hay datos de usuario pendientes de verificación, redirigir al login principal
if (!$pendingData || empty($pendingData->usu_doc)) {
    header("Location: " . Conectar::ruta());
    exit();
}

$dniEnmascarado = substr($pendingData->usu_doc, 0, 2) . '****' . substr($pendingData->usu_doc, -2);
$nombreUsuario = trim(($pendingData->usu_nom ?? '') . ' ' . ($pendingData->usu_apep ?? ''));

// Verificar si el usuario cuenta con correo institucional registrado
$correoInstitucional = TwoFactorModel::getEmailByDni($pendingData->usu_doc);
$tieneCorreo = !empty($correoInstitucional);
$headerSisAbrNom = mb_strtoupper($_ENV['SIST_INIC'], 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title><?= htmlspecialchars($headerSisAbrNom) ?></title>
    <link rel="icon" type="image/png" href="<?= Conectar::ruta() ?>public/static/illustrations/logomuni3.png">
    
    <!-- Tabler UI + Fuentes -->
    <link href="<?= Conectar::ruta() ?>public/css/tabler.min.css?1692870487" rel="stylesheet"/>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="../assets/css/two_factor.css" rel="stylesheet"/>

    <style>
        :root {
            --mpch-blue: #066ed0;
            --mpch-yellow: #fed84d;
            --mpch-yellow-hover: #fbc02d;
            --text-dark: #0f172a;
            --text-muted: #64748b;
            --bg-page: #e7f1fb;
            --border-color: #cbd5e1;
        }

        body {
            margin: 0;
            padding: 24px 16px;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: var(--bg-page) !important;
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: var(--text-dark);
            -webkit-font-smoothing: antialiased;
        }

        .challenge-container {
            width: 100%;
            max-width: 480px;
            margin: auto;
        }

        .card-custom {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 30px -5px rgba(15, 23, 42, 0.08), 0 4px 6px -2px rgba(15, 23, 42, 0.04);
            border: none;
            overflow: hidden;
        }

        /* Franja superior Azul (82%) y Amarillo (18%) */
        .card-strip {
            height: 8px;
            display: flex;
            width: 100%;
        }
        .card-strip .strip-blue {
            background-color: var(--mpch-blue);
            width: 82%;
            height: 100%;
        }
        .card-strip .strip-yellow {
            background-color: var(--mpch-yellow);
            width: 18%;
            height: 100%;
        }

        .card-body-custom {
            padding: 40px 36px 30px;
            text-align: center;
        }

        .muni-shield-top {
            width: 58px;
            height: auto;
            margin-bottom: 16px;
            user-select: none;
        }

        .shield-icon-circle {
            width: 52px;
            height: 52px;
            background-color: #e0f0fe;
            color: var(--mpch-blue);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
        }

        .challenge-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-dark);
            letter-spacing: -0.4px;
            margin-bottom: 8px;
        }

        .challenge-subtitle {
            font-size: 13.5px;
            color: var(--text-muted);
            line-height: 1.5;
            margin-bottom: 28px;
            max-width: 390px;
            margin-left: auto;
            margin-right: auto;
        }

        .challenge-subtitle strong {
            color: #334155;
        }

        .form-label-code {
            display: block;
            text-align: left;
            font-size: 11px;
            font-weight: 700;
            color: #475569;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .input-code-digits {
            width: 100%;
            height: 56px;
            background-color: #ffffff;
            border: 1.5px solid var(--border-color);
            border-radius: 8px;
            font-size: 28px;
            font-weight: 700;
            text-align: center;
            letter-spacing: 10px;
            color: var(--text-dark);
            font-family: 'SFMono-Regular', Menlo, Monaco, Consolas, monospace;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
            outline: none;
        }

        .input-code-digits::placeholder {
            color: #94a3b8;
            letter-spacing: 8px;
            font-weight: 500;
        }

        .input-code-digits:focus {
            border-color: var(--mpch-blue);
            box-shadow: 0 0 0 3px rgba(6, 110, 208, 0.15);
        }

        .hint-timer-text {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .btn-yellow-action {
            width: 100%;
            height: 48px;
            background-color: var(--mpch-yellow);
            color: #0f172a;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
            text-decoration: none !important;
            transition: background-color 0.15s ease, transform 0.05s ease;
            margin-top: 20px;
            font-family: inherit;
        }

        .btn-yellow-action:hover {
            background-color: var(--mpch-yellow-hover);
            color: #0f172a;
        }

        .btn-yellow-action:active {
            transform: scale(0.99);
        }

        /* Sección Problemas con tu Celular */
        .trouble-section {
            margin-top: 28px;
        }

        .trouble-header {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.6px;
            color: #94a3b8;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .trouble-link {
            font-size: 13px;
            font-weight: 600;
            color: var(--mpch-blue);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: color 0.15s ease;
        }

        .trouble-link:hover {
            color: #055cb1;
            text-decoration: underline;
        }

        /* Divider & Links */
        .bottom-links-row {
            margin-top: 24px;
            padding-top: 18px;
            border-top: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .bottom-link {
            font-size: 13px;
            color: var(--mpch-blue);
            font-weight: 500;
            text-decoration: none;
            transition: color 0.15s ease;
        }

        .bottom-link:hover {
            text-decoration: underline;
        }

        .bottom-link-muted {
            font-size: 13px;
            color: var(--text-muted);
            font-weight: 500;
            text-decoration: none;
            transition: color 0.15s ease;
        }

        .bottom-link-muted:hover {
            color: #334155;
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="challenge-container">
        <div class="card-custom">
            
            <!-- Franja Azul (82%) y Amarillo (18%) -->
            <div class="card-strip">
                <div class="strip-blue"></div>
                <div class="strip-yellow"></div>
            </div>

            <div class="card-body-custom">
                
                <!-- Escudo Institucional -->
                <div>
                    <img src="<?= Conectar::ruta() ?>public/static/illustrations/logomuni3.png" alt="Escudo MPCH" class="muni-shield-top" draggable="false">
                </div>

                <!-- Icono de Seguridad -->
                <div>
                    <div class="shield-icon-circle">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M12 3a12 12 0 0 0 8.5 3a12 12 0 0 1 -8.5 15a12 12 0 0 1 -8.5 -15a12 12 0 0 0 8.5 -3" />
                            <path d="M9 12l2 2l4 -4" />
                        </svg>
                    </div>
                </div>

                <!-- Título y Subtítulo -->
                <h1 class="challenge-title">Verificación en Dos Pasos</h1>
                <p class="challenge-subtitle">
                    Ingresa el código dinámico de 6 dígitos generado por tu aplicación 
                    <strong>Google Authenticator</strong> para el usuario con DNI <strong><?= htmlspecialchars($dniEnmascarado) ?></strong>.
                </p>

                <!-- Caja de Alertas Dinámicas -->
                <div id="alert2FA" class="alert d-none mb-3" role="alert"></div>

                <!-- Formulario de Desafío -->
                <form id="form2FAChallenge" autocomplete="off" novalidate>
                    <div>
                        <label class="form-label-code" for="codigo_2fa">
                            Código de Autenticación
                        </label>
                        <input 
                            type="text" 
                            id="codigo_2fa" 
                            name="codigo_2fa" 
                            class="input-code-digits" 
                            placeholder="0 0 0 0 0 0" 
                            maxlength="6" 
                            inputmode="numeric" 
                            pattern="[0-9]*" 
                            autocomplete="one-time-code" 
                            required
                            autofocus
                        />
                        <div class="hint-timer-text">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" />
                                <path d="M12 7v5l3 3" />
                            </svg>
                            <span>El código cambia cada 30 segundos en tu teléfono.</span>
                        </div>
                    </div>

                    <button type="submit" id="btnSubmit2FA" class="btn-yellow-action">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M5 12l5 5l10 -10" />
                        </svg>
                        <span>Verificar y Acceder</span>
                    </button>
                </form>

                <!-- Problemas con tu Celular -->
                <div class="trouble-section">
                    <div class="trouble-header">¿Problemas con tu celular?</div>
                    <a href="javascript:void(0)" id="linkIniciarRevinc" onclick="iniciarRevinculacionDispositivo()" class="trouble-link">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M6 5a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-8a2 2 0 0 1 -2 -2v-14z" />
                            <path d="M11 4h2" />
                            <path d="M12 17v.01" />
                        </svg>
                        <span>¿Cambiaste de teléfono? Vincular nuevo dispositivo</span>
                    </a>
                </div>

                <!-- Enlaces Inferiores -->
                <div class="bottom-links-row">
                    <a href="selector_metodo.php" class="bottom-link">
                        &larr; Cambiar método
                    </a>
                    <a href="<?= Conectar::ruta() ?>index.php" class="bottom-link-muted">
                        Cerrar sesión
                    </a>
                </div>

            </div>
        </div>
    </div>

    <!-- ========================================================
         MODAL 1: VERIFICAR IDENTIDAD POR CORREO ANTES DE RE-VINCULAR
         ======================================================== -->
    <div class="modal modal-blur fade" id="modalCorreoRevinc" tabindex="-1" role="dialog" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 500px;">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
                <div class="card-strip">
                    <div class="strip-blue"></div>
                    <div class="strip-yellow"></div>
                </div>

                <div class="modal-header bg-white border-bottom py-3 px-4">
                    <h5 class="modal-title fw-bold d-flex align-items-center gap-2" style="color: var(--mpch-blue); font-size: 16px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M12 3a12 12 0 0 0 8.5 3a12 12 0 0 1 -8.5 15a12 12 0 0 1 -8.5 -15a12 12 0 0 0 8.5 -3" />
                            <path d="M12 11m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                            <path d="M12 12l0 2.5" />
                        </svg>
                        Confirmar Identidad para Re-vincular
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4 text-center">
                    <p class="text-muted small mb-2">
                        Por seguridad, hemos enviado un código de 6 dígitos a tu correo institucional para autorizar la vinculación de un nuevo teléfono:
                    </p>
                    <div class="badge font-monospace fs-3 px-3 py-2 mb-3" style="background-color: #eaf2fa; color: var(--mpch-blue);" id="lblCorreoRevincDestino">
                        cargando...
                    </div>

                    <div class="my-3 px-2">
                        <label class="form-label text-start fw-bold text-muted small text-uppercase mb-2">
                            Código de Autorización
                        </label>
                        <input 
                            type="text" 
                            id="codigo_correo_revinc_input" 
                            class="input-code-digits mb-2" 
                            placeholder="0 0 0 0 0 0" 
                            maxlength="6" 
                            inputmode="numeric" 
                            pattern="[0-9]*" 
                            autocomplete="one-time-code"
                        />

                        <div class="d-flex align-items-center justify-content-center gap-2 text-muted small my-2">
                            <span class="badge fw-bold d-flex align-items-center gap-1" id="badgeTimerRevinc" style="background-color: #fef3c7; color: #b45309;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" />
                                    <path d="M12 7v5l3 3" />
                                </svg>
                                Expira en: <span id="lblCountdownRevinc" class="font-monospace">02:00</span>
                            </span>
                        </div>

                        <div id="alertEmailRevinc" class="alert alert-danger small d-none my-2" role="alert"></div>

                        <button type="button" id="btnValidarCodigoRevinc" class="btn-yellow-action mt-3" style="height: 48px;" onclick="validarCodigoRevinc()">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M12 3a12 12 0 0 0 8.5 3a12 12 0 0 1 -8.5 15a12 12 0 0 1 -8.5 -15a12 12 0 0 0 8.5 -3" />
                                <path d="M9 12l2 2l4 -4" />
                            </svg>
                            <span>Autorizar y Vincular Nuevo Celular</span>
                        </button>
                    </div>

                    <div class="mt-3 pt-3 border-top d-flex justify-content-between align-items-center">
                        <button type="button" class="btn btn-link link-secondary small p-0 text-decoration-none" data-bs-dismiss="modal">
                            Cancelar
                        </button>
                        <button type="button" class="btn btn-link link-primary small p-0 text-decoration-none fw-bold" id="btnReenviarRevinc" onclick="iniciarRevinculacionDispositivo()" disabled>
                            Reenviar (<span id="lblReenviarRevincCountdown">30</span>s)
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Integración del Componente Spinner MPCH -->
    <?php require_once __DIR__ . '/../../../public/componente-spinner/spinner.php'; ?>

    <!-- Dependencias JS -->
    <script src="<?= Conectar::ruta() ?>public/js/jquery.min.js"></script>
    <script src="<?= Conectar::ruta() ?>public/js/tabler.min.js" defer></script>
    <script src="<?= Conectar::ruta() ?>public/js/sweetalert2.all.min.js"></script>
    <script src="../assets/js/qrcode.min.js"></script>
    <script src="../assets/js/two_factor.js"></script>
    <script>
        window.TWO_FACTOR_API_URL = '<?= Conectar::ruta() ?>seguridad/2fa/controllers/TwoFactorController.php';
    </script>

    <!-- Modal 2: Enrolamiento Horizontal -->
    <?php require_once __DIR__ . '/modal_enrolamiento.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            TwoFactorApp.initChallenge();

            ['2fa_email_otp', '2fa_revinculacion_ok', '2fa_backup_pending', 'warn'].forEach(function(c) {
                document.cookie = c + '=; Path=/; Expires=Thu, 01 Jan 1970 00:00:01 GMT;';
                document.cookie = c + '=; Path=/<?= $_ENV['APP_NAME'] ?>/; Expires=Thu, 01 Jan 1970 00:00:01 GMT;';
            });
        });

        let g_revincTimer = null;
        let g_revincCooldownTimer = null;
        let g_revincSecs = 120;
        let g_revincCooldownSecs = 30;
        let g_isSendingRevinc = false;
        let g_isValidatingRevinc = false;

        const G_TIENE_CORREO = <?= $tieneCorreo ? 'true' : 'false' ?>;

        function ocultarModalCarga() {
            const modalEl = document.getElementById('modalCargaEnvio');
            if (modalEl) {
                if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    const modalInstance = bootstrap.Modal.getInstance(modalEl);
                    if (modalInstance) {
                        modalInstance.hide();
                    }
                }
                $(modalEl).modal('hide');
                setTimeout(() => {
                    $(modalEl).removeClass('show').css('display', 'none');
                    $('body').removeClass('modal-open').css('overflow', '').css('padding-right', '');
                    $('.modal-backdrop').remove();
                }, 150);
            }
        }

        function iniciarRevinculacionDispositivo() {
            if (!G_TIENE_CORREO) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Sin Correo Registrado',
                        text: 'No cuentas con un correo institucional en el sistema para recibir el código de autorización. Por favor, comunícate con la Gerencia de Tecnología o Recursos Humanos para registrar tu correo institucional.',
                        confirmButtonText: 'Entendido',
                        confirmButtonColor: '#066ed0'
                    });
                } else {
                    alert('No cuentas con un correo institucional en el sistema para recibir el código de autorización.');
                }
                return;
            }

            if (g_isSendingRevinc) {
                return;
            }
            g_isSendingRevinc = true;

            const link = $('#linkIniciarRevinc');
            const btnReenviar = $('#btnReenviarRevinc');

            link.addClass('disabled pe-none text-muted').html('<span class="spinner-border spinner-border-sm me-1"></span>Enviando código...');
            btnReenviar.prop('disabled', true);

            $('#modalCorreoRevinc').modal('hide');
            showSpinner();
            $('#alertEmailRevinc').addClass('d-none');
            $('#codigo_correo_revinc_input').val('');

            $.ajax({
                url: window.TWO_FACTOR_API_URL + '?op=solicitar_codigo_correo',
                type: 'POST',
                dataType: 'json',
                data: { accion: 'revinculacion' },
                success: function(resp) {
                    hideSpinner();

                    if (resp.success) {
                        $('#lblCorreoRevincDestino').text(resp.correo_enmascarado || 'correo institucional');
                        setTimeout(() => {
                            $('#modalCorreoRevinc').modal('show');
                            iniciarTemporizadorRevinc(resp.expira_seg || 120);
                            setTimeout(() => {
                                $('#codigo_correo_revinc_input').focus();
                            }, 300);
                        }, 250);
                    } else if (resp.sin_correo) {
                        setTimeout(() => {
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'Sin Correo Institucional',
                                    text: resp.message || 'No cuentas con un correo institucional registrado.',
                                    confirmButtonText: 'Entendido',
                                    confirmButtonColor: '#066ed0'
                                });
                            } else {
                                alert(resp.message);
                            }
                        }, 250);
                    } else {
                        setTimeout(() => {
                            alert(resp.message || 'Error al enviar código de re-vinculación.');
                        }, 250);
                    }
                },
                error: function() {
                    hideSpinner();
                    setTimeout(() => {
                        alert('Error de conexión al solicitar código de re-vinculación.');
                    }, 250);
                },
                complete: function() {
                    g_isSendingRevinc = false;
                    link.removeClass('disabled pe-none text-muted').html('<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 5a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-8a2 2 0 0 1 -2 -2v-14z" /><path d="M11 4h2" /><path d="M12 17v.01" /></svg> <span>¿Cambiaste de teléfono? Vincular nuevo dispositivo</span>');
                }
            });
        }

        function iniciarTemporizadorRevinc(segundos) {
            clearInterval(g_revincTimer);
            clearInterval(g_revincCooldownTimer);

            g_revincSecs = segundos;
            g_revincCooldownSecs = 30;

            actualizarDisplayRevinc();

            g_revincTimer = setInterval(() => {
                g_revincSecs--;
                actualizarDisplayRevinc();

                if (g_revincSecs <= 0) {
                    clearInterval(g_revincTimer);
                    $('#alertEmailRevinc').text('El código ha expirado. Por favor, solicita uno nuevo.').removeClass('d-none');
                    $('#btnValidarCodigoRevinc').prop('disabled', true);
                }
            }, 1000);

            const btnReenviar = $('#btnReenviarRevinc');
            btnReenviar.prop('disabled', true);

            g_revincCooldownTimer = setInterval(() => {
                g_revincCooldownSecs--;
                if (g_revincCooldownSecs > 0) {
                    $('#lblReenviarRevincCountdown').text(g_revincCooldownSecs);
                } else {
                    clearInterval(g_revincCooldownTimer);
                    btnReenviar.prop('disabled', false).html('Reenviar ahora');
                }
            }, 1000);
        }

        function actualizarDisplayRevinc() {
            const min = Math.floor(g_revincSecs / 60);
            const seg = g_revincSecs % 60;
            const texto = String(min).padStart(2, '0') + ':' + String(seg).padStart(2, '0');
            $('#lblCountdownRevinc').text(texto);

            if (g_revincSecs <= 30) {
                $('#badgeTimerRevinc').css({'background-color': '#fee2e2', 'color': '#b91c1c'});
            } else {
                $('#badgeTimerRevinc').css({'background-color': '#fef3c7', 'color': '#b45309'});
            }
        }

        function validarCodigoRevinc() {
            if (g_isValidatingRevinc) return;

            const codigo = $('#codigo_correo_revinc_input').val().trim();
            if (codigo.length !== 6) {
                $('#alertEmailRevinc').text('Ingresa los 6 dígitos recibidos en tu correo.').removeClass('d-none');
                $('#codigo_correo_revinc_input').focus();
                return;
            }

            g_isValidatingRevinc = true;
            const btn = $('#btnValidarCodigoRevinc');
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Validando...');

            $.ajax({
                url: window.TWO_FACTOR_API_URL + '?op=verificar_codigo_correo',
                type: 'POST',
                dataType: 'json',
                data: {
                    codigo_correo: codigo,
                    accion: 'revinculacion'
                },
                success: function(resp) {
                    if (resp.success) {
                        $('#modalCorreoRevinc').modal('hide');
                        setTimeout(() => {
                            TwoFactorApp.openEnrollModal();
                        }, 400);
                    } else {
                        g_isValidatingRevinc = false;
                        btn.prop('disabled', false).html('<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 3a12 12 0 0 0 8.5 3a12 12 0 0 1 -8.5 15a12 12 0 0 1 -8.5 -15a12 12 0 0 0 8.5 -3" /><path d="M9 12l2 2l4 -4" /></svg> <span>Autorizar y Vincular Nuevo Celular</span>');
                        $('#alertEmailRevinc').text(resp.message).removeClass('d-none');
                        $('#codigo_correo_revinc_input').val('').focus();
                    }
                },
                error: function() {
                    g_isValidatingRevinc = false;
                    btn.prop('disabled', false).html('<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 3a12 12 0 0 0 8.5 3a12 12 0 0 1 -8.5 15a12 12 0 0 1 -8.5 -15a12 12 0 0 0 8.5 -3" /><path d="M9 12l2 2l4 -4" /></svg> <span>Autorizar y Vincular Nuevo Celular</span>');
                    $('#alertEmailRevinc').text('Error de conexión al validar código de re-vinculación.').removeClass('d-none');
                }
            });
        }

        $(document).ready(function() {
            $('#codigo_correo_revinc_input').on('input', function() {
                this.value = this.value.replace(/\D/g, '').slice(0, 6);
                if (this.value.length === 6) {
                    setTimeout(validarCodigoRevinc, 300);
                }
            });

            $('#codigo_correo_revinc_input').on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    validarCodigoRevinc();
                }
            });
        });
    </script>
</body>
</html>
