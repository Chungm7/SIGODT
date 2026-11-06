<?php
/**
 * Controlador de Endpoints para el Módulo de Doble Factor (2FA)
 * Procesa las solicitudes AJAX de verificación por Correo Institucional y Dispositivo (TOTP).
 * 
 * Municipalidad Provincial de Chiclayo (MPCH)
 */

header('Content-Type: application/json; charset=utf-8');
if (file_exists(__DIR__ . '/../../../config/conexion.php')) {
    require_once __DIR__ . '/../../../config/conexion.php';
    require_once __DIR__ . '/../../../config/jwt.php';
} else {
    require_once __DIR__ . '/../../config/conexion.php';
    require_once __DIR__ . '/../../config/jwt.php';
}
require_once __DIR__ . '/../services/TwoFactorService.php';
require_once __DIR__ . '/../services/SigurMailer.php';
require_once __DIR__ . '/../services/SmtpMailer.php';
require_once __DIR__ . '/../models/TwoFactorModel.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$jwt = new JwtHandler();
$op = $_GET['op'] ?? '';

// Obtener datos del usuario pendiente de 2FA o con sesión activa
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
if (!$pendingData && !empty($_COOKIE['session'])) {
    $dec = $jwt->decrypt($_COOKIE['session']);
    if (!empty($dec->data)) {
        $pendingData = $dec->data;
    }
}

// Función auxiliar para enmascarar correos (ej: j****z@munichiclayo.gob.pe)
function maskEmailString($email) {
    if (!$email || strpos($email, '@') === false) {
        return 'correo@munichiclayo.gob.pe';
    }
    list($user, $domain) = explode('@', $email, 2);
    $len = strlen($user);
    if ($len <= 2) {
        $masked = substr($user, 0, 1) . '***';
    } else {
        $masked = substr($user, 0, 2) . str_repeat('*', max(3, $len - 3)) . substr($user, -1);
    }
    return $masked . '@' . $domain;
}

