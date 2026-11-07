<?php
/** Public password entries, real bridge, fake transport/JWE, scratch-only fixture writes. */
function pwAssert($ok, $why) { if (!$ok) throw new RuntimeException($why); }
function pwRun($entry, $session, $post, $mode='ok', $origin='https://host.invalid', $method='POST', $query=[]) {
    $root=sys_get_temp_dir().'/external-password-'.bin2hex(random_bytes(5));
    foreach (['auth','controller','models','helpers','config','seguridad/recuperar_contrasena/views','seguridad/primer_acceso/views','seguridad/actualizar_contrasena/views','public/componente-spinner'] as $d) mkdir("$root/$d",0700,true);
    foreach (['auth/proxy.php','controller/clave.php','models/Recuperar.php','helpers/SiindiSessionBridge.php','helpers/Captcha.php','helpers/Recaptcha.php','index.php','seguridad/recuperar_contrasena/views/index.php','seguridad/primer_acceso/views/index.php','seguridad/actualizar_contrasena/views/index.php','public/componente-spinner/spinner.php'] as $f) if(is_file(__DIR__.'/../'.$f)) copy(__DIR__.'/../'.$f,"$root/$f");
    file_put_contents("$root/config/conexion.php",'<?php class Conectar { function conexion(){return new class {function prepare($q){return new class {function execute($p){} function fetch($mode){return ["pers_nombre"=>"Fixture","pers_emailm"=>"fixture@example.invalid"];}};}};} static function initSession() {} static function ruta(){return "https://host.invalid/SIGODT/";} static function getCookiePath(){return "/SIGODT/";} static function isProduction(){return false;} static function isDevelopment(){return true;} static function getEnv($k,$d=null){return ["SIGUR_URL"=>"https://provider.invalid/","SIST_INIC"=>"TEST","SIST_PASS"=>"dummy"][$k]??($_ENV[$k]??$d);} } require_once __DIR__."/../helpers/SiindiSessionBridge.php"; SiindiSessionBridge::bootstrap();');
    file_put_contents("$root/config/jwt.php",'<?php class JwtHandler { function decrypt($token){return (object)["data"=>(object)($_SESSION["blanqueamiento_user"]??[])];} function encrypt($d,$t,$ttl=0){return "fixture";} function setCookie(...$args){} }');
    file_put_contents("$root/run.php", <<<'PHP'
<?php
foreach (['CURLOPT_URL','CURLOPT_RETURNTRANSFER','CURLOPT_POST','CURLOPT_POSTFIELDS','CURLOPT_HTTPHEADER','CURLOPT_TIMEOUT','CURLOPT_CONNECTTIMEOUT','CURLOPT_SSL_VERIFYPEER','CURLOPT_SSL_VERIFYHOST','CURLOPT_FOLLOWLOCATION','CURLINFO_HTTP_CODE'] as $i=>$k) define($k,$i+1);
$requests=[]; $recaptchaCalls=0;
function curl_init($url=null){return (object)['options'=>[CURLOPT_URL=>$url]];}
function curl_setopt($c,$k,$v){$c->options[$k]=$v;}
function curl_exec($c){$GLOBALS['requests'][]=$c->options; $url=$c->options[CURLOPT_URL]; if(str_ends_with($url,'/auth/')) return '{"data":{"token":"fake"}}'; if(str_ends_with($url,'ws/reset.php')) return json_encode(['data'=>['usu_doc'=>$GLOBALS['mode']==='mismatch'?'99999999':'00000042','usu_tipo'=>'P','usu_id'=>900]] + ($GLOBALS['mode']==='lookup-denied'?['status'=>false]:[])); return match($GLOBALS['mode']){'text'=>'OK','empty'=>'{}','denied'=>'{"success":false}','loose'=>'{"success":"true"}','status'=>'{"status":200}',default=>'{"success":true}'};}
function curl_errno($c){return $GLOBALS['mode']==='transport'?7:0;}
function curl_getinfo($c,$k){return $GLOBALS['mode']==='http'?503:200;}
function curl_close($c){}
$_ENV['SIST_INIC']='TEST'; extract(json_decode(stream_get_contents(STDIN),true)); $_SESSION=$session; $_POST=$post; $_GET=$query; $_COOKIE=[]; $_SERVER=['REQUEST_METHOD'=>$method,'HTTP_ORIGIN'=>$origin,'SCRIPT_FILENAME'=>__DIR__.'/'.$entry];
if (str_starts_with($mode,'recaptcha-')) { $_ENV += ['RECAPTCHA_ENABLED'=>'true','RECAPTCHA_SITE_KEY'=>'fake-site','RECAPTCHA_SECRET_KEY'=>'fake-secret']; require_once __DIR__.'/helpers/Recaptcha.php'; Recaptcha::setHttpClient(function(){ $GLOBALS['recaptchaCalls']++; return json_encode(['success'=>$GLOBALS['mode']==='recaptcha-pass','action'=>'login','score'=>0.9]); }); }
chdir(__DIR__); ob_start(); register_shutdown_function(function(){echo json_encode(['body'=>ob_get_clean(),'session'=>$_SESSION,'requests'=>$GLOBALS['requests'],'status'=>http_response_code()?:200,'recaptchaCalls'=>$GLOBALS['recaptchaCalls']]);}); require __DIR__.'/'.$entry;
PHP
    );
    $p=proc_open([PHP_BINARY,'-n','-d','error_reporting=24575',"$root/run.php"],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);
    fwrite($pipes[0],json_encode(compact('entry','session','post','mode','origin','method','query'))); fclose($pipes[0]);
    $out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$exit=proc_close($p);
    pwAssert($exit===0 && $err==='',"exit=$exit stderr=$err stdout=$out"); $r=json_decode($out,true);pwAssert(is_array($r),'structured stdout '.$out);$r['json']=json_decode($r['body'],true);return $r;
}
function pwContext($stage){return ['csrf_token'=>str_repeat('a',64),'sigodt_password_context'=>['identity'=>['usu_doc'=>'00000042','usu_tipo'=>'P'],'stage'=>$stage,'nonce'=>'test','expires_at'=>time()+600]];}
function pwReject($r,$s,$status=403){pwAssert($r['status']===$status && ($r['json']['success']??null)===false && is_string($r['json']['message']??null),'rejection status/body');pwAssert($r['session']===$s && $r['requests']===[] && $r['recaptchaCalls']===0,'rejection changes no state/provider counters');}
$s=pwContext('recovery');
foreach (['','null','https://evil.invalid','https://host.invalid:444','https://host.invalid/path'] as $origin) pwReject(pwRun('auth/proxy.php',$s,['api'=>'clave.php?op=token','metodo'=>'1'],'ok',$origin),$s);
pwReject(pwRun('auth/proxy.php',$s,['api'=>'clave.php?op=token'],'ok','https://host.invalid','GET'),$s);
foreach (['clave.php?op=regresar','https://evil.invalid','../clave.php?op=token','clave.php?op=token&x=1',[]] as $selector) pwReject(pwRun('auth/proxy.php',$s,['api'=>$selector]),$s,400);
$expired=pwContext('recovery');$expired['sigodt_password_context']['expires_at']=time()-1;pwReject(pwRun('auth/proxy.php',$expired,['api'=>'clave.php?op=token','metodo'=>'1']),$expired);
foreach (['token'=>['metodo'=>[]],'validar_token'=>['codigo'=>'123456'],'blanqueamiento'=>['pers_pass_nueva'=>'fake']] as $op=>$input) pwReject(pwRun('auth/proxy.php',$s,['api'=>'clave.php?op='.$op]+$input),$s,$op==='token'?400:403);
foreach (['metodo'=>['', '3', [], 1], 'codigo'=>['', '12345', [], '!!!!!!'], 'pers_pass_nueva'=>['', [], str_repeat('x',1025)]] as $field=>$values) {
    $op=['metodo'=>'token','codigo'=>'validar_token','pers_pass_nueva'=>'blanqueamiento'][$field];
    $s=pwContext(['token'=>'recovery','validar_token'=>'recovery_sent','blanqueamiento'=>'first_access'][$op]);
    foreach ($values as $value) pwReject(pwRun('auth/proxy.php',$s,['api'=>'clave.php?op='.$op,$field=>$value]),$s,400);
}
foreach (['recovery','recovery_sent'] as $stage){$s=pwContext($stage);$r=pwRun('auth/proxy.php',$s,['api'=>'clave.php?op=token','metodo'=>'2','usu_doc'=>'99999999']);pwAssert($r['json']['success']===true && $r['session']['sigodt_password_context']['stage']==='recovery_sent','send/resend');parse_str($r['requests'][1][4],$b);pwAssert($b===['usu_doc'=>'00000042','usu_tipo'=>'P','metodo'=>'2'],'server identity/source method');}
foreach (['text','empty','denied','transport','http'] as $mode){$s=pwContext('recovery_sent');$r=pwRun('auth/proxy.php',$s,['api'=>'clave.php?op=validar_token','codigo'=>'123456'],$mode);pwAssert($r['json']['success']===false && $r['session']===$s && count($r['requests'])===($mode==='transport'||$mode==='http'?1:2),'ambiguous verification unchanged '.$mode);}
foreach (['ok','loose','status'] as $mode){$r=pwRun('auth/proxy.php',pwContext('recovery_sent'),['api'=>'clave.php?op=validar_token','codigo'=>'123456'],$mode);pwAssert($r['json']['success']===true && $r['session']['sigodt_password_context']['stage']==='first_access','explicit acknowledgement '.$mode);}
$s=pwContext('first_access');$post=['api'=>'clave.php?op=blanqueamiento','pers_pass_nueva'=>'dummy-new'];$r=pwRun('auth/proxy.php',$s,$post,'denied');pwAssert($r['json']['success']===false && $r['session']===$s,'failed reset retains grant');$r=pwRun('auth/proxy.php',$s,$post);pwAssert($r['json']['success']===true && !isset($r['session']['sigodt_password_context']) && $r['json']['enlace']==='https://host.invalid/SIGODT/index.php','reset consumes grant');
$s=['usua_id_SIGODT'=>42,'sigur_usuario_id_SIGODT'=>900,'usua_dni_SIGODT'=>'00000042','csrf_token'=>str_repeat('a',64)];$post=['api'=>'clave.php?op=actualizar','usu_pass_actual'=>'dummy-old','usu_pass_nueva'=>'dummy-new','usu_doc'=>'99999999'];$r=pwRun('auth/proxy.php',$s,$post);pwAssert($r['json']['success']===true && $r['session']===$s && $r['json']['enlace']==='https://host.invalid/SIGODT/view/setting/','regular update/no logout');
foreach($r['requests'] as $req) pwAssert($req[8]===true && $req[9]===2 && $req[10]===false && !str_contains($req[1],'dummy') && !str_contains($req[1],'/sigur/sigur/'),'verified transport');
pwReject(pwRun('controller/clave.php',$s,[],'ok','https://host.invalid','POST',['op'=>'actualizar']),$s);
foreach (['recuperar_contrasena'=>'recovery','primer_acceso'=>'first_access'] as $screen=>$stage) {
    $entry='seguridad/'.$screen.'/views/index.php';
    $r=pwRun($entry,pwContext($stage),[],'ok','https://host.invalid','GET');
    pwAssert($r['status']===200 && str_contains($r['body'],'main-card') && str_contains($r['body'],'auth/proxy.php') && $r['requests']===[],'original public screen '.$screen);
    $r=pwRun($entry,[],[],'ok','https://host.invalid','GET');pwAssert($r['status']===302 && $r['body']==='' && $r['session']===[] && $r['requests']===[],'screen denies absent grant '.$screen);
}
// Local root dispatch: direct recovery via models/Recuperar.php with trusted origin and identity validation.
$s=pwContext('first_access')+['2fa_pending_SIGODT'=>['challenge_id'=>'old']];
$post=['usu_doc'=>'00000042'];
foreach (['','null','https://evil.invalid'] as $origin) pwReject(pwRun('models/Recuperar.php',$s,$post,'ok',$origin),$s);
pwReject(pwRun('models/Recuperar.php',$s,$post+[],'ok','https://host.invalid','GET'),$s);
pwReject(pwRun('models/Recuperar.php',$s,array_replace($post,['usu_doc'=>[]])),$s,400);
$r=pwRun('models/Recuperar.php',$s,$post,'lookup-denied');pwAssert($r['status']===502 && $r['json']['success']===false && $r['session']===$s && count($r['requests'])===2,'explicit lookup denial overrides identity data');
$r=pwRun('models/Recuperar.php',$s,$post,'mismatch');pwAssert($r['status']===502 && $r['json']['success']===false && $r['session']===$s && count($r['requests'])===2,'mismatched lookup preserves old authority');
$r=pwRun('models/Recuperar.php',$s,$post);pwAssert($r['status']===302 && $r['body']==='' && count($r['requests'])===2 && $r['session']['sigodt_password_context']['stage']==='recovery' && !isset($r['session']['2fa_pending_SIGODT']),'lookup clears previous lifecycle then starts recovery');
parse_str($r['requests'][1][4],$fields);pwAssert($fields===['usu_doc'=>'00000042','usu_tipo'=>'P'] && $r['requests'][1][1]==='https://provider.invalid/sigur/ws/reset.php','source lookup protocol');
$r=pwRun('index.php',[],[],'ok','https://host.invalid','GET');pwAssert(str_contains($r['body'],'name="usu_dni"') && str_contains($r['body'],'name="usu_pass"') && str_contains($r['body'],'id="recuperar"') && str_contains($r['body'],'<span>Ingresar</span>') && $r['requests']===[],'regular login retained');
echo "External password entries: PASS\n";
