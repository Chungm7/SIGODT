<?php
header("Content-Type: application/json");
include('../db.php');

// Datos de autenticación desde entorno
$username = Conectar::getEnv('SATCH_CLIENT_ID', 'satchapitest');
$password = Conectar::getEnv('SATCH_CLIENT_SECRET', 'rQ4iDSYbHpq6c1D');

// Datos del cuerpo de la solicitud
$data = array(
    'grant_type' => 'password',
    'username' => Conectar::getEnv('SATCH_USERNAME', 'mpch@test.com'),
    'password' => Conectar::getEnv('SATCH_PASSWORD', 'sX2kHKCQ27yjoi4N')
);

// URL de la API
$url = Conectar::getEnv('SATCH_AUTH_URL', 'http://satch.gob.pe:81/api.test/oauth/token');

// Configurar opciones de la solicitud
$options = array(
    'http' => array(
        'header' => "Authorization: Basic " . base64_encode("$username:$password"),
        'method' => 'POST',
        'content' => http_build_query($data),
    )
);

// Crear contexto
$context = stream_context_create($options);

// Realizar la solicitud
$result = file_get_contents($url, false, $context);

// Si hay un error, mostrarlo
if ($result === FALSE) {
    die('Error en la solicitud');
}

// Decodificar la respuesta JSON
$response = json_decode($result, true);

// Insertar los datos en la base de datos
if ($response && isset($response['access_token'])) {
    $access_token = $response['access_token'];
    $token_type = $response['token_type'];
    $expires_in = $response['expires_in'];
    $scope = $response['scope'];
    $jti = $response['jti'];

    // Obtener el último token
    $last_token = get_last_token();
    if ($last_token) {
        desactivar_token($last_token['token_id']);
    }
   
    // Insertar el nuevo token en la base de datos
    insert_token($access_token, $token_type, $expires_in, $scope, $jti);
}

// Imprimir la respuesta JSON
echo $result;

// Función para insertar en la base de datos
function insert_token($access_token, $token_type, $expires_in, $scope, $jti) {
    global $con;

    $insert_query = "INSERT INTO sc_giros.tb_token_satch(access_token, token_type, expires_in, scope, jti) VALUES ($1, $2, $3, $4, $5)";
    $insert_result = pg_query_params($con, $insert_query, array($access_token, $token_type, $expires_in, $scope, $jti));

    if (!$insert_result) {
        die('Error al insertar el nuevo token.');
    }
}

// Función para obtener el último token
function get_last_token() {
    global $con;

    $select_query = "SELECT token_id FROM sc_giros.tb_token_satch ORDER BY token_id DESC LIMIT 1";
    $select_result = pg_query($con, $select_query);

    if (!$select_result) {
        die('Error al obtener el último token.');
    }

    return pg_fetch_assoc($select_result);
}

// Función para desactivar un token
function desactivar_token($token_id) {
    global $con;

    $update_query = "UPDATE sc_giros.tb_token_satch SET token_est = 0 WHERE token_id = $1";
    $update_result = pg_query_params($con, $update_query, array($token_id));

    if (!$update_result) {
        die('Error al desactivar el token anterior.');
    }
}
?>
