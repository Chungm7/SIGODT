<?php
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
    public static $conexionDouble = null;

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

    /**
     * Determines whether the current request is over HTTPS.
     */
    public static function isHttps(): bool {
        if (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') {
            return true;
        }
        if (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) {
            return true;
        }
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string)$_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
            return true;
        }
        return false;
    }

    /**
     * Obtains the cookie path scoped to the application URL path.
     */
    public static function getCookiePath(): string {
        $url = self::getEnv('APP_URL', 'http://10.10.10.16/SIGODT/');
        $path = parse_url($url, PHP_URL_PATH);
        $trimmed = is_string($path) ? trim($path, '/') : '';
        if ($trimmed === '') {
            return '/SIGODT/';
        }
        return '/' . $trimmed . '/';
    }

    /**
     * Initializes the SIGODT session with isolated name and secure cookie parameters.
     */
    public static function initSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            if (!headers_sent()) {
                session_name('SIGODT_SESSID');
                session_set_cookie_params([
                    'lifetime' => 0,
                    'path'     => self::getCookiePath(),
                    'domain'   => '',
                    'secure'   => self::isHttps(),
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]);
            }
            @session_start();
        }
    }

    protected function conexion($throwOnError = false) {
        if (property_exists(self::class, 'conexionDouble') && self::$conexionDouble !== null) {
            $double = self::$conexionDouble;
            return is_callable($double) ? $double() : $double;
        }
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
            if ($throwOnError) {
                throw $e;
            }
            print "Error BD!: " . $e->getMessage() . "<br/>";
            die();
        }
    }

    public function set_names() {
        if (property_exists(self::class, 'conexionDouble') && self::$conexionDouble !== null) {
            return true;
        }
        return $this->dbh->query("SET NAMES 'utf8'");
    }

    public static function ruta(): string {
        $url = self::getEnv('APP_URL', 'http://10.10.10.16/SIGODT/');
        return rtrim($url, '/') . '/';
    }

    /**
     * Valida si la sesión actual pertenece a un usuario con rol Administrador
     */
    public static function esAdministrador(): bool {
        if (!isset($_SESSION["usua_id_SIGODT"])) {
            return false;
        }
        $roleId = filter_var($_SESSION["rol_id_SIGODT"] ?? null, FILTER_VALIDATE_INT);
        if ($roleId === 9) {
            return true;
        }
        $roleName = $_SESSION["rol_nombre_SIGODT"] ?? '';
        return is_string($roleName) && strcasecmp(trim($roleName), 'ADMINISTRADOR') === 0;
    }
}

Conectar::initSession();
?>