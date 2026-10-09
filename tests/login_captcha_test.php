<?php
// No production configuration, database, cURL, or credentials are loaded.
// The HTTP router uses an in-memory session handler: no session files are written.
if (PHP_SAPI === 'cli-server') {
    session_set_save_handler(new class implements SessionHandlerInterface {
        public function open(string $path, string $name): bool { return true; }
        public function close(): bool { return true; }
        public function read(string $id): string { return 'captcha_text|s:6:"OLD234";captcha_issued_at|i:1000;'; }
        public function write(string $id, string $data): bool { return true; }
        public function destroy(string $id): bool { return true; }
        public function gc(int $max_lifetime): int|false { return 0; }
    }, true);
    ob_start();
    require __DIR__ . '/../captcha.php';
    header('X-Test-Challenge: ' . (isset($_SESSION['captcha_text']) ? 'present' : 'absent'));
    echo ob_get_clean();
    return;
}
function check($condition, $message) {
    if (!$condition) { throw new RuntimeException($message); }
}
class Conectar {
    public function conexion() { throw new RuntimeException('credential-boundary'); }
    public function set_names() { throw new RuntimeException('unexpected DB setup'); }
    public static function ruta() { return '/'; }
}
require_once __DIR__ . '/../models/Usuario.php';

if (($argv[1] ?? '') === 'boundary') {
    $case = $argv[2];
    $_SESSION = ['captcha_text' => 'ABC234', 'captcha_issued_at' => time()];
    $_POST = ['enviar' => 'si', 'captchaInput' => 'ABC234', 'usu_dni' => 'test-only', 'usu_pass' => 'test-only'];
    if ($case === 'missing') { unset($_POST['captchaInput']); }
    if ($case === 'empty') { $_POST['captchaInput'] = ''; }
    if ($case === 'wrong') { $_POST['captchaInput'] = 'XYZ789'; }
    if ($case === 'array') { $_POST['captchaInput'] = ['ABC234']; }
    if ($case === 'expired') { $_SESSION['captcha_issued_at'] -= 301; }
    if ($case === 'refreshed') { $_SESSION['captcha_text'] = 'XYZ789'; }
    if ($case === 'reused') { $_SESSION = []; }
    if ($case === 'malformed-session') { $_SESSION['captcha_text'] = []; }
    if ($case === 'malformed-time') { $_SESSION['captcha_issued_at'] = 'invalid'; }
    if ($case === 'malformed-credentials') { $_POST['usu_pass'] = []; }
    register_shutdown_function(function () use ($case) {
        check(!isset($_SESSION['captcha_text']), "$case: challenge not consumed");
        check(!isset($_SESSION['usua_id_SIGODT']), "$case: captcha authorized session");
        echo "rejected:$case\n";
    });
    try {
        (new Usuario())->login();
        throw new RuntimeException('login returned unexpectedly');
    } catch (RuntimeException $e) {
        if ($case !== 'valid' || $e->getMessage() !== 'credential-boundary') {
            fwrite(STDERR, $e->getMessage() . "\n");
            exit(1);
        }
        echo "boundary:valid\n";
    }
    exit;
}
foreach (['missing', 'empty', 'wrong', 'array', 'expired', 'refreshed', 'reused', 'malformed-session', 'malformed-time', 'malformed-credentials', 'valid'] as $case) {
    exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' boundary ' . $case . ' 2>&1', $output, $code);
    check($code === 0, "$case failed: " . implode("\n", $output));
    check(in_array("rejected:$case", $output), "$case did not complete rejection check");
    if ($case === 'valid') { check(in_array('boundary:valid', $output), 'valid did not reach boundary'); }
    $output = [];
}
require_once __DIR__ . '/../helpers/Captcha.php';
$session = [];
$image = Captcha::issue($session, 1000, function ($text) { return 'data:image/png;base64,' . base64_encode('image'); });
check(is_string($image) && preg_match('/^[A-Z2-9]{6}$/', $session['captcha_text']), 'generation contract');
$answer = $session['captcha_text'];
check(Captcha::consume($session, strtolower($answer), 1299), 'case normalization');
check(!Captcha::consume($session, $answer, 1299), 'single use');
$session = ['captcha_text' => 'OLD234', 'captcha_issued_at' => 1000];
Captcha::issue($session, 1001, function ($text) { return 'data:image/png;base64,aW1hZ2U='; });
check($session['captcha_text'] !== 'OLD234', 'refresh did not replace challenge');
check(!Captcha::consume($session, 'OLD234', 1002), 'refreshed answer accepted');
foreach ([null, [], 123, '', ' ABC234 ', 'ABC234', str_repeat('A', 1000)] as $input) {
    $session = ['captcha_text' => 'ABC234', 'captcha_issued_at' => 1000];
    check(!Captcha::consume($session, $input, 1300), 'expiry/malformed input');
    check($session === [], 'failed attempt consumption');
}
foreach ([999, 1300] as $now) {
    $session = ['captcha_text' => 'ABC234', 'captcha_issued_at' => 1000];
    check(!Captcha::consume($session, 'ABC234', $now), 'time boundary');
}
$session = ['captcha_text' => 'OLD234', 'captcha_issued_at' => 1000];
foreach ([function () { throw new RuntimeException('render failed'); }, function () { return ''; }] as $renderer) {
    $session = ['captcha_text' => 'OLD234', 'captcha_issued_at' => 1000];
    $failed = false;
    try { Captcha::issue($session, 1001, $renderer); }
    catch (RuntimeException $e) { $failed = true; }
    check($failed && $session === [], 'render failure must invalidate challenge');
}
if (!extension_loaded('gd')) {
    try { Captcha::issue($session); throw new LogicException('missing GD accepted'); }
    catch (RuntimeException $e) { check($session === [], 'missing GD preserved challenge'); }
    echo "GD absent: real image success unavailable; fail-closed verified\n";
} else {
    check(strpos(Captcha::issue($session), 'data:image/png;base64,iVBOR') === 0, 'real PNG');
}
// Real local HTTP contracts; the router above isolates all session storage.
$socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
check($socket !== false, 'local socket unavailable');
$address = stream_socket_get_name($socket, false);
fclose($socket);
$process = proc_open([PHP_BINARY, '-S', $address, __FILE__], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
check(is_resource($process), 'HTTP server unavailable');
try {
    $ready = false;
    for ($i = 0; $i < 100; $i++) {
        $connection = @stream_socket_client('tcp://' . $address, $errno, $error, 0.1);
        if ($connection) { fclose($connection); $ready = true; break; }
        usleep(20000);
    }
    check($ready, 'HTTP server did not start');
    foreach (['invalid', 'generateCaptcha'] as $action) {
        $context = stream_context_create(['http' => ['ignore_errors' => true, 'timeout' => 5, 'header' => 'Cookie: PHPSESSID=captcha-contract-test']]);
        $body = file_get_contents('http://' . $address . '/captcha.php?action=' . $action, false, $context);
        $headers = http_get_last_response_headers();
        $joined = strtolower(implode("\n", $headers));
        check(strpos($joined, 'content-type: application/json; charset=utf-8') !== false, 'JSON HTTP header');
        check(strpos($joined, 'cache-control: no-store') !== false, 'HTTP cache policy');
        $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        check(!array_key_exists('captcha_text', $data), 'answer leaked over HTTP');
        if ($action === 'invalid' || !extension_loaded('gd')) {
            check(strpos($headers[0], $action === 'invalid' ? '400' : '503') !== false, 'failure HTTP status');
            check(strpos($joined, 'x-test-challenge: absent') !== false, 'failure kept previous session challenge');
            check(array_keys($data) === ['message'], 'failure JSON shape');
        } else {
            check(strpos($headers[0], '200') !== false, 'success HTTP status');
            check(array_keys($data) === ['captcha'], 'success image-only JSON');
            check(strpos($joined, 'x-test-challenge: present') !== false, 'success challenge missing');
        }
    }
    echo "HTTP JSON/no-store/status/session contracts passed (in-memory test session)\n";
} finally {
    proc_terminate($process);
    foreach ($pipes as $pipe) { fclose($pipe); }
    proc_close($process);
}
echo "PHP CAPTCHA tests passed\n";
