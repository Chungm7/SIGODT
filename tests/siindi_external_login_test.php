<?php
/** Isolated HTTP fixture: unchanged source controller -> real JWE cookie -> host bootstrap.
 * Run only inside the documented read-only, network-unshared sandbox. */
function externalAssert($ok, $why) { if (!$ok) throw new RuntimeException($why); }
function externalFixture() {
    $root = sys_get_temp_dir().'/external-login-'.bin2hex(random_bytes(6));
    foreach (['config','helpers','models','view/inicio','seguridad/2fa/controllers','seguridad/2fa/models','seguridad/2fa/services','seguridad/2fa/views','seguridad/primer_acceso/views','seguridad/recuperar_contrasena/views','seguridad/actualizar_contrasena/views','public/componente-spinner'] as $dir) mkdir($root.'/'.$dir,0700,true);
    $files = ['config/conexion.php','config/jwt.php','config/jwe.php','helpers/SiindiSessionBridge.php','helpers/Captcha.php','helpers/Recaptcha.php','models/Usuario.php','seguridad/2fa/controllers/TwoFactorController.php','seguridad/2fa/models/TwoFactorModel.php','seguridad/2fa/services/TwoFactorService.php'];
    foreach (['selector_metodo','desafio_login','enrolar_login','perfil_2fa','modal_enrolamiento'] as $view) $files[]='seguridad/2fa/views/'.$view.'.php';
    foreach (['primer_acceso','recuperar_contrasena','actualizar_contrasena'] as $page) $files[]='seguridad/'.$page.'/views/index.php';
    $files[]='public/componente-spinner/spinner.php';
    foreach ($files as $file) copy(__DIR__.'/../'.$file,$root.'/'.$file);
    // The actual JWT dependency remains read-only; no duplicate implementation.
    symlink(realpath(__DIR__.'/../vendor'),$root.'/vendor');
    file_put_contents($root.'/models/Area.php','<?php class Area { function sincronizar_area_defecto($p,$d) { $GLOBALS["state"]["areas"][]=[$p,$d]; } }');
    file_put_contents($root.'/seguridad/2fa/services/SigurMailer.php','<?php class SigurMailer { static function sendVerificationCode($e,$c,$n,$t) { $GLOBALS["state"]["mail"]=$c; return ["success"=>true]; } }');
    file_put_contents($root.'/seguridad/2fa/services/SmtpMailer.php','<?php');
    // Preserve the actual landing's existing full-session guard (without its unrelated shell/assets).
    file_put_contents($root.'/view/inicio/index.php','<?php require_once __DIR__."/../../config/conexion.php"; if (isset($_SESSION["usua_id_SIGODT"])) echo "HOST:".$_SESSION["usua_id_SIGODT"]; else header("Location:".Conectar::ruta()."view/404/");');
    file_put_contents($root.'/router.php', <<<'PHP'
<?php
error_reporting(E_ALL & ~E_DEPRECATED);
foreach (['CURLOPT_URL','CURLOPT_POST','CURLOPT_POSTFIELDS','CURLOPT_RETURNTRANSFER','CURLOPT_TIMEOUT','CURLOPT_HTTPHEADER','CURLOPT_CONNECTTIMEOUT'] as $i=>$key) define($key,$i+1);
function curl_init() { return (object)['opts'=>[]]; }
function curl_setopt($c,$k,$v) { $c->opts[$k]=$v; }
function curl_exec($c) {
    $GLOBALS['state']['provider']++;
    if (str_contains($c->opts[CURLOPT_URL],'/auth/')) return '{"data":{"token":"fixture-token"}}';
    $scenario=$GLOBALS['state']['scenario'];
    if ($scenario==='authfail') return '{"status":403,"success":false,"message":"Denied"}';
    if (in_array($scenario,['whitening','forgedwhitening','ambiguouswhitening'],true)) return json_encode(['status'=>$scenario==='forgedwhitening'?403:200,'success'=>$scenario==='ambiguouswhitening'?'false':$scenario==='whitening','detalle'=>'Blanqueamiento de clave','data'=>['url'=>'untrusted-redirect']]);
    return '{"status":200,"success":true,"data":{"usu_id":900,"pers_id":42}}';
}
function curl_errno($c) { return 0; }
function curl_close($c) {}
class FixtureStatement {
    private $sql; private $bind=[];
    function __construct($sql) { $this->sql=$sql; }
    function bindValue($k,$v,$type=null) { $this->bind[$k]=$v; }
    function execute($args=[]) {
        $this->bind=$args+$this->bind;
        $s=&$GLOBALS['state']; $s['sql'][]=[$this->sql,$this->bind];
        if (str_contains($this->sql,'tb_historial_sesion')) {
            $s['history_attempts']++;
            if ($s['scenario']==='historyfail') throw new RuntimeException('fixture history failure');
            $s['history']++;
        }
        if ($s['scenario']==='enrollmentreadfail' && str_contains($this->sql,'SELECT seus_id')) throw new RuntimeException('fixture enrollment read failure');
        if (str_contains($this->sql,'INSERT INTO public.tb_seguridad_usuario_2fa')) {
            if (!is_array($s['record'])) $s['record']=['seus_id'=>7,'seus_usuario_id'=>$this->bind[':usu_id'],'seus_usuario_doc'=>$this->bind[':usu_doc'],'seus_marca_tiem'=>0,'seus_bloqueo_temp'=>null];
            $s['record']['seus_llave_secr']=$this->bind[':secret_key']; $s['record']['seus_esta_vinc']=false; $s['factor_writes']++;
        }
        if (str_contains($this->sql,'SET seus_esta_vinc')) { $s['record']['seus_esta_vinc']=true; $s['factor_writes']++; }
        if (str_contains($this->sql,'seus_marca_tiem    = :last_ts')) { $s['record']['seus_marca_tiem']=$this->bind[':last_ts']; $s['factor_writes']++; }
        return true;
    }
    function fetch($mode=null) {
        $s=$GLOBALS['state'];
        if (str_contains($this->sql,'tb_permiso')) {
            if ($s['scenario']==='revoked') return false;
            return ['pers_id'=>$s['scenario']==='changedperson'?43:42,'pers_dni'=>'00000042','pers_nombre'=>'Fixture','pers_apelpat'=>'Person','pers_apelmat'=>'Test','pers_estado'=>'A','pers_emailm'=>'fixture@example.invalid','pers_celu01'=>'900000001','pers_foto'=>'','perf_id'=>$s['scenario']==='changedpermission'?2:8,'perf_nombre'=>$s['scenario']==='changedpermission'?'CONSULTA':'DIGITADOR','depe_id'=>$s['scenario']==='changedpermission'?93:92];
        }
        if (str_contains($this->sql,'tb_historial_sesion')) return ['hise_id'=>101];
        if (str_contains($this->sql,'SELECT pers_emailm')) return ['pers_emailm'=>'fixture@example.invalid'];
        if (str_contains($this->sql,'SELECT seus_id')) return $s['record'];
        return false;
    }
}
class FixturePdo { function prepare($sql) { return new FixtureStatement($sql); } }
$stateFile=__DIR__.'/state.json';
$state=is_file($stateFile)?json_decode(file_get_contents($stateFile),true):['scenario'=>'ok','sql'=>[],'provider'=>0,'history'=>0,'history_attempts'=>0,'areas'=>[],'factor_writes'=>0];
$_ENV=['APP_URL'=>'http://127.0.0.1:'.$_SERVER['SERVER_PORT'].'/SIGODT/','APP_NAME'=>'SIGODT','SIST_INIC'=>'SIGODT','JWT_SECRET'=>'fixture-signing-only','JWE_SECRET'=>'fixture-encryption-only','SIGUR_URL'=>'https://provider.invalid/sigur/'];
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
// Install the actual stable config and its DB double before setting a routed script.
$_SERVER['SCRIPT_FILENAME']=__FILE__;
require __DIR__.'/config/conexion.php';
Conectar::$conexionDouble=new FixturePdo();
require __DIR__.'/seguridad/2fa/services/TwoFactorService.php';
if (!isset($state['record'])) $state['record']=['seus_id'=>7,'seus_usuario_id'=>900,'seus_usuario_doc'=>'00000042','seus_llave_secr'=>TwoFactorService::encryptSecret('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ'),'seus_esta_vinc'=>true,'seus_marca_tiem'=>0,'seus_bloqueo_temp'=>null];
register_shutdown_function(function() use ($stateFile) { file_put_contents($stateFile,json_encode($GLOBALS['state'])); });
if ($path==='/fixture') {
    $input=json_decode(file_get_contents('php://input'),true)?:[];
    if (isset($input['scenario'])) $state['scenario']=$input['scenario'];
    if (isset($input['session'])) $_SESSION=$input['session'];
    if (isset($input['patch'])) foreach ($input['patch'] as $k=>$v) $_SESSION[$k]=$v;
    if (isset($input['record'])) $state['record']=$input['record'];
    if (isset($input['cookie_payload'])) {
        require __DIR__.'/config/jwt.php';
        $payload=array_replace($_SESSION['2fa_pending_user']??[], $input['cookie_payload']);
        (new JwtHandler())->setCookie('session',(new JwtHandler())->encrypt($payload,$input['type']??'session',$input['ttl']??1500),0,'/SIGODT/');
    }
    if (!empty($input['code'])) {
        $secret=TwoFactorService::decryptSecret($state['record']['seus_llave_secr']);
        $state['code']=TwoFactorService::calculateCode($secret);
    }
    header('Content-Type: application/json'); echo json_encode(['session'=>$_SESSION,'state'=>$state]); return;
}
if ($path==='/login') {
    $_SESSION['captcha_text']='ABC234'; $_SESSION['captcha_issued_at']=time();
    if ($state['scenario']==='recaptcha') $_ENV['RECAPTCHA_ENABLED']='true';
    require __DIR__.'/models/Usuario.php'; (new Usuario())->login(); return;
}
if ($path==='/logout') { require __DIR__.'/models/Usuario.php'; (new Usuario())->logout(101); return; }
if ($path==='/finalize') { require __DIR__.'/models/Usuario.php'; echo json_encode(['success'=>(new Usuario())->finalizarLogin2FA()]); return; }
$relative=substr($path,strlen('/SIGODT/'));
$target=__DIR__.'/'.$relative;
if (!is_file($target)) { http_response_code(404); return; }
$_SERVER['SCRIPT_FILENAME']=$target;
SiindiSessionBridge::bootstrap();
require $target;
PHP
    );
    $socket=stream_socket_server('tcp://127.0.0.1:0',$errno,$error);
    externalAssert((bool)$socket,'Fixture port allocation'); $address=stream_socket_get_name($socket,false); fclose($socket);
    $proc=proc_open([PHP_BINARY,'-n','-d','display_errors=1','-d','session.save_path='.$root,'-S',$address,$root.'/router.php'],[0=>['pipe','r'],1=>['file',$root.'/stdout','a'],2=>['file',$root.'/stderr','a']],$pipes,$root);
    externalAssert(is_resource($proc),'Fixture server starts'); fclose($pipes[0]);
    return ['root'=>$root,'proc'=>$proc,'base'=>'http://'.$address,'cookies'=>[]];
}
function externalRequest(&$f,$path,$post=[], $origin=true,$method='POST') {
    $cookie=[]; foreach ($f['cookies'] as $k=>$v) $cookie[]=$k.'='.$v;
    $headers=['Connection: close','Cookie: '.implode('; ',$cookie)];
    if ($origin) $headers[]='Origin: '.$f['base'];
    $fixture=$path==='/fixture';
    $headers[]='Content-Type: '.($fixture?'application/json':'application/x-www-form-urlencoded');
    $context=stream_context_create(['http'=>['method'=>$method,'header'=>implode("\r\n",$headers),'content'=>$fixture?json_encode($post):http_build_query($post),'ignore_errors'=>true,'follow_location'=>0,'timeout'=>3]]);
    // One bounded startup wait, never poll/retry a failed request.
    if (empty($f['started'])) { usleep(150000); $f['started']=true; }
    $body=file_get_contents($f['base'].$path,false,$context);
    $responseHeaders=http_get_last_response_headers();
    externalAssert(is_string($body) && is_array($responseHeaders),'HTTP fixture request failed');
    externalAssert(!preg_match('/<(?:b|br)[^>]*>.*(?:Fatal error|Warning|Notice|Deprecated|Parse error)/s',$body),'Unexpected PHP diagnostic in body');
    preg_match('/ (\d{3}) /',$responseHeaders[0],$m); $status=(int)$m[1];
    foreach ($responseHeaders as $header) if (preg_match('/^Set-Cookie: ([^=]+)=([^;]*)/i',$header,$m)) {
        if ($m[2]==='' || $m[2]==='deleted') unset($f['cookies'][$m[1]]); else $f['cookies'][$m[1]]=$m[2];
    }
    externalAssert(file_get_contents($f['root'].'/stdout')==='','Unexpected server stdout');
    $stderr=file_get_contents($f['root'].'/stderr');
    // Built-in server lifecycle/access lines are expected; no PHP diagnostics are allowed.
    foreach (array_filter(explode("\n",trim($stderr))) as $line) {
        externalAssert((bool)preg_match('/^\[[^\]]+\] (?:PHP .*Development Server .* started|127\\.0\\.0\\.1:\\d+ (?:Accepted|Closing))$/',$line),'Unexpected server stderr: '.$line);
    }
    return ['status'=>$status,'body'=>$body,'headers'=>$responseHeaders,'json'=>json_decode($body,true)];
}
function externalSnapshot(&$f,$input=[]) { $r=externalRequest($f,'/fixture',$input); externalAssert($r['status']===200 && is_array($r['json']),'Fixture snapshot'); return $r['json']; }
function externalPrimary(&$f,$scenario='ok',$captcha='ABC234',$enrolled=true) {
    $record=externalSnapshot($f)['state']['record'];
    $record['seus_marca_tiem']=0; $record['seus_esta_vinc']=$enrolled;
    externalSnapshot($f,['scenario'=>$scenario,'session'=>[],'record'=>$record]);
    return externalRequest($f,'/login',['enviar'=>'si','usu_dni'=>'00000042','usu_pass'=>'fixture-password','captchaInput'=>$captcha]);
}
function externalLanding(&$f) { return externalRequest($f,'/SIGODT/view/inicio/index.php',[],true,'GET'); }
function externalEffects($snapshot) { return array_intersect_key($snapshot['state'],array_flip(['provider','history','history_attempts','areas','factor_writes'])); }
function externalNoPromotion($before,$after,$why,$historyAttempts=0) {
    externalAssert(empty($after['session']['usua_id_SIGODT']) && empty($after['session']['rol_id_SIGODT']) && empty($after['session']['usu_depe_id_SIGODT']),$why.': no full identity');
    $expected=externalEffects($before); $expected['history_attempts']+=$historyAttempts;
    externalAssert(externalEffects($after)===$expected,$why.': persisted effects/counters unchanged');
    $expectedState=$before['state']; $actualState=$after['state'];
    unset($expectedState['sql'],$actualState['sql']);
    $expectedState['history_attempts']+=$historyAttempts;
    externalAssert($actualState===$expectedState,$why.': all other persisted fixture data unchanged');
}
$rules=[];
$rules['pending']=function(&$f) {
    $r=externalPrimary($f); $s=externalSnapshot($f);
    externalAssert($r['status']===302 && $r['body']==='' && str_contains(implode('\n',$r['headers']),'selector_metodo.php'),'Primary pending redirect');
    $p=$s['session']['2fa_pending_SIGODT'];
    externalAssert(isset($s['session']['2fa_pending_user']['sigodt_nonce']) && $p['sigodt_nonce']===$s['session']['2fa_pending_user']['sigodt_nonce'],'Bridge pending alias and nonce');
    externalAssert($p['usu_id']===900 && $p['pers_id']===42 && $p['expires_at']<=time()+300,'Separate institutional/local identity and host expiry');
    externalAssert(!isset($s['session']['usua_id_SIGODT']) && !$s['state']['history'] && !$s['state']['areas'],'No primary promotion');
    externalSnapshot($f,['patch'=>['usua_id_SIGODT'=>99,'rol_id_SIGODT'=>1,'sigodt_password_context'=>['stage'=>'first_access','expires_at'=>time()+300]]]);
    $r=externalRequest($f,'/login',['enviar'=>'si','usu_dni'=>'00000042','usu_pass'=>'fixture-password','captchaInput'=>'ABC234']); $fresh=externalSnapshot($f);
    externalAssert($r['status']===302 && $r['body']==='' && !isset($fresh['session']['usua_id_SIGODT']) && !isset($fresh['session']['rol_id_SIGODT']) && !isset($fresh['session']['sigodt_password_context']) && $fresh['session']['2fa_pending_SIGODT']['sigodt_nonce']!==$p['sigodt_nonce'],'Fresh primary lifecycle removes old full/reset authority and rotates nonce');
    externalAssert(externalEffects($fresh)['history']===0 && !$fresh['state']['areas'],'Fresh lifecycle still creates no business session');
};
$rules['whitening']=function(&$f) {
    externalPrimary($f); $before=externalSnapshot($f);
    externalSnapshot($f,['scenario'=>'whitening']);
    $r=externalRequest($f,'/login',['enviar'=>'si','usu_dni'=>'00000042','usu_pass'=>'fixture-password','captchaInput'=>'ABC234']);
    $s=externalSnapshot($f);
    externalAssert($r['status']===302 && $r['body']==='' && str_contains(implode('\n',$r['headers']),'/SIGODT/seguridad/primer_acceso/views/'),'Local first-access route');
    externalAssert(($s['session']['sigodt_password_context']['stage']??'')==='first_access' && $s['session']['sigodt_password_context']['identity']['usu_doc']==='00000042','Provider-backed temporary context');
    externalAssert(!isset($s['session']['2fa_pending_SIGODT']) && !isset($s['session']['2fa_pending_user']) && !isset($s['session']['usua_id_SIGODT']),'Whitening clears pending, not login');
    foreach ($r['headers'] as $h) if (str_starts_with($h,'Set-Cookie:')) externalAssert(!str_contains($h,'path=/sigur/'),'No central cookie mutation');
    $page=externalRequest($f,'/SIGODT/seguridad/primer_acceso/views/index.php',[],true,'GET'); externalAssert($page['status']===200 && str_contains($page['body'],'Nueva Contraseña'),'First access bootstrap accepts temporary grant: '.$page['status'].' '.$page['body']);
    foreach (['forgedwhitening','ambiguouswhitening'] as $scenario) {
        externalPrimary($f,$scenario); $s=externalSnapshot($f); externalAssert(!isset($s['session']['sigodt_password_context']),'Failed/ambiguous provider cannot grant first access: '.$scenario);
    }
};
$rules['completion']=function(&$f) {
    externalPrimary($f); externalRequest($f,'/SIGODT/seguridad/2fa/controllers/TwoFactorController.php?op=enviar_codigo_correo');
    $s=externalSnapshot($f); externalAssert(isset($s['state']['mail']),'Original controller sends fixture email'); $r=externalRequest($f,'/SIGODT/seguridad/2fa/controllers/TwoFactorController.php?op=verificar_codigo_correo',['codigo_correo'=>$s['state']['mail']]);
    externalAssert($r['status']===200 && ($r['json']['success']??false) && isset($f['cookies']['session']),'Original email controller emits actual cookie');
    $before=externalSnapshot($f); externalAssert(empty($before['session']['usua_id_SIGODT']) && isset($before['session']['2fa_pending_SIGODT']),'Cookie emission alone never promotes');
    $r=externalLanding($f); $after=externalSnapshot($f);
    externalAssert($r['status']===200 && $r['body']==='HOST:42','Host landing promotes source cookie: '.$r['status'].' '.$r['body']);
    externalAssert($after['session']['sigur_usuario_id_SIGODT']===900 && $after['session']['usua_id_SIGODT']===42 && $after['session']['rol_id_SIGODT']===8 && $after['session']['rol_nombre_SIGODT']==='DIGITADOR','Host roles, never source default administrator');
    externalAssert($after['state']['history']===$before['state']['history']+1 && $after['state']['areas']===[[42,92]],'One history and area after completion');
    externalAssert(!isset($after['session']['2fa_pending_SIGODT']) && !isset($after['session']['2fa_verified_SIGODT']),'Pending receipt consumed');
    $again=externalLanding($f); $last=externalSnapshot($f); externalAssert($again['status']===200 && $again['body']==='HOST:42' && externalEffects($last)===externalEffects($after),'Landing refresh does not repeat promotion');
};
$rules['receipt']=function(&$f) {
    externalPrimary($f); $s=externalSnapshot($f); $p=$s['session']['2fa_pending_SIGODT'];
    $valid=['kind'=>'siindi_completion','sigodt_nonce'=>$p['sigodt_nonce']??'missing','challenge_id'=>$p['challenge_id'],'usu_doc'=>$p['usu_doc'],'usu_id'=>$p['usu_id'],'expires_at'=>time()+30];
    externalSnapshot($f,['patch'=>['2fa_verified_SIGODT'=>$valid]]);
    $r=externalRequest($f,'/finalize'); externalAssert($r['status']===200 && $r['json']===['success'=>true],'Finalizer accepts correlated source receipt, not invented method');
};
$rules['logout']=function(&$f) {
    externalPrimary($f); externalSnapshot($f,['patch'=>['sigodt_password_context'=>['stage'=>'first_access','expires_at'=>time()+100]]]);
    $r=externalRequest($f,'/logout'); $s=externalSnapshot($f);
    externalAssert($r['status']===302 && $r['body']==='' && $s['session']===[],'Logout destroys own session');
    $names=[]; foreach ($r['headers'] as $h) if (preg_match('/^Set-Cookie: ([^=]+)=/',$h,$m)) { $names[]=$m[1]; externalAssert(!str_contains($h,'path=/sigur/'),'Logout preserves central cookies'); }
    foreach (['session','2fa_pending','2fa','2fa_email_otp','2fa_revinculacion_ok','blanqueamiento','SIGODT_SESSID'] as $name) externalAssert(in_array($name,$names,true),'Logout clears '.$name);
};
function externalSourceCompletion(&$f,$branch='email') {
    $controller='/SIGODT/seguridad/2fa/controllers/TwoFactorController.php?op=';
    if ($branch==='email') {
        $send=externalRequest($f,$controller.'enviar_codigo_correo');
        externalAssert($send['status']===200 && ($send['json']['success']??false),'Source email send acknowledged');
        $s=externalSnapshot($f);
        $r=externalRequest($f,$controller.'verificar_codigo_correo',['codigo_correo'=>$s['state']['mail']]);
    } else {
        if ($branch==='enrollment') {
            $start=externalRequest($f,$controller.'iniciar_enrolamiento');
            externalAssert($start['status']===200 && ($start['json']['success']??false) && !empty($start['json']['secret']),'Original first enrollment during pending login');
        }
        $s=externalSnapshot($f,['code'=>true]);
        $r=externalRequest($f,$controller.($branch==='enrollment'?'confirmar_enrolamiento':'verificar_desafio'),[($branch==='enrollment'?'codigo_confirmacion':'codigo_2fa')=>$s['state']['code']]);
    }
    externalAssert($r['status']===200 && ($r['json']['success']??false) && isset($f['cookies']['session']),'Source '.$branch.' completes with actual cookie: '.$r['body']);
    externalAssert(($r['json']['redirect']??'')===$f['base'].'/SIGODT/view/inicio/','Source redirect contract');
    return $r;
}
$rules['totp-enrollment']=function(&$f) {
    foreach (['totp','enrollment'] as $branch) {
        externalPrimary($f,'ok','ABC234',$branch==='totp'); $start=externalSnapshot($f);
        externalSourceCompletion($f,$branch); $before=externalSnapshot($f);
        externalAssert($before['state']['history']===$start['state']['history'] && $before['state']['areas']===$start['state']['areas'] && empty($before['session']['usua_id_SIGODT']),'No business promotion at '.$branch.' source controller');
        externalAssert($before['state']['factor_writes']===$start['state']['factor_writes']+($branch==='totp'?1:2),'Original '.$branch.' persistence count');
        $r=externalLanding($f); $after=externalSnapshot($f);
        externalAssert($r['status']===200 && $r['body']==='HOST:42' && $after['state']['history']===$start['state']['history']+1 && count($after['state']['areas'])===count($start['state']['areas'])+1,'Host promotes '.$branch.' once');
        externalAssert($after['session']['pers_apelpat_SIGODT']==='Person' && $after['session']['pers_apelmat_SIGODT']==='Test' && $after['session']['historial_login_SIGODT']===101,'Prior surname/history behavior');
    }
};
$rules['enrollment-eligibility']=function(&$f) {
    $controller='/SIGODT/seguridad/2fa/controllers/TwoFactorController.php?op=';
    foreach (['iniciar_enrolamiento','confirmar_enrolamiento'] as $op) {
        externalPrimary($f); $before=externalSnapshot($f);
        $r=externalRequest($f,$controller.$op,['codigo_confirmacion'=>'123456']);
        $after=externalSnapshot($f);
        externalAssert($r['status']===403 && ($r['json']['success']??null)===false,'Linked factor cannot be replaced with primary-only authority: '.$op);
        externalNoPromotion($before,$after,'Linked-factor replacement denied');
        externalAssert($after['session']===$before['session'],'Denied replacement leaves private authority unchanged');
    }
    externalPrimary($f,'ok','ABC234',false); $before=externalSnapshot($f);
    $r=externalRequest($f,$controller.'confirmar_enrolamiento',['codigo_confirmacion'=>'123456']);
    $after=externalSnapshot($f);
    externalAssert($r['status']===403 && ($r['json']['success']??null)===false,'Confirmation requires eligible initialization in this challenge');
    externalNoPromotion($before,$after,'Uninitialized first enrollment denied');
    externalPrimary($f,'enrollmentreadfail'); $before=externalSnapshot($f);
    $r=externalRequest($f,$controller.'iniciar_enrolamiento'); $after=externalSnapshot($f);
    externalAssert($r['status']===503 && ($r['json']['success']??null)===false,'Enrollment lookup failure cannot mean not enrolled');
    externalNoPromotion($before,$after,'Enrollment lookup failed closed');
    // A genuine first setup with no previous row remains permitted.
    externalPrimary($f); externalSnapshot($f,['record'=>false]);
    externalSourceCompletion($f,'enrollment');
    externalAssert(externalLanding($f)['body']==='HOST:42','Initial no-record setup remains permitted');
    // An old initial-enrollment grant cannot survive a new login nonce.
    externalPrimary($f,'ok','ABC234',false);
    externalAssert(externalRequest($f,$controller.'iniciar_enrolamiento')['json']['success']??false,'Eligible initial enrollment starts');
    $old=externalSnapshot($f)['session']['2fa_initial_enrollment_SIGODT']??null;
    externalPrimary($f,'ok','ABC234',false);
    externalSnapshot($f,['patch'=>['2fa_initial_enrollment_SIGODT'=>$old]]); $before=externalSnapshot($f);
    $r=externalRequest($f,$controller.'confirmar_enrolamiento',['codigo_confirmacion'=>'123456']); $after=externalSnapshot($f);
    externalAssert($r['status']===403 && ($r['json']['success']??null)===false,'Stale initialization cannot authorize a new challenge');
    externalNoPromotion($before,$after,'Stale initialization denied');
    // Completed MFA still allows the original full-account replacement workflow.
    externalPrimary($f); externalSourceCompletion($f,'totp'); externalLanding($f);
    $before=externalSnapshot($f); externalSourceCompletion($f,'enrollment'); $after=externalSnapshot($f);
    externalAssert(($after['session']['usua_id_SIGODT']??0)===42 && $after['state']['history']===$before['state']['history'] && $after['state']['areas']===$before['state']['areas'],'Full-account rebind preserved without duplicate promotion');
};
$rules['fresh-permission']=function(&$f) {
    foreach (['changedpermission','revoked','changedperson','historyfail'] as $scenario) {
        externalPrimary($f); externalSourceCompletion($f,'totp');
        $before=externalSnapshot($f,['scenario'=>$scenario]);
        $r=externalLanding($f); $after=externalSnapshot($f);
        $queries=array_values(array_filter(array_slice($after['state']['sql'],count($before['state']['sql'])),fn($q)=>str_contains($q[0],'tb_permiso')));
        externalAssert(count($queries)===1 && $queries[0][1]===[':pers_id'=>42,':dni'=>'00000042'] && str_contains($queries[0][0],"perm.sist_id = 8 AND perm.perm_estado = 'A'") && str_contains($queries[0][0],"p.pers_estado = 'A'"),'Fresh actual system8 identity/permission query');
        if ($scenario==='changedpermission') {
            externalAssert($r['status']===200 && $r['body']==='HOST:42' && $after['session']['rol_id_SIGODT']===2 && $after['session']['rol_nombre_SIGODT']==='CONSULTA' && $after['session']['usu_depe_id_SIGODT']===93 && end($after['state']['areas'])===[42,93],'Changed authorization drives role and area, never cached/source profile');
        } else {
            externalAssert($r['status']===302 && $r['body']==='' && str_contains(implode('\n',$r['headers']),'/SIGODT/index.php'),'Landing rejection status/body: '.$scenario);
            externalNoPromotion($before,$after,$scenario,$scenario==='historyfail'?1:0);
            externalAssert(!isset($after['session']['2fa_pending_SIGODT']) && !isset($after['session']['2fa_verified_SIGODT']),'Denied completion consumes host pending');
        }
    }
};
$rules['completion-rejections']=function(&$f) {
    foreach (['wrong-account','wrong-document','wrong-nonce','wrong-type','expired-cookie','expired-pending','stale','missing-cookie','replayed'] as $case) {
        externalPrimary($f); $initial=externalSnapshot($f); $payload=$initial['session']['2fa_pending_user'];
        externalSourceCompletion($f);
        $cookie=$f['cookies']['session'];
        if ($case==='replayed') { externalLanding($f); externalSnapshot($f,['session'=>[]]); $f['cookies']['session']=$cookie; }
        elseif ($case==='missing-cookie') unset($f['cookies']['session']);
        elseif ($case==='stale') { externalPrimary($f); $f['cookies']['session']=$cookie; }
        elseif ($case==='expired-pending') { $p=$initial['session']['2fa_pending_SIGODT']; $p['expires_at']=time()-1; externalSnapshot($f,['patch'=>['2fa_pending_SIGODT'=>$p]]); }
        else {
            $input=['cookie_payload'=>$payload];
            if ($case==='wrong-account') $input['cookie_payload']['usu_id']=901;
            if ($case==='wrong-document') $input['cookie_payload']['usu_doc']='00000043';
            if ($case==='wrong-nonce') $input['cookie_payload']['sigodt_nonce']=str_repeat('0',64);
            if ($case==='wrong-type') $input['type']='blanqueamiento';
            if ($case==='expired-cookie') $input['ttl']=-5;
            externalSnapshot($f,$input);
        }
        $before=externalSnapshot($f); $r=externalLanding($f); $after=externalSnapshot($f);
        externalAssert($r['status']===302 && $r['body']==='','Rejected completion status/body: '.$case);
        externalNoPromotion($before,$after,$case);
        externalAssert(!isset($after['session']['2fa_pending_SIGODT']) && !isset($after['session']['2fa_verified_SIGODT']),'Rejected completion clears temporary authority: '.$case);
    }
};
$rules['receipt-rejections']=function(&$f) {
    externalPrimary($f); $initial=externalSnapshot($f); $p=$initial['session']['2fa_pending_SIGODT'];
    $valid=['kind'=>'siindi_completion','sigodt_nonce'=>$p['sigodt_nonce'],'challenge_id'=>$p['challenge_id'],'usu_doc'=>$p['usu_doc'],'usu_id'=>$p['usu_id'],'expires_at'=>time()+30];
    foreach (['kind'=>'email','sigodt_nonce'=>'wrong','challenge_id'=>'wrong','usu_doc'=>'00000043','usu_id'=>901,'expires_at'=>time()-1] as $field=>$value) {
        $receipt=$valid; $receipt[$field]=$value;
        $before=externalSnapshot($f,['patch'=>['2fa_verified_SIGODT'=>$receipt]]);
        $r=externalRequest($f,'/finalize'); $after=externalSnapshot($f);
        externalAssert($r['status']===200 && $r['body']==='{"success":false}' && $before['session']===$after['session'] && count($before['state']['sql'])===count($after['state']['sql']),'Rejected receipt leaves session/SQL unchanged: '.$field);
        externalNoPromotion($before,$after,'Receipt '.$field);
    }
};
$rules['origin-captcha-stages']=function(&$f) {
    foreach (['captcha','recaptcha','authfail','forgedwhitening'] as $case) {
        $before=externalSnapshot($f); $r=externalPrimary($f,$case,$case==='captcha'?'wrong':'ABC234'); $after=externalSnapshot($f);
        externalAssert($r['status']===302 && $r['body']==='' && empty($after['session']['2fa_pending_SIGODT']) && empty($after['session']['sigodt_password_context']),'Primary rejection preserves authority boundary: '.$case);
        if ($case==='captcha' || $case==='recaptcha') externalAssert(externalEffects($after)===externalEffects($before) && count($after['state']['sql'])===count($before['state']['sql']),'CAPTCHA/reCAPTCHA before provider and DB');
        else externalAssert($after['state']['history']===$before['state']['history'] && $after['state']['areas']===$before['state']['areas'],'Provider rejection no business effects');
    }
    externalPrimary($f); $before=externalSnapshot($f);
    foreach ([['POST',false],['GET',true]] as [$method,$origin]) {
        $r=externalRequest($f,'/SIGODT/seguridad/2fa/controllers/TwoFactorController.php?op=iniciar_enrolamiento',[],$origin,$method); $after=externalSnapshot($f);
        externalAssert($r['status']===403 && $r['body']==='{"success":false,"message":"Solicitud no autorizada."}' && $before['session']===$after['session'] && $before['state']===$after['state'],'Untrusted source request rejected before all state/DB/counters');
    }
    foreach (['pending','ordinary'] as $context) {
        if ($context==='ordinary') externalSnapshot($f,['session'=>['usua_id_SIGODT'=>42,'sigur_usuario_id_SIGODT'=>900,'usua_dni_SIGODT'=>'00000042']]);
        foreach (['primer_acceso','recuperar_contrasena'] as $page) {
            $before=externalSnapshot($f); $r=externalRequest($f,'/SIGODT/seguridad/'.$page.'/views/index.php',[],true,'GET'); $after=externalSnapshot($f);
            externalAssert($r['status']===302 && $r['body']==='' && $before['session']===$after['session'] && externalEffects($before)===externalEffects($after),'No reset authority from '.$context.': '.$page);
        }
    }
};
$rules['views']=function(&$f) {
    externalPrimary($f);
    foreach (['selector_metodo','enrolar_login','desafio_login'] as $view) {
        $r=externalRequest($f,'/SIGODT/seguridad/2fa/views/'.$view.'.php',[],true,'GET');
        $marker=['selector_metodo'=>'Fixture','enrolar_login'=>'qrContainerLogin','desafio_login'=>'Google Authenticator'][$view];
        externalAssert($r['status']===200 && str_contains($r['body'],$marker),'Original pending view renders through bootstrap: '.$view.' status='.$r['status']);
    }
    foreach (['seguridad/2fa/views/perfil_2fa.php','seguridad/2fa/views/modal_enrolamiento.php','seguridad/actualizar_contrasena/views/index.php'] as $path) {
        $before=externalSnapshot($f); $r=externalRequest($f,'/SIGODT/'.$path,[],true,'GET'); $after=externalSnapshot($f);
        externalAssert($r['status']===302 && $r['body']==='' && $before['session']===$after['session'] && $before['state']===$after['state'],'Pending cannot enter authenticated source route: '.$path);
    }
    $p=externalSnapshot($f)['session']['2fa_pending_SIGODT']; $p['expires_at']=time()-1;
    externalSnapshot($f,['patch'=>['2fa_pending_SIGODT'=>$p]]); $before=externalSnapshot($f);
    $r=externalRequest($f,'/SIGODT/seguridad/2fa/views/selector_metodo.php',[],true,'GET'); $after=externalSnapshot($f);
    externalAssert($r['status']===302 && $r['body']==='' && !isset($after['session']['2fa_pending_user']) && externalEffects($before)===externalEffects($after),'Expired host pending cannot render source alias');
};
$fail=0; $selected=$argv[1]??null;
foreach ($rules as $name=>$rule) {
    if ($selected!==null && $selected!==$name) continue;
    $f=externalFixture();
    try { $rule($f); echo "PASS $name\n"; }
    catch (Throwable $e) { $fail++; echo "FAIL $name: ".$e->getMessage()."\n"; }
    finally { proc_terminate($f['proc']); proc_close($f['proc']); }
}
exit($fail?1:0);
