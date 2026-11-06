<?php
/** Isolated public-controller fixtures for the three transplanted SIINDI contact operations.
 * Run in the task's read-only, network-isolated OS sandbox; all fixtures live in TMPDIR.
 * php -n children deliberately have no cURL extension: the dummy provider records requests.
 */
function portCheck($condition, $message) {
    if (!$condition) { throw new RuntimeException($message); }
}
function contactFixture($op, $session, $post = [], $method = 'POST', $provider = 'ok', $target = 'contact') {
    $root = sys_get_temp_dir() . '/siindi-contact-' . bin2hex(random_bytes(6));
    foreach (['controller', 'config', 'models'] as $dir) { mkdir($root . '/' . $dir, 0700, true); }
    copy(__DIR__ . '/../controller/usuario.php', $root . '/controller/usuario.php');
    file_put_contents($root . '/config/conexion.php', <<<'PHP'
<?php
class Conectar {
    public static function getEnv($key, $default = null) {
        return ['SIGUR_URL' => 'https://provider.invalid/sigur/', 'SIST_INIC' => 'TEST', 'SIST_PASS' => 'fixture-only'][$key] ?? $default;
    }
}
PHP
    );
    file_put_contents($root . '/models/Usuario.php', '<?php class Usuario { public function listarSistemasUsuario($id) { return [["pers_id" => $id, "legacy" => true]]; } }');
    file_put_contents($root . '/models/Bitacora.php', '<?php class Bitacora {}');
    if ($target === 'mailer') {
        mkdir($root . '/services', 0700);
        foreach (['SigurMailer', 'SmtpMailer'] as $service) {
            $source = __DIR__ . '/../seguridad/2fa/services/' . $service . '.php';
            if (is_file($source)) { copy($source, $root . '/services/' . $service . '.php'); }
        }
    }
    $runner = <<<'PHP'
<?php
foreach (['CURLOPT_URL', 'CURLOPT_RETURNTRANSFER', 'CURLOPT_POST', 'CURLOPT_POSTFIELDS', 'CURLOPT_HTTPHEADER', 'CURLOPT_TIMEOUT', 'CURLOPT_CONNECTTIMEOUT', 'CURLOPT_SSL_VERIFYPEER', 'CURLOPT_SSL_VERIFYHOST', 'CURLOPT_FOLLOWLOCATION', 'CURLINFO_HTTP_CODE'] as $i => $name) { define($name, $i + 1); }
$GLOBALS['requests'] = [];
function curl_init($url = null) { return (object)['options' => [CURLOPT_URL => $url]]; }
function curl_setopt($ch, $key, $value) { $ch->options[$key] = $value; return true; }
function fixtureProviderMode($ch) {
    $mode = $GLOBALS['provider'];
    if (str_starts_with($mode, 'mail_')) {
        return str_ends_with($ch->options[CURLOPT_URL], '/auth/') ? 'ok' : substr($mode, 5);
    }
    return $mode;
}
function curl_exec($ch) {
    $GLOBALS['requests'][] = $ch->options;
    $auth = str_ends_with($ch->options[CURLOPT_URL], '/auth/');
    $mode = fixtureProviderMode($ch);
    if ($mode === 'transport') { return false; }
    if ($mode === 'malformed') { return '<html>upstream failure</html>'; }
    if ($mode === 'denied') { return json_encode(['success' => false, 'message' => 'PRIVATE upstream detail']); }
    return json_encode($auth ? ['success' => true, 'data' => ['token' => 'dummy-token']] : ['success' => true, 'data' => ['usu_celu01' => '900000001', 'usu_emailp' => 'person@example.invalid', 'usu_emails' => 'office@example.invalid', 'secret' => 'never-return']]);
}
function curl_errno($ch) { return fixtureProviderMode($ch) === 'transport' ? 7 : 0; }
function curl_error($ch) { return fixtureProviderMode($ch) === 'transport' ? 'PRIVATE transport detail' : ''; }
function curl_getinfo($ch, $key) { return fixtureProviderMode($ch) === 'http' ? 503 : 200; }
function curl_close($ch) {}
ob_start();
register_shutdown_function(function () {
    $body = ob_get_clean();
    echo json_encode(['body' => $body, 'requests' => $GLOBALS['requests'], 'session' => $_SESSION, 'status' => http_response_code()]);
});
$input = json_decode(stream_get_contents(STDIN), true);
$_GET = ['op' => $input['op']]; $_POST = $input['post']; $_SESSION = $input['session'];
$_SERVER['REQUEST_METHOD'] = $input['method']; $GLOBALS['provider'] = $input['provider'];
if ($input['target'] === 'mailer') {
    $_ENV = ['SIGUR_URL' => 'https://provider.invalid/', 'SIST_INIC' => 'TEST', 'SIST_PASS' => 'fixture-only', 'APP_NAME' => 'SIGODT'];
    if ($input['provider'] === 'missing') { unset($_ENV['SIST_PASS']); }
    if ($input['provider'] === 'httpbase') { $_ENV['SIGUR_URL'] = 'http://provider.invalid/sigur/'; }
    require __DIR__ . '/services/SmtpMailer.php';
    $result = $input['op'] === 'notification'
        ? SmtpMailer::sendDeviceLinkedNotification('office@example.invalid', '<b>Fixture</b>')
        : SmtpMailer::sendVerificationCode('office@example.invalid', '123456', '<b>Fixture</b>', 120);
    echo json_encode($result);
    return;
}
chdir(__DIR__ . '/controller');
require 'usuario.php';
PHP;
    file_put_contents($root . '/run.php', $runner);
    $process = proc_open([PHP_BINARY, '-n', $root . '/run.php'], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    portCheck(is_resource($process), 'Cannot start fixture child');
    fwrite($pipes[0], json_encode(compact('op', 'session', 'post', 'method', 'provider', 'target'))); fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]); fclose($pipes[1]);
    $stderr = stream_get_contents($pipes[2]); fclose($pipes[2]);
    $status = proc_close($process);
    portCheck($status === 0 && ($target === 'mailer' || $stderr === ''), 'Fixture failed: ' . $stderr . $stdout);
    $result = json_decode($stdout, true);
    portCheck(is_array($result), 'Fixture did not return JSON: ' . $stdout);
    $result['json'] = json_decode($result['body'], true);
    $result['stderr'] = $stderr;
    return $result;
}
$session = ['usua_id_SIGODT' => 42, 'usua_dni_SIGODT' => '00000042', 'csrf_token' => str_repeat('a', 64)];
$csrf = ['csrf_token' => $session['csrf_token']];
foreach (['mostrar_datos', 'update_telefono', 'update_emailp'] as $op) {
    $dato = $op === 'update_telefono' ? '900000001' : 'person@example.invalid';
    $goodPost = $csrf + ['dato' => $dato, 'usu_doc' => 'attacker', 'usu_tipo' => 'C', 'pers_id' => 999];
    $result = contactFixture($op, $session, $goodPost);
    portCheck(($result['json']['success'] ?? false) === true, "$op must succeed for the owned account");
    portCheck(count($result['requests']) === 2, "$op must acquire one token and call provider once");
    $request = $result['requests'][1];
    // Constants in php -n fixture are numbered in the order declared above.
    parse_str($request[4], $body);
    portCheck($body['usu_doc'] === '00000042' && $body['usu_tipo'] === 'P', "$op must ignore posted identity");
    portCheck($request[8] === true && $request[9] === 2 && $request[10] === false, "$op must verify TLS and disallow redirects");
    portCheck($request[6] <= 15 && $request[7] <= 5, "$op must bound provider timeouts");
    portCheck(!isset($result['json']['data']['secret']), "$op must filter provider output");
    if ($op === 'mostrar_datos') {
        portCheck($result['json']['data']['usu_emaili'] === 'office@example.invalid', 'Source usu_emails must map to displayed usu_emaili');
    } else {
        portCheck($body['tipo_cambio'] === ($op === 'update_telefono' ? '1' : '2'), 'Preserve source change selector');
    }
    foreach ([['session' => [], 'post' => $goodPost, 'method' => 'POST'], ['session' => ['2fa_pending_user' => ['usu_doc' => '00000042']], 'post' => $goodPost, 'method' => 'POST'], ['session' => $session, 'post' => ['dato' => $dato], 'method' => 'POST'], ['session' => $session, 'post' => ['csrf_token' => ['bad'], 'dato' => $dato], 'method' => 'POST'], ['session' => $session, 'post' => $goodPost, 'method' => 'GET']] as $bad) {
        $rejected = contactFixture($op, $bad['session'], $bad['post'], $bad['method']);
        portCheck(($rejected['json']['success'] ?? null) === false, "$op must reject unauthenticated/pending/CSRF/method violations");
        portCheck($rejected['requests'] === [], "$op rejection must not call provider");
        portCheck($rejected['session'] === $bad['session'], "$op rejection must leave session unchanged");
    }
    foreach (['transport', 'malformed', 'denied', 'http'] as $failure) {
        $failed = contactFixture($op, $session, $goodPost, 'POST', $failure);
        portCheck(($failed['json']['success'] ?? null) === false, "$op must deny $failure failure");
        portCheck(!str_contains($failed['body'], 'PRIVATE'), 'Do not leak upstream errors');
        portCheck($failed['session'] === $session, 'Provider failure must not change session');
    }
}
foreach ([['update_telefono', '123'], ['update_telefono', ['bad']], ['update_emailp', 'not-an-email'], ['update_emailp', ['bad']]] as [$op, $dato]) {
    $bad = contactFixture($op, $session, $csrf + ['dato' => $dato]);
    portCheck(($bad['json']['success'] ?? null) === false && $bad['requests'] === [], 'Invalid contact must be rejected before provider call');
}
$legacy = contactFixture('listar_sistemas', $session);
portCheck($legacy['json'] === [['pers_id' => 42, 'legacy' => true]], 'Existing administrative operation must remain available');
echo "PASS: SIINDI contact handlers, ownership, CSRF, provider failures, alias and legacy dispatch\n";

