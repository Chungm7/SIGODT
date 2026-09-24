<?php
require_once(__DIR__ . "/../base/Response.php");
require_once("../config/conexion.php");
require_once("../models/Ciudadano.php");
require_once("../models/Vehiculo.php");
require_once("../models/Bitacora.php");


class TokenHelper
{
    const SECRET_KEY = 'sgd*2023'; // Debe coincidir con el servidor

    public static function encrypt(string $plain): string
    {
        return openssl_encrypt($plain, 'AES-128-ECB', self::SECRET_KEY);
    }

    public static function decrypt(string $cipher): ?string
    {
        return openssl_decrypt($cipher, 'AES-128-ECB', self::SECRET_KEY);
    }
}
function response($message, $data = [], $suc = true, $httpStatusCode = 200)
{
    $status = $httpStatusCode;
    $success = $suc;
    $timestamp = date("Y-m-d H:i:s");
    http_response_code($httpStatusCode);
    echo json_encode(compact('timestamp', 'status', 'success', 'message', 'data'), JSON_UNESCAPED_UNICODE);
    exit();
}

$bitacora = new Bitacora();
$ciudadano = new Ciudadano();
$vehiculo = new Vehiculo();

switch ($_GET["op"]) {

    case "buscarciudadanoxDOC":
        $datos = $ciudadano->get_ciudadano_x_doc($_POST["ciudadano_doc"], 1);
        if (is_array($datos) == true and count($datos) <> 0) {
            foreach ($datos as $row) {
                $output["ciudadano_id"] = $row["ciud_id"];
                $output["ciudadano_dni"] = $row["ciud_numero_documento"];
                $output["ciudadano_nombre"] = $row["ciud_nombre"];
                $output["ciudadano_apep"] = $row["ciud_primer_apellido"];
                $output["ciudadano_apem"] = $row["ciud_segundo_apellido"];
                $output["ciudadano_direccion"] = $row["ciud_domicilio_real"];
                $output["ciud_sexo"] = $row["ciud_sexo"];
                $output["ciud_fecha_nac"] = $row["ciud_fecha_nac"];
                $output["ciud_foto"] = $row["ciud_foto"];
            }
            echo json_encode($output);
        }
        break;
    case "consultar_dni":
        $datos = $ciudadano->get_ciudadano_x_doc($_POST["ciudadano_doc"], '1');

        if (is_array($datos) == true and count($datos) <> 0) {
            foreach ($datos as $row) {
                $output["ciudadano_id"] = $row["ciud_id"];
                $output["ciudadano_dni"] = $row["ciud_numero_documento"];
                $output["ciudadano_nombre"] = $row["ciud_nombre"];
                $output["ciudadano_apep"] = $row["ciud_primer_apellido"];
                $output["ciudadano_apem"] = $row["ciud_segundo_apellido"];
                $output["ciudadano_direccion"] = $row["ciud_domicilio_real"];
                $output["ciud_sexo"] = $row["ciud_sexo"];
                $output["ciud_fecha_nac"] = $row["ciud_fecha_nac"];
                // Guardar la foto en una carpeta temporal
                if (!empty($row["ciud_foto"])) {
                    $imagen_base64 = $row["ciud_foto"];

                    // Si la cadena incluye el prefijo "data:image/...", eliminarlo
                    if (strpos($imagen_base64, 'data:image') === 0) {
                        $imagen_base64 = preg_replace('/^data:image\/\w+;base64,/', '', $imagen_base64);
                    }

                    // Opcional: registrar la longitud de la cadena base64 para verificar que no esté truncada
                    error_log("Longitud de la cadena base64: " . strlen($imagen_base64));

                    // Decodificar en modo estricto
                    $imagen_decodificada = base64_decode($imagen_base64, true);
                    if ($imagen_decodificada === false) {
                        error_log("Error al decodificar la imagen para DNI " . $_POST["ciudadano_doc"]);
                    }

                    // Definir la ruta donde se guardará la imagen
                    $ruta_carpeta = "uploads/ciudadanos/";
                    if (!file_exists($ruta_carpeta)) {
                        mkdir($ruta_carpeta, 0777, true); // Crear la carpeta si no existe
                    }

                    $nombre_archivo = "foto_" . $_POST["ciudadano_doc"] . ".png";
                    $ruta_imagen = $ruta_carpeta . $nombre_archivo;

                    // Guardar la imagen en el servidor y comprobar cuántos bytes se escribieron
                    $bytes_escritos = file_put_contents($ruta_imagen, $imagen_decodificada);
                    if ($bytes_escritos === false || $bytes_escritos == 0) {
                        error_log("Error al guardar la imagen en: " . $ruta_imagen);
                    }

                    // Devolver la ruta en la respuesta JSON (asegúrate de que la ruta sea accesible)
                    $output["ciud_foto"] = "../../controller/" . $ruta_imagen;
                } else {
                    $output["ciud_foto"] = "";
                }
            }
            echo json_encode($output);
        } else {
            // Datos de prueba - usando las credenciales exactas de la base de datos
            $usuario = '20250001';
            $contrasena = '20250001@';  // Exactamente como está en la base de datos

            // Generar token
            $token = TokenHelper::encrypt($contrasena);
            if (!isset($_POST["ciudadano_doc"])) {
                response("Debe ingresar un DNI", [], false, 400);
                exit;
            }
            // El ciudadano no está en la base de datos local, realizar consulta a la API de Reniec
            $url = 'https://www.munichiclayo.gob.pe/Pide/Reniec/' . $_POST["ciudadano_doc"];
            $data = [
                'usu_dni' => $usuario,
                'usu_contrasena' => $token
            ];

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

            // Ejecutar y capturar la respuesta
            $response = curl_exec($ch);

            // Comprobar si hubo un error en la solicitud
            if (curl_errno($ch)) {
                $output = array("error" => "Error en la solicitud cURL: " . curl_error($ch));
                echo json_encode($output);
            } else {
                // Formatear los datos de la API de Reniec de manera similar a los datos locales
                $reniecData = json_decode($response, true);

                if ($reniecData["data"]['restriccion'] === "CANCELADO" || $reniecData["data"]['restriccion'] === "FALLECIMIENTO") {
                    $estado = 'inprocedente';
                } else {
                    $estado = 'procede';
                }
                if (isset($reniecData["message"]) && $reniecData["message"] === "Consulta realizada correctamente" && $estado === 'procede') {
                    // La consulta se realizó correctamente, registrar al ciudadano en la base de datos local

                    $usuario = '20250001';
                    $contrasena = '20250001@';  // Exactamente como está en la base de datos
                    // Generar token
                    $token = TokenHelper::encrypt($contrasena);
                    $url_minsa = 'https://www.munichiclayo.gob.pe/Pide/Minsa/' . $_POST["ciudadano_doc"];
                    $data = [
                        'usu_dni' => $usuario,
                        'usu_contrasena' => $token
                    ];


                    $ch = curl_init($url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
                    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

                    // Ejecutar y capturar la respuesta
                    $response_minsa = curl_exec($ch);

                    // Decodificar la respuesta de Minsa
                    $minsaData = json_decode($response_minsa, true);

                    if (isset($minsaData["data"]["sexo"])) {
                        $output["ciud_sexo"] = $minsaData["data"]["sexo"];
                    } else {
                        $output["ciud_sexo"] = '';
                    }
                    if (isset($minsaData["data"]["fecnac"])) {
                        $output["ciud_fecha_nac"] = $minsaData["data"]["fecnac"];
                    } else {
                        $output["ciud_fecha_nac"] = '';
                    }
                    $ciud = $ciudadano->insert_ciudadano($_POST["ciudadano_doc"], $reniecData["data"]["prenombres"], $reniecData["data"]["apPrimer"], $reniecData["data"]["apSegundo"], $reniecData["data"]["direccion"], $reniecData["data"]["foto"], $minsaData["data"]["fecnac"], $minsaData["data"]["sexo"], 1, 1);
                    $bitacora->update_bitacora($_SESSION["usua_id_SIGODT"]);
                    $ciudadano_id = $ciudadano->getlastId();
                    // Verificar si se pudo insertar al ciudadano correctamente
                    if ($ciudadano_id) {
                        // Ciudadano insertado correctamente, devolver los datos junto con el ID del ciudadano registrado
                        $output["ciudadano_id"] = $ciudadano_id;
                        $output["ciudadano_dni"] = $_POST["ciudadano_doc"];
                        $output["ciudadano_nombre"] = $reniecData["data"]["prenombres"];
                        $output["ciudadano_apep"] = $reniecData["data"]["apPrimer"];
                        $output["ciudadano_apem"] = $reniecData["data"]["apSegundo"];
                        $output["ciudadano_direccion"] = $reniecData["data"]["direccion"];

                        if (!empty($reniecData["data"]["foto"])) {
                            $imagen_base64 = $reniecData["data"]["foto"];

                            // Si la cadena incluye el prefijo "data:image/...", eliminarlo
                            if (strpos($imagen_base64, 'data:image') === 0) {
                                $imagen_base64 = preg_replace('/^data:image\/\w+;base64,/', '', $imagen_base64);
                            }

                            // Opcional: registrar la longitud de la cadena base64 para verificar que no esté truncada
                            error_log("Longitud de la cadena base64: " . strlen($imagen_base64));

                            // Decodificar en modo estricto
                            $imagen_decodificada = base64_decode($imagen_base64, true);
                            if ($imagen_decodificada === false) {
                                error_log("Error al decodificar la imagen para DNI " . $_POST["ciudadano_doc"]);
                            }

                            // Definir la ruta donde se guardará la imagen
                            $ruta_carpeta = "uploads/ciudadanos/";
                            if (!file_exists($ruta_carpeta)) {
                                mkdir($ruta_carpeta, 0777, true); // Crear la carpeta si no existe
                            }

                            $nombre_archivo = "foto_" . $_POST["ciudadano_doc"] . ".png";
                            $ruta_imagen = $ruta_carpeta . $nombre_archivo;

                            // Guardar la imagen en el servidor y comprobar cuántos bytes se escribieron
                            $bytes_escritos = file_put_contents($ruta_imagen, $imagen_decodificada);
                            if ($bytes_escritos === false || $bytes_escritos == 0) {
                                error_log("Error al guardar la imagen en: " . $ruta_imagen);
                            }

                            // Devolver la ruta en la respuesta JSON (asegúrate de que la ruta sea accesible)
                            $output["ciud_foto"] = "../../controller/" . $ruta_imagen;
                        } else {
                            $output["ciud_foto"] = "";
                        }


                    } else {
                        // Si ocurrió un error al insertar al ciudadano, devolver un mensaje de error
                        $output = array("error" => "Error al insertar al ciudadano en la base de datos.");
                    }
                } else if ($reniecData["message"] === "El número de DNI corresponde a un menor de edad") {

                    $usuario = '20250001';
                    $contrasena = '20250001@';  // Exactamente como está en la base de datos
                    // Generar token
                    $token = TokenHelper::encrypt($contrasena);
                    $url_minsa = 'https://www.munichiclayo.gob.pe/Pide/Minsa/' . $_POST["ciudadano_doc"];
                    $data = [
                        'usu_dni' => $usuario,
                        'usu_contrasena' => $token
                    ];


                    $ch = curl_init($url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
                    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

                    // Ejecutar y capturar la respuesta
                    $response_minsa = curl_exec($ch);

                    // Decodificar la respuesta de Minsa
                    $minsaData = json_decode($response_minsa, true);
                    if (empty($minsaData["data"]["nombres"]) ||
                        empty($minsaData["data"]["apep"]) ||
                        empty($minsaData["data"]["apem"])) 
                    {
                        $output = array("error" => "No se puede continuar: DNI inválido o corresponde a menor de edad");
                    } else {
                        if (isset($minsaData["data"]["sexo"])) {
                            $output["ciud_sexo"] = $minsaData["data"]["sexo"];
                        }
                        if (isset($minsaData["data"]["fecnac"])) {
                            $output["ciud_fecha_nac"] = $minsaData["data"]["fecnac"];
                        }
                        $ciud = $ciudadano->insert_ciudadano($_POST["ciudadano_doc"], $minsaData["data"]["nombres"], $minsaData["data"]["apep"], $minsaData["data"]["apem"], $reniecData["data"]["direccion"], '', $minsaData["data"]["fecnac"], $minsaData["data"]["sexo"], 1, 1);
                        $bitacora->update_bitacora($_SESSION["usua_id_SIGODT"]);
                        $output["ciudadano_id"] = $ciud;
                        $output["ciudadano_dni"] = $_POST["ciudadano_doc"];
                        $output["ciudadano_nombre"] = $minsaData["data"]["nombres"];
                        $output["ciudadano_apep"] = $minsaData["data"]["apep"];
                        $output["ciudadano_apem"] = $minsaData["data"]["apem"];
                        $output["ciudadano_direccion"] = '';
                    }
                } else {
                    if ($estado === 'inprocedente') {
                        $output = array("error" => $reniecData["data"]['restriccion']);
                    } else {
                        // La API de Reniec devolvió un mensaje de error, devolver ese mensaje como error
                        $output = array("error" => $reniecData["message"]);
                    }
                }
                // Devolver la respuesta como JSON
                echo json_encode($output);
            }

            // Cerrar la conexión cURL
            curl_close($ch);
        }
        break;

    case "consultar_carnet":
        $datos = $ciudadano->get_ciudadano_x_doc($_POST["ciudadano_doc"], "3");
        if (is_array($datos) == true and count($datos) <> 0) {
            foreach ($datos as $row) {
                $output["ciudadano_id"] = $row["ciud_id"];
                $output["ciudadano_dni"] = $row["ciud_numero_documento"];
                $output["ciudadano_nombre"] = $row["ciud_nombre"];
                $output["ciudadano_apep"] = $row["ciud_primer_apellido"];
                $output["ciudadano_apem"] = $row["ciud_segundo_apellido"];
                $output["ciudadano_direccion"] = $row["ciud_domicilio_real"];
                $output["ciud_sexo"] = $row["ciud_sexo"];
                $output["ciud_fecha_nac"] = $row["ciud_fecha_nac"];
                $output["ciud_foto"] = $row["ciud_foto"];
            }
            echo json_encode($output);
        } else {
            // El ciudadano no está en la base de datos local, realizar consulta a la API de extranjería
            $usuario = '20250001';
            $contrasena = '20250001@';  // Exactamente como está en la base de datos
            // Generar token
            $token = TokenHelper::encrypt($contrasena);
            $url = 'https://www.munichiclayo.gob.pe/Pide/Migraciones/' . $_POST["ciudadano_doc"];
            $data = [
                'usu_dni' => $usuario,
                'usu_contrasena' => $token
            ];

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

            // Ejecutar y capturar la respuesta
            $response = curl_exec($ch);
            // Comprobar si hubo un error en la solicitud
            if (curl_errno($ch)) {
                $output = array("error" => "Error en la solicitud cURL: " . curl_error($ch));
                echo json_encode($output);
                exit;
            }
            curl_close($ch);

            // Decodificar la respuesta de la API
            $extranjeriaData = json_decode($response, true);

            if (isset($extranjeriaData["data"])) {
                // La API de extranjería devolvió datos correctamente
                $data = $extranjeriaData["data"];

                if (isset($data["nombres"]) && !empty($data["nombres"])) {
                    // La consulta se realizó correctamente, registrar al ciudadano en la base de datos local
                    try {
                        $apellidoPaterno = isset($data["apep"]) ? $data["apep"] : "";
                        $apellidoMaterno = isset($data["apem"]) ? $data["apem"] : "";

                        $ciud = $ciudadano->insert_ciudadano($_POST["ciudadano_doc"], $data["nombres"], $apellidoPaterno, $apellidoMaterno, '', '', '', '', 1, 3);
                        $bitacora->update_bitacora($_SESSION["usua_id_SIGODT"]);
                    } catch (Exception $e) {
                        // Manejo de la excepción, puedes imprimir un mensaje de error, registrar el error en un archivo de registro, etc.
                        echo 'Se produjo un error: ' . $e->getMessage();
                        exit;
                    }

                    $ciudadano_id = $ciudadano->getlastId();
                    // Verificar si se pudo insertar al ciudadano correctamente
                    if ($ciudadano_id) {
                        // Ciudadano insertado correctamente, devolver los datos junto con el ID del ciudadano registrado
                        $output["ciudadano_id"] = $ciudadano_id;
                        $output["ciudadano_dni"] = $_POST["ciudadano_doc"];
                        $output["ciudadano_nombre"] = $data["nombres"];
                        $output["ciudadano_apep"] = $apellidoPaterno;
                        $output["ciudadano_apem"] = $apellidoMaterno;
                        $output["ciudadano_direccion"] = '';
                        $output["ciud_sexo"] = '';
                        $output["ciud_fecha_nac"] = '';
                    } else {
                        // Si ocurrió un error al insertar al ciudadano, devolver un mensaje de error
                        $output = array("error" => "Error al insertar al ciudadano en la base de datos.");
                    }
                } else {
                    // La API de extranjería devolvió un mensaje de error, devolver ese mensaje como error
                    $output = array("error" => "No se encontró información");
                }
            } else {
                // La API de extranjería devolvió un mensaje de error, devolver ese mensaje como error
                $output = array("mensaje" => $extranjeriaData["message"]);
            }
            // Devolver la respuesta como JSON
            echo json_encode($output);
        }
        break;

    case "consultar_CPP":
        $datos = $ciudadano->get_ciudadano_x_doc($_POST["ciudadano_doc"], "4");
        if (is_array($datos) == true and count($datos) <> 0) {
            foreach ($datos as $row) {
                $output["ciudadano_id"] = $row["ciud_id"];
                $output["ciudadano_dni"] = $row["ciud_numero_documento"];
                $output["ciudadano_nombre"] = $row["ciud_nombre"];
                $output["ciudadano_apep"] = $row["ciud_primer_apellido"];
                $output["ciudadano_apem"] = $row["ciud_segundo_apellido"];
                $output["ciudadano_direccion"] = $row["ciud_domicilio_real"];
                $output["ciud_sexo"] = $row["ciud_sexo"];
                $output["ciud_fecha_nac"] = $row["ciud_fecha_nac"];
            }
            echo json_encode($output);
        } else {
            // El ciudadano no está en la base de datos local, realizar consulta a la API de extranjería
            $url = 'https://www.munichiclayo.gob.pe/BE_DBCIMCIX/apiPide/cpp/v2/' . $_POST["ciudadano_doc"];
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
                // Formatear los datos de la API de extranjería de manera similar a los datos locales
                $extranjeriaData = json_decode($response, true);
                if (isset($extranjeriaData["Nombres"]) && !empty($extranjeriaData["Nombres"])) {
                    // La consulta se realizó correctamente, registrar al ciudadano en la base de datos local
                    $apellidoPaterno = isset($extranjeriaData["ApellidoPaterno"]) ? $extranjeriaData["ApellidoPaterno"] : "";
                    $apellidoMaterno = isset($extranjeriaData["ApellidoMaterno"]) ? $extranjeriaData["ApellidoMaterno"] : "";
                    $ciud = $ciudadano->insert_ciudadano($_POST["ciudadano_doc"], $extranjeriaData["Nombres"], $apellidoPaterno, $apellidoMaterno, '', '', '', '', 1, 4);
                    $bitacora->update_bitacora($_SESSION["usua_id_SIGODT"]);
                    $ciudadano_id = $ciudadano->getlastId();
                    // Verificar si se pudo insertar al ciudadano correctamente
                    if ($ciudadano_id) {
                        // Ciudadano insertado correctamente, devolver los datos junto con el ID del ciudadano registrado
                        $output["ciudadano_id"] = $ciudadano_id;
                        $output["ciudadano_dni"] = $_POST["ciudadano_doc"];
                        $output["ciudadano_nombre"] = $extranjeriaData["Nombres"];
                        $output["ciudadano_apep"] = $apellidoPaterno;
                        $output["ciudadano_apem"] = $apellidoMaterno;
                        $output["ciud_sexo"] = '';
                        $output["ciud_fecha_nac"] = '';
                    } else {
                        // Si ocurrió un error al insertar al ciudadano, devolver un mensaje de error
                        $output = array("error" => "Error al insertar al ciudadano en la base de datos.");
                    }
                } else {
                    // La API de extranjería devolvió un mensaje de error, devolver ese mensaje como error
                    $output = array("error" => "No se encontraron datos de carnet CPP");
                }
                // Devolver la respuesta como JSON
                echo json_encode($output);
            }
            // Cerrar la conexión cURL
            curl_close($curl);
        }
        break;
    case "insertar_ciud_grupo":
        $id = $vehiculo->get_prced_empr_id();
        foreach ($id as $row) {
            $id_grupo = $row['procedempr_id'];
        }

        $ids = $_POST["ciud_id"];
        foreach ($ids as $ciud_id) {
            $ciudadano->insert_ciudadano_grupo($ciud_id, $id_grupo);
            $bitacora->update_bitacora($_SESSION["usua_id_SIGODT"]);
        }
        break;
    case "actualizardireccion":
        $ciudadano->actualizarDir($_POST['ciud_id'], $_POST['direccion']);
        break;
    case "actualizardatos":
        $ciudadano->actualizar_sexo_fecha_nac($_POST['ciud_dni'], $_POST['ciud_sexo'], $_POST['ciud_fecha_nac']);
        break;
}
?>