<?php
// Establecer cabecera Content-Type como application/json
header("Content-Type: application/json");

// Incluye el archivo de configuraciÃ³n de la base de datos para PostgreSQL
include('db.php');
$ruta = 'https://www.munichiclayo.gob.pe/';
// FunciÃ³n para responder con JSON

function response($message, $data = [], $suc, $httpStatusCode = 200)
{
    date_default_timezone_set('America/Bogota');
    $status = $httpStatusCode;
    $success = $suc;
    $datetime = date("Y-m-d H:i:s");
    http_response_code($httpStatusCode);
    echo json_encode(compact('datetime', 'status', 'success', 'message', 'data'));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($_GET['user'] == null || $_GET['pass'] == null) {
        response("Error: Credenciales no ingresados.", [], false, 400);
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
        if (strpos($ip, '::') === 0) {
            $ip = '::1'; // Dirección IPv6 de localhost
            // $ip = "192.168.12.44"; // No es necesario reemplazar la IP si no se está utilizando IPv6
        }

        $ch = curl_init();
        $ws_reniec = "https://www.munichiclayo.gob.pe/sisSeguridad/ws/ws.php/?op=login&pers_dni=" . $_GET['user'] . "&pers_contrasena=" . $_GET['pass'] . "&pers_ip=" . $ip . "&sist_inic=SIGI";
        curl_setopt($ch, CURLOPT_URL, $ws_reniec);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
        } else {
            curl_close($ch);
            $data = json_decode($response, true);
        }

        $detalle = $data["detalle"];
        if ($detalle == "No se encontraron datos" || $detalle == "IP Persona no registrada" || $detalle == "Persona inactiva" || $detalle == "Fuera de la hora de acceso" || $detalle == "Usuario no vigente" || $detalle == "Datos incorrectos") {
            response($detalle, [], false, 400);
        } else if ($data !== null ) {
            $hise_id = $data["hise_id"];
            // Verifica si se proporcionó un ID en la solicitud GET
            if (isset($_GET['doc']) && $_GET['doc'] != "") {
                $url2 = 'https://www.munichiclayo.gob.pe/SIGODT/api/buscar.php';
                $response2 = file_get_contents($url2);

                $id = $_GET['doc'];
                $tipo = $_GET['tipo'];
                // Inicializamos la parte de la consulta SQL que se mantendrá constante
                $query = "SELECT 
                            og.ogciud_id,
                        og.est orden_est,
                        og.recibo_nro,
                        tt.est as procedimiento_est,
                        TO_CHAR(og.fechacrea, 'YYYY-MM-DD') AS fecha,
                        TO_CHAR(og.fechacrea, 'HH12:MI:SS AM') AS hora,
                        CONCAT(tu.pers_nombre, ' ', tu.pers_apelpat, ' ', tu.pers_apelmat) AS nombre_girador,
                        c.ciud_nombre,c.ciud_primer_apellido,c.ciud_segundo_apellido,
                        c.ciud_numero_documento AS ciudadano_doc,
                        c.ciud_domicilio_real,
                        c.tido_id,
                        tido.tido_descripcion,
                        tm.tupa_nom as proced_tupa,
                        ta.depe_denominacion,
                        t.proced_id,
                        t.proced_nom,
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
                        WHERE ";
                switch ($tipo) {
                    case 1:
                        if (isset($_GET['est'])) {
                            $est = $_GET['est'];
                            $query .= " og.est = " . $est . " and (c.ciud_numero_documento = $1 OR empr.empr_ruc = $1)";
                            $query .= " AND (og.est = 1 OR og.est = 2 OR og.est = 3 OR og.est = 4 OR og.est = 5 OR og.est = 6) ORDER BY og.fechacrea DESC limit 10";
                        } else {

                            $query .= "c.ciud_numero_documento = $1 OR empr.empr_ruc = $1";
                            $query .= " AND (og.est = 1 OR og.est = 2 OR og.est = 3 OR og.est = 4 OR og.est = 5 OR og.est = 6) ORDER BY og.fechacrea DESC limit 10";
                        }
                        break;
                    case 2:
                        if (isset($_GET['est'])) {
                            $est = $_GET['est'];
                            $query .= " og.est = " . $est . " and (og.ogciud_id = $1 OR og.recibo_nro = $1)";
                            $query .= " AND (og.est = 1 OR og.est = 2 OR og.est = 3 OR og.est = 4 OR og.est = 5 OR og.est = 6) ORDER BY og.fechacrea DESC limit 10";
                        } else {
                            $query .= "og.ogciud_id = $1 OR og.recibo_nro = $1 ";
                            $query .= " AND (og.est = 1 OR og.est = 2 OR og.est = 3 OR og.est = 4 OR og.est = 5 OR og.est = 6) ORDER BY og.fechacrea DESC limit 10";
                        }
                        break;
                    default:
                        response('Error al realizar la consulta', [], false, 400);
                }
                // Ejecutamos la consulta con un array que contenga el valor como texto y como entero
                $res = pg_query_params($con, $query, array($id));
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
                        $tasa = array('categoria_tasa' => $row['tasa_nom'], 'nombre_tasa' => $row['nombre_tasa'], 'importe' => $row['importe'], 'cod_ref' => str_pad($row['cod_ref'], 5, '0', STR_PAD_LEFT));
                        $data[$orderIndex]['tasas'][] = $tasa;
                        $totalgirado += $row['importe'];
                        $data[$orderIndex]['procedimiento_estado_text'] = getProcedureStateText($row['procedimiento_est']);

                        $data[$orderIndex]['orden_est_text'] = getOrdenStateText($row['orden_est']);
                        $data[$orderIndex]['totalgirado'] =  $totalgirado;
                        $totalgirado = 0;

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
                            'area_nom' => $row['area_nom'],
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
                    response("No se encontraron datos", [], false, 400);
                }
                $ch = curl_init();
                $ws_reniec =  $ruta . "sisSeguridad/ws/ws.php/?op=logout&hise_id=" . $hise_id;

                curl_setopt($ch, CURLOPT_URL, $ws_reniec);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($ch);

                if (curl_errno($ch)) {
                    $error_msg = curl_error($ch);
                    response('Error al conectarse al servicio', [], false, 400);
                } else {
                    curl_close($ch);
                }
            } else {
                // No se proporcionó un ID en la solicitud GET
                response("Error: No se proporcionó un doc", [], false, 400);
            }
        } else {
            response("Usuario sin Acceso", [], false, 401);
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verifica si se proporcionÃ³ un ID de orden de giro y un nuevo estado en la solicitud POST
    if ($_POST['user'] == null || $_POST['pass'] == null) {
        response("Credenciales no ingresados.", [], false, 400);
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
        if (strpos($ip, '::') === 0) {
            $ip = '::1'; // Dirección IPv6 de localhost
            // $ip = "192.168.12.44"; // No es necesario reemplazar la IP si no se está utilizando IPv6
        }

        $ch = curl_init();
        $ws_reniec =  $ruta . "sisSeguridad/ws/ws.php/?op=login&pers_dni=" . $_POST['user'] . "&pers_contrasena=" . $_POST['pass'] . "&pers_ip=" . $ip . "&sist_inic=SIGI";
        curl_setopt($ch, CURLOPT_URL, $ws_reniec);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
        } else {
            curl_close($ch);
            $data = json_decode($response, true);
        }

        $detalle = $data["detalle"];
        if ($detalle == "No se encontraron datos" || $detalle == "IP Persona no registrada" || $detalle == "Persona inactiva" || $detalle == "Fuera de la hora de acceso" || $detalle == "Usuario no vigente" || $detalle == "Datos incorrectos") {
            response($detalle, [], false, 400);
        } else if ($data !== null ) {
            $hise_id = $data["hise_id"];
            if ($_POST['id'] == null && $_POST['recibo_nro'] == null) {
                response('Error: Debes proporcionar un ID de orden de giro, un nuevo estado y un número de recibo mediante una solicitud POST', [], false, 400);
            } else  if (isset($_POST['est'])) {
                $id = isset($_POST['id']) ? $_POST['id'] : null;
                $reciboNro = isset($_POST['recibo_nro']) ? $_POST['recibo_nro'] : null;
                $newStatus = $_POST['est'];

                // Inicia una transacciÃ³n para asegurar que todas las actualizaciones sean atÃ³micas
                pg_query($con, "BEGIN");

                // Actualiza el estado de la orden de giro y el nÃºmero de recibo
                if (isset($_POST['recibo_nro'])) {
                    // Consulta para verificar si el nÃºmero de recibo ya estÃ¡ en uso
                    $checkReciboQuery = "SELECT recibo_nro FROM sc_giros.td_ordengirociud WHERE recibo_nro = $1 AND (ogciud_id != $2 OR recibo_nro != $3)";
                    $checkReciboRes = pg_query_params($con, $checkReciboQuery, array($reciboNro, $id, $reciboNro));

                    // Verifica si se encontrÃ³ algÃºn recibo con el mismo nÃºmero
                    if (pg_num_rows($checkReciboRes) > 0) {
                        response('Recibo ya usado. No se puede actualizar el número de recibo.', array("ogciud_id" => $id, "recibo_nro" => $reciboNro), false, 400);
                        exit; // Sale del script si se encuentra un recibo con el mismo nÃºmero
                    } else {
                        // Actualiza el estado de la orden de giro y el nÃºmero de recibo
                        $updateOrderQuery = "UPDATE sc_giros.td_ordengirociud SET est = $1, recibo_nro = $2 WHERE ogciud_id = $3 OR recibo_nro = $4";
                        $updateOrderRes = pg_query_params($con, $updateOrderQuery, array($newStatus, $reciboNro, $id, $reciboNro));
                    }
                } else {
                    // Si no se proporcionÃ³ un nÃºmero de recibo, solo actualiza el estado de la orden de giro
                    $updateOrderQuery = "UPDATE sc_giros.td_ordengirociud SET est = $1 WHERE ogciud_id = $2 OR recibo_nro = $3";
                    $updateOrderRes = pg_query_params($con, $updateOrderQuery, array($newStatus, $id, $reciboNro));
                }
                if ($newStatus == 0 || $newStatus == 2 || $newStatus == 4 || $newStatus == 5 || $newStatus == 6) {
                    // Actualiza el estado de la tasa asociada con el ID de orden de giro
                    $updateTasaQuery = "UPDATE sc_giros.td_tasatciud SET est = $1 WHERE tasatciud_id IN (SELECT tasaciud_id FROM sc_giros.td_giro_tasa_ciudadano gtc inner join sc_giros.td_ordengirociud ogc on ogc.ogciud_id = gtc.girot_giro WHERE girot_giro = $2 or ogc.recibo_nro = $3)";
                    $updateTasaRes = pg_query_params($con, $updateTasaQuery, array($newStatus, $id, $reciboNro));
                    // Verifica si todas las tasas tienen el mismo estado
                } else {
                    $updateTasaRes = true;
                }
                $checkSameStatusQuery = "SELECT COUNT(DISTINCT est) AS unique_statuses FROM sc_giros.td_tasatciud WHERE tasatciud_procedciud = ( SELECT procedciudadano_id FROM sc_giros.td_procedciudadano tpc  INNER JOIN sc_giros.td_tasatciud ttc ON tpc.procedciudadano_id = ttc.tasatciud_procedciud INNER JOIN sc_giros.td_giro_tasa_ciudadano gtc ON gtc.tasaciud_id = ttc.tasatciud_id INNER JOIN sc_giros.td_ordengirociud og ON gtc.girot_giro = og.ogciud_id WHERE og.ogciud_id = $1 OR og.recibo_nro = $2 limit 1);";
                $checkSameStatusRes = pg_query_params($con, $checkSameStatusQuery, array($id, $reciboNro));
                $checkSameStatusRow = pg_fetch_assoc($checkSameStatusRes);
                $uniqueStatuses = $checkSameStatusRow['unique_statuses'];
                if ($newStatus == 3) {
                    $updateProcedimientoQuery = "UPDATE sc_giros.td_procedciudadano SET est = $1 WHERE procedciudadano_id = (SELECT tasatciud_procedciud FROM sc_giros.td_tasatciud WHERE tasatciud_id = (SELECT tasaciud_id FROM sc_giros.td_giro_tasa_ciudadano gtc INNER JOIN sc_giros.td_ordengirociud og ON gtc.girot_giro = og.ogciud_id WHERE og.ogciud_id = $2 OR og.recibo_nro = $3 LIMIT 1)LIMIT 1);";
                    $updateProcedimientoRes = pg_query_params($con, $updateProcedimientoQuery, array($newStatus, $id, $reciboNro));
                }
                if ($uniqueStatuses == 1) {
                    // Si todas las tasas tienen el mismo estado, actualiza el estado del procedimiento al mismo estado de las tasas
                    $updateProcedimientoQuery = "UPDATE sc_giros.td_procedciudadano SET est = $1 WHERE procedciudadano_id = (SELECT tasatciud_procedciud FROM sc_giros.td_tasatciud WHERE tasatciud_id = (SELECT tasaciud_id FROM sc_giros.td_giro_tasa_ciudadano gtc INNER JOIN sc_giros.td_ordengirociud og ON gtc.girot_giro = og.ogciud_id WHERE og.ogciud_id = $2 OR og.recibo_nro = $3 LIMIT 1)LIMIT 1);";
                    $updateProcedimientoRes = pg_query_params($con, $updateProcedimientoQuery, array($newStatus, $id, $reciboNro));
                }

                // Verifica si todas las actualizaciones fueron exitosas
                if ($updateOrderRes && $updateTasaRes) {
                    // Si todas las actualizaciones fueron exitosas, confirma la transacciÃ³n
                    pg_query($con, "COMMIT");
                    response("Estado de la orden de giro, tasa y procedimiento actualizados correctamente", array("ogciud_id" => $id, "new_status" => $newStatus, "recibo_nro" => $reciboNro), true, 200);
                } else {
                    // Si alguna actualizaciÃ³n fallÃ³, realiza un rollback de la transacciÃ³n
                    pg_query($con, "ROLLBACK");
                    response("Error al actualizar el estado de la orden de giro, tasa y/o procedimiento", array("ogciud_id" => $id, "new_status" => $newStatus, "recibo_nro" => $reciboNro), false, 200);
                }

                $ch = curl_init();
                $ws_reniec =  $ruta . "sisSeguridad/ws/ws.php/?op=logout&hise_id=" . $hise_id;

                curl_setopt($ch, CURLOPT_URL, $ws_reniec);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($ch);

                if (curl_errno($ch)) {
                    $error_msg = curl_error($ch);
                    response("Error al conectarce al servicio", [], false, 400);
                } else {
                    curl_close($ch);
                }
            } else {
                response("Error: Debes proporcionar un ID de orden de giro, un nuevo estado y un número de recibo mediante una solicitud POST", [], false, 400);
            }
        } else {
            response('Error: Usuario sin Acceso', [], false, 401);
        }
    }
} else {
    response('Error: Método de solicitud no admitido', [], false, 400);
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

// Cierra la conexiÃ³n a la base de datos PostgreSQL
pg_close($con);
