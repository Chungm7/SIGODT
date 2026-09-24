<?php

require_once("../config/conexion.php");

require_once("../models/Tasa.php");
require_once("../models/Empresa.php");
require_once("../models/Procedimiento.php");
require_once("../models/Bitacora.php");
$bitacora = new Bitacora();

$tasa = new Tasa();
$empresa = new Empresa();
$procedimiento = new Procedimiento();

switch ($_GET["op"]) {

    case "guardaryeditar":
        if (empty($_POST["tasa_id"])) {
            $tasa->insert_tasa($_POST["tasa_nom"], $_POST["tasa_tipo"]);
            $bitacora->update_bitacora($_SESSION["usua_id_SIGODT"]);
        } else {
            $tasa->update_tasa($_POST["tasa_id"], $_POST["tasa_nom"], $_POST["tasa_tipo"]);
            $bitacora->update_bitacora($_SESSION["usua_id_SIGODT"]);
        }
        break;

    case "mostrar":
        $datos = $tasa->get_tasa_id($_POST["tasa_id"]);
        if (is_array($datos) == true and count($datos) <> 0) {
            foreach ($datos as $row) {
                $output["tasa_id"] = $row["tasa_id"];
                $output["tasa_nom"] = $row["tasa_nom"];
                $output["tasa_tipo"] = $row["multiplica"];
            }
            echo json_encode($output);
        }
        break;

    case "eliminar":
        $tasa->delete_tasa($_POST["tasa_id"]);
        $bitacora->update_bitacora($_SESSION["usua_id_SIGODT"]);
        break;
    case "Pagar_Order_Giro":
        if (!isset($_SESSION["usua_id_SIGODT"]) || empty($_SESSION["usua_id_SIGODT"])) {
            echo json_encode(["error" => true, "message" => "No se pudo generar la Orden de Giro porque su sesión ha expirado. Por favor, inicie sesión nuevamente."]);
            exit;
        }

        $cantant = $bitacora->get_max_id()[0]["bita_id"];
        $resultado = $tasa->Pagar_Order_Giro($_POST["tasatciudadano_id"], $_SESSION["usua_id_SIGODT"], $_POST["comentario"]);
        $bitacora->update_bitacora_grupo($_SESSION["usua_id_SIGODT"], $cantant);

        echo json_encode(["error" => false, "message" => "Orden de Giro Exitoso."]);
        break;

    case "Pagar_Order_Giro_trabajador":
        $cantant = $bitacora->get_max_id()[0]["bita_id"];

        // Realizar el pago de la orden de giro
        $orden = $tasa->Pagar_Order_Giro_trabajador($_POST["tasatciudadano_id"], $_SESSION["usua_id_SIGODT"], $_POST["comentario"]);

        // Obtener el número de recibo del trabajador
        $nmrrecibo = $tasa->get_nmr_recibo_trabajador();

        // Actualizar el número de recibo en cada orden de giro pagada
        foreach ($orden as $row) {
            foreach ($nmrrecibo as $row2) {
                $tasa->update_nmr_recibo_trabajador($row2['valor_recibo'], $row['ogciud_id']);
            }
        }

        $bitacora->update_bitacora_grupo($_SESSION["usua_id_SIGODT"], $cantant);
        break;
    case "Pagar_Order_Giro_empresa":
        $tasa->Pagar_Order_Giro_Empresa($_POST["tasatempr_id"], $_SESSION["usua_id_SIGODT"], 1, $_POST["comentario"]);
        $bitacora->update_bitacora($_SESSION["usua_id_SIGODT"]);
        break;
    case "Pagar_Ordern_Giro_grupo":
        $datos = explode(',', $_POST['tasatciud_id']);
        $data = array();
        echo json_encode($data);
        // Crear la orden de giro
        $tasa->crearOrdenini($_SESSION["usua_id_SIGODT"], $_POST["comentario"]);
        $bitacora->update_bitacora($_SESSION["usua_id_SIGODT"]);
        // Obtener el ID de la última orden de giro creada
        $OrdenID = $tasa->obtenerultimovalor();
        $orden = null;
        foreach ($OrdenID as $row) {
            $orden = $row["ogciud_id"];
        }
        // Cantidad fija para las tasas
        $p_cantidad = 1;

        // Iterar sobre las tasas y agregarlas a la orden de giro
        foreach ($datos as $row) {
            $sub_array = array();
            $idx = $tasa->insertarTasaGiros($row, $orden, $p_cantidad);

            if ($idx) {
                $bitacora->update_bitacora($_SESSION["usua_id_SIGODT"]);
                $sub_array[] = $idx;
                $data[] = $sub_array;
            }
        }
        break;
    case "Pagar_Orden_Giro_grupo_empresa":
        $tasatempresa_ids = explode(',', $_POST['tasatempresa_id']);
        $multiplica_values = $_POST['multiplica'];
        $multiempo_values = $_POST['multiempo'];
        $year_text = $_POST['year_text'];
        $data = array();
        $tasa->crearOrdenini_empr($_SESSION["usua_id_SIGODT"], $_POST["comentario"]);
        $bitacora->update_bitacora($_SESSION["usua_id_SIGODT"]);
        // Obtener el ID de la última orden de giro creada
        $OrdenID = $tasa->obtenerultimovalor_empr();
        $orden = null;
        foreach ($OrdenID as $row2) {
            $orden = $row2["ogempr_id"];
        }

        // Obtener el ID del procedimiento de la empresa asociado a la primera tasa
        $prcedemprs = $tasa->obtenerid_proceempresa($tasatempresa_ids[0]);

        foreach ($prcedemprs as $col) {
            $prcedempr = $col["procedempr_id"];
        }

        if ($_POST["tipo"] == 'V') {
            $cantidads = $empresa->get_cantidad_grupo($prcedempr);
        } else {
            $cantidads = $empresa->get_cantidad_grupo_ciud($prcedempr);
        }

        $p_cantidad = 0;
        foreach ($cantidads as $row1) {
            $p_cantidad = $row1["count"];
        }
        $year_values = explode(',', $year_text);

        // Inicializar un nuevo arreglo asociativo
        $years_associative = [];

        // Recorrer el arreglo de años
        foreach ($year_values as $year) {
            // Limpiar los caracteres no deseados como comillas y llaves
            $clean_year = str_replace(['"', '{', '}'], '', $year);
            // Separar la placa y la cantidad
            $parts = explode(':', $clean_year);
            // Añadir la placa como clave y la cantidad como valor al arreglo asociativo
            $years_associative[$parts[0]] = $parts[1];
        }

        // Convertir el arreglo asociativo a formato JSON
        $year_text = json_encode($years_associative);
        echo $year_text;

        // Iterar sobre las tasas y agregarlas a la orden de giro
        foreach ($tasatempresa_ids as $key => $tasatempresa_id) {
            $sub_array = array();
            $multiplica = $multiplica_values[$key]; // Obtener el valor de multiplica correspondiente
            // Definir la cantidad según el valor de multiplica
            $cantidad = ($multiplica == 0) ? 1 : $p_cantidad;

            // Insertar la tasa en la base de datos con los años asociados
            $idx = $tasa->insertarTasaGiros_empresa($tasatempresa_id, $orden, $cantidad, $year_text);
            if ($idx) {
                $bitacora->update_bitacora($_SESSION["usua_id_SIGODT"]);
                $sub_array[] = $idx;
                $data[] = $sub_array;
            }
        }
        echo json_encode($data);
        break;

    case "listar":
        $datos = $tasa->get_tasa();
        $data = array();
        foreach ($datos as $row) {
            $sub_array = array();
            $sub_array[] = $row["tasa_nom"];
            if ($row["multiplica"] == 1) {
                $sub_array[] = "Multiplica";
            } else {
                $sub_array[] = "Simple";
            }
            $sub_array[] = '<button type="button" onClick="editar(' . $row["tasa_id"] . ');"  id="' . $row["tasa_id"] . '" class="btn btn-outline-warning btn-icon"><div><i class="fa fa-edit"></i></div></button>';
            $sub_array[] = '<button type="button" onClick="eliminar(' . $row["tasa_id"] . ');"  id="' . $row["tasa_id"] . '" class="btn btn-outline-danger btn-icon"><svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-trash"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg></button>';
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
    case "listar_proceds_tasa":
        $datos = $tasa->get_proced_tasa_x_id($_POST["proced_id"], $_POST["tupa_id"], $_POST["area_id"]);
        $data = array();
        foreach ($datos as $row) {
            $sub_array = array();
            $sub_array[] = $row["tasa_nom"];
            $sub_array[] = empty($row["nombre_tasa"]) ? '<span style="color: red;">null</span>' : $row["nombre_tasa"];
            $sub_array[] = empty($row["tasaproced_pos"]) ? '<span style="color: red;">null</span>' : $row["tasaproced_pos"];
            $sub_array[] = empty($row["tasaproced_monto"]) ? '<span style="color: red;">null</span>' : $row["tasaproced_monto"];
            $sub_array[] = empty($row["cod_ref"]) ? '<span style="color: red;">null</span>' : str_pad($row["cod_ref"], 5, '0', STR_PAD_LEFT);
            $tupa_estado = $row["tupa_block"];

            $editButton = '<button type="button" onClick="editar(' . $row["tasaproced_id"] . ');"  id="' . $row["tasaproced_id"] . '" class="btn btn-outline-primary btn-icon"';
            $deleteButton = '<button type="button" onClick="eliminar(' . $row["tasaproced_id"] . ');"  id="' . $row["tasaproced_id"] . '" class="btn btn-outline-danger btn-icon"';

            if ($tupa_estado != 0) {
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
    case "listar_proceds_tasa_x_procedciudadano":
        $datos = $tasa->listar_proceds_tasa_x_procedciudadano($_POST["proceciudadano_id"]);
        $data = array();

        foreach ($datos as $row) {
            $sub_array = array();

            // Determinar el estado y el color
            $estado = '';
            $color = '';
            switch ($row["est"]) {
                case 0:
                    $estado = "Anulado";
                    $color = "#dc3545"; // Rojo
                    break;
                case 1:
                    $estado = "Pendiente";
                    $color = "#ffc107"; // Amarillo
                    break;
                case 2:
                    $estado = "Girado";
                    $color = "#28a745"; // Verde
                    break;
                case 3:
                    $estado = "Improcedente";
                    $color = "#dc3545"; // Rojo
                    break;
                case 4:
                    $estado = "Pagado";
                    $color = "#007bff"; // Azul
                    break;
                case 5:
                    $estado = "Completado";
                    $color = "#6f42c1"; // Morado
                    break;
                default:
                    $estado = "Extornado";
                    $color = "#000000"; // Negro
                    break;
            }

            // Verificar si el estado es "Improcedente" o "Anulado" y desactivar el checkbox en consecuencia
            $disabledCheckbox = ($row["est"] == 3 || $row["est"] == 0) ? "disabled" : "";

            // Marcar el checkbox si el estado no es "Improcedente" o "Anulado"
            $checkedCheckbox = ($row["est"] != 3 && $row["est"] != 0) ? "checked" : "";

            $sub_array[] = "<input type='checkbox' class='form-check-input' name='detallecheck[]' value='" . $row["tasatciud_id"] . "' data-estado='" . $row["est"] . "' $disabledCheckbox $checkedCheckbox>";

            $sub_array[] = $row["tasa_nom"];
            $sub_array[] = 'S/  ' . $row["tasaproced_monto"];
            $estado_html = '<div style="background-color: ' . $color . '; display: flex; align-items: center; justify-content: center; height: 5px; text-align: center; color: white; border-radius: 12px; box-shadow: 0px 8px 15px rgba(0, 0, 0, 0.1); padding: 15px 10px;">' . $estado . ' </div>';
            $sub_array[] = $estado_html;

            // Configuración del botón "Pagar" o "Cancelar Pago"
            $icon_class = ($row["est"] == 2) ? "fa fa-times" : "fa fa-money"; // Cambiar el icono según el estado
            $pago_button = '';

            if ($row["est"] == 1) {
                $pago_button = '<button type="button" onClick="pagar(' . $row["tasatciud_id"] . ',' . $_POST["proceciudadano_id"] . ');"  id="' . $row["tasatciud_id"] . '" class="btn btn-outline-primary btn-icon"><div><i class="' . $icon_class . '"></i></div></button>';
            } elseif ($row["est"] == 2) {
                $pago_button = '<button type="button" onClick="cancelarPago(' . $row["tasatciud_id"] . ',' . $_POST["proceciudadano_id"] . ');"  id="' . $row["tasatciud_id"] . '" class="btn btn-outline-primary btn-icon"><div><i class="' . $icon_class . '"></i></div></button>';
            }

            // Deshabilitar el botón si el estado es "Improcedente" o "Anulado"
            $disabled_attr = ($row["est"] == 3 || $row["est"] == 0) ? 'disabled' : '';
            $sub_array[] = $pago_button;

            // Botón "Imprimir" con un icono de impresora
            $imprimir_button = '';
            // Solo mostrar el botón de imprimir si el estado es "Girado"
            if ($row["est"] == 2) {
                $imprimir_button = '<button type="button" onClick="imprimir(' . $row["tasatciud_id"] . ');"  id="' . $row["tasatciud_id"] . '" class="btn btn-outline-danger btn-icon"><div><i class="fa fa-print"></i></div></button>';
            }

            $sub_array[] = $imprimir_button;
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


    case "listar_proceds_tasa_x_procedempresa":
        $datos = $tasa->listar_proceds_tasa_x_procedempresa($_POST["proceempresa_id"]);
        $data = array();

        $tcampo = "";
        $pvalor = "";
        $pestado = "";

        $count = count($datos);
        for ($i = 0; $i < $count; $i++) {
            $row = $datos[$i];
            $sub_array = array();

            if ($tcampo === "") {
                $tcampo = $row["proced_tipocampo"];
            }

            $estado = ($row["est"] == 1) ? "Pendiente" : (($row["est"] == 2) ? "Girado" : "Improcedente");
            $color = ($row["est"] == 1) ? "#ffc107" : (($row["est"] == 2) ? "#28a745" : "#dc3545");

            $checkbox = "";

            if ($tcampo === 2) {
                if ($i == 0) {
                    // For the first row
                    if ($row["est"] == 2) {
                        // If the first occurrence is "Pagado," mark all subsequent "Pendiente" checkboxes as checked
                        $checkbox = "<input type='checkbox' class='form-check-input' name='detallecheck[]' value='" . $row["tasatempr_id"] . "' data-estado='" . $row["est"] . "' data-multiplica='" . $row["multiplica"] . "' data-multiempo='" . $row["multiempo"] . "' disabled>";
                    } else {
                        // For other states or when handling "Pendiente" after "Pagado," show a checkbox accordingly
                        $checked = ($row["est"] == 1) ? "checked" : "";
                        $checkbox = "<input type='checkbox' class='form-check-input' name='detallecheck[]' value='" . $row["tasatempr_id"] . "' data-estado='" . $row["est"] . "' data-multiplica='" . $row["multiplica"] . "' data-multiempo='" . $row["multiempo"] . "' disabled $checked>";
                    }
                } else {
                    // For other rows
                    $checkbox = ($datos[0]["est"] == 2) ? "<input type='checkbox' class='form-check-input' name='detallecheck[]' value='" . $row["tasatempr_id"] . "' data-estado='" . $row["est"] . "' data-multiplica='" . $row["multiplica"] . "' data-multiempo='" . $row["multiempo"] . "' disabled checked>" : "<input type='checkbox' class='form-check-input' name='detallecheck[]' value='" . $row["tasatempr_id"] . "' data-estado='" . $row["est"] . "' data-multiplica='" . $row["multiplica"] . "' disabled>";
                }
            } else {
                // Verificar si el estado es "Improcedente" y desactivar el checkbox en consecuencia
                $disabledCheckbox = ($row["est"] == 3) ? "disabled" : "";

                // Marcar el checkbox si el estado es "Pendiente"
                $checkedCheckbox = ($row["est"] != 3) ? "checked" : "";

                $checkbox = "<input type='checkbox' class='form-check-input' name='detallecheck[]' value='" . $row["tasatempr_id"] . "' data-estado='" . $row["est"] . "' data-multiplica='" . $row["multiplica"] . "' data-multiempo='" . $row["multiempo"] . "' $disabledCheckbox $checkedCheckbox>";
            }
            $multiplica = ($row["multiplica"] == 1) ? true : false;
            $sub_array[] = $checkbox;
            $sub_array[] = $row["tasaproced_pos"];
            $texto_tasa = $row["tasa_nom"];
            $tipo = $procedimiento->get_tipoProc($_POST["proced_id"]);
            foreach ($tipo as $t) {
                $tp = $t["proced_tipoindvasc"];
            }
            if ($tp == "V") {
                $cantidad_grupo = $empresa->get_cantidad_grupo($_POST["proceempresa_id"]);
            } else {
                $cantidad_grupo = $empresa->get_cantidad_grupo_ciud($_POST["proceempresa_id"]);
            }


            if ($multiplica) {
                foreach ($cantidad_grupo as $row2) {
                    $cantidad = $row2["count"];
                }
                $texto_tasa .= " X " . $cantidad;
            }
            $sub_array[] = $texto_tasa;
            $monto = $row["tasaproced_monto"];
            if ($multiplica) {
                $monto *= $cantidad;
            }
            $sub_array[] = $monto;
            $estado_html = '<div style="background-color: ' . $color . '; display: flex; align-items: center; justify-content: center; height: 5px; text-align: center; color: white; border-radius: 12px; box-shadow: 0px 8px 15px rgba(0, 0, 0, 0.1); padding: 15px 10px;">' . $estado . ' </div>';
            $sub_array[] = $estado_html;

            // Configuración del botón "Pagar" o "Cancelar Pago"
            $icon_class = ($row["est"] == 2) ? "fa fa-times" : "fa fa-money"; // Cambiar el icono según el estado
            $pago_button = '';

            if ($row["est"] == 1) {
                if ($tcampo === 2) {
                    if ($i == 0) {
                        $pago_button = '<button type="button" onClick="pagar(' . $row["tasatempr_id"] . ',' . $_POST["proceempresa_id"] . ');"  id="' . $row["tasatempr_id"] . '" class="btn btn-outline-primary btn-icon"><div><i class="' . $icon_class . '"></i></div></button>';
                    } else {
                        $pago_button = '';
                    }
                } else {
                    $pago_button = '<button type="button" onClick="pagar(' . $row["tasatempr_id"] . ',' . $_POST["proceempresa_id"] . ');"  id="' . $row["tasatempr_id"] . '" class="btn btn-outline-primary btn-icon"><div><i class="' . $icon_class . '"></i></div></button>';
                }
            } elseif ($row["est"] == 2) {
                if ($tcampo === 2) {
                    if ($i == 0) {
                        $pago_button = '<button type="button" onClick="imprimir(' . $row["tasatempr_id"] . ');"  id="' . $row["tasatempr_id"] . '" class="btn btn-outline-danger btn-icon"><div><i class="fa fa-print"></i></div></button>';
                    } else {
                        $pago_button = '';
                    }
                } else {
                    $pago_button = '<button type="button" onClick="imprimir(' . $row["tasatempr_id"] . ');"  id="' . $row["tasatempr_id"] . '" class="btn btn-outline-danger btn-icon"><div><i class="fa fa-print"></i></div></button>';
                }
            }

            // Deshabilitar el botón si el estado es "Improcedente"
            $disabled_attr = ($row["est"] == 3) ? 'disabled' : '';
            $sub_array[] = $pago_button;
            $data[] = $sub_array;
            $pestado = $row["est"];
        }
        $results = array(
            "sEcho" => 1,
            "iTotalRecords" => count($data),
            "iTotalDisplayRecords" => count($data),
            "aaData" => $data
        );
        echo json_encode($results);
        break;
    case "listar_proceds_tasa_x_procedciudadano_usu":
        $datos = $tasa->listar_proceds_tasa_x_procedciudadano($_POST["proceciudadano_id"]);
        $data = array();
        $tcampo = "";
        $pvalor = "";
        $pestado = "";

        $count = count($datos);
        for ($i = 0; $i < $count; $i++) {
            $row = $datos[$i];
            $sub_array = array();
            $multiplica = ($row["multiplica"] == 1) ? true : false;
            if ($tcampo === "") {
                $tcampo = $row["proced_tipocampo"];
            }

            $estado = '';
            $color = '';

            switch ($row["est"]) {
                case 0:
                    $estado = "Anulado";
                    $color = "red"; // Rojo
                    break;
                case 1:
                    $estado = "Pendiente";
                    $color = "yellow"; // Amarillo
                    break;
                case 2:
                    $estado = "Girado";
                    $color = "green"; // Verde
                    break;
                case 3:
                    $estado = "Improcedente";
                    $color = "red"; // Rojo
                    break;
                case 4:
                    $estado = "Pagado";
                    $color = "blue"; // Azul
                    break;
                case 5:
                    $estado = "Completado";
                    $color = "purple"; // Morado
                    break;
                default:
                    $estado = "Extornado";
                    $color = "black"; // Negro
                    break;
            }

            $checkbox = "";

            if ($tcampo === 2) {
                if ($i == 0 && $tcampo === 2) {
                    // For the first row
                    if ($row["est"] == 2) {
                        // If the first occurrence is "Pagado," mark all subsequent "Pendiente" checkboxes as checked
                        $checkbox = "<input type='checkbox' class='form-check-input' name='detallecheck[]' value='" . $row["tasatciud_id"] . "' data-estado='" . $row["est"] . "' disabled>";
                    } else {
                        // For other states or when handling "Pendiente" after "Pagado," show a checkbox accordingly
                        $checked = "";
                        $checkbox = "<input type='checkbox' class='form-check-input' name='detallecheck[]' value='" . $row["tasatciud_id"] . "' data-estado='" . $row["est"] . "' disabled $checked>";
                    }
                } else {
                    // For other rows
                    $checkbox = ($datos[0]["est"] !== 3) ? "<input type='checkbox' class='form-check-input' name='detallecheck[]' value='" . $row["tasatciud_id"] . "' data-estado='" . $row["est"] . "' disabled checked>" : "<input type='checkbox' class='form-check-input' name='detallecheck[]' value='" . $row["tasatciud_id"] . "' data-estado='" . $row["est"] . "' disabled>";
                }
            } else {
                if ($tcampo === 3) {
                    // Verificar si el estado es "Improcedente" y desactivar el checkbox en consecuencia
                    $disabledCheckbox = "disabled";
                    $disabledCheckbox = "disabled";
                } else {
                    // Verificar si el estado es "Improcedente" y desactivar el checkbox en consecuencia
                    $disabledCheckbox = ($row["est"] == 3) ? "disabled" : "";
                    $disabledCheckbox = ($row["est"] == 0) ? "disabled" : "";
                }


                // Marcar el checkbox si el estado es "Pendiente"
                $checkedCheckbox = ($row["est"] !== 3) ? "checked" : "";
                $checkedCheckbox = ($row["est"] !== 0) ? "checked" : "";

                $checkbox = "<input type='checkbox' class='form-check-input' name='detallecheck[]' value='" . $row["tasatciud_id"] . "' data-estado='" . $row["est"] . "' $disabledCheckbox $checkedCheckbox>";
            }

            $sub_array[] = $checkbox;
            $sub_array[] = $row["tasaproced_pos"];
            $sub_array[] = $row["tasa_nom"];
            $sub_array[] = "S/ " . $row["tasaproced_monto"];
            $estado_html = "<span class='badge bg-{$color}-lt'>{$estado}</span>";
            $sub_array[] = $estado_html;

            // Configuración del botón "Pagar" o "Cancelar Pago"
            $icon_class = ($row["est"] == 2) ? '<svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-cancel"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M18.364 5.636l-12.728 12.728" /></svg>' :
                '<svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-credit-card-pay"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 19h-6a3 3 0 0 1 -3 -3v-8a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v4.5" /><path d="M3 10h18" /><path d="M16 19h6" /><path d="M19 16l3 3l-3 3" /><path d="M7.005 15h.005" /><path d="M11 15h2" /></svg>';
            $pago_button = '';
            if ($row["est"] == 1) {
                if ($tcampo === 2) {
                    if ($i == 0) {
                        $pago_button = '<button type="button" onClick="pagar(' . $row["tasatciud_id"] . ',' . $_POST["proceciudadano_id"] . ');"  id="' . $row["tasatciud_id"] . '" class="btn btn-outline-primary btn-icon">' . $icon_class . '</button>';
                    } else {
                        $pago_button = '';
                    }
                } else if ($tcampo === 3) {
                    $pago_button = '';
                } else {
                    $pago_button = '<button type="button" onClick="pagar(' . $row["tasatciud_id"] . ',' . $_POST["proceciudadano_id"] . ',' . $_POST["est"] . ');"  id="' . $row["tasatciud_id"] . '" class="btn btn-outline-primary btn-icon">' . $icon_class . '</button>';
                }
            } else if ($row["est"] !== 3 || $row["est"] !== 0 || $row["est"] !== 6) {
                if ($tcampo === 2) {
                    if ($i == 0) {
                        $pago_button = '<button type="button" onClick="imprimir(' . $row["tasatciud_id"] . ');"  id="' . $row["tasatciud_id"] . '" class="btn btn-outline-danger btn-icon"><svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-printer"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M17 17h2a2 2 0 0 0 2 -2v-4a2 2 0 0 0 -2 -2h-14a2 2 0 0 0 -2 2v4a2 2 0 0 0 2 2h2" /><path d="M17 9v-4a2 2 0 0 0 -2 -2h-6a2 2 0 0 0 -2 2v4" /><path d="M7 13m0 2a2 2 0 0 1 2 -2h6a2 2 0 0 1 2 2v4a2 2 0 0 1 -2 2h-6a2 2 0 0 1 -2 -2z" /></svg>  </button>';
                    } else {
                        $pago_button = '';
                    }
                } else {
                    $pago_button = '';
                }
            }

            // Deshabilitar el botón si el estado es "Improcedente"
            $disabled_attr = ($row["est"] == 3) ? 'disabled' : '';
            $sub_array[] = $pago_button;
            $data[] = $sub_array;
            $pestado = $row["est"];
        }
        $results = array(
            "sEcho" => 1,
            "iTotalRecords" => count($data),
            "iTotalDisplayRecords" => count($data),
            "aaData" => $data
        );
        echo json_encode($results);
        break;
    case "listar_proceds_tasa_x_procedciudadano_ciudadano":
        $datos = $tasa->listar_proceds_tasa_x_procedciudadano($_POST["procedciudadano_id"]);
        $data = array();

        $count = count($datos);
        for ($i = 0; $i < $count; $i++) {
            $row = $datos[$i];
            $sub_array = array();

            $sub_array[] = $row["est"];
            $sub_array[] = $row["tasaproced_pos"];
            $sub_array[] = $row["tasa_nom"];
            $sub_array[] = $row["tasaproced_monto"];
            $data[] = $sub_array;
            $pestado = $row["est"];
        }
        $results = array(
            "sEcho" => 1,
            "iTotalRecords" => count($data),
            "iTotalDisplayRecords" => count($data),
            "aaData" => $data
        );
        echo json_encode($results);
        break;
    case "datosProgress":
        $datos = $tasa->listar_proceds_tasa_x_procedciudadano($_POST["proceciudadano_id"]);
        $data = array();

        foreach ($datos as $row) {
            $sub_array = array();
            $sub_array[] = $row["tasaproced_pos"];
            $sub_array[] = $row["tasa_nom"];
            $sub_array[] = $row["tasaproced_monto"];
            $sub_array[] = $row["est"];
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
    case "listar_detalle_tasa":
        $datos = $tasa->get_tasa_modal($_POST["proced_id"]);
        $data = array();
        foreach ($datos as $row) {
            $sub_array = array();
            $sub_array[] = "<input type='checkbox' class='form-check-input' name='detallecheck[]' value='" . $row["tasa_id"] . "'>";
            $sub_array[] = $row["tasa_nom"];
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
    case "update_tasaproced":
        $tamite->update_tasaproced($_POST["tasaproced_id"], $_POST["tasaproced_pos"], $_POST["tasaproced_monto"]);
        $bitacora->update_bitacora($_SESSION["usua_id_SIGODT"]);
}
