<?php
/**
 * Unit test for ENABLE_2FA feature flag.
 * Verifies that when ENABLE_2FA is false, 2FA gating is disabled,
 * and when true, 2FA gating is active.
 */
require_once __DIR__ . '/../config/conexion.php';

function assertCondition(bool $cond, string $msg): void {
    if (!$cond) {
        fwrite(STDERR, "FAIL: $msg\n");
        exit(1);
    }
}

// 1. Boolean resolution for false
$_ENV['ENABLE_2FA'] = 'false';
$val = filter_var(Conectar::getEnv('ENABLE_2FA', 'true'), FILTER_VALIDATE_BOOLEAN);
assertCondition($val === false, 'ENABLE_2FA=false resolves to false');

// 2. Boolean resolution for true
$_ENV['ENABLE_2FA'] = 'true';
$val = filter_var(Conectar::getEnv('ENABLE_2FA', 'true'), FILTER_VALIDATE_BOOLEAN);
assertCondition($val === true, 'ENABLE_2FA=true resolves to true');

// 3. Fallback when key does not exist in environment
$unsetKey = 'NON_EXISTENT_KEY_' . bin2hex(random_bytes(4));
$val = filter_var(Conectar::getEnv($unsetKey, 'true'), FILTER_VALIDATE_BOOLEAN);
assertCondition($val === true, 'Unset flag resolves to default fallback');

// 4. View setting gating simulation
$twoFactorPerfilFile = __DIR__ . '/../seguridad/2fa/views/perfil_2fa.php';
$_ENV['ENABLE_2FA'] = 'false';
$enable2FA = filter_var(
    method_exists('Conectar', 'getEnv') ? Conectar::getEnv('ENABLE_2FA', 'true') : ($_ENV['ENABLE_2FA'] ?? 'true'),
    FILTER_VALIDATE_BOOLEAN
);
$renderedWhenFalse = ($enable2FA && file_exists($twoFactorPerfilFile));
assertCondition($renderedWhenFalse === false, '2FA card is not included when ENABLE_2FA=false');

$_ENV['ENABLE_2FA'] = 'true';
$enable2FA = filter_var(
    method_exists('Conectar', 'getEnv') ? Conectar::getEnv('ENABLE_2FA', 'true') : ($_ENV['ENABLE_2FA'] ?? 'true'),
    FILTER_VALIDATE_BOOLEAN
);
$renderedWhenTrue = ($enable2FA && file_exists($twoFactorPerfilFile));
assertCondition($renderedWhenTrue === true, '2FA card is included when ENABLE_2FA=true');

echo "PASS: 2FA feature flag gating verified (ENABLE_2FA true/false/fallback)\n";
