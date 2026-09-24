<?php
require_once("../config/conexion.php");
require_once("../models/OrdenGiro_rep.php");

$ordenGiro = new OrdenGiro();

switch ($_GET["op"]) {
  case "get_ordenes_giro":
    $documento = $_POST["documento"];
    $data = $ordenGiro->get_ordenes_giro($documento);
    echo json_encode($data);
    break;


  case "get_ordenes_mes":
    $search = isset($_POST["search"]) ? trim($_POST["search"]) : "";
    $page = isset($_POST["page"]) ? intval($_POST["page"]) : 1;
    $limit = isset($_POST["limit"]) ? intval($_POST["limit"]) : 10;
    $offset = ($page - 1) * $limit;

    $data = $ordenGiro->get_ordenes_mes($search, $limit, $offset);
    $total = $ordenGiro->get_total_ordenes_mes($search); // Obtener total de registros

    echo json_encode([
      "data" => $data,
      "total" => $total,
      "page" => $page,
      "limit" => $limit
    ]);
    break;
  case "get_orden_giro":
    $ogciud_id = isset($_POST["ogciud_id"]) ? $_POST["ogciud_id"] : null;
    $data = $ordenGiro->get_orden_giro_by_id($ogciud_id);

    if ($data) {
      echo json_encode(["status" => "success", "data" => $data]);
    } else {
      echo json_encode(["status" => "error", "message" => "No se encontró la Orden de Giro"]);
    }
    break;



  default:
    echo json_encode(["status" => "error", "message" => "Operación no válida"]);
    break;
}
?>