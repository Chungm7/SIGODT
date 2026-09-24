<?php
require_once("../config/conexion.php");
require_once("../models/Ventanilla.php");
$ventanilla = new Ventanilla();

switch ($_GET["op"]) {
    case "get_ventanillas":
        $data = $ventanilla->get_ventanillas();
        echo json_encode($data);
        break;

    case "insert_ventanilla":


        if (!isset($_POST["categ_id"], $_POST["pers_id"], $_POST["venta_nmr"], $_POST["venta_horario_inio"], $_POST["venta_horario_fin"])) {
            echo json_encode(["status" => "error", "message" => "Datos incompletos"]);
            exit;
        }

        try {
            $ventanilla->insert_ventanilla(
                $_POST["categ_id"],
                $_POST["pers_id"],
                $_POST["venta_nmr"],
                $_POST["venta_horario_inio"],
                $_POST["venta_horario_fin"]
            );
            echo json_encode(["status" => "success", "message" => "Ventanilla registrada con éxito"]);
        } catch (Exception $e) {
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        break;

    case "update_ventanilla":
        // Asegúrate de que estos valores existan en $_POST
        if (isset($_POST["venta_id"], $_POST["categ_id"], $_POST["pers_id"], $_POST["venta_nmr"], $_POST["venta_horario_inio"], $_POST["venta_horario_fin"], $_POST["venta_est"])) {
            $ventanilla->update_ventanilla(
                $_POST["venta_id"],
                $_POST["categ_id"],
                $_POST["pers_id"],
                $_POST["venta_nmr"],
                $_POST["venta_horario_inio"],
                $_POST["venta_horario_fin"],
                $_POST["venta_est"]
            );
            echo json_encode(["status" => "success", "message" => "Ventanilla actualizada con éxito"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Datos incompletos"]);
        }
        break;

    case "get_ventanilla":
        if (!isset($_POST["id"])) {
            echo json_encode(["status" => "error", "message" => "ID no proporcionado"]);
            exit;
        }

        $venta_id = $_POST["id"];
        $data = $ventanilla->get_ventanilla_by_id($venta_id);
        if ($data) {
            echo json_encode($data);
        } else {
            echo json_encode(["status" => "error", "message" => "Ventanilla no encontrada"]);
        }
        break;
        case 'update_estado':
            // Verificamos que se hayan enviado los datos requeridos
            if (isset($_POST['venta_id']) && isset($_POST['venta_est'])) {
                $venta_id = $_POST['venta_id'];
                $venta_est = $_POST['venta_est'];
    
                // Llamamos a la función del modelo para actualizar el estado
                $result = $ventanilla->update_ventanilla_est($venta_id, $venta_est);
                
                // Devolvemos una respuesta en formato JSON
                if ($result) {
                    echo json_encode(["status" => "success", "message" => "Estado actualizado correctamente"]);
                } else {
                    echo json_encode(["status" => "error", "message" => "No se pudo actualizar el estado"]);
                }
            } else {
                echo json_encode(["status" => "error", "message" => "Datos incompletos"]);
            }
            break;  
}
?>