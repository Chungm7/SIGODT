<?php
/** Exact compatibility dispatch for the unchanged SIINDI password screens. */
require_once __DIR__ . '/../config/conexion.php';
Conectar::initSession();
require_once __DIR__ . '/../helpers/SiindiSessionBridge.php';
SiindiSessionBridge::requireTrustedPost();
$selectors = [
    'clave.php?op=actualizar'=>'actualizar',
    'clave.php?op=token'=>'token',
    'clave.php?op=validar_token'=>'validar_token',
    'clave.php?op=blanqueamiento'=>'blanqueamiento',
];
$selector = $_POST['api'] ?? null;
if (!is_string($selector) || !isset($selectors[$selector])) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success'=>false,'message'=>'Operación de seguridad no reconocida.']);
    exit;
}
// Origin proof precedes authority lookup and server CSRF forwarding.
$op = $selectors[$selector];
$context = SiindiSessionBridge::passwordContext();
$authorized = $op === 'actualizar'
    ? !empty($_SESSION['usua_id_SIGODT']) && !empty($_SESSION['sigur_usuario_id_SIGODT']) && !empty($_SESSION['usua_dni_SIGODT'])
    : ($context && in_array($context['stage'], match ($op) {
        'token'=>['recovery','recovery_sent'], 'validar_token'=>['recovery_sent'], default=>['first_access'],
    }, true));
if (!$authorized) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success'=>false,'message'=>'Sesión no válida o expirada.']);
    exit;
}
$_POST['csrf_token'] = $_SESSION['csrf_token'] ?? null;
$_GET = ['op'=>$op];
require __DIR__ . '/../controller/clave.php';
