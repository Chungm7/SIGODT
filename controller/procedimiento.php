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
        header('Content-Type: application/json');
        try {
            if (empty($_POST["proced_id"])) {
                $created = $proced->insert_proced($_POST["tupa_id"] ?? null, $_POST["area_id"], $_POST["proced_cod"], $_POST["proced_nom"], $_POST["tipo_campo"], $_POST["tipo_administrado"], $_POST["proced_tipoindvasc"]);
                if (!$created) {
                    echo json_encode(['success' => false, 'message' => 'Solo se pueden agregar procedimientos a un documento válido En Proceso o Vigente.']);
                    break;
                }
            } else {
                $proced->update_proced($_POST["proced_id"], $_POST["proced_cod"], $_POST["proced_nom"], $_POST["tipo_campo"], $_POST["tipo_administrado"], $_POST["proced_tipoindvasc"]);
            }
            $bitacora->update_bitacora($_SESSION["usua_id_SIGODT"]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'No se pudo guardar el procedimiento.']);
        }
        break;
    case "abrirproced":
        header('Content-Type: application/json');
        $person = $_SESSION['usua_id_SIGODT'] ?? null;
        $role = $_SESSION['rol_id_SIGODT'] ?? null;
        $area = $_POST['area_id'] ?? null;
        $procedure = $_POST['proced_id'] ?? null;
        $valid = true;
        foreach ([$person, $role, $area, $procedure, $_POST['ciud_id'] ?? null] as $id) {
            if (!Procedimiento::registration_id($id)) $valid = false;
        }
        if (!$valid || !$proced->get_registration_options($person, $role, $area, $procedure)) {
            echo json_encode([
                "success" => false,
                "message" => "Seleccione un ciudadano y un procedimiento vigente de un área autorizada con una sesión válida."
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
            $razon_social,
            $role,
            $area
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

    case "registration_areas":
    case "getAreas_usu":
        $datos = $proced->get_registration_options($_SESSION['usua_id_SIGODT'] ?? null, $_SESSION['rol_id_SIGODT'] ?? null);
        $html = "<option value=''>Seleccione</option>";
        $seen = [];
        foreach ($datos as $row) {
            if (isset($seen[$row['depe_id']])) continue;
            $seen[$row['depe_id']] = true;
            $html .= "<option value='" . (int)$row['depe_id'] . "'>" . htmlspecialchars($row['depe_denominacion'], ENT_QUOTES, 'UTF-8') . "</option>";
        }
        echo $html;
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

    case "registration_combo":
    case "combo_tupa_tusne":
        $area = $_POST['area_id'] ?? null;
        $document = $_GET['op'] === 'registration_combo' ? ($_POST['tupa_id'] ?? null) : null;
        $datos = [];
        if (Procedimiento::registration_id($area) && ($_GET['op'] !== 'registration_combo' || Procedimiento::registration_id($document))) {
            $datos = $proced->get_registration_options($_SESSION['usua_id_SIGODT'] ?? null, $_SESSION['rol_id_SIGODT'] ?? null, $area, null, $document);
        }
        $html = "<option value=''>Seleccione</option>";
        foreach ($datos as $row) {
            $html .= "<option value='" . (int)$row['proced_id'] . "' data-tipo='" . htmlspecialchars($row['proced_tipoindvasc'], ENT_QUOTES, 'UTF-8') . "'>" . htmlspecialchars($row['proced_cod'] . '-' . $row['proced_nom'], ENT_QUOTES, 'UTF-8') . "</option>";
        }
        echo $html;
        break;
    case "registration_detail":
    case "registration_show":
        header('Content-Type: application/json');
        $area = $_POST['area_id'] ?? null;
        $procedure = $_POST['proced_id'] ?? null;
        $context = ['person' => $_SESSION['usua_id_SIGODT'] ?? null, 'role' => $_SESSION['rol_id_SIGODT'] ?? null, 'area' => $area];
        $valid = Procedimiento::registration_id($area) && Procedimiento::registration_id($procedure);
        if ($_GET['op'] === 'registration_show') {
            echo json_encode($valid ? $proced->get_registration_options($context['person'], $context['role'], $area, $procedure) : []);
        } else {
            echo json_encode($valid ? $proced->get_procedimiento_detalle($procedure, $context) : ['procedimiento' => false, 'tasas' => []]);
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
        $datos = array_filter(array_map('trim', explode(',', $_POST['tasa_id'] ?? '')));
        $data = array();
        foreach ($datos as $row) {
            $sub_array = array();
            $idx = $proced->insert_proced_tasa($_POST["proced_id"], $row);
            $sub_array[] = $idx;
            $data[] = $sub_array;
            $bitacora->update_bitacora($_SESSION["usua_id_SIGODT"]);
        }
        $proced->update_cod_ref();

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

            // Estado como badge nativo Tabler
            $estado_html = '<span class="badge bg-' . $color_barra . ' text-' . $color_barra . '-fg">' . $estado . '</span>';
            $sub_array[] = $estado_html;

            // Barra de progreso Tabler
            $progress_bar_html = '<div class="d-flex align-items-center justify-content-center gap-2"><div class="progress progress-sm flex-fill" style="min-width: 60px;"><div class="progress-bar bg-' . $color_barra . '" style="width:' . $porcentaje . '%" role="progressbar" aria-valuenow="' . $porcentaje . '" aria-valuemin="0" aria-valuemax="100"></div></div><span class="text-secondary small fw-bold">' . $porcentaje . '%</span></div>';
            $sub_array[] = $progress_bar_html;

            $check_svg = '<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-check" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10" /></svg>';
            $ban_svg = '<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-ban" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M5.7 5.7l12.6 12.6" /></svg>';
            $procedencia_button = ($row["est"] == 3) ? '<button type="button" onClick="setProcedencia(' . (int)$row["proceciudadano_id"] . ');" id="' . (int)$row["proceciudadano_id"] . '" class="btn btn-outline-success btn-icon" title="Habilitar trámite">' . $check_svg . '</button>' : '<button type="button" onClick="setProcedencia(' . (int)$row["proceciudadano_id"] . ');" id="' . (int)$row["proceciudadano_id"] . '" class="btn btn-outline-danger btn-icon" title="Marcar improcedente">' . $ban_svg . '</button>';
            $sub_array[] = $procedencia_button;

            $edit_svg = '<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-pencil" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 20h4l10.5 -10.5a2.828 2.828 0 1 0 -4 -4l-10.5 10.5v4" /><path d="M13.5 6.5l4 4" /></svg>';
            $sub_array[] = '<button type="button" onClick="editar(' . (int)$row["proceciudadano_id"] . ');" id="' . (int)$row["proceciudadano_id"] . '" class="btn btn-outline-warning btn-icon" title="Editar trámite">' . $edit_svg . '</button>';

            $trash_svg = '<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-trash" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg>';
            $sub_array[] = '<button type="button" onClick="eliminar(' . (int)$row["proceciudadano_id"] . ');" id="' . (int)$row["proceciudadano_id"] . '" class="btn btn-outline-danger btn-icon" title="Eliminar trámite">' . $trash_svg . '</button>';

            $file_svg = '<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-file-invoice" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4" /><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" /><path d="M9 12h6" /><path d="M9 16h6" /></svg>';
            $sub_array[] = '<button type="button" onClick="ver(' . (int)$row["proceciudadano_id"] . ');" id="' . (int)$row["proceciudadano_id"] . '" class="btn btn-outline-success btn-icon" data-estado="' . $row["est"] . '" title="Ver tasas y liquidación">' . $file_svg . '</button>';

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
        header('Content-Type: application/json; charset=utf-8');
        $draw = filter_var($_POST['draw'] ?? $_POST['sEcho'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 1000000000]]);
        $draw = $draw === false ? 0 : $draw;
        $empty = ['recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []];
        $emit = function ($draw, $page, $rows, $error = null) {
            $response = ['draw' => (int)$draw, 'recordsTotal' => $page['recordsTotal'], 'recordsFiltered' => $page['recordsFiltered'], 'data' => $rows,
                'sEcho' => (int)$draw, 'iTotalRecords' => $page['recordsTotal'], 'iTotalDisplayRecords' => $page['recordsFiltered'], 'aaData' => $rows];
            if ($error !== null) $response['error'] = $error;
            echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        };
        if (!Procedimiento::registration_id($_SESSION['usua_id_SIGODT'] ?? null) || !Procedimiento::registration_id($_SESSION['rol_id_SIGODT'] ?? null)) {
            http_response_code(401);
            $emit($draw, $empty, [], 'Sesión no válida. Inicie sesión nuevamente.');
            break;
        }
        try {
            $filters = Procedimiento::operational_filters($_POST);
            $procedure = $_POST['proced_id'] ?? null;
            $area = $_POST['area_id'] ?? null;
            if (in_array($area, ['', '0', 0], true)) $area = null;
            if ($area !== null && !Procedimiento::registration_id($area)) throw new InvalidArgumentException('Área inválida.');
            if (in_array($procedure, [null, '', '0', 0], true)) {
                $emit($filters['draw'], $empty, []);
                break;
            }
            if (!Procedimiento::registration_id($procedure)) throw new InvalidArgumentException('Procedimiento inválido.');
            $page = $proced->get_operational_list($procedure, $_SESSION['usua_id_SIGODT'], $_SESSION['rol_id_SIGODT'], $area, $filters);
            $data = [];
            $escape = function ($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); };
            $labels = [0 => ['Anulado', 'danger'], 1 => ['Girado', 'success'], 3 => ['Improcedente', 'danger'],
                4 => ['Pagado', 'primary'], 5 => ['Usado', 'purple'], 6 => ['Extornado', 'secondary'], 9 => ['Pendiente', 'warning']];
            foreach ($page['data'] as $row) {
                $state = $row['filter_state'] === null ? null : (int)$row['filter_state'];
                $unknown = 'Desconocido (' . ($row['est'] === null ? '—' : $row['est']) . ')';
                [$label, $color] = $state !== null && isset($labels[$state]) ? $labels[$state] : [$unknown, 'secondary'];
                $total = (int)$row['total'];
                $percentage = number_format($total > 0 ? min(100, max(0, (int)$row['pagadas'] / $total * 100)) : 0, 2, '.', '');
                $id = (int)$row['procedciudadano_id'];
                $identifier = $row['ogciud_id'] ?? ($row['procedciudadano_cod'] ?: 'Trámite ' . $id);
                $sub_array = [$escape($identifier), $escape($row['ciudadano_dni']), $escape($row['nombre_completo']), $escape($row['fechacrea'])];
                $sub_array[] = '<span class="badge bg-' . $color . ' text-' . $color . '-fg">' . $escape($label) . '</span>';
                $sub_array[] = '<div class="d-flex align-items-center justify-content-center gap-2"><div class="progress progress-sm flex-fill" style="min-width:60px"><div class="progress-bar bg-' . $color . '" style="width:' . $percentage . '%" role="progressbar" aria-valuenow="' . $percentage . '" aria-valuemin="0" aria-valuemax="100"></div></div><span class="text-secondary small fw-bold">' . $percentage . '%</span></div>';
                $view_icon = '<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1-2-2v-14a2 2 0 0 1 2-2h7l5 5v11a2 2 0 0 1-2 2z"/><path d="M9 17h6M9 13h6"/></svg>';
                $sub_array[] = '<button type="button" onclick="ver(' . $id . ')" class="btn btn-outline-success btn-icon" data-estado="' . $escape($state) . '" title="Ver tasas y liquidación">' . $view_icon . '</button>';
                $order_argument = $row['ogciud_id'] === null ? 'null' : $escape(json_encode((string)$row['ogciud_id'], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP));
                $disabled = in_array($state, [1,4,9], true) ? '' : ' disabled';
                $trash_icon = '<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M10 11v6M14 11v6M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2l1-12M9 7v-3a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v3"/></svg>';
                $sub_array[] = '<button type="button" onclick="eliminar(' . $id . ', ' . (int)$state . ', ' . $order_argument . ')" class="btn btn-outline-danger btn-icon" title="Eliminar trámite"' . $disabled . '>' . $trash_icon . '</button>';
                $data[] = $sub_array;
            }
            $emit($filters['draw'], $page, $data);
        } catch (InvalidArgumentException $error) {
            http_response_code(400);
            $emit($draw, $empty, [], $error->getMessage());
        } catch (Throwable $error) {
            http_response_code(500);
            $emit($draw, $empty, [], 'No se pudo cargar el listado. Intente nuevamente.');
        }
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
