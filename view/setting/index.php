<?php
/**
 * Vista de Ajustes de Cuenta / Mi Perfil - sisIndicadores
 * Estandarizada con componentes compartidos en view/html/ y Design System MPCH (Node 103:226 & 54:1291)
 *
 * Municipalidad Provincial de Chiclayo (MPCH)
 */

require_once("../../config/conexion.php");
Conectar::initSession();
if (empty($_SESSION['usua_id_SIGODT']) || empty($_SESSION['usua_dni_SIGODT'])) {
    header('Location: ' . Conectar::ruta() . 'index.php');
    exit;
}
header('Cache-Control: no-store');
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$userData = (object)[
    'usu_nom' => $_SESSION['pers_nombre_SIGODT'] ?? '',
    'usu_apep' => $_SESSION['pers_apelpat_SIGODT'] ?? '',
    'usu_apem' => $_SESSION['pers_apelmat_SIGODT'] ?? '',
    'usu_emaili' => $_SESSION['pers_emailm_SIGODT'] ?? '',
    'usu_emailp' => $_SESSION['pers_emailp_SIGODT'] ?? '',
    'perf_nombre' => $_SESSION['rol_nombre_SIGODT'] ?? '',
];

// Source status block retained; feedback now uses text-only AJAX responses.
$message = null;
$alert = null;
$text = null;
$icon = null;

$headerSisAbrNom = 'SIGODT';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php require_once("../html/mainHead.php"); ?>
    <link href="../../public/css/institutional-settings.css" rel="stylesheet">
