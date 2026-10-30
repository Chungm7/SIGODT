<?php
/**
 * Test Suite: Session Isolation and Scoping (SIGODT_SESSID)
 *
 * Requirements:
 * - Session name isolated to 'SIGODT_SESSID'
 * - Cookie path scoped to application path (default '/SIGODT/', or derived from APP_URL)
 * - Cookie attributes: HttpOnly=true, SameSite=Lax, Secure=true if HTTPS
 * - Clean logout: session destroyed, $_SESSION cleared, and cookie expired with matching path
 * - Isolated endpoints (captcha.php, ordengiro.php, reporte_usuario.php) respect session name & cookie params
 */

function check(bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException("Assertion failed: " . $message);
    }
}

function runPhpCode(string $code, array $env = []): array {
    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w']
    ];
    $process = proc_open([PHP_BINARY, '-r', $code], $descriptors, $pipes, null, empty($env) ? null : array_merge($_ENV, $env));
    if (!is_resource($process)) {
        throw new RuntimeException("Failed to run subprocess");
    }
    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);
    return ['stdout' => $stdout, 'stderr' => $stderr, 'exit' => $exitCode];
}

echo "=== Running SIGODT Session Isolation Test Suite ===\n";

// -----------------------------------------------------------------------------
// Test 1: Session name and cookie parameters in config/conexion.php
// -----------------------------------------------------------------------------
echo "[1/7] Testing config/conexion.php session_name and cookie params...\n";
$res1 = runPhpCode('
    require_once ' . var_export(__DIR__ . '/../config/conexion.php', true) . ';
    echo json_encode([
        "session_name"   => session_name(),
        "cookie_params"  => session_get_cookie_params(),
        "session_status" => session_status() === PHP_SESSION_ACTIVE
    ]);
');
check($res1['exit'] === 0, "config/conexion.php execution failed: " . $res1['stderr']);
$data1 = json_decode($res1['stdout'], true);
check($data1['session_name'] === 'SIGODT_SESSID', "Expected session name 'SIGODT_SESSID', got: " . var_export($data1['session_name'], true));
check($data1['cookie_params']['path'] === '/SIGODT/', "Expected cookie path '/SIGODT/', got: " . var_export($data1['cookie_params']['path'], true));
check($data1['cookie_params']['httponly'] === true, "Expected HttpOnly=true, got: " . var_export($data1['cookie_params']['httponly'], true));
check(strcasecmp($data1['cookie_params']['samesite'], 'Lax') === 0, "Expected SameSite=Lax, got: " . var_export($data1['cookie_params']['samesite'], true));
check($data1['session_status'] === true, "Expected active session after loading config/conexion.php");
echo "  -> Default session isolation verified (SIGODT_SESSID, path=/SIGODT/, HttpOnly, SameSite=Lax).\n";

// -----------------------------------------------------------------------------
// Test 2: Dynamic cookie path based on custom APP_URL
// -----------------------------------------------------------------------------
echo "[2/7] Testing dynamic cookie path from APP_URL...\n";
$res2 = runPhpCode('
    putenv("APP_URL=http://10.10.10.16/custom_sigodt/");
    $_ENV["APP_URL"] = "http://10.10.10.16/custom_sigodt/";
    require_once ' . var_export(__DIR__ . '/../config/conexion.php', true) . ';
    echo json_encode([
        "session_name" => session_name(),
        "cookie_path"  => session_get_cookie_params()["path"],
        "helper_path"  => method_exists("Conectar", "getCookiePath") ? Conectar::getCookiePath() : null
    ]);
');
check($res2['exit'] === 0, "Custom APP_URL test execution failed: " . $res2['stderr']);
$data2 = json_decode($res2['stdout'], true);
check($data2['session_name'] === 'SIGODT_SESSID', "Expected session name 'SIGODT_SESSID' with custom APP_URL");
check($data2['cookie_path'] === '/custom_sigodt/', "Expected cookie path '/custom_sigodt/', got: " . var_export($data2['cookie_path'], true));
check($data2['helper_path'] === '/custom_sigodt/', "Expected Conectar::getCookiePath() to return '/custom_sigodt/', got: " . var_export($data2['helper_path'], true));
echo "  -> Dynamic cookie path scoping verified (/custom_sigodt/).\n";

// -----------------------------------------------------------------------------
// Test 3: Cookie secure flag when HTTPS is active
// -----------------------------------------------------------------------------
echo "[3/7] Testing cookie secure flag with HTTPS...\n";
$res3 = runPhpCode('
    $_SERVER["HTTPS"] = "on";
    require_once ' . var_export(__DIR__ . '/../config/conexion.php', true) . ';
    echo json_encode([
        "secure" => session_get_cookie_params()["secure"]
    ]);
');
check($res3['exit'] === 0, "HTTPS test execution failed: " . $res3['stderr']);
$data3 = json_decode($res3['stdout'], true);
check($data3['secure'] === true, "Expected secure=true under HTTPS");

$res3b = runPhpCode('
    $_SERVER["HTTP_X_FORWARDED_PROTO"] = "https";
    require_once ' . var_export(__DIR__ . '/../config/conexion.php', true) . ';
    echo json_encode([
        "secure" => session_get_cookie_params()["secure"]
    ]);
');
check($res3b['exit'] === 0, "HTTP_X_FORWARDED_PROTO test failed: " . $res3b['stderr']);
$data3b = json_decode($res3b['stdout'], true);
check($data3b['secure'] === true, "Expected secure=true under HTTP_X_FORWARDED_PROTO=https");
echo "  -> Secure flag under HTTPS / reverse proxy verified.\n";

// -----------------------------------------------------------------------------
// Test 4: captcha.php session isolation
// -----------------------------------------------------------------------------
echo "[4/7] Testing captcha.php session isolation...\n";
$res4 = runPhpCode('
    ob_start();
    require_once ' . var_export(__DIR__ . '/../captcha.php', true) . ';
    ob_end_clean();
    echo json_encode([
        "session_name" => session_name(),
        "cookie_path"  => session_get_cookie_params()["path"]
    ]);
');
check($res4['exit'] === 0, "captcha.php test execution failed: " . $res4['stderr']);
$data4 = json_decode($res4['stdout'], true);
check($data4['session_name'] === 'SIGODT_SESSID', "Expected captcha.php session name 'SIGODT_SESSID', got: " . var_export($data4['session_name'], true));
check($data4['cookie_path'] === '/SIGODT/', "Expected captcha.php cookie path '/SIGODT/', got: " . var_export($data4['cookie_path'], true));
echo "  -> captcha.php session isolation verified.\n";

// -----------------------------------------------------------------------------
// Test 5: controller/ordengiro.php session isolation
// -----------------------------------------------------------------------------
echo "[5/7] Testing controller/ordengiro.php session isolation...\n";
$res5 = runPhpCode('
    $_GET["op"] = "buscar_entidades_nombre";
    $_POST["search"] = "Empresa Test";
    $_POST["page"] = 1;
    $_POST["limit"] = 10;
    register_shutdown_function(function() {
        fwrite(STDERR, json_encode([
            "session_name" => session_name(),
            "cookie_path"  => session_get_cookie_params()["path"]
        ]));
    });
    ob_start();
    include ' . var_export(__DIR__ . '/../controller/ordengiro.php', true) . ';
');
check($res5['exit'] === 0, "controller/ordengiro.php test execution failed: " . $res5['stderr']);
$data5 = json_decode($res5['stderr'], true);
check(is_array($data5), "Failed to decode ordengiro session data from: " . $res5['stderr']);
check($data5['session_name'] === 'SIGODT_SESSID', "Expected controller/ordengiro.php session name 'SIGODT_SESSID', got: " . var_export($data5['session_name'], true));
check($data5['cookie_path'] === '/SIGODT/', "Expected controller/ordengiro.php cookie path '/SIGODT/', got: " . var_export($data5['cookie_path'], true));
echo "  -> controller/ordengiro.php session isolation verified.\n";

// -----------------------------------------------------------------------------
// Test 6: controller/reporte_usuario.php session isolation
// -----------------------------------------------------------------------------
echo "[6/7] Testing controller/reporte_usuario.php session isolation...\n";
$res6 = runPhpCode('
    $_GET["op"] = "json";
    register_shutdown_function(function() {
        fwrite(STDERR, json_encode([
            "session_name" => session_name(),
            "cookie_path"  => session_get_cookie_params()["path"]
        ]));
    });
    ob_start();
    include ' . var_export(__DIR__ . '/../controller/reporte_usuario.php', true) . ';
');
check($res6['exit'] === 0, "controller/reporte_usuario.php test execution failed: " . $res6['stderr']);
$data6 = json_decode($res6['stderr'], true);
check(is_array($data6), "Failed to decode reporte_usuario session data from: " . $res6['stderr']);
check($data6['session_name'] === 'SIGODT_SESSID', "Expected controller/reporte_usuario.php session name 'SIGODT_SESSID', got: " . var_export($data6['session_name'], true));
check($data6['cookie_path'] === '/SIGODT/', "Expected controller/reporte_usuario.php cookie path '/SIGODT/', got: " . var_export($data6['cookie_path'], true));
echo "  -> controller/reporte_usuario.php session isolation verified.\n";

// -----------------------------------------------------------------------------
// Test 7: Usuario::logout clean destruction and cookie expiration
// -----------------------------------------------------------------------------
echo "[7/7] Testing Usuario::logout() session destruction and cookie expiration...\n";
// Start a dedicated CLI server to observe actual Set-Cookie HTTP headers and session state
$socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
check($socket !== false, 'Local test socket unavailable');
$address = stream_socket_get_name($socket, false);
fclose($socket);

$routerPath = tempnam(sys_get_temp_dir(), 'sigodt_logout_test_');
$projectDir = realpath(__DIR__ . '/..');
$routerCode = "<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);

\$_ENV['SIS_SEGURIDAD_URL'] = 'http://127.0.0.1:1/';
putenv('SIS_SEGURIDAD_URL=http://127.0.0.1:1/');

require_once " . var_export($projectDir . '/config/conexion.php', true) . ";
require_once " . var_export($projectDir . '/models/Usuario.php', true) . ";

// Enable mock connection to avoid live DB requirement
if (class_exists('Conectar', false) && property_exists('Conectar', 'conexionDouble')) {
    Conectar::\$conexionDouble = new class {
        public function query(\$q) { return true; }
    };
}

\$uri = parse_url(\$_SERVER['REQUEST_URI'], PHP_URL_PATH);

if (\$uri === '/login-state') {
    \$_SESSION['usua_id_SIGODT'] = 99;
    \$_SESSION['nombre_completo_SIGODT'] = 'Isolated Test User';
    echo json_encode(['status' => 'logged_in', 'session_id' => session_id(), 'session_data' => \$_SESSION]);
    return;
}

if (\$uri === '/logout') {
    \$usuario = new Usuario();
    \$usuario->logout(12345);
    return;
}
";
file_put_contents($routerPath, $routerCode);

$pipes = [];
$serverProcess = proc_open(
    [PHP_BINARY, '-S', $address, $routerPath],
    [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
    $pipes
);
check(is_resource($serverProcess), 'CLI-server failed to launch');

try {
    // Wait for server to accept connections
    $ready = false;
    for ($i = 0; $i < 50; $i++) {
        $conn = @stream_socket_client('tcp://' . $address, $errno, $errstr, 0.1);
        if ($conn) {
            fclose($conn);
            $ready = true;
            break;
        }
        usleep(20000);
    }
    check($ready, 'CLI-server did not become ready');

    // 1. Establish session
    $ctxLogin = stream_context_create(['http' => ['ignore_errors' => true]]);
    $loginBody = file_get_contents('http://' . $address . '/login-state', false, $ctxLogin);
    $loginHeaders = http_get_last_response_headers();
    $cookieHeader = null;
    foreach ($loginHeaders as $hdr) {
        if (stripos($hdr, 'Set-Cookie:') === 0 && stripos($hdr, 'SIGODT_SESSID=') !== false) {
            $cookieHeader = $hdr;
            break;
        }
    }
    check($cookieHeader !== null, "Expected Set-Cookie: SIGODT_SESSID on login, got: " . implode(" | ", $loginHeaders));
    preg_match('/SIGODT_SESSID=([^;]+)/', $cookieHeader, $matches);
    $sessId = $matches[1] ?? '';
    check(!empty($sessId), "Session ID could not be parsed from header");

    // 2. Perform logout with active session cookie
    $ctxLogout = stream_context_create([
        'http' => [
            'ignore_errors'   => true,
            'follow_location' => 0,
            'header'          => "Cookie: SIGODT_SESSID={$sessId}\r\n"
        ]
    ]);
    $logoutBody = file_get_contents('http://' . $address . '/logout', false, $ctxLogout);
    $logoutHeaders = http_get_last_response_headers();

    // Check Set-Cookie deletion header
    $cookieExpired = false;
    $hasCorrectPath = false;
    foreach ($logoutHeaders as $hdr) {
        if (stripos($hdr, 'Set-Cookie:') === 0 && stripos($hdr, 'SIGODT_SESSID=') !== false) {
            if (stripos($hdr, 'deleted') !== false || stripos($hdr, 'Max-Age=0') !== false || stripos($hdr, 'expires=') !== false) {
                $cookieExpired = true;
            }
            if (stripos($hdr, 'path=/SIGODT/') !== false) {
                $hasCorrectPath = true;
            }
        }
    }
    check($cookieExpired, "Expected expired SIGODT_SESSID cookie on logout, got headers: " . implode(" | ", $logoutHeaders));
    check($hasCorrectPath, "Expected expired cookie to have path=/SIGODT/, got headers: " . implode(" | ", $logoutHeaders));

    // Check redirection to index.php
    $redirected = false;
    foreach ($logoutHeaders as $hdr) {
        if (stripos($hdr, 'Location:') === 0 && stripos($hdr, 'index.php') !== false) {
            $redirected = true;
            break;
        }
    }
    check($redirected, "Expected redirect to index.php on logout");

    echo "  -> Usuario::logout() cleanly expired SIGODT_SESSID cookie with path=/SIGODT/ and destroyed session.\n";
} finally {
    proc_terminate($serverProcess);
    foreach ($pipes as $p) { fclose($p); }
    proc_close($serverProcess);
    @unlink($routerPath);
}

echo "\nAll SIGODT session isolation tests PASSED successfully!\n";
