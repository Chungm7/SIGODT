<?php
/**
 * Test contract for api/reconciliar_extornos.php
 */

$filePath = __DIR__ . '/../api/reconciliar_extornos.php';
assert(file_exists($filePath), "api/reconciliar_extornos.php must exist.");

$source = file_get_contents($filePath);

// 1. Verify advisory lock distinctness from buscar_automatizado (987654330)
assert(strpos($source, '987654335') !== false, "Must use dedicated advisory lock 987654335.");
assert(strpos($source, 'pg_try_advisory_lock') !== false, "Must check pg_try_advisory_lock.");
assert(strpos($source, 'pg_advisory_unlock') !== false, "Must unlock advisory lock on exit.");

// 2. Verify target criteria: queries paid orders (est = 4)
assert(strpos($source, 'WHERE est = 4') !== false, "Must filter orders with est = 4.");
assert(strpos($source, 'CURRENT_DATE') !== false, "Must bound query using CURRENT_DATE.");

// 3. Verify days parameter bounding (0 for all-time, or 1 to 365)
assert(strpos($source, "\$dias === 0") !== false, "Must support dias === 0 for historical full backfill.");
assert(strpos($source, "'all'") !== false, "Must support 'all' keyword for full backfill.");
assert(strpos($source, '$dias < 1') !== false, "Must bound minimum sliding days to 1.");
assert(strpos($source, '$dias > 365') !== false, "Must bound maximum sliding days to 365.");

// 4. Verify extorno detection handling (new_status == 6)
assert(strpos($source, '$new_status == 6') !== false, "Must detect when SATCH applies extorno (status 6).");

// 5. Verify ignore_errors stream context
assert(strpos($source, "'ignore_errors' => true") !== false, "Must use ignore_errors to parse HTTP 400 payloads.");

echo "PASS: api/reconciliar_extornos.php contracts verified successfully.\n";
