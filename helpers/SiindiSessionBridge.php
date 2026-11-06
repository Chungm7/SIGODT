<?php
/** Compatibility boundary for the unchanged SIINDI screens; not an OTP provider. */
final class SiindiSessionBridge
{
    private static bool $running = false;

    private static function origin(string $url, bool $header = false): ?string
    {
        $p = parse_url($url);
        if (!is_array($p) || !in_array($p['scheme'] ?? '', ['http', 'https'], true) || empty($p['host'])
            || isset($p['user']) || isset($p['pass']) || ($header && (isset($p['query']) || isset($p['fragment']) || !empty($p['path'])))) return null;
        return strtolower($p['scheme'] . '://' . $p['host']) . ':' . ($p['port'] ?? ($p['scheme'] === 'https' ? 443 : 80));
    }

    public static function trustedPost(): bool
    {
        $sent = $_SERVER['HTTP_ORIGIN'] ?? null;
        $expected = self::origin(Conectar::ruta());
        return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && is_string($sent) && $expected !== null
            && self::origin($sent, true) === $expected;
    }

    public static function requireTrustedPost(): void
    {
        if (self::trustedPost()) return;
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success'=>false, 'message'=>'Solicitud no autorizada.']);
        exit;
    }

    private static function jwt(): JwtHandler
    {
        require_once __DIR__ . '/../config/jwt.php';
        return new JwtHandler();
    }

    private static function cookie(string $name, string $value, int $ttl = 0): void
    {
        if (!headers_sent()) self::jwt()->setCookie($name, $value, $ttl, Conectar::getCookiePath());
        if ($ttl < 0) unset($_COOKIE[$name]); else $_COOKIE[$name] = $value;
    }

    private static function issue(string $name, array $data, string $type, int $ttl): void
    {
        $previous = $_ENV['APP_NAME'] ?? null;
        $_ENV['APP_NAME'] = trim(Conectar::getCookiePath(), '/');
        try { self::cookie($name, self::jwt()->encrypt($data, $type, $ttl), $ttl); }
        finally { if ($previous === null) unset($_ENV['APP_NAME']); else $_ENV['APP_NAME'] = $previous; }
    }

    public static function clearPasswordContext(): void
    {
        unset($_SESSION['sigodt_password_context'], $_SESSION['blanqueamiento_user']);
        self::cookie('blanqueamiento', '', -3600);
    }

    public static function clearLogin(): void
    {
        foreach (['2fa_pending_SIGODT','2fa_verified_SIGODT','2fa_pending_user','2fa_email_otp','2fa_email_otp_time','2fa_revinculacion_ok','2fa_initial_enrollment_SIGODT'] as $key) unset($_SESSION[$key]);
        foreach (['session','2fa_pending','2fa','2fa_email_otp','2fa_revinculacion_ok'] as $name) self::cookie($name, '', -3600);
        self::clearPasswordContext();
    }

    public static function beginPending(array $pending): void
    {
        if (empty($pending['usu_id']) || empty($pending['pers_id']) || !preg_match('/^[0-9]{8}$/D', (string)($pending['usu_doc'] ?? ''))
            || ($pending['expires_at'] ?? 0) <= time() || empty($pending['challenge_id'])) throw new InvalidArgumentException('Invalid pending identity');
        self::clearLogin();
        foreach (array_keys($_SESSION) as $key) if (str_ends_with((string)$key, '_SIGODT')) unset($_SESSION[$key]);
        $pending['sigodt_nonce'] = bin2hex(random_bytes(32));
        $pending['usu_tipo'] = 'P';
        $_SESSION['2fa_pending_SIGODT'] = $pending;
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        self::projectPending();
    }

    private static function pending(): ?array
    {
        $p = $_SESSION['2fa_pending_SIGODT'] ?? null;
        return is_array($p) && ($p['expires_at'] ?? 0) > time() && !empty($p['sigodt_nonce']) ? $p : null;
    }

