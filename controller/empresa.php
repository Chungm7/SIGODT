<?php
require_once(__DIR__ . "/../base/Response.php");
require_once(__DIR__ . "/../config/conexion.php");
require_once(__DIR__ . "/../models/Empresa.php");
require_once(__DIR__ . "/../models/Bitacora.php");

class TokenHelper {
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

$usuario = '20250001';
$contrasena = '20250001@';

$bitacora = new Bitacora();
$empresa = new Empresa();

switch ($_GET["op"]) {
    case "buscarempresaxRUC":
        $datos = $empresa->get_empresa_x_RUC($_POST["empr_ruc"]);
        if (!empty($datos)) {
            $output = [];
            foreach ($datos as $row) {
                $output = [
                    "empr_id" => $row["empr_id"],
                    "empr_categoria" => $row["empr_categoria"],
                    "empr_ruc" => $row["empr_ruc"],
                    "empr_razon_social" => $row["empr_razon_social"],
                    "empr_nombre_comercial" => $row["empr_nombre_comercial"],
                    "empr_tipo_actividad" => $row["empr_tipo_actividad"],
                    "empr_direccion" => $row["empr_direccion"],
                    "empr_telefono" => $row["empr_telefono"],
                    "empr_estado" => $row["empr_estado"]
                ];
            }
            echo json_encode($output);
        }
        break;

    case "consultar_sunat_v2":
        $token = TokenHelper::encrypt($contrasena);
        $url = 'https://www.munichiclayo.gob.pe/Pide/Sunat/' . $_POST["empr_ruc"];
        $data = [
            'usu_dni' => $usuario,
            'usu_contrasena' => $token
        ];
        $curl = curl_init();
        // Configurar la solicitud cURL
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        $resp = curl_exec($curl);

        if (curl_errno($curl)) {
            echo json_encode(['error' => 'cURL: ' . curl_error($curl)]);
            curl_close($curl);
            break;
        }
        curl_close($curl);

        $sun = json_decode($resp, true);
        if (empty($sun['success']) || empty($sun['data']['ruc'])) {
            echo json_encode(['error' => 'RUC inválido o sin datos SUNAT']);
            break;
        }
        $output = [
            "raz_social" => $sun["data"]["raz_social"],
            "nom_comercial" => $sun["data"]["nom_comercial"],
            "domicilio" => $sun["data"]["domicilio"]
        ];
        echo json_encode($output);
        break;

    case "consultar_RUC":
        $datos = $empresa->get_empresa_x_RUC($_POST["empr_ruc"]);
        if (!empty($datos)) {
            $output = [];
            foreach ($datos as $row) {
                $categoria = $row["empr_categoria"];
                $output = [
                    "empr_id" => $row["empr_id"],
                    "empr_categoria" => match ($categoria) {
                        'L' => 'Libre',
                        'E' => 'Empresa',
                        'M' => 'Mercado',
                        default => 'Desconocido'
                    },
                    "empr_ruc" => $row["empr_ruc"],
                    "empr_razon_social" => $row["empr_razon_social"],
                    "empr_nombre_comercial" => $row["empr_nombre_comercial"],
                    "empr_tipo_actividad" => $row["empr_tipo_actividad"],
                    "empr_direccion" => $row["empr_direccion"],
                    "empr_telefono" => $row["empr_telefono"],
                    "empr_estado" => $row["empr_estado"]
                ];
            }
            echo json_encode($output);
        } else {
            $error_message = "No se ha encontrado información para el RUC proporcionado.";
            echo json_encode(["error" => $error_message]);
        }
        break;

    /*
	case 'consultar_sunat':
        $ruc = $_POST['empr_ruc'] ?? '';
        if (empty($ruc)) {
            echo json_encode(['error' => 'RUC no proporcionado']);
            break;
        }
        
        $datos = $empresa->get_empresa_x_RUC($ruc);

        $dato = $empresa->getDirecciones($ruc);

        if (!empty($dato)) {
            $html = "<option label='Seleccione'></option>";

            foreach ($dato as $row) {
                $html .= '<option value="'.$row['empr_id'].'">'.$row['empr_direccion'].'</option>';
            }

            echo json_encode([
                'source'   => 'bd',
                'html'     => $html, // 👈 aquí incorporas tu HTML
                'empresas' => array_map(function ($row) {
                    return [
                        'empr_id'               => $row['empr_id'],
                        'empr_razon_social'     => $row['empr_razon_social'],
                        'empr_nombre_comercial' => $row['empr_nombre_comercial'],
                        'direcciones'           => array_map(
                            fn($d) => trim($d, '"'),
                            explode(',', trim($row['direcciones'], '{}'))
                        )
                    ];
                }, $datos)
            ]);
            break;
        }

        $url = 'https://www.munichiclayo.gob.pe/Pide/Sunat/' . $ruc;
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
        ]);
        $resp = curl_exec($curl);

        if (curl_errno($curl)) {
            echo json_encode(['error' => 'cURL: ' . curl_error($curl)]);
            curl_close($curl);
            break;
        }
        curl_close($curl);

        $sun = json_decode($resp, true);
        if (empty($sun['success']) || empty($sun['data']['ruc'])) {
            echo json_encode(['error' => 'RUC inválido o sin datos SUNAT']);
            break;
        }

        $d = $sun['data'];

        $giroNombre = trim($d['ciiu'] ?? '');
        $giroId = null;

        if ($giroNombre !== '') {
            $giroId = $empresa->getIdGiro($giroNombre);
            if (empty($giroId)) {
                $empresa->registrarGiro($giroNombre);
                $giroId = $empresa->getIdGiro($giroNombre);
            }
        }

        $gico_id = '{' . $giroId . '}';


        $estado = ($d['estado_ruc'] === 'ACTIVO') ? 'A' : 'I';
        $nomCom = ($d['nom_comercial'] === '-') ? '' : $d['nom_comercial'];

        $emprId = $empresa->registrarEmpresa(
            'E',
            $d['ruc'],
            $d['raz_social'],
            $nomCom,
            $d['domicilio'],
            '000000000',
            '0',
            '',
            $estado,
            $gico_id
        );

        $bitacora->update_bitacora($_SESSION['usua_id_SIGODT']);

        echo json_encode([
            'source'   => 'sunat',
            'empresas' => [[
                'empr_id'               => $emprId,
                'empr_razon_social'     => $d['raz_social'],
                'empr_nombre_comercial' => $nomCom,
                'empr_direccion'        => $d['domicilio']
            ]]
        ]);
        break;
	*/
	case 'consultar_sunat':
        $ruc = $_POST['empr_ruc'] ?? '';
        if (empty($ruc)) {
            echo json_encode(['error' => 'RUC no proporcionado']);
            break;
        }

        // $token = TokenHelper::encrypt($contrasena);
        // $url = 'https://www.munichiclayo.gob.pe/Pide/Sunat/' . $_POST["empr_ruc"];
        // $data = [
        //     'usu_dni' => $usuario,
        //     'usu_contrasena' => $token
        // ];
        // $curl = curl_init();
        // // Configurar la solicitud cURL
        // curl_setopt($curl, CURLOPT_URL, $url);
        // curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        // curl_setopt($curl, CURLOPT_POST, true);
        // curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        // curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        // $resp = curl_exec($curl);

        // if (curl_errno($curl)) {
        //     echo json_encode(['error' => 'cURL: ' . curl_error($curl)]);
        //     curl_close($curl);
        //     break;
        // }
        // curl_close($curl);

        // $sun = json_decode($resp, true);
        // if (empty($sun['success']) || empty($sun['data']['ruc'])) {
        //     echo json_encode(['error' => 'RUC inválido o sin datos SUNAT']);
        //     break;
        // }

        // $d = $sun['data'];

        // $estado = ($d['estado_ruc'] === 'ACTIVO') ? 'A' : 'I';
        // $nomCom = ($d['nom_comercial'] === '-') ? '' : $d['nom_comercial'];


        // $bitacora->update_bitacora($_SESSION['usua_id_SIGODT']);

        // echo json_encode([
        //     'source'   => 'sunat',
        //     'empresas' => [[
        //         'empr_razon_social'     => $d['raz_social'],
        //         'empr_ruc'        => $d['ruc'],
        //         'empr_estado_sunat' => $d['estado_ruc'],
        //         'empr_condicion_sunat' => $d['condicion']
        //     ]]
        // ]);


        $datos = $empresa->get_empresa_x_RUC($ruc);

        if (!empty($datos)) {
            echo json_encode([
                'source'   => 'sunat',
                'empresas' => [[
                    'empr_razon_social'     => $datos[0]['empr_razon_social'],
                    'empr_ruc'        => $ruc,
                    'empr_estado_sunat' => "ACTIVO",
                    'empr_condicion_sunat' => "HABIDO"
                ]]
            ]);
        }

        break;
    case "combo_empresa":
        $datos = $empresa->get_direcciones($_POST['empr_ruc']);
        if (!empty($datos)) {
            $html = "<option label='Seleccione'></option>";
            foreach ($datos as $row) {
                $html .= "<option value='" . $row['empr_id'] . "'>" . $row['empr_direccion'] . "</option>";
            }
            echo $html;
        }
        break;

    case "listar":
        $datos = $empresa->listarEmpresas();
        echo json_encode([
            "success" => true,
            "data"    => $datos
        ]);
        break;

    case "listar_tabla":
        $draw         = intval($_POST["draw"] ?? 0);
        $search       = $_POST["search"]       ?? "";
        $start        = intval($_POST["start"]  ?? 0);
        $length       = intval($_POST["length"] ?? 10);
        $order_column = $_POST["order_column"] ?? 0;
        $order_dir    = $_POST["order_dir"]    ?? "desc";
        $estado       = $_POST["estado"]       ?? "A";

        $list  = $empresa->list_empresa($search, $start, $length, $order_column, $order_dir, $estado);
        $total = $empresa->get_total_empresa($search);

        $data = [];
        foreach ($list as $r) {
            if ($r["empr_estado"] === "A") {
                $estadoHtml = "<span class='badge bg-green text-green-fg'>Activo</span>";
                $botonEstado = "Inactivar";
            } else {
                $estadoHtml = "<span class='badge bg-red text-red-fg'>Inactivo</span>";
                $botonEstado = "Activar";
            }

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

    case "crear":
        $d = [
            "empr_ruc"              => $_POST["empr_ruc"],
            "empr_razon_social"     => $_POST["empr_razon_social"],
            "empr_nombre_comercial" => $_POST["empr_nombre_comercial"],
            "empr_direccion"        => $_POST["empr_direccion"]
        ];

        if ($empresa->existeRuc($d["empr_ruc"])) {
            echo json_encode([
                "success" => false,
                "message" => "Ya existe una empresa con ese RUC."
            ]);
            break;
        }

        if ($empresa->insertar($d)) {
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

    case "editar":
        $d = [
            "empr_ruc"                 => $_POST["empr_ruc"],
            "empr_razon_social"        => $_POST["empr_razon_social"],
            "empr_nombre_comercial"    => $_POST["empr_nombre_comercial"],
            "empr_direccion"           => $_POST["empr_direccion"]
        ];

        if ($empresa->editar($_POST["empr_id"], $d)) {
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

    case "cambiar_estado":
        if ($empresa->inactivar($_POST["empr_id"])) {
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false]);
        }
        break;

    case "mostrar":
        $id = $_POST["empr_id"];
        $row = $empresa->mostrar($id);
        echo json_encode($row);
        break;
}
?>
