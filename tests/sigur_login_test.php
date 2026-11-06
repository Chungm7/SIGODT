<?php
/**
 * Test Suite: Unified SIGUR Login Validation and Dynamic Error Messages
 *
 * Validates:
 * 1. Acquisition of system Bearer token from SIGUR auth endpoint with fallback credentials.
 * 2. Dynamic error message propagation into $_SESSION['login_error'] on login failure
 *    (e.g., "La contraseña es incorrecta (1/5)", "El usuario no tiene acceso al sistema").
 * 3. Default fallback error message when response message is empty ("Datos incorrectos").
 * 4. Password whitening (blanqueamiento) flow when response contains data.url:
 *    isolated temporary cookie/context on /SIGODT/ and local original first-access route.
 * 5. Validation of active system 8 permission in local DB (db_simcix):
 *    if absent, $_SESSION['login_error'] = 'El usuario no tiene permisos activos para el sistema SIGODT'.
 * 6. Successful login flow:
 *    - Profile loading from sc_escalafon.tb_persona + sc_seguridad.tb_permiso + sc_seguridad.tb_perfil
 *    - Session history recording in sc_seguridad.tb_historial_sesion
 *    - Area auto-provisioning via Area::sincronizar_area_defecto
 *    - Session variables population (usua_id_SIGODT, rol_id_SIGODT, etc.)
 *    - Redirection to view/inicio/
 * 7. Auth service failure handling (connection failure or missing token).
 * 8. index.php error alert display for $_SESSION['login_error'] and immediate unsetting.
 */