    private static function projectPending(): void
    {
        $p = self::pending();
        if (!$p) { unset($_SESSION['2fa_pending_user']); return; }
        $profile = $p['user_data'] ?? [];
        $_SESSION['2fa_pending_user'] = [
            'usu_id'=>$p['usu_id'], 'usu_doc'=>$p['usu_doc'], 'usu_tipo'=>'P',
            'usu_nom'=>$p['usu_nom'] ?? '', 'usu_apep'=>$p['usu_apep'] ?? '', 'usu_apem'=>$p['usu_apem'] ?? '',
            'perf_id'=>$profile['perf_id'] ?? 0, 'perfil_id'=>$profile['perf_id'] ?? 0,
            'perf_nombre'=>$profile['perf_nombre'] ?? '', 'sigodt_nonce'=>$p['sigodt_nonce'],
        ];
        // Cookie-only views need identity, not the private proof nonce. Keep the
        // nonce solely in the PHP alias consumed by the successful controller.
        $public = $_SESSION['2fa_pending_user'];
        unset($public['sigodt_nonce']);
        self::issue('2fa_pending', $public, '2fa_pending', max(1, $p['expires_at'] - time()));
    }

    /** Only call with the result of JwtHandler::decrypt(), never browser JSON. */
    public static function completionProof(object $envelope): ?array
    {
        $p = self::pending();
        $d = $envelope->data ?? null;
        if (!$p || !empty($_SESSION['usua_id_SIGODT']) || !is_object($d)
            || !in_array($envelope->success ?? null, [true, 'true'], true) || ($envelope->type ?? '') !== 'session'
            || !is_numeric($envelope->exp ?? null) || $envelope->exp <= time()
            || ($envelope->system->APP_NAME ?? null) !== trim(Conectar::getCookiePath(), '/')
            || !is_string($d->sigodt_nonce ?? null) || !hash_equals($p['sigodt_nonce'], $d->sigodt_nonce)
            || !is_scalar($d->usu_id ?? null) || (string)$d->usu_id !== (string)$p['usu_id']
            || ($d->usu_doc ?? null) !== $p['usu_doc'] || ($d->usu_tipo ?? null) !== 'P') return null;
        return ['kind'=>'siindi_completion', 'sigodt_nonce'=>$p['sigodt_nonce'], 'challenge_id'=>$p['challenge_id'],
            'usu_doc'=>$p['usu_doc'], 'usu_id'=>$p['usu_id'], 'expires_at'=>min($p['expires_at'], time()+30)];
    }

    public static function passwordContext(?string $stage = null): ?array
    {
        $context = $_SESSION['sigodt_password_context'] ?? null;
        if (!is_array($context) || ($context['expires_at'] ?? 0) <= time() || empty($context['nonce'])
            || ($stage !== null && ($context['stage'] ?? '') !== $stage)) return null;
        return $context;
    }

    public static function startPasswordContext(array $identity, string $stage = 'recovery', int $ttl = 900): void
    {
        if (!in_array($stage, ['recovery','first_access'], true) || !preg_match('/^[0-9]{8}$/D', (string)($identity['usu_doc'] ?? ''))
            || ($identity['usu_tipo'] ?? 'P') !== 'P' || $ttl <= 0) throw new InvalidArgumentException('Invalid reset identity');
        $clean = [];
        foreach (['usu_id','usu_doc','usu_nom','usu_apep','usu_apem','usu_correo','usu_emailp','pers_emailm','pers_emailp'] as $key) {
            if (isset($identity[$key]) && is_scalar($identity[$key])) $clean[$key] = $identity[$key];
        }
        $clean['usu_tipo'] = 'P';
        if (!is_string($_SESSION['csrf_token'] ?? null) || strlen($_SESSION['csrf_token']) !== 64) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['sigodt_password_context'] = ['identity'=>$clean, 'stage'=>$stage, 'nonce'=>bin2hex(random_bytes(32)), 'expires_at'=>time()+min($ttl,1900)];
        self::passwordCookie();
    }

    public static function advancePasswordContext(string $stage): void
    {
        $c = self::passwordContext();
        $allowed = ['recovery'=>'recovery_sent', 'recovery_sent'=>'first_access'];
        if (!$c || ($allowed[$c['stage']] ?? null) !== $stage) throw new LogicException('Invalid reset transition');
        $c['stage'] = $stage;
        $c['nonce'] = bin2hex(random_bytes(32));
        $c['expires_at'] = min($c['expires_at'], time()+($stage === 'first_access' ? 300 : 900));
        $_SESSION['sigodt_password_context'] = $c;
        self::passwordCookie();
    }

    private static function passwordCookie(): void
    {
        $c = self::passwordContext();
        if (!$c) return;
        $data = $c['identity'] + ['blanqueamiento'=>2, 'sigodt_reset_nonce'=>$c['nonce']];
        $_SESSION['blanqueamiento_user'] = $data;
        self::issue('blanqueamiento', $data, 'blanqueamiento', max(1,$c['expires_at']-time()));
    }