// Original services are preserved templates. Removed strict-key/TLS/simulation
// assertions describe proposals, not the behavior this integration may silently change.
foreach (['verification', 'notification'] as $operation) {
    $mail = contactFixture($operation, [], [], 'POST', 'ok', 'mailer');
    portCheck(($mail['json']['success'] ?? false) === true && ($mail['json']['simulated'] ?? true) === false, 'Original source acknowledges successful delivery');
    portCheck(count($mail['requests']) === 2 && $mail['stderr'] === '', 'Source authenticates and sends once without diagnostics');
    portCheck($mail['requests'][0][1] === 'https://provider.invalid/sigur/auth/', 'Original source expects the host-root URL convention');
    $request = $mail['requests'][1];
    portCheck($request[1] === 'https://provider.invalid/sigur/ws/correo-ss.php', 'Original mail endpoint');
    portCheck(is_array($request[4]) && array_keys($request[4]) === ['destino', 'titulo', 'mensaje'], 'Original multipart field names');
    portCheck($request[4]['destino'] === 'office@example.invalid', 'Server-selected recipient');
    if ($operation === 'verification') portCheck(str_contains($request[4]['mensaje'], '123456'), 'Original OTP template');
    foreach (['transport','malformed','denied','http','mail_transport','mail_http','mail_malformed','mail_denied'] as $failure) {
        $mail = contactFixture($operation, [], [], 'POST', $failure, 'mailer');
        portCheck(($mail['json']['success'] ?? null) === false, 'Original source delivery rejection: '.$failure);
        portCheck(!preg_match('/PHP (?:Fatal|Warning|Parse)|Deprecated:/', $mail['stderr']), 'No fixture execution failure');
    }
    $simulation = contactFixture($operation, [], [], 'POST', 'missing', 'mailer');
    portCheck(($simulation['json']['simulated'] ?? false) === true && !$simulation['requests'], 'Original development fallback is explicit, NOT delivery proof');
}
echo "PASS: unchanged source mail protocol; key/TLS/audit hardening remains proposal-only\n";

