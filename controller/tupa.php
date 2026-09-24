<?php

require_once("../config/conexion.php");

require_once("../models/Tupa.php");
require_once("../models/Bitacora.php");
$bitacora = new Bitacora();
/* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
$tupa = new tupa();

switch ($_GET["op"]) {

    case "guardaryeditar":
        if (empty($_POST["tupa_id"])) {
            $tupa->insert_tupa($_POST["tupa_nom"], $_POST["tupa_año"], $_POST["tupa_tipo"]);
            $bitacora->update_bitacora($_SESSION["usua_id_SIGODT"]);
        } else {
            $tupa->update_tupa($_POST["tupa_id"], $_POST["tupa_nom"], $_POST["tupa_año"], $_POST["tupa_tipo"]);
            $bitacora->update_bitacora($_SESSION["usua_id_SIGODT"]);
        }
        break;

    case "mostrar":
        $datos = $tupa->get_tupa_id($_POST["tupa_id"]);
        if (is_array($datos) == true and count($datos) <> 0) {
            foreach ($datos as $row) {
                $output["tupa_id"] = $row["tupa_id"];
                $output["tupa_nom"] = $row["tupa_nom"];
                $output["tupa_año"] = $row["tupa_año"];
                $output["tipo_doc"] = $row["tipo_doc"];
            }
            echo json_encode($output);
        }
        break;

    case "eliminar":
        $tupa->delete_tupa($_POST["tupa_id"]);
        $bitacora->update_bitacora($_SESSION["usua_id_SIGODT"]);
        break;
    case "bloquear":
        $tupa->bloquear_tupa($_POST["tupa_id"]);
        $bitacora->update_bitacora($_SESSION["usua_id_SIGODT"]);
        break;
    case "duplicar":
        $cantant = $bitacora->get_max_id()[0]["bita_id"];

        $tupa->duplicar_tupa($_POST["tupa_id"]);
        $bitacora->update_bitacora_grupo($_SESSION["usua_id_SIGODT"], $cantant);
        break;
    case "desbloquear_tupa":
        $tupa->desbloquear_tupa($_POST["tupa_id"]);
        $bitacora->update_bitacora($_SESSION["usua_id_SIGODT"]);
        break;
    case "activar_tupa":
        $tupa->activar_tupa($_POST["tupa_id"]);
        $bitacora->update_bitacora($_SESSION["usua_id_SIGODT"]);
        break;
    case "desactivar_tupa":
        $tupa->desactivar_tupa($_POST["tupa_id"]);
        $bitacora->update_bitacora($_SESSION["usua_id_SIGODT"]);
        break;
    case "listar":
        $datos = $tupa->get_docs();
        $data = array();
        $ico_trash = '<svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-trash"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg>';
        $ico_check = '<svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-checks"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 12l5 5l10 -10" /><path d="M2 12l5 5m5 -5l5 -5" /></svg>';
        $ico_cancel = '<svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-cancel"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M18.364 5.636l-12.728 12.728" /></svg>';
        $ico_edit = '<svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-edit"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1" /><path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z" /><path d="M16 5l3 3" /></svg>';
        $ico_clone = '<svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-copy-check"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path stroke="none" d="M0 0h24v24H0z" /><path d="M7 9.667a2.667 2.667 0 0 1 2.667 -2.667h8.666a2.667 2.667 0 0 1 2.667 2.667v8.666a2.667 2.667 0 0 1 -2.667 2.667h-8.666a2.667 2.667 0 0 1 -2.667 -2.667z" /><path d="M4.012 16.737a2 2 0 0 1 -1.012 -1.737v-10c0 -1.1 .9 -2 2 -2h10c.75 0 1.158 .385 1.5 1" /><path d="M11 14l2 2l4 -4" /></svg>';
        $ico_lock = '<svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-lock"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 13a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v6a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2v-6z" /><path d="M11 16a1 1 0 1 0 2 0a1 1 0 0 0 -2 0" /><path d="M8 11v-4a4 4 0 1 1 8 0v4" /></svg>';
        $ico_unlock = '<svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-lock-open-2"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 13a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v6a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2z" /><path d="M9 16a1 1 0 1 0 2 0a1 1 0 0 0 -2 0" /><path d="M13 11v-4a4 4 0 1 1 8 0v4" /></svg>';
        foreach ($datos as $row) {
            $sub_array = array();
            $estado = $row["est"];
            // Estado de activo
            $activo_span = ($estado == 2) ? '<span class="badge bg-success text-white">Activo</span>' : '<span class="badge bg-danger text-white">Inactivo</span>';

            // Estado de bloqueado
            $bloqueado_span = ($block == '0') ? '<span class="badge bg-warning text-white">Desbloqueado</span>' : '<span class="badge bg-secondary text-white">Bloqueado</span>';

            $block = $row["tupa_block"];
            $acciones = '<div class="d-flex gap-1 justify-content-center">';

            if ($block == '0') {
                $acciones .= '<a href="#" class="accion-icon text-secondary" onClick="blockear(' . $row["tupa_id"] . ');" title="Bloquear">' . $ico_lock . '</a>';
            } else {
                $acciones .= '<a href="#" class="accion-icon text-warning" onClick="desbloquear_tupa(' . $row["tupa_id"] . ');" title="Desbloquear">' . $ico_unlock . '</a>';
            }

            $acciones .= '<a href="#" class="accion-icon text-primary" onClick="editar(' . $row["tupa_id"] . ');" title="Editar">' . $ico_edit . '</a>';

            if ($estado == 2) {
                $acciones .= '<a href="#" class="accion-icon text-danger" onClick="desactivar(' . $row["tupa_id"] . ');" title="Desactivar">' . $ico_cancel . '</a>';
            } else {
                $acciones .= '<a href="#" class="accion-icon text-success" onClick="activar(' . $row["tupa_id"] . ');" title="Activar">' . $ico_check . '</a>';
            }

            if ($estado != 2 || $block != '1') {
                $acciones .= '<a href="#" class="accion-icon text-danger" onClick="eliminar(' . $row["tupa_id"] . ');" title="Eliminar">' . $ico_trash . '</a>';
            } else {
                $acciones .= '<a href="#" class="accion-icon text-muted" style="pointer-events:none;opacity:0.5;" title="Eliminar">' . $ico_trash . '</a>';
            }

            $acciones .= '<a href="#" class="accion-icon text-info" onClick="duplicar(' . $row["tupa_id"] . ');" title="Duplicar">' . $ico_clone . '</a>';
            $acciones .= '</div>';



            // Añadir los datos a la fila
            $sub_array[] = $row["tupa_nom"];
            $sub_array[] = $row["tupa_año"];
            $sub_array[] = $row["tipo_doc"];
            $sub_array[] = $activo_span;  // Insertar el estado de activo
            $sub_array[] = $bloqueado_span;  // Insertar el estado de bloqueado
            $sub_array[] = $acciones;
            // Insertar el dropdown de acciones aquí

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
        $datos = $tupa->get_tupa();
        if (is_array($datos) == true and count($datos) > 0) {
            $html = " <option label='Seleccione'></option>";
            foreach ($datos as $row) {
                $html .= "<option value='" . $row['tupa_id'] . "' data-estado='" . $row['tupa_block'] . "'>" . $row['tupa_nom'] . "</option>";
            }
            echo $html;
        }
        break;
      case "combo_ti":
        $datos = $tupa->get_tupa_tusnet();
        if (is_array($datos) == true and count($datos) > 0) {
            $html = " <option label='Seleccione'></option>";
            foreach ($datos as $row) {
                $html .= "<option value='" . $row['tupa_id'] . "' data-estado='" . $row['tupa_block'] . "'>" . $row['tupa_nom'] . "</option>";
            }
            echo $html;
        }
        break;
   // Caso en el controlador PHP para obtener el TUPA y TUSNE vigentes
case "gettupa_vigente":
    try {
        $datos = $tupa->get_tupa(); // Llamamos al método para obtener los TUPA y TUSNE

        $output = array();

        // Verificamos si hay datos válidos para el TUPA y TUSNE
        foreach ($datos as $row) {
            if ($row['est'] == 2) { // Solo si el estado es 2 (vigente)
                if ($row['tipo_doc'] == 'TUPA') {
                    // Si es TUPA
                    $output['tupa_id'] = $row['tupa_id'];
                    $output['tupa_nom'] = $row['tupa_nom'];
                } elseif ($row['tipo_doc'] == 'TUSNE') {
                    // Si es TUSNE
                    $output['tusne_id'] = $row['tupa_id'];  // Usamos tupa_id para tusne_id
                    $output['tusne_nom'] = $row['tupa_nom']; // Usamos tupa_nom para tusne_nom
                }
            }
        }

        // Enviar la respuesta con los datos obtenidos
        echo json_encode($output);

    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    break;

    case "combo_usu":
        $datos = $tupa->get_tupa_usu();
        if (is_array($datos) == true and count($datos) > 0) {
            $html = " <option label='Seleccione'></option>";
            foreach ($datos as $row) {
                $html .= "<option value='" . $row['tupa_id'] . "' data-estado='" . $row['est'] . "'>" . $row['tupa_nom'] . "</option>";
            }
            echo $html;
        }
        break;
}