switch ($op) {

    // =====================================================================
    // 1. SOLICITAR CÓDIGO POR CORREO INSTITUCIONAL (6 DÍGITOS - 120 SEGUNDOS)
    // =====================================================================
    case 'solicitar_codigo_correo':
    case 'enviar_codigo_correo':
        if (!$pendingData || empty($pendingData->usu_doc)) {
            echo json_encode(['success' => false, 'message' => 'Sesión expirada. Por favor, ingresa tus credenciales nuevamente.']);
            exit;
        }

        // Protección contra doble clic o solicitudes concurrentes (cooldown mínimo de 5 segundos)
        $ultimoEnvio = $_SESSION['2fa_email_otp_time'] ?? 0;
        if ((time() - $ultimoEnvio) < 5) {
            echo json_encode([
                'success' => false,
                'message' => 'Ya se ha enviado un código recientemente. Por favor, espera unos segundos antes de solicitar otro.'
            ]);
            break;
        }
        $_SESSION['2fa_email_otp_time'] = time();

        $usu_doc = $pendingData->usu_doc;
        $nombreUsuario = trim(($pendingData->usu_nom ?? 'Servidor') . ' ' . ($pendingData->usu_apep ?? ''));

        // Consultar correo institucional en sc_escalafon.tb_persona
        $emailInstitucional = TwoFactorModel::getEmailByDni($usu_doc);

        // Si no tiene correo institucional registrado, no enviar al DNI con dominio institucional
        if (empty($emailInstitucional)) {
            unset($_SESSION['2fa_email_otp_time']);

            TwoFactorModel::logAudit($pendingData->usu_id ?? null, $usu_doc, 'SOLICITUD_FALLIDA_SIN_CORREO', [
                'metodo'  => 'CORREO_INSTITUCIONAL',
                'sistema' => $_ENV['APP_NAME'] ?? 'sisIndicadores',
                'motivo'  => 'Usuario sin correo institucional registrado en escalafón'
            ]);

            echo json_encode([
                'success'    => false,
                'sin_correo' => true,
                'message'    => 'No cuentas con un correo institucional registrado. Por favor, ingresa utilizando tu aplicación de doble factor (Google Authenticator).'
            ]);
            break;
        }

        // Generar código numérico único de 6 dígitos
        $codigoCorreo = (string)random_int(100000, 999999);
        $expiraEn = time() + 120; // 120 segundos de validez

        // Guardar OTP en la sesión del servidor (evita sobrecargar los encabezados HTTP Cookie)
        $otpPayload = [
            'usu_doc'   => $usu_doc,
            'otp_hash'  => password_hash($codigoCorreo, PASSWORD_BCRYPT),
            'expira_en' => $expiraEn,
            'email'     => $emailInstitucional
        ];
        $_SESSION['2fa_email_otp'] = (object)$otpPayload;

        // Purgar cookies residuales para mantener los encabezados HTTP ligeros (< 2KB)
        $jwt->setCookie('2fa_email_otp', '', -3600, '/' . $_ENV['APP_NAME'] . '/');
        $jwt->setCookie('2fa_revinculacion_ok', '', -3600, '/' . $_ENV['APP_NAME'] . '/');

        // Despachar correo electrónico mediante API institucional de SIGUR (MPCH)
        $resMail = SigurMailer::sendVerificationCode($emailInstitucional, $codigoCorreo, $nombreUsuario, 120);

        if (!$resMail['success']) {
            echo json_encode([
                'success' => false,
                'message' => 'No se pudo enviar el correo de verificación: ' . ($resMail['message'] ?? 'Error en el servicio de correo.')
            ]);
            break;
        }

        // Registrar auditoría en español
        TwoFactorModel::logAudit($pendingData->usu_id ?? null, $usu_doc, 'SOLICITUD_CODIGO_CORREO', [
            'metodo'         => 'CORREO_INSTITUCIONAL',
            'sistema'        => $_ENV['APP_NAME'] ?? 'sisIndicadores',
            'correo_destino' => maskEmailString($emailInstitucional),
            'validez_seg'    => 120
        ]);

        echo json_encode([
            'success'            => true,
            'message'            => 'Se ha enviado un código de 6 dígitos a su correo institucional.',
            'correo_enmascarado' => maskEmailString($emailInstitucional),
            'expira_seg'         => 120
        ]);
        break;


    // =====================================================================
    // 2. VERIFICAR CÓDIGO DEL CORREO INSTITUCIONAL (6 DÍGITOS)
    // =====================================================================
    case 'verificar_codigo_correo':
        if (!$pendingData || empty($pendingData->usu_doc)) {
            echo json_encode(['success' => false, 'message' => 'Sesión expirada. Inicia sesión nuevamente.']);
            exit;
        }

        $codigoIngresado = trim($_POST['codigo_correo'] ?? '');
        $accion = trim($_POST['accion'] ?? 'login'); // 'login' o 'revinculacion'

        // Obtener datos del código OTP generado
        $otpData = null;
        if (!empty($_COOKIE['2fa_email_otp'])) {
            $decOtp = $jwt->decrypt($_COOKIE['2fa_email_otp']);
            if (!empty($decOtp->data)) $otpData = $decOtp->data;
        }
        if (!$otpData && !empty($_SESSION['2fa_email_otp'])) {
            $otpData = (object)$_SESSION['2fa_email_otp'];
        }

        if (!$otpData || empty($otpData->otp_hash)) {
            echo json_encode(['success' => false, 'message' => 'No hay un código pendiente. Solicita uno nuevo.']);
            exit;
        }

        $ahora = time();
        $restantes = max(0, ($otpData->expira_en ?? 0) - $ahora);

        // Validar expiración de 120 segundos
        if ($ahora > ($otpData->expira_en ?? 0)) {
            TwoFactorModel::logAudit($pendingData->usu_id ?? null, $pendingData->usu_doc, 'VERIFICACION_FALLIDA_CORREO', [
                'metodo'           => 'CORREO_INSTITUCIONAL',
                'sistema'          => $_ENV['APP_NAME'] ?? 'sisIndicadores',
                'codigo_ingresado' => $codigoIngresado,
                'correo_destino'   => maskEmailString($otpData->email ?? ''),
                'motivo'           => 'CÓDIGO EXPIRADO (+120s)'
            ]);

            echo json_encode(['success' => false, 'message' => 'El código ha expirado (más de 120s). Solicita un nuevo código.']);
            exit;
        }

        // Validar coincidencia del código de 6 dígitos
        if (!password_verify($codigoIngresado, $otpData->otp_hash)) {
            TwoFactorModel::logAudit($pendingData->usu_id ?? null, $pendingData->usu_doc, 'VERIFICACION_FALLIDA_CORREO', [
                'metodo'           => 'CORREO_INSTITUCIONAL',
                'sistema'          => $_ENV['APP_NAME'] ?? 'sisIndicadores',
                'codigo_ingresado' => $codigoIngresado,
                'correo_destino'   => maskEmailString($otpData->email ?? ''),
                'motivo'           => 'CÓDIGO INCORRECTO'
            ]);

            echo json_encode(['success' => false, 'message' => 'El código de 6 dígitos ingresado es incorrecto.']);
            exit;
        }

        // CASO A: Verificación para RE-VINCULAR NUEVO DISPOSITIVO
        if ($accion === 'revinculacion') {
            TwoFactorModel::logAudit($pendingData->usu_id ?? null, $pendingData->usu_doc, 'SOLICITUD_RE_VINCULACION_DISPOSITIVO', [
                'metodo'              => 'CORREO_INSTITUCIONAL',
                'sistema'             => $_ENV['APP_NAME'] ?? 'sisIndicadores',
                'codigo_ingresado'    => $codigoIngresado,
                'correo_destino'      => maskEmailString($otpData->email ?? ''),
                'tiempo_restante_seg' => $restantes
            ]);

            // Guardar autorización en sesión del servidor (sin sobrecargar cookies del navegador)
            $_SESSION['2fa_revinculacion_ok'] = [
                'doc'        => $pendingData->usu_doc,
                'autorizado' => true,
                'ts'         => time()
            ];

            // Limpiar OTP y cookies residuales
            unset($_SESSION['2fa_email_otp']);
            $jwt->setCookie('2fa_email_otp', '', -3600, '/' . $_ENV['APP_NAME'] . '/');
            $jwt->setCookie('2fa_revinculacion_ok', '', -3600, '/' . $_ENV['APP_NAME'] . '/');

            echo json_encode([
                'success'       => true,
                'revinculacion' => true,
                'message'       => 'Identidad confirmada. Ahora puedes vincular tu nuevo dispositivo.'
            ]);
            exit;
        }

        // CASO B: Verificación de INGRESO AL SISTEMA POR CORREO
        TwoFactorModel::logAudit($pendingData->usu_id ?? null, $pendingData->usu_doc, 'VERIFICACION_EXITOSA_CORREO', [
            'metodo'              => 'CORREO_INSTITUCIONAL',
            'sistema'             => $_ENV['APP_NAME'] ?? 'sisIndicadores',
            'codigo_ingresado'    => $codigoIngresado,
            'correo_destino'      => maskEmailString($otpData->email ?? ''),
            'tiempo_restante_seg' => $restantes
        ]);

        // Emitir sesión definitiva JWT aislada al APP_NAME
        $sessionPayload = (object)[
            'usu_id'      => $pendingData->usu_id ?? 1,
            'usu_doc'     => $pendingData->usu_doc,
            'usu_nom'     => $pendingData->usu_nom ?? 'Usuario',
            'usu_apep'    => $pendingData->usu_apep ?? '',
            'usu_apem'    => $pendingData->usu_apem ?? '',
            'usu_tipo'    => $pendingData->usu_tipo ?? 'P',
            'perf_id'     => $pendingData->perf_id ?? 1,
            'perfil_id'   => $pendingData->perfil_id ?? 1,
            'perf_nombre' => $pendingData->perf_nombre ?? 'Administrador',
            'dfa'         => 1
        ];

        foreach ($pendingData as $k => $v) {
            if (!isset($sessionPayload->$k)) {
                $sessionPayload->$k = $v;
            }
        }

        $sessionToken = $jwt->encrypt($sessionPayload, 'session');
        $jwt->setCookie('session', $sessionToken, 0, '/' . $_ENV['APP_NAME'] . '/');

        // Limpiar tokens temporales y cookies residuales (estrictamente en el subdirectorio del sistema)
        $jwt->setCookie('2fa_pending', '', -3600, '/' . $_ENV['APP_NAME'] . '/');
        $jwt->setCookie('2fa', '', -3600, '/' . $_ENV['APP_NAME'] . '/');
        $jwt->setCookie('blanqueamiento', '', -3600, '/' . $_ENV['APP_NAME'] . '/');
        $jwt->setCookie('2fa_email_otp', '', -3600, '/' . $_ENV['APP_NAME'] . '/');
        $jwt->setCookie('2fa_revinculacion_ok', '', -3600, '/' . $_ENV['APP_NAME'] . '/');
        unset($_SESSION['2fa_pending_user']);
        unset($_SESSION['2fa_email_otp']);

        echo json_encode([
            'success'  => true,
            'message'  => 'Código verificado con éxito. Ingresando al sistema...',
            'redirect' => Conectar::ruta() . 'view/inicio/'
        ]);
        break;


    // =====================================================================
    // 3. VERIFICACIÓN DEL CÓDIGO DE 6 DÍGITOS DE GOOGLE AUTHENTICATOR (TOTP)
    // =====================================================================
    case 'verificar_desafio':
        if (!$pendingData || empty($pendingData->usu_doc)) {
            echo json_encode([
                'success'  => false,
                'message'  => 'La sesión ha expirado. Ingresa tus credenciales nuevamente.',
                'redirect' => Conectar::ruta()
            ]);
            exit;
        }

        $usu_doc = $pendingData->usu_doc;
        $codigo = trim($_POST['codigo_2fa'] ?? '');

        // 1. Verificar bloqueo por fuerza bruta
        $status = TwoFactorModel::checkStatus($usu_doc);
        if ($status['locked']) {
            TwoFactorModel::logAudit($pendingData->usu_id ?? null, $usu_doc, 'BLOQUEO_TEMPORAL', [
                'metodo'  => 'GOOGLE_AUTHENTICATOR',
                'sistema' => $_ENV['APP_NAME'] ?? 'sisIndicadores',
                'motivo'  => 'Bloqueo temporal por exceso de intentos fallidos'
            ]);

            echo json_encode([
                'success' => false,
                'message' => "Cuenta bloqueada temporalmente por intentos fallidos. Intenta nuevamente en {$status['lock_minutes']} minuto(s)."
            ]);
            exit;
        }

        if (!$status['enabled'] || empty($status['data'])) {
            echo json_encode([
                'success' => false,
                'message' => 'No tienes configurado el segundo factor con Google Authenticator.'
            ]);
            exit;
        }

        // 2. Desencriptar secreto Base32
        $plainSecret = TwoFactorService::decryptSecret($status['data']['seus_llave_secr']);
        if (!$plainSecret) {
            echo json_encode(['success' => false, 'message' => 'Error al procesar credenciales de seguridad.']);
            exit;
        }

        // 3. Validar código con anti-replay (Ventana de tolerancia 2: actual ± 60 segundos)
        $lastTs = (int)($status['data']['seus_marca_tiem'] ?? 0);
        $verifyRes = TwoFactorService::verifyCode($plainSecret, $codigo, 2, $lastTs);

        if ($verifyRes['valid']) {
            TwoFactorModel::recordSuccess($usu_doc, $verifyRes['timestamp']);
            TwoFactorModel::logAudit($pendingData->usu_id ?? null, $usu_doc, 'VERIFICACION_EXITOSA_DISPOSITIVO', [
                'metodo'           => 'GOOGLE_AUTHENTICATOR',
                'sistema'          => $_ENV['APP_NAME'] ?? 'sisIndicadores',
                'codigo_ingresado' => $codigo,
                'ts_usado'         => $verifyRes['timestamp']
            ]);

            // Emitir sesión definitiva
            $sessionPayload = (object)[
                'usu_id'      => $pendingData->usu_id ?? 1,
                'usu_doc'     => $usu_doc,
                'usu_nom'     => $pendingData->usu_nom ?? 'Usuario',
                'usu_apep'    => $pendingData->usu_apep ?? '',
                'usu_apem'    => $pendingData->usu_apem ?? '',
                'usu_tipo'    => $pendingData->usu_tipo ?? 'P',
                'perf_id'     => $pendingData->perf_id ?? 1,
                'perfil_id'   => $pendingData->perfil_id ?? 1,
                'perf_nombre' => $pendingData->perf_nombre ?? 'Administrador',
                'dfa'         => 1
            ];

            foreach ($pendingData as $k => $v) {
                if (!isset($sessionPayload->$k)) {
                    $sessionPayload->$k = $v;
                }
            }

            $sessionToken = $jwt->encrypt($sessionPayload, 'session');
            $jwt->setCookie('session', $sessionToken, 0, '/' . $_ENV['APP_NAME'] . '/');

            // Purgar tokens temporales y cookies residuales (estrictamente en el subdirectorio del sistema)
            $jwt->setCookie('2fa_pending', '', -3600, '/' . $_ENV['APP_NAME'] . '/');
            $jwt->setCookie('2fa', '', -3600, '/' . $_ENV['APP_NAME'] . '/');
            $jwt->setCookie('blanqueamiento', '', -3600, '/' . $_ENV['APP_NAME'] . '/');
            $jwt->setCookie('2fa_email_otp', '', -3600, '/' . $_ENV['APP_NAME'] . '/');
            $jwt->setCookie('2fa_revinculacion_ok', '', -3600, '/' . $_ENV['APP_NAME'] . '/');
            unset($_SESSION['2fa_pending_user']);
            unset($_SESSION['2fa_email_otp']);
            unset($_SESSION['2fa_revinculacion_ok']);

            echo json_encode([
                'success'  => true,
                'message'  => 'Verificación exitosa. Accediendo al sistema...',
                'redirect' => Conectar::ruta() . 'view/inicio/'
            ]);
        } else {
            $failInfo = TwoFactorModel::recordFailedAttempt($usu_doc);
            TwoFactorModel::logAudit($pendingData->usu_id ?? null, $usu_doc, 'VERIFICACION_FALLIDA_DISPOSITIVO', [
                'metodo'           => 'GOOGLE_AUTHENTICATOR',
                'sistema'          => $_ENV['APP_NAME'] ?? 'sisIndicadores',
                'codigo_ingresado' => $codigo,
                'intentos'         => $failInfo['attempts'],
                'motivo'           => $verifyRes['message']
            ]);

            $msg = $verifyRes['message'];
            if ($failInfo['locked']) {
                $msg = "Has superado el límite de intentos. Tu cuenta ha sido bloqueada temporalmente por {$failInfo['lock_minutes']} minutos.";
            } else {
                $restantes = max(0, 5 - $failInfo['attempts']);
                if ($restantes > 0 && $restantes <= 3) {
                    $msg .= " Te quedan {$restantes} intento(s).";
                }
            }

            echo json_encode([
                'success' => false,
                'message' => $msg,
                'locked'  => $failInfo['locked']
            ]);
        }
        break;


    // =====================================================================
    // 4. INICIAR ENROLAMIENTO (NUEVO QR Y CLAVE SECRETA)
    // =====================================================================
    case 'iniciar_enrolamiento':
        $activeUser = $pendingData;
        if (!$activeUser || empty($activeUser->usu_doc)) {
            echo json_encode(['success' => false, 'message' => 'Sesión no válida o expirada.']);
            exit;
        }

        $secret = TwoFactorService::generateSecret(20);
        $systemLabel = !empty($_ENV['APP_NAME']) ? $_ENV['APP_NAME'] : (!empty($_ENV['SIS_NOM']) ? $_ENV['SIS_NOM'] : 'Sistemas');
        $issuer = 'MPCH 2FA';
        $account = $activeUser->usu_doc . ' (' . ($activeUser->usu_nom ?? 'Usuario') . ')';
        $otpUri = TwoFactorService::getOtpAuthUri($issuer, $account, $secret);

        // Guardar secreto en base de datos como pendiente de confirmación
        $initOk = TwoFactorModel::initEnrollment($activeUser->usu_id ?? 1, $activeUser->usu_doc, $secret);
        if (!$initOk) {
            echo json_encode(['success' => false, 'message' => 'Error al registrar la clave secreta en la base de datos.']);
            exit;
        }

        TwoFactorModel::logAudit($activeUser->usu_id ?? 1, $activeUser->usu_doc, 'VINCULACION_DISPOSITIVO_INICIADA', [
            'metodo'  => 'GOOGLE_AUTHENTICATOR',
            'sistema' => $_ENV['APP_NAME'] ?? 'sisIndicadores'
        ]);

        echo json_encode([
            'success'     => true,
            'secret'      => $secret,
            'secret_fmt'  => chunk_split($secret, 4, ' '),
            'otp_uri'     => $otpUri,
            'account'     => $account,
            'issuer'      => $issuer
        ]);
        break;


    // =====================================================================
    // 5. CONFIRMAR ENROLAMIENTO (SIN CÓDIGOS DE RESPALDO -> ENTRA DE FRENTE)
    // =====================================================================
    case 'confirmar_enrolamiento':
        $activeUser = $pendingData;
        if (!$activeUser || empty($activeUser->usu_doc)) {
            echo json_encode(['success' => false, 'message' => 'Sesión no válida o expirada.']);
            exit;
        }

        $codigo = trim($_POST['codigo_confirmacion'] ?? '');
        $status = TwoFactorModel::getRecordByDoc($activeUser->usu_doc);

        if (!$status || empty($status['seus_llave_secr'])) {
            echo json_encode(['success' => false, 'message' => 'No hay un proceso de vinculación pendiente.']);
            exit;
        }

        $plainSecret = TwoFactorService::decryptSecret($status['seus_llave_secr']);
        if (!$plainSecret) {
            echo json_encode(['success' => false, 'message' => 'Error al leer la clave de vinculación. Vuelve a escanear el código QR.']);
            exit;
        }

        // Validar código con ventana de tolerancia 2 (actual ± 60 segundos)
        $verifyRes = TwoFactorService::verifyCode($plainSecret, $codigo, 2, 0);

        if ($verifyRes['valid']) {
            // Activar en base de datos sin códigos de respaldo
            TwoFactorModel::confirmEnrollment($activeUser->usu_doc);

            // Determinar si fue primera vinculación o re-vinculación
            $esRevinc = !empty($_SESSION['2fa_revinculacion_ok']) || !empty($_COOKIE['2fa_revinculacion_ok']);
            $eventoAuditoria = $esRevinc ? 'RE_VINCULACION_CONFIRMADA' : 'PRIMERA_VINCULACION_DISPOSITIVO';

            TwoFactorModel::logAudit($activeUser->usu_id ?? 1, $activeUser->usu_doc, $eventoAuditoria, [
                'metodo'           => 'GOOGLE_AUTHENTICATOR',
                'sistema'          => $_ENV['APP_NAME'] ?? 'sisIndicadores',
                'codigo_ingresado' => $codigo
            ]);

            // Enviar notificación de seguridad por correo al ciudadano/servidor al re-vincular nuevo dispositivo
            if ($esRevinc) {
                $emailNotif = TwoFactorModel::getEmailByDni($activeUser->usu_doc);
                if (!empty($emailNotif)) {
                    $nombreUser = trim(($activeUser->usu_nom ?? 'Servidor') . ' ' . ($activeUser->usu_apep ?? ''));
                    SigurMailer::sendDeviceLinkedNotification($emailNotif, $nombreUser);
                }
            }

            // Emitir sesión definitiva inmediatamente (Pasa de frente al sistema)
            $sessionPayload = (object)[
                'usu_id'      => $activeUser->usu_id ?? 1,
                'usu_doc'     => $activeUser->usu_doc,
                'usu_nom'     => $activeUser->usu_nom ?? 'Usuario',
                'usu_apep'    => $activeUser->usu_apep ?? '',
                'usu_apem'    => $activeUser->usu_apem ?? '',
                'usu_tipo'    => $activeUser->usu_tipo ?? 'P',
                'perf_id'     => $activeUser->perf_id ?? 1,
                'perfil_id'   => $activeUser->perfil_id ?? 1,
                'perf_nombre' => $activeUser->perf_nombre ?? 'Administrador',
                'dfa'         => 1
            ];

            foreach ($activeUser as $k => $v) {
                if (!isset($sessionPayload->$k)) {
                    $sessionPayload->$k = $v;
                }
            }

            $sessionToken = $jwt->encrypt($sessionPayload, 'session');
            $jwt->setCookie('session', $sessionToken, 0, '/' . $_ENV['APP_NAME'] . '/');

            // Limpiar tokens temporales y cookies residuales (estrictamente en el subdirectorio del sistema)
            $jwt->setCookie('2fa_pending', '', -3600, '/' . $_ENV['APP_NAME'] . '/');
            $jwt->setCookie('2fa', '', -3600, '/' . $_ENV['APP_NAME'] . '/');
            $jwt->setCookie('blanqueamiento', '', -3600, '/' . $_ENV['APP_NAME'] . '/');
            $jwt->setCookie('2fa_revinculacion_ok', '', -3600, '/' . $_ENV['APP_NAME'] . '/');
            $jwt->setCookie('2fa_email_otp', '', -3600, '/' . $_ENV['APP_NAME'] . '/');
            $jwt->setCookie('2fa_backup_pending', '', -3600, '/' . $_ENV['APP_NAME'] . '/');
            unset($_SESSION['2fa_pending_user']);
            unset($_SESSION['2fa_revinculacion_ok']);
            unset($_SESSION['2fa_email_otp']);

            echo json_encode([
                'success'  => true,
                'message'  => 'Dispositivo vinculado con éxito. Accediendo al sistema...',
                'redirect' => Conectar::ruta() . 'view/inicio/'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'El código de 6 dígitos ingresado no coincide con tu aplicación.'
            ]);
        }
        break;


    // =====================================================================
    // 6. DESACTIVAR 2FA (OPCIONAL DESDE EL PERFIL)
    // =====================================================================
    case 'desactivar_2fa':
        $activeUser = $pendingData;
        if (!$activeUser || empty($activeUser->usu_doc)) {
            echo json_encode(['success' => false, 'message' => 'Sesión no válida.']);
            exit;
        }

        TwoFactorModel::disable2FA($activeUser->usu_doc);
        TwoFactorModel::logAudit($activeUser->usu_id ?? null, $activeUser->usu_doc, 'CIERRE_SESION', [
            'metodo'  => 'PERFIL_USUARIO',
            'sistema' => $_ENV['APP_NAME'] ?? 'sisIndicadores',
            'accion'  => 'DESACTIVACION_VOLUNTARIA_2FA'
        ]);

        echo json_encode(['success' => true, 'message' => 'El Doble Factor ha sido desactivado.']);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Operación no válida.']);
        break;
}
