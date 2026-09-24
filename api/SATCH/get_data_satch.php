<?php
header("Content-Type: application/json");
include('../db.php');

function response($message, $data = [], $success = false, $httpStatusCode = 200)
{
    date_default_timezone_set('America/Bogota');
    $status = $httpStatusCode;
    $datetime = date("Y-m-d H:i:s");
    http_response_code($httpStatusCode);
    echo json_encode(compact('datetime', 'status', 'success', 'message', 'data'));
    exit();
}


$token = isset($_GET['token']) ? $_GET['token'] : '';
if (empty($token)) {
    response('El parámetro token es requerido.', [], false, 400);
}

// URL de la API y parámetro de orden de giro
// Obtener el parámetro orden_de_giro
$orden_de_giro = isset($_GET['orden_de_giro']) ? $_GET['orden_de_giro'] : '';
if (empty($orden_de_giro)) {
    response('El parámetro orden_de_giro es requerido.', [], false, 400);
}

/* // Separar la parte numérica y el año
list($num_part, $year_part) = explode('-', $orden_de_giro);

// Eliminar los ceros iniciales de la parte numérica
$num_part = ltrim($num_part, '0');

// Concatenar de nuevo el número sin ceros iniciales y el año
$orden_de_giro = $num_part . '-' . $year_part; */

if (empty($orden_de_giro)) {
    response('El parámetro orden_de_giro es requerido.', [], false, 400);
}
$url = 'http://satch.gob.pe:81/api.test/v1/recibos/getDatosReciboPorOrdenGiro/' . $orden_de_giro;

// Configurar opciones de la solicitud
$options = array(
    'http' => array(
        'header' => "Authorization: Bearer " . $token,
        'method' => 'GET',
    )
);

// Crear contexto
$context = stream_context_create($options);

// Realizar la solicitud
$result = file_get_contents($url, false, $context);
$response_data = json_decode($result, true);
// Decodificar el resultado JSON
$response_data = json_decode($result, true);

if ($response_data['status'] == 200 && $response_data['successful']) {
    $ordenGiro = isset($response_data['data']['ordenGiro']) ? $response_data['data']['ordenGiro'] : '';
    $ordenGiroParts = explode('-', $ordenGiro);
    $primerNumero = str_pad($ordenGiroParts[0], 6, '0', STR_PAD_LEFT);
    $ordenGiro = $primerNumero . '-' . $ordenGiroParts[1];
    $reciboNro = isset($response_data['data']['numeroRecibo']) ? $response_data['data']['numeroRecibo'] : null;

    // Determinar el nuevo estado basado en el estado de la respuesta de la API
    $estadoApi = isset($response_data['data']['estado']) ? $response_data['data']['estado'] : '';
    if (strtolower($estadoApi) == 'extornado') {
        $newStatus = 6; // Estado extornado
    } else {
        $newStatus = 4; // Estado pagado
    }

    actualizarOrden($ordenGiro, $reciboNro, $newStatus);
} else {
    response('La solicitud a la API no fue exitosa.', $response_data, false, 400);
}


// Función para obtener el token activo desde la base de dato
// Función para generar un nuevo token
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

