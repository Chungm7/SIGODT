<?php
/**
 * Test Suite: SIGUR Password Whitening (Blanqueamiento de Clave) Integration
 *
 * Validates:
 * 1. JWE encryption and decryption symmetry (A256KW + A256CBC-HS512).
 * 2. Token structure compliance with RFC 7516 (5 parts, alg: A256KW, enc: A256CBC-HS512).
 * 3. Payload preservation (usu_doc, usu_tipo, blanqueamiento, system.APP_NAME).
 * 4. Security cookie attributes (HttpOnly, SameSite=Lax, path=/sigur/, expiry).
 * 5. End-to-end detection of "$detalle === 'Blanqueamiento de clave'" in Usuario::login()
 *    with cookie issuance and redirection to sigur/view/clave/blanqueamiento.php.
 */

// If invoked as a built-in CLI server, route mock endpoints
if (PHP_SAPI === 'cli-server') {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

    // Mock SIGUR Auth API
    if (strpos($uri, 'auth') !== false) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => 200,
            'success' => true,
            'message' => 'Token generado correctamente',
            'data' => ['token' => 'mock_test_token_123']
        ]);
        return;
    }

    // Mock SIGUR Login API
    if (strpos($uri, 'login.php') !== false) {
        header('Content-Type: application/json; charset=utf-8');
        $dni = $_POST['usu_doc'] ?? '';
        if ($dni === 'fail_user') {
            http_response_code(401);
            echo json_encode([
                'status' => 401,
                'success' => false,
                'message' => 'Datos incorrectos',
                'data' => []
            ]);
        } else {
            echo json_encode([
                'status' => 200,
                'success' => true,
                'message' => 'Blanqueamiento de clave',
                'data' => [
                    'url' => 'view/clave/blanqueamiento.php'
                ]
            ]);
        }
        return;
    }

    // Endpoint invoking Usuario::login()
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

        $_SESSION['captcha_text'] = 'CAPT23';
        $_SESSION['captcha_issued_at'] = time();

        $dni = $_GET['dni'] ?? '78901234';
        $_POST = [
            'enviar' => '1',
            'captchaInput' => 'CAPT23',
            'usu_dni' => $dni,
            'usu_pass' => 'tempPass123'
        ];

        // Isolated test mock of Conectar
        if (!class_exists('Conectar')) {
            class Conectar {
                public function conexion() {
                    return new class {
                        public function query($q) { return true; }
                    };
                }
                public function set_names() { return true; }
                public static function ruta() { return 'http://10.10.10.16/SIGODT/'; }
                public static function getClientIp(): string { return '127.0.0.1'; }
                public static function getEnv(string $key, $default = null) {
                    return $_ENV[$key] ?? $default;
                }
            }
        }

        // Set runtime env variables for testing
        $_ENV['SIGUR_URL'] = 'http://10.10.10.16/sigur/';
        $_ENV['SIGUR_AUTH_URL'] = 'http://' . $_SERVER['HTTP_HOST'] . '/sigur/auth/';
        $_ENV['SIGUR_LOGIN_URL'] = 'http://' . $_SERVER['HTTP_HOST'] . '/sigur/ws/login.php';
        $_ENV['JWT_SECRET'] = 'Mun12345_GLE';
        $_ENV['JWE_SECRET'] = 'Mun12345_GLE';
        $_ENV['DOMAIN'] = '10.10.10.16';
        $_ENV['APP_NAME'] = 'SIGODT';
        $_ENV['BASE_URL'] = 'http://10.10.10.16/';

        require_once __DIR__ . '/../models/Usuario.php';
        (new Usuario())->login();
        return;
    }

    return false;
}

// -----------------------------------------------------------------------------
// Test Execution (CLI Mode)
// -----------------------------------------------------------------------------

function check(bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException("Assertion failed: " . $message);
    }
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/jwe.php';
require_once __DIR__ . '/../config/jwt.php';

use Jose\Component\Core\Util\Base64UrlSafe;

echo "=== Running SIGUR Blanqueamiento Test Suite ===\n";

// Ensure environment keys are set for CLI test run
$_ENV['JWT_SECRET'] = 'Mun12345_GLE';
$_ENV['JWE_SECRET'] = 'Mun12345_GLE';
$_ENV['SIGUR_URL'] = 'http://10.10.10.16/sigur/';
$_ENV['BASE_URL'] = 'http://10.10.10.16/';
$_ENV['DOMAIN'] = '10.10.10.16';
$_ENV['APP_NAME'] = 'SIGODT';

