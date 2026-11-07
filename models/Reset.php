<?php
/** Source Reset client: system authentication, then POST ws/reset.php. No local OTP. */
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../helpers/SiindiSessionBridge.php';
Conectar::initSession();
SiindiSessionBridge::requireTrustedPost();
$doc = $_POST['usu_doc'] ?? ($_POST['usu_dni'] ?? null);
if (!is_string($doc) || !preg_match('/^[0-9]{8}$/D', $doc)) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success'=>false,'message'=>'Debe ingresar un documento válido.']);
    exit;
}
define('SIINDI_PASSWORD_LIBRARY_ONLY', true);
require_once __DIR__ . '/../controller/clave.php';
$base = sigurPasswordBase();
$token = getSigurSystemToken($base);
$response = $token ? sigurPasswordRequest($base . 'ws/reset.php', ['usu_doc'=>$doc,'usu_tipo'=>'P'], $token) : null;
$identity = $response['data'] ?? null;
if (!is_array($identity) || ($identity['usu_doc'] ?? null) !== $doc || ($identity['usu_tipo'] ?? 'P') !== 'P'
    || ((array_key_exists('success', $response) || array_key_exists('status', $response)) && !sigurPasswordAcknowledged($response))) {
    http_response_code(502);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success'=>false,'message'=>'No se pudo procesar la solicitud de recuperación.']);
    exit;
}
SiindiSessionBridge::clearLogin();
SiindiSessionBridge::startPasswordContext($identity, 'recovery');
header('Location: ' . Conectar::ruta() . 'seguridad/recuperar_contrasena/views/');
exit;
