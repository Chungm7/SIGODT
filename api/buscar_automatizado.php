<?php

header("Content-Type: application/json");

include('db.php');

date_default_timezone_set('America/Lima');

ignore_user_abort(true);
set_time_limit(0);

/*
|--------------------------------------------------------------------------
| RESPUESTA JSON
|--------------------------------------------------------------------------
*/
function response($message, $data = [], $success = false, $httpStatusCode = 200)
{
    http_response_code($httpStatusCode);

    echo json_encode([
        'datetime' => date("Y-m-d H:i:s"),
        'status'   => $httpStatusCode,
        'success'  => $success,
        'message'  => $message,
        'data'     => $data
    ]);

    exit();
}

/*
|--------------------------------------------------------------------------
| LOGS
|--------------------------------------------------------------------------
*/
$log_dir = __DIR__ . '/logs';

if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
}

$log_file = $log_dir . '/consulta_ordenes_giro_' . date('Y_m_d') . '.log';

function write_log($message)
{
    global $log_file;

    file_put_contents(
        $log_file,
        "[" . date('Y-m-d H:i:s') . "] " . $message . PHP_EOL,
        FILE_APPEND
    );
}

/*
|--------------------------------------------------------------------------
| LOCK
|--------------------------------------------------------------------------
*/
$lock_id = 987654330;

$lock = pg_query($con, "SELECT pg_try_advisory_lock($lock_id) AS locked");

if (!$lock) {
    response('Error al obtener lock.', [], false, 500);
}

$lock_row = pg_fetch_assoc($lock);

if ($lock_row['locked'] !== 't') {
    response('Ya existe un proceso ejecutándose.', [], false, 200);
}

write_log("LOCK obtenido correctamente");

/*
|--------------------------------------------------------------------------
| TOKEN
|--------------------------------------------------------------------------
*/
function get_active_token()
{
    global $con;

    $sql = "
    SELECT 
        CASE 
            WHEN NOW() > (fechacrea + expires_in * INTERVAL '1 second')
                THEN 'Token expirado'
            ELSE access_token 
        END AS access_token
    FROM sc_giros.tb_token_satch
    WHERE token_est = 1
    LIMIT 1
    ";

    $result = pg_query($con, $sql);

    if (!$result) {
        return false;
    }

    return pg_fetch_assoc($result);
}

function generate_new_token()
{
    $new_token_url = Conectar::ruta() . 'api/SATCH/tokenGen.php';

    $response = @file_get_contents($new_token_url);

    if ($response === false) {
        return false;
    }

    return json_decode($response, true);
}

$token = get_active_token();

if (!$token) {

    pg_query($con, "SELECT pg_advisory_unlock($lock_id)");

    response('No se encontró token.', [], false, 500);
}

if ($token['access_token'] === 'Token expirado') {

    write_log("Token expirado. Generando nuevo token...");

    generate_new_token();

    $token = get_active_token();

    if (!$token || $token['access_token'] === 'Token expirado') {

        pg_query($con, "SELECT pg_advisory_unlock($lock_id)");

        response('No se pudo renovar token.', [], false, 500);
    }
}

write_log("TOKEN OK");

/*
|--------------------------------------------------------------------------
| CONFIG
|--------------------------------------------------------------------------
*/
$limite = 20;

$total_procesadas = 0;

$updated_orders = [];
$not_found_orders = [];
$error_orders = [];

/*
|--------------------------------------------------------------------------
| EXCLUIR ERRORES SOLO EN ESTA EJECUCIÓN
|--------------------------------------------------------------------------
*/
$ordenes_con_error = [];

write_log("INICIO DEL PROCESO");

