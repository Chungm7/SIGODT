<?php
require_once("../config/conexion.php");
require_once("../models/Empresa_mnt.php");

$emp = new Empresa();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
switch ($_GET["op"]) {

    // Para poblar un <select> con empresas activas
    case "listar":
        $datos = $emp->listarEmpresas();
        echo json_encode([
            "success" => true,
            "data"    => $datos
        ]);
        break;

    // Para DataTables (server-side)
    case "listar_tabla":
        $draw         = intval($_POST["draw"] ?? 0);
        $search       = $_POST["search"]       ?? "";
        $start        = intval($_POST["start"]  ?? 0);
        $length       = intval($_POST["length"] ?? 10);
        $order_column = $_POST["order_column"] ?? 0;
        $order_dir    = $_POST["order_dir"]    ?? "desc";
        $estado    = $_POST["estado"]    ?? "A";

        $list  = $emp->list_empresa($search, $start, $length, $order_column, $order_dir, $estado);
        $total = $emp->get_total_empresa($search);

        $data = [];
        foreach ($list as $r) {
            // Badge de estado
            if ($r["empr_estado"] === "A") {
                $estadoHtml = "<span class='badge bg-green text-green-fg'>Activo</span>";
                $botonEstado = "Inactivar";
            } else {
                $estadoHtml = "<span class='badge bg-red text-red-fg'>Inactivo</span>";
                $botonEstado = "Activar";
            }

            // ↓ Antes tenías algo así:
            // $acciones = "<div class='dropdown'>…</div>";

            // ↑ Sustitúyelo por esto:
            $iconEditar = '<svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-edit"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1" /><path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z" /><path d="M16 5l3 3" /></svg>';
            $iconInact = '<svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-trash"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg>';
            $iconAct   = '<svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-recycle-off"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 17l-2 2l2 2m-2 -2h9m1.896 -2.071a2 2 0 0 0 -.146 -.679l-.55 -1" /><path d="M8.536 11l-.732 -2.732l-2.732 .732m2.732 -.732l-4.5 7.794a2 2 0 0 0 1.506 2.89l1.141 .024" /><path d="M15.464 11l2.732 .732l.732 -2.732m-.732 2.732l-4.5 -7.794a2 2 0 0 0 -3.256 -.14l-.591 .976" /><path d="M3 3l18 18" /></svg>';
            $iconCambio = $r["empr_estado"] === "A"
                ? $iconInact . ' Inactivar'
                : $iconAct   . ' Activar';

            $acciones = '
                <div class="dropdown">
               <a href="#" class="btn dropdown-toggle" data-bs-toggle="dropdown">
                         <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-nut"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M19 6.84a2.007 2.007 0 0 1 1 1.754v6.555c0 .728 -.394 1.4 -1.03 1.753l-6 3.844a1.995 1.995 0 0 1 -1.94 0l-6 -3.844a2.006 2.006 0 0 1 -1.03 -1.752v-6.557c0 -.728 .394 -1.399 1.03 -1.753l6 -3.582a2.049 2.049 0 0 1 2 0l6 3.582h-.03z" /><path d="M12 12m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" /></svg>
                    </a>
                <div class="dropdown-menu">
                    <a class="dropdown-item" href="#" onclick="editar(' . $r['empr_id'] . ', \'' . addslashes($r['empr_razon_social']) . '\')">
                    ' . $iconEditar . ' Editar
                    </a>
                    <a class="dropdown-item" href="#" onclick="cambiarEstado(' . $r['empr_id'] . ', \'' . $r['empr_estado'] . '\')">
                    ' . $iconCambio . '
                    </a>
                </div>
                </div>';


            $data[] = [
                $r["empr_id"],
                $r["empr_ruc"],
                $r["empr_razon_social"],
                $r["empr_nombre_comercial"],
                $r["empr_direccion"],
                $estadoHtml,
                $acciones
            ];
        }

        echo json_encode([
            "draw"            => $draw,
            "recordsTotal"    => $total,
            "recordsFiltered" => $total,
            "data"            => $data
        ]);
        break;

    // Crear nueva empresa
// En controller/empresa_mnt.php, elimina empr_categoria del case "crear":
    case "crear":
        $d = [
            "empr_ruc"              => $_POST["empr_ruc"],
            "empr_razon_social"     => $_POST["empr_razon_social"],
            "empr_nombre_comercial" => $_POST["empr_nombre_comercial"],
            "empr_direccion"        => $_POST["empr_direccion"]
        ];
    
        // Validar RUC duplicado
        if ($emp->existeRuc($d["empr_ruc"])) {
            echo json_encode([
                "success" => false,
                "message" => "Ya existe una empresa con ese RUC."
            ]);
            break;
        }
    
        // Intentar insertar
        if ($emp->insertar($d)) {
            echo json_encode([
                "success" => true,
                "message" => "Empresa registrada correctamente."
            ]);
        } else {
            echo json_encode([
                "success" => false,
                "message" => "Error al registrar la empresa."
            ]);
        }
        break;
    
    // Editar empresa existente
    case "editar":
        $d = [

            "empr_ruc"                 => $_POST["empr_ruc"],
            "empr_razon_social"        => $_POST["empr_razon_social"],
            "empr_nombre_comercial"    => $_POST["empr_nombre_comercial"],
            "empr_direccion"           => $_POST["empr_direccion"]
        ];

        if ($emp->editar($_POST["empr_id"], $d)) {
            echo json_encode([
                "success" => true,
                "message" => "Empresa actualizada correctamente."
            ]);
        } else {
            echo json_encode([
                "success" => false,
                "message" => "Error al actualizar la empresa."
            ]);
        }
        break;

    // Cambiar estado a 'E' (inactivar)
    case "cambiar_estado":
        if ($emp->inactivar($_POST["empr_id"])) {
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false]);
        }
        break;

    // Mostrar datos de una empresa
    case "mostrar":
        $id = $_POST["empr_id"];
        $row = $emp->mostrar($id);
        echo json_encode($row);
        break;
}
