<?php
// index.php

header("Content-Type: application/json");
include('db.php');              // define $con
$sisUrl = Conectar::getEnv('SIS_SEGURIDAD_URL', 'http://10.10.10.16/sisSeguridad/');
$ruta = rtrim(str_replace('/sisSeguridad', '', rtrim($sisUrl, '/')), '/') . '/';

// ——————— Funciones de ayuda ———————

function responseAndExit($message, $data = [], $success = false, $status = 200)
{
    date_default_timezone_set('America/Lima');
    http_response_code($status);
    echo json_encode([
        'datetime' => date("Y-m-d H:i:s"),
        'status'   => $status,
        'success'  => $success,
        'message'  => $message,
        'data'     => $data
    ]);
    exit;
}

function getClientIp(): string
{
    return Conectar::getClientIp();
}

function authenticate(string $user, string $pass, string $ruta): array
{
    if (!$user || !$pass) {
        responseAndExit("Error: Credenciales no ingresados.", [], false, 400);
    }
    $ip  = getClientIp();
    $sistInic = Conectar::getEnv('SIS_SEGURIDAD_INIT', 'SIGI');
    $url = "{$ruta}sisSeguridad/ws/ws.php/?op=login"
        . "&pers_dni=" . urlencode($user) . "&pers_contrasena=" . urlencode($pass)
        . "&pers_ip=" . urlencode($ip) . "&sist_inic=" . urlencode($sistInic);

    $ch   = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FAILONERROR    => false,
    ]);
    $resp = curl_exec($ch);
    if (curl_errno($ch)) {
        responseAndExit("Error al conectar con el servicio de autenticación: " . curl_error($ch), [], false, 400);
    }
    curl_close($ch);

    $data = json_decode($resp, true);
    if ($data === null) {
        responseAndExit("Error al parsear la respuesta JSON.", [], false, 500);
    }
    if (isset($data['detalle'])) {
        responseAndExit($data['detalle'], [], false, 400);
    }
    if (!isset($data['hise_id'])) {
        responseAndExit("Respuesta inesperada del servicio de autenticación.", [], false, 500);
    }
    return $data;
}

function logout(string $ruta, int $hise_id): void
{
    $url = "{$ruta}sisSeguridad/ws/ws.php/?op=logout&hise_id={$hise_id}";
    $ch  = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);
}

function fetchTributos($con, $ruta, $hise_id): ?array
{
    $sql = "
      SELECT
        tp.tasaproced_id,
        tp.tasaproced_pos,
        tp.tasaproced_monto,
        tp.est         AS tasaproced_est,
        tp.cod_ref,
        tp.tasa_partida,
        p.proced_cod,
        p.proced_nom,
        t.tasa_nom,
        tp.is_multiplica,
        d.depe_denominacion,
        d.depe_abreviatura,
        tu.\"tupa_año\",
        tu.tupa_nom as Documento
      FROM sc_giros.td_tasaproced AS tp
      INNER JOIN sc_giros.tm_procedimiento AS p
        ON tp.proced_id = p.proced_id
      INNER JOIN sc_giros.tm_tasa AS t
        ON tp.tasa_id = t.tasa_id
      INNER JOIN public.tb_dependencia AS d
        ON p.proced_area = d.depe_id
      INNER JOIN sc_giros.tm_tupa AS tu
        ON tu.tupa_id = p.proced_tupa
      WHERE p.est = 1 and tp.est = 1
      ORDER BY p.proced_cod, tp.tasaproced_pos
    ";
    $res = pg_query($con, $sql);
    if (!$res) {
        // Capturamos el error real
        $err = pg_last_error($con);
        logout($ruta, $hise_id);
        responseAndExit("Error al ejecutar consulta de tributos: $err", [], false, 500);
    }
    $out = [];
    while ($row = pg_fetch_assoc($res)) {
        $out[] = $row;
    }
    return $out;
}
function fetchGiros($con, $id, $tipo, $est): ?array
{
    // Armar cláusula WHERE dinámica
    if ($tipo == 1) {
        $where = ($est !== null && $est !== "")
            ? "og.est = $est AND (c.ciud_numero_documento = \$1 OR empr.empr_ruc = \$1 OR tt.empresa_ruc = \$1)"
            : "(c.ciud_numero_documento = \$1 OR empr.empr_ruc = \$1 OR tt.empresa_ruc = \$1)";
    } elseif ($tipo == 2) {
        $where = ($est !== null && $est !== "")
            ? "og.est = $est AND (og.ogciud_id = \$1 OR og.recibo_nro = \$1)"
            : "(og.ogciud_id = \$1 OR og.recibo_nro = \$1)";
    } else {
        return null;
    }
    $where .= " AND og.est IN (0,1,2,3,4,5,6)";
    $limit  = "ORDER BY og.fechacrea DESC LIMIT 10";

    $sql = "
            SELECT 
            og.ogciud_id,
            og.est AS orden_est,
            og.recibo_nro,
            --tt.est AS procedimiento_est,
            TO_CHAR(og.fechacrea, 'YYYY-MM-DD') AS fecha,
            TO_CHAR(og.fechacrea, 'HH12:MI:SS AM') AS hora,
            CONCAT(tu.pers_nombre,' ',tu.pers_apelpat,' ',tu.pers_apelmat) AS nombre_girador,
            c.ciud_nombre,
            c.ciud_primer_apellido,
            c.ciud_segundo_apellido,
            c.ciud_numero_documento AS ciudadano_doc,
            c.ciud_domicilio_real,
            c.tido_id,
            tido.tido_descripcion,
            tm.tupa_nom AS proced_tupa,
            ta.depe_denominacion AS area_nom,
            t.proced_id,
            t.proced_nom,
            tst.cod_ref,
            ts.tasa_nom AS nombre_tasa,
            ts.tasa_nom AS categoria_tasa,
            gt.importe,
            COALESCE(gt.cantidad, 1) as cantidad,
            -- Monto unitario: importe dividido por cantidad
            CASE
            WHEN gt.cantidad = 0 THEN 0
            ELSE gt.importe / COALESCE(gt.cantidad, 1)
            END AS monto_unitario,

            t.proced_tipoindvasc,
            COALESCE(empr.empr_ruc, tt.empresa_ruc) AS empr_ruc,
    		REPLACE(
                COALESCE(empr.empr_razon_social, tt.empresa_razon_social),
                '''',
                ''
            ) AS empr_razon_social,
            '' AS empr_direccion,
            og.ogciud_comentario
        FROM sc_giros.td_ordengirociud og
        INNER JOIN sc_escalafon.tb_persona tu 
        ON og.pers_id = tu.pers_id
        INNER JOIN sc_giros.td_giro_tasa_ciudadano gt 
        ON gt.girot_giro = og.ogciud_id
        INNER JOIN sc_giros.td_tasatciud ttc 
        ON gt.tasaciud_id = ttc.tasatciud_id
        INNER JOIN sc_giros.td_procedciudadano tt 
        ON ttc.tasatciud_procedciud = tt.procedciudadano_id
        INNER JOIN public.tb_ciudadano c 
        ON c.ciud_id = tt.ciud_id
        INNER JOIN sc_giros.tm_procedimiento t 
        ON tt.proced_id = t.proced_id
        INNER JOIN sc_giros.td_tasaproced tst 
        ON ttc.tasatciud_tasaproced = tst.tasaproced_id
        INNER JOIN sc_giros.tm_tasa ts 
        ON tst.tasa_id = ts.tasa_id
        INNER JOIN public.tb_dependencia ta 
        ON t.proced_area = ta.depe_id
        INNER JOIN sc_giros.tm_tupa tm 
        ON t.proced_tupa = tm.tupa_id
        LEFT JOIN public.tb_empresa empr 
        ON empr.empr_id = tt.empr_id
        LEFT JOIN public.tb_tipo_documento tido 
        ON c.tido_id = tido.tido_id
      WHERE {$where}
      {$limit}
    ";

    $res = pg_query_params($con, $sql, [$id]);
    if (!$res) {
        return null;
    }

    $data = [];
    $prev = null;
    $idx = -1;
    $total = 0;
    while ($row = pg_fetch_assoc($res)) {
        if ($row['ogciud_id'] !== $prev) {
            $total = 0; // 🔥 REINICIAR AQUÍ

            $entry = [];
            foreach ($row as $k => $v) {
                if (!in_array($k, ['tasa_nom', 'cantidad', 'importe', 'cod_ref', 'nombre_tasa', 'categoria_tasa', 'monto_unitario'])) {
                    $entry[$k] = $v;
                }
            }
            $entry['tasas'] = [];
            $data[] = $entry;
            $idx++;
        }

        $tasa = [
            'nombre_tasa'   => $row['nombre_tasa'],
            'importe'       => number_format($row['monto_unitario'], 2, '.', ''),
            'cantidad'      => $row['cantidad'] === null ? 1 : $row['cantidad'],
            'total'         => number_format($row['importe'], 2, '.', ''),
            'cod_ref'       => str_pad($row['cod_ref'], 5, '0', STR_PAD_LEFT)
        ];

        $data[$idx]['tasas'][] = $tasa;

        $total += $row['importe']; // suma SOLO de ese giro

        //$data[$idx]['procedimiento_estado_text'] = getProcedureStateText($row['procedimiento_est']);
        $data[$idx]['orden_est_text'] = getOrdenStateText($row['orden_est']);
        $data[$idx]['totalgirado'] = number_format($total, 2, '.', '');

        $prev = $row['ogciud_id'];
    }

    // Reemplazar null por ""
    foreach ($data as &$r) {
        foreach ($r as $k => &$v) {
            if ($v === null) {
                $v = "";
            }
        }
        $r['tipopers'] = empty($r['empr_ruc']) ? "N" : "J";
    }
    unset($r);

    return $data;
}

// ——————— Dispatcher principal ———————

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // 1) Autenticar
    $login   = authenticate($_GET['user'] ?? '', $_GET['pass'] ?? '', $ruta);
    $hise_id = (int)$login['hise_id'];

    // 2a) GET get_data_tributos
    if (isset($_GET['op']) && $_GET['op'] === 'get_data_tributos') {
        $out = fetchTributos($con, $ruta, $hise_id);
        logout($ruta, $hise_id);
        responseAndExit("Datos de tributos obtenidos", $out, true, 200);
    }

    // 2b) GET consulta de giros
    if (empty($_GET['doc']) || empty($_GET['tipo'])) {
        logout($ruta, $hise_id);
        responseAndExit("Error: No se proporcionó un doc o tipo", [], false, 400);
    }
    $dataG = fetchGiros(
        $con,
        $_GET['doc'],
        $_GET['tipo'],
        $_GET['est'] ?? null
    );
    if ($dataG === null) {
        logout($ruta, $hise_id);
        responseAndExit("Error al realizar la consulta", [], false, 400);
    }
    logout($ruta, $hise_id);
    responseAndExit("Consulta realizada correctamente", $dataG, true, 200);
} elseif ($method === 'POST') {
    // 1) Autenticar con POST
    $login   = authenticate($_POST['user'] ?? '', $_POST['pass'] ?? '', $ruta);
    $hise_id = (int)$login['hise_id'];

    // 2) Validar parámetros
    $id          = $_POST['id']          ?? null;
    $recibo_nro  = $_POST['recibo_nro']  ?? null;
    $newStatus   = $_POST['est']         ?? null;
    if (($id === null && $recibo_nro === null) || $newStatus === null) {
        logout($ruta, $hise_id);
        responseAndExit(
            "Error: Debes proporcionar un ID de orden de giro, un nuevo estado y un número de recibo mediante una solicitud POST",
            [],
            false,
            400
        );
    }

    // 3) Iniciar transacción
    pg_query($con, "BEGIN");

    // 4) Actualizar orden y recibo
    if ($recibo_nro !== null) {
        // Verificar recibo duplicado
        $checkSql = "
          SELECT recibo_nro
            FROM sc_giros.td_ordengirociud
           WHERE recibo_nro = \$1
             AND (ogciud_id != \$2 OR recibo_nro != \$3)
        ";
        $checkRes = pg_query_params($con, $checkSql, [$recibo_nro, $id, $recibo_nro]);
        if (pg_num_rows($checkRes) > 0) {
            logout($ruta, $hise_id);
            responseAndExit(
                "Recibo ya usado. No se puede actualizar el número de recibo.",
                ["ogciud_id" => $id, "recibo_nro" => $recibo_nro],
                false,
                400
            );
        }
        $updOrderSql = "
          UPDATE sc_giros.td_ordengirociud
             SET est = \$1, recibo_nro = \$2
           WHERE ogciud_id = \$3 OR recibo_nro = \$4
        ";
        $updateOrderRes = pg_query_params(
            $con,
            $updOrderSql,
            [$newStatus, $recibo_nro, $id, $recibo_nro]
        );
    } else {
        $updOrderSql = "
          UPDATE sc_giros.td_ordengirociud
             SET est = \$1
           WHERE ogciud_id = \$2 OR recibo_nro = \$3
        ";
        $updateOrderRes = pg_query_params(
            $con,
            $updOrderSql,
            [$newStatus, $id, $recibo_nro]
        );
    }

    // 5) Actualizar tasa si corresponde
    if (in_array($newStatus, [0, 2, 4, 5, 6], true)) {
        $updTasaSql = "
          UPDATE sc_giros.td_tasatciud
             SET est = \$1
           WHERE tasatciud_id IN (
             SELECT gtc.tasaciud_id
               FROM sc_giros.td_giro_tasa_ciudadano gtc
               JOIN sc_giros.td_ordengirociud ogc
                 ON ogc.ogciud_id = gtc.girot_giro
              WHERE gtc.girot_giro = \$2
                 OR ogc.recibo_nro = \$3
           )
        ";
        $updateTasaRes = pg_query_params($con, $updTasaSql, [$newStatus, $id, $recibo_nro]);
    } else {
        $updateTasaRes = true;
    }

    // 6) Contar estados distintos de tasa
    $checkSameSql = "
      SELECT COUNT(DISTINCT est) AS unique_statuses
        FROM sc_giros.td_tasatciud
       WHERE tasatciud_procedciud = (
         SELECT ttc.tasatciud_procedciud
           FROM sc_giros.td_tasatciud ttc
           JOIN sc_giros.td_giro_tasa_ciudadano gtc
             ON gtc.tasaciud_id = ttc.tasatciud_id
           JOIN sc_giros.td_ordengirociud og
             ON gtc.girot_giro = og.ogciud_id
          WHERE og.ogciud_id = \$1 OR og.recibo_nro = \$2
          LIMIT 1
       )
    ";
    $sameRes = pg_query_params($con, $checkSameSql, [$id, $recibo_nro]);
    $sameRow = pg_fetch_assoc($sameRes);
    $uniqueStatuses = $sameRow['unique_statuses'];

    // 7) Actualizar procedimiento si es necesario
    if ($newStatus == 3 || $uniqueStatuses == 1) {
        $updProcSql = "
          UPDATE sc_giros.td_procedciudadano
             SET est = \$1
           WHERE procedciudadano_id = (
             SELECT ttc.tasatciud_procedciud
               FROM sc_giros.td_tasatciud ttc
               JOIN sc_giros.td_giro_tasa_ciudadano gtc
                 ON gtc.tasaciud_id = ttc.tasatciud_id
               JOIN sc_giros.td_ordengirociud og
                 ON gtc.girot_giro = og.ogciud_id
              WHERE og.ogciud_id = \$2 OR og.recibo_nro = \$3
              LIMIT 1
           )
        ";
        $updateProcRes = pg_query_params($con, $updProcSql, [$newStatus, $id, $recibo_nro]);
    }

    // 8) Commit o rollback
    if ($updateOrderRes && $updateTasaRes) {
        pg_query($con, "COMMIT");
        logout($ruta, $hise_id);
        responseAndExit(
            "Estado de la orden de giro, tasa y procedimiento actualizados correctamente",
            ["ogciud_id" => $id, "new_status" => $newStatus, "recibo_nro" => $recibo_nro],
            true,
            200
        );
    } else {
        pg_query($con, "ROLLBACK");
        logout($ruta, $hise_id);
        responseAndExit(
            "Error al actualizar el estado de la orden de giro, tasa y/o procedimiento",
            ["ogciud_id" => $id, "new_status" => $newStatus, "recibo_nro" => $recibo_nro],
            false,
            200
        );
    }
} else {
    responseAndExit("Error: Método de solicitud no admitido", [], false, 405);
}

// ——————— Textos de estado ———————

function getOrdenStateText($s)
{
    switch ($s) {
        case 0:
            return "ANULADO";
        case 1:
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

function getProcedureStateText($s)
{
    switch ($s) {
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

pg_close($con);
