<?php


header("Content-Type: application/json");
include ('db.php');


function response($message, $data = [], $success = false, $httpStatusCode = 200)
{
    date_default_timezone_set('America/Bogota');
    $status = $httpStatusCode;
    $datetime = date("Y-m-d H:i:s");
    http_response_code($httpStatusCode);
    echo json_encode(compact('datetime', 'status', 'success', 'message', 'data'));
    exit();
}

// Obtener las órdenes de giro con estado pendiente
$query = "WITH recent_orders AS (
    SELECT ogciud_id, est 
    FROM sc_giros.td_ordengirociud
    ORDER BY fechacrea DESC
),
target_procedimientos AS (
    SELECT 
        og.ogciud_id,
        og.est AS orden_est,
        pc.procedciudadano_id, 
        pc.fechacrea, 
        pc.est AS procedimiento_est
    FROM recent_orders ro
    INNER JOIN sc_giros.td_ordengirociud og ON ro.ogciud_id = og.ogciud_id
    INNER JOIN sc_giros.td_giro_tasa_ciudadano gtc ON og.ogciud_id = gtc.girot_giro
    INNER JOIN sc_giros.td_tasatciud tc ON gtc.tasaciud_id = tc.tasatciud_id
    INNER JOIN sc_giros.td_procedciudadano pc ON tc.tasatciud_procedciud = pc.procedciudadano_id
)
SELECT
    tp.ogciud_id,
    tp.orden_est,
    tp.procedciudadano_id,
    tp.fechacrea,
    tp.procedimiento_est
FROM target_procedimientos tp 
WHERE tp.orden_est = 1 
   OR (tp.procedimiento_est = 0 AND tp.fechacrea::date = CURRENT_DATE) limit 10;
";
$result = pg_query($con, $query);

if (!$result) {
    response('Error al obtener las órdenes de giro pendientes.', [], false, 500);
}

$ordenes_de_giro = pg_fetch_all($result);
if (!$ordenes_de_giro) {
    response('No se encontraron órdenes de giro pendientes.', [], true, 200);
}

$updated_orders = [];
$not_found_orders = [];
// Obtener el token activo
$token = get_active_token();

// Verifica si el token está expirado y genera uno nuevo si es así
if ($token['access_token'] === 'Token expirado') {
    generate_new_token();
    $token = get_active_token();
} elseif (!$token) {
    // Si no hay ningún token activo, devuelve un error
    response('No se encontró un token activo en la base de datos.', [], false, 500);
}

// Ahora `$token` contiene el token activo y no expirado

// Realizar la solicitud a la API para cada orden de giro pendiente
foreach ($ordenes_de_giro as $orden) {
    $orden_de_giro = $orden['ogciud_id'];
    $api_url = 'http://10.10.10.16/SIGODT/api/SATCH/get_data_satch.php?orden_de_giro=' . $orden_de_giro . '&token=' . $token['access_token'];

    $api_response = file_get_contents($api_url);
    $api_data = json_decode($api_response, true);

    if ($api_data['success'] === 'false') {
        // Registro no encontrado en la API
        $not_found_orders[] = $orden_de_giro;
        continue; // Continuar con la siguiente iteración
    }

    // Verificar si la API respondió con éxito
    if (isset($api_data['status']) && $api_data['status'] == 200 && isset($api_data['data']['new_status']) && $api_data['data']['new_status'] == 4) {
        $updated_orders[] = $orden_de_giro;
    } else {
        $not_found_orders[] = $orden_de_giro;
    }
}
function get_active_token()
{
    global $con;

    $sql = "SELECT 
        CASE 
            WHEN NOW() > (fechacrea + expires_in * INTERVAL '1 second') THEN 'Token expirado'
            ELSE access_token 
        END AS access_token,
        CASE 
            WHEN NOW() > (fechacrea + expires_in * INTERVAL '1 second') THEN '0'::text
            ELSE expires_in::text 
        END AS expires_in,
        token_est,
        fechacrea
    FROM 
        sc_giros.tb_token_satch 
    WHERE 
        token_est = 1;";
    $result = pg_query($con, $sql);

    if (!$result) {
        response('Error al obtener el token activo desde la base de datos.', [], false, 500);
    }

    return pg_fetch_assoc($result);
}
function generate_new_token()
{
    $new_token_url = 'http://10.10.10.16/SIGODT/api/SATCH/tokenGen.php';
    // Realizar la solicitud para generar un nuevo token
    $new_token_result = file_get_contents($new_token_url);
    if ($new_token_result === FALSE) {
        return false;
    }
    // Decodificar la respuesta JSON
    $new_token_response = json_decode($new_token_result, true);
    if (!$new_token_response || !isset($new_token_response['access_token'])) {
        return false;
    }
    return $new_token_response['access_token'];
}
// Cerrar la conexión a la base de datos
pg_close($con);

// Imprimir las órdenes de giro actualizadas y no encontradas en formato JSON
response('Órdenes de giro procesadas.', [
    'updated_orders' => $updated_orders,
    'not_found_orders' => $not_found_orders
], true, 200);
