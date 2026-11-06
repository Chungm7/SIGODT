<?php
/**
 * Vista de Selección de Método de Autenticación 2FA
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
if (!$pendingData && !empty($_SESSION['2fa_pending_user'])) {
    $pendingData = (object)$_SESSION['2fa_pending_user'];
}

// Si no hay datos pendientes, redirigir al login principal
if (!$pendingData || empty($pendingData->usu_doc)) {
    header("Location: " . Conectar::ruta());
    exit();
}

$nombreCompleto = trim(($pendingData->usu_nom ?? 'Servidor') . ' ' . ($pendingData->usu_apep ?? ''));
$dniEnmascarado = substr($pendingData->usu_doc, 0, 2) . '****' . substr($pendingData->usu_doc, -2);

// Verificar si el usuario ya tiene configurado Google Authenticator
$statusDispositivo = TwoFactorModel::checkStatus($pendingData->usu_doc);
$tieneDispositivo = !empty($statusDispositivo['enabled']);
$urlDispositivo = $tieneDispositivo ? 'desafio_login.php' : 'enrolar_login.php';

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
            --card-border: #dbeafe;
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

        .auth-container {
            width: 100%;
            max-width: 880px;
            margin: auto;
        }

        /* Card Principal */
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

        /* Header de la tarjeta */
        .card-header-custom {
            padding: 20px 32px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
            background: #ffffff;
        }

        .muni-brand-group {
            display: flex;
            align-items: center;
            gap: 16px;
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
            font-size: 19px;
            font-weight: 700;
            color: var(--mpch-blue);
            margin: 0;
            letter-spacing: -0.3px;
        }

        /* Badge de usuario */
        .user-pill-badge {
            background-color: #eaf2fa;
            color: var(--mpch-blue);
            font-size: 13px;
            font-weight: 600;
            padding: 8px 16px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            letter-spacing: 0.2px;
        }

        /* Cuerpo */
        .card-body-custom {
            padding: 40px 48px;
        }

        .section-title {
            font-size: 26px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 6px;
            text-align: center;
            letter-spacing: -0.4px;
        }

        .section-subtitle {
            font-size: 14px;
            color: var(--text-muted);
            text-align: center;
            margin-bottom: 36px;
        }

        /* Tarjetas de método */
        .method-box {
            border: 1.5px solid var(--card-border);
            border-radius: 16px;
            background: #ffffff;
            padding: 36px 28px 28px;
            text-align: center;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: all 0.25s ease;
            position: relative;
        }

        .method-box:hover {
            border-color: #93c5fd;
            transform: translateY(-3px);
            box-shadow: 0 12px 24px -6px rgba(6, 110, 208, 0.12);
        }

        .method-icon-wrap {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background-color: #e0f0fe;
            color: var(--mpch-blue);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }

        .method-name {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 10px;
        }

        .method-desc {
            font-size: 13.5px;
            color: var(--text-muted);
            line-height: 1.55;
            margin-bottom: 24px;
            min-height: 48px;
        }

        .method-desc strong {
            color: #334155;
        }

        /* Botón Amarillo Acción */
        .btn-yellow-action {
            width: 100%;
            height: 46px;
            background-color: var(--mpch-yellow);
            color: #0f172a;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
            text-decoration: none !important;
            transition: background-color 0.15s ease, transform 0.05s ease;
        }

        .btn-yellow-action:hover {
            background-color: var(--mpch-yellow-hover);
            color: #0f172a;
        }

        .btn-yellow-action:active {
            transform: scale(0.99);
        }

        /* Enlace de cancelar */
        .cancel-back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--mpch-blue);
            font-size: 13.5px;
            font-weight: 500;
            text-decoration: none;
            transition: color 0.15s ease;
        }

        .cancel-back-link:hover {
            color: #055cb1;
            text-decoration: underline;
        }

        /* Estilos específicos Modal Correo según Figma */
        .input-email-digits {
            width: 100%;
            height: 64px;
            background-color: #ffffff;
            border: 1.5px solid #cbd5e1;
            border-radius: 12px;
            font-size: 32px;
            font-weight: 700;
            text-align: center;
            letter-spacing: 12px;
            color: var(--text-dark);
            font-family: 'SFMono-Regular', Menlo, Monaco, Consolas, monospace;
            outline: none;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .input-email-digits::placeholder {
            color: #94a3b8;
            letter-spacing: 10px;
            font-style: italic;
            font-weight: 600;
        }

        .input-email-digits:focus {
            border-color: var(--mpch-blue);
            box-shadow: 0 0 0 3px rgba(6, 110, 208, 0.15);
        }

        .badge-timer-pill {
            background-color: #fef3c7;
            color: #b45309;
            font-size: 12.5px;
            font-weight: 600;
            padding: 6px 16px;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }

        @media (max-width: 768px) {
            .card-body-custom {
                padding: 30px 20px;
            }
            .card-header-custom {
                padding: 16px 20px;
            }
            .section-title {
                font-size: 22px;
            }
        }
    </style>
</head>

<body>
    <div class="auth-container">
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
                        <h1 class="muni-header-title">Verificación de Seguridad en Dos Pasos</h1>
                    </div>
                </div>
                <div>
                    <span class="user-pill-badge">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" />
                            <path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" />
                        </svg>
                        <span><?= htmlspecialchars($nombreCompleto) ?> (DNI: <?= htmlspecialchars($dniEnmascarado) ?>)</span>
                    </span>
                </div>
            </div>

            <!-- Cuerpo con las 2 Tarjetas -->
            <div class="card-body-custom">
                <h2 class="section-title">Elige tu método de acceso</h2>
                <p class="section-subtitle">Selecciona la vía por la cual deseas confirmar tu identidad para ingresar al sistema:</p>

                <div class="row g-4 justify-content-center">
                    
                    <!-- Tarjeta 1: Correo Institucional -->
                    <div class="col-12 col-md-6">
                        <div class="method-box">
                            <div>
                                <div class="method-icon-wrap">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <path d="M3 7a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v10a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-10z" />
                                        <path d="M3 7l9 6l9 -6" />
                                    </svg>
                                </div>
                                <h3 class="method-name">Correo Institucional</h3>
                                <p class="method-desc">
                                    Recibe un código dinámico de <strong>6 dígitos</strong> en tu cuenta de correo institucional de la municipalidad.
                                </p>
                            </div>
                            <button type="button" class="btn-yellow-action" id="btnElegirCorreo" onclick="iniciarFlujoCorreo()">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M10 14l11 -11" />
                                    <path d="M21 3l-6.5 18a.55 .55 0 0 1 -1 0l-3.5 -7l-7 -3.5a.55 .55 0 0 1 0 -1l18 -6.5" />
                                </svg>
                                <span>Ingresar por Correo</span>
                            </button>
                        </div>
                    </div>

                    <!-- Tarjeta 2: Dispositivo Móvil -->
                    <div class="col-12 col-md-6">
                        <div class="method-box">
                            <div>
                                <div class="method-icon-wrap">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <path d="M6 5a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-8a2 2 0 0 1 -2 -2v-14z" />
                                        <path d="M11 4h2" />
                                        <path d="M12 17v.01" />
                                    </svg>
                                </div>
                                <h3 class="method-name">Dispositivo Móvil</h3>
                                <p class="method-desc">
                                    Genera tu código de <strong>6 dígitos</strong> de forma instantánea mediante <strong>Google Authenticator</strong> en tu celular.
                                </p>
                            </div>
                            <a href="<?= $urlDispositivo ?>" class="btn-yellow-action">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M4 4m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z" />
                                    <path d="M14 4m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z" />
                                    <path d="M4 14m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z" />
                                    <path d="M14 14l1 0" /><path d="M14 17l0 1" /><path d="M14 20l2 0" /><path d="M17 17l1 0" /><path d="M20 17l0 1" /><path d="M20 20l1 0" />
                                </svg>
                                <span><?= $tieneDispositivo ? 'Ingresar con Dispositivo' : 'Vincular Dispositivo (Primera Vez)' ?></span>
                            </a>
                        </div>
                    </div>

                </div>

                <!-- Enlace para volver -->
                <div class="text-center mt-5">
                    <a href="<?= Conectar::ruta() ?>index.php" class="cancel-back-link">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M5 12l14 0" />
                            <path d="M5 12l6 6" />
                            <path d="M5 12l6 -6" />
                        </svg>
                        <span>Cancelar y volver al inicio de sesión</span>
                    </a>
                </div>
            </div>

        </div>
    </div>

    <!-- ========================================================
         MODAL DE INGRESO DEL CÓDIGO POR CORREO (FIGMA IDÉNTICO)
         ======================================================== -->
    <div class="modal modal-blur fade" id="modalCodigoCorreo" tabindex="-1" role="dialog" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 520px;">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden; background: #ffffff;">
                
                <!-- Franja Azul (82%) y Amarillo (18%) -->
                <div class="card-strip">
                    <div class="strip-blue"></div>
                    <div class="strip-yellow"></div>
                </div>

                <!-- Cabecera del Modal -->
                <div class="modal-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
                    <h5 class="modal-title fw-bold d-flex align-items-center gap-2 mb-0" style="color: var(--mpch-blue); font-size: 18px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M3 7a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v10a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-10z" />
                            <path d="M3 7l9 6l9 -6" />
                        </svg>
                        <span>Código de Seguridad por Correo</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Cuerpo del Modal -->
                <div class="modal-body p-4 text-center">
                    <p class="text-muted small mb-3" style="font-size: 13.5px; color: #475569;">
                        Hemos enviado un código dinámico de 6 dígitos a tu correo institucional:
                    </p>
                    
                    <div class="badge font-monospace mb-4" style="background-color: #eaf2fa; color: var(--mpch-blue); font-size: 15px; font-weight: 700; padding: 10px 20px; border-radius: 8px;" id="lblCorreoDestino">
                        cargando...
                    </div>

                    <!-- Input Grande de 6 Dígitos -->
                    <div class="my-2 px-1">
                        <input 
                            type="text" 
                            id="codigo_correo_input" 
                            class="input-email-digits mb-2" 
                            placeholder="0 0 0 0 0 0" 
                            maxlength="6" 
                            inputmode="numeric" 
                            pattern="[0-9]*" 
                            autocomplete="one-time-code"
                            autofocus
                        />

                        <!-- Temporizador regresivo: Pill con reloj -->
                        <div class="d-flex align-items-center justify-content-center my-2">
                            <span class="badge-timer-pill" id="badgeTimer">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" />
                                    <path d="M12 7v5l3 3" />
                                </svg>
                                <span>Expira en: <span id="lblCountdownTimer" class="font-monospace">02:00</span></span>
                            </span>
                        </div>

                        <!-- Alerta de Error -->
                        <div id="alertEmailCode" class="alert alert-danger small d-none my-2" role="alert"></div>

                        <!-- Botón Principal Amarillo -->
                        <button type="button" id="btnValidarCodigoCorreo" class="btn-yellow-action mt-3" style="height: 48px;" onclick="validarCodigoCorreo()">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M12 3a12 12 0 0 0 8.5 3a12 12 0 0 1 -8.5 15a12 12 0 0 1 -8.5 -15a12 12 0 0 0 8.5 -3" />
                                <path d="M9 12l2 2l4 -4" />
                            </svg>
                            <span>Verificar e Ingresar al Sistema</span>
                        </button>
                    </div>

                    <!-- Reenviar o Cambiar Método -->
                    <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
                        <button type="button" class="btn btn-link link-secondary small p-0 text-decoration-none d-inline-flex align-items-center gap-1" data-bs-dismiss="modal" style="color: #64748b; font-size: 13px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M5 12l14 0" />
                                <path d="M5 12l6 6" />
                                <path d="M5 12l6 -6" />
                            </svg>
                            <span>Cambiar método</span>
                        </button>
                        <button type="button" class="btn btn-link small p-0 text-decoration-none fw-bold" id="btnReenviarCodigo" onclick="iniciarFlujoCorreo()" disabled style="color: var(--mpch-blue); font-size: 13px;">
                            Reenviar (<span id="lblReenviarCountdown">30</span>s)
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Integración del Componente Spinner MPCH -->
    <?php require_once __DIR__ . '/../../../public/componente-spinner/spinner.php'; ?>

    <script src="<?= Conectar::ruta() ?>public/js/jquery.min.js"></script>
    <script src="<?= Conectar::ruta() ?>public/js/tabler.min.js" defer></script>
    <script src="<?= Conectar::ruta() ?>public/js/sweetalert2.all.min.js"></script>
    <script>
    window.TWO_FACTOR_API_URL = '<?= Conectar::ruta() ?>seguridad/2fa/controllers/TwoFactorController.php';

    // Purgar cookies residuales para evitar sobrecarga de encabezados HTTP
    ['2fa_email_otp', '2fa_revinculacion_ok', '2fa_backup_pending', 'warn'].forEach(function(c) {
        document.cookie = c + '=; Path=/; Expires=Thu, 01 Jan 1970 00:00:01 GMT;';
        document.cookie = c + '=; Path=/<?= $_ENV['APP_NAME'] ?>/; Expires=Thu, 01 Jan 1970 00:00:01 GMT;';
    });

    let g_timerInterval = null;
    let g_resendInterval = null;
    let g_segundosRestantes = 120;
    let g_resendCooldown = 30;
    let g_isSendingEmailCode = false;
    let g_isValidatingEmailCode = false;

    const G_TIENE_CORREO = <?= $tieneCorreo ? 'true' : 'false' ?>;
    const URL_DISPOSITIVO = '<?= $urlDispositivo ?>';

    function iniciarFlujoCorreo() {
        if (!G_TIENE_CORREO) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Sin Correo Institucional',
                    text: 'No cuentas con un correo institucional registrado. Por favor, ingresa utilizando tu aplicación de doble factor (Google Authenticator).',
                    confirmButtonText: 'Ingresar con Dispositivo',
                    confirmButtonColor: '#066ed0',
                    showCancelButton: true,
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = URL_DISPOSITIVO;
                    }
                });
            } else {
                alert('No cuentas con un correo institucional registrado. Por favor, ingresa con tu dispositivo.');
            }
            return;
        }

        if (g_isSendingEmailCode) {
            return;
        }
        g_isSendingEmailCode = true;

        const btn = $('#btnElegirCorreo');
        const card = $('.method-box').first();
        const btnReenviar = $('#btnReenviarCodigo');

        btn.prop('disabled', true).addClass('disabled').html('<span class="spinner-border spinner-border-sm me-1"></span>Enviando código...');
        card.css('pointer-events', 'none').css('opacity', '0.7');
        btnReenviar.prop('disabled', true);

        $('#modalCodigoCorreo').modal('hide');
        showSpinner(); // Mostrar el spinner institucional MPCH
        $('#alertEmailCode').addClass('d-none');
        $('#codigo_correo_input').val('');

        $.ajax({
            url: window.TWO_FACTOR_API_URL + '?op=solicitar_codigo_correo',
            type: 'POST',
            dataType: 'json',
            success: function(resp) {
                hideSpinner();

                if (resp.success) {
                    $('#lblCorreoDestino').text(resp.correo_enmascarado || 'correo institucional');
                    setTimeout(() => {
                        $('#modalCodigoCorreo').modal('show');
                        iniciarTemporizador(resp.expira_seg || 120);
                        setTimeout(() => {
                            $('#codigo_correo_input').focus();
                        }, 300);
                    }, 250);
                } else if (resp.sin_correo) {
                    setTimeout(() => {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Sin Correo Institucional',
                                text: resp.message || 'No cuentas con un correo institucional registrado.',
                                confirmButtonText: 'Ingresar con Dispositivo',
                                confirmButtonColor: '#066ed0',
                                showCancelButton: true,
                                cancelButtonText: 'Cancelar'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.href = URL_DISPOSITIVO;
                                }
                            });
                        } else {
                            alert(resp.message);
                        }
                    }, 250);
                } else {
                    setTimeout(() => {
                        alert(resp.message || 'Error al enviar código al correo.');
                    }, 250);
                }
            },
            error: function() {
                hideSpinner();
                setTimeout(() => {
                    alert('Error de conexión con el servidor al solicitar código.');
                }, 250);
            },
            complete: function() {
                g_isSendingEmailCode = false;
                btn.prop('disabled', false).removeClass('disabled').html('<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 14l11 -11" /><path d="M21 3l-6.5 18a.55 .55 0 0 1 -1 0l-3.5 -7l-7 -3.5a.55 .55 0 0 1 0 -1l18 -6.5" /></svg> <span>Ingresar por Correo</span>');
                card.css('pointer-events', 'auto').css('opacity', '1');
            }
        });
    }

    function iniciarTemporizador(segundos) {
        clearInterval(g_timerInterval);
        clearInterval(g_resendInterval);

        g_segundosRestantes = segundos;
        g_resendCooldown = 30;

        actualizarDisplayTimer();

        g_timerInterval = setInterval(() => {
            g_segundosRestantes--;
            actualizarDisplayTimer();

            if (g_segundosRestantes <= 0) {
                clearInterval(g_timerInterval);
                $('#alertEmailCode').text('El código de 6 dígitos ha expirado. Por favor, solicita uno nuevo.').removeClass('d-none');
                $('#btnValidarCodigoCorreo').prop('disabled', true);
            }
        }, 1000);

        const btnReenviar = $('#btnReenviarCodigo');
        btnReenviar.prop('disabled', true);

        g_resendInterval = setInterval(() => {
            g_resendCooldown--;
            if (g_resendCooldown > 0) {
                $('#lblReenviarCountdown').text(g_resendCooldown);
            } else {
                clearInterval(g_resendInterval);
                btnReenviar.prop('disabled', false).html('Reenviar código ahora');
            }
        }, 1000);
    }

    function actualizarDisplayTimer() {
        const min = Math.floor(g_segundosRestantes / 60);
        const seg = g_segundosRestantes % 60;
        const texto = String(min).padStart(2, '0') + ':' + String(seg).padStart(2, '0');
        $('#lblCountdownTimer').text(texto);

        if (g_segundosRestantes <= 30) {
            $('#badgeTimer').css({'background-color': '#fee2e2', 'color': '#b91c1c'});
        } else {
            $('#badgeTimer').css({'background-color': '#fef3c7', 'color': '#b45309'});
        }
    }

    function validarCodigoCorreo() {
        if (g_isValidatingEmailCode) return;

        const codigo = $('#codigo_correo_input').val().trim();
        if (codigo.length !== 6) {
            $('#alertEmailCode').text('Ingresa los 6 dígitos recibidos en tu correo institucional.').removeClass('d-none');
            $('#codigo_correo_input').focus();
            return;
        }

        g_isValidatingEmailCode = true;
        const btn = $('#btnValidarCodigoCorreo');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Validando...');

        $.ajax({
            url: window.TWO_FACTOR_API_URL + '?op=verificar_codigo_correo',
            type: 'POST',
            dataType: 'json',
            data: { 
                codigo_correo: codigo,
                accion: 'login'
            },
            success: function(resp) {
                if (resp.success) {
                    btn.html('<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10" /></svg> <span>Acceso Concedido</span>');
                    window.location.href = resp.redirect || '../../view/inicio/';
                } else {
                    g_isValidatingEmailCode = false;
                    btn.prop('disabled', false).html('<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 3a12 12 0 0 0 8.5 3a12 12 0 0 1 -8.5 15a12 12 0 0 1 -8.5 -15a12 12 0 0 0 8.5 -3" /><path d="M9 12l2 2l4 -4" /></svg> <span>Verificar e Ingresar al Sistema</span>');
                    $('#alertEmailCode').text(resp.message).removeClass('d-none');
                    $('#codigo_correo_input').val('').focus();
                }
            },
            error: function() {
                g_isValidatingEmailCode = false;
                btn.prop('disabled', false).html('<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 3a12 12 0 0 0 8.5 3a12 12 0 0 1 -8.5 15a12 12 0 0 1 -8.5 -15a12 12 0 0 0 8.5 -3" /><path d="M9 12l2 2l4 -4" /></svg> <span>Verificar e Ingresar al Sistema</span>');
                $('#alertEmailCode').text('Error de conexión al validar código.').removeClass('d-none');
            }
        });
    }

    $(document).ready(function() {
        $('#codigo_correo_input').on('input', function() {
            this.value = this.value.replace(/\D/g, '').slice(0, 6);
            if (this.value.length === 6) {
                setTimeout(validarCodigoCorreo, 300);
            }
        });

        $('#codigo_correo_input').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                validarCodigoCorreo();
            }
        });
    });
    </script>
</body>
</html>
