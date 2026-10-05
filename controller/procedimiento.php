<?php
require_once("../config/conexion.php");
require_once("../models/Procedimiento.php");
require_once("../models/Bitacora.php");
$bitacora = new Bitacora();
$proced = new Procedimiento();
 ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); 
switch ($_GET["op"]) {
    case "guardaryeditar":
        if (empty($_POST["proced_id"])) {
            $proced->insert_proced($_POST["tupa_id"], $_POST["area_id"], $_POST["proced_cod"], $_POST["proced_nom"], $_POST["tipo_campo"], $_POST["tipo_administrado"], $_POST["proced_tipoindvasc"]);
            $bitacora->update_bitacora($_SESSION["usua_id_SIGODT"]);
        } else {
            $proced->update_proced($_POST["proced_id"], $_POST["proced_cod"], $_POST["proced_nom"], $_POST["tipo_campo"], $_POST["tipo_administrado"], $_POST["proced_tipoindvasc"]);
            $bitacora->update_bitacora($_SESSION["usua_id_SIGODT"]);
        }
        break;
    case "abrirproced":
        if (empty($_POST["ciud_id"]) || empty($_POST["proced_id"])) {
            echo json_encode([
                "success" => false,
                "message" => "El ID del ciudadano y el ID del procedimiento son obligatorios."
            ]);
            exit;
        }

        $cantant = $bitacora->get_max_id()[0]["bita_id"];
        $empr_id = !empty($_POST["empr_id"]) ? $_POST["empr_id"] : null;
        $ruc = !empty($_POST["empr_ruc"]) ? $_POST["empr_ruc"] : null;
        $razon_social = !empty($_POST["empr_razon_social"]) ? $_POST["empr_razon_social"] : null;
        // 🔹 Crear el procedimiento del ciudadano y obtener el ID generado
        $procedciudadanoID = $proced->crearProcedCiud(
            $_POST["ciud_id"],
            $_POST["proced_id"],
            $empr_id,
            $_SESSION["usua_id_SIGODT"],
            $ruc,
            $razon_social
        );

        if (!$procedciudadanoID) {
            echo json_encode([
                "success" => false,
                "message" => "Error al registrar el procedimiento del ciudadano."
            ]);
            exit;
        }

        // 🔹 Actualizar código del procedimiento y obtener el código generado
        $procedCodigo = $proced->actualizarCod($_POST["proced_id"], $procedciudadanoID);

        if (!$procedCodigo) {
            echo json_encode([
                "success" => false,
                "message" => "Error al actualizar el código del procedimiento."
            ]);
            exit;
        }

        // 🔹 Insertar tasas asociadas al procedimiento y verificar
        $tasasInsertadas = $proced->updateTasas($_POST["proced_id"], $procedciudadanoID);

        if ($tasasInsertadas === false) {
            echo json_encode([
                "success" => false,
                "message" => "Error al registrar las tasas asociadas."
            ]);
            exit;
        }

        // 🔹 Actualizar bitácora
        $bitacora->update_bitacora_grupo($_SESSION["usua_id_SIGODT"], $cantant);

        // 🔹 Enviar respuesta de éxito con el código generado
        echo json_encode([
            "success" => true,
            "message" => "Procedimiento registrado correctamente.",
            "codigo" => $procedCodigo,
            "procedciudadano_id" => (int)$procedciudadanoID
        ]);
        exit;



    case "mostrar":
        $datos = $proced->get_proced_id($_POST["proced_id"]);
        if (is_array($datos) == true and count($datos) <> 0) {
            foreach ($datos as $row) {
                $output["proced_id"] = $row["proced_id"];
                $output["proced_nom"] = $row["proced_nom"];
                $output["proced_cod"] = $row["proced_cod"];
                $output["proced_area"] = $row["proced_area"];
                $output["proced_administradotipo"] = $row["proced_administradotipo"];
                $output["proced_tipocampo"] = $row["proced_tipocampo"];
                $output["proced_tipoindvasc"] = $row["proced_tipoindvasc"];
            }
            echo json_encode($output);
        }
        break;
    case "mostrar_procedciudadano":
        $datos = $proced->get_procedciudadano_idprocedciudadano($_POST["procedciudadano_id"]);
        if (is_array($datos) == true and count($datos) <> 0) {
            foreach ($datos as $row) {
                $output["procedciudadano_id"] = $row["procedciudadano_id"];
                $output["ciudadano_nombre"] = $row["ciud_nombre"];
                $output["ciud_id"] = $row["ciud_id"];
                $output["ciudadano_apep"] = $row["ciud_primer_apellido"];
                $output["ciudadano_apem"] = $row["ciud_segundo_apellido"];
                $output["ciudadano_doc"] = $row["ciud_numero_documento"];
                $output["ciudadano_tipo"] = $row["proced_tipoindvasc"];
                $output["ciudadano_placa"] = $row["vehi_placa"];
                $output["ciud_domicilio_real"] = $row["ciud_domicilio_real"];
                $output["ciud_sexo"] = $row["ciud_sexo"];
                $output["ciud_fecha_nac"] = $row["ciud_fecha_nac"];
                $output["empr_ruc"] = $row["empr_ruc"];
                $output["empr_razon_social"] = $row["empr_razon_social"];
                $output["empr_nombre_comercial"] = $row["empr_nombre_comercial"];
            }
            echo json_encode($output);
        }
        break;

    case "eliminar":
        $proced->delete_proced($_POST["proced_id"]);
        $bitacora->update_bitacora($_SESSION["usua_id_SIGODT"]);
        break;
    case "eliminar_proced_ciudadano":
        $cantant = $bitacora->get_max_id()[0]["bita_id"];
        $orden_giro = isset($_POST["orden_giro"]) && $_POST["orden_giro"] !== "null" ? $_POST["orden_giro"] : null;
        $proced->delete_proced_ciudadano($_POST["proceciudadano_id"], $orden_giro);
        $bitacora->update_bitacora_grupo($_SESSION["usua_id_SIGODT"], $cantant);
        break;
    case "cambiar_procedencia_proced_ciudadano":
        $cantant = $bitacora->get_max_id()[0]["bita_id"];
        $proced->cambiar_procedencia_proced_ciudadano($_POST["proceciudadano_id"]);
        $bitacora->update_bitacora_grupo($_SESSION["usua_id_SIGODT"], $cantant);
        break;

    case "getAreas_usu":
        if ($_SESSION["rol_id_SIGODT"] == 9) {
            // Rol 9 obtiene todas las áreas
            $datos = $proced->get_todas_areas();
        } else {
            // Otros roles obtienen solo sus áreas asignadas
            $datos = $proced->get_Areas_usu($_SESSION["usua_id_SIGODT"]);
        }

        if (is_array($datos) && count($datos) > 0) {
            $html = "<option label='Seleccione'></option>";
            foreach ($datos as $row) {
                $html .= "<option value='" . $row['depe_id'] . "'>" . $row['depe_denominacion'] . "</option>";
            }
            echo $html;
        }
        break;

    case "listar":
        $datos = $proced->listar_proceds($_POST["area_id"], $_POST["tupa_id"]);
        $data = array();
        foreach ($datos as $row) {
            $sub_array = array();
            $sub_array[] = $row["proced_id"];
            $sub_array[] = $row["proced_cod"];
            $sub_array[] = $row["proced_nom"];
            $fecha_original = $row["fechacrea"];
            $fecha = date("d/m/Y h:i A", strtotime($fecha_original));
            $sub_array[] = $fecha;

            $obligatorio = $row["proced_tipocampo"];
            if ($obligatorio == "1") {
                $sub_array[] = '<span class="badge bg-secondary-lt" title="No obligatorios: se pagan de forma independiente.">No Obligatorios</span>';
            } else if ($obligatorio == "2") {
                $sub_array[] = '<span class="badge bg-blue-lt" title="Obligatorios: se pagan en estricto orden de prioridad.">Obligatorios</span>';
            } else {
                $sub_array[] = '<span class="badge bg-warning-lt" title="Obligatorio sin unidad: pago conjunto de tasas.">Obligatorios (Sin Unidad)</span>';
            }

            $administrado_tipo = $row["proced_administradotipo"];
            if ($administrado_tipo == "C") {
                $sub_array[] = '<span class="badge bg-azure-lt">Ciudadano</span>';
            } else if ($administrado_tipo == "E") {
                $sub_array[] = '<span class="badge bg-purple-lt">Ciudadano y Empresa</span>';
            } else {
                $sub_array[] = '<span class="badge bg-teal-lt">Ambos (Empresa opcional)</span>';
            }

            $administrado_indv = $row["proced_tipoindvasc"];
            if ($administrado_indv == "V") {
                $sub_array[] = '<span class="badge bg-orange-lt">Vehículo</span>';
            } else {
                $sub_array[] = '<span class="badge bg-cyan-lt">Ciudadano</span>';
            }

            $sub_array[] = '<button type="button" onClick="editar(' . $row["proced_id"] . ');" id="' . $row["proced_id"] . '" class="btn btn-outline-warning btn-icon" title="Editar"><svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-edit" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1" /><path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z" /><path d="M16 5l3 3" /></svg></button>';
            $sub_array[] = '<button type="button" onClick="eliminar(' . $row["proced_id"] . ');" id="' . $row["proced_id"] . '" class="btn btn-outline-danger btn-icon" title="Eliminar"><svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-trash" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg></button>';
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
        $datos = $proced->get_proced_area_tupa($_POST["area_id"], $_POST["tupa_id"]);
        if (is_array($datos) == true and count($datos) > 0) {
            $html = " <option label='Seleccione'></option>";
            foreach ($datos as $row) {
                $html .= "<option value='" . $row['proced_id'] . "' data-tipo='" . $row['proced_tipoindvasc'] . "'>" . $row['proced_cod'] . '-' . $row['proced_nom'] . "</option>";
            }
            echo $html;
        }
        break;

    case "combo_tupa_tusne":
        $datos = $proced->get_proced_area_tupa_tusne($_POST["area_id"], $_POST["tupa_id"]);
        if (is_array($datos) == true and count($datos) > 0) {
            $html = " <option label='Seleccione'></option>";
            foreach ($datos as $row) {
                $html .= "<option value='" . $row['proced_id'] . "' data-tipo='" . $row['proced_tipoindvasc'] . "'>" . $row['proced_cod'] . '-' .  $row['proced_nom'] . "</option>";
            }
            echo $html;
        }
        break;
    case "mostrar_detalle":
        $datos = $proced->get_procedimiento_detalle($_POST["proced_id"]);
        echo json_encode($datos);
        break;

    case "comb":
        $datos = $proced->get_proced();
        if (is_array($datos) == true and count($datos) > 0) {
            $html = " <option label='Seleccione'></option>";
            foreach ($datos as $row) {
                $html .= "<option value='" . $row['proced_id'] . "'>" . $row['proced_nom'] . "</option>";
            }
            echo $html;
        }
        break;
    case "eliminar_proced_tasa":
        $proced->delete_proced_tasa($_POST["tasaproced_id"]);

        $bitacora->update_bitacora($_SESSION["usua_id_SIGODT"]);
        break;

    case "insert_proced_tasa":
        $datos = explode(',', $_POST['tasa_id']);
        $data = array();
        foreach ($datos as $row) {
            $sub_array = array();
            $idx = $proced->insert_proced_tasa($_POST["proced_id"], $row);
            $proced->update_cod_ref();
            $sub_array[] = $idx;
            $data[] = $sub_array;
            $bitacora->update_bitacora($_SESSION["usua_id_SIGODT"]);
        }

        echo json_encode($data);
        break;
    case "mostrartasaproced":
        $datos = $proced->get_tasaproced_id($_POST["tasaproced_id"]);
        if (is_array($datos) == true and count($datos) <> 0) {
            foreach ($datos as $row) {
                $output["tasaproced_id"] = $row["tasaproced_id"];
                $output["tasa_nom"] = $row["tasa_nom"];
                $output["proced_nom"] = $row["proced_nom"];
                $output["tasaproced_pos"] = $row["tasaproced_pos"];
                $output["tasaproced_monto"] = $row["tasaproced_monto"];
                $output["cod_ref"] = $row["cod_ref"];
                $output["desc_tasa"] = $row["desc_tasa"]; // Asegúrate de enviar también la descripción
                $output["is_multiplica"] = $row["is_multiplica"]; // Enviar el valor de is_multiplica
            }
            echo json_encode($output);
        }
        break;

    case "getTipoProc":
        $datos = $proced->get_tipoProc($_POST["tasaproced_id"]);

        foreach ($datos as $row) {
            $tipo = $row["proced_tipoindvasc"];
        }
        echo ($tipo);
        break;
    case "get_proces_by_id":
        $datos = $proced->get_proces_by_id($_POST["proced_id"]);

        // Verifica si hay datos antes de enviarlos
        if (!empty($datos)) {
            echo json_encode($datos, JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(["error" => "No se encontraron datos"]);
        }
        break;

    case "get_data_proced_ciud":
        $datos = $proced->get_data_proced_ciud($_POST["proceciudadano_id"]);

        // Recorrer cada registro para procesar la imagen (si existe)
        foreach ($datos as &$row) {
            if (!empty($row["ciud_foto"])) {
                $imagen_base64 = $row["ciud_foto"];

                // Si el string incluye el prefijo "data:image/...", eliminarlo
                if (strpos($imagen_base64, 'data:image') === 0) {
                    $imagen_base64 = preg_replace('/^data:image\/\w+;base64,/', '', $imagen_base64);
                }

                // Opcional: Verificar la longitud de la cadena
                // error_log("Longitud de la cadena base64: " . strlen($imagen_base64));

                // Decodificar la imagen en modo estricto
                $imagen_decodificada = base64_decode($imagen_base64, true);
                if ($imagen_decodificada !== false) {
                    // Definir la ruta donde se guardará la imagen
                    $ruta_carpeta = "uploads/ciudadanos/";
                    if (!file_exists($ruta_carpeta)) {
                        mkdir($ruta_carpeta, 0777, true);
                    }

                    // Usar el documento del ciudadano como nombre de archivo
                    $nombre_archivo = "foto_" . $row["ciud_numero_documento"] . ".png";
                    $ruta_imagen = $ruta_carpeta . $nombre_archivo;

                    // Guardar la imagen y verificar que se hayan escrito bytes
                    $bytes_escritos = file_put_contents($ruta_imagen, $imagen_decodificada);
                    if ($bytes_escritos !== false && $bytes_escritos > 0) {
                        // Reemplazar el contenido de la imagen por la ruta accesible
                        $row["ciud_foto"] = "../../controller/" . $ruta_imagen;
                    } else {
                        error_log("Error al guardar la imagen en: " . $ruta_imagen);
                        $row["ciud_foto"] = "";
                    }
                } else {
                    error_log("Error al decodificar la imagen para el documento: " . $row["ciud_numero_documento"]);
                    $row["ciud_foto"] = "";
                }
            } else {
                $row["ciud_foto"] = "";
            }
        }

        echo json_encode($datos);
        break;

    case "tasaEditar":
        try {
            $proced->update_tasaproced(
                $_POST["tasaproced_id"],
                $_POST["tasaproced_pos"],
                $_POST["tasaproced_monto"],
                $_POST["cod_ref"],
                $_POST["desc_tasa"],
                $_POST["is_multiplica"]
            );
            // Siempre limpia y corta la salida
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        } catch (Exception $e) {
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
            exit;
        }
        break;


    case "listar_proced_ciudadano":
        $datos = $proced->get_procedciudadano_id_admin($_POST["proced_id"]);
        $data = array();

        foreach ($datos as $row) {
            $sub_array = array();
            $estado = '';
            $color = '';
            $color_barra = '';

            switch ($row["est"]) {
                case 0:
                    $estado = "Anulado";
                    $color = "#dc3545"; // Rojo
                    $color_barra = 'danger';
                    break;
                case 1:
                    $estado = "Pendiente";
                    $color = "#ffc107"; // Amarillo
                    $color_barra = 'warning';
                    break;
                case 2:
                    $estado = "Girado";
                    $color = "#28a745"; // Verde
                    $color_barra = 'success';
                    break;
                case 3:
                    $estado = "Improcedente";
                    $color = "#dc3545"; // Rojo
                    $color_barra = 'danger';
                    break;
                case 4:
                    $estado = "Pagado";
                    $color = "#007bff"; // Azul
                    $color_barra = 'primary';
                    break;
                case 5:
                    $estado = "Usado";
                    $color = "#6f42c1"; // Morado
                    $color_barra = 'purple';
                    break;
                default:
                    $estado = "Extornado";
                    $color = "#000000"; // Negro
                    $color_barra = 'warning';
                    break;
            }

            $porcentaje = number_format($row["pagadas"] / $row["total"] * 100, 2);

            $sub_array[] = $row["procedciudadano_cod"];
            $sub_array[] = $row["ciudadano_dni"];
            $sub_array[] = $row["nombre_completo"];
            $fecha_formateada = date("Y-m-d H:i:s", strtotime($row["fechacrea"]));
            $sub_array[] = $fecha_formateada;

            $estado_html = '<div style="background-color: ' . $color . '; display: flex; align-items: center; justify-content: center; height: 5px; text-align: center; color: white; border-radius: 12px; box-shadow: 0px 8px 15px rgba(0, 0, 0, 0.1); padding: 15px 10px;">' . $estado . ' </div>';
            $sub_array[] = $estado_html;

            $progress_bar_html = '<div class="progress mg-b-20" style="font-size: 13px;display: flex; align-items: center;text-align: center; margin-block:20px;">
                    <div class="progress-bar bg-' . $color_barra . ' wd-35p" role="progressbar" aria-valuenow="80" style="width:' . $porcentaje . '%"; aria-valuemin="0" aria-valuemax="100">' . $porcentaje . '%</div>
                    </div>';
            $sub_array[] = $progress_bar_html;

            $procedencia_button = ($row["est"] == 3) ? '<button type="button" onClick="setProcedencia(' . $row["proceciudadano_id"] . ');" id="' . $row["proceciudadano_id"] . '" class="btn btn-outline-success btn-icon"><div><i class="fa fa-check"></i></div></button>' : '<button type="button" onClick="setProcedencia(' . $row["proceciudadano_id"] . ');" id="' . $row["proceciudadano_id"] . '" class="btn btn-outline-danger btn-icon"><div><i class="fa fa-ban"></i></div></button>';
            $sub_array[] = $procedencia_button;

            $sub_array[] = '<button type="button" onClick="editar(' . $row["proceciudadano_id"] . ');"  id="' . $row["proceciudadano_id"] . '" class="btn btn-outline-warning btn-icon"><div><i class="fa fa-edit"></i></div></button>';
            $sub_array[] = '<button type="button" onClick="eliminar(' . $row["proceciudadano_id"] . ');"  id="' . $row["proceciudadano_id"] . '" class="btn btn-outline-danger btn-icon" ><div><div><i class="fa fa-trash"></i></div></button>';
            $sub_array[] = '<button type="button" onClick="ver(' . $row["proceciudadano_id"] . ');"  id="' . $row["proceciudadano_id"] . '" class="btn btn-outline-success btn-icon"><div><i class="fa fa-file"></i></div></button>';

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

    case "listar_proced_ciudadano_usuario":

        $datos = $proced->get_procedciudadano_id($_POST["proced_id"]);


        $data = array();
        foreach ($datos as $row) {
            $sub_array = array();
            $estado = '';
            $color = '';
            $color_barra = '';

            $est = $row["est"];

            // convertir vacío o null real a 1
            if ($est === null || $est === "") {
                $est = 9;
            }

            switch ($est) {
                case 0:
                    $estado = "Anulado";
                    $color = "#dc3045"; // Amarillo
                    $color_barra = 'danger';
                    break;
                
                case 9:
                    $estado = "Pendiente";
                    $color = "#ffc107"; // Amarillo
                    $color_barra = 'warning';
                    break;

                case 1:
                    $estado = "Girado";
                    $color = "#28a745"; // Verde
                    $color_barra = 'success';
                    break;
                case 3:
                    $estado = "Improcedente";
                    $color = "#dc3045"; // Rojo
                    $color_barra = 'danger';
                    break;
                case 4:
                    $estado = "Pagado";
                    $color = "#007bff";
                    $color_barra = 'primary';
                    break;
                case 5:
                    $estado = "Usado";
                    $color = "#6f42c1";
                    $color_barra = 'purple';
                    break;
                default:
                    $estado = "Extornado";
                    $color = "#6c757d";
                    $color_barra = 'secondary';
                    break;
            }

            $porcentaje = number_format($row["pagadas"] / $row["total"] * 100, 2);

            $sub_array[] = $row["ogciud_id"];
            $sub_array[] = $row["ciudadano_dni"];
            $sub_array[] = $row["nombre_completo"];
            $fecha_formateada = date("Y-m-d H:i:s", strtotime($row["fechacrea"]));
            $sub_array[] = $fecha_formateada;

            // Estado como badge nativo Tabler
            $estado_html = '<span class="badge bg-' . $color_barra . ' text-' . $color_barra . '-fg">' . $estado . '</span>';
            $sub_array[] = $estado_html;

            // Barra de progreso Tabler
            $progress_bar_html = '<div class="d-flex align-items-center justify-content-center gap-2"><div class="progress progress-sm flex-fill" style="min-width: 60px;"><div class="progress-bar bg-' . $color_barra . '" style="width:' . $porcentaje . '%" role="progressbar" aria-valuenow="' . $porcentaje . '" aria-valuemin="0" aria-valuemax="100"></div></div><span class="text-secondary small fw-bold">' . $porcentaje . '%</span></div>';
            $sub_array[] = $progress_bar_html;

            // Botón Ver Tasas con Tabler SVG
            $sub_array[] = '<button type="button" onClick="ver(' . $row["procedciudadano_id"] . ');" id="' . $row["procedciudadano_id"] . '" class="btn btn-outline-success btn-icon" data-estado="' . $row["est"] . '" title="Ver tasas y liquidación"><svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4" /><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" /><path d="M9 17h6" /><path d="M9 13h6" /></svg></button>';

            $trash_svg = '<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-trash" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg>';

            $og_param = !empty($row["ogciud_id"]) ? "'" . addslashes($row["ogciud_id"]) . "'" : "null";
            if ($row["est"] == 1 || $row["est"] == 2 || $row["est"] == 4 || $est == 9) {
                $sub_array[] = '<button type="button" onClick="eliminar(' . (int)$row["procedciudadano_id"] . ', ' . (int)$est . ', ' . $og_param . ');" id="' . $row["procedciudadano_id"] . '" class="btn btn-outline-danger btn-icon" title="Eliminar trámite">' . $trash_svg . '</button>';
            } else {
                $sub_array[] = '<button type="button" disabled onClick="eliminar(' . (int)$row["procedciudadano_id"] . ', ' . (int)$est . ', ' . $og_param . ');" id="' . $row["procedciudadano_id"] . '" class="btn btn-outline-danger btn-icon" title="Eliminar trámite">' . $trash_svg . '</button>';
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

    case "listaConsulta":
        $datos = $proced->get_proced_ciudadano_x_idciudadano($_POST["ciudadano_id"]);
        $data = array();
        foreach ($datos as $row) {
            $sub_array = array();
            $sub_array[] = $row["proceciudadano_id"];
            $sub_array[] = $row["procedciudadano_cod"];
            $sub_array[] = $row["proced_nombre"];
            $sub_array[] = $row["fechacrea"];
            $sub_array[] = $row["est"];
            $sub_array[] = $row["pagadas"];
            $sub_array[] = $row["total"];
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