// -----------------------------------------------------------------------------
// 1. JWE Encryption / Decryption Symmetry
// -----------------------------------------------------------------------------
echo "[1/5] Testing JWE encryption and decryption symmetry...\n";

$rawPayload = json_encode(['test_data' => 'Integration test', 'timestamp' => time()]);
$encryptedJwe = encryptJWE($rawPayload);
check(is_string($encryptedJwe) && strlen($encryptedJwe) > 50, "JWE encryption returned an empty or invalid token");

$decryptedJwe = decryptJWE($encryptedJwe);
check($decryptedJwe === $rawPayload, "JWE decryption payload mismatch");

// Tampered ciphertext should fail gracefully
$tamperedToken = substr($encryptedJwe, 0, -10) . 'tampered00';
$tamperedDecrypted = decryptJWE($tamperedToken);
check($tamperedDecrypted === false, "Tampered JWE token should return false");

echo "  -> JWE symmetry and tamper-resistance verified.\n";

// -----------------------------------------------------------------------------
// 2. Token Structure Compliance (RFC 7516 Compact JWE)
// -----------------------------------------------------------------------------
echo "[2/5] Testing token structure (alg: A256KW, enc: A256CBC-HS512)...\n";

$parts = explode('.', $encryptedJwe);
check(count($parts) === 5, "JWE compact token must have exactly 5 parts (got " . count($parts) . ")");

$protectedHeader = json_decode(Base64UrlSafe::decode($parts[0]), true);
check(is_array($protectedHeader), "Failed to parse JWE protected header");
check(isset($protectedHeader['alg']) && $protectedHeader['alg'] === 'A256KW', "Header 'alg' must be 'A256KW' (got " . ($protectedHeader['alg'] ?? 'none') . ")");
check(isset($protectedHeader['enc']) && $protectedHeader['enc'] === 'A256CBC-HS512', "Header 'enc' must be 'A256CBC-HS512' (got " . ($protectedHeader['enc'] ?? 'none') . ")");

echo "  -> Token structure compliance verified.\n";

// -----------------------------------------------------------------------------
// 3. JwtHandler Payload Preservation and Symmetry
// -----------------------------------------------------------------------------
echo "[3/5] Testing JwtHandler payload preservation...\n";

$jwt = new JwtHandler();
$dni = '78901234';
$param = [
    "usu_doc" => $dni,
    "usu_tipo" => "P",
    "success" => true,
    "blanqueamiento" => 2
];

$token = $jwt->encrypt($param, 'blanqueamiento', 1900);
check(is_string($token) && strlen($token) > 0, "JwtHandler encrypt produced empty token");

$decoded = $jwt->decrypt($token);
check(is_object($decoded), "JwtHandler decrypt should return an object");
check(isset($decoded->data), "Decoded token missing 'data' attribute");
check($decoded->data->usu_doc === $dni, "Payload 'usu_doc' mismatch: expected $dni, got " . ($decoded->data->usu_doc ?? 'null'));
check($decoded->data->usu_tipo === "P", "Payload 'usu_tipo' mismatch: expected 'P', got " . ($decoded->data->usu_tipo ?? 'null'));
check($decoded->data->success === true, "Payload 'success' mismatch: expected true, got " . var_export($decoded->data->success ?? null, true));
check($decoded->data->blanqueamiento === 2, "Payload 'blanqueamiento' mismatch: expected 2, got " . ($decoded->data->blanqueamiento ?? 'null'));
check($decoded->type === 'blanqueamiento', "Token 'type' mismatch: expected 'blanqueamiento', got " . ($decoded->type ?? 'null'));
check(isset($decoded->system->APP_NAME) && $decoded->system->APP_NAME === 'SIGODT', "System APP_NAME mismatch: expected SIGODT");
check(isset($decoded->exp) && isset($decoded->iat), "Token missing iat or exp timestamps");
check(($decoded->exp - $decoded->iat) === 1900, "Token expiry delta should be 1900 seconds");

// Verify expired token returns status 498
$expiredToken = $jwt->encrypt($param, 'blanqueamiento', -50);
$expiredResult = $jwt->decrypt($expiredToken);
check(is_object($expiredResult) && ($expiredResult->status ?? 0) === 498, "Expired token must yield status 498");

echo "  -> Payload preservation and security checks verified.\n";

// -----------------------------------------------------------------------------
// 4. Cookie Security Options
// -----------------------------------------------------------------------------
echo "[4/5] Testing cookie security options (SameSite=Lax, HttpOnly, path=/sigur/)...\n";

