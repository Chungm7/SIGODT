<?php 
    require_once("../../config/conexion.php");
    require_once("../../models/Usuario.php");
    $usuario = new Usuario();
    $usuario->logout($_SESSION["historial_login_masgd"]);
?>