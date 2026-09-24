<?php
require_once("config/conexion.php");
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Función para generar un texto CAPTCHA aleatorio
function generateCaptchaText($length = 6) {
    $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $captchaText = '';
    for ($i = 0; $i < $length; $i++) {
        $captchaText .= $characters[mt_rand(0, strlen($characters) - 1)];
    }
    return $captchaText;
}

// Función para generar la imagen CAPTCHA
function generateCaptchaImage($captchaText) {
    $width = 56; // Ancho de la imagen
    $height = 19; // Alto de la imagen

    // Crear una imagen en blanco
    $image = imagecreatetruecolor($width, $height);

    // Definir colores
    $backgroundColor = imagecolorallocate($image, 255, 255, 255); // Blanco
    $textColor = imagecolorallocate($image, 0, 0, 0); // Negro

    // Rellenar la imagen con el color de fondo blanco
    imagefill($image, 0, 0, $backgroundColor);

    // Añadir texto al CAPTCHA
    $fontSize = 5; // Tamaño del texto (de 1 a 5 en fuentes integradas de GD)
    $textWidth = (int)(imagefontwidth($fontSize) * strlen($captchaText));
    $textHeight = (int)imagefontheight($fontSize);

    // Posicionar el texto en el centro de la imagen
    $x = (int)(($width - $textWidth) / 2);
    $y = (int)(($height - $textHeight) / 2);

    // Añadir el texto a la imagen
    imagestring($image, $fontSize, $x, $y, $captchaText, $textColor);

    // Capturar la salida de la imagen en un buffer
    ob_start();
    imagepng($image);
    $imageData = ob_get_contents();
    ob_end_clean();

    // Destruir la imagen en memoria
    imagedestroy($image);

    // Retornar la imagen codificada en base64
    return base64_encode($imageData);
}

// Controlador que maneja las acciones con un switch
$action = isset($_GET['action']) ? $_GET['action'] : 'default';

switch ($action) {
    case 'generateCaptcha':
        // Generar texto CAPTCHA
        $captchaText = generateCaptchaText();

        // Guardar el texto CAPTCHA en la sesión para su verificación
        $_SESSION['captcha_text'] = $captchaText;

        // Generar la imagen CAPTCHA en base64
        $captchaImageBase64 = generateCaptchaImage($captchaText);

        // Devolver la imagen en formato JSON (base64)
        echo json_encode([
            'captcha' => 'data:image/png;base64,' . $captchaImageBase64,
            'captcha_text' => $captchaText
        ]);
        break;

    default:
        echo json_encode(['message' => 'No action specified']);
        break;
}
?>
    