<?php
// Establecer cabecera Content-Type como application/json
header("Content-Type: application/json");

// Incluye el archivo de configuración de la base de datos para PostgreSQL
include('db.php');
$ruta = 'http://10.10.10.16/';

// Función para responder con JSON
function response($message, $data = [], $success = false, $httptatusCode = 200)
{
    date_default_timezone_set('America/Bogota');
    $status = $httptatusCode;
    $datetime = date("Y-m-d H:i:s");
    http_response_code($httptatusCode);
    echo json_encode(compact('datetime', 'status', 'success', 'message', 'data'));
    exit();
}

// Verifica la conexión a la base de datos
if (!$con) {
    response("Error de conexión a la base de datos", [], false, 500);
}

// Verifica si el método de solicitud es GET
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Verifica si se proporcionó un ID en la solicitud GET
    if (isset($_GET['doc']) && $_GET['doc'] != "" && isset($_GET['proced_id']) && $_GET['proced_id'] != "") {
        $url2 = 'http://10.10.10.16/SIGODT/api/buscar.php';
        $response2 = file_get_contents($url2);
        $id = $_GET['doc'];
        $proced_id = $_GET['proced_id'];

        // Inicializamos la parte de la consulta SQL que se mantendrá constante
        $query = "SELECT 
                        og.ogciud_id,
                        og.est orden_est,
                        og.recibo_nro,
                        tt.est as procedimiento_est,
                        TO_CHAR(og.fechacrea, 'YYYY-MM-DD') AS fecha,
                        TO_CHAR(og.fechacrea, 'HH12:MI:SS AM') AS hora,
                        CONCAT(tu.pers_nombre, ' ', tu.pers_apelpat, ' ', tu.pers_apelmat) AS nombre_girador,
                        c.ciud_nombre, c.ciud_primer_apellido, c.ciud_segundo_apellido,
                        c.ciud_numero_documento AS ciudadano_doc,
                        c.ciud_domicilio_real,
                        c.tido_id,
                        tido.tido_descripcion,
                        tm.tupa_nom as proced_tupa,
                        ta.depe_denominacion,
                        t.proced_id,
                        t.proced_nom,
                        tst.nombre_tasa,
                        tst.cod_ref,
                        ts.tasa_nom,
                        gt.importe,
                        t.proced_tipoindvasc,
                        empr.empr_ruc,
                        empr.empr_razon_social,
                        empr.empr_direccion,
                        og.ogciud_comentario
                        FROM 
                        sc_giros.td_ordengirociud og
                        INNER JOIN 
                        sc_escalafon.tb_persona tu ON og.pers_id = tu.pers_id
                        INNER JOIN 
                        sc_giros.td_giro_tasa_ciudadano gt ON gt.girot_giro = og.ogciud_id
                        INNER JOIN 
                        sc_giros.td_tasatciud ttc ON gt.tasaciud_id = ttc.tasatciud_id
                        INNER JOIN 
                        sc_giros.td_procedciudadano tt ON ttc.tasatciud_procedciud = tt.procedciudadano_id
                        INNER JOIN 
                        public.tb_ciudadano c ON c.ciud_id = tt.ciud_id
                        INNER JOIN 
                        sc_giros.tm_procedimiento t ON tt.proced_id = t.proced_id
                        INNER JOIN 
                        sc_giros.td_tasaproced tst ON ttc.tasatciud_tasaproced = tst.tasaproced_id
                        INNER JOIN 
                        sc_giros.tm_tasa ts ON tst.tasa_id = ts.tasa_id
                        INNER JOIN 
                        tb_dependencia ta ON t.proced_area = ta.depe_id
                        INNER JOIN 
                        sc_giros.tm_tupa tm ON t.proced_tupa = tm.tupa_id
                        LEFT JOIN 
                        public.tb_empresa empr ON empr.empr_id = tt.empr_id
                        LEFT JOIN 
                        public.tb_tipo_documento tido ON c.tido_id = tido.tido_id
                        WHERE (og.ogciud_id = $1 OR og.recibo_nro = $1)
                        AND (og.est = 1 OR og.est = 2 OR og.est = 3 OR og.est = 4 OR og.est = 5 OR og.est = 6)
                        AND t.proced_id = $2";

        // Ejecutamos la consulta con un array que contenga el valor como texto y como entero
        $res = pg_query_params($con, $query, array($id, $proced_id));

        // Verifica si hubo un error en la consulta
        if (!$res) {
            response("Error en la consulta SQL", [], false, 500);
        }

        // Verifica si se encontraron resultados
        if (pg_num_rows($res) > 0) {
            $data = array();
            $previousOrderId = null; // Variable para almacenar el ID de la orden de giro anterior
            $orderIndex = -1; // Índice de la orden de giro actual en el array $data
            $totalgirado = 0;

            while ($row = pg_fetch_assoc($res)) {
                // Verifica si el ID de la orden de giro es diferente al anterior
                if ($row['ogciud_id'] != $previousOrderId) {
                    // Si es diferente, agrega un nuevo elemento al array $data para la nueva orden de giro
                    $rowData = array();
                    foreach ($row as $key => $value) {
                        if ($key != 'tasa_nom' && $key != 'importe' && $key != 'cod_ref' && $key != 'nombre_tasa') {
                            // Crea un arreglo asociativo con la nombre de la columna como clave, excluyendo ogciud_id, tasa_nom e importe
                            $rowData[$key] = $value;
                        }
                    }
                    $rowData['tasas'] = array(); // Inicializa el arreglo de tasas para la nueva orden de giro
                    $data[] = $rowData; // Agrega la nueva orden de giro al array $data
                    $orderIndex++; // Incrementa el índice de la orden de giro actual
                }

                // Agrega la tasa al arreglo de tasas de la orden de giro actual
                $tasa = array('nombre_tasa' => $row['nombre_tasa'], 'importe' => $row['importe'], 'cod_ref' => str_pad($row['cod_ref'], 5, '0', STR_PAD_LEFT));
                $data[$orderIndex]['tasas'][] = $tasa;
                $totalgirado += $row['importe'];
                $data[$orderIndex]['procedimiento_estado_text'] = getProcedureStateText($row['procedimiento_est']);
                $data[$orderIndex]['orden_est_text'] = getOrdenStateText($row['orden_est']);
                $data[$orderIndex]['totalgirado'] = $totalgirado;

                // Actualiza el ID de la orden de giro anterior
                $previousOrderId = $row['ogciud_id'];
            }

            foreach ($data as &$row) {
                // Determina el valor de tipopers basado en empr_ruc
                if (empty($row['empr_ruc'])) {
                    $tipopers = "N"; // Si empr_ruc está vacío, asigna tipopers como "N" (persona natural)
                } else {
                    $tipopers = "J"; // Si empr_ruc tiene un valor, asigna tipopers como "J" (persona jurídica)
                }

                // Creamos un nuevo array para la fila actual con el orden deseado
                $newRow = [
                    'ogciud_id' => $row['ogciud_id'],
                    'orden_est' => $row['orden_est'],
                    'orden_est_text' => $row['orden_est_text'],
                    'recibo_nro' => $row['recibo_nro'],
                    'procedimiento_est' => $row['procedimiento_est'],
                    'procedimiento_estado_text' => $row['procedimiento_estado_text'],
                    'fecha' => $row['fecha'],
                    'hora' => $row['hora'],
                    'nombre_girador' => $row['nombre_girador'],
                    'tipopers' => $tipopers,
                    'ciud_nombre' => $row['ciud_nombre'],
                    'ciud_primer_apellido' => $row['ciud_primer_apellido'],
                    'ciud_segundo_apellido' => $row['ciud_segundo_apellido'],
                    'ciudadano_doc' => $row['ciudadano_doc'],
                    'ciud_domicilio_real' => $row['ciud_domicilio_real'],
                    'tido_id' => $row['tido_id'],
                    'tido_descripcion' => $row['tido_descripcion'],
                    'proced_tupa' => $row['proced_tupa'],
                    'depe_denominacion' => $row['depe_denominacion'],
                    'proced_id' => $row['proced_id'],
                    'proced_nom' => $row['proced_nom'],
                    'proced_tipoindvasc' => $row['proced_tipoindvasc'],
                    'empr_ruc' => $row['empr_ruc'],
                    'empr_razon_social' => $row['empr_razon_social'],
                    'empr_direccion' => $row['empr_direccion'],
                    'ogciud_comentario' => $row['ogciud_comentario'],
                    'totalgirado' => $row['totalgirado'],
                    'tasas' => $row['tasas']
                ];

                // Reemplaza la fila actual con el nuevo array que tiene el orden deseado
                $row = $newRow;

                foreach ($row as $key => &$value) {
                    if ($value === null) {
                        $value = ""; // Reemplazamos los valores nulos por cadenas vacías
                    }
                }
            }
            response("Consulta realizada correctamente", $data, true, 200);
        } else {
            response("Recibo y procedimiento Incompatibles", [], false, 400);
        }
    } else {
        // No se proporcionó un ID en la solicitud GET
        response("Error: No se proporcionó un doc o proced_id", [], false, 400);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verifica si se proporcionaron todos los parámetros necesarios en la solicitud POST
    if (empty($_POST['user']) || empty($_POST['pass']) || empty($_POST['ip']) || empty($_POST['sist_inic']) || empty($_POST['perf_id'])) {
        response("Credenciales no ingresadas.", [], false, 400);
    } else {
        $ws = $ruta . "sisSeguridad/ws/ws.php/?op=login&pers_dni=" . $_POST['user'] . "&pers_contrasena=" . $_POST['pass'] . "&pers_ip=" . $_POST['ip'] . "&sist_inic=" . $_POST['sist_inic'];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $ws);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
            curl_close($ch);
            response("Error al conectarse al servicio: $error_msg", [], false, 500);
        } else {
            curl_close($ch);
            $data = json_decode($response, true);
        }

        if ($data === null) {
            response("Error al decodificar la respuesta del servicio", [], false, 500);
        }

        $detalle = $data["detalle"];
        if ($detalle == "No se encontraron datos" || $detalle == "IP Persona no registrada" || $detalle == "Persona inactiva" || $detalle == "Fuera de la hora de acceso" || $detalle == "Usuario no vigente" || $detalle == "Datos incorrectos") {
            response($detalle, [], false, 400);
        } else if ($data["perf_id"] == $_POST['perf_id']) {
            $hise_id = $data["hise_id"];
            $pers_id = $data["pers_id"];

            if (empty($_POST['doc'])) {
                response('Error: Debes proporcionar un ID de orden de giro mediante una solicitud POST', [], false, 400);
            } else {
                $doc = $_POST['doc'];
                $newStatus = 5;

                // Inicia una transacción para asegurar que todas las actualizaciones sean atómicas
                pg_query($con, "BEGIN");

                $selectOrderQuery = "SELECT ogciud_id, est FROM sc_giros.td_ordengirociud WHERE ogciud_id = $1 OR recibo_nro = $2";
                $selectOrderRes = pg_query_params($con, $selectOrderQuery, array($doc, $doc));
                if (!$selectOrderRes || pg_num_rows($selectOrderRes) == 0) {
                    pg_query($con, "ROLLBACK");
                    response("No se encontró una orden de giro válida con el ID o número de recibo proporcionado.", [], false, 404);
                }
                $order = pg_fetch_assoc($selectOrderRes);
                $orderStatus = $order['est'];
                if ($orderStatus == 5) {
                    response("Recibo u Orden ya usado!", [], false, 400);
                } elseif ($orderStatus == 1 || $orderStatus == 2) {
                    response("La orden de giro o recibo aún no ha sido pagado.", [], false, 400);
                } elseif ($orderStatus == 6) {
                    response("La orden de giro o recibo ha sido extornado.", [], false, 400);
                } elseif ($orderStatus != 4) {
                    response("Estado de la orden de giro o recibo no es válido para esta operación.", [], false, 400);
                }

                // Ahora, obtenemos el estado del procedimiento asociado a la orden de giro o recibo
                $selectProcedimientoQuery = "
                    SELECT proced_id, fechacrea, est
                    FROM sc_giros.td_procedciudadano
                    WHERE procedciudadano_id = (
                        SELECT tasatciud_procedciud
                        FROM sc_giros.td_tasatciud
                        WHERE tasatciud_id = (
                            SELECT tasaciud_id
                            FROM sc_giros.td_giro_tasa_ciudadano gtc
                            INNER JOIN sc_giros.td_ordengirociud og ON gtc.girot_giro = og.ogciud_id
                            WHERE og.ogciud_id = $1 OR og.recibo_nro = $2
                            LIMIT 1
                        )
                        LIMIT 1
                    )
                ";
                $selectProcedimientoRes = pg_query_params($con, $selectProcedimientoQuery, array($doc, $doc));

                if (!$selectProcedimientoRes || pg_num_rows($selectProcedimientoRes) == 0) {
                    response("No se encontró un procedimiento asociado a la orden de giro o recibo.", [], false, 404);
                }
                $procedimiento = pg_fetch_assoc($selectProcedimientoRes);

                if ($procedimiento['est'] == 0) {
                    // Si el estado del procedimiento es 0, significa que está anulado
                    response("El procedimiento asociado está anulado.", [], false, 400);
                }

                // Actualiza el estado de la orden de giro
                $updateOrderQuery = "UPDATE sc_giros.td_ordengirociud SET est = $1, sis_upd = $4, usu_sis = $5, fecha_update = now() WHERE ogciud_id = $2 OR recibo_nro = $3";
                $updateOrderRes = pg_query_params($con, $updateOrderQuery, array($newStatus, $doc, $doc, $_POST['sist_inic'], $data['pers_id']));

                if ($newStatus == 5) {
                    // Actualiza el estado de la tasa asociada con el ID de orden de giro
                    $updateTasaQuery = "UPDATE sc_giros.td_tasatciud SET est = $1 WHERE tasatciud_id IN (SELECT tasaciud_id FROM sc_giros.td_giro_tasa_ciudadano gtc INNER JOIN sc_giros.td_ordengirociud ogc ON ogc.ogciud_id = gtc.girot_giro WHERE girot_giro = $2 OR ogc.recibo_nro = $3)";
                    $updateTasaRes = pg_query_params($con, $updateTasaQuery, array($newStatus, $doc, $doc));
                } else {
                    $updateTasaRes = true;
                }

                // Verifica si todas las tasas tienen el mismo estado
                $checkSameStatusQuery = "SELECT COUNT(DISTINCT est) AS unique_statuses FROM sc_giros.td_tasatciud WHERE tasatciud_procedciud = (SELECT procedciudadano_id FROM sc_giros.td_procedciudadano tpc INNER JOIN sc_giros.td_tasatciud ttc ON tpc.procedciudadano_id = ttc.tasatciud_procedciud INNER JOIN sc_giros.td_giro_tasa_ciudadano gtc ON gtc.tasaciud_id = ttc.tasatciud_id INNER JOIN sc_giros.td_ordengirociud og ON gtc.girot_giro = og.ogciud_id WHERE og.ogciud_id = $1 OR og.recibo_nro = $2 LIMIT 1)";
                $checkSameStatusRes = pg_query_params($con, $checkSameStatusQuery, array($doc, $doc));
                $checkSameStatusRow = pg_fetch_assoc($checkSameStatusRes);
                $uniqueStatuses = $checkSameStatusRow['unique_statuses'];

                if ($uniqueStatuses == 1) {
                    // Si todas las tasas tienen el mismo estado, actualiza el estado del procedimiento al mismo estado de las tasas
                    $updateProcedimientoQuery = "UPDATE sc_giros.td_procedciudadano SET est = $1 WHERE procedciudadano_id = (SELECT tasatciud_procedciud FROM sc_giros.td_tasatciud WHERE tasatciud_id = (SELECT tasaciud_id FROM sc_giros.td_giro_tasa_ciudadano gtc INNER JOIN sc_giros.td_ordengirociud og ON gtc.girot_giro = og.ogciud_id WHERE og.ogciud_id = $2 OR og.recibo_nro = $3 LIMIT 1) LIMIT 1)";
                    $updateProcedimientoRes = pg_query_params($con, $updateProcedimientoQuery, array($newStatus, $doc, $doc));
                } else {
                    $updateProcedimientoRes = true;
                }

                // Verifica si todas las actualizaciones fueron exitosas
                if ($updateOrderRes && $updateTasaRes && $updateProcedimientoRes) {
                    // Si todas las actualizaciones fueron exitosas, confirma la transacción
                    pg_query($con, "COMMIT");
                    response("Estado de la orden de giro, tasa y procedimiento actualizados correctamente", array("recibo:" => $doc, "new_status" => $newStatus), true, 200);
                } else {
                    // Si alguna actualización falló, realiza un rollback de la transacción
                    pg_query($con, "ROLLBACK");
                    response("Error al actualizar el estado de la orden de giro, tasa y/o procedimiento", array("recibo" => $doc, "new_status" => $newStatus), false, 500);
                }

                // Realiza el logout del servicio web
                $ws_logout = $ruta . "sisSeguridad/ws/ws.php/?op=logout&hise_id=" . $hise_id;
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $ws_logout);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($ch);

                if (curl_errno($ch)) {
                    $error_msg = curl_error($ch);
                    response("Error al desconectarse del servicio: $error_msg", [], false, 500);
                } else {
                    curl_close($ch);
                }
            }
        } else {
            response('Error: Usuario sin Acceso', [], false, 401);
        }
    }
} else {
    response("Método no permitido", [], false, 405);
}

function getOrdenStateText($procedureState)
{
    switch ($procedureState) {
        case 0:
            return "ANULADO";
        case 1:
            return "GIRADO";
        case 2:
            return "GIRADO";
        case 3:
            return "IMPROCEDENTE";
        case 4:
            return "PAGADO";
        case 5:
            return "USADO";
        case 6:
            return "EXTORNADO";
        default:
            return "Estado desconocido";
    }
}

function getProcedureStateText($procedureState)
{
    switch ($procedureState) {
        case 0:
            return "ANULADO";
        case 1:
            return "PENDIENTE";
        case 2:
            return "GIRADO";
        case 3:
            return "IMPROCEDENTE";
        case 4:
            return "PAGADO";
        case 5:
            return "USADO";
        case 6:
            return "EXTORNADO";
        default:
            return "Estado desconocido";
    }
}

// Cierra la conexión a la base de datos PostgreSQL
pg_close($con);
