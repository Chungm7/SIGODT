<?php
// Deterministic tests: fake keys and an injected fake HTTP client. No network, no .env.
function check($condition, $message) {
    if (!$condition) { throw new RuntimeException($message); }
}
class Conectar {
    public function conexion() { throw new RuntimeException('credential-boundary'); }
    public function set_names() { throw new RuntimeException('unexpected DB setup'); }
    public static function ruta() { return '/'; }
    public static function getEnv(string $key, $default = null) {
        $value = getenv($key);
        return ($value === false || $value === '') ? $default : $value;
    }
}
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../helpers/Recaptcha.php';

const FAKE_SITE = 'fake-site-key';
const FAKE_SECRET = 'fake-secret-key';
function enable() {
    putenv('RECAPTCHA_ENABLED=true');
    putenv('RECAPTCHA_SITE_KEY=' . FAKE_SITE);
    putenv('RECAPTCHA_SECRET_KEY=' . FAKE_SECRET);
    putenv('RECAPTCHA_MIN_SCORE');
    putenv('RECAPTCHA_EXPECTED_HOSTNAME');
}
function fakeClient($response, &$calls = null) {
    $calls = [];
    return function ($url, $fields, $timeout) use ($response, &$calls) {
        $calls[] = compact('url', 'fields', 'timeout');
        if ($response instanceof Throwable) { throw $response; }
        return $response;
    };
}
function good(array $override = []) {
    return json_encode($override + ['success' => true, 'action' => 'login', 'score' => 0.9, 'hostname' => 'sigodt.test']);
}

// Subprocess boundary: Usuario::login with reCAPTCHA state.
if (($argv[1] ?? '') === 'boundary') {
    $case = $argv[2];
    $_SESSION = ['captcha_text' => 'ABC234', 'captcha_issued_at' => time()];
    $_POST = ['enviar' => 'si', 'captchaInput' => 'ABC234', 'recaptchaToken' => 'tok_en-123', 'usu_dni' => 'test-only', 'usu_pass' => 'test-only'];
    $response = good();
    if ($case !== 'disabled' && $case !== 'bad-image') { enable(); }
    if ($case === 'missing-token') { unset($_POST['recaptchaToken']); }
    if ($case === 'low-score') { $response = good(['score' => 0.1]); }
    if ($case === 'no-keys') { putenv('RECAPTCHA_SECRET_KEY'); }
    if ($case === 'bad-image') { enable(); $_POST['captchaInput'] = 'XYZ789'; }
    Recaptcha::setHttpClient(fakeClient($response, $calls));
    register_shutdown_function(function () use ($case, &$calls) {
        check(!isset($_SESSION['usua_id_SIGODT']), "$case: authorized session");
        if ($case === 'bad-image') { check($calls === [], 'image failure must not call Google'); }
        echo "rejected:$case\n";
    });
    try {
        (new Usuario())->login();
        throw new RuntimeException('login returned unexpectedly');
    } catch (RuntimeException $e) {
        if (!in_array($case, ['valid', 'disabled'], true) || $e->getMessage() !== 'credential-boundary') {
            fwrite(STDERR, $e->getMessage() . "\n");
            exit(1);
        }
        echo "boundary:$case\n";
    }
    exit;
}
foreach (['missing-token', 'low-score', 'no-keys', 'bad-image', 'valid', 'disabled'] as $case) {
    $output = [];
    exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' boundary ' . $case . ' 2>&1', $output, $code);
    check($code === 0, "$case failed: " . implode("\n", $output));
    if (in_array($case, ['valid', 'disabled'], true)) {
        check(in_array("boundary:$case", $output), "$case did not reach the DB boundary");
    } else {
        check(in_array("rejected:$case", $output), "$case did not reject before the DB boundary");
    }
}

$config = ['flag' => true, 'site_key' => FAKE_SITE, 'secret_key' => FAKE_SECRET, 'min_score' => 0.5, 'hostname' => ''];

// Disabled mode and configuration.
$off = ['flag' => false] + $config;
check(!Recaptcha::isEnabled($off) && Recaptcha::check(null, null, $off), 'disabled mode skips the check');
check(Recaptcha::siteKey($off) === '', 'site key hidden when disabled');
$noKeys = ['secret_key' => ''] + $config;
check(Recaptcha::isMisconfigured($noKeys) && !Recaptcha::check('tok', null, $noKeys), 'enabled without keys fails closed');
putenv('RECAPTCHA_ENABLED'); putenv('RECAPTCHA_SITE_KEY'); putenv('RECAPTCHA_SECRET_KEY');
check(!Recaptcha::isEnabled() && Recaptcha::config()['min_score'] === 0.5, 'default config is off with min score 0.5');
enable();
check(Recaptcha::isEnabled() && Recaptcha::siteKey() === FAKE_SITE, 'env config enables');

// Token validation happens before any HTTP request.
foreach ([null, '', [], 123, 'has space', "tok\n", str_repeat('a', 4097)] as $token) {
    Recaptcha::setHttpClient(fakeClient(good(), $calls));
    check(!Recaptcha::verify($token, null, $config) && $calls === [], 'malformed token rejected without HTTP');
}

// Response validation.
$cases = [
    'wrong action' => [good(['action' => 'register']), $config, false],
    'missing action' => [good(['action' => null]), $config, false],
    'low score' => [good(['score' => 0.49]), $config, false],
    'missing score' => [json_encode(['success' => true, 'action' => 'login']), $config, false],
    'string score' => [good(['score' => '0.9']), $config, false],
    'success false' => [good(['success' => false]), $config, false],
    'success string' => [good(['success' => 'true']), $config, false],
    'wrong host' => [good(), ['hostname' => 'other.test'] + $config, false],
    'non-JSON' => ['<html>error</html>', $config, false],
    'JSON scalar' => ['true', $config, false],
    'network failure null' => [null, $config, false],
    'network exception' => [new RuntimeException('down'), $config, false],
    'boundary score' => [good(['score' => 0.5]), $config, true],
    'host match' => [good(), ['hostname' => 'sigodt.test'] + $config, true],
    'success' => [good(), $config, true],
];
foreach ($cases as $name => [$response, $cfg, $expected]) {
    Recaptcha::setHttpClient(fakeClient($response, $calls));
    check(Recaptcha::verify('valid_token-1', null, $cfg) === $expected, $name);
    check(count($calls) === 1, "$name: one HTTP call");
}

// Request contract.
Recaptcha::setHttpClient(fakeClient(good(), $calls));
Recaptcha::verify('valid_token-1', '203.0.113.7', $config);
check($calls[0]['url'] === 'https://www.google.com/recaptcha/api/siteverify', 'verify URL');
check($calls[0]['fields'] === ['secret' => FAKE_SECRET, 'response' => 'valid_token-1', 'remoteip' => '203.0.113.7'], 'request fields');
check($calls[0]['timeout'] > 0 && $calls[0]['timeout'] <= 10, 'short timeout');
Recaptcha::verify('valid_token-1', 'not-an-ip', $config);
check(!isset($calls[1]['fields']['remoteip']), 'invalid IP not forwarded');

// Default client with TLS verification stays enabled.
$source = file_get_contents(__DIR__ . '/../helpers/Recaptcha.php');
check(strpos($source, 'CURLOPT_SSL_VERIFYPEER => true') !== false && strpos($source, 'CURLOPT_SSL_VERIFYHOST => 2') !== false, 'TLS verification');
echo "PHP reCAPTCHA tests passed\n";
