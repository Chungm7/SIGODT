<?php

require_once("../config/conexion.php");
if (!isset($_SESSION["usua_id_siagth"])) {
    header("Location:" . Conectar::ruta() . "view/404/");
}
// Incluye la clase TokenHelper si no está ya incluida
class TokenHelper {
    const SECRET_KEY = 'sgd*2023'; // Debe coincidir con el servidor

    public static function encrypt(string $plain): string
    {
        return openssl_encrypt($plain, 'AES-128-ECB', self::SECRET_KEY);
    }

    public static function decrypt(string $cipher): ?string
    {
        return openssl_decrypt($cipher, 'AES-128-ECB', self::SECRET_KEY);
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
// Datos de prueba - usando las credenciales exactas de la base de datos
$usuario = '123';
$contrasena = '123****';  // Exactamente como está en la base de datos
$dni = $_GET['dni'] ?? null;
if (!$dni) {
    response("Debe ingresar un DNI", [], false, 400);
}

// Generar token
$token = TokenHelper::encrypt($contrasena);

// URL del endpoint al que quieres enviar la solicitud
$url = 'https://munichiclayo.gob.pe/Pide/Reniec/75143409'; // cambia esto según corresponda

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