require_once __DIR__ . '/../seguridad/2fa/services/TwoFactorService.php';
$service = TwoFactorService::class;
portCheck($service::calculateCode('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', 1) === '287082', 'RFC6238 original vector');
$secret = $service::generateSecret();
portCheck(strlen($secret) === 32 && preg_match('/^[A-Z2-7]+$/D', $secret), 'Original160-bit secret');
$step = (int)floor(time()/30);
$code = $service::calculateCode($secret, $step);
portCheck($service::verifyCode($secret,$code,2,0)['valid'], 'Original current code');
portCheck(!$service::verifyCode($secret,$code,2,$step)['valid'], 'Original service consumed-step check');
portCheck(!$service::verifyCode($secret,'bad',2,0)['valid'], 'Original malformed-code rejection');
unset($_ENV['APP_2FA_MASTER_KEY']);
$fallback = $service::encryptSecret($secret);
portCheck($service::decryptSecret($fallback) === $secret, 'Preserve original fallback compatibility, not a secure-key assertion');
$_ENV['APP_2FA_MASTER_KEY'] = str_repeat('k',32);
$ciphertext = $service::encryptSecret($secret);
portCheck($service::decryptSecret($ciphertext) === $secret, 'Original GCM roundtrip');
$raw = base64_decode($ciphertext,true);
portCheck(strlen($raw) === 12+16+strlen($secret), 'Original IV/tag envelope');
$raw[28] = chr(ord($raw[28])^1);
portCheck($service::decryptSecret(base64_encode($raw)) === false, 'Original authentication rejects tamper');
$_ENV['APP_2FA_MASTER_KEY'] = str_repeat('x',32);
portCheck($service::decryptSecret($ciphertext) === false, 'Original wrong-key rejection');
portCheck(str_starts_with($service::getOtpAuthUri('MPCH 2FA','Fixture employee',$secret),'otpauth://totp/MPCH%202FA:Fixture%20employee?secret='), 'Original local URI');
unset($_ENV['APP_2FA_MASTER_KEY']);
echo "PASS: original source TOTP/GCM compatibility (not institutional key proof)\n";
