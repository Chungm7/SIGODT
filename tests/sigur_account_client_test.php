<?php
require_once __DIR__ . '/../helpers/Account/SigurAccountClient.php';
function check($ok, $label) { if (!$ok) throw new RuntimeException($label); }
$env = ['SIGUR_URL' => 'https://sigur.example.test/sigur/', 'SIST_INIC' => 'FIXTURE', 'SIST_PASS' => 'dummy-only'];
$get = function ($key, $default = null) use (&$env) { return $env[$key] ?? $default; };
$calls = []; $mode = 'ok';
$transport = function ($request) use (&$calls, &$mode) {
    $calls[] = $request;
    if (strpos($request['url'], '/auth/') !== false) return ['status' => 200, 'body' => json_encode(['success' => $mode !== 'authfail', 'data' => ['token' => 'dummy-token']])];
    if ($mode === 'wrong_id') return ['status' => 200, 'body' => '{"success":true,"data":{"usu_id":8,"usu_doc":"87654321"}}'];
    if ($mode === 'no_mail') return ['status' => 200, 'body' => '{"success":true,"data":{"usu_emailm":"wrong-alias@example.test"}}'];
    if ($mode === 'throw') throw new RuntimeException('dummy-secret');
    if ($mode === 'html') return ['status' => 200, 'body' => '<html>dummy-secret</html>'];
    if ($mode === 'http') return ['status' => 500, 'body' => '{"success":true}'];
    if ($mode === 'failure') return ['status' => 200, 'body' => '{"success":false,"message":"dummy-secret"}'];
    if ($mode === 'contradiction') return ['status' => 200, 'body' => '{"success":true,"status":403}'];
    return ['status' => 200, 'body' => json_encode(['success' => true, 'status' => 200, 'data' => ['usu_id' => 7, 'usu_doc' => '12345678', 'usu_celu01' => '999888777', 'usu_emailp' => 'p@example.test', 'usu_emails' => 'i@example.test']])];
};
$client = new SigurAccountClient($get, $transport);
$id = ['id' => 7, 'document' => '12345678', 'type' => 'P'];
$data = $client->currentAccount($id);
check($data['institutional_email'] === 'i@example.test' && $data['personal_email'] === 'p@example.test', 'observed aliases');
check(count($calls) === 2 && $calls[1]['url'] === $env['SIGUR_URL'] . 'ws/mostrar.php', 'single data request');
$client->updatePhone($id, '987654321');
$client->updatePersonalEmail($id, 'new@example.test');
$client->changePassword($id, 'Old1234!', 'New1234!');
$client->sendInstitutionalOtpMail($id, '123456', 'Fixture', 120);
check(count($calls) === 11, 'two calls per operation plus profile lookup for mail');
check($calls[3]['fields']['tipo_cambio'] === '1' && $calls[5]['fields']['tipo_cambio'] === '2', 'contact update contracts');
check($calls[7]['fields']['usu_pass_actual'] === 'Old1234!' && strpos($calls[7]['url'], 'Old1234') === false, 'password only in POST');
check($calls[10]['fields']['destino'] === 'i@example.test' && strpos($calls[10]['url'], 'correo-ss.php') !== false, 'server-resolved institutional recipient');
foreach ($calls as $request) {
    check($request['method'] === 'POST' && $request['verify_peer'] === true && $request['verify_host'] === 2, 'secure transport');
    check($request['connect_timeout'] <= 5 && $request['timeout'] <= 20 && $request['follow_redirects'] === false, 'bounded nonredirecting transport');
}
foreach (['throw', 'html', 'http', 'failure', 'contradiction'] as $mode) {
    $before = count($calls);
    try { $client->updatePhone($id, '987654321'); throw new LogicException('accepted invalid upstream'); }
    catch (SigurAccountException $e) { check($e->getMessage() === 'account_service_unavailable', 'stable error'); }
    check(count($calls) === $before + 2, 'no mutation retry');
}
$mode = 'authfail'; $before = count($calls);
try { $client->updatePhone($id, '987654321'); throw new LogicException('accepted failed auth'); }
catch (SigurAccountException $e) { check(count($calls) === $before + 1, 'no mutation after failed auth'); }
$mode = 'wrong_id';
try { $client->currentAccount($id); throw new LogicException('accepted another owner'); }
catch (SigurAccountException $e) { check($e->getMessage() === 'account_service_unavailable', 'ownership mismatch rejected'); }
$mode = 'no_mail'; $before = count($calls);
try { $client->sendInstitutionalOtpMail($id, '123456'); throw new LogicException('accepted unobserved institutional alias'); }
catch (SigurAccountException $e) { check($e->getMessage() === 'institutional_email_unavailable', 'missing observed recipient fails closed'); }
check(count($calls) === $before + 2, 'never dispatch to guessed institutional alias');
$mode = 'ok'; $before = count($calls);
foreach ([['updatePhone', [$id, 'invalid']], ['updatePersonalEmail', [$id, 'bad']], ['changePassword', [$id, 'old', 'weak']], ['currentAccount', [['id' => 0, 'document' => '12345678', 'type' => 'P']]]] as [$method, $args]) {
    try { $client->$method(...$args); throw new LogicException('accepted invalid client input'); }
    catch (InvalidArgumentException $e) { check(count($calls) === $before, 'client rejection makes no requests'); }
}
$env['SIST_PASS'] = ''; $before = count($calls);
try { $client->sendInstitutionalOtpMail($id, '123456'); throw new LogicException('fake mail success'); }
catch (SigurAccountException $e) { check($e->getMessage() === 'account_configuration_missing', 'missing config fails closed'); }
check(count($calls) === $before, 'no requests missing config');
$env['SIST_PASS'] = 'dummy-only'; $env['SIGUR_URL'] = 'http://sigur.example.test/sigur/';
try { $client->currentAccount($id); throw new LogicException('accepted cleartext credentials'); }
catch (SigurAccountException $e) { check($e->getMessage() === 'account_configuration_missing', 'HTTPS required'); }
echo "sigur_account_client_test: passed\n";
