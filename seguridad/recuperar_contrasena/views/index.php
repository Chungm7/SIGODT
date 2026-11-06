<?php
require_once __DIR__ . '/../../../config/conexion.php';
require_once __DIR__ . '/../../../config/jwt.php';

$jwt = new JwtHandler();

function maskEmailAddress($email) {
    $email = trim((string)$email);
    if (empty($email) || strpos($email, '@') === false) return '';
    $parts = explode('@', $email, 2);
    $user = $parts[0];
    $domain = $parts[1];
    $userLen = strlen($user);
    if ($userLen <= 2) {
        $maskedUser = substr($user, 0, 1) . '*';
    } elseif ($userLen <= 4) {
        $maskedUser = substr($user, 0, 2) . '**';
    } else {
        $maskedUser = substr($user, 0, 2) . '***' . substr($user, -1);
    }
    return $maskedUser . '@' . $domain;
}

// Verificar token de recuperación
$userData = null;
if (!empty($_COOKIE['blanqueamiento'])) {
    $dec = $jwt->decrypt($_COOKIE['blanqueamiento']);
    if (!empty($dec->data)) {
        $userData = $dec->data;
    }
} elseif (!empty($_SESSION['blanqueamiento_user'])) {
    $userData = (object)$_SESSION['blanqueamiento_user'];
}

if (!$userData || empty($userData->usu_doc)) {
    header("Location: " . Conectar::ruta() . "index.php");
    exit();
}

$dniUsuario = $userData->usu_doc ?? '';
$nombreUsuario = !empty($userData->usu_nom) 
    ? trim($userData->usu_nom . ' ' . ($userData->usu_apep ?? ''))
    : ($userData->usuario ?? 'USUARIO');

