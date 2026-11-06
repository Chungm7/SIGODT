<?php
/**
 * Componente de Gestión de Doble Factor para la vista de Perfil
 * Estandarizado con clases de view/html/mainHead.php e iconos nativos de Tabler
 * 
 * Municipalidad Provincial de Chiclayo (MPCH)
 */

require_once __DIR__ . '/../models/TwoFactorModel.php';

$dniUsuarioSesion = $usu_doc ?? ($GLOBALS['data']->data->usu_doc ?? ($data->data->usu_doc ?? ($_SESSION['usu_doc'] ?? null)));
if (!$dniUsuarioSesion && isset($_COOKIE['session'])) {
    if (file_exists(__DIR__ . '/../../../config/jwt.php')) {
        require_once __DIR__ . '/../../../config/jwt.php';
    } else {
        require_once __DIR__ . '/../../config/jwt.php';
    }
    $jwtTmp = new JwtHandler();
    $decSession = $jwtTmp->decrypt($_COOKIE['session']);
    $dniUsuarioSesion = $decSession->data->usu_doc ?? null;
}
$status2FA = ['enabled' => false, 'data' => null];

if ($dniUsuarioSesion) {
    $status2FA = TwoFactorModel::checkStatus($dniUsuarioSesion);
}

$isActivo = !empty($status2FA['enabled']);
$fechaActivacion = !empty($status2FA['data']['seus_enrol_conf']) ? date('d/m/Y H:i', strtotime($status2FA['data']['seus_enrol_conf'])) : '—';
?>

<div class="card mpch-2fa-card">
    <div class="card-header mpch-2fa-header">
        <div class="d-flex align-items-center gap-3">
            <div class="mpch-2fa-icon-box">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-shield-check" width="22" height="22" viewBox="0 0 24 24" stroke-width="2" stroke="#0066CC" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                    <path d="M11.46 20.846a12 12 0 0 1 -7.96 -14.846a12 12 0 0 0 8.5 -3a12 12 0 0 0 8.5 3a12 12 0 0 1 -.09 7.06" />
                    <path d="M15 19l2 2l4 -4" />
                </svg>
            </div>
            <div>
                <h4 class="mpch-2fa-title">Verificación en Dos Pasos (2FA)</h4>
                <div class="mpch-2fa-subtitle">Protege tu acceso mediante Google Authenticator</div>
            </div>
        </div>

        <?php if ($isActivo): ?>
            <span class="service-status-pill status-active" style="font-weight: 700; font-size: 11px; letter-spacing: 0.05em;">
                <span class="status-dot"></span> ACTIVADO
            </span>
        <?php else: ?>
            <span class="service-status-pill" style="background-color: var(--mpch-surface-inactive); border: 1px solid var(--mpch-border-color); color: var(--mpch-text-inactive); font-weight: 700; font-size: 11px; letter-spacing: 0.05em;">
                <span style="width: 6px; height: 6px; border-radius: 9999px; background-color: var(--mpch-text-aux);"></span> INACTIVO
            </span>
        <?php endif; ?>
    </div>

    <div class="card-body mpch-2fa-body">
        <?php if ($isActivo): ?>
            <div class="row align-items-center">
                <div class="col-md-12">
                    <div class="mpch-2fa-desc-title">
                        Tu cuenta está protegida con un segundo factor de autenticación.
                    </div>
                    <div class="mpch-2fa-desc-text">
                        Al iniciar sesión, además de tu contraseña se te solicitará el código dinámico de 6 dígitos generado en tu teléfono móvil.
                    </div>
                    <div class="mpch-2fa-meta">
                        <div><strong>Vinculado el:</strong> <?= htmlspecialchars($fechaActivacion) ?></div>
                        <div><strong>Recuperación de Acceso:</strong> Correo Institucional</div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="mpch-2fa-desc-title">
                        Aumenta la seguridad de tu cuenta municipal.
                    </div>
                    <div class="mpch-2fa-desc-text mb-0">
                        Agrega una capa adicional de protección frente a robos de contraseña configurando Google Authenticator en tu celular de forma rápida y gratuita.
                    </div>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <button type="button" class="btn fw-bold px-3" style="background-color: var(--mpch-action-yellow); color: var(--mpch-text-primary); border-radius: 8px; border: none; font-size: 13px;" onclick="abrirModalEnrolamiento2FA()">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-lock-plus me-1" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M12.5 21h-5.5a2 2 0 0 1 -2 -2v-6a2 2 0 0 1 2 -2h10a2 2 0 0 1 1.74 1.012" />
                            <path d="M11 16a1 1 0 1 0 2 0a1 1 0 0 0 -2 0" />
                            <path d="M8 11v-4a4 4 0 1 1 8 0v4" />
                            <path d="M16 19h6" />
                            <path d="M19 16v6" />
                        </svg>
                        Activar 2FA Ahora
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Incluir modales y librerías de soporte -->
<?php require_once __DIR__ . '/modal_enrolamiento.php'; ?>
<link href="<?= Conectar::ruta() ?>seguridad/2fa/assets/css/two_factor.css" rel="stylesheet" />
<script src="<?= Conectar::ruta() ?>seguridad/2fa/assets/js/qrcode.min.js"></script>
<script src="<?= Conectar::ruta() ?>seguridad/2fa/assets/js/two_factor.js"></script>

<script>
window.TWO_FACTOR_API_URL = '<?= Conectar::ruta() ?>seguridad/2fa/controllers/TwoFactorController.php';

<?php /*
function desactivar2FA() {
    if (!confirm('¿Estás seguro de que deseas desactivar el Doble Factor de Autenticación? Tu cuenta quedará protegida únicamente con tu contraseña habitual.')) {
        return;
    }

    $.ajax({
        url: window.TWO_FACTOR_API_URL + '?op=desactivar_2fa',
        type: 'POST',
        dataType: 'json',
        success: function(resp) {
            if (resp.success) {
                alert(resp.message);
                location.reload();
            } else {
                alert(resp.message || 'Error al desactivar.');
            }
        },
        error: function() {
            alert('Error de conexión.');
        }
    });
}
*/ ?>
</script>
