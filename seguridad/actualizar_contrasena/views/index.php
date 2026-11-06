<?php
require_once __DIR__ . '/../../../config/conexion.php';
require_once __DIR__ . '/../../../config/jwt.php';

$jwt = new JwtHandler();

// Verificar que exista sesión válida
$userData = null;
if (!empty($_COOKIE['session'])) {
    $dec = $jwt->decrypt($_COOKIE['session']);
    if (!empty($dec->data)) {
        $userData = $dec->data;
    }
} elseif (!empty($_COOKIE['blanqueamiento'])) {
    $dec = $jwt->decrypt($_COOKIE['blanqueamiento']);
    if (!empty($dec->data)) {
        $userData = $dec->data;
    }
}

if (!$userData || empty($userData->usu_doc)) {
    header("Location: " . Conectar::ruta() . "index.php");
    exit();
}

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
            max-width: 540px;
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
            padding: 28px 36px 36px;
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
            margin-bottom: 28px;
        }

        .form-label-custom {
            font-size: 14px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 6px;
            display: block;
        }
        .required-star {
            color: #ef4444;
            margin-left: 2px;
        }

        /* Input Group estilizado */
        .input-group-custom {
            display: flex;
            align-items: center;
            width: 100%;
            margin-bottom: 18px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: #ffffff;
            transition: all 0.2s ease;
            position: relative;
        }
        .input-group-custom:focus-within {
            border-color: var(--mpch-blue);
            box-shadow: 0 0 0 3px rgba(6, 110, 208, 0.15);
        }
        .input-icon-left {
            width: 44px;
            height: 46px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            background-color: #f8fafc;
            border-right: 1px solid #e2e8f0;
            border-radius: 7px 0 0 7px;
            flex-shrink: 0;
        }
        .input-field-custom {
            flex: 1;
            height: 46px;
            border: none;
            padding: 0 14px;
            font-size: 14px;
            color: var(--text-dark);
            outline: none;
            background: transparent;
        }
        .input-field-custom::placeholder {
            color: #94a3b8;
        }
        .toggle-pwd-btn {
            background: none;
            border: none;
            color: #94a3b8;
            padding: 0 14px;
            cursor: pointer;
            height: 46px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .toggle-pwd-btn:hover {
            color: #475569;
        }

        /* Cuadro de detalle / requisitos */
        .rules-box {
            background-color: #f8fafc;
            border-radius: 10px;
            padding: 16px 20px;
            margin: 10px 0 24px;
            border: 1px solid #edf2f7;
        }
        .rules-title {
            font-size: 13.5px;
            font-weight: 700;
            color: #334155;
            margin-bottom: 10px;
        }
        .rules-list {
            margin: 0;
            padding: 0;
            list-style: none;
        }
        .rule-item {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 6px;
            font-size: 13px;
            font-weight: 500;
            color: #dc2626; /* Rojo por defecto */
            transition: color 0.2s ease;
        }
        .rule-item.valid {
            color: #16a34a; /* Verde cuando se cumple */
        }
        .rule-item .rule-icon {
            width: 16px;
            height: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .rule-item.invalid .icon-valid {
            display: none !important;
        }
        .rule-item.invalid .icon-invalid {
            display: inline-block !important;
        }
        .rule-item.valid .icon-invalid {
            display: none !important;
        }
        .rule-item.valid .icon-valid {
            display: inline-block !important;
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
            gap: 8px;
            cursor: pointer;
            transition: background-color 0.15s ease, transform 0.05s ease;
        }
        .btn-yellow-submit:hover {
            background-color: var(--mpch-yellow-hover);
        }
        .btn-yellow-submit:active {
            transform: scale(0.99);
        }
        .btn-yellow-submit:disabled {
            opacity: 0.7;
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

        .alert-custom {
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 13.5px;
            margin-bottom: 20px;
            display: none;
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

        <!-- Cuerpo del Formulario -->
        <div class="card-body-custom">
            <h2 class="section-title">Cambio de Contraseña Segura</h2>
            <p class="section-subtitle">
                Una contraseña segura contribuye a evitar el acceso no autorizado a su cuenta.
            </p>

            <div id="alertBox" class="alert-custom" role="alert"></div>

            <form id="formActualizarClave" autocomplete="off" onsubmit="event.preventDefault(); submitActualizarClave();">
                
                <!-- Contraseña Actual -->
                <div class="mb-3">
                    <label class="form-label-custom" for="pass_actual">
                        Contraseña Actual<span class="required-star">*</span>
                    </label>
                    <div class="input-group-custom">
                        <span class="input-icon-left">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M16.555 3.843l3.602 3.602a2.877 2.877 0 0 1 0 4.069l-2.643 2.643a2.877 2.877 0 0 1 -4.069 0l-.301 -.301l-6.558 6.558a2 2 0 0 1 -1.239 .578l-.175 .008h-1.172a1 1 0 0 1 -.993 -.883l-.007 -.117v-1.172a2 2 0 0 1 .467 -1.284l.119 -.13l.414 -.414h2v-2h2v-2l2.144 -2.144l-.301 -.301a2.877 2.877 0 0 1 0 -4.069l2.643 -2.643a2.877 2.877 0 0 1 4.069 0z"/>
                                <path d="M15 9h.01"/>
                            </svg>
                        </span>
                        <input type="password" id="pass_actual" name="pass_actual" class="input-field-custom" placeholder="Ingrese su contraseña actual" required>
                        <button type="button" class="toggle-pwd-btn" onclick="togglePasswordVisibility('pass_actual', this)" title="Ver u ocultar contraseña">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"/><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Nueva Contraseña -->
                <div class="mb-3">
                    <label class="form-label-custom" for="pass_nueva">
                        Nueva Contraseña<span class="required-star">*</span>
                    </label>
                    <div class="input-group-custom">
                        <span class="input-icon-left">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M5 13a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v6a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2v-6z"/>
                                <path d="M11 16a1 1 0 1 0 2 0a1 1 0 0 0 -2 0"/>
                                <path d="M8 11v-4a4 4 0 1 1 8 0v4"/>
                            </svg>
                        </span>
                        <input type="password" id="pass_nueva" name="pass_nueva" class="input-field-custom" placeholder="Ingrese su contraseña nueva" required>
                        <button type="button" class="toggle-pwd-btn" onclick="togglePasswordVisibility('pass_nueva', this)" title="Ver u ocultar contraseña">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"/><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Confirma Nueva Contraseña -->
                <div class="mb-3">
                    <label class="form-label-custom" for="pass_confirm">
                        Confirma Nueva Contraseña<span class="required-star">*</span>
                    </label>
                    <div class="input-group-custom">
                        <span class="input-icon-left">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M5 13a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v6a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2v-6z"/>
                                <path d="M11 16a1 1 0 1 0 2 0a1 1 0 0 0 -2 0"/>
                                <path d="M8 11v-4a4 4 0 1 1 8 0v4"/>
                            </svg>
                        </span>
                        <input type="password" id="pass_confirm" name="pass_confirm" class="input-field-custom" placeholder="Ingrese su contraseña nueva" required>
                        <button type="button" class="toggle-pwd-btn" onclick="togglePasswordVisibility('pass_confirm', this)" title="Ver u ocultar contraseña">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"/><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Detalle de requisitos de seguridad -->
                <div class="rules-box">
                    <div class="rules-title">Detalle de requisitos:</div>
                    <ul class="rules-list" id="passwordRulesList">
                        <li class="rule-item invalid" id="rule-upper">
                            <span class="rule-icon">
                                <svg class="icon-invalid" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M18 6l-12 12" /><path d="M6 6l12 12" /></svg>
                                <svg class="icon-valid" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10" /></svg>
                            </span>
                            <span>Al menos 1 letra mayúscula</span>
                        </li>
                        <li class="rule-item invalid" id="rule-lower">
                            <span class="rule-icon">
                                <svg class="icon-invalid" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M18 6l-12 12" /><path d="M6 6l12 12" /></svg>
                                <svg class="icon-valid" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10" /></svg>
                            </span>
                            <span>Al menos 1 letra minúscula</span>
                        </li>
                        <li class="rule-item invalid" id="rule-number">
                            <span class="rule-icon">
                                <svg class="icon-invalid" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M18 6l-12 12" /><path d="M6 6l12 12" /></svg>
                                <svg class="icon-valid" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10" /></svg>
                            </span>
                            <span>Al menos 1 número</span>
                        </li>
                        <li class="rule-item invalid" id="rule-special">
                            <span class="rule-icon">
                                <svg class="icon-invalid" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M18 6l-12 12" /><path d="M6 6l12 12" /></svg>
                                <svg class="icon-valid" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10" /></svg>
                            </span>
                            <span>Al menos 1 carácter especial (ej. !@#$%^&*.,-_)</span>
                        </li>
                        <li class="rule-item invalid" id="rule-minlen">
                            <span class="rule-icon">
                                <svg class="icon-invalid" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M18 6l-12 12" /><path d="M6 6l12 12" /></svg>
                                <svg class="icon-valid" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10" /></svg>
                            </span>
                            <span>Mínimo 8 caracteres</span>
                        </li>
                    </ul>
                </div>

                <!-- Botón Actualizar Contraseña -->
                <button type="submit" id="btnSubmit" class="btn-yellow-submit">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M5 13a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v6a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2v-6z"/>
                        <path d="M11 16a1 1 0 1 0 2 0a1 1 0 0 0 -2 0"/>
                        <path d="M8 11v-4a4 4 0 1 1 8 0v4"/>
                    </svg>
                    <span>Actualizar Contraseña</span>
                </button>

            </form>

            <!-- Enlace Volver a la página anterior -->
            <div class="footer-back">
                <a href="<?= Conectar::ruta() ?>view/setting/" class="back-link">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M5 12l14 0"/><path d="M5 12l6 6"/><path d="M5 12l6 -6"/>
                    </svg>
                    <span>Volver a la pagina anterior</span>
                </a>
            </div>

        </div>
    </div>

    <!-- Scripts -->
    <script src="<?= Conectar::ruta() ?>public/js/jquery.min.js"></script>
    <?php require_once __DIR__ . '/../../../public/componente-spinner/spinner.php'; ?>

    <script>
        function togglePasswordVisibility(fieldId, btn) {
            const input = document.getElementById(fieldId);
            if (!input) return;
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            btn.style.color = isPassword ? 'var(--mpch-blue)' : '#94a3b8';
        }

        function showAlert(msg, type) {
            const box = $('#alertBox');
            box.removeClass('alert-danger alert-success alert-warning');
            if (type === 'danger') {
                box.addClass('alert-danger').css({'background-color': '#fee2e2', 'color': '#b91c1c', 'border': '1px solid #fca5a5'});
            } else if (type === 'success') {
                box.addClass('alert-success').css({'background-color': '#dcfce7', 'color': '#15803d', 'border': '1px solid #86efac'});
            } else {
                box.addClass('alert-warning').css({'background-color': '#fef3c7', 'color': '#b45309', 'border': '1px solid #fde047'});
            }
            box.text(msg).fadeIn();
        }

        function validarRequisitosPassword(val) {
            const hasUpper = /[A-Z]/.test(val);
            const hasLower = /[a-z]/.test(val);
            const hasNumber = /[0-9]/.test(val);
            const hasSpecial = /[^A-Za-z0-9]/.test(val);
            const hasMinLen = val.length >= 8;

            actualizarEstadoRegla('#rule-upper', hasUpper);
            actualizarEstadoRegla('#rule-lower', hasLower);
            actualizarEstadoRegla('#rule-number', hasNumber);
            actualizarEstadoRegla('#rule-special', hasSpecial);
            actualizarEstadoRegla('#rule-minlen', hasMinLen);

            return (hasUpper && hasLower && hasNumber && hasSpecial && hasMinLen);
        }

        function actualizarEstadoRegla(selector, isValid) {
            const el = $(selector);
            if (isValid) {
                el.removeClass('invalid').addClass('valid');
            } else {
                el.removeClass('valid').addClass('invalid');
            }
        }

        $(document).ready(function() {
            $('#pass_nueva').on('input keyup', function() {
                validarRequisitosPassword(this.value);
            });
        });

        function submitActualizarClave() {
            const actual = $('#pass_actual').val().trim();
            const nueva = $('#pass_nueva').val().trim();
            const confirm = $('#pass_confirm').val().trim();

            if (!actual || !nueva || !confirm) {
                showAlert('Por favor complete todos los campos requeridos.', 'danger');
                return;
            }

            if (!validarRequisitosPassword(nueva)) {
                showAlert('La nueva contraseña no cumple con todos los requisitos mínimos de seguridad (revise los puntos en rojo).', 'warning');
                return;
            }

            if (nueva !== confirm) {
                showAlert('La nueva contraseña y su confirmación no coinciden.', 'danger');
                return;
            }

            const btn = $('#btnSubmit');
            btn.prop('disabled', true);
            showSpinner();

            $.post('../../../auth/proxy.php', {
                api: 'clave.php?op=actualizar',
                usu_doc: '<?= htmlspecialchars($userData->usu_doc ?? '') ?>',
                usu_tipo: '<?= htmlspecialchars($userData->usu_tipo ?? 'P') ?>',
                usu_pass_actual: actual,
                usu_pass_nueva: nueva
            }, function(resp) {
                hideSpinner();
                if (resp.success) {
                    showAlert(resp.message || 'Contraseña actualizada correctamente.', 'success');
                    setTimeout(function() {
                        window.location.href = resp.enlace || resp.redirect || '<?= Conectar::ruta() ?>view/setting/';
                    }, 1500);
                } else {
                    btn.prop('disabled', false);
                    showAlert(resp.message || 'Error al actualizar la contraseña.', 'danger');
                }
            }, 'json').fail(function() {
                hideSpinner();
                btn.prop('disabled', false);
                showAlert('Error de conexión con el servidor. Intente nuevamente.', 'danger');
            });
        }
    </script>
</body>
</html>
