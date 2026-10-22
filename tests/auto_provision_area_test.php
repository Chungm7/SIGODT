<?php
// Deterministic regression test for user area auto-provisioning
// No live PostgreSQL connection, no network, runs entirely with doubles.

class Conectar {
    public static $db;
    public function conexion() { return self::$db; }
    public function set_names() {}
    public static function ruta() { return '/'; }
    public static function getClientIp() { return '127.0.0.1'; }
    public static function getEnv($key, $default = null) { return $default; }
}

require_once __DIR__ . '/../models/Area.php';

function check($condition, $message) {
    if (!$condition) throw new RuntimeException($message);
}

class MockStatement {
    public $bindings = [];
    private $db;
    private $sql;

    public function __construct($db, $sql) {
        $this->db = $db;
        $this->sql = $sql;
    }

    public function bindValue($key, $value, $type = null) {
        $this->bindings[$key] = [$value, $type];
    }

    public function execute() {
        if ($this->db->throwOnExecute) {
            throw new RuntimeException('DB error simulation');
        }
        return true;
    }

    public function fetch($mode = null) {
        if (strpos($this->sql, 'sc_giros.td_areausu WHERE pers_id = ? AND est = 1') !== false) {
            return $this->db->hasActiveArea ? ['1' => 1] : false;
        }
        if (strpos($this->sql, 'sc_giros.td_areausu WHERE pers_id = ? AND depe_id = ?') !== false) {
            return $this->db->hasPriorAssignment ? ['1' => 1] : false;
        }
        if (strpos($this->sql, 'public.tb_dependencia WHERE depe_id = ? AND depe_estado') !== false) {
            return $this->db->dependencyActive ? ['1' => 1] : false;
        }
        if (strpos($this->sql, 'INSERT INTO sc_giros.td_areausu') !== false) {
            return ['areausu_id' => 999];
        }
        return false;
    }
}

class MockConnection {
    public $queries = [];
    public $statements = [];
    public $hasActiveArea = false;
    public $hasPriorAssignment = false;
    public $dependencyActive = true;
    public $throwOnExecute = false;

    public function prepare($sql) {
        $this->queries[] = $sql;
        $stmt = new MockStatement($this, $sql);
        $this->statements[] = $stmt;
        return $stmt;
    }
}

$db = new MockConnection();
Conectar::$db = $db;
$model = new Area();

// 1. Method existence
check(method_exists($model, 'sincronizar_area_defecto'), 'Area model must implement sincronizar_area_defecto');

// 2. Input validation: invalid IDs must fail-safe without issuing any queries
foreach ([
    [null, 132],
    [1257, null],
    ['', 132],
    [1257, ''],
    [0, 132],
    [1257, 0],
    [-5, 132],
    [1257, -10],
    ['abc', 132],
    [1257, 'xyz'],
    [[], 132],
    [1257, []],
    [1.5, 132],
    [1257, 2.8]
] as $invalidPair) {
    $before = count($db->queries);
    $result = $model->sincronizar_area_defecto($invalidPair[0], $invalidPair[1]);
    check($result === false, 'Invalid inputs must return false: ' . json_encode($invalidPair));
    check(count($db->queries) === $before, 'Invalid inputs must not query DB: ' . json_encode($invalidPair));
}

// 3. User already has active area: must NOT insert
$db->queries = [];
$db->hasActiveArea = true;
$db->hasPriorAssignment = false;
$db->dependencyActive = true;
$result = $model->sincronizar_area_defecto(1257, 132);
check($result === false, 'User with existing active area must not be re-provisioned');
check(count($db->queries) === 1, 'Should stop after checking active areas');
check(strpos($db->queries[0], 'est = 1') !== false, 'Must check for active assignments with est = 1');

// 4. User has prior assignment for this depe_id (e.g. revoked by admin): must NOT reactivate
$db->queries = [];
$db->hasActiveArea = false;
$db->hasPriorAssignment = true;
$db->dependencyActive = true;
$result = $model->sincronizar_area_defecto(1257, 132);
check($result === false, 'User with prior assignment must not be reactivated automatically');
check(count($db->queries) === 2, 'Should check active area then prior assignment for that depe');

// 5. Dependency is inactive in public.tb_dependencia: must NOT insert
$db->queries = [];
$db->hasActiveArea = false;
$db->hasPriorAssignment = false;
$db->dependencyActive = false;
$result = $model->sincronizar_area_defecto(1257, 9999);
check($result === false, 'Inactive or non-existent dependency must not be assigned');
check(count($db->queries) === 3, 'Should check active, prior, and dependency state');

// 6. Brand new user: must insert into sc_giros.td_areausu with est = 1
$db->queries = [];
$db->statements = [];
$db->hasActiveArea = false;
$db->hasPriorAssignment = false;
$db->dependencyActive = true;
$result = $model->sincronizar_area_defecto('1257', '132');
check($result !== false && is_array($result) && ($result['areausu_id'] ?? null) === 999, 'Brand new user must be auto-provisioned');
check(count($db->queries) === 4, 'Must perform 4 steps: active check, prior check, depe check, insert');
$insertSql = $db->queries[3];
check(strpos($insertSql, 'INSERT INTO sc_giros.td_areausu') !== false, 'Must insert into sc_giros.td_areausu');
check(strpos($insertSql, 'VALUES (?, ?, 1)') !== false, 'Must insert with active state est = 1');

// Verify integer parameter bindings on insert
$insertStmt = end($db->statements);
check($insertStmt->bindings[1] === [1257, PDO::PARAM_INT], 'pers_id must be bound as integer');
check($insertStmt->bindings[2] === [132, PDO::PARAM_INT], 'depe_id must be bound as integer');

// 7. Fail-safe on DB error: must not throw, return false
$db->throwOnExecute = true;
$result = $model->sincronizar_area_defecto(1257, 132);
check($result === false, 'DB error must fail-safe and return false');
$db->throwOnExecute = false;

echo "All auto-provisioning unit tests passed successfully!\n";
