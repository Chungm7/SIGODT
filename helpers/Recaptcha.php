<?php
/**
 * Server-side verification for Google reCAPTCHA v3.
 *
 * Complements the image CAPTCHA (helpers/Captcha.php). Every failure path is
 * fail-closed: a missing configuration, a network error or an unexpected
 * response never authorizes a login attempt.
 */
class Recaptcha
{
    const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';
    const ACTION = 'login';
    const MAX_TOKEN_LENGTH = 4096;
    const TIMEOUT = 5;
    const DEFAULT_MIN_SCORE = 0.5;

    /** @var callable|null Injected HTTP client: fn(string $url, array $fields, int $timeout): ?string */
    private static $httpClient = null;

    public static function setHttpClient(callable $client = null)
    {
        self::$httpClient = $client;
    }

    private static function env($key, $default = null)
    {
        if (class_exists('Conectar', false) && method_exists('Conectar', 'getEnv')) {
            return Conectar::getEnv($key, $default);
        }
        $value = getenv($key);
        return ($value === false || $value === '') ? $default : $value;
    }

    /**
     * Reads the reCAPTCHA settings. Secrets stay inside this array and are
     * never echoed; only the site key is exposed to the frontend.
     */
    public static function config()
    {
        $flag = strtolower(trim((string) self::env('RECAPTCHA_ENABLED', '')));
        $min = self::env('RECAPTCHA_MIN_SCORE', self::DEFAULT_MIN_SCORE);
        $min = (is_numeric($min) && $min >= 0 && $min <= 1) ? (float) $min : self::DEFAULT_MIN_SCORE;
        return [
            'flag' => in_array($flag, ['1', 'true', 'yes', 'on'], true),
            'site_key' => trim((string) self::env('RECAPTCHA_SITE_KEY', '')),
            'secret_key' => trim((string) self::env('RECAPTCHA_SECRET_KEY', '')),
            'min_score' => $min,
            'hostname' => trim((string) self::env('RECAPTCHA_EXPECTED_HOSTNAME', '')),
        ];
    }

    /** True when the flag is on AND both keys exist. */
    public static function isEnabled(array $config = null)
    {
        $config = $config ?? self::config();
        return $config['flag'] && $config['site_key'] !== '' && $config['secret_key'] !== '';
    }

    /** Flag on but a key missing: login must be rejected, not silently allowed. */
    public static function isMisconfigured(array $config = null)
    {
        $config = $config ?? self::config();
        return $config['flag'] && !self::isEnabled($config);
    }

    /** Site key for the frontend, or '' when the feature is not active. */
    public static function siteKey(array $config = null)
    {
        $config = $config ?? self::config();
        return self::isEnabled($config) ? $config['site_key'] : '';
    }

    /**
     * Decides whether the login request passes the reCAPTCHA layer.
     * Disabled mode passes; enabled or misconfigured mode requires a valid token.
     */
    public static function check($token, $remoteIp = null, array $config = null)
    {
        $config = $config ?? self::config();
        if (self::isMisconfigured($config)) {
            return false;
        }
        if (!self::isEnabled($config)) {
            return true;
        }
        return self::verify($token, $remoteIp, $config);
    }

    public static function verify($token, $remoteIp, array $config)
    {
        if (!is_string($token) || $token === '' || strlen($token) > self::MAX_TOKEN_LENGTH
            || !preg_match('/^[A-Za-z0-9_-]+$/D', $token)) {
            return false;
        }
        $fields = ['secret' => $config['secret_key'], 'response' => $token];
        if (is_string($remoteIp) && filter_var($remoteIp, FILTER_VALIDATE_IP)) {
            $fields['remoteip'] = $remoteIp;
        }
        $client = self::$httpClient ?? [self::class, 'curlPost'];
        try {
            $body = $client(self::VERIFY_URL, $fields, self::TIMEOUT);
        } catch (Throwable $e) {
            return false;
        }
        if (!is_string($body)) {
            return false;
        }
        $data = json_decode($body, true);
        if (!is_array($data) || ($data['success'] ?? null) !== true) {
            return false;
        }
        if (($data['action'] ?? null) !== self::ACTION) {
            return false;
        }
        if ($config['hostname'] !== '' && ($data['hostname'] ?? null) !== $config['hostname']) {
            return false;
        }
        $score = $data['score'] ?? null;
        return (is_int($score) || is_float($score)) && $score >= $config['min_score'];
    }

    /** Default HTTP client: short timeout, TLS verification enabled. */
    public static function curlPost($url, array $fields, $timeout)
    {
        if (!function_exists('curl_init')) {
            return null;
        }
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($fields),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FOLLOWLOCATION => false,
        ]);
        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        if ($body === false || $status !== 200) {
            return null;
        }
        return $body;
    }
}
