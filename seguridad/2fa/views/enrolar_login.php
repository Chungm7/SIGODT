<?php
/**
 * Vista de Enrolamiento 2FA en el Login
 * Rediseño Aprobado en Figma - Municipalidad Provincial de Chiclayo (MPCH)
 */

if (file_exists(__DIR__ . '/../../../config/conexion.php')) {
    require_once __DIR__ . '/../../../config/conexion.php';
    require_once __DIR__ . '/../../../config/jwt.php';
} else {
    require_once __DIR__ . '/../../config/conexion.php';
    require_once __DIR__ . '/../../config/jwt.php';
}

$jwt = new JwtHandler();
$pendingData = null;

if (!empty($_COOKIE['2fa_pending'])) {
    $dec = $jwt->decrypt($_COOKIE['2fa_pending']);
    if (!empty($dec->data)) {
        $pendingData = $dec->data;
    }
}
if (!$pendingData && !empty($_SESSION['2fa_pending_user'])) {
    $pendingData = (object)$_SESSION['2fa_pending_user'];
}

// Si no hay sesión pendiente, redirigir al login principal
if (!$pendingData || empty($pendingData->usu_doc)) {
    header("Location: " . Conectar::ruta());
    exit();
}

$nombreCompleto = trim(($pendingData->usu_nom) . ' ' . ($pendingData->usu_apep ?? ''));
$dniEnmascarado = substr($pendingData->usu_doc, 0, 2) . '****' . substr($pendingData->usu_doc, -2);
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

        .enroll-container {
            width: 100%;
            max-width: 960px;
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

        .card-header-custom {
            padding: 18px 32px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #ffffff;
        }

        .muni-brand-group {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .muni-brand-group img {
            width: 44px;
            height: auto;
            user-select: none;
        }

        .muni-subtitle {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.8px;
            color: #1e293b;
            text-transform: uppercase;
            margin-bottom: 2px;
        }

        .muni-header-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--mpch-blue);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
            letter-spacing: -0.3px;
        }

        .btn-close-header {
            background: none;
            border: none;
            color: #94a3b8;
            font-size: 22px;
            line-height: 1;
            padding: 4px 8px;
            cursor: pointer;
            border-radius: 6px;
            transition: color 0.15s ease;
            text-decoration: none;
        }
        .btn-close-header:hover {
            color: #0f172a;
        }

        /* Columnas del cuerpo */
        .enroll-left-col {
            padding: 36px 36px;
            border-right: 1px solid #f1f5f9;
        }

        .enroll-right-col {
            padding: 36px 36px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            background: #ffffff;
        }

        .step-badge-num {
            width: 24px;
            height: 24px;
            background-color: var(--mpch-blue);
            color: #ffffff;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
            flex-shrink: 0;
        }

        .step-heading {
            font-size: 15px;
            font-weight: 700;
            color: var(--text-dark);
            margin: 0;
        }

        .step-desc {
            font-size: 13px;
            color: var(--text-muted);
            margin-top: 6px;
            margin-bottom: 12px;
            line-height: 1.5;
        }

        .step-desc strong {
            color: #334155;
        }

        .store-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            color: #334155;
            text-decoration: none;
            transition: all 0.15s ease;
        }
        .store-pill:hover {
            background: #e2e8f0;
            color: #0f172a;
        }

        .divider-steps {
            height: 1px;
            background-color: #f1f5f9;
            margin: 24px 0;
        }

        /* Contenedor QR */
        .qr-card-box {
            background: #ffffff;
            padding: 12px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            display: inline-block;
            margin: 8px auto 14px;
        }

        .manual-secret-toggle {
            font-size: 13px;
            color: var(--mpch-blue);
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .manual-secret-toggle:hover {
            text-decoration: underline;
        }

        .manual-secret-box {
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 8px 12px;
            font-family: monospace;
            font-size: 14px;
            font-weight: 600;
            color: #0f172a;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 8px;
        }

        /* Sección Derecha */
        .device-icon-circle {
            width: 56px;
            height: 56px;
            background-color: #e0f0fe;
            color: var(--mpch-blue);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
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
            height: 54px;
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

        .security-rfc-note {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        /* Footer de la tarjeta */
        .card-footer-custom {
            padding: 16px 32px;
            border-top: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #ffffff;
            font-size: 13px;
        }

        .footer-cancel-link {
            color: var(--mpch-blue);
            text-decoration: none;
            font-weight: 500;
        }
        .footer-cancel-link:hover {
            text-decoration: underline;
        }

        @media (max-width: 991.98px) {
            .enroll-left-col {
                border-right: none;
                border-bottom: 1px solid #f1f5f9;
                padding: 28px 24px;
            }
            .enroll-right-col {
                padding: 28px 24px;
            }
            .card-header-custom {
                padding: 16px 20px;
            }
            .card-footer-custom {
                padding: 14px 20px;
            }
        }
    </style>
</head>

<body>
    <div class="enroll-container">
        <div class="card-custom">
            
            <!-- Franja Azul (82%) y Amarillo (18%) -->
            <div class="card-strip">
                <div class="strip-blue"></div>
                <div class="strip-yellow"></div>
            </div>

            <!-- Cabecera Institucional -->
            <div class="card-header-custom">
                <div class="muni-brand-group">
                    <img src="<?= Conectar::ruta() ?>public/static/illustrations/logomuni3.png" alt="Escudo MPCH" draggable="false">
                    <div>
                        <div class="muni-subtitle">MUNICIPALIDAD PROVINCIAL DE CHICLAYO</div>
                        <h1 class="muni-header-title">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M12 3a12 12 0 0 0 8.5 3a12 12 0 0 1 -8.5 15a12 12 0 0 1 -8.5 -15a12 12 0 0 0 8.5 -3" />
                                <path d="M9 12l2 2l4 -4" />
                            </svg>
                            <span>Configuración de Doble Factor de Autenticación (2FA)</span>
                        </h1>
                    </div>
                </div>
                <div>
                    <a href="selector_metodo.php" class="btn-close-header" title="Cerrar">&times;</a>
                </div>
            </div>

            <!-- Contenido Dividido en 2 Columnas -->
            <div class="row g-0">
                
                <!-- Columna Izquierda: Paso 1 y Paso 2 -->
                <div class="col-12 col-lg-6 enroll-left-col">
                    
                    <!-- Paso 1 -->
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="step-badge-num">1</span>
                            <h2 class="step-heading">Descarga la Aplicación</h2>
                        </div>
                        <p class="step-desc">
                            Instala gratis en tu teléfono <strong>Google Authenticator</strong> o <strong>Microsoft Authenticator</strong>:
                        </p>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="store-pill">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M4 10l0 6" /><path d="M20 10l0 6" />
                                    <path d="M7 9h10v8a1 1 0 0 1 -1 1h-8a1 1 0 0 1 -1 -1v-8a5 5 0 0 1 10 0" />
                                    <path d="M8 3l1 2" /><path d="M16 3l-1 2" />
                                    <path d="M9 18l0 3" /><path d="M15 18l0 3" />
                                </svg>
                                <span>Google Play Store (Android)</span>
                            </span>
                            <span class="store-pill">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M8.286 7.008c-3.216 0 -4.286 3.23 -4.286 5.92c0 3.229 2.143 8.072 4.286 8.072c1.165 -.05 1.799 -.911 3.286 -.911c1.487 0 2.03 .911 3.286 .911c2.143 0 4.286 -4.843 4.286 -8.072c0 -2.69 -1.07 -5.92 -4.286 -5.92c-2 0 -2.464 1.303 -3.286 1.303c-.822 0 -1.286 -1.303 -3.286 -1.303z" />
                                    <path d="M12 4a2 2 0 0 0 2 -2a2 2 0 0 0 -2 2" />
                                </svg>
                                <span>App Store (iPhone)</span>
                            </span>
                        </div>
                    </div>

                    <div class="divider-steps"></div>

                    <!-- Paso 2 -->
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="step-badge-num">2</span>
                            <h2 class="step-heading">Escanea el Código QR</h2>
                        </div>
                        <p class="step-desc">
                            Abre la aplicación en tu celular, pulsa en <strong>(+)</strong> y selecciona <strong>Escanear código QR</strong>:
                        </p>

                        <!-- QR Centrado -->
                        <div class="text-center">
                            <div class="qr-card-box">
                                <div id="qrContainerLogin">
                                    <div class="spinner-border text-primary my-4" role="status"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Clave Manual Alternativa -->
                        <div class="text-center mt-2">
                            <a class="manual-secret-toggle" data-bs-toggle="collapse" href="#collapseManualSecretLogin" role="button">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M2 5m0 2a2 2 0 0 1 2 -2h16a2 2 0 0 1 2 2v10a2 2 0 0 1 -2 2h-16a2 2 0 0 1 -2 -2z" />
                                    <path d="M6 10l0 .01" /><path d="M10 10l0 .01" /><path d="M14 10l0 .01" /><path d="M18 10l0 .01" />
                                    <path d="M6 14l0 .01" /><path d="M18 14l0 .01" /><path d="M10 14l4 0" />
                                </svg>
                                <span>¿No puedes escanear con la cámara? Configurar con clave manual</span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M12 5l0 14" /><path d="M16 15l-4 4" /><path d="M8 15l4 4" />
                                </svg>
                            </a>
                            <div class="collapse text-start mt-2" id="collapseManualSecretLogin">
                                <div class="manual-secret-box">
                                    <span id="txtManualSecretLogin" class="font-monospace text-dark fw-bold">—</span>
                                    <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2" onclick="TwoFactorApp.copyToClipboard(document.getElementById('txtManualSecretLogin').innerText, 'Clave secreta copiada')">
                                        Copiar
                                    </button>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>

                <!-- Columna Derecha: Paso 3 -->
                <div class="col-12 col-lg-6 enroll-right-col">
                    
                    <div class="device-icon-circle">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M6 5a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-8a2 2 0 0 1 -2 -2v-14z" />
                            <path d="M11 4h2" />
                            <path d="M12 17v.01" />
                        </svg>
                    </div>

                    <div class="d-inline-flex align-items-center gap-2 mb-1">
                        <span class="step-badge-num">3</span>
                        <h2 class="step-heading" style="font-size: 16px;">Verificación y Activación</h2>
                    </div>
                    <p class="step-desc text-center" style="max-width: 320px;">
                        Ingresa el código dinámico de 6 dígitos que muestra tu teléfono:
                    </p>

                    <!-- Formulario -->
                    <div class="w-100" style="max-width: 320px;">
                        <label class="form-label-code" for="codigo_confirmacion_login">
                            Código de 6 Dígitos
                        </label>
                        <input 
                            type="text" 
                            id="codigo_confirmacion_login" 
                            class="input-code-digits" 
                            placeholder="0 0 0 0 0 0" 
                            maxlength="6" 
                            inputmode="numeric" 
                            pattern="[0-9]*"
                            autocomplete="one-time-code"
                            autofocus
                        />
                        <div class="hint-timer-text">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" />
                                <path d="M12 7v5l3 3" />
                            </svg>
                            <span>El código numérico se renueva cada 30 segundos</span>
                        </div>

                        <div id="alertConfirmLogin" class="alert alert-danger small d-none my-2" role="alert"></div>

                        <button type="button" id="btnConfirmarLogin" class="btn-yellow-action" onclick="confirmarActivacionLogin()">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M12 3a12 12 0 0 0 8.5 3a12 12 0 0 1 -8.5 15a12 12 0 0 1 -8.5 -15a12 12 0 0 0 8.5 -3" />
                                <path d="M9 12l2 2l4 -4" />
                            </svg>
                            <span>Activar 2FA</span>
                        </button>

                        <div class="security-rfc-note">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M5 11m0 2a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v6a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2z" />
                                <path d="M8 11v-4a4 4 0 1 1 8 0v4" />
                            </svg>
                            <span>Protegido mediante algoritmo estándar RFC 6238</span>
                        </div>
                    </div>

                </div>

            </div>

            <!-- Footer -->
            <div class="card-footer-custom">
                <span class="text-muted">Municipalidad Provincial de Chiclayo</span>
                <a href="selector_metodo.php" class="footer-cancel-link">Cancelar</a>
            </div>

        </div>
    </div>

    <!-- Scripts Locales -->
    <script src="<?= Conectar::ruta() ?>public/js/jquery.min.js"></script>
    <script src="<?= Conectar::ruta() ?>public/js/tabler.min.js" defer></script>
    <script src="../assets/js/qrcode.min.js"></script>
    <script src="../assets/js/two_factor.js"></script>

    <script>
    window.TWO_FACTOR_API_URL = '<?= Conectar::ruta() ?>seguridad/2fa/controllers/TwoFactorController.php';

    document.addEventListener('DOMContentLoaded', function() {
        iniciarEnrolamientoLogin();

        const inputDigit = document.getElementById('codigo_confirmacion_login');
        if (inputDigit) {
            inputDigit.addEventListener('input', function() {
                this.value = this.value.replace(/\D/g, '').slice(0, 6);
                if (this.value.length === 6) {
                    setTimeout(() => {
                        confirmarActivacionLogin();
                    }, 300);
                }
            });
        }
    });

    function iniciarEnrolamientoLogin() {
        $('#qrContainerLogin').html('<div class="spinner-border text-primary my-4" role="status"></div>');
        $('#alertConfirmLogin').addClass('d-none');
        $('#codigo_confirmacion_login').val('');

        $.ajax({
            url: window.TWO_FACTOR_API_URL + '?op=iniciar_enrolamiento',
            type: 'POST',
            dataType: 'json',
            success: function(resp) {
                if (resp.success) {
                    $('#txtManualSecretLogin').text(resp.secret_fmt || resp.secret);
                    TwoFactorApp.renderLocalQr('qrContainerLogin', resp.otp_uri, 190);
                } else {
                    $('#qrContainerLogin').html('<div class="text-danger small p-3">' + resp.message + '</div>');
                }
            },
            error: function() {
                $('#qrContainerLogin').html('<div class="text-danger small p-3">Error al generar clave de vinculación.</div>');
            }
        });
    }

    function confirmarActivacionLogin() {
        const code = $('#codigo_confirmacion_login').val().trim();
        if (code.length !== 6) {
            $('#alertConfirmLogin').text('Ingresa los 6 dígitos que aparecen en tu aplicación.').removeClass('d-none');
            $('#codigo_confirmacion_login').focus();
            return;
        }

        const btn = $('#btnConfirmarLogin');
        if (btn.data('loading')) return;
        btn.data('loading', true).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Validando...');

        $.ajax({
            url: window.TWO_FACTOR_API_URL + '?op=confirmar_enrolamiento',
            type: 'POST',
            dataType: 'json',
            data: { codigo_confirmacion: code },
            success: function(resp) {
                if (resp.success) {
                    btn.html('<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10" /></svg> <span>¡Activación Exitosa!</span>');
                    window.location.href = resp.redirect || '../../view/inicio/';
                } else {
                    btn.data('loading', false);
                    btn.prop('disabled', false).html('<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 3a12 12 0 0 0 8.5 3a12 12 0 0 1 -8.5 15a12 12 0 0 1 -8.5 -15a12 12 0 0 0 8.5 -3" /><path d="M9 12l2 2l4 -4" /></svg> <span>Activar 2FA</span>');
                    $('#alertConfirmLogin').text(resp.message).removeClass('d-none');
                    $('#codigo_confirmacion_login').val('').focus();
                }
            },
            error: function() {
                btn.data('loading', false);
                btn.prop('disabled', false).html('<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 3a12 12 0 0 0 8.5 3a12 12 0 0 1 -8.5 15a12 12 0 0 1 -8.5 -15a12 12 0 0 0 8.5 -3" /><path d="M9 12l2 2l4 -4" /></svg> <span>Activar 2FA</span>');
                $('#alertConfirmLogin').text('Error de conexión al activar el 2FA.').removeClass('d-none');
            }
        });
    }
    </script>
</body>
</html>
