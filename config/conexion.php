<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cargar autoload de Composer y variables de entorno .env si existen
$rootDir = dirname(__DIR__);
if (file_exists($rootDir . '/vendor/autoload.php')) {
    require_once $rootDir . '/vendor/autoload.php';
    if (file_exists($rootDir . '/.env')) {
        $dotenv = Dotenv\Dotenv::createImmutable($rootDir);
        $dotenv->safeLoad();
    }
}

class Conectar {
    protected $dbh;

    /**
     * Obtiene una variable de entorno con valor predeterminado
     */
    public static function getEnv(string $key, $default = null) {
        if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
            return $_ENV[$key];
        }
        if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
            return $_SERVER[$key];
        }
        $val = getenv($key);
        if ($val !== false && $val !== '') {
            return $val;
        }
        return $default;
    }

    /**
     * Obtiene la IP del cliente de forma segura.
     * En entornos no productivos, si la conexión proviene de localhost o IPv6 local,
     * permite utilizar la IP configurada en DEV_MOCK_IP para interactuar con sisSeguridad.
     */
    public static function getClientIp(): string {
        $appEnv = self::getEnv('APP_ENV', 'production');
        $devMockIp = self::getEnv('DEV_MOCK_IP', '');
        $remoteIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        // Solo permitir mock en desarrollo/local cuando la IP sea loopback o IPv6
        if ($appEnv !== 'production' && !empty($devMockIp)) {
            if ($remoteIp === '127.0.0.1' || $remoteIp === '::1' || strpos($remoteIp, '::') === 0) {
                return $devMockIp;
            }
        }

        // Si se encuentra detrás de proxy confiable
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $firstIp = trim($ips[0]);
            if (filter_var($firstIp, FILTER_VALIDATE_IP)) {
                return $firstIp;
            }
        }

        return $remoteIp;
    }

    protected function conexion() {
        try {
            $host = self::getEnv('DB_HOST', '10.10.10.16');
            $port = self::getEnv('DB_PORT', '5432');
            $dbname = self::getEnv('DB_NAME', 'db_simcix');
            $user = self::getEnv('DB_USER', 'postgres');
            $pass = self::getEnv('DB_PASS', '');

            $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";
            $conectar = $this->dbh = new PDO($dsn, $user, $pass);
            $conectar->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $conectar->exec("SET NAMES 'utf8'");
            return $conectar;
        } catch (Exception $e) {
            print "Error BD!: " . $e->getMessage() . "<br/>";
            die();
        }
    }

    public function set_names() {
        return $this->dbh->query("SET NAMES 'utf8'");
    }

    public static function ruta(): string {
        $url = self::getEnv('APP_URL', 'http://10.10.10.16/SIGODT/');
        return rtrim($url, '/') . '/';
    }
}
?>