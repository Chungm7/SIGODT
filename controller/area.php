<?php

require_once("../config/conexion.php");

require_once("../models/Area.php");
require_once("../models/Bitacora.php");
$bitacora = new Bitacora();
$area = new Area();

switch ($_GET["op"]) {
    case "mostrar":
        $datos = $area->get_depe_id($_POST["depe_id"]);
        if (is_array($datos) == true and count($datos) <> 0) {
            foreach ($datos as $row) {
                $output["depe_id"] = $row["depe_id"];
                $output["depe_denominacion"] = $row["depe_denominacion"];
            }
            echo json_encode($output);
        }
        break;
    case "listar":
        $datos = $area->get_area();
        $data = array();
        foreach ($datos as $row) {
            $sub_array = array();
            $sub_array[] = $row["depe_denominacion"];
            $sub_array[] = $row["depe_codigo"];
            $sub_array[] = $row["depe_representante"];
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
        $datos = $area->get_area();
        if (is_array($datos) == true and count($datos) > 0) {
            $html = " <option label='Seleccione'></option>";
            foreach ($datos as $row) {
                $html .= "<option value='" . $row['depe_id'] . "'>" . $row['depe_denominacion'] . "</option>";
            }
            echo $html;
        }
        break;
    case "insert_area_usu":
        $datos = explode(',', $_POST['pers_id']);
        $data = array();
        foreach ($datos as $row) {
            $sub_array = array();
            $idx = $area->insert_area_usu($_POST["depe_id"], $row);
            $bitacora->update_bitacora($_SESSION["usua_id_SIGODT"]);
            $sub_array[] = $idx;
            $data[] = $sub_array;
        }

        echo json_encode($data);
        break;
    case "eliminar_area_usu":
        $area->eliminar_area_usu($_POST["areausua_id"]);
        $bitacora->update_bitacora($_SESSION["usua_id_SIGODT"]);
        break;
}