</head>
<body>
    <script src="../../public/tabler/js/demo-theme.min.js"></script>
    <div class="page">
      <div class="wrapper">
        <!-- 1. CABECERA INSTITUCIONAL UNIFICADA -->
        <?php require_once("../html/mainProfile.php"); ?>

        <?php require_once("../html/menu.php"); ?>
        <!-- Source settings content inside the existing SIGODT shared shell. -->
        <div class="page-wrapper">

            <main id="institutional-settings" class="mpch-main-container">

                <!-- Breadcrumb Contextual de Configuración (Ruta de Navegación) -->
                <div class="mpch-subsys-breadcrumb settings-breadcrumb">
                    <a href="<?= Conectar::ruta() ?>view/inicio/" class="mpch-bc-sys text-decoration-none" title="Ir al Inicio"><?= htmlspecialchars($headerSisAbrNom) ?></a>
                    <span class="mpch-bc-sep">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 6l6 6l-6 6" /></svg>
                    </span>
                    <span class="mpch-bc-sec">Configuración</span>
                </div>

                <!-- Encabezado de la Página (Título y Descripción) -->
                <div class="page-header d-print-none mb-4 settings-page-header">
                    <div class="row align-items-center">
                        <div class="col">
                            <h1 class="page-title mb-1">CONFIGURACIÓN</h1>
                            <div class="text-secondary mt-1" style="font-size: 14px;">
                                Gestión de la cuenta de usuario, credenciales y seguridad del acceso
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Panel de Configuración en Dos Columnas (Figma 103:241) -->
                <div class="settings-card-panel muni-stripe-top">

                    <!-- Barra Lateral de Navegación (Figma 103:242) -->
                    <aside class="settings-sidebar">
                        <nav>
                            <a href="../setting/" class="settings-nav-item active">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-user" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" />
                                    <path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" />
                                </svg>
                                <span>Cuenta</span>
                            </a>
                        </nav>
                    </aside>

                    <!-- Área de Contenido Central (Figma 103:248) -->
                    <div class="settings-content-area">

                        <!-- Recuadro 1: Mi Perfil (Identidad Personal) -->
                        <div class="card mpch-settings-card">
                            <div class="card-header mpch-settings-header">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="mpch-settings-icon-box">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-user" width="22" height="22" viewBox="0 0 24 24" stroke-width="2" stroke="#0066CC" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" />
                                            <path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="mpch-settings-title">Mi Perfil</h4>
                                        <div class="mpch-settings-subtitle">Datos de identificación personal y nombres registrados en el sistema</div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body mpch-settings-body">
                                <div class="row g-3">
                                    <div class="col-lg-4 col-md-4 col-12">
                                        <div class="form-field-group">
                                            <label class="form-field-label">Nombre <span class="req-star">*</span></label>
                                            <div class="custom-input-box disabled">
                                                <span class="input-addon-icon">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-user" width="17" height="17" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                        <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" />
                                                        <path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" />
                                                    </svg>
                                                </span>
                                                <input type="text" class="custom-native-input" value="<?php echo htmlspecialchars($userData->usu_nom ?? ''); ?>" disabled>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-md-4 col-12">
                                        <div class="form-field-group">
                                            <label class="form-field-label">Apellido Paterno <span class="req-star">*</span></label>
                                            <div class="custom-input-box disabled">
                                                <span class="input-addon-icon">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-user" width="17" height="17" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                        <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" />
                                                        <path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" />
                                                    </svg>
                                                </span>
                                                <input type="text" class="custom-native-input" value="<?php echo htmlspecialchars($userData->usu_apep ?? ''); ?>" disabled>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-md-4 col-12">
                                        <div class="form-field-group">
                                            <label class="form-field-label">Apellido Materno <span class="req-star">*</span></label>
                                            <div class="custom-input-box disabled">
                                                <span class="input-addon-icon">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-user" width="17" height="17" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                        <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" />
                                                        <path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" />
                                                    </svg>
                                                </span>
                                                <input type="text" class="custom-native-input" value="<?php echo htmlspecialchars($userData->usu_apem ?? ''); ?>" disabled>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Recuadro 2: Contacto y Cargo Laboral -->
                        <div class="card mpch-settings-card">
                            <div class="card-header mpch-settings-header">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="mpch-settings-icon-box">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-phone" width="22" height="22" viewBox="0 0 24 24" stroke-width="2" stroke="#0066CC" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <path d="M5 4h4l2 5l-2.5 1.5a11 11 0 0 0 5 5l1.5 -2.5l5 2v4a2 2 0 0 1 -2 2a16 16 0 0 1 -15 -15a2 2 0 0 1 2 -2" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="mpch-settings-title">Contacto y Cargo Institucional</h4>
                                        <div class="mpch-settings-subtitle">Número de teléfono para notificaciones y cargo institucional asignado</div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body mpch-settings-body">
                                <div class="row g-3">
                                    <div class="col-lg-6 col-md-6 col-12">
                                        <div class="form-field-group">
                                            <label class="form-field-label">Teléfono <span class="req-star">*</span></label>
                                            <div class="custom-input-box">
                                                <span class="input-addon-icon">+51</span>
                                                <input type="number" id="telefono" class="custom-native-input" oninput="limitarDigitosTlf(this)" placeholder="Número de celular">
                                                <button type="button" class="input-action-btn" id="cambiar-tlf" title="Guardar teléfono">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-device-floppy" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="#0F172A" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                        <path d="M6 4h10l4 4v10a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2" />
                                                        <path d="M12 14m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
                                                        <path d="M14 4l0 4l-6 0l0 -4" />
                                                    </svg>
                                                </button>
                                            </div>
                                            <div class="field-feedback-text">
                                                <span id="txt-telefono" style="color: var(--mpch-danger);">No cuenta con número de teléfono registrado</span>
                                            </div>
                                        </div>
                                    </div>

                                    <?php if (!empty($userData->perf_nombre)): ?>
                                    <div class="col-lg-6 col-md-6 col-12">
                                        <div class="form-field-group">
                                            <label class="form-field-label">Cargo <span class="req-star">*</span></label>
                                            <div class="custom-input-box disabled">
                                                <span class="input-addon-icon">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-star" width="17" height="17" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                        <path d="M12 17.75l-6.172 3.245l1.179 -6.873l-5 -4.867l6.9 -1l3.086 -6.253l3.086 6.253l6.9 1l-5 4.867l1.179 6.873z" />
                                                    </svg>
                                                </span>
                                                <input type="text" id="cargo" class="custom-native-input" value="<?php echo htmlspecialchars($userData->perf_nombre ?? ''); ?>" disabled>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Recuadro 3: Correos Electrónicos -->
                        <div class="card mpch-settings-card">
                            <div class="card-header mpch-settings-header">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="mpch-settings-icon-box">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-mail" width="22" height="22" viewBox="0 0 24 24" stroke-width="2" stroke="#0066CC" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <path d="M3 7a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v10a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-10z" />
                                            <path d="M3 7l9 6l9 -6" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="mpch-settings-title">Correos Electrónicos</h4>
                                        <div class="mpch-settings-subtitle">Bandeja de correo institucional Zimbra y correo electrónico personal</div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body mpch-settings-body">
                                <div class="row g-3">
                                    <?php if (true): // Institutional email remains readonly. ?>
                                    <div class="col-lg-6 col-md-6 col-12">
                                        <div class="form-field-group">
                                            <label class="form-field-label">Correo Institucional <span class="req-star">*</span></label>
                                            <div class="custom-input-box disabled">
                                                <span class="input-addon-icon">@</span>
                                                <input type="text" id="emaili" class="custom-native-input" value="<?php echo htmlspecialchars($userData->usu_emaili ?? ''); ?>" disabled>
                                                <button type="button" class="input-action-btn" onclick="window.open('https://correo.munichiclayo.gob.pe/', '_blank');" title="Abrir Correo Zimbra">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-arrow-right" width="18" height="18" viewBox="0 0 24 24" stroke-width="2.2" stroke="#0F172A" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                        <path d="M5 12l14 0" />
                                                        <path d="M13 18l6 -6" />
                                                        <path d="M13 6l6 6" />
                                                    </svg>
                                                </button>
                                            </div>
                                            <div class="field-feedback-text">
                                                <a href="javascript:void(0)" class="pwd-recovery-link" onclick="verTip()">¿Olvidó su contraseña?</a>
                                            </div>
                                            <div id="modaldata" style="display:none;" class="mt-2"></div>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <div class="col-lg-6 col-md-6 col-12">
                                        <div class="form-field-group">
                                            <label class="form-field-label">Correo Personal</label>
                                            <div class="custom-input-box">
                                                <span class="input-addon-icon">@</span>
                                                <input type="text" id="emailp" class="custom-native-input" value="<?php echo htmlspecialchars($userData->usu_emailp ?? ''); ?>" placeholder="correo@ejemplo.com">
                                                <button type="button" class="input-action-btn" id="cambiar-emailp" title="Guardar correo personal">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-device-floppy" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="#0F172A" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                        <path d="M6 4h10l4 4v10a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2" />
                                                        <path d="M12 14m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
                                                        <path d="M14 4l0 4l-6 0l0 -4" />
                                                    </svg>
                                                </button>
                                            </div>
                                            <div class="field-feedback-text">
                                                <span id="txt-emailp" style="color: var(--mpch-danger);">No cuenta con correo electrónico personal registrado</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Recuadro 4: Contraseña de Acceso -->
                        <div class="card mpch-settings-card">
                            <div class="card-header mpch-settings-header">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="mpch-settings-icon-box">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-key" width="22" height="22" viewBox="0 0 24 24" stroke-width="2" stroke="#0066CC" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <path d="M16.555 3.843l3.602 3.602a2.877 2.877 0 0 1 0 4.069l-2.643 2.643a2.877 2.877 0 0 1 -4.069 0l-.301 -.301l-6.558 6.558a2 2 0 0 1 -1.239 .578l-.175 .008h-1.172a1 1 0 0 1 -.993 -.883l-.007 -.117v-1.172a2 2 0 0 1 .467 -1.284l.119 -.13l.414 -.414h2v-2h2v-2l2.144 -2.144l-.301 -.301a2.877 2.877 0 0 1 0 -4.069l2.643 -2.643a2.877 2.877 0 0 1 4.069 0z" />
                                            <path d="M15 9h.01" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="mpch-settings-title">Contraseña de Acceso</h4>
                                        <div class="mpch-settings-subtitle">Gestión de la credencial de acceso para mantener la cuenta segura</div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body mpch-settings-body">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <p class="text-secondary mb-0" style="font-size: 13.5px; line-height: 20px;">
                                            Puedes cambiar tu contraseña de manera permanente. Se recomienda actualizarla periódicamente para mantener tu cuenta protegida.
                                        </p>
                                    </div>
                                    <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                        <a id="cambiar-clave" href="<?= Conectar::ruta() ?>seguridad/actualizar_contrasena/views/" class="btn-change-password">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-key" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                <path d="M16.555 3.843l3.602 3.602a2.877 2.877 0 0 1 0 4.069l-2.643 2.643a2.877 2.877 0 0 1 -4.069 0l-.301 -.301l-6.558 6.558a2 2 0 0 1 -1.239 .578l-.175 .008h-1.172a1 1 0 0 1 -.993 -.883l-.007 -.117v-1.172a2 2 0 0 1 .467 -1.284l.119 -.13l.414 -.414h2v-2h2v-2l2.144 -2.144l-.301 -.301a2.877 2.877 0 0 1 0 -4.069l2.643 -2.643a2.877 2.877 0 0 1 4.069 0z" />
                                                <path d="M15 9h.01" />
                                            </svg>
                                            <span>Cambiar Contraseña</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Recuadro 5: Módulo de Doble Factor de Autenticación (2FA) (Figma 103:320) -->
                        <?php
                        $enable2FA = filter_var(
                            method_exists('Conectar', 'getEnv') ? Conectar::getEnv('ENABLE_2FA', 'true') : ($_ENV['ENABLE_2FA'] ?? 'true'),
                            FILTER_VALIDATE_BOOLEAN
                        );
                        $twoFactorPerfilFile = __DIR__ . '/../../seguridad/2fa/views/perfil_2fa.php';
                        if ($enable2FA && file_exists($twoFactorPerfilFile)):
                        ?>
                        <div class="mt-1">
                            <?php require_once $twoFactorPerfilFile; ?>
                        </div>
                        <?php endif; ?>

                        <!-- Notificaciones / Mensajes de Estado -->
                        <?php if (isset($message)): ?>
                        <div class="col-12 mt-3" id="setting_data">
                            <div class="alert alert-<?= htmlspecialchars($alert) ?> py-2" role="alert">
                                <div class="d-flex align-items-center gap-2">
                                    <div><?= $icon ?></div>
                                    <div>
                                        <h4 class="alert-title mb-0 fs-5"><?= htmlspecialchars($text) ?></h4>
                                        <div class="text-secondary small"><?= htmlspecialchars($message); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                    </div>
                </div>

            </main>

            <!-- 3. FOOTER INSTITUCIONAL UNIFICADO -->
            <?php require_once("../html/footer.php"); ?>
        </div>
      </div>
    </div>

    <!-- Scripts y librerías estandarizadas del sistema -->
    <?php require_once("../html/mainjs.php"); ?>
    <script>
      window.SETTINGS_BASE_URL = <?= json_encode(Conectar::ruta()) ?>;
      window.SETTINGS_CSRF_TOKEN = <?= json_encode($_SESSION['csrf_token']) ?>;
    </script>
    <script src="settings.js"></script>

    <!-- Source cargarDatos/contact handlers live in settings.js with the same IDs,
         feedback and operations; only the direct SIGODT boundary is localized. -->
</body>
</html>
