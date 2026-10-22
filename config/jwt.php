<?php
/**
 * JWT & JWE Security Handler
 *
 * Combines compact JWE encryption (A256KW / A256CBC-HS512) and HMAC-SHA256 JWT
 * signature verification for cross-system authentication with SIGUR.
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/jwe.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\BeforeValidException;
use Jose\Component\Core\JWK;
use Jose\Component\Core\Util\Base64UrlSafe;

class JwtHandler {
    private string $jwt_key;
    private JWK $jwe_key;
    private string $algorithm = 'HS256';

    /**
     * Initializes JWT signing key and JWE encryption key.
     *
     * @param string|null $jwtKey Optional explicit JWT secret key.
     * @param string|null $jweSecret Optional explicit JWE secret key.
     */
    public function __construct(?string $jwtKey = null, ?string $jweSecret = null) {
        $this->jwt_key = $jwtKey ?? $_ENV['JWT_SECRET'] ?? getenv('JWT_SECRET') ?: 'Mun12345_GLE';

        $rawJwe = $jweSecret ?? $_ENV['JWE_SECRET'] ?? getenv('JWE_SECRET') ?: 'Mun12345_GLE';
        $sharedSecret = substr(hash('sha256', (string)$rawJwe, true), 0, 32);
        $this->jwe_key = new JWK([
            'kty' => 'oct',
            'k' => Base64UrlSafe::encodeUnpadded($sharedSecret),
            'use' => 'enc'
        ]);
    }

    /**
     * Emits a standard JSON response and terminates execution (for API endpoints).
     */
    public function response(string $message, array $data = [], bool $suc = true, int $httpStatusCode = 200): void {
        date_default_timezone_set('UTC');
        $status = $httpStatusCode;
        $success = $suc;
        $timestamp = date("Y-m-d H:i:s");
        http_response_code($httpStatusCode);
        echo json_encode(compact('timestamp', 'status', 'success', 'message', 'data'), JSON_UNESCAPED_UNICODE);
        exit();
    }

    /**
     * Signs JWT and encrypts with JWE.
     *
     * @param mixed $data Custom payload data.
     * @param string $type Operation type identifier (e.g. 'blanqueamiento').
     * @param int $time Expiration delta in seconds.
     * @return string Compact encrypted JWE token.
     */
    public function encrypt($data = null, string $type = '', int $time = 1500): string {
        try {
            $iss = $_ENV["DB_HOST"] ?? getenv("DB_HOST") ?: 'localhost';
            $payload = [
                'iss' => $iss,
                'aud' => $iss,
                'success' => 'true',
                'type' => $type,
                'iat' => time(),
                'exp' => time() + $time,
                'system' => [
                    "BASE_URL" => $_ENV["BASE_URL"] ?? getenv("BASE_URL") ?: 'http://10.10.10.16/',
                    "DOMAIN" => $_ENV["DOMAIN"] ?? getenv("DOMAIN") ?: '10.10.10.16',
                    "APP_NAME" => $_ENV["APP_NAME"] ?? getenv("APP_NAME") ?: 'SIGODT'
                ],
                'data' => $data
            ];

            $jwtString = JWT::encode($payload, $this->jwt_key, $this->algorithm);
            return encryptJWE($jwtString, $this->jwe_key);
        } catch (\Throwable $e) {
            if (php_sapi_name() === 'cli' && empty($_SERVER['HTTP_HOST'])) {
                throw new \RuntimeException("Encryption error: " . $e->getMessage(), 0, $e);
            }
            $this->response("Error al encriptar: " . $e->getMessage(), [], false, 500);
            return '';
        }
    }

    /**
     * Decrypts JWE token and verifies JWT signature.
     *
     * @param string $token Compact JWE token.
     * @return object Decoded JWT payload object or error object.
     */
    public function decrypt(string $token): object {
        try {
            $decrypted = decryptJWE($token, $this->jwe_key);
            if ($decrypted === false) {
                return (object)[
                    "message" => "JWE decryption failed",
                    "success" => false,
                    "status" => 498
                ];
            }
            return JWT::decode($decrypted, new Key($this->jwt_key, $this->algorithm));
        } catch (ExpiredException $e) {
            return (object)[
                "message" => "El token ha expirado",
                "success" => false,
                "status" => 498
            ];
        } catch (SignatureInvalidException $e) {
            return (object)[
                "message" => "La firma es inválida",
                "success" => false,
                "status" => 498
            ];
        } catch (BeforeValidException $e) {
            return (object)[
                "message" => "El token aún no es válido",
                "success" => false,
                "status" => 498
            ];
        } catch (\Throwable $e) {
            return (object)[
                "message" => "Token inválido: " . $e->getMessage(),
                "success" => false,
                "status" => 498
            ];
        }
    }

    /**
     * Checks if current connection is over HTTPS.
     */
    private function isHttps(): bool {
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
     * Builds cookie options adhering to security guidelines.
     *
     * @param int $expireSeconds Expiration delta in seconds (0 = session).
     * @param string $path Cookie URL scope path.
     * @return array Standard cookie options.
     */
    public function getCookieOptions(int $expireSeconds = 0, string $path = '/'): array {
        $expires = $expireSeconds > 0 ? time() + $expireSeconds : 0;

        if ($path !== '/') {
            $path = '/' . trim($path, '/') . '/';
        }

        $domain = $_ENV['DOMAIN'] ?? getenv('DOMAIN') ?: '';

        return [
            'expires'  => $expires,
            'path'     => $path,
            'domain'   => $domain,
            'secure'   => $this->isHttps(),
            'httponly' => true,
            'samesite' => 'Lax'
        ];
    }

    /**
     * Sets a cookie with strict security headers (httponly, secure if HTTPS, samesite Lax).
     *
     * @param string $name Cookie name.
     * @param mixed $value Cookie value.
     * @param int $expireSeconds Lifetime in seconds.
     * @param string $path Cookie path scope.
     * @return bool True on success, false on error.
     */
    public function setCookie(string $name, $value, int $expireSeconds = 0, string $path = '/'): bool {
        try {
            if (is_array($value) || is_object($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            }

            $options = $this->getCookieOptions($expireSeconds, $path);
            return setcookie($name, (string)$value, $options);
        } catch (\Throwable $e) {
            if (php_sapi_name() === 'cli' && empty($_SERVER['HTTP_HOST'])) {
                return false;
            }
            $this->response("Error al asignar el cookie: " . $e->getMessage(), [], false, 500);
            return false;
        }
    }
}
