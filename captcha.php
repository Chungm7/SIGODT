<?php
require_once __DIR__ . '/helpers/Captcha.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
header('X-Content-Type-Options: nosniff');
try {
    if (!class_exists('Conectar', false)) {
        require_once __DIR__ . '/config/conexion.php';
    }
    if (session_status() !== PHP_SESSION_ACTIVE) {
        if (class_exists('Conectar', false) && method_exists('Conectar', 'initSession')) {
            Conectar::initSession();
        } elseif (session_status() === PHP_SESSION_NONE) {
            if (!headers_sent()) {
                session_name('SIGODT_SESSID');
                $cookiePath = (class_exists('Conectar', false) && method_exists('Conectar', 'getCookiePath'))
                    ? Conectar::getCookiePath()
                    : '/SIGODT/';
                $isSecure = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
                    || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
                    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string)$_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
                session_set_cookie_params([
                    'lifetime' => 0,
                    'path'     => $cookiePath,
                    'domain'   => '',
                    'secure'   => $isSecure,
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]);
            }
            if (!@session_start()) {
                throw new RuntimeException('Sesión no disponible');
            }
        }
    }
    if (session_status() !== PHP_SESSION_ACTIVE) {
        throw new RuntimeException('Sesión no disponible');
    }
    Captcha::invalidate($_SESSION);
    if (($_GET['action'] ?? null) !== 'generateCaptcha') {
        http_response_code(400);
        echo json_encode(['message' => 'Acción inválida']);
    } else {
        echo json_encode(['captcha' => Captcha::issue($_SESSION)]);
    }
} catch (Throwable $error) {
    if (isset($_SESSION)) { Captcha::invalidate($_SESSION); }
    http_response_code(503);
    echo json_encode(['message' => 'No se pudo generar el CAPTCHA. Intente nuevamente.']);
}