// Consultar estrictamente los correos registrados en la base de datos (sc_escalafon.tb_persona)
$emailInst = '';
$emailPers = '';
try {
    $con = (new Conectar())->conexion();
    $stmt = $con->prepare("
        SELECT pers_nombre, pers_apelpat, pers_apelmat, pers_emailm, pers_emailp 
        FROM sc_escalafon.tb_persona 
        WHERE pers_dni = :dni 
        LIMIT 1
    ");
    $stmt->execute([':dni' => trim($dniUsuario)]);
    $persona = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($persona) {
        $nomDb = trim(($persona['pers_nombre'] ?? '') . ' ' . ($persona['pers_apelpat'] ?? '') . ' ' . ($persona['pers_apelmat'] ?? ''));
        if (!empty($nomDb)) {
            $nombreUsuario = $nomDb;
        }
        $emailInst = trim($persona['pers_emailm'] ?? '');
        $emailPers = trim($persona['pers_emailp'] ?? '');
    }
} catch (Exception $e) {
    error_log("[recuperar_contrasena] Error consultando sc_escalafon.tb_persona: " . $e->getMessage());
}

// Fallback al token de sesión si la base de datos no arrojó valor
if (empty($emailInst)) {
    $emailInst = trim($userData->usu_correo ?? ($userData->pers_emailm ?? ''));
}
if (empty($emailPers)) {
    $emailPers = trim($userData->usu_emailp ?? ($userData->pers_emailp ?? ''));
}

$nombreUsuario = mb_strtoupper($nombreUsuario, 'UTF-8');

$tieneInst = !empty($emailInst) && filter_var($emailInst, FILTER_VALIDATE_EMAIL);
$tienePers = !empty($emailPers) && filter_var($emailPers, FILTER_VALIDATE_EMAIL);

$maskInst = $tieneInst ? maskEmailAddress($emailInst) : 'No cuenta con correo institucional';
$maskPers = $tienePers ? maskEmailAddress($emailPers) : 'No cuenta con correo personal registrado';

$logoMuni = Conectar::ruta() . 'public/img/logo_mpch.png';
if (!file_exists(__DIR__ . '/../../../public/img/logo_mpch.png')) {
    $logoMuni = Conectar::ruta() . 'public/componente-spinner/public/img/logo_mpch.png';
}
$headerSisAbrNom = mb_strtoupper($_ENV['SIST_INIC'], 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <title><?= htmlspecialchars($headerSisAbrNom) ?></title>
    
    <!-- Tabler UI -->
    <link href="<?= Conectar::ruta() ?>public/css/tabler.min.css" rel="stylesheet"/>
    <link rel="icon" type="image/png" href="<?= Conectar::ruta() ?>public/static/illustrations/logomuni3.png">

    <style>
        :root {
            --mpch-blue: #066ed0;
            --mpch-blue-hover: #0558a7;
            --mpch-yellow: #fed84d;
            --mpch-yellow-hover: #e5c242;
            --bg-page: #eef2f6;
            --card-border: #e2e8f0;
            --text-dark: #0f172a;
            --text-muted: #64748b;
        }

        body {
            background-color: var(--bg-page);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            margin: 0;
        }

        .main-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.04);
            border: 1px solid var(--card-border);
            width: 100%;
            max-width: 680px;
            overflow: hidden;
        }

        /* Franja Bicolor Superior (82% Azul / 18% Amarillo) */
        .card-strip {
            height: 6px;
            display: flex;
            width: 100%;
        }
        .strip-blue {
            width: 82%;
            background-color: var(--mpch-blue);
        }
        .strip-yellow {
            width: 18%;
            background-color: var(--mpch-yellow);
        }

        /* Cabecera del Card */
        .card-header-custom {
            padding: 20px 32px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid #f1f5f9;
        }
        .card-header-logo {
            width: 32px;
            height: 38px;
            object-fit: contain;
        }
        .card-header-title {
            font-size: 14.5px;
            font-weight: 600;
            color: #334155;
            margin: 0;
        }

        /* Cuerpo del Card */
        .card-body-custom {
            padding: 32px 36px 36px;
        }

        .section-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-dark);
            text-align: center;
            margin-bottom: 8px;
        }

        .section-subtitle {
            font-size: 13.5px;
            color: var(--text-muted);
            text-align: center;
            line-height: 1.5;
            margin-bottom: 32px;
        }

        /* Tarjeta de Opción de Envío */
        .method-card {
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px;
            display: flex;
            align-items: center;
            gap: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
            background: #ffffff;
            margin-bottom: 14px;
            position: relative;
        }
        .method-card:hover {
            border-color: #93c5fd;
            background-color: #f8fafc;
        }
        .method-card.selected {
            border-color: var(--mpch-blue);
            background-color: #f0f7ff;
            box-shadow: 0 0 0 2px rgba(6, 110, 208, 0.15);
        }
        .method-card.disabled {
            opacity: 0.6;
            cursor: not-allowed;
            background-color: #f8fafc;
        }
        .method-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            background-color: #ffffff;
            border: 1.5px solid #bfdbfe;
            color: var(--mpch-blue);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .method-card.selected .method-icon {
            background-color: var(--mpch-blue);
            color: #ffffff;
            border-color: var(--mpch-blue);
        }
        .method-info {
            flex: 1;
            min-width: 0;
        }
        .method-title {
            font-size: 14.5px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 2px;
        }
        .method-subtitle {
            font-size: 12.5px;
            color: var(--text-muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Tarjeta de Perfil de Usuario (Columna Derecha) */
        .profile-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            min-height: 140px;
            text-align: center;
            padding: 10px;
        }
        .avatar-placeholder {
            width: 90px;
            height: 90px;
            border-radius: 14px;
            background-color: #cbd5e1;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            margin-bottom: 12px;
            overflow: hidden;
        }
        .avatar-placeholder svg {
            width: 72px;
            height: 72px;
        }
        .profile-name {
            font-size: 13.5px;
            font-weight: 700;
            color: #334155;
            letter-spacing: 0.5px;
            max-width: 160px;
            line-height: 1.3;
        }

        /* Botón de Acción Amarillo */
        .btn-yellow-submit {
            width: 100%;
            height: 48px;
            background-color: var(--mpch-yellow);
            color: #0f172a;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            cursor: pointer;
            margin-top: 24px;
            transition: background-color 0.15s ease, transform 0.05s ease;
        }
        .btn-yellow-submit:hover {
            background-color: var(--mpch-yellow-hover);
        }
        .btn-yellow-submit:active {
            transform: scale(0.99);
        }
        .btn-yellow-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* Enlace de regreso */
        .footer-back {
            margin-top: 24px;
            padding-top: 18px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
        }
        .back-link {
            color: var(--mpch-blue);
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: color 0.15s ease;
        }
        .back-link:hover {
            color: var(--mpch-blue-hover);
            text-decoration: underline;
        }

        /* Input de código de 6 dígitos en el modal (Idéntico a Figma) */
        .input-email-digits {
            width: 100%;
            height: 64px;
            background-color: #f8fafc;
            border: 2px solid #cbd5e1;
            border-radius: 12px;
            font-size: 28px;
            font-weight: 700;
            font-style: italic;
            letter-spacing: 12px;
            text-align: center;
            color: var(--mpch-blue);
            font-family: inherit;
            outline: none;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }
        .input-email-digits:focus {
            border-color: var(--mpch-blue);
            box-shadow: 0 0 0 3px rgba(6, 110, 208, 0.15);
            background-color: #ffffff;
        }
    </style>
</head>
<body>

    <div class="main-card">
        <!-- Franja Azul (82%) y Amarillo (18%) -->
        <div class="card-strip">
            <div class="strip-blue"></div>
            <div class="strip-yellow"></div>
        </div>

        <!-- Encabezado con Escudo Municipal -->
        <div class="card-header-custom">
            <img src="<?= htmlspecialchars($logoMuni) ?>" alt="MPCH" class="card-header-logo">
            <h1 class="card-header-title">Municipalidad Provincial de Chiclayo</h1>
        </div>

        <!-- Cuerpo del Card -->
        <div class="card-body-custom">
            <h2 class="section-title">Recuperación de Contraseña</h2>
            <p class="section-subtitle">
                ¿Por cual medio desea recibir el código de verificación para recuperar su contraseña?
            </p>

            <div class="row g-4 align-items-center">
                <!-- Columna Izquierda: Opciones de Envío -->
                <div class="col-12 col-md-8">
                    
                    <!-- Opción 1: Correo Institucional -->
                    <div class="method-card <?= $tieneInst ? 'selected' : 'disabled' ?>" id="cardMetodoInst" onclick="seleccionarMetodo('institucional')">
                        <div class="method-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M3 7a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v10a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-10z" />
                                <path d="M3 7l9 6l9 -6" />
                            </svg>
                        </div>
                        <div class="method-info">
                            <div class="method-title">Enviar código por correo institucional</div>
                            <div class="method-subtitle" id="lblSubtitleInst"><?= htmlspecialchars($maskInst) ?></div>
                        </div>
                    </div>

                    <!-- Opción 2: Correo Personal -->
                    <div class="method-card <?= (!$tieneInst && $tienePers) ? 'selected' : ($tienePers ? '' : 'disabled') ?>" id="cardMetodoPers" onclick="seleccionarMetodo('personal')">
                        <div class="method-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" />
                                <path d="M12 10m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" />
                                <path d="M6.168 18.849a4 4 0 0 1 3.832 -2.849h4a4 4 0 0 1 3.834 2.855" />
                            </svg>
                        </div>
                        <div class="method-info">
                            <div class="method-title">Enviar código por correo personal</div>
                            <div class="method-subtitle" id="lblSubtitlePers"><?= htmlspecialchars($maskPers) ?></div>
                        </div>
                    </div>

                </div>

                <!-- Columna Derecha: Tarjeta de Perfil -->
                <div class="col-12 col-md-4">
                    <div class="profile-box">
                        <div class="avatar-placeholder">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2a5 5 0 1 1 -5 5l.005 -.217a5 5 0 0 1 4.995 -4.783z" />
                                <path d="M14 14a5 5 0 0 1 5 5v1a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2v-1a5 5 0 0 1 5 -5h4z" />
                            </svg>
                        </div>
                        <div class="profile-name"><?= htmlspecialchars($nombreUsuario) ?></div>
                    </div>
                </div>
            </div>

            <?php if (!$tieneInst && !$tienePers): ?>
                <div class="alert alert-warning small text-center my-3" role="alert">
                    No cuenta con correo institucional ni personal registrado en el sistema. Por favor comuníquese con la Gerencia de Tecnología e Informática.
                </div>
            <?php endif; ?>

            <!-- Botón Continuar -->
            <button type="button" id="btnContinuar" class="btn-yellow-submit" onclick="continuarRecuperacion()" <?= (!$tieneInst && !$tienePers) ? 'disabled' : '' ?>>
                <span>&gt;&gt; Continuar</span>
            </button>

            <!-- Enlace Volver al inicio de sesión -->
            <div class="footer-back">
                <a href="<?= Conectar::ruta() ?>index.php" class="back-link">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M5 12l14 0"/><path d="M5 12l6 6"/><path d="M5 12l6 -6"/>
                    </svg>
                    <span>Volver al inicio de sesión</span>
                </a>
            </div>

        </div>
    </div>

    <!-- ========================================================
         MODAL: CÓDIGO DE SEGURIDAD POR CORREO (FIGMA IDÉNTICO)
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
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <!-- Cuerpo del Modal -->
                <div class="modal-body p-4 text-center">
                    <p class="text-muted mb-2" style="font-size: 14px;">
                        Hemos enviado un código dinámico de 6 caracteres a tu cuenta de correo:
                    </p>

                    <!-- Badge de Correo Enmascarado -->
                    <div class="badge font-monospace px-3 py-2 mb-4" style="background-color: #eaf2fa; color: var(--mpch-blue); font-size: 15px; border-radius: 8px;" id="lblCorreoDestino">
                        cargando...
                    </div>

                    <!-- Input Central de 6 caracteres alfanuméricos -->
                    <div class="mb-3 px-2">
                        <input 
                            type="text" 
                            id="codigo_correo_input" 
                            class="input-email-digits" 
                            placeholder="C Ó D I G O" 
                            maxlength="6" 
                            autocomplete="one-time-code"
                            spellcheck="false"
                            style="text-transform: uppercase;"
                        />
                    </div>

                    <!-- Píldora del Temporizador -->
                    <div class="d-flex align-items-center justify-content-center gap-2 my-3">
                        <span class="badge fw-bold d-flex align-items-center gap-1 px-3 py-2" id="badgeTimer" style="background-color: #fef3c7; color: #b45309; border-radius: 20px; font-size: 13px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" />
                                <path d="M12 7v5l3 3" />
                            </svg>
                            Expira en: <span id="lblCountdownTimer" class="font-monospace ms-1">02:00</span>
                        </span>
                    </div>

                    <!-- Alerta de Error -->
                    <div id="alertEmailCode" class="alert alert-danger small d-none my-3 text-start" role="alert"></div>

                    <!-- Botón Verificar e Ingresar -->
                    <button type="button" id="btnValidarCodigoCorreo" class="btn-yellow-submit mt-2" onclick="validarCodigoCorreo()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M12 3a12 12 0 0 0 8.5 3a12 12 0 0 1 -8.5 15a12 12 0 0 1 -8.5 -15a12 12 0 0 0 8.5 -3" />
                            <path d="M9 12l2 2l4 -4" />
                        </svg>
                        <span>Verificar e Ingresar al Sistema</span>
                    </button>

                    <!-- Acciones Inferiores -->
                    <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center" style="font-size: 13.5px;">
                        <button type="button" class="btn btn-link link-secondary p-0 text-decoration-none" data-bs-dismiss="modal">
                            &larr; Cambiar método
                        </button>
                        <button type="button" class="btn btn-link link-primary p-0 text-decoration-none fw-bold" id="btnReenviarCodigo" onclick="continuarRecuperacion()" disabled>
                            Reenviar código (<span id="lblReenviarCountdown">30</span>s)
                        </button>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="<?= Conectar::ruta() ?>public/js/jquery.min.js"></script>
    <script src="<?= Conectar::ruta() ?>public/js/tabler.min.js" defer></script>
    <?php require_once __DIR__ . '/../../../public/componente-spinner/spinner.php'; ?>

    <script>
        let g_metodoSeleccionado = <?= $tieneInst ? "'institucional'" : ($tienePers ? "'personal'" : "null") ?>;
        const g_tieneInst = <?= $tieneInst ? 'true' : 'false' ?>;
        const g_tienePers = <?= $tienePers ? 'true' : 'false' ?>;
        let g_timerInterval = null;
        let g_resendInterval = null;
        let g_segundosRestantes = 120;
        let g_resendCooldown = 30;

        function seleccionarMetodo(metodo) {
            if (metodo === 'institucional') {
                if (!g_tieneInst) return;
                g_metodoSeleccionado = 'institucional';
                $('#cardMetodoInst').addClass('selected');
                $('#cardMetodoPers').removeClass('selected');
            } else if (metodo === 'personal') {
                if (!g_tienePers) return;
                g_metodoSeleccionado = 'personal';
                $('#cardMetodoPers').addClass('selected');
                $('#cardMetodoInst').removeClass('selected');
            }
        }

        function continuarRecuperacion() {
            if (!g_metodoSeleccionado) {
                alert('Por favor selecciona un método disponible para recibir tu código.');
                return;
            }

            if (g_metodoSeleccionado === 'institucional' && !g_tieneInst) {
                alert('No cuenta con correo institucional registrado.');
                return;
            }

            if (g_metodoSeleccionado === 'personal' && !g_tienePers) {
                alert('No cuenta con correo personal registrado.');
                return;
            }

            showSpinner();
            $('#btnContinuar').prop('disabled', true);

            const metodoVal = (g_metodoSeleccionado === 'personal') ? '2' : '1';

            $.post('../../../auth/proxy.php', {
                api: 'clave.php?op=token',
                usu_doc: '<?= htmlspecialchars($dniUsuario) ?>',
                usu_tipo: '<?= htmlspecialchars($userData->usu_tipo ?? 'P') ?>',
                metodo: metodoVal
            }, function(resp) {
                hideSpinner();
                $('#btnContinuar').prop('disabled', false);

                const isOk = (resp && (resp.success === true || resp.success === 'true' || resp.status == 200));

                if (isOk) {
                    const correoDestino = resp.correo_enmascarado || (metodoVal === '1' ? '<?= htmlspecialchars($maskInst) ?>' : '<?= htmlspecialchars($maskPers) ?>');
                    $('#lblCorreoDestino').text(correoDestino);
                    $('#modalCodigoCorreo').modal('show');
                    iniciarTemporizador(120);
                    setTimeout(() => {
                        $('#codigo_correo_input').val('').focus();
                    }, 400);
                } else {
                    alert((resp && resp.message) ? resp.message : 'No se pudo procesar el envío del código.');
                }
            }, 'json').fail(function() {
                hideSpinner();
                $('#btnContinuar').prop('disabled', false);
                alert('Error de conexión con el servidor.');
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
            const codigo = $('#codigo_correo_input').val().trim().toUpperCase();
            if (codigo.length !== 6) {
                $('#alertEmailCode').text('Ingresa el código de 6 caracteres recibido en tu correo.').removeClass('d-none');
                $('#codigo_correo_input').focus();
                return;
            }

            const btn = $('#btnValidarCodigoCorreo');
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Validando...');
            $('#alertEmailCode').addClass('d-none');

            $.post('../../../auth/proxy.php', {
                api: 'clave.php?op=validar_token',
                usu_doc: '<?= htmlspecialchars($dniUsuario) ?>',
                usu_tipo: '<?= htmlspecialchars($userData->usu_tipo ?? 'P') ?>',
                codigo: codigo
            }, function(resp) {
                const isOk = (resp && (resp.success === true || resp.success === 'true' || resp.status == 200));

                if (isOk) {
                    btn.html('<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10" /></svg> <span>Acceso Concedido</span>');
                    const destino = resp.enlace || resp.redirect || (resp.data && resp.data.enlace) || '<?= Conectar::ruta() ?>seguridad/primer_acceso/views/';
                    window.location.href = destino;
                } else {
                    btn.prop('disabled', false).html('<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 3a12 12 0 0 0 8.5 3a12 12 0 0 1 -8.5 15a12 12 0 0 1 -8.5 -15a12 12 0 0 0 8.5 -3" /><path d="M9 12l2 2l4 -4" /></svg> <span>Verificar e Ingresar al Sistema</span>');
                    $('#alertEmailCode').text((resp && resp.message) ? resp.message : 'Código incorrecto o expirado.').removeClass('d-none');
                    $('#codigo_correo_input').val('').focus();
                }
            }, 'json').fail(function() {
                btn.prop('disabled', false).html('<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 3a12 12 0 0 0 8.5 3a12 12 0 0 1 -8.5 15a12 12 0 0 1 -8.5 -15a12 12 0 0 0 8.5 -3" /><path d="M9 12l2 2l4 -4" /></svg> <span>Verificar e Ingresar al Sistema</span>');
                $('#alertEmailCode').text('Error de conexión al validar código.').removeClass('d-none');
            });
        }

        $(document).ready(function() {
            // Acepta letras y números, convirtiendo automáticamente a mayúsculas
            $('#codigo_correo_input').on('input', function() {
                this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 6);
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
