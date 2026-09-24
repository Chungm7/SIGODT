<?php
require_once("../config/conexion.php");
require_once("../models/Dashboard.php");
$dashboard = new Dashboard();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
switch ($_GET["op"]) {
    // Total recaudado
    case "total_recaudado":
        $datos = $dashboard->get_total_recaudado(
            $_POST["fecha_ini"] ?? null,
            $_POST["fecha_fin"] ?? null,
            $_POST["estados"] ?? null
        );
        echo json_encode($datos[0]);
        break;

    case "recaudacion_area_estado":
        $datos = $dashboard->get_recaudacion_por_area_estado(
            $_POST["fecha_ini"] ?? null,
            $_POST["fecha_fin"] ?? null,
            $_POST["estados"] ?? null
        );
        echo json_encode($datos);
        break;

    case "recaudacion_usuario":
        $datos = $dashboard->get_recaudacion_por_usuario(
            $_POST["fecha_ini"] ?? null,
            $_POST["fecha_fin"] ?? null,
            $_POST["estados"] ?? null
        );
        echo json_encode($datos);
        break;
    // En dashboard.php, añade en el switch:
    case "pendiente_por_cobrar":
        $datos = $dashboard->get_pendiente_por_cobrar(
            $_POST["fecha_ini"] ?? null,
            $_POST["fecha_fin"] ?? null,
            $_POST["estados"] ?? null
        );
        echo json_encode($datos[0]);
        break;

    case "ordenes_por_estado":
        $datos = $dashboard->get_ordenes_por_estado(
            $_POST["fecha_ini"] ?? null,
            $_POST["fecha_fin"] ?? null,
            $_POST["estados"] ?? null
        );
        echo json_encode($datos);
        break;

    case "procedimientos_por_estado":
        $datos = $dashboard->get_procedimientos_por_estado(
            $_POST["fecha_ini"] ?? null,
            $_POST["fecha_fin"] ?? null,
            $_POST["estados"] ?? null
        );
        echo json_encode($datos);
        break;

    case "procedimientos_iniciados":
        // 1. Leer los estados de ordenes que manda el front (estados[])
        $orderEstados = isset($_POST['estados'])
            ? (array) $_POST['estados']
            : [];

        // 2. Si está “all”, quitamos el filtro
        if (in_array('all', $orderEstados, true)) {
            $procEstados = null;
        } else {
            // 3. Map de estados de órdenes → estados de procedimientos
            $map = [
                0 => [0],   // Anulado → Anulado
                1 => [2],   // Pendiente (órdenes) se agrupa como “Girado” (proc abierto)
                2 => [2],   // Girado → Girado
                3 => [3],   // Improcedente → Improcedente
                4 => [4],   // Pagado → Pagado
                5 => [5],   // Usado → Completado
                6 => [6]    // Extornado → Extornado
            ];
            $procEstados = [];
            foreach ($orderEstados as $st) {
                $code = intval($st);
                if (isset($map[$code])) {
                    $procEstados = array_merge($procEstados, $map[$code]);
                }
            }
            // 4. Únicos, por si se repiten
            $procEstados = array_unique($procEstados);
        }

        // 5. Llamar al modelo con los estados ya “traducidos”
        $datos = $dashboard->get_procedimientos_iniciados(
            $_POST["fecha_ini"]  ?? null,
            $_POST["fecha_fin"]  ?? null,
            $procEstados         // null o array de códigos de proc
        );
        echo json_encode($datos[0]);
        break;


    case "evolucion_mensual":
        $datos = $dashboard->get_evolucion_mensual(
            $_POST["fecha_ini"] ?? null,
            $_POST["fecha_fin"] ?? null,
            $_POST["estados"] ?? null
        );
        echo json_encode($datos);
        break;
    case "errores_por_usuario_dependencia":
        $datos = $dashboard->get_errores_por_usuario_dependencia(
            $_POST["fecha_ini"] ?? null,
            $_POST["fecha_fin"] ?? null
        );
        echo json_encode($datos);
        break;


    case "ordenes_por_dependencia_estado":
        $datos = $dashboard->get_ordenes_por_dependencia_estado(
            $_POST["fecha_ini"] ?? null,
            $_POST["fecha_fin"] ?? null,
            $_POST["estados"] ?? null
        );
        echo json_encode($datos);
        break;

    case "resumen_dependencia":
        $datos = $dashboard->get_resumen_dependencia(
            $_POST["depe_id"],
            $_POST["fecha_ini"] ?? null,
            $_POST["fecha_fin"] ?? null,
            $_POST["estados"] ?? null
        );
        echo json_encode($datos);
        break;
}
