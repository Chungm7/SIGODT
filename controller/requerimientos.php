<?php

require_once("../config/conexion.php");

require_once("../models/Requerimientos.php");
require_once("../models/Bitacora.php");
    $bitacora = new Bitacora();

    $requerimientos = new Requerimientos();

switch ($_GET["op"]) {

    case "guardaryeditar":
        if (empty($_POST["req_id"])) {
            $requerimientos->insert_requerimientos($_POST["req_nom"]);
            $bitacora->update_bitacora($_SESSION["usua_id_SIGODT"]);
        } else {
            $requerimientos->update_requerimientos($_POST["req_id"], $_POST["req_nom"]);
            $bitacora->update_bitacora($_SESSION["usua_id_SIGODT"]);
        }
        break;
    case "mostrartreqproced":
        $datos = $requerimientos->get_requerimientos_editar($_POST["reqproced_id"]);
        if(is_array($datos)==true and count($datos)<>0){
            foreach($datos as $row){
                $output["reqproced_id"] = $row["reqproced_id"];
                $output["req_nom"] = $row["req_nom"];
                $output["reqproced_pos"] = $row["reqproced_pos"];
               
            }
            echo json_encode($output);
        }
        break;
    case "mostrar":
        $datos = $requerimientos->get_requerimientos_id($_POST["req_id"]);
        if (is_array($datos) == true and count($datos) <> 0) {
            foreach ($datos as $row) {
                $output["req_id"] = $row["req_id"];
                $output["req_nom"] = $row["req_nom"];
            }
            echo json_encode($output);
        }
        break;

    case "eliminar":
        $requerimientos->delete_requerimientos($_POST["req_id"]);
        $bitacora->update_bitacora($_SESSION["usua_id_SIGODT"]);
        break;
    case "reqEditar":
        $requerimientos->update_reqproced($_POST["reqproced_id"],$_POST["req_pos"]);
        $bitacora->update_bitacora($_SESSION["usua_id_SIGODT"]);
    break;

    case "listar":
        $datos = $requerimientos->get_requerimientos();
        $data = array();
        foreach ($datos as $row) {
            $sub_array = array();
            $sub_array[] = $row["req_nom"];
            $sub_array[] = '<button type="button" onClick="editar(' . $row["req_id"] . ');"  id="' . $row["req_id"] . '" class="btn btn-outline-warning btn-icon"><div><i class="fa fa-edit"></i></div></button>';
            $sub_array[] = '<button type="button" onClick="eliminar(' . $row["req_id"] . ');"  id="' . $row["req_id"] . '" class="btn btn-outline-danger btn-icon"><div><i class="fa fa-close"></i></div></button>';
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
    case "listar_proceds_requerimientos":
        $datos = $requerimientos->get_proced_requerimientos_x_id($_POST["proced_id"],$_POST["tupa_id"],$_POST["area_id"]);
        $data = array();
        foreach ($datos as $row) {
            $sub_array = array();
            $sub_array[] = $row["req_nom"];
            $sub_array[] = empty($row["reqproced_pos"]) ? '<span style="color: red;">null</span>' : $row["reqproced_pos"];
            $tupa_estado = $row["tupa_block"];
            $editButton = '<button type="button" onClick="editar(' . $row["reqproced_id"] . ');"  id="' . $row["reqproced_id"] . '" class="btn btn-outline-primary btn-icon"';
            $deleteButton = '<button type="button" onClick="eliminar(' . $row["reqproced_id"] . ');"  id="' . $row["reqproced_id"] . '" class="btn btn-outline-danger btn-icon"';
            if($tupa_estado != 0) {
                // Si el estado no es 1, desactiva los botones
                $editButton .= ' disabled';
                $deleteButton .= ' disabled';
            }
            // Cierre de las etiquetas de los botones
            $editButton .= '><div><i class="fa fa-pencil"></i></div></button>';
            $deleteButton .= '><div><i class="fa fa-close"></i></div></button>';
            $sub_array[] = $editButton;
            $sub_array[] = $deleteButton;
           
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
    case "eliminar_proced_req":
        $requerimientos->delete_proced_req($_POST["reqproced_id"]);
        $bitacora->update_bitacora($_SESSION["usua_id_SIGODT"]);
        break;
    case "listar_detalle_requerimientos":
        $datos = $requerimientos->get_requerimientos_modal($_POST["proced_id"]);
        $data = array();
        foreach ($datos as $row) {
            $sub_array = array();
            $sub_array[] = "<input type='checkbox'  style='scale:1.5; cursor:pointer;' name='detallecheck[]' value='" . $row["req_id"] . "'>";
            $sub_array[] = $row["req_nom"];
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
    case "insert_proced_req":
        $datos = explode(',', $_POST['req_id']);
        $data = Array();
        foreach($datos as $row){
            $sub_array = array();
            $idx=$requerimientos->insert_proced_req($_POST["proced_id"],$row);
            $sub_array[] = $idx;
            $data[] = $sub_array;
            $bitacora->update_bitacora($_SESSION["usua_id_SIGODT"]);
        }

        echo json_encode($data);
        break;
    case "update_requerimientosproced":
        $tamite->update_requerimientosproced($_POST["reqproced_id"], $_POST["reqproced_pos"]);
        $bitacora->update_bitacora($_SESSION["usua_id_SIGODT"]);
}
