<?php
/** Public host-bridge contract. No provider or database calls. */
$_ENV['APP_URL'] = 'https://app.invalid/SIGODT/';
$_ENV['APP_NAME'] = 'SIGODT';
$_ENV['JWT_SECRET'] = 'fixture-only-signing-key-not-a-secret';
$_ENV['JWE_SECRET'] = 'fixture-only-encryption-key-not-a-secret';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/jwt.php';
if (is_file(__DIR__ . '/../helpers/SiindiSessionBridge.php')) require_once __DIR__ . '/../helpers/SiindiSessionBridge.php';
ob_start();
function bridgeAssert($value, $message) { if (!$value) throw new RuntimeException($message); }
function bridgePending() { return ['usu_id'=>900,'pers_id'=>42,'usu_doc'=>'00000042','usu_tipo'=>'P','usu_nom'=>'Fixture','usu_apep'=>'A','usu_apem'=>'B','expires_at'=>time()+300,'challenge_id'=>str_repeat('a',64),'user_data'=>['perf_id'=>2,'perf_nombre'=>'CONSULTA'],'ip'=>'127.0.0.1']; }
$cases = [];
$cases['browser origin proof'] = function () {
    foreach (['https://app.invalid', 'https://app.invalid:443'] as $origin) {
        $_SERVER['REQUEST_METHOD']='POST'; $_SERVER['HTTP_ORIGIN']=$origin;
        bridgeAssert(SiindiSessionBridge::trustedPost(), 'Same configured origin');
    }
    foreach (['', 'null', 'https://evil.invalid', 'http://app.invalid', 'https://app.invalid:444', 'https://app.invalid/path', 'https://app.invalid.evil.invalid'] as $origin) {
        $_SERVER['HTTP_ORIGIN']=$origin;
        bridgeAssert(!SiindiSessionBridge::trustedPost(), 'Reject origin: '.$origin);
    }
    $_SERVER['HTTP_ORIGIN']='https://app.invalid'; $_SERVER['REQUEST_METHOD']='GET';
    bridgeAssert(!SiindiSessionBridge::trustedPost(), 'Changing GET denied');
};
$cases['pending projection and original nonce passthrough'] = function () {
    $_SESSION=[]; $_COOKIE=[];
    SiindiSessionBridge::beginPending(bridgePending());
    $alias=$_SESSION['2fa_pending_user']; $private=$_SESSION['2fa_pending_SIGODT'];
    bridgeAssert($alias['usu_id']===900 && $alias['usu_doc']==='00000042', 'Institutional identity retained');
    bridgeAssert(strlen($alias['sigodt_nonce'])===64 && $alias['sigodt_nonce']===$private['sigodt_nonce'], 'Nonce correlated');
    $token=(new JwtHandler())->decrypt($_COOKIE['2fa_pending'] ?? '');
    bridgeAssert(($token->type ?? '')==='2fa_pending' && ($token->data->usu_doc ?? '')===$private['usu_doc'] && !isset($token->data->sigodt_nonce), 'Cookie-only TOTP view gets identity, never private completion nonce');
    bridgeAssert(!isset($alias['pers_id'], $alias['user_data'], $alias['ip']) && !isset($alias['dfa']), 'No private authorization or fake verification');
    bridgeAssert($alias['perf_id']===2 && $alias['perfil_id']===2, 'Never source administrator defaults');
};
$cases['public pending data cannot be converted into completion proof'] = function () {
    $_SESSION=[]; $_COOKIE=[]; SiindiSessionBridge::beginPending(bridgePending());
    $jwt=new JwtHandler();
    $public=$jwt->decrypt($_COOKIE['2fa_pending'])->data;
    // Even knowing legacy/shared fixture crypto must not disclose the server nonce.
    $forged=$jwt->decrypt($jwt->encrypt($public,'session'));
    bridgeAssert(SiindiSessionBridge::completionProof($forged)===null, 'Browser pending identity is not a verified factor');
};
$cases['original controller uses private alias and source mail URL convention'] = function () {
    $_SESSION=[]; $_COOKIE=[]; SiindiSessionBridge::beginPending(bridgePending());
    $_SERVER['SCRIPT_FILENAME']=dirname(__DIR__).'/seguridad/2fa/controllers/TwoFactorController.php';
    $_SERVER['REQUEST_METHOD']='POST'; $_SERVER['HTTP_ORIGIN']='https://app.invalid';
    foreach (['https://provider.invalid/sigur/','https://provider.invalid/','https://provider.invalid/sigur/ws/'] as $base) {
        $_ENV['SIGUR_URL']=$base; SiindiSessionBridge::bootstrap();
        bridgeAssert($_ENV['SIGUR_URL']==='https://provider.invalid/', 'Original mailer appends sigur once');
        bridgeAssert(!isset($_COOKIE['2fa_pending']) && !empty($_SESSION['2fa_pending_user']['sigodt_nonce']), 'Original producer uses private server nonce, never browser cookie');
    }
};
$cases['completed host identity overrides residual source pending identity'] = function () {
    $_SESSION=['usua_id_SIGODT'=>42,'sigur_usuario_id_SIGODT'=>900,'usua_dni_SIGODT'=>'00000042','rol_id_SIGODT'=>2,'rol_nombre_SIGODT'=>'CONSULTA', '2fa_pending_user'=>['usu_id'=>901,'usu_doc'=>'00000043']];
    $_COOKIE=['2fa_pending'=>(new JwtHandler())->encrypt(['usu_id'=>901,'usu_doc'=>'00000043','usu_tipo'=>'P'],'2fa_pending')];
    $_SERVER['SCRIPT_FILENAME']=dirname(__DIR__).'/seguridad/2fa/controllers/TwoFactorController.php';
    $_SERVER['REQUEST_METHOD']='POST'; $_SERVER['HTTP_ORIGIN']='https://app.invalid';
    SiindiSessionBridge::bootstrap();
    bridgeAssert(!isset($_COOKIE['2fa_pending']) && !isset($_SESSION['2fa_pending_user']), 'Residual source pending must never override the authenticated host account');
    $own=(new JwtHandler())->decrypt($_COOKIE['session']);
    bridgeAssert(($own->data->usu_doc ?? '')==='00000042' && !isset($own->data->sigodt_nonce), 'Source operations use own host identity without a login proof nonce');
};
$cases['cryptographic completion belongs to the current challenge only'] = function () {
    $_SESSION=[]; $_COOKIE=[]; SiindiSessionBridge::beginPending(bridgePending());
    $jwt=new JwtHandler(); $payload=$_SESSION['2fa_pending_user']; $payload['dfa']=1;
    $envelope=$jwt->decrypt($jwt->encrypt($payload,'session'));
    $proof=SiindiSessionBridge::completionProof($envelope);
    bridgeAssert(($proof['kind']??'')==='siindi_completion' && $proof['usu_id']===900, 'Correlated original completion accepted');
    foreach (['sigodt_nonce'=>'stale','usu_id'=>901,'usu_doc'=>'00000043','usu_tipo'=>'C'] as $field=>$value) {
        $bad=clone $envelope; $bad->data=clone $envelope->data; $bad->data->$field=$value;
        bridgeAssert(SiindiSessionBridge::completionProof($bad)===null, 'Reject wrong '.$field);
    }
    $bad=clone $envelope; $bad->type='blanqueamiento'; bridgeAssert(SiindiSessionBridge::completionProof($bad)===null,'Temporary token is not full session');
    bridgeAssert(SiindiSessionBridge::completionProof($jwt->decrypt('invalid'))===null, 'Invalid cipher is not proof');
    $_SESSION['2fa_pending_SIGODT']['expires_at']=time()-1;
    bridgeAssert(SiindiSessionBridge::completionProof($envelope)===null, 'Expired host challenge denied');
    $_SESSION=[]; bridgeAssert(SiindiSessionBridge::completionProof($envelope)===null, 'No replay after pending consumed');
};
$cases['recovery and first-access authority are separate'] = function () {
    $_SESSION=[]; $_COOKIE=[];
    SiindiSessionBridge::startPasswordContext(['usu_doc'=>'00000042','usu_tipo'=>'P','usu_id'=>900],'recovery');
    bridgeAssert(is_string($_SESSION['csrf_token'] ?? null) && strlen($_SESSION['csrf_token'])===64, 'Temporary stage retains existing controller CSRF contract');
    bridgeAssert(SiindiSessionBridge::passwordContext('recovery')!==null && SiindiSessionBridge::passwordContext('first_access')===null,'Lookup is not permission to change password');
    SiindiSessionBridge::advancePasswordContext('recovery_sent');
    bridgeAssert(SiindiSessionBridge::passwordContext('recovery_sent')!==null,'Delivery stage');
    SiindiSessionBridge::advancePasswordContext('first_access');
    bridgeAssert(SiindiSessionBridge::passwordContext('recovery')===null && SiindiSessionBridge::passwordContext('first_access')!==null,'Verified stage rotates');
    $_SESSION['sigodt_password_context']['expires_at']=time()-1;
    bridgeAssert(SiindiSessionBridge::passwordContext()===null,'Expired reset denied');
    SiindiSessionBridge::clearPasswordContext();
    bridgeAssert(!isset($_SESSION['sigodt_password_context'], $_SESSION['blanqueamiento_user']), 'Reset authority removed');
};
$cases['shared connection and cookie semantics'] = function () {
    bridgeAssert((new ReflectionMethod(Conectar::class,'conexion'))->isPublic(),'Original module connection is callable');
    $jwt=new JwtHandler();
    bridgeAssert($jwt->getCookieOptions(-3600,'/SIGODT/')['expires']<time(),'Negative TTL actually deletes');
    bridgeAssert($jwt->getCookieOptions(0,'/sigur/')['expires']===0,'Previous session cookie preserved');
    $round=$jwt->decrypt($jwt->encrypt(['usu_doc'=>'00000042'],'blanqueamiento',1900));
    bridgeAssert($round->type==='blanqueamiento' && $round->data->usu_doc==='00000042','Central JWE compatibility retained');
};
$failed=0;
foreach ($cases as $name=>$case) {
    try { $case(); echo "PASS: $name\n"; }
    catch (Throwable $error) { $failed++; echo "FAIL: $name: ".$error->getMessage()."\n"; }
}
echo count($cases)." rules, $failed failures\n";
ob_end_flush();
exit($failed?1:0);
