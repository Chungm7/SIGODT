<?php
require_once __DIR__ . '/helpers/Captcha.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
header('X-Content-Type-Options: nosniff');
try {
    if (session_status() !== PHP_SESSION_ACTIVE && !@session_start()) {
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
