<?php
require_once dirname(__DIR__) . '/config/conexion.php';

$host = Conectar::getEnv('DB_HOST', '10.10.10.16');
$port = Conectar::getEnv('DB_PORT', '5432');
$dbname = Conectar::getEnv('DB_NAME', 'db_simcix');
$user = Conectar::getEnv('DB_USER', 'postgres');
$password = Conectar::getEnv('DB_PASS', '');

$con = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");
if (!$con) {
    echo "Error: No se pudo conectar a la base de datos.";
    exit;
}
?>
