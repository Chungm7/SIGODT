<?php
require_once(__DIR__ . "/../base/Response.php");
require_once(__DIR__ . "/../config/conexion.php");
require_once(__DIR__ . "/../models/Ciudadano.php");
require_once(__DIR__ . "/../models/Vehiculo.php");
require_once(__DIR__ . "/../models/Bitacora.php");


class TokenHelper
{
    public static function getKey(): string
    {
        return Conectar::getEnv('PIDE_SECRET_KEY', 'sgd*2023');
    }

    public static function encrypt(string $plain): string
    {
        return openssl_encrypt($plain, 'AES-128-ECB', self::getKey());
    }

    public static function decrypt(string $cipher): ?string
    {
        return openssl_decrypt($cipher, 'AES-128-ECB', self::getKey());
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
            // Credenciales PIDE desde entorno
            $usuario = Conectar::getEnv('PIDE_RENIEC_USER', '20250001');
            $contrasena = Conectar::getEnv('PIDE_RENIEC_PASS', '20250001@');

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

                    $usuario = Conectar::getEnv('PIDE_RENIEC_USER', '20250001');
                    $contrasena = Conectar::getEnv('PIDE_RENIEC_PASS', '20250001@');
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

                    $usuario = Conectar::getEnv('PIDE_RENIEC_USER', '20250001');
                    $contrasena = Conectar::getEnv('PIDE_RENIEC_PASS', '20250001@');
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
            $usuario = Conectar::getEnv('PIDE_RENIEC_USER', '20250001');
            $contrasena = Conectar::getEnv('PIDE_RENIEC_PASS', '20250001@');
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

    case "listar":
        $datos = $ciudadano->listarCiudadanos();
        echo json_encode(["success" => true, "data" => $datos]);
        break;

    case "listar_tipos":
        $tipos = $ciudadano->get_tito_doc();
        echo json_encode([
            "success" => true,
            "data"    => $tipos
        ]);
        break;

    case "listar_tabla":
        $search       = $_POST["search"]       ?? "";
        $start        = intval($_POST["start"]  ?? 0);
        $length       = intval($_POST["length"] ?? 10);
        $order_column = $_POST["order_column"] ?? "0";
        $order_dir    = in_array(strtolower($_POST["order_dir"] ?? ""), ["asc", "desc"])
            ? $_POST["order_dir"] : "desc";

        $datos = $ciudadano->list_ciudadano($search, $start, $length, $order_column, $order_dir);
        $total = $ciudadano->get_total_ciudadano($search);

        $data = [];
        foreach ($datos as $r) {
            if ($r["ciud_estado"] === "A") {
                $estadoClass = "green";
                $estadoTexto = "Activo";
                $iconCambio  = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" stroke="orange" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon dropdown-item-icon"><path stroke="none" d="M0 0h24v24H0z"/><path d="M18 6L6 18M6 6l12 12"/></svg> Inactivar';
            } else {
                $estadoClass = "red";
                $estadoTexto = "Inactivo";
                $iconCambio  = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" stroke="green" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon dropdown-item-icon"><path stroke="none" d="M0 0h24v24H0z"/><path d="M5 12l5 5L20 7"/></svg> Activar';
            }

            $acciones = '
            <div class="dropdown">
             <a href="#" class="btn dropdown-toggle" data-bs-toggle="dropdown">
                         <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-nut"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M19 6.84a2.007 2.007 0 0 1 1 1.754v6.555c0 .728 -.394 1.4 -1.03 1.753l-6 3.844a1.995 1.995 0 0 1 -1.94 0l-6 -3.844a2.006 2.006 0 0 1 -1.03 -1.752v-6.557c0 -.728 .394 -1.399 1.03 -1.753l6 -3.582a2.049 2.049 0 0 1 2 0l6 3.582h-.03z" /><path d="M12 12m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" /></svg>
                    </a>
              <div class="dropdown-menu">
                <a class="dropdown-item" href="#" onclick="editar(' . $r["ciud_id"] . ', \'' . addslashes($r["ciud_nombre"] . ' ' . $r["ciud_primer_apellido"]) . '\')">
                  <svg xmlns="http://www.w3.org/2000/svg" class="icon dropdown-item-icon" width="24" height="24" stroke="orange" fill="none" stroke-width="2"><path stroke="none" d="M0 0h24v24H0z"/><path d="M4 20h4l10.5 -10.5a1.5 1.5 0 1 0 -4 -4L4 16v4z"/></svg> Editar
                </a>
                <a class="dropdown-item" href="#" onclick="cambiarEstado(' . $r["ciud_id"] . ', \'' . $r["ciud_estado"] . '\')">
                  ' . $iconCambio . '
                </a>
              </div>
            </div>';

            $data[] = [
                $r["ciud_id"],
                $r["tipo_documento"],
                $r["ciud_numero_documento"],
                $r["ciud_primer_apellido"],
                $r["ciud_segundo_apellido"],
                $r["ciud_nombre"],
                "<span class='badge bg-{$estadoClass} text-{$estadoClass}-fg'>{$estadoTexto}</span>",
                $acciones
            ];
        }

        echo json_encode([
            "draw"            => intval($_POST["draw"] ?? 0),
            "recordsTotal"    => $total,
            "recordsFiltered" => $total,
            "data"            => $data
        ]);
        break;

    case "crear":
        $fotoInput = $_POST["ciud_foto"] ?? null;
        $d = [
            "ciud_numero_documento" => $_POST["ciud_numero_documento"],
            "ciud_primer_apellido"  => $_POST["ciud_primer_apellido"],
            "ciud_segundo_apellido" => $_POST["ciud_segundo_apellido"],
            "ciud_nombre"           => $_POST["ciud_nombre"],
            "ciud_foto"             => $fotoInput !== '' ? $fotoInput : null,
            "ciud_fecha_nac"        => $_POST["ciud_fecha_nac"],
            "ciud_sexo"             => $_POST["ciud_sexo"],
            "tido_id"               => $_POST["tido_id"]
        ];

        if ($ciudadano->existeDocumento($d["ciud_numero_documento"], $d["tido_id"])) {
            echo json_encode([
                "success" => false,
                "message" => "Ya existe un ciudadano con ese número y tipo de documento."
            ]);
            break;
        }

        if ($ciudadano->insertar($d)) {
            echo json_encode([
                "success" => true,
                "message" => "Ciudadano registrado correctamente."
            ]);
        } else {
            echo json_encode([
                "success" => false,
                "message" => "Ocurrió un error al registrar el ciudadano."
            ]);
        }
        break;

    case "mostrar":
        $id = $_POST["ciud_id"];
        $r = $ciudadano->mostrar($id);
        echo json_encode($r);
        break;

    case "editar":
        $fotoInput = $_POST["ciud_foto"] ?? null;
        $d = [
            "ciud_numero_documento" => $_POST["ciud_numero_documento"],
            "ciud_primer_apellido"  => $_POST["ciud_primer_apellido"],
            "ciud_segundo_apellido" => $_POST["ciud_segundo_apellido"],
            "ciud_nombre"           => $_POST["ciud_nombre"],
            "ciud_foto"             => $fotoInput !== '' ? $fotoInput : null,
            "ciud_fecha_nac"        => $_POST["ciud_fecha_nac"],
            "ciud_sexo"             => $_POST["ciud_sexo"],
            "tido_id"               => $_POST["tido_id"]
        ];
        echo json_encode([
            "success" => $ciudadano->editar($_POST["ciud_id"], $d)
        ]);
        break;

    case "cambiar_estado":
        echo json_encode([
            "success" => $ciudadano->inactivar($_POST["ciud_id"])
        ]);
        break;
}
?>