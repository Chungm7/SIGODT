<?php
require_once("../config/conexion.php");
require_once("../models/Ciudadano_mnt.php");

$ciud = new Ciudadano();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
switch ($_GET["op"]) {

    // Poblar <select> con ciudadanos activos
    case "listar":
        $datos = $ciud->listarCiudadanos();
        echo json_encode(["success" => true, "data" => $datos]);
        break;
    // En controller/ciudadano.php, dentro del switch($_GET['op'])
    case "listar_tipos":
        $tipos = $ciud->get_tito_doc();
        echo json_encode([
            "success" => true,
            "data"    => $tipos
        ]);
        break;

    // DataTables server-side
    case "listar_tabla":
        $search       = $_POST["search"]       ?? "";
        $start        = intval($_POST["start"]  ?? 0);
        $length       = intval($_POST["length"] ?? 10);
        $order_column = $_POST["order_column"] ?? "0";
        $order_dir    = in_array(strtolower($_POST["order_dir"] ?? ""), ["asc", "desc"])
            ? $_POST["order_dir"] : "desc";

        // Obtiene datos y total
        $datos = $ciud->list_ciudadano($search, $start, $length, $order_column, $order_dir);
        $total = $ciud->get_total_ciudadano($search);

        $data = [];
        foreach ($datos as $r) {
            // Badge de estado
            if ($r["ciud_estado"] === "A") {
                $estadoClass = "green";
                $estadoTexto = "Activo";
                $iconCambio  = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" stroke="orange" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon dropdown-item-icon"><path stroke="none" d="M0 0h24v24H0z"/><path d="M18 6L6 18M6 6l12 12"/></svg> Inactivar';
            } else {
                $estadoClass = "red";
                $estadoTexto = "Inactivo";
                $iconCambio  = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" stroke="green" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon dropdown-item-icon"><path stroke="none" d="M0 0h24v24H0z"/><path d="M5 12l5 5L20 7"/></svg> Activar';
            }

            // Dropdown de acciones
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

    // Crear nuevo ciudadano
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

        // 1) validar duplicado
        if ($ciud->existeDocumento($d["ciud_numero_documento"], $d["tido_id"])) {
            echo json_encode([
                "success" => false,
                "message" => "Ya existe un ciudadano con ese número y tipo de documento."
            ]);
            break;
        }

        // 2) intentar insertar
        if ($ciud->insertar($d)) {
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
    // En controller/ciudadano_mnt.php (o ciudadano.php)
    case "mostrar":
        $id  = $_POST["ciud_id"];
        $r   = $ciud->mostrar($id);
        // Devolvemos directamente el registro como JSON
        echo json_encode($r);
        break;


    // Editar ciudadano
    case "editar":
        $fotoInput = $_POST["ciud_foto"] ?? null;
        $d = [
            "ciud_numero_documento"    => $_POST["ciud_numero_documento"],
            "ciud_primer_apellido"     => $_POST["ciud_primer_apellido"],
            "ciud_segundo_apellido"    => $_POST["ciud_segundo_apellido"],
            "ciud_nombre"              => $_POST["ciud_nombre"],
            "ciud_foto"             => $fotoInput !== '' ? $fotoInput : null,
            "ciud_fecha_nac"           => $_POST["ciud_fecha_nac"],
            "ciud_sexo"                => $_POST["ciud_sexo"],
            "tido_id"                  => $_POST["tido_id"]
        ];
        echo json_encode([
            "success" => $ciud->editar($_POST["ciud_id"], $d)
        ]);
        break;

    // Inactivar (marcar estado 'E')
    case "cambiar_estado":
        echo json_encode([
            "success" => $ciud->inactivar($_POST["ciud_id"])
        ]);
        break;
}
