<?php
require_once __DIR__ . '/../helpers/Account/AccountSecurity.php';
require_once __DIR__ . '/../helpers/Account/SigurAccountClient.php';

/** Injectable public handler; no live includes, output or HTTP mutation here. */
function account_api_handle(array &$session, string $method, string $op, array $input, $client): array
{
    $reply = static function (int $status, string $error = '', array $data = []) {
        return ['status' => $status, 'body' => $error === ''
            ? ['success' => true, 'data' => $data]
            : ['success' => false, 'error' => $error]];
    };
    $identity = AccountSecurity::identity($session);
    if ($identity === null) return $reply(401, 'authentication_required');
    $methods = ['profile' => 'GET', 'csrf' => 'GET', 'update_phone' => 'POST', 'update_personal_email' => 'POST', 'change_password' => 'POST'];
    if (!isset($methods[$op])) return $reply(404, 'operation_not_found');
    if ($method !== $methods[$op]) return $reply(405, 'method_not_allowed');
    // Reject every extra field, rather than silently accepting identity overrides.
    $allowed = ['profile' => [], 'csrf' => [], 'update_phone' => ['csrf_token', 'phone'],
        'update_personal_email' => ['csrf_token', 'personal_email'],
        'change_password' => ['csrf_token', 'current_password', 'new_password', 'confirm_password']];
    if (array_diff(array_keys($input), $allowed[$op])) return $reply(400, 'unexpected_field');
    if ($method === 'POST' && !AccountSecurity::validCsrf($session, $input['csrf_token'] ?? null)) return $reply(403, 'csrf_invalid');
    try {
        if ($op === 'csrf') return $reply(200, '', ['csrf_token' => AccountSecurity::csrf($session)]);
        if ($op === 'profile') {
            $profile = $client->currentAccount($identity);
            foreach (['phone', 'personal_email', 'institutional_email'] as $key) {
                if (!is_string($profile[$key] ?? null)) throw new RuntimeException();
            }
            $session['pers_celu01_SIGODT'] = $profile['phone'];
            $session['pers_emailp_SIGODT'] = $profile['personal_email'];
            $session['pers_emailm_SIGODT'] = $profile['institutional_email'];
            return $reply(200, '', $profile + [
                'names' => $session['pers_nombre_SIGODT'] ?? '',
                'surnames' => $session['pers_apellidos_SIGODT'] ?? '',
                'display_name' => $session['nombre_completo_SIGODT'] ?? '',
                'role' => $session['rol_nombre_SIGODT'] ?? '',
                // Local login has a role and dependency, but no observed job-title field.
                'position' => null,
            ]);
        }
        if ($op === 'update_phone') {
            $phone = $input['phone'] ?? null;
            if (!is_string($phone) || !preg_match('/\A[0-9]{9}\z/D', $phone)) return $reply(422, 'invalid_phone');
            $client->updatePhone($identity, $phone);
            $session['pers_celu01_SIGODT'] = $phone;
            return $reply(200, '', ['phone' => $phone]);
        }
        if ($op === 'update_personal_email') {
            $email = $input['personal_email'] ?? null;
            if (!is_string($email) || strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)) return $reply(422, 'invalid_personal_email');
            $client->updatePersonalEmail($identity, $email);
            $session['pers_emailp_SIGODT'] = $email;
            return $reply(200, '', ['personal_email' => $email]);
        }
        $current = $input['current_password'] ?? null;
        $new = $input['new_password'] ?? null;
        $confirm = $input['confirm_password'] ?? null;
        if (!is_string($current) || $current === '' || strlen($current) > 1024 || strpos($current, "\0") !== false || !is_string($new) || !AccountSecurity::validNewPassword($new) || !is_string($confirm) || $new !== $confirm) return $reply(422, 'invalid_password');
        $client->changePassword($identity, $current, $new);
        return $reply(200, '', ['transition' => 'settings']);
    } catch (Throwable $e) {
        return $reply(502, 'account_service_unavailable');
    }
}

function account_api_render(array &$session, string $method, string $op, array $input, $client): void
{
    $response = account_api_handle($session, $method, $op, $input, $client);
    http_response_code($response['status']);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    if ($response['status'] === 405) header('Allow: ' . (in_array($op, ['profile', 'csrf'], true) ? 'GET' : 'POST'));
    echo json_encode($response['body'], JSON_INVALID_UTF8_SUBSTITUTE);
}

if (!defined('ACCOUNT_API_LIBRARY_ONLY')) {
    require_once __DIR__ . '/../config/conexion.php';
    // Conectar initializes SIGODT_SESSID; URL supplies only the operation.
    $op = is_string($_GET['op'] ?? null) ? $_GET['op'] : '';
    $input = $_POST;
    // No identity, secrets or contact values accepted through query parameters.
    if (array_diff(array_keys($_GET), ['op'])) $input['unexpected_query_field'] = true;
    account_api_render($_SESSION, $_SERVER['REQUEST_METHOD'] ?? '', $op, $input, new SigurAccountClient());
}