/*
|--------------------------------------------------------------------------
| PROCESAR BLOQUES
|--------------------------------------------------------------------------
|
| SIN OFFSET
|
*/
while (true) {

    /*
    |--------------------------------------------------------------------------
    | EXCLUIR ERRORES TEMPORALES
    |--------------------------------------------------------------------------
    */
    $where_error = '';

    if (!empty($ordenes_con_error)) {

        $ids_error = array_map(function ($id) use ($con) {
            return "'" . pg_escape_string($con, $id) . "'";
        }, $ordenes_con_error);

        $where_error = " AND tp.ogciud_id NOT IN (" . implode(',', $ids_error) . ")";
    }

    /*
    |--------------------------------------------------------------------------
    | QUERY
    |--------------------------------------------------------------------------
    */
    $query = "
    WITH recent_orders AS (
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
        INNER JOIN sc_giros.td_ordengirociud og
            ON ro.ogciud_id = og.ogciud_id
        INNER JOIN sc_giros.td_giro_tasa_ciudadano gtc
            ON og.ogciud_id = gtc.girot_giro
        INNER JOIN sc_giros.td_tasatciud tc
            ON gtc.tasaciud_id = tc.tasatciud_id
        INNER JOIN sc_giros.td_procedciudadano pc
            ON tc.tasatciud_procedciud = pc.procedciudadano_id
    )
    SELECT DISTINCT
        tp.ogciud_id,
        tp.orden_est,
        tp.procedciudadano_id,
        tp.fechacrea,
        tp.procedimiento_est
    FROM target_procedimientos tp
    WHERE tp.orden_est in (1,2)
    $where_error
    ORDER BY tp.fechacrea DESC
    LIMIT $limite
    ";

    $result = pg_query($con, $query);

    if (!$result) {

        write_log("ERROR QUERY: " . pg_last_error($con));

        break;
    }

    $ordenes = pg_fetch_all($result);

    /*
    |--------------------------------------------------------------------------
    | YA TERMINÓ
    |--------------------------------------------------------------------------
    */
    if (!$ordenes) {

        write_log("NO HAY MÁS ÓRDENES");

        break;
    }

    write_log("BLOQUE OBTENIDO: " . count($ordenes));

    /*
    |--------------------------------------------------------------------------
    | RECORRER ÓRDENES
    |--------------------------------------------------------------------------
    */
    foreach ($ordenes as $orden) {

        $orden_de_giro = $orden['ogciud_id'];

        write_log("Consultando orden: $orden_de_giro");

        $api_url =
            Conectar::ruta() . 'api/SATCH/get_data_satch.php?orden_de_giro=' .
            urlencode($orden_de_giro) .
            '&token=' .
            urlencode($token['access_token']);

        $context = stream_context_create([
            'http' => [
                'timeout' => 15
            ]
        ]);

        /*
        |--------------------------------------------------------------------------
        | CONSUMIR API
        |--------------------------------------------------------------------------
        */
        $api_response = @file_get_contents($api_url, false, $context);

        /*
        |--------------------------------------------------------------------------
        | ERROR API
        |--------------------------------------------------------------------------
        */
        if ($api_response === false) {

            $error = error_get_last();

            $mensaje_error = $error['message'] ?? 'Error desconocido';

            write_log("ERROR API: $orden_de_giro | DETALLE: $mensaje_error");

            $ordenes_con_error[] = $orden_de_giro;

            $error_orders[] = [
                'orden' => $orden_de_giro,
                'error' => $mensaje_error
            ];

            continue;
        }

        /*
        |--------------------------------------------------------------------------
        | DECODIFICAR JSON
        |--------------------------------------------------------------------------
        */
        $api_data = json_decode($api_response, true);

        /*
        |--------------------------------------------------------------------------
        | JSON INVÁLIDO
        |--------------------------------------------------------------------------
        */
        if (!$api_data) {

            $json_error = json_last_error_msg();

            write_log("JSON INVALIDO: $orden_de_giro | DETALLE: $json_error");

            $ordenes_con_error[] = $orden_de_giro;

            $error_orders[] = [
                'orden' => $orden_de_giro,
                'error' => "JSON INVALIDO: $json_error"
            ];

            continue;
        }

        /*
        |--------------------------------------------------------------------------
        | VALIDAR API
        |--------------------------------------------------------------------------
        */
        if (
            isset($api_data['status']) &&
            $api_data['status'] == 200
        ) {

            /*
            |--------------------------------------------------------------------------
            | PAGADA
            |--------------------------------------------------------------------------
            */
            if (
                isset($api_data['data']['new_status']) &&
                $api_data['data']['new_status'] == 4
            ) {

                write_log("PAGADA: $orden_de_giro");

                $updated_orders[] = $orden_de_giro;

            } else {

                write_log("SIN CAMBIOS: $orden_de_giro");

                $not_found_orders[] = $orden_de_giro;
            }

        } else {

            $respuesta_error = json_encode($api_data);

            write_log("RESPUESTA INVALIDA API: $orden_de_giro | RESPUESTA: $respuesta_error");

            $ordenes_con_error[] = $orden_de_giro;

            $error_orders[] = [
                'orden' => $orden_de_giro,
                'error' => $api_data
            ];
        }

        $total_procesadas++;

        usleep(200000);
    }

    write_log("BLOQUE FINALIZADO");

    usleep(500000);
}

/*
|--------------------------------------------------------------------------
| RESUMEN FINAL
|--------------------------------------------------------------------------
*/
write_log("TOTAL PROCESADAS: $total_procesadas");
write_log("TOTAL PAGADAS: " . count($updated_orders));
write_log("TOTAL SIN CAMBIOS: " . count($not_found_orders));
write_log("TOTAL ERRORES: " . count($error_orders));
write_log("TOTAL ERRORES EXCLUIDOS: " . count($ordenes_con_error));

/*
|--------------------------------------------------------------------------
| LIBERAR LOCK
|--------------------------------------------------------------------------
*/
pg_query($con, "SELECT pg_advisory_unlock($lock_id)");

write_log("LOCK LIBERADO");

/*
|--------------------------------------------------------------------------
| CERRAR CONEXIÓN
|--------------------------------------------------------------------------
*/
pg_close($con);

/*
|--------------------------------------------------------------------------
| RESPUESTA FINAL
|--------------------------------------------------------------------------
*/
response(
    'Proceso finalizado correctamente.',
    [
        'total_procesadas' => $total_procesadas,
        'updated_orders' => count($updated_orders),
        'not_found_orders' => count($not_found_orders),
        'error_orders' => count($error_orders)
    ],
    true,
    200
);