    private static function fullIdentity(): ?array
    {
        if (empty($_SESSION['usua_id_SIGODT']) || empty($_SESSION['sigur_usuario_id_SIGODT']) || empty($_SESSION['usua_dni_SIGODT'])) return null;
        return ['usu_id'=>$_SESSION['sigur_usuario_id_SIGODT'], 'usu_doc'=>$_SESSION['usua_dni_SIGODT'], 'usu_tipo'=>'P',
            'usu_nom'=>$_SESSION['pers_nombre_SIGODT'] ?? '', 'usu_apep'=>$_SESSION['pers_apelpat_SIGODT'] ?? '', 'usu_apem'=>$_SESSION['pers_apelmat_SIGODT'] ?? '',
            'perf_id'=>$_SESSION['rol_id_SIGODT'] ?? 0, 'perfil_id'=>$_SESSION['rol_id_SIGODT'] ?? 0, 'perf_nombre'=>$_SESSION['rol_nombre_SIGODT'] ?? '',
            'sigodt_sid'=>hash('sha256', session_id())];
    }

    private static function fullCookie(array $identity): void
    {
        $token = $_COOKIE['session'] ?? null;
        $e = is_string($token) && $token !== '' ? self::jwt()->decrypt($token) : null;
        if (is_object($e) && ($e->type ?? '') === 'session' && ($e->exp ?? 0) > time()
            && ($e->data->sigodt_sid ?? null) === $identity['sigodt_sid']
            && (string)($e->data->usu_id ?? '') === (string)$identity['usu_id'] && ($e->data->usu_doc ?? null) === $identity['usu_doc']) return;
        // This compatibility cookie has no pending nonce and can never promote a new login.
        self::issue('session', $identity, 'session', 1500);
    }

