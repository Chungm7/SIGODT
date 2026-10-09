<?php
// New read-only operations have their own boundary; legacy handlers remain unchanged.
$nombreOperation = $_GET['op'] ?? '';
if (in_array($nombreOperation, ['buscar_entidades_nombre', 'historial_entidad'], true)) {
    ini_set('display_errors', '0');
    header('Content-Type: application/json; charset=utf-8');
    if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
    if (!isset($_SESSION['usua_id_SIGODT'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Sesión no válida. Inicie sesión nuevamente.']);
        exit;
    }
    // Reject malformed input before loading connection configuration or touching the DB.
    $page = filter_var($_POST['page'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1000000000]]);
    $limit = filter_var($_POST['limit'] ?? 10, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 100]]);
    $valid = $page !== false && $limit !== false;
    if ($nombreOperation === 'buscar_entidades_nombre') {
        $search = $_POST['search'] ?? null;
        $valid = $valid && is_string($search) && trim($search) !== '' && strlen($search) <= 200 && preg_match('//u', $search) === 1;
    } else {
        $type = $_POST['entity_type'] ?? null;
        $key = $_POST['entity_key'] ?? null;
        $valid = $valid && is_string($key) && strlen($key) <= 200 && preg_match('//u', $key) === 1 &&
            (($type === 'ciudadano' && preg_match('/^[1-9][0-9]*$/D', $key)) ||
             ($type === 'empresa' && (preg_match('/^(id|sin):[1-9][0-9]*$/D', $key) ||
              (strpos($key, 'ruc:') === 0 && trim(substr($key, 4)) !== '' && substr($key, 4) === trim(substr($key, 4))))));
    }
    if (!$valid) {
        http_response_code(400);
        echo json_encode(['error' => 'Criterio, entidad o paginación inválidos.']);
        exit;
    }
    ob_start();
    try {
        require_once(__DIR__ . '/../config/conexion.php');
        require_once(__DIR__ . '/../models/OrdenGiro.php');
        $model = new Ordengiro();
        $arguments = Ordengiro::validar_nombre_input($nombreOperation, $_POST);
        $result = call_user_func_array([$model, $nombreOperation], $arguments);
        ob_end_clean();
        echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    } catch (Throwable $error) {
        ob_end_clean();
        http_response_code(500);
        echo json_encode(['error' => 'No se pudo realizar la consulta. Intente nuevamente.']);
    }
    exit;
}
require_once(__DIR__ . "/../base/Response.php");
require_once(__DIR__ . "/../config/conexion.php");
require_once(__DIR__ . "/../models/OrdenGiro.php");
require_once(__DIR__ . "/../models/Bitacora.php");

error_reporting(E_ALL);
ini_set('display_errors', 1);

