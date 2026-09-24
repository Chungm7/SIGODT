<?php
   
    require_once("../config/conexion.php");

    require_once("../models/Vehiculo.php");
    require_once("../models/Bitacora.php");
    $bitacora = new Bitacora();
    $vehiculo = new Vehiculo();

    switch($_GET["op"]){
       
        case "guardaryeditar":
            if(empty($_POST["vehiculo_id"])){
                $vehiculo->insert_vehiculo($_POST["vehiculo_nom"]);
                $bitacora->update_bitacora($_SESSION["usua_id_SIGODT"]);
            }else{
                $bitacora->update_bitacora($_SESSION["usua_id_SIGODT"]);
            }
            break;
       
        case "mostrar":
            $datos = $vehiculo->get_vehiculo_x_placa($_POST["vehiculo_id"]);
            if(is_array($datos)==true and count($datos)<>0){
                foreach($datos as $row){
                    $output["vehiculo_id"] = $row["vehiculo_id"];
                    $output["vehiculo_nom"] = $row["vehiculo_nom"];
                }
                echo json_encode($output);
            }
            break;
   
        case "eliminar":
          
            $bitacora->update_bitacora($_SESSION["usua_id_SIGODT"]);
            break;
     
        case "listar":
            $datos=$vehiculo->get_vehiculo_x_empresa($_POST['empr_id']);
            $data= Array();
            foreach($datos as $row){
                $sub_array = array();
                $sub_array[] = $row["vehi_placa"];
                $sub_array[] = $row["empr_razon_social"];
                $sub_array[]  = $row["vehi_annofab"];
                $año = $row["vehi_annofab"];
            
                $estado_html = '';
                
                // Verificar si el año de fabricación es mayor a 10 años
                if(date("Y") - $año > 20) {
                    $estado_html = '<div style="background-color:#dc3545; display: flex; align-items: center; justify-content: center; height: 5px; text-align: center; color: white; border-radius: 12px; box-shadow: 0px 8px 15px rgba(0, 0, 0, 0.1); padding: 15px 10px;">Improcedente</div>';
                } else {
                    $estado_html = '<div style="background-color: #28a745; display: flex; align-items: center; justify-content: center; height: 5px; text-align: center; color: white; border-radius: 12px; box-shadow: 0px 8px 15px rgba(0, 0, 0, 0.1); padding: 15px 10px;">Procedente </div>';
                }
                
                $sub_array[] = $estado_html;
                $sub_array[] = "<input type='checkbox' name='detallecheck[]' value='" . $row["tiv_id"] . "'>";
                $data[] = $sub_array;
            }
            $results = array(
                "sEcho"=>1,
                "iTotalRecords"=>count($data),
                "iTotalDisplayRecords"=>count($data),
                "aaData"=>$data);
            echo json_encode($results);
            break;
        case "combo":
            $datos=$vehiculo->get_vehiculo();
            if(is_array($datos)==true and count($datos)>0){
                $html= " <option label='Seleccione'></option>";
                foreach($datos as $row){
                    $html.= "<option value='".$row['vehiculo_id']."'>".$row['vehiculo_nom']."</option>";
                }
                echo $html;
            }
            break;
        case "insert_vehiculo_grupo":
            $id =  $vehiculo->get_prced_empr_id();
            foreach($id as $row){
                $id_grupo =  $row['procedempr_id'];
            }
            
            $datos = explode(',', $_POST['tiv_id']);
            $data = Array();
            foreach($datos as $row){
                $sub_array = array();
                $idx=$vehiculo->insert_vehiculo_grupo($row,$id_grupo);
                $bitacora->update_bitacora($_SESSION["usua_id_SIGODT"]);
                $sub_array[] = $idx;
                $data[] = $sub_array;
            }
            echo json_encode($data);
            break;
            case "getGrupo":
                $datos=$vehiculo->get_vehiculo_x_grupo($_POST['tasatempresa_id']);
                $data= Array();
                foreach($datos as $row){
                    $sub_array = array();
                    $sub_array[] = $row["vehi_placa"];
                    $html= '<input type="number" class="form-control"  style="max-width: 200px;" id="years"data-placa="' . $row["vehi_placa"] . '"placeholder="Ingrese los años">';
                    $sub_array[] = $html;
                    $data[] = $sub_array;
                }
                $results = array(
                    "sEcho"=>1,
                    "iTotalRecords"=>count($data),
                    "iTotalDisplayRecords"=>count($data),
                    "aaData"=>$data);
                echo json_encode($results);
                break;
        case "consultar_placa":
                $datos = $vehiculo->get_vehiculo_x_placa($_POST["vehi_placa"]);
                if (is_array($datos) == true and count($datos) <> 0) {
                    foreach ($datos as $row) {
                        $output["tiv_id"] =  $row["tiv_id"]; 
                        $output["vehi_placa"] = $row["vehi_placa"];
                        $output["vehi_annofab"] = $row["vehi_annofab"];
                        $output["ciud_nombre"] = $row["ciud_nombre"];
                        $output["empr_razon_social"] = $row["empr_razon_social"];
                    }
                    echo json_encode($output);
                } else {
                    // El vehiculo no está en la base de datos local, realizar consulta a la API de Reniec
                    $url = 'https://www.munichiclayo.gob.pe/BE_DBCIMCIX/apiPide/sunarp/' . $_POST["vehi_placa"];
                    $curl = curl_init();
                    // Configurar la solicitud cURL
                    curl_setopt_array($curl, array(
                        CURLOPT_URL => $url,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_SSL_VERIFYPEER => 0,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 2,
                        CURLOPT_TIMEOUT => 0,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_CUSTOMREQUEST => 'GET',
                    ));
            
                    $response = curl_exec($curl);
            
                    // Comprobar si hubo un error en la solicitud
                    // En caso de error, devuelve un JSON indicando el error
                    if (curl_errno($curl)) {
                        $output = array("error" => "Error en la solicitud cURL: " . curl_error($curl));
                        echo json_encode($output);
                    } else {
                        // Formatear los datos de la API de Reniec de manera similar a los datos locales
                        $sunarpData = json_decode($response, true);
            
                        // Verificar si la API de Reniec devolvió datos válidos
                        if (isset($sunarpData["mensaje"])) {
                            if ($sunarpData["mensaje"] === "Consulta realizada correctamente") {
                                // La consulta se realizó correctamente, devolver los datos
                                $output["vehiculo_id"] = null;  // Asignar valor nulo ya que es un nuevo vehiculo
                                $output["placa"] = $sunarpData["data"]["placa"];
                                $output["serie"] = $sunarpData["data"]["serie"];
                                $output["vin"] = $sunarpData["data"]["vin"];
                                $output["nro_motor"] = $sunarpData["data"]["nro_motor"];
                                $output["color"] = $sunarpData["data"]["nro_motor"];
                                $output["marca"] = $sunarpData["data"]["marca"];
                                $output["modelo"] = $sunarpData["modelo"]["modelo"];
                                $output["estado"] = $sunarpData["data"]["estado"];
                                $output["sede"] = $sunarpData["data"]["sede"];
                                $output["anoFabricacion"] = $sunarpData["data"]["anoFabricacion"];
                                $output["codCategoria"] = $sunarpData["data"]["codCategoria"];
                                $output["codTipoCarr"] = $sunarpData["data"]["codTipoCarr"];
                                $output["carroceria"] = $sunarpData["data"]["carroceria"];
                                if (isset($sunarpData["data"]["propietarios"]["nombre"])) {
                                    $propietarios = $sunarpData["data"]["propietarios"]["nombre"];
                                    $output["propietarios"] = $propietarios;
                                } else {
                                    $output["propietarios"] = [];
                                }

                            } else {
                                // Se encontró un mensaje de error, devolver el mensaje de error
                                $output = array("error" => $sunarpData["mensaje"]);
                            }
                        } else {
                            // La respuesta de la API no contiene el mensaje esperado, devolver un mensaje de error genérico
                            $output = array("error" => "Error desconocido al procesar la respuesta de la API de Sunarp");
                        }
                        // Devolver la respuesta como JSON
                        echo json_encode($output);
                    }
            
                    // Cerrar la conexión cURL
                    curl_close($curl);
                }
                break;
 
    }
?>