$cookieOptions = $jwt->getCookieOptions(1900, "/sigur/");
check($cookieOptions['path'] === '/sigur/', "Cookie path should be '/sigur/'");
check($cookieOptions['httponly'] === true, "Cookie httponly must be true");
check($cookieOptions['samesite'] === 'Lax', "Cookie samesite must be 'Lax'");
check($cookieOptions['domain'] === '10.10.10.16', "Cookie domain should be '10.10.10.16'");
check($cookieOptions['expires'] > time() + 1800, "Cookie expiry must be in the future (~1900s)");

echo "  -> Cookie security options verified.\n";

// -----------------------------------------------------------------------------
// 5. Mock Response Detection and Redirection (End-to-End)
// -----------------------------------------------------------------------------
echo "[5/5] Testing Usuario::login() mock response detection & redirection...\n";

// Allocate dynamic local port
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
    // Wait for server ready
    $ready = false;
    for ($i = 0; $i < 50; $i++) {
        $conn = @stream_socket_client('tcp://' . $address, $errno, $error, 0.1);
        if ($conn) {
            fclose($conn);
            $ready = true;
            break;
        }
        usleep(20000);
    }
    check($ready, "Local mock server did not become ready in time");

    // Case A: Successful blanqueamiento detection
    $ch = curl_init('http://' . $address . '/test-login?dni=78901234');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    check($httpCode === 302, "Expected HTTP 302 redirect for blanqueamiento, got $httpCode");

    // Verify Location header
    preg_match('/^Location:\s*(.*?)$/mi', $response, $locationMatches);
    check(!empty($locationMatches[1]), "Redirect Location header missing in response");
    $redirectUrl = trim($locationMatches[1]);
    check(
        $redirectUrl === 'http://10.10.10.16/sigur/view/clave/blanqueamiento.php',
        "Redirect target mismatch: expected 'http://10.10.10.16/sigur/view/clave/blanqueamiento.php', got '$redirectUrl'"
    );

    // Verify Set-Cookie header for blanqueamiento
    preg_match('/^Set-Cookie:\s*blanqueamiento=([^;]+);/mi', $response, $cookieMatches);
    check(!empty($cookieMatches[1]), "Set-Cookie 'blanqueamiento' missing in redirect response");
    $cookieToken = urldecode(trim($cookieMatches[1]));

    // Decrypt token from cookie and verify contents
    $cookieDecoded = $jwt->decrypt($cookieToken);
    check(is_object($cookieDecoded) && isset($cookieDecoded->data), "Failed to decrypt cookie token or missing data payload");
    check($cookieDecoded->data->usu_doc === '78901234', "Cookie token usu_doc mismatch: got " . ($cookieDecoded->data->usu_doc ?? 'null'));
    check($cookieDecoded->data->usu_tipo === 'P', "Cookie token usu_tipo mismatch");
    check($cookieDecoded->data->blanqueamiento === 2, "Cookie token blanqueamiento mismatch");
    check($cookieDecoded->data->success === true, "Cookie token success flag mismatch");
    check($cookieDecoded->system->APP_NAME === 'SIGODT', "Cookie token system APP_NAME mismatch");

    // Case B: Non-blanqueamiento response ('Datos incorrectos')
    $chFail = curl_init('http://' . $address . '/test-login?dni=fail_user');
    curl_setopt($chFail, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($chFail, CURLOPT_HEADER, true);
    curl_setopt($chFail, CURLOPT_FOLLOWLOCATION, false);
    $responseFail = curl_exec($chFail);
    $httpCodeFail = curl_getinfo($chFail, CURLINFO_HTTP_CODE);
    curl_close($chFail);

    check($httpCodeFail === 302, "Expected HTTP 302 redirect for error, got $httpCodeFail");
    preg_match('/^Location:\s*(.*?)$/mi', $responseFail, $locationFailMatches);
    check(!empty($locationFailMatches[1]), "Redirect Location header missing in error response");
    check(strpos($locationFailMatches[1], 'index.php') !== false, "Expected redirect to index.php on invalid data");
    check(stripos($responseFail, 'Set-Cookie: blanqueamiento=') === false, "Cookie 'blanqueamiento' must not be set on failed login");

    echo "  -> End-to-end mock response detection & redirection verified.\n";
} finally {
    proc_terminate($serverProcess);
    foreach ($pipes as $pipe) {
        if (is_resource($pipe)) {
            fclose($pipe);
        }
    }
    proc_close($serverProcess);
}

echo "\nAll SIGUR Blanqueamiento tests PASSED successfully!\n";
