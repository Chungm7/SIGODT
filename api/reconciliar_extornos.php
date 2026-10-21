<?php
/**
 * Automated reconciliation script for detecting extornos from Caja (SATCH).
 * Evaluates paid orders (est = 4) created within a configurable date window
 * (default: 15 days) against SATCH API to detect and apply any reversals (est = 6).
 */

header("Content-Type: application/json; charset=utf-8");

include(__DIR__ . '/db.php');

date_default_timezone_set('America/Lima');

ignore_user_abort(true);
set_time_limit(0);

/*
|--------------------------------------------------------------------------
| JSON Response Helper
|--------------------------------------------------------------------------
*/
function response($message, $data = [], $success = false, $httpStatusCode = 200, $con = null, $lock_id = null)
{
    if ($con && $lock_id !== null) {
        @pg_query($con, "SELECT pg_advisory_unlock($lock_id)");
    }

    http_response_code($httpStatusCode);
    echo json_encode([
        'datetime' => date("Y-m-d H:i:s"),
        'status'   => $httpStatusCode,
        'success'  => $success,
        'message'  => $message,
        'data'     => $data
    ], JSON_UNESCAPED_UNICODE);

    exit();
}

/*
|--------------------------------------------------------------------------
| Configurable Days Window Parameter (CLI / GET / ENV)
|--------------------------------------------------------------------------
| - dias=0 or dias=all: Historical full backfill (without date restriction)
| - dias=N (1..365): Sliding window from CURRENT_DATE - N days
*/
$defaultDays = (int) Conectar::getEnv('SATCH_RECONCILIATION_DAYS', '15');
if ($defaultDays <= 0) {
    $defaultDays = 15;
}

$dias = $defaultDays;

if (php_sapi_name() === 'cli' && isset($argv[1])) {
    $raw = strtolower(trim($argv[1]));
    if (strpos($raw, 'dias=') === 0) {
        $raw = substr($raw, 5);
    }
    if ($raw === 'all' || $raw === 'todos' || $raw === '0') {
        $dias = 0;
    } elseif (is_numeric($raw)) {
        $dias = (int) $raw;
    }
} elseif (isset($_GET['dias'])) {
    $raw = strtolower(trim($_GET['dias']));
    if ($raw === 'all' || $raw === 'todos' || $raw === '0') {
        $dias = 0;
    } elseif (is_numeric($raw)) {
        $dias = (int) $raw;
    }
}

if ($dias !== 0) {
    if ($dias < 1) {
        $dias = 1;
    } elseif ($dias > 365) {
        $dias = 365;
    }
}

/*
|--------------------------------------------------------------------------
| Logging Setup
|--------------------------------------------------------------------------
*/
$log_dir = __DIR__ . '/logs';
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
}

$log_file = $log_dir . '/reconciliacion_extornos_' . date('Y_m_d') . '.log';

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
| Advisory Lock for Reconciliation (Dedicated Lock ID: 987654335)
|--------------------------------------------------------------------------
*/
$lock_id = 987654335;
$lock = pg_query($con, "SELECT pg_try_advisory_lock($lock_id) AS locked");

if (!$lock) {
    response('Error al obtener lock de base de datos.', [], false, 500);
}

$lock_row = pg_fetch_assoc($lock);
if ($lock_row['locked'] !== 't') {
    response('Ya existe un proceso de reconciliación de extornos ejecutándose.', [], false, 200);
}

$ventanaLog = ($dias === 0) ? "HISTORICO TOTAL (Sin limite de fecha)" : "$dias dias";
write_log("--- INICIO DE RECONCILIACION DE EXTORNOS (Ventana: $ventanaLog) ---");

/*
|--------------------------------------------------------------------------
| SATCH Token Helper Functions
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
    $tokenGenFile = __DIR__ . '/SATCH/tokenGen.php';
    if (file_exists($tokenGenFile)) {
        ob_start();
        include($tokenGenFile);
        $output = ob_get_clean();
        $res = json_decode($output, true);
        if ($res && isset($res['access_token'])) {
            return $res;
        }
    }

    $new_token_url = Conectar::ruta() . 'api/SATCH/tokenGen.php';
    $response = @file_get_contents($new_token_url);
    if ($response === false) {
        return false;
    }
    return json_decode($response, true);
}

$token = get_active_token();
if (!$token || $token['access_token'] === 'Token expirado') {
    write_log("Token no encontrado o expirado. Generando nuevo token...");
    generate_new_token();
    $token = get_active_token();
}

if (!$token || $token['access_token'] === 'Token expirado') {
    write_log("ERROR: No se pudo obtener ni generar token de SATCH.");
    response('No se pudo obtener token de SATCH.', [], false, 500, $con, $lock_id);
}

write_log("TOKEN SATCH ACTIVO OK");

/*
|--------------------------------------------------------------------------
| Query Paid Orders (est = 4) in the specified window
|--------------------------------------------------------------------------
*/
$limite = 25;
$offset = 0;
$total_analizadas = 0;
$extornadas = [];
$confirmadas_pagadas = [];
$errores = [];

