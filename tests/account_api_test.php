<?php
// Standalone fake-principal tests: never load destination configuration.
define('ACCOUNT_API_LIBRARY_ONLY', true);
require_once __DIR__ . '/../controller/account.php';
function check($ok, $label) { if (!$ok) throw new RuntimeException($label); }
class AccountProviderDouble {
    public $calls = [];
    public $fail = false;
    public function __call($name, $args) {
        $this->calls[] = [$name, $args];
        if ($this->fail) throw new RuntimeException('upstream secret');
        return $name === 'currentAccount' ? ['phone' => '999888777', 'personal_email' => 'p@example.test', 'institutional_email' => 'i@example.test'] : [];
    }
}
function principal() { return ['usua_id_SIGODT' => 7, 'usua_dni_SIGODT' => '12345678', 'rol_id_SIGODT' => 9, 'account_csrf' => str_repeat('a', 64), 'pers_nombre_SIGODT' => 'Fixture', 'rol_nombre_SIGODT' => 'ADMINISTRADOR']; }
$p = new AccountProviderDouble();
foreach (['profile', 'update_phone', 'update_personal_email', 'change_password', 'csrf'] as $op) {
    $s = []; $r = account_api_handle($s, 'POST', $op, [], $p);
    check($r['status'] === 401 && $r['body']['error'] === 'authentication_required', 'unauthenticated ' . $op);
}
foreach ([['pending_login' => ['usua_id_SIGODT' => 7, 'usua_dni_SIGODT' => '12345678']], ['usua_id_SIGODT' => [], 'usua_dni_SIGODT' => '12345678'], ['usua_id_SIGODT' => 7], ['usua_id_SIGODT' => 0, 'usua_dni_SIGODT' => '12345678']] as $invalidSession) {
    $beforeInvalid = $invalidSession;
    $r = account_api_handle($invalidSession, 'GET', 'profile', [], $p);
    check($r['status'] === 401 && $invalidSession === $beforeInvalid, 'pending or malformed identity cannot become principal');
}
check(count($p->calls) === 0, 'no unauthenticated provider calls');
$s = principal(); $before = $s;
foreach ([['GET', 'update_phone', [], 405], ['POST', 'update_phone', [], 403], ['POST', 'unknown', [], 404], ['POST', 'update_phone', ['csrf_token' => $s['account_csrf'], 'phone' => '123'], 422], ['POST', 'update_personal_email', ['csrf_token' => $s['account_csrf'], 'personal_email' => 'bad'], 422], ['POST', 'change_password', ['csrf_token' => $s['account_csrf'], 'current_password' => 'old', 'new_password' => 'weak', 'confirm_password' => 'weak'], 422], ['POST', 'update_phone', ['csrf_token' => $s['account_csrf'], 'phone' => '999888777', 'usu_doc' => '99999999'], 400]] as [$method, $op, $input, $status]) {
    $r = account_api_handle($s, $method, $op, $input, $p);
    check($r['status'] === $status && $r['body']['success'] === false, 'reject status ' . $op . ':' . $status);
    check($s === $before && count($p->calls) === 0, 'reject unchanged state/counter');
}
foreach (['usu_doc', 'usu_tipo', 'pers_id', 'usua_id_SIGODT', 'role', 'institutional_email'] as $override) {
    $r = account_api_handle($s, 'POST', 'update_phone', ['csrf_token' => $s['account_csrf'], 'phone' => '987654321', $override => 'override'], $p);
    check($r['status'] === 400 && $s === $before && count($p->calls) === 0, 'no identity/role override ' . $override);
}
foreach (['update_phone' => ['phone' => []], 'update_personal_email' => ['personal_email' => []], 'change_password' => ['current_password' => []]] as $op => $input) {
    $r = account_api_handle($s, 'POST', $op, $input + ['csrf_token' => $s['account_csrf']], $p);
    check($r['status'] === 422 && $s === $before && count($p->calls) === 0, 'array input rejected unchanged');
}
$r = account_api_handle($s, 'GET', 'csrf', [], $p);
check($r['body']['data']['csrf_token'] === $s['account_csrf'], 'csrf contract');
$r = account_api_handle($s, 'GET', 'profile', [], $p);
check($r['status'] === 200 && $r['body']['data']['names'] === 'Fixture' && $r['body']['data']['position'] === null, 'display mapping');
check($s['pers_emailm_SIGODT'] === 'i@example.test', 'institutional session mapping');
foreach ([['update_phone', ['phone' => '987654321'], 'pers_celu01_SIGODT', '987654321'], ['update_personal_email', ['personal_email' => 'new@example.test'], 'pers_emailp_SIGODT', 'new@example.test']] as [$op, $input, $key, $value]) {
    $r = account_api_handle($s, 'POST', $op, $input + ['csrf_token' => $s['account_csrf']], $p);
    check($r['status'] === 200 && $s[$key] === $value, 'successful write refreshes session');
    $call = end($p->calls); check($call[1][0] === ['id' => 7, 'document' => '12345678', 'type' => 'P'], 'same server principal');
}
$r = account_api_handle($s, 'POST', 'change_password', ['csrf_token' => $s['account_csrf'], 'current_password' => 'Old1234!', 'new_password' => 'New1234!', 'confirm_password' => 'New1234!'], $p);
check($r['status'] === 200 && $r['body']['data']['transition'] === 'settings' && $s['usua_id_SIGODT'] === 7, 'regular password preserves authenticated session');
$p->fail = true; $before = $s; $count = count($p->calls);
$r = account_api_handle($s, 'POST', 'update_phone', ['csrf_token' => $s['account_csrf'], 'phone' => '999888777'], $p);
check($r['status'] === 502 && $r['body']['error'] === 'account_service_unavailable' && strpos(json_encode($r), 'secret') === false, 'sanitized failure');
check($s === $before && count($p->calls) === $count + 1, 'failure preserves state, no retry');
// Render the same injectable entry used by HTTP, without live config.
ob_start(); account_api_render($s, 'GET', 'unknown', [], $p); $body = json_decode(ob_get_clean(), true);
check(http_response_code() === 404 && $body['error'] === 'operation_not_found', 'render exact HTTP and JSON');
echo "account_api_test: passed\n";