    /** Primary credentials authorize first setup, never replacement of a linked factor. */
    private static function guardInitialEnrollment(string $operation): void
    {
        if (!in_array($operation, ['iniciar_enrolamiento','confirmar_enrolamiento'], true)) return;
        $p = self::pending();
        if (!$p) return;
        $grant = $_SESSION['2fa_initial_enrollment_SIGODT'] ?? null;
        if ($operation === 'confirmar_enrolamiento' && (!is_array($grant)
            || ($grant['expires_at'] ?? 0) <= time()
            || ($grant['challenge_id'] ?? null) !== $p['challenge_id']
            || ($grant['sigodt_nonce'] ?? null) !== $p['sigodt_nonce']
            || ($grant['usu_doc'] ?? null) !== $p['usu_doc']
            || (string)($grant['usu_id'] ?? '') !== (string)$p['usu_id'])) self::denyEnrollment(403);
        try {
            // Read directly: the source reader maps DB errors to "no record", which
            // cannot establish eligibility at this mandatory-authentication boundary.
            $db = (new Conectar())->conexion(true);
            $statement = $db->prepare('SELECT seus_id, seus_usuario_id, seus_usuario_doc, seus_esta_vinc
                FROM public.tb_seguridad_usuario_2fa WHERE seus_usuario_doc = :usu_doc LIMIT 1');
            $statement->execute([':usu_doc'=>$p['usu_doc']]);
            $record = $statement->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $error) { self::denyEnrollment(503); }
        if ($record !== false) {
            if (!is_array($record) || !array_key_exists('seus_esta_vinc', $record)) self::denyEnrollment(503);
            if ((string)($record['seus_usuario_id'] ?? '') !== (string)$p['usu_id']
                || ($record['seus_usuario_doc'] ?? null) !== $p['usu_doc']) self::denyEnrollment(403);
            $flag = $record['seus_esta_vinc'];
            if (in_array($flag, [true,1,'1','t','true'], true)) self::denyEnrollment(403);
            if (!in_array($flag, [false,0,'0','f','false'], true)) self::denyEnrollment(503);
        } elseif ($operation === 'confirmar_enrolamiento') self::denyEnrollment(403);
        if ($operation === 'iniciar_enrolamiento') {
            $_SESSION['2fa_initial_enrollment_SIGODT'] = [
                'usu_id'=>$p['usu_id'], 'usu_doc'=>$p['usu_doc'], 'challenge_id'=>$p['challenge_id'],
                'sigodt_nonce'=>$p['sigodt_nonce'], 'expires_at'=>$p['expires_at'],
            ];
        }
    }

    private static function denyEnrollment(int $status): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success'=>false,'message'=>$status === 503
            ? 'No se pudo verificar el estado del segundo factor.'
            : 'Para reemplazar un dispositivo vinculado debes completar primero el segundo factor.']);
        exit;
    }

    private static function loginRedirect(): void
    {
        header('Location: ' . Conectar::ruta() . 'index.php');
        exit;
    }

    public static function bootstrap(): void
    {
        if (self::$running) return;
        self::$running = true;
        try {
            $root = str_replace('\\', '/', dirname(__DIR__));
            $script = str_replace('\\', '/', realpath($_SERVER['SCRIPT_FILENAME'] ?? '') ?: '');
            $module = str_starts_with($script, $root . '/seguridad/');
            $landing = $script === $root . '/view/inicio/index.php';
            if (!$module && !$landing) return;
            if ($module) $_ENV['APP_NAME'] = trim(Conectar::getCookiePath(), '/');
            $controller = $script === $root . '/seguridad/2fa/controllers/TwoFactorController.php';
            if ($controller) self::requireTrustedPost(); // Reject before touching stored authority/counters.
            if ($module) {
                // Original mailer appends sigur/auth and sigur/ws itself. Adapt
                // destination API-base conventions only for this module request.
                $base = Conectar::getEnv('SIGUR_URL', '');
                if (is_string($base) && $base !== '') $_ENV['SIGUR_URL'] = preg_replace('~/sigur(?:/ws)?$~i', '', rtrim($base, '/')) . '/';
            }
            if (isset($_SESSION['2fa_pending_SIGODT']) && !self::pending()) self::clearLogin();
            if ($landing && self::pending() && empty($_SESSION['usua_id_SIGODT'])) {
                $token = $_COOKIE['session'] ?? '';
                $proof = is_string($token) ? self::completionProof(self::jwt()->decrypt($token)) : null;
                $ok = false;
                if ($proof) {
                    $_SESSION['2fa_verified_SIGODT'] = $proof;
                    require_once __DIR__ . '/../models/Usuario.php';
                    try { $ok = (new Usuario())->finalizarLogin2FA(); } catch (Throwable $e) { $ok = false; }
                }
                if ($ok) unset($_SESSION['2fa_initial_enrollment_SIGODT']);
                if (!$ok) {
                    self::clearLogin();
                    $_SESSION['login_error'] = 'No se pudo completar el acceso. Ingresa nuevamente.';
                    self::loginRedirect();
                }
            }
            $full = self::fullIdentity();
            if ($full && ($module || $landing)) {
                self::fullCookie($full);
                if (!self::pending()) {
                    unset($_SESSION['2fa_pending_user']);
                    if (isset($_COOKIE['2fa_pending'])) self::cookie('2fa_pending', '', -3600);
                }
            }
            if (!$module) return;
            if (str_contains($script, '/recuperar_contrasena/')) {
                $context = self::passwordContext();
                if (!$context || !in_array($context['stage'], ['recovery','recovery_sent'], true)) self::loginRedirect();
                self::passwordCookie(); return;
            }
            if (str_contains($script, '/primer_acceso/')) {
                if (!self::passwordContext('first_access')) self::loginRedirect();
                self::passwordCookie(); return;
            }
            if (str_contains($script, '/actualizar_contrasena/') || str_ends_with($script, '/perfil_2fa.php') || str_ends_with($script, '/modal_enrolamiento.php')) {
                if (!$full) self::loginRedirect();
                return;
            }
            if (self::pending()) {
                if ($controller) self::guardInitialEnrollment(is_string($_GET['op'] ?? null) ? $_GET['op'] : '');
                self::projectPending();
                // Ignore client pending data at the producer boundary: source must
                // consume the authoritative private alias, including its nonce.
                if ($controller) unset($_COOKIE['2fa_pending']);
                // An old full cookie must not replace the current pending identity.
                if (!$landing && isset($_COOKIE['session'])) self::cookie('session', '', -3600);
                return;
            }
            if (!$full) {
                unset($_SESSION['2fa_pending_user']);
                foreach (['session','2fa_pending'] as $name) if (isset($_COOKIE[$name])) self::cookie($name, '', -3600);
                if ($controller) { http_response_code(401); echo json_encode(['success'=>false,'message'=>'Sesión expirada.']); exit; }
                self::loginRedirect();
            }
        } finally { self::$running = false; }
    }
}
