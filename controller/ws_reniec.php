<?php

require_once("../config/conexion.php");
if (!isset($_SESSION["usua_id_SIGODT"]) && !isset($_SESSION["usua_id_siagth"])) {
    header("Location:" . Conectar::ruta() . "view/404/");
    exit();
}

// Incluye la clase TokenHelper si no está ya incluida
class TokenHelper {
    public static function getKey(): string
    {
        return Conectar::getEnv('PIDE_SECRET_KEY', 'sgd*2023');
    }

    public static function encrypt(string $plain): string
    {
        return openssl_encrypt($plain, 'AES-128-ECB', self::getKey());
    }

    public static function decrypt(string $cipher): ?string
    {
        return openssl_decrypt($cipher, 'AES-128-ECB', self::getKey());
    }   
}

function response($message, $data = [], $suc = true, $httpStatusCode = 200){
    $status = $httpStatusCode;
    $success = $suc;
    $timestamp = date("Y-m-d H:i:s");
    http_response_code($httpStatusCode);
    echo json_encode(compact('timestamp', 'status', 'success', 'message', 'data'), JSON_UNESCAPED_UNICODE);
    exit();
}

$usuario = Conectar::getEnv('PIDE_RENIEC_USER', '20250001');
$contrasena = Conectar::getEnv('PIDE_RENIEC_PASS', '20250001@');
$dni = $_GET['dni'] ?? null;
if (!$dni) {
    response("Debe ingresar un DNI", [], false, 400);
}

// Generar token
$token = TokenHelper::encrypt($contrasena);

// URL del endpoint al que quieres enviar la solicitud
$pideBase = rtrim(Conectar::getEnv('PIDE_BASE_URL', 'https://www.munichiclayo.gob.pe/'), '/') . '/';
$url = $pideBase . 'Pide/Reniec/' . urlencode($dni);

// Crear los datos POST
$data = [
    'usu_dni' => $usuario,
    'usu_contrasena' => $token
];

// Usar cURL para enviar la solicitud
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

// Ejecutar y capturar la respuesta
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

header('Content-Type: application/json; charset=utf-8');

echo "$response";
