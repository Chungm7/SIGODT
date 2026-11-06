<?php
/** Source Reset client: system authentication, then POST ws/reset.php. No local OTP. */
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../helpers/SiindiSessionBridge.php';
require_once __DIR__ . '/../helpers/Captcha.php';
require_once __DIR__ . '/../helpers/Recaptcha.php';
Conectar::initSession();
SiindiSessionBridge::requireTrustedPost();
$doc = $_POST['usu_doc'] ?? null;
if (!is_string($doc) || !preg_match('/^[0-9]{8}$/D', $doc)) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success'=>false,'message'=>'Debe ingresar un documento válido.']);
    exit;
}
// Check against a copy: rejected requests must not damage an existing login/reset grant.
$captchaSession = $_SESSION;
if (!Captcha::consume($captchaSession, $_POST['captchaInput'] ?? null)
    || !Recaptcha::check($_POST['recaptchaToken'] ?? null, $_SERVER['REMOTE_ADDR'] ?? null)) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success'=>false,'message'=>'No se pudo verificar la seguridad del acceso.']);
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
    echo json_encode(['success'=>false,'message'=>'No se pudo procesar la solicitud de recuperación.']);
    exit;
}
// A successful lookup starts a new lifecycle, never attached to an old pending login.
Captcha::invalidate($_SESSION);
SiindiSessionBridge::clearLogin();
SiindiSessionBridge::startPasswordContext($identity, 'recovery');
header('Location: ' . Conectar::ruta() . 'seguridad/recuperar_contrasena/views/');
