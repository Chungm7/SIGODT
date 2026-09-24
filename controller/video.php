<?php
require_once("../config/conexion.php");
require_once("../models/Video.php");

$video = new Video();

switch ($_GET["op"]) {
    case "get_videos":
        // Obtiene todos los videos
        $data = $video->getVideos();
        echo json_encode($data);
        break;

    case "get_video_by_id":
        // Obtiene un video por su ID
        $data = $video->getVideoById($_POST["id"]);
        echo json_encode($data);
        break;

    case "register_video":
        // Registra un nuevo video
        $titulo = $_POST['titulo'];
        $descripcion = $_POST['descripcion'];
        $file = isset($_FILES['file']) ? $_FILES['file'] : null;
        $link = $_POST['link'] ?? '';
        $result = $video->registerVideo($titulo, $descripcion, $file, $link );
        echo json_encode(['success' => $result]);
        break;

    case "update_video":
        // Actualiza un video
        $id = $_POST['id'];
        $titulo = $_POST['titulo'];
        $descripcion = $_POST['descripcion'];
        $file = isset($_FILES['file']) ? $_FILES['file'] : null;
        $link = $_POST['link'] ?? '';
        $result = $video->updateVideo($id, $titulo, $descripcion, $file, $link);
        echo json_encode(['success' => $result]);
        break;

        case "delete_video":
            // Elimina un video
            $id = $_POST['id'];
            $result = $video->deleteVideo($id);
            echo json_encode(['success' => $result]);
            break;
        

    case "change_video_order":
        // Cambia el orden de un video
        $id = $_POST['id'];
        $direction = $_POST['direction'];
        $result = $video->changeVideoOrder($id, $direction);
        echo json_encode(['success' => $result]);
        break;
}
?>
