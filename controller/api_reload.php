<?php
require_once("../config/conexion.php");
require_once("../models/Bitacora.php");
$bitacora = new Bitacora();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cantant = $bitacora->get_max_id()[0]["bita_id"];
    // Realizar la solicitud a la URL configurada
    $url = Conectar::getEnv('SYNC_SEARCH_URL', Conectar::ruta() . 'api/buscar.php');
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    
    // Configurar otros encabezados si es necesario
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
   
    if ($httpCode >= 200 && $httpCode < 300) {
        // La solicitud fue exitosa
        // Manejar la respuesta como desees

        echo $response;
        $bitacora->update_bitacora_grupo($_SESSION["usua_id_SIGODT"], $cantant);
    } else {
        // Hubo un error en la solicitud
        // Manejar el error como desees
        echo "Error al realizar la solicitud: $httpCode";
    }
    
    curl_close($ch);
} else {
    // Método no permitido
    http_response_code(405);
    echo "Método no permitido";
}
?>