while (true) {
    if ($dias === 0) {
        $query = "
        SELECT ogciud_id, est, recibo_nro, fechacrea
        FROM sc_giros.td_ordengirociud
        WHERE est = 4
        ORDER BY fechacrea DESC
        LIMIT $1 OFFSET $2
        ";
        $result = pg_query_params($con, $query, [$limite, $offset]);
    } else {
        $query = "
        SELECT ogciud_id, est, recibo_nro, fechacrea
        FROM sc_giros.td_ordengirociud
        WHERE est = 4
          AND fechacrea >= CURRENT_DATE - ($1 || ' days')::interval
        ORDER BY fechacrea DESC
        LIMIT $2 OFFSET $3
        ";
        $result = pg_query_params($con, $query, [$dias, $limite, $offset]);
    }
    if (!$result) {
        write_log("ERROR AL CONSULTAR ORDENES: " . pg_last_error($con));
        break;
    }

    $ordenes = pg_fetch_all($result);
    if (!$ordenes) {
        break;
    }

    write_log("Procesando bloque de " . count($ordenes) . " órdenes (Offset: $offset)");

    foreach ($ordenes as $orden) {
        $orden_de_giro = $orden['ogciud_id'];
        $recibo_actual = $orden['recibo_nro'];
        $total_analizadas++;

        $api_url = Conectar::ruta() . 'api/SATCH/get_data_satch.php?orden_de_giro=' .
            urlencode($orden_de_giro) .
            '&token=' .
            urlencode($token['access_token']);

        $context = stream_context_create([
            'http' => [
                'timeout'       => 15,
                'ignore_errors' => true
            ]
        ]);

        $api_response = @file_get_contents($api_url, false, $context);

        if ($api_response === false) {
            $last_error = error_get_last()['message'] ?? 'Fallo de conexion';
            write_log("ERROR CONEXION: $orden_de_giro | DETALLE: $last_error");
            $errores[] = ['orden' => $orden_de_giro, 'error' => $last_error];
            continue;
        }

        $api_data = json_decode($api_response, true);
        if (!$api_data) {
            write_log("ERROR PARSING JSON: $orden_de_giro");
            $errores[] = ['orden' => $orden_de_giro, 'error' => 'Respuesta JSON inválida'];
            continue;
        }

        $status = $api_data['status'] ?? 0;
        $new_status = $api_data['data']['new_status'] ?? null;
        $message = $api_data['message'] ?? '';

        if ($status == 200 && $new_status == 6) {
            // Extorno applied successfully!
            write_log(">>> EXTORNO DETECTADO Y APLICADO: Orden $orden_de_giro paso a estado 6 (Extornado)");
            $extornadas[] = [
                'orden'      => $orden_de_giro,
                'recibo'     => $recibo_actual,
                'new_status' => 6
            ];
        } elseif ($status == 400 && strpos($message, 'La orden ya se encuentra en otro estado') !== false) {
            // Normal: SATCH confirmed it's still paid, no state change allowed (stays 4)
            write_log("CONFIRMADO PAGADO (Sin extorno): $orden_de_giro");
            $confirmadas_pagadas[] = $orden_de_giro;
        } elseif ($status == 200 && $new_status == 4) {
            // Already paid
            $confirmadas_pagadas[] = $orden_de_giro;
        } else {
            write_log("RESPUESTA INESPERADA: $orden_de_giro | HTTP $status: $message");
            $errores[] = ['orden' => $orden_de_giro, 'status' => $status, 'message' => $message];
        }

        usleep(100000); // 100ms throttle between calls
    }

    $offset += $limite;
    usleep(250000); // 250ms throttle between batches
}

/*
|--------------------------------------------------------------------------
| Summary & Lock Release
|--------------------------------------------------------------------------
*/
write_log("--- FIN DE RECONCILIACION ---");
write_log("Total analizadas: $total_analizadas");
write_log("Extornos detectados y aplicados: " . count($extornadas));
write_log("Pagadas confirmadas vigentes: " . count($confirmadas_pagadas));
write_log("Errores: " . count($errores));

response(
    'Proceso de reconciliación de extornos finalizado correctamente.',
    [
        'dias_evaluados'      => $dias,
        'total_analizadas'    => $total_analizadas,
        'extornadas'          => count($extornadas),
        'confirmadas_pagadas' => count($confirmadas_pagadas),
        'errores'             => count($errores),
        'detalle_extornos'    => $extornadas
    ],
    true,
    200,
    $con,
    $lock_id
);