$bitacora = new Bitacora();
$ordengiro =  new Ordengiro();
switch ($_GET["op"]) {

    case "consultarOrden":
        $datos = $ordengiro->get_orden_id($_POST["ordengiro_id"]);

        if (is_array($datos) && count($datos) > 0) {
            $output = array();

            foreach ($datos as $row) {
                $output["ogciud_id"] = $row["ogciud_id"];
                $output["fecha"] = $row["fecha"];
                $output["hora"] = $row["hora"];
                $output["nombre_completo"] = $row["nombre_completo"];
                $output["nombre_ciudadano"] = $row["nombre_ciudadano"];
                $output["ciudadano_dni"] = $row["ciudadano_dni"];
                $output["importe"] = $row["importe"];
                $output["proced_nom"] = $row["proced_nom"];
                $output["area_nom"] = $row["area_nom"];
                $output["tasa_nom"] = $row["tasa_nom"];
                $output["proced_tipoindvasc"] = $row["proced_tipoindvasc"];
                $output["vehi_placa"] = $row["vehi_placa"];
                $output["empr_ruc"] = $row["empr_ruc"];
                $output["empr_razon_social"] = $row["empr_razon_social"];
                $output["empr_direccion"] = $row["empr_direccion"];
                $output["proced_tupa"] = $row["proced_tupa"];
                $output["ogciud_comentario"] = $row["ogciud_comentario"];
                $output["ciud_domicilio_real"] = $row["ciud_domicilio_real"];
                $output["tido_id"] = $row["tido_id"];
            }

            echo json_encode($output);
        } else {
            $error_message = "No se ha encontrado información para la orden proporcionada.";
            echo json_encode(array("error" => $error_message));
        }
        break;
    case "getOrdenes":
        $datos = $ordengiro->get_ordenes();

        if (is_array($datos) && count($datos) > 0) {
            $output = array();

            foreach ($datos as $row) {
                $item = array(
                    "ogciud_id" => $row["ogciud_id"],
                    "fechacrea" => $row["fechacrea"],
                    "pers_id" => $row["pers_id"],
                    "est" => $row["est"],
                    "ogciud_comentario" => $row["ogciud_comentario"],
                    "recibo_nro" => $row["recibo_nro"]
                );
                $output[] = $item; // Agrega cada fila como un nuevo elemento en $output
            }
            echo json_encode($output);
        } else {
            $error_message = "No se ha encontrado información para la orden proporcionada.";
            echo json_encode(array("error" => $error_message));
        }
        break;
    case "getTasasOrdenes":
        $datos = $ordengiro->get_tasas_ordenes();

        if (is_array($datos) && count($datos) > 0) {
            $output = array();

            foreach ($datos as $row) {
                $item = array(
                    "tasatciud_id" => $row["tasatciud_id"],
                    "tasatciud_tasaproced" => $row["tasatciud_tasaproced"],
                    "est" => $row["estadotasat"],
                    "tasatciud_procedciud" => $row["tasatciud_procedciud"],
                    "fechacrea" => $row["fechacrea"]
                );
                $output[] = $item; // Agrega cada fila como un nuevo elemento en $output
            }
            echo json_encode($output);
        } else {
            $error_message = "No se ha encontrado información para la orden proporcionada.";
            echo json_encode(array("error" => $error_message));
        }
        break;
    case "getTasasSemana":
        $datos = $ordengiro->get_tasas_semana();

        if (is_array($datos) && count($datos) > 0) {
            $output = array();

            foreach ($datos as $row) {
                $item = array(
                    "tasatciud_id" => $row["tasatciud_id"],
                    "tasatciud_tasaproced" => $row["tasatciud_tasaproced"],
                    "estadotasat" => $row["estadotasat"],
                    "importe" => $row["importe"],
                    "fechacrea" => $row["fechacrea"]

                );
                $output[] = $item; // Agrega cada fila como un nuevo elemento en $output
            }
            echo json_encode($output);
        } else {
            $error_message = "No se ha encontrado información para la orden proporcionada.";
            echo json_encode(array("error" => $error_message));
        }
        break;
    case "getgirosUsuarios":
        $datos = $ordengiro->get_ordenes_Usuario();

        if (is_array($datos) && count($datos) > 0) {
            $output = array();

            foreach ($datos as $row) {
                $item = array(
                    "ogciud_id" => $row["ogciud_id"],
                    "pers_id" => $row["pers_id"],
                    "fechacrea" => $row["fechacrea"],
                    "area_nom" => $row["area_nom"],
                    "area_id" => $row["area_id"],
                    "nombrecompleto" => $row["nombrecompleto"]

                );
                $output[] = $item; // Agrega cada fila como un nuevo elemento en $output
            }
            echo json_encode($output);
        } else {
            $error_message = "No se ha encontrado información para la orden proporcionada.";
            echo json_encode(array("error" => $error_message));
        }
        break;
    case "getgirosUsuarios_totales":
        // Obtener los totales de órdenes de usuario
        $datos_totales = $ordengiro->get_ordenes_total_usuario($_SESSION['usua_id_SIGODT']);
        
        if (is_array($datos_totales) && count($datos_totales) > 0) {
            // Utilizaremos solo el primer registro, ya que todos los registros serán iguales en este caso
            $row = $datos_totales[0];

            // Construimos el array de salida con los tres campos deseados
            $output = array(
                "total_general" => $row["total_general"],
                "total_dia" => $row["total_dia"],
                "total_ayer" => $row["total_ayer"]
            );

            echo json_encode($output);
        } else {
            $error_message = "No se ha encontrado información para la orden proporcionada.";
            echo json_encode(array("error" => $error_message));
        }
        break;


    case "getgirosUsuariosAreas":
        $datos = $ordengiro->get_ordenes_Usuario_area();

        if (is_array($datos) && count($datos) > 0) {
            $output = array();

            foreach ($datos as $row) {
                $item = array(
                    "ogciud_id" => $row["ogciud_id"],
                    "pers_id" => $row["pers_id"],
                    "fechacrea" => $row["fechacrea"],
                    "area_nom" => $row["area_nom"],
                    "tasa_id" => $row["tasa_id"],
                    "cod_ref" => $row["cod_ref"],
                    "tasa_nom" => $row["tasa_nom"],
                    "proced_nom" => $row["proced_nom"],
                    "area_id" => $row["area_id"],
                    "tasatciud_id" => $row["tasatciud_id"],
                    "tasatciud_tasaproced" => $row["tasatciud_tasaproced"],
                    "estadotasat" => $row["estadotasat"],
                    "tasatciud_procedciud" => $row["tasatciud_procedciud"],
                    "girot_id" => $row["girot_id"],
                    "girot_fechacrea" => $row["fechacrea"],
                    "girot_tasaciud_id" => $row["tasaciud_id"],
                    "girot_cantidad" => $row["cantidad"],
                    "girot_importe" => $row["importe"],
                    "estadotasagirada" => $row["estadotasagirada"],
                    "girot_giro" => $row["girot_giro"],
                    "nombrecompleto" => $row["nombrecompleto"]
                );
                $output[] = $item; // Agrega cada fila como un nuevo elemento en $output
            }
            echo json_encode($output);
        } else {
            $error_message = "No se ha encontrado información para la orden proporcionada.";
            echo json_encode(array("error" => $error_message));
        }
        break;
    case "gettasasprocedimientos":
        $datos = $ordengiro->get_Tasas_Area($_POST["area_id"], $_POST["tupa_id"], $_POST["proced_id"]);

        if (is_array($datos) && count($datos) > 0) {
            $output = array();

            foreach ($datos as $row) {
                $item = array(

                    "fechacrea" => $row["fechacrea"],
                    "estadotasat" => $row["estadotasat"],
                    "tasatciud_procedciud" => $row["tasatciud_procedciud"],
                    "girot_id" => $row["girot_id"],
                    "girot_fechacrea" => $row["fechacrea"],
                    "girot_tasaciud_id" => $row["tasaciud_id"],
                    "girot_cantidad" => $row["cantidad"],
                    "girot_importe" => $row["importe"],
                    "estadotasagirada" => $row["estadotasagirada"],
                    "tasa_nom" => $row["tasa_nom"],
                    "tasa_id" => $row["tasa_id"]

                );
                $output[] = $item; // Agrega cada fila como un nuevo elemento en $output
            }
            echo json_encode($output);
        } else {
            $error_message = "No se ha encontrado información para la orden proporcionada.";
            echo json_encode(array("error" => $error_message));
        }
        break;

    case "get_ordenes_giro":
        $documento = $_POST["documento"] ?? "";
        $data = $ordengiro->get_ordenes_giro($documento);
        echo json_encode($data);
        break;

    case "get_ordenes_mes":
        $search = isset($_POST["search"]) ? trim($_POST["search"]) : "";
        $page = isset($_POST["page"]) ? intval($_POST["page"]) : 1;
        $limit = isset($_POST["limit"]) ? intval($_POST["limit"]) : 10;
        $offset = ($page - 1) * $limit;

        $data = $ordengiro->get_ordenes_mes($search, $limit, $offset);
        $total = $ordengiro->get_total_ordenes_mes($search);

        echo json_encode([
            "data" => $data,
            "total" => $total,
            "page" => $page,
            "limit" => $limit
        ]);
        break;

    case "get_orden_giro":
        $ogciud_id = isset($_POST["ogciud_id"]) ? $_POST["ogciud_id"] : null;
        $data = $ordengiro->get_orden_giro_by_id($ogciud_id);

        if ($data) {
            echo json_encode(["status" => "success", "data" => $data]);
        } else {
            echo json_encode(["status" => "error", "message" => "No se encontró la Orden de Giro"]);
        }
        break;
}

?>