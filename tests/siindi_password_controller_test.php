<?php
/** Retain prior regular-change contracts against public entries and the real bridge. */
require_once __DIR__ . '/siindi_external_password_test.php';
$s=['usua_id_SIGODT'=>42,'sigur_usuario_id_SIGODT'=>900,'usua_dni_SIGODT'=>'00000042','csrf_token'=>str_repeat('a',64)];
$post=['csrf_token'=>$s['csrf_token'],'usu_pass_actual'=>'dummy-old','usu_pass_nueva'=>'dummy-new','usu_doc'=>'attacker','usu_tipo'=>'C'];
$r=pwRun('controller/clave.php',$s,$post,'ok','https://host.invalid','POST',['op'=>'actualizar']);
pwAssert($r['json']['success']===true && $r['json']['enlace']==='https://host.invalid/SIGODT/view/setting/' && $r['session']===$s && count($r['requests'])===2,'regular direct change, no logout');
parse_str($r['requests'][1][4],$body);pwAssert($body['usu_doc']==='00000042' && $body['usu_tipo']==='P','identity from full PHP session');
foreach (['text','loose','denied','http','transport'] as $mode){$r=pwRun('controller/clave.php',$s,$post,$mode,'https://host.invalid','POST',['op'=>'actualizar']);pwAssert($r['json']['success']===false && $r['session']===$s && count($r['requests'])===($mode==='transport'||$mode==='http'?1:2),'regular fail closed '.$mode);}
foreach ([[],['2fa_pending_SIGODT'=>['usu_doc'=>'00000042'],'csrf_token'=>$s['csrf_token']]] as $anon){$r=pwRun('controller/clave.php',$anon,$post,'ok','https://host.invalid','POST',['op'=>'actualizar']);pwAssert($r['json']['success']===false && !$r['requests'] && $r['session']===$anon,'incomplete session has no authority');}
foreach (['GET','POST'] as $method) pwReject(pwRun('controller/clave.php',$s,[],'ok','https://host.invalid',$method,['op'=>'actualizar']),$s);
foreach (['blanqueamiento','token','validar_token'] as $op) pwReject(pwRun('controller/clave.php',$s,$post,'ok','https://host.invalid','POST',['op'=>$op]),$s);
echo "Password controller: PASS\n";
