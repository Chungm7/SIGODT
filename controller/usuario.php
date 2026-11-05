<?php
require_once("../config/conexion.php");
require_once("../models/Usuario.php");
require_once("../models/Bitacora.php");
$bitacora = new Bitacora();
$usuario =  new Usuario();

/** SIINDI contact seam: the original token + POST contract, with verified transport. */
function sigurUsuarioRequest(string $url, array $fields, ?string $token = null): array
{
    if (parse_url($url, PHP_URL_SCHEME) !== 'https' || !parse_url($url, PHP_URL_HOST)) {
        throw new RuntimeException('Servicio no disponible.');
    }
    $ch = curl_init($url);
    if ($ch === false) {
        throw new RuntimeException('Servicio no disponible.');
    }
    try {
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge(
            ['Content-Type: application/x-www-form-urlencoded'],
            $token === null ? [] : ['Authorization: Bearer ' . $token]
        ));
        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($response === false || curl_errno($ch) || $status < 200 || $status >= 300) {
            throw new RuntimeException('Servicio no disponible.');
        }
        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Respuesta no válida.');
        }
        return $decoded;
    } finally {
        curl_close($ch);
    }
}

switch ($_GET["op"]) {
    // Directly transferred from SIINDI controller/usuario.php; existing admin cases below stay intact.
    case 'mostrar_datos':
    case 'update_telefono':
    case 'update_emailp':
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['usua_id_SIGODT']) || !is_string($_SESSION['usua_dni_SIGODT'] ?? null)
            || $_SESSION['usua_dni_SIGODT'] === '') {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Sesión no válida.']);
            break;
        }
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
            break;
        }
        if (!is_string($_SESSION['csrf_token'] ?? null) || $_SESSION['csrf_token'] === ''
            || !is_string($_POST['csrf_token'] ?? null)
            || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Solicitud no válida.']);
            break;
        }
        $op = $_GET['op'];
        $dato = $_POST['dato'] ?? '';
        if ($op !== 'mostrar_datos' && (!is_string($dato)
            || ($op === 'update_telefono' && !preg_match('/^[0-9]{9}$/D', $dato))
            || ($op === 'update_emailp' && (strlen($dato) > 254 || !filter_var($dato, FILTER_VALIDATE_EMAIL))))) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Dato de contacto no válido.']);
            break;
        }
        try {
            $sigurUrl = rtrim((string)Conectar::getEnv('SIGUR_URL', ''), '/') . '/';
            $authUrl = (string)Conectar::getEnv('SIGUR_AUTH_URL', $sigurUrl . 'auth/');
            $authJson = sigurUsuarioRequest($authUrl, [
                'sist_inic' => Conectar::getEnv('SIST_INIC', ''),
                'sist_pass' => Conectar::getEnv('SIST_PASS', '')
            ]);
            $token = $authJson['data']['token'] ?? null;
            if (!is_string($token) || $token === '' || preg_match('/[\r\n]/', $token)) {
                throw new RuntimeException('Autorización no disponible.');
            }
            // Identity comes exclusively from the destination's completed session, never from POST.
            $updateData = ['usu_doc' => $_SESSION['usua_dni_SIGODT'], 'usu_tipo' => 'P'];
            if ($op !== 'mostrar_datos') {
                $updateData['tipo_cambio'] = $op === 'update_telefono' ? '1' : '2';
                $updateData['dato'] = $dato;
            }
            $decoded = sigurUsuarioRequest($sigurUrl . 'ws/' . ($op === 'mostrar_datos' ? 'mostrar.php' : 'actualizar.php'), $updateData, $token);
            if (($decoded['success'] ?? null) !== true || !is_array($decoded['data'] ?? null)) {
                throw new RuntimeException('Operación no confirmada.');
            }
            $filtered = [];
            foreach (['usu_celu01', 'usu_emailp', 'usu_emaili'] as $field) {
                // SIINDI's observed provider alias is usu_emails; its HTML expects usu_emaili.
                $value = $decoded['data'][$field] ?? ($field === 'usu_emaili' ? ($decoded['data']['usu_emails'] ?? '') : '');
                $filtered[$field] = is_string($value) ? $value : '';
            }
            echo json_encode(['success' => true, 'data' => $filtered]);
        } catch (Throwable $e) {
            http_response_code(502);
            echo json_encode(['success' => false, 'message' => 'No se pudo completar la solicitud al servicio de seguridad.']);
        }
        break;
    case "mostrar":
        $datos = $usuario->get_usuarios($_POST["usua_id"]);
        if (is_array($datos) == true and count($datos) <> 0) {
            foreach ($datos as $row) {
                $output["usua_id"] = $row["usua_id"];
                $output["usu_nom"] = $row["usu_nom"];
                $output["usu_apep"] = $row["usu_apep"];
                $output["usu_apem"] = $row["usu_apem"];
                $output["usu_pass"] = $row["usu_pass"];
                $output["usu_rol"] = $row["usu_rol"];
                $output["usu_dni"] = $row["usu_dni"];
            }

            echo json_encode($output);
        }
        break;
    case "mostrarPerfil":
        $datos = $usuario->get_usuario_x_id($_SESSION["usua_id_SIGODT"]);
        if (is_array($datos) == true and count($datos) <> 0) {
            foreach ($datos as $row) {
                $output["pers_nombre"] = $row["pers_nombre"];
                $output["pers_apelpat"] = $row["pers_apelpat"];
                $output["pers_apelmat"] = $row["pers_apelmat"];
                $output["pers_dni"] = $row["pers_dni"];
            }
            echo json_encode($output);
        }
        break;
    case "cambiar_contrasena":
        $id =  $_SESSION["usua_id_SIGODT"];
        $claveantigua =  $_POST["usua_pass"];
        $clave = $_POST["nuevaPass"];
        $clave2 = $_POST["confirmarPass"];

        $data = $usuario->cambiar_contrasena_API($id, $claveantigua, $clave, $clave2);

        echo $data;

        break;
    case "eliminar":
        $usuario->delete_usuario($_POST["usua_id"]);
        $bitacora->update_bitacora($_SESSION["usua_id_SIGODT"]);
        break;
    case "listar":
        $datos = $usuario->get_usuario();
        $data = array();
        foreach ($datos as $row) {
            $sub_array = array();
            $sub_array[] = $row["pers_dni"];
            $sub_array[] = $row["pers_nombre"];
            $sub_array[] = $row["pers_apelpat"];
            $sub_array[] = $row["pers_apelmat"];
            if ($row["perf_id"] == 2) {
                $sub_array[] = "Usuario";
            } else {
                $sub_array[] = "Admin";
            }
            $data[] = $sub_array;
        }

        $results = array(
            "sEcho" => 1,
            "iTotalRecords" => count($data),
            "iTotalDisplayRecords" => count($data),
            "aaData" => $data
        );
        echo json_encode($results);
        break;
    case "listar_sistemas":
        $datos = $usuario->listarSistemasUsuario($_SESSION["usua_id_SIGODT"]);
        echo json_encode($datos);
        break;
    case "consulta_dni":
        $datos = $usuario->get_usuario_x_dni($_POST["usu_dni"]);
        if (is_array($datos) == true and count($datos) <> 0) {
            foreach ($datos as $row) {
                $output["usua_id"] = $row["usua_id"];
                $output["usu_nom"] = $row["usu_nom"];
                $output["usu_apep"] = $row["usu_apep"];
                $output["usu_apem"] = $row["usu_apem"];
                $output["usu_sex"] = $row["usu_sex"];
                $output["usu_pass"] = $row["usu_pass"];
                $output["usu_rol"] = $row["usu_rol"];
                $output["usu_dni"] = $row["usu_dni"];
            }
            echo json_encode($output);
        }
        break;
    case "listar_detalle_usu":
        $datos = $usuario->get_usu_modal($_POST["depe_id"]);
        $data = array();
        foreach ($datos as $row) {
            $sub_array = array();
            // Generar el checkbox para seleccionar usuarios
            $sub_array[] = "<input type='checkbox' class='form-check-input' name='detallecheck[]' value='" . $row["pers_id"] . "'>";

            // Mostrar DNI
            $sub_array[] = $row["pers_dni"];

            // Mostrar nombre completo
            $nombre_completo = $row["pers_nombre"] . " " . $row["pers_apelpat"] . " " . $row["pers_apelmat"];
            $sub_array[] = $nombre_completo;

            // Mostrar rol (perfil)
            $sub_array[] = $row["perf_id"];

            $data[] = $sub_array;
        }

        // Devolver los resultados en formato JSON
        $results = array(
            "sEcho" => 1,
            "iTotalRecords" => count($data),
            "iTotalDisplayRecords" => count($data),
            "aaData" => $data,
        );
        echo json_encode($results);
        break;


    case "listar_area_usu":
        $datos = $usuario->get_area_usu_x_id($_POST["depe_id"]);
        $data = array();
        foreach ($datos as $row) {
            $sub_array = array();
            $sub_array[] = $row["pers_dni"];
            $sub_array[] = $row["pers_nombre"];
            $sub_array[] = $row["pers_apelpat"];
            $sub_array[] = $row["pers_apelmat"];
            $sub_array[] = '<button type="button" onClick="eliminar(' . $row["areausu_id"] . ');" id="' . $row["areausu_id"] . '" class="btn btn-outline-danger btn-icon" title="Quitar usuario del área"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-trash"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg></button>';
            $data[] = $sub_array;
        }

        $results = array(
            "sEcho" => 1,
            "iTotalRecords" => count($data),
            "iTotalDisplayRecords" => count($data),
            "aaData" => $data
        );
        echo json_encode($results);
        break;
}
