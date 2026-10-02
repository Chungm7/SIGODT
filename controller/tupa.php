<?php

require_once("../config/conexion.php");

require_once("../models/Tupa.php");
require_once("../models/Bitacora.php");
$bitacora = new Bitacora();
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$tupa = new tupa();

switch ($_GET["op"]) {

    case "guardaryeditar":
        if (empty($_POST["tupa_id"])) {
            $tupa->insert_tupa($_POST["tupa_nom"], $_POST["tupa_año"], $_POST["tupa_tipo"]);
            $bitacora->update_bitacora($_SESSION["usua_id_SIGODT"]);
        } else {
            $tupa->update_tupa($_POST["tupa_id"], $_POST["tupa_nom"], $_POST["tupa_año"], $_POST["tupa_tipo"]);
            $bitacora->update_bitacora($_SESSION["usua_id_SIGODT"]);
        }
        break;

    case "mostrar":
        $datos = $tupa->get_tupa_id($_POST["tupa_id"]);
        if (is_array($datos) == true and count($datos) <> 0) {
            foreach ($datos as $row) {
                $output["tupa_id"] = $row["tupa_id"];
                $output["tupa_nom"] = $row["tupa_nom"];
                $output["tupa_año"] = $row["tupa_año"];
                $output["tipo_doc"] = $row["tipo_doc"];
                $output["tupa_tipo"] = $row["tipo_doc"];
            }
            echo json_encode($output);
        }
        break;

    case "eliminar":
        $tupa->delete_tupa($_POST["tupa_id"]);
        $bitacora->update_bitacora($_SESSION["usua_id_SIGODT"]);
        break;
    case "bloquear":
        $tupa->bloquear_tupa($_POST["tupa_id"]);
        $bitacora->update_bitacora($_SESSION["usua_id_SIGODT"]);
        break;
    case "duplicar":
        $cantant = $bitacora->get_max_id()[0]["bita_id"];

        $tupa->duplicar_tupa($_POST["tupa_id"]);
        $bitacora->update_bitacora_grupo($_SESSION["usua_id_SIGODT"], $cantant);
        break;
    case "desbloquear_tupa":
        $tupa->desbloquear_tupa($_POST["tupa_id"]);
        $bitacora->update_bitacora($_SESSION["usua_id_SIGODT"]);
        break;
    case "activar_tupa":
        $tupa->activar_tupa($_POST["tupa_id"]);
        $bitacora->update_bitacora($_SESSION["usua_id_SIGODT"]);
        break;
    case "desactivar_tupa":
        $tupa->desactivar_tupa($_POST["tupa_id"]);
        $bitacora->update_bitacora($_SESSION["usua_id_SIGODT"]);
        break;
    case "listar":
        $datos = $tupa->get_docs();
        $data = array();
        foreach ($datos as $row) {
            $sub_array = array();
            $sub_array["tupa_id"] = (int)$row["tupa_id"];
            $sub_array["tupa_nom"] = $row["tupa_nom"];
            $sub_array["tupa_año"] = $row["tupa_año"];
            $sub_array["tipo_doc"] = !empty($row["tipo_doc"]) ? $row["tipo_doc"] : 'TUPA';
            $sub_array["est"] = (int)$row["est"];
            $sub_array["tupa_block"] = (string)$row["tupa_block"];
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

    case "combo":
        $datos = $tupa->get_tupa();
        if (is_array($datos) == true and count($datos) > 0) {
            $html = " <option label='Seleccione'></option>";
            foreach ($datos as $row) {
                $html .= "<option value='" . $row['tupa_id'] . "' data-estado='" . $row['tupa_block'] . "'>" . $row['tupa_nom'] . "</option>";
            }
            echo $html;
        }
        break;
      case "combo_ti":
        $datos = $tupa->get_tupa_tusnet();
        if (is_array($datos) == true and count($datos) > 0) {
            $html = " <option label='Seleccione'></option>";
            foreach ($datos as $row) {
                $html .= "<option value='" . $row['tupa_id'] . "' data-estado='" . $row['tupa_block'] . "'>" . $row['tupa_nom'] . "</option>";
            }
            echo $html;
        }
        break;
   // Caso en el controlador PHP para obtener el TUPA y TUSNE vigentes
case "gettupa_vigente":
    try {
        $datos = $tupa->get_tupa(); // Llamamos al método para obtener los TUPA y TUSNE

        $output = array();

        // Verificamos si hay datos válidos para el TUPA y TUSNE
        foreach ($datos as $row) {
            if ($row['est'] == 2) { // Solo si el estado es 2 (vigente)
                if ($row['tipo_doc'] == 'TUPA') {
                    // Si es TUPA
                    $output['tupa_id'] = $row['tupa_id'];
                    $output['tupa_nom'] = $row['tupa_nom'];
                } elseif ($row['tipo_doc'] == 'TUSNE') {
                    // Si es TUSNE
                    $output['tusne_id'] = $row['tupa_id'];  // Usamos tupa_id para tusne_id
                    $output['tusne_nom'] = $row['tupa_nom']; // Usamos tupa_nom para tusne_nom
                }
            }
        }

        // Enviar la respuesta con los datos obtenidos
        echo json_encode($output);

    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    break;

    case "combo_usu":
        $datos = $tupa->get_tupa_usu();
        if (is_array($datos) == true and count($datos) > 0) {
            $html = " <option label='Seleccione'></option>";
            foreach ($datos as $row) {
                $html .= "<option value='" . $row['tupa_id'] . "' data-estado='" . $row['est'] . "'>" . $row['tupa_nom'] . "</option>";
            }
            echo $html;
        }
        break;
}
