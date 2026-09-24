<?php

require_once("../config/conexion.php");

require_once("../models/Persona.php");
require_once("../models/Bitacora.php");
$bitacora = new Bitacora();
$persona = new Persona();

switch ($_GET["op"]) {

    case "buscarDNI":
        $datos = $persona->get_persona_dni($_POST["pers_dni"]);
        if (is_array($datos) == true and count($datos) <> 0) {
            foreach ($datos as $row) {
                $output["pers_id"] = $row["pers_id"];
                $output["pers_dni"] = $row["pers_dni"];
                $output["nombre_completo"] = $row["nombre_completo"];
            }
            echo json_encode($output);
        }
        break;

}
