<?php
$host = "10.10.10.16";
$port = "5432"; // Puerto predeterminado de PostgreSQL
$dbname = "db_simcix";
$user = "postgres";
$password = "Mpch*2023*";

$con = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");
if (!$con) {
    echo "Error: No se pudo conectar a la base de datos.";
    exit;
}
?>