// If invoked as a built-in CLI server, route mock endpoints and test runners
if (PHP_SAPI === 'cli-server') {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

    // Mock SIGUR Auth API
    if (strpos($uri, 'auth') !== false) {
        header('Content-Type: application/json; charset=utf-8');
        $sistInic = $_POST['sist_inic'] ?? '';
        if ($sistInic === 'FAIL_AUTH') {
            http_response_code(401);
            echo json_encode([
                'status' => 401,
                'success' => false,
                'message' => 'Credenciales del sistema invalidas',
                'data' => []
            ]);
        } else {
            echo json_encode([
                'status' => 200,
                'success' => true,
                'message' => 'Token generado correctamente',
                'data' => ['token' => 'mock_jwt_bearer_token_xyz']
            ]);
        }
        return;
    }

    // Mock SIGUR Login API
    if (strpos($uri, 'login.php') !== false) {
        header('Content-Type: application/json; charset=utf-8');
        $usuDoc = $_POST['usu_doc'] ?? '';
        if ($usuDoc === '78901234') $usuDoc = 'whitening_user';
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if ($authHeader !== 'Bearer mock_jwt_bearer_token_xyz') {
            http_response_code(401);
            echo json_encode([
                'status' => 498,
                'success' => false,
                'message' => 'El token no se ha proporcionado o es invalido',
                'data' => []
            ]);
            return;
        }

        if ($usuDoc === 'wrong_password') {
            http_response_code(401);
            echo json_encode([
                'status' => 401,
                'success' => false,
                'message' => 'La contraseña es incorrecta (1/5)',
                'data' => []
            ]);
        } elseif ($usuDoc === 'no_access') {
            http_response_code(403);
            echo json_encode([
                'status' => 403,
                'success' => false,
                'message' => 'El usuario no tiene acceso al sistema',
                'data' => []
            ]);
        } elseif ($usuDoc === 'empty_message_fail') {
            http_response_code(400);
            echo json_encode([
                'status' => 400,
                'success' => false,
                'data' => []
            ]);
        } elseif ($usuDoc === 'whitening_user') {
            echo json_encode([
                'status' => 200,
                'success' => true,
                'message' => 'Blanqueamiento de clave requerido',
                'data' => [
                    'url' => 'view/clave/blanqueamiento.php'
                ]
            ]);
        } elseif ($usuDoc === 'no_perm_user') {
            echo json_encode([
                'status' => 200,
                'success' => true,
                'message' => 'Autenticación exitosa',
                'data' => [
                    'usu_id' => 99999,
                    'pers_id' => 99999
                ]
            ]);
        } else {
            // Valid user
            echo json_encode([
                'status' => 200,
                'success' => true,
                'message' => 'Autenticación exitosa',
                'data' => [
                    'usu_id' => 3914,
                    'pers_id' => 3914
                ]
            ]);
        }
        return;
    }

    // Mock endpoint invoking Usuario::login()
    if ($uri === '/test-login') {
        session_set_save_handler(new class implements SessionHandlerInterface {
            public function open(string $path, string $name): bool { return true; }
            public function close(): bool { return true; }
            public function read(string $id): string { return ''; }
            public function write(string $id, string $data): bool { return true; }
            public function destroy(string $id): bool { return true; }
            public function gc(int $max_lifetime): int|false { return 0; }
        }, true);
        session_start();

        $scenario = $_GET['scenario'] ?? 'success';

        $_SESSION['captcha_text'] = 'CAPT99';
        $_SESSION['captcha_issued_at'] = time();

        $_POST = [
            'enviar' => 'si',
            'captchaInput' => 'CAPT99',
            'usu_dni' => $scenario === 'whitening_user' ? '78901234' : (in_array($scenario, ['valid_user', 'complete_user'], true) ? '16477575' : $scenario),
            'usu_pass' => 'TestPass123'
        ];

        // Isolated test mock of Conectar and DB
        if (!class_exists('Conectar')) {
            class MockStatement {
                private $sql;
                private $scenario;
                public $bindings = [];

                public function __construct(string $sql, string $scenario) {
                    $this->sql = $sql;
                    $this->scenario = $scenario;
                }

                public function bindValue($param, $value, $type = null): bool {
                    $this->bindings[$param] = [$value, $type];
                    return true;
                }

                public function execute(): bool {
                    return true;
                }

                public function fetch($mode = null) {
                    if (strpos($this->sql, 'sc_escalafon.tb_persona') !== false) {
                        if ($this->scenario === 'no_perm_user') {
                            return false;
                        }
                        return [
                            'pers_id' => 3914,
                            'pers_dni' => '16477575',
                            'pers_nombre' => 'FELIX',
                            'pers_apelpat' => 'RODRIGUEZ',
                            'pers_apelmat' => 'LOPEZ',
                            'pers_estado' => 'A',
                            'pers_emailm' => 'frodriguez@munichiclayo.gob.pe',
                            'pers_celu01' => '987654321',
                            'perf_id' => 8,
                            'perf_nombre' => 'DIGITADOR',
                            'depe_id' => 92
                        ];
                    }
                    if (strpos($this->sql, 'sc_seguridad.tb_historial_sesion') !== false) {
                        return ['hise_id' => 61690];
                    }
                    if (strpos($this->sql, 'sc_giros.td_areausu WHERE pers_id = ? AND est = 1') !== false) {
                        return false;
                    }
                    if (strpos($this->sql, 'sc_giros.td_areausu WHERE pers_id = ? AND depe_id = ?') !== false) {
                        return false;
                    }
                    if (strpos($this->sql, 'tb_dependencia') !== false) {
                        return ['depe_id' => 92, 'depe_estado' => 'A'];
                    }
                    if (strpos($this->sql, 'INSERT INTO sc_giros.td_areausu') !== false) {
                        return ['areausu_id' => 5001];
                    }
                    return false;
                }

                public function fetchAll($mode = null): array {
                    $item = $this->fetch($mode);
                    return $item ? [$item] : [];
                }
            }

            class MockPdo {
                private $scenario;
                public function __construct(string $scenario) { $this->scenario = $scenario; }
                public function prepare($sql) { return new MockStatement($sql, $this->scenario); }
                public function query($sql) { return new MockStatement($sql, $this->scenario); }
            }

            class Conectar {
                public static $scenario = 'success';
                public function conexion() { return new MockPdo(self::$scenario); }
                public function set_names() { return true; }
                public static function ruta(): string { return 'http://10.10.10.16/SIGODT/'; }
                public static function getCookiePath(): string { return '/SIGODT/'; }
                public static function getClientIp(): string { return '192.168.1.50'; }
                public static function getEnv(string $key, $default = null) {
                    return $_ENV[$key] ?? $default;
                }
            }
        }

        Conectar::$scenario = $scenario;

        // Configure test runtime environment
        $_ENV['SIGUR_URL'] = 'http://10.10.10.16/sigur/';
        $_ENV['SIGUR_AUTH_URL'] = 'http://' . $_SERVER['HTTP_HOST'] . '/sigur/auth/';
        $_ENV['SIGUR_LOGIN_URL'] = 'http://' . $_SERVER['HTTP_HOST'] . '/sigur/ws/login.php';
        $_ENV['JWT_SECRET'] = 'Mun12345_GLE';
        $_ENV['JWE_SECRET'] = 'Mun12345_GLE';
        $_ENV['DOMAIN'] = '10.10.10.16';
        $_ENV['APP_NAME'] = 'SIGODT';
        $_ENV['BASE_URL'] = 'http://' . $_SERVER['HTTP_HOST'] . '/';

        if ($scenario === 'auth_fail') {
            $_ENV['SIST_INIC'] = 'FAIL_AUTH';
        }

        // Register shutdown function to output final session snapshot in response body
        register_shutdown_function(function () use ($scenario) {
            if ($scenario === 'complete_user' && isset($_SESSION['2fa_pending_SIGODT'])) {
                // Public factor verification is tested separately in siindi_login_integration_test.php.
                $p = $_SESSION['2fa_pending_SIGODT'];
                $_SESSION['2fa_verified_SIGODT'] = ['kind'=>'siindi_completion', 'sigodt_nonce'=>$p['sigodt_nonce'], 'usu_id'=>$p['usu_id'],
                    'usu_doc'=>$p['usu_doc'], 'challenge_id'=>$p['challenge_id'], 'expires_at'=>$p['expires_at']];
                if ((new Usuario())->finalizarLogin2FA()) {
                    header('Location: ' . Conectar::ruta() . 'view/inicio/');
                }
            }
            echo json_encode($_SESSION);
        });

        require_once __DIR__ . '/../models/Usuario.php';
        (new Usuario())->login();
        return;
    }

    return false;
}

// -----------------------------------------------------------------------------
// Test Execution (CLI Mode)
// -----------------------------------------------------------------------------

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

function check(bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException("Assertion failed: " . $message);
    }
}

echo "=== Running SIGUR Unified Login Test Suite ===\n";

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/jwe.php';
require_once __DIR__ . '/../config/jwt.php';

// Allocate dynamic local port and start mock CLI server
$socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
check($socket !== false, "Unable to bind temporary local test socket: $error");
$address = stream_socket_get_name($socket, false);
fclose($socket);

$serverProcess = proc_open(
    [PHP_BINARY, '-S', $address, __FILE__],
    [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
    $pipes,
    null,
    array_merge($_ENV, ['PHP_CLI_SERVER_WORKERS' => '4'])
);
check(is_resource($serverProcess), "Failed to start local mock CLI server");

try {
    // One finite startup allowance; a failed request is never polled/retried.
    usleep(200000);

    $executeLoginTest = function (string $scenario) use ($address): array {
        $ch = curl_init('http://' . $address . '/test-login?scenario=' . urlencode($scenario));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        $raw = curl_exec($ch);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $headerStr = substr($raw, 0, $headerSize);
        $bodyStr = substr($raw, $headerSize);
        $session = json_decode($bodyStr, true) ?? [];

        preg_match('/^Location:\s*(.*?)$/mi', $headerStr, $locMatches);
        $location = isset($locMatches[1]) ? trim($locMatches[1]) : null;

        preg_match_all('/^Set-Cookie:\s*blanqueamiento=([^;]+);/mi', $headerStr, $cookieMatches);
        $blanqueamientoCookie = !empty($cookieMatches[1]) ? urldecode(trim(end($cookieMatches[1]))) : null;

        return [
            'httpCode' => $httpCode,
            'location' => $location,
            'blanqueamientoCookie' => $blanqueamientoCookie,
            'session' => $session,
            'headers' => $headerStr
        ];
    };

    // -------------------------------------------------------------------------
    // 1. Dynamic Error: Password incorrect ("La contraseña es incorrecta (1/5)")
    // -------------------------------------------------------------------------
    echo "[1/8] Testing dynamic error: wrong password...\n";
    $res1 = $executeLoginTest('wrong_password');
    check($res1['httpCode'] === 302, "Expected HTTP 302 redirect");
    check(strpos($res1['location'], 'index.php') !== false, "Expected redirect to index.php");
    check(
        isset($res1['session']['login_error']) && $res1['session']['login_error'] === 'La contraseña es incorrecta (1/5)',
        "Expected login_error 'La contraseña es incorrecta (1/5)', got: " . var_export($res1['session']['login_error'] ?? null, true)
    );
    check($res1['blanqueamientoCookie'] === null, "blanqueamiento cookie must not be set on failure");
    echo "  -> Dynamic password error verified.\n";

    // -------------------------------------------------------------------------
    // 2. Dynamic Error: No access to system ("El usuario no tiene acceso al sistema")
    // -------------------------------------------------------------------------
    echo "[2/8] Testing dynamic error: no system access...\n";
    $res2 = $executeLoginTest('no_access');
    check($res2['httpCode'] === 302, "Expected HTTP 302 redirect");
    check(strpos($res2['location'], 'index.php') !== false, "Expected redirect to index.php");
    check(
        isset($res2['session']['login_error']) && $res2['session']['login_error'] === 'El usuario no tiene acceso al sistema',
        "Expected login_error 'El usuario no tiene acceso al sistema', got: " . var_export($res2['session']['login_error'] ?? null, true)
    );
    echo "  -> Dynamic access denied error verified.\n";

    // -------------------------------------------------------------------------
    // 3. Fallback Error: Missing error message -> "Datos incorrectos"
    // -------------------------------------------------------------------------
    echo "[3/8] Testing fallback error message...\n";
    $res3 = $executeLoginTest('empty_message_fail');
    check($res3['httpCode'] === 302, "Expected HTTP 302 redirect");
    check(strpos($res3['location'], 'index.php') !== false, "Expected redirect to index.php");
    check(
        isset($res3['session']['login_error']) && $res3['session']['login_error'] === 'Datos incorrectos',
        "Expected fallback 'Datos incorrectos', got: " . var_export($res3['session']['login_error'] ?? null, true)
    );
    echo "  -> Fallback error message verified.\n";

    // -------------------------------------------------------------------------
    // 4. System Auth Failure
    // -------------------------------------------------------------------------
    echo "[4/8] Testing system auth endpoint failure...\n";
    $res4 = $executeLoginTest('auth_fail');
    check($res4['httpCode'] === 302, "Expected HTTP 302 redirect");
    check(strpos($res4['location'], 'index.php') !== false, "Expected redirect to index.php");
    check(
        isset($res4['session']['login_error']) && strpos($res4['session']['login_error'], 'invalidas') !== false,
        "Expected auth error in session, got: " . var_export($res4['session']['login_error'] ?? null, true)
    );
    echo "  -> System auth failure handling verified.\n";

    // -------------------------------------------------------------------------
    // 5. Password Whitening (Blanqueamiento) Flow Preservation
    // -------------------------------------------------------------------------
    echo "[5/8] Testing password whitening (blanqueamiento) flow...\n";
    $res5 = $executeLoginTest('whitening_user');
    check($res5['httpCode'] === 302, "Expected HTTP 302 redirect");
    check(
        $res5['location'] === 'http://10.10.10.16/SIGODT/seguridad/primer_acceso/views/',
        "Expected redirect to blanqueamiento.php, got: " . var_export($res5['location'], true)
    );
    check(!empty($res5['blanqueamientoCookie']), "blanqueamiento cookie must be set");

    // Decrypt JWE cookie
    $_ENV['JWT_SECRET'] = 'Mun12345_GLE';
    $_ENV['JWE_SECRET'] = 'Mun12345_GLE';
    $jwt = new JwtHandler();
    $decodedCookie = $jwt->decrypt($res5['blanqueamientoCookie']);
    check(is_object($decodedCookie) && isset($decodedCookie->data), "Failed to decrypt JWE cookie");
    check($decodedCookie->data->usu_doc === '78901234', "Cookie usu_doc mismatch");
    check(($res5['session']['sigodt_password_context']['stage'] ?? '') === 'first_access', 'Authoritative login creates temporary first-access context');
    check(str_contains($res5['headers'], 'path=/SIGODT/') && !str_contains($res5['headers'], 'path=/sigur/'), 'Module-only cookies, no central cookie mutation');
    check($decodedCookie->data->blanqueamiento === 2, "Cookie blanqueamiento mismatch");
    echo "  -> Password whitening flow verified.\n";

    // -------------------------------------------------------------------------
    // 6. Permission Check: No active permission for System 8
    // -------------------------------------------------------------------------
    echo "[6/8] Testing user without active permission for system 8...\n";
    $res6 = $executeLoginTest('no_perm_user');
    check($res6['httpCode'] === 302, "Expected HTTP 302 redirect");
    check(strpos($res6['location'], 'index.php') !== false, "Expected redirect to index.php");
    check(
        isset($res6['session']['login_error']) && $res6['session']['login_error'] === 'El usuario no tiene permisos activos para el sistema SIGODT',
        "Expected permission error, got: " . var_export($res6['session']['login_error'] ?? null, true)
    );
    echo "  -> System 8 permission check verified.\n";

    // -------------------------------------------------------------------------
    // 7. Successful Login Flow: Profile, Session Variables, and Redirection
    // -------------------------------------------------------------------------
    echo "[7/8] Testing successful login flow and session population...\n";
    $pending7 = $executeLoginTest('valid_user');
    check($pending7['httpCode'] === 302, "Expected HTTP 302 redirect to second factor");
    check($pending7['location'] === 'http://10.10.10.16/SIGODT/seguridad/2fa/views/selector_metodo.php', 'Expected copied selector');
    check(isset($pending7['session']['2fa_pending_SIGODT']) && !isset($pending7['session']['usua_id_SIGODT']), 'No completed session before factor');
    $res7 = $executeLoginTest('complete_user');
    check($res7['httpCode'] === 302, "Expected HTTP 302 redirect");
    check(
        $res7['location'] === 'http://10.10.10.16/SIGODT/view/inicio/',
        "Expected redirect to view/inicio/, got: " . var_export($res7['location'], true)
    );
    $session = $res7['session'];
    check($session['usua_id_SIGODT'] === 3914, "usua_id_SIGODT mismatch");
    check($session['usua_dni_SIGODT'] === '16477575', "usua_dni_SIGODT mismatch");
    check($session['rol_id_SIGODT'] === 8, "rol_id_SIGODT mismatch");
    check($session['usua_estado_SIGODT'] === 'A', "usua_estado_SIGODT mismatch");
    check($session['pers_nombre_SIGODT'] === 'FELIX', "pers_nombre_SIGODT mismatch");
    check($session['pers_apellidos_SIGODT'] === 'RODRIGUEZ LOPEZ', "pers_apellidos_SIGODT mismatch");
    check($session['nombre_completo_SIGODT'] === 'FELIX RODRIGUEZ LOPEZ', "nombre_completo_SIGODT mismatch");
    check($session['rol_nombre_SIGODT'] === 'DIGITADOR', "rol_nombre_SIGODT mismatch");
    check($session['pers_emailm_SIGODT'] === 'frodriguez@munichiclayo.gob.pe', "pers_emailm_SIGODT mismatch");
    check($session['pers_celu01_SIGODT'] === '987654321', "pers_celu01_SIGODT mismatch");
    check($session['usu_depe_id_SIGODT'] === 92, "usu_depe_id_SIGODT mismatch");
    check($session['historial_login_SIGODT'] === 61690, "historial_login_SIGODT mismatch");
    echo "  -> Successful login flow and session population verified.\n";

    // -------------------------------------------------------------------------
    // 8. index.php Error Alert Rendering & Unsetting
    // -------------------------------------------------------------------------
    echo "[8/8] Testing index.php dynamic error display and unsetting...\n";
    $testIndexRender = function (array $sessionData, array $getData): array {
        $_SESSION = $sessionData;
        $_GET = $getData;
        $_POST = [];
        ob_start();
        include __DIR__ . '/../index.php';
        $output = ob_get_clean();
        return ['output' => $output, 'session' => $_SESSION];
    };

    // Subcase 8.1: login_error display
    $render1 = $testIndexRender(['login_error' => 'La contraseña es incorrecta (1/5)'], []);
    check(
        strpos($render1['output'], 'La contraseña es incorrecta (1/5)') !== false,
        "Rendered output missing login_error message"
    );
    check(!isset($render1['session']['login_error']), "login_error must be unset after display");

    // Subcase 8.2: legacy ?m=11 display
    $render2 = $testIndexRender([], ['m' => '11']);
    check(
        strpos($render2['output'], 'CAPTCHA') !== false,
        "Rendered output missing legacy ?m=11 captcha message"
    );

    echo "  -> index.php dynamic error display and unsetting verified.\n";

} finally {
    proc_terminate($serverProcess);
    foreach ($pipes as $pipe) {
        if (is_resource($pipe)) {
            fclose($pipe);
        }
    }
    proc_close($serverProcess);
}

echo "\nAll SIGUR Unified Login tests PASSED (8/8) successfully!\n";
