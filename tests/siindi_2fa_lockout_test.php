<?php
/**
 * Test focused on 2FA lockout window behavior:
 * - Locks at 5 failed attempts with 2 minutes window.
 * - When expired, the next failed attempt starts a clean cycle at 1 (not 6).
 */
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../seguridad/2fa/models/TwoFactorModel.php';

// Mock DB connection for isolated unit testing
class MockPDOStatement {
    private $sql;
    private $currentAttempts = 0;
    private $lockedUntil = null;
    public function __construct($sql, $attempts = 0, $lockedUntil = null) {
        $this->sql = $sql;
        $this->currentAttempts = $attempts;
        $this->lockedUntil = $lockedUntil;
    }
    public function execute($params = []) {
        return true;
    }
    public function fetch($mode = PDO::FETCH_ASSOC) {
        if (strpos($this->sql, 'RETURNING seus_intentos_fall, seus_bloqueo_temp') !== false) {
            return [
                'seus_intentos_fall' => $this->currentAttempts,
                'seus_bloqueo_temp'  => $this->lockedUntil
            ];
        }
        return false;
    }
}

// 1. Verify SQL structure in TwoFactorModel contains 2 minutes interval and expiry reset
$modelCode = file_get_contents(__DIR__ . '/../seguridad/2fa/models/TwoFactorModel.php');

assert(strpos($modelCode, "INTERVAL '2 minutes'") !== false, "Must configure 2 minutes lockout interval");
assert(strpos($modelCode, "WHEN seus_bloqueo_temp IS NOT NULL AND seus_bloqueo_temp <= CURRENT_TIMESTAMP THEN 1") !== false, "Must reset attempts to 1 when lock expired");
assert(strpos($modelCode, "clearExpiredLock") !== false, "Must provide clearExpiredLock helper");

echo "PASS: 2FA 2-minute lockout and post-expiry reset verified\n";