function actualizarOrden($id, $reciboNro, $newStatus)
{
    global $con;

    // Inicia una transacción para asegurar que todas las actualizaciones sean atómicas
    pg_query($con, "BEGIN");

    /*
    |--------------------------------------------------------------------------
    | VALIDAR SI LA ORDEN YA ESTÁ USADA
    |--------------------------------------------------------------------------
    */

    $checkEstadoQuery = "
        SELECT est
        FROM sc_giros.td_ordengirociud
        WHERE ogciud_id = $1
    ";

    $checkEstadoRes = pg_query_params(
        $con,
        $checkEstadoQuery,
        array($id)
    );

    $ordenActual = pg_fetch_assoc($checkEstadoRes);

    // Solo órdenes en estado 1 o 2 pueden pasar a PAGADO (4)
    if (
        $ordenActual &&
        $newStatus == 4 &&
        !in_array((int)$ordenActual['est'], [1, 2])
    ) {

        pg_query($con, "ROLLBACK");

        response(
            'La orden ya se encuentra en otro estado y no puede actualizarse.',
            array(
                "ogciud_id" => $id,
                "estado_actual" => $ordenActual['est'],
                "nuevo_estado" => $newStatus
            ),
            false,
            400
        );

        exit;
    }


    // Actualiza el estado de la orden de giro y el número de recibo
    if (isset($reciboNro)) {
        // Consulta para verificar si el número de recibo ya está en uso
        $checkReciboQuery = "SELECT recibo_nro FROM sc_giros.td_ordengirociud WHERE recibo_nro = $1 AND ogciud_id != $2";
        $checkReciboRes = pg_query_params($con, $checkReciboQuery, array($reciboNro, $id));

        // Verifica si se encontró algún recibo con el mismo número
        if (pg_num_rows($checkReciboRes) > 0) {
            response('Recibo ya usado. No se puede actualizar el número de recibo.', array("ogciud_id" => $id, "recibo_nro" => $reciboNro), false, 400);
        } else {
            // verificar que la orden de giro no se encuentre en otro esta que no sea el girado
            // Actualiza el estado de la orden de giro y el número de recibo
            $updateOrderQuery = "UPDATE sc_giros.td_ordengirociud SET est = $1, recibo_nro = $2 WHERE ogciud_id = $3";
            $updateOrderRes = pg_query_params($con, $updateOrderQuery, array($newStatus, $reciboNro, $id));
        }
    } else {
        // Si no se proporcionó un número de recibo, solo actualiza el estado de la orden de giro
        $updateOrderQuery = "UPDATE sc_giros.td_ordengirociud SET est = $1 WHERE ogciud_id = $2 OR recibo_nro = $3";
        $updateOrderRes = pg_query_params($con, $updateOrderQuery, array($newStatus, $id, $reciboNro));
    }
    $search = 0;
    // Actualiza el estado de la tasa asociada con el ID de orden de giro
    if ($newStatus == 0 || $newStatus == 2 || $newStatus == 4 || $newStatus == 5 || $newStatus == 6) {
        if ($newStatus == 4) {
            // Consulta para verificar el estado del procedimiento
            $checkProcedimientoQuery = "SELECT pc.est 
                                        FROM sc_giros.td_procedciudadano pc 
                                        INNER JOIN sc_giros.td_tasatciud tc ON pc.procedciudadano_id = tc.tasatciud_procedciud
                                        INNER JOIN sc_giros.td_giro_tasa_ciudadano gtc ON tc.tasatciud_id = gtc.tasaciud_id
                                        INNER JOIN sc_giros.td_ordengirociud ogc ON gtc.girot_giro = ogc.ogciud_id
                                        WHERE ogc.ogciud_id = $1 OR ogc.recibo_nro = $2";

            $checkProcedimientoRes = pg_query_params($con, $checkProcedimientoQuery, array($id, $reciboNro));
            $procedimiento = pg_fetch_assoc($checkProcedimientoRes);

            if ($procedimiento && $procedimiento['est'] == 0) {
                // No actualizar si el estado del procedimiento es 0
                $updateTasaRes = true;
            } else {
                // Proceder con la actualización
                $updateTasaQuery = "UPDATE sc_giros.td_tasatciud SET est = $1 WHERE tasatciud_id IN (SELECT tasaciud_id FROM sc_giros.td_giro_tasa_ciudadano gtc INNER JOIN sc_giros.td_ordengirociud ogc ON ogc.ogciud_id = gtc.girot_giro WHERE girot_giro = $2 OR ogc.recibo_nro = $3)";
                $updateTasaRes = pg_query_params($con, $updateTasaQuery, array($newStatus, $id, $reciboNro));
                $search = 1;
            }
        } else {
            // Proceder con la actualización para otros estados
            $updateTasaQuery = "UPDATE sc_giros.td_tasatciud SET est = $1 WHERE tasatciud_id IN (SELECT tasaciud_id FROM sc_giros.td_giro_tasa_ciudadano gtc INNER JOIN sc_giros.td_ordengirociud ogc ON ogc.ogciud_id = gtc.girot_giro WHERE girot_giro = $2 OR ogc.recibo_nro = $3)";
            $updateTasaRes = pg_query_params($con, $updateTasaQuery, array($newStatus, $id, $reciboNro));
            $search = 1;
        }
    } else {
        $updateTasaRes = true;
        $search = 1;
    }

    if ($search == 1) {
        // Verifica si todas las tasas tienen el mismo estado
        $checkSameStatusQuery = "SELECT COUNT(DISTINCT est) AS unique_statuses FROM sc_giros.td_tasatciud WHERE tasatciud_procedciud = ( SELECT procedciudadano_id FROM sc_giros.td_procedciudadano tpc  INNER JOIN sc_giros.td_tasatciud ttc ON tpc.procedciudadano_id = ttc.tasatciud_procedciud INNER JOIN sc_giros.td_giro_tasa_ciudadano gtc ON gtc.tasaciud_id = ttc.tasatciud_id INNER JOIN sc_giros.td_ordengirociud og ON gtc.girot_giro = og.ogciud_id WHERE og.ogciud_id = $1 OR og.recibo_nro = $2 limit 1);";
        $checkSameStatusRes = pg_query_params($con, $checkSameStatusQuery, array($id, $reciboNro));
        $checkSameStatusRow = pg_fetch_assoc($checkSameStatusRes);
        $uniqueStatuses = $checkSameStatusRow['unique_statuses'];
    }


    // Actualiza el estado del procedimiento si es necesario
    if ($newStatus == 3) {
        $updateProcedimientoQuery = "UPDATE sc_giros.td_procedciudadano SET est = $1 WHERE procedciudadano_id = (SELECT tasatciud_procedciud FROM sc_giros.td_tasatciud WHERE tasatciud_id = (SELECT tasaciud_id FROM sc_giros.td_giro_tasa_ciudadano gtc INNER JOIN sc_giros.td_ordengirociud og ON gtc.girot_giro = og.ogciud_id WHERE og.ogciud_id = $2 OR og.recibo_nro = $3 LIMIT 1)LIMIT 1);";
        $updateProcedimientoRes = pg_query_params($con, $updateProcedimientoQuery, array($newStatus, $id, $reciboNro));
    }
    if ($uniqueStatuses == 1) {
        // Si todas las tasas tienen el mismo estado, actualiza el estado del procedimiento al mismo estado de las tasas
        $updateProcedimientoQuery = "UPDATE sc_giros.td_procedciudadano SET est = $1 WHERE procedciudadano_id = (SELECT tasatciud_procedciud FROM sc_giros.td_tasatciud WHERE tasatciud_id = (SELECT tasaciud_id FROM sc_giros.td_giro_tasa_ciudadano gtc INNER JOIN sc_giros.td_ordengirociud og ON gtc.girot_giro = og.ogciud_id WHERE og.ogciud_id = $2 OR og.recibo_nro = $3 LIMIT 1)LIMIT 1);";
        $updateProcedimientoRes = pg_query_params($con, $updateProcedimientoQuery, array($newStatus, $id, $reciboNro));
    }

    // Si todas las actualizaciones fueron exitosas, confirma la transacción
    if ($updateOrderRes && $updateTasaRes) {
        pg_query($con, "COMMIT");
        response("Estado de la orden de giro, tasa y procedimiento actualizados correctamente", array("ogciud_id" => $id, "new_status" => $newStatus, "recibo_nro" => $reciboNro), true, 200);
    } else {
        // Si alguna actualización falló, realiza un rollback de la transacción
        pg_query($con, "ROLLBACK");
        response("Error al actualizar el estado de la orden de giro, tasa y/o procedimiento", array("ogciud_id" => $id, "new_status" => $newStatus, "recibo_nro" => $reciboNro), false, 500);
    }
}
