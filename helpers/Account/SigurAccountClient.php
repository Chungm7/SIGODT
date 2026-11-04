<?php
/** Source-aligned SIGUR account adapter. No configuration/session side effects. */
class SigurAccountException extends RuntimeException {}

class SigurAccountClient
{
    private $env;
    private $transport;

    /** Transport receives a request array and returns ['status'=>int, 'body'=>string]. */
    public function __construct(?callable $env = null, ?callable $transport = null)
    {
        $this->env = $env ?? static function ($key, $default = null) {
            return Conectar::getEnv($key, $default);
        };
        $this->transport = $transport ?? [self::class, 'curlTransport'];
    }

    public function currentAccount(array $identity): array
    {
        $data = $this->operation('ws/mostrar.php', $this->identityFields($identity));
        return $this->profile($data, $identity);
    }

    public function updatePhone(array $identity, string $phone): array
    {
        if (!preg_match('/\A[0-9]{9}\z/D', $phone)) throw new InvalidArgumentException('invalid_phone');
        $this->operation('ws/actualizar.php', $this->identityFields($identity) + ['tipo_cambio' => '1', 'dato' => $phone]);
        return [];
    }

    public function updatePersonalEmail(array $identity, string $email): array
    {
        if (strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('invalid_personal_email');
        $this->operation('ws/actualizar.php', $this->identityFields($identity) + ['tipo_cambio' => '2', 'dato' => $email]);
        return [];
    }

    public function changePassword(array $identity, string $current, string $new): array
    {
        if ($current === '' || strlen($current) > 1024 || !AccountSecurity::validNewPassword($new)) {
            throw new InvalidArgumentException('invalid_password');
        }
        $this->operation('controller/clave.php?op=actualizar', $this->identityFields($identity) + [
            'usu_pass_actual' => $current, 'usu_pass_nueva' => $new,
        ]);
        return ['transition' => 'settings'];
    }

    /** Internal T4 seam only: never takes a caller-supplied recipient. */
    public function sendInstitutionalOtpMail(array $identity, string $code, string $name = 'Servidor Municipal', int $expires = 120): array
    {
        if (!preg_match('/\A[0-9]{6}\z/D', $code) || $expires < 1 || $expires > 300) {
            throw new InvalidArgumentException('invalid_otp_mail');
        }
        $fields = $this->identityFields($identity);
        [$base, $auth, $credentials] = $this->configuration();
        $token = $this->token($auth, $credentials);
        $data = $this->request($base . 'ws/mostrar.php', $fields, $token);
        $profile = $this->profile($data, $identity);
        $email = $profile['institutional_email'];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new SigurAccountException('institutional_email_unavailable');
        $safeName = htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $this->request($base . 'ws/correo-ss.php', [
            'destino' => $email,
            'titulo' => 'Código de Seguridad 2FA - SIGODT',
            'mensaje' => '<p>' . $safeName . '</p><p>Código de Seguridad 2FA: <b>' . $code . '</b></p><p>Tiempo de validez: ' . $expires . ' segundos.</p>',
        ], $token);
        return ['success' => true, 'simulated' => false];
    }

    private function identityFields(array $id): array
    {
        if (!isset($id['id'], $id['document'], $id['type']) || !filter_var($id['id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) || !is_string($id['document']) || !preg_match('/\A[0-9]{8}\z/D', $id['document']) || $id['type'] !== 'P') {
            throw new InvalidArgumentException('invalid_identity');
        }
        return ['usu_doc' => $id['document'], 'usu_tipo' => $id['type']];
    }

    private function profile(array $response, array $identity): array
    {
        $data = $response['data'] ?? null;
        if (!is_array($data) || (isset($data['usu_id']) && (string)$data['usu_id'] !== (string)$identity['id']) || (isset($data['usu_doc']) && $data['usu_doc'] !== $identity['document'])) {
            throw new SigurAccountException('account_service_unavailable');
        }
        $out = [];
        // SIINDI mostrar_datos explicitly filters these three aliases.
        foreach (['phone' => 'usu_celu01', 'personal_email' => 'usu_emailp', 'institutional_email' => 'usu_emails'] as $key => $alias) {
            if (isset($data[$alias]) && !is_string($data[$alias])) throw new SigurAccountException('account_service_unavailable');
            $out[$key] = $data[$alias] ?? '';
        }
        return $out;
    }

    private function configuration(): array
    {
        $get = $this->env;
        $base = $get('SIGUR_URL', '');
        $initials = $get('SIST_INIC', '');
        $password = $get('SIST_PASS', '');
        if (!is_string($base) || !is_string($initials) || !is_string($password) || $initials === '' || $password === '') throw new SigurAccountException('account_configuration_missing');
        $base = rtrim($base, '/') . '/';
        // Destination SIGUR_URL is the /sigur/ root, not SIINDI's host root.
        if (preg_match('#/ws/$#', $base)) $base = substr($base, 0, -3);
        $auth = $get('SIGUR_AUTH_URL', '') ?: $base . 'auth/';
        foreach ([$base, $auth] as $url) {
            $parts = is_string($url) ? parse_url($url) : false;
            if (!$parts || ($parts['scheme'] ?? '') !== 'https' || empty($parts['host']) || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])) {
                throw new SigurAccountException('account_configuration_missing');
            }
        }
        return [$base, $auth, ['sist_inic' => $initials, 'sist_pass' => $password]];
    }

    private function token(string $url, array $credentials): string
    {
        $response = $this->request($url, $credentials);
        $token = $response['data']['token'] ?? null;
        if (!is_string($token) || $token === '' || preg_match('/[\r\n]/', $token)) throw new SigurAccountException('account_service_unavailable');
        return $token;
    }

    private function operation(string $path, array $fields): array
    {
        [$base, $auth, $credentials] = $this->configuration();
        return $this->request($base . $path, $fields, $this->token($auth, $credentials));
    }

    private function request(string $url, array $fields, ?string $token = null): array
    {
        try {
            $response = ($this->transport)([
                'url' => $url, 'method' => 'POST', 'fields' => $fields,
                'headers' => $token === null ? [] : ['Authorization: Bearer ' . $token],
                'connect_timeout' => 5, 'timeout' => 20, 'verify_peer' => true,
                'verify_host' => 2, 'follow_redirects' => false,
            ]);
            if (!is_array($response) || !is_int($response['status'] ?? null) || $response['status'] < 200 || $response['status'] >= 300 || !is_string($response['body'] ?? null)) throw new RuntimeException();
            $json = json_decode($response['body'], true);
            if (!is_array($json) || ($json['success'] ?? null) !== true || (isset($json['status']) && (string)$json['status'] !== '200')) throw new RuntimeException();
            return $json;
        } catch (Throwable $e) {
            // Deliberately discard upstream bodies, URLs and exception messages.
            throw new SigurAccountException('account_service_unavailable');
        }
    }

    public static function curlTransport(array $request): array
    {
        if (!function_exists('curl_init')) throw new RuntimeException();
        $ch = curl_init($request['url']);
        curl_setopt_array($ch, [
            CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($request['fields']),
            CURLOPT_HTTPHEADER => array_merge(['Content-Type: application/x-www-form-urlencoded'], $request['headers']),
            CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => $request['connect_timeout'],
            CURLOPT_TIMEOUT => $request['timeout'], CURLOPT_SSL_VERIFYPEER => $request['verify_peer'],
            CURLOPT_SSL_VERIFYHOST => $request['verify_host'], CURLOPT_FOLLOWLOCATION => false,
        ]);
        // Bound response memory independently of an upstream Content-Length.
        $body = '';
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, static function ($handle, $chunk) use (&$body) {
            if (strlen($body) + strlen($chunk) > 1048576) return 0;
            $body .= $chunk;
            return strlen($chunk);
        });
        $ok = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_errno($ch);
        curl_close($ch);
        if ($ok === false || $error) throw new RuntimeException();
        return ['status' => $status, 'body' => $body];
    }
}
require_once __DIR__ . '/AccountSecurity.php';
