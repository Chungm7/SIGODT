<?php
/**
 * Test contract and smoke test for api/buscar_automatizado.php
 */

$filePath = __DIR__ . '/../api/buscar_automatizado.php';
assert(file_exists($filePath), "api/buscar_automatizado.php must exist.");

$source = file_get_contents($filePath);

// 1. Verify advisory lock distinctness (dedicated lock 987654334)
assert(strpos($source, '987654334') !== false, "Must use dedicated advisory lock 987654334.");
assert(strpos($source, 'pg_try_advisory_lock') !== false, "Must check pg_try_advisory_lock.");
assert(strpos($source, 'pg_advisory_unlock') !== false, "Must unlock advisory lock on exit.");

// 2. Verify target criteria: queries unpaid orders in state (1, 2)
assert(strpos($source, 'tp.orden_est in (1,2)') !== false, "Must filter orders with orden_est in (1,2).");

// 3. Verify batch limit (20 orders)
assert(strpos($source, '$limite = 20') !== false, "Must configure batch limit to 20.");

// 4. Verify in-memory exclusion of failed/unpaid orders to prevent infinite loop in same run
assert(strpos($source, '$ordenes_con_error') !== false, "Must track errored orders in memory.");
assert(strpos($source, '$where_error') !== false, "Must exclude errored orders from subsequent queries.");

// 5. Verify token handling and renewal
assert(strpos($source, 'get_active_token') !== false, "Must verify active token.");
assert(strpos($source, 'generate_new_token') !== false, "Must renew token when expired.");

echo "PASS: api/buscar_automatizado.php contracts verified successfully.\n";
