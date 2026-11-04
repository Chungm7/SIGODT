<?php
/** Identity remains the existing SIGODT login principal; never promote pending data. */
class AccountSecurity
{
    public static function identity(array $session): ?array
    {
        $id = $session['usua_id_SIGODT'] ?? null;
        $doc = $session['usua_dni_SIGODT'] ?? null;
        if (!is_scalar($id) || !filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) || !is_string($doc) || !preg_match('/\A[0-9]{8}\z/D', $doc)) return null;
        // models/Usuario.php authenticates exclusively usu_tipo=P.
        return ['id' => (int)$id, 'document' => $doc, 'type' => 'P'];
    }

    public static function csrf(array &$session): string
    {
        if (!is_string($session['account_csrf'] ?? null) || !preg_match('/\A[a-f0-9]{64}\z/D', $session['account_csrf'])) {
            $session['account_csrf'] = bin2hex(random_bytes(32));
        }
        return $session['account_csrf'];
    }

    public static function validCsrf(array $session, $token): bool
    {
        return is_string($token) && is_string($session['account_csrf'] ?? null) && strlen($session['account_csrf']) === 64 && hash_equals($session['account_csrf'], $token);
    }

    public static function validNewPassword(string $password): bool
    {
        return strlen($password) >= 8 && strlen($password) <= 1024 && strpos($password, "\0") === false
            && preg_match('/[A-Z]/', $password) && preg_match('/[a-z]/', $password)
            && preg_match('/[0-9]/', $password) && preg_match('/[^A-Za-z0-9]/', $password);
    }
}
