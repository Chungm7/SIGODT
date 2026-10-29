<?php
// Dependency-free S1 tests for multi-area giro visibility in Consultas (OrdenGiro).
class conectar {
    public static $db;
    public function conexion() { return self::$db; }
    public function set_names() {}
    public static function getEnv($key, $default = null) { return $default; }
}
if (!class_exists('Conectar', false)) {
    class_alias('conectar', 'Conectar');
}
class Bitacora {}

require_once __DIR__ . '/../models/OrdenGiro.php';

function check($condition, $message) {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

class AreaScopedStatementDouble {
    public $bindings = [];
    public $executedParams = null;
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function bindValue($key, $value, $type = null) {
        $this->bindings[$key] = [$value, $type];
    }

    public function bindParam($key, &$value, $type = null) {
        $this->bindings[$key] = [$value, $type];
    }

    public function execute($params = null) {
        if (is_array($params)) {
            $this->executedParams = $params;
        }
        return true;
    }

    public function fetchAll($mode = null) {
        return $this->db->rows;
    }

    public function fetch($mode = null) {
        return $this->db->rows[0] ?? false;
    }
}

class AreaScopedConnectionDouble {
    public $queries = [];
    public $statements = [];
    public $statement = null;
    public $rows = [];

    public function prepare($sql) {
        $this->queries[] = $sql;
        $stmt = new AreaScopedStatementDouble($this);
        $this->statements[] = $stmt;
        $this->statement = $stmt;
        return $stmt;
    }
}

function runOrdengiroController($op, $post, $session, $rows = []) {
    $db = new AreaScopedConnectionDouble();
    $db->rows = $rows;
    conectar::$db = $db;
    $_GET = ['op' => $op];
    $_POST = $post;
    $_SESSION = $session;

    $source = file_get_contents(__DIR__ . '/../controller/ordengiro.php');
    $source = preg_replace('/require_once\([^\n]+;/', '', $source);
    $source = preg_replace('/\bheader\([^\n]+;/', '', $source);
    $source = str_replace('exit;', 'return;', $source);

    ob_start();
    try {
        eval('?>' . $source);
        $raw = ob_get_contents();
        return ['body' => json_decode($raw, true), 'raw' => $raw, 'db' => $db];
    } finally {
        ob_end_clean();
    }
}

function assertMultiAreaSql($sql, $context) {
    check(strpos($sql, ':role = 9') !== false, $context . ': missing admin role bypass (:role = 9)');
    check(strpos($sql, 'sc_giros.td_areausu tau') !== false, $context . ': missing sc_giros.td_areausu join/subquery');
    check(strpos($sql, 'tau.depe_id = p.proced_area') !== false, $context . ': missing tau.depe_id = p.proced_area');
    check(strpos($sql, 'tau.est = 1') !== false, $context . ': missing active assignment check tau.est = 1');
    check(strpos($sql, 'tau.pers_id = :person') !== false, $context . ': missing user binding tau.pers_id = :person');
}

// 1. Unauthenticated / invalid session requests must be rejected before querying the DB.
$invalidSessions = [
    [],
    ['usua_id_SIGODT' => 0],
    ['usua_id_SIGODT' => -3],
    ['usua_id_SIGODT' => []],
    ['usua_id_SIGODT' => '1 OR 1=1'],
];

$operations = [
    ['buscar_entidades_nombre', ['search' => 'Empresa', 'page' => 1, 'limit' => 10]],
    ['historial_entidad', ['entity_type' => 'ciudadano', 'entity_key' => '12', 'page' => 1, 'limit' => 10]],
    ['get_ordenes_giro', ['documento' => '12345678']],
    ['get_ordenes_mes', ['search' => 'Licencia', 'page' => 1, 'limit' => 10]],
    ['get_orden_giro', ['ogciud_id' => '000001-2026']],
    ['consultarOrden', ['ordengiro_id' => '000001-2026']],
    ['getOrdenes', []],
    ['getTasasOrdenes', []],
    ['getTasasSemana', []],
    ['getgirosUsuarios', []],
    ['getgirosUsuarios_totales', []],
    ['getgirosUsuariosAreas', []],
    ['gettasasprocedimientos', ['area_id' => 4, 'tupa_id' => 1, 'proced_id' => 8]],
];

foreach ($invalidSessions as $badSession) {
    foreach ($operations as [$op, $post]) {
        $res = runOrdengiroController($op, $post, $badSession, [['ogciud_id' => '000001-2026']]);
        check(count($res['db']->queries) === 0, "S1 unauthenticated ($op): must not query DB for session " . json_encode($badSession));
        if ($op === 'get_ordenes_giro') {
            check($res['body'] === [], "S1 unauthenticated ($op): expected empty JSON array");
        } elseif ($op === 'get_ordenes_mes') {
            check($res['body']['data'] === [] && $res['body']['total'] === 0, "S1 unauthenticated ($op): expected empty paginated payload");
        } elseif ($op === 'get_orden_giro') {
            check(($res['body']['status'] ?? '') === 'error', "S1 unauthenticated ($op): expected status=error");
        } else {
            check(isset($res['body']['error']), "S1 unauthenticated ($op): expected error key");
        }
    }
}
echo "PASS S1: unauthenticated/invalid sessions rejected across all Ordengiro operations without touching DB\n";

// 2. Non-admin multi-area scoping and role=9 admin bypass across controller & model operations.
$nonAdminSession = ['usua_id_SIGODT' => '15', 'rol_id_SIGODT' => '2'];
$adminSession = ['usua_id_SIGODT' => 1, 'rol_id_SIGODT' => 9];

// 2a. buscar_entidades_nombre & historial_entidad
foreach ([[15, 2], [1, 9]] as [$expectedPers, $expectedRole]) {
    $session = ['usua_id_SIGODT' => $expectedPers, 'rol_id_SIGODT' => $expectedRole];

    $resSearch = runOrdengiroController(
        'buscar_entidades_nombre',
        ['search' => 'Alpha', 'page' => 1, 'limit' => 10, 'usua_id_SIGODT' => 999, 'rol_id_SIGODT' => 9],
        $session,
        [['total' => '1', 'page' => '1', 'entity_type' => 'ciudadano', 'entity_key' => '12', 'nombre' => 'Alpha', 'documento' => '12345678', 'ordenes' => '1']]
    );
    check(count($resSearch['db']->queries) === 1, 'buscar_entidades_nombre executes one snapshot query');
    $sqlSearch = $resSearch['db']->queries[0];
    assertMultiAreaSql($sqlSearch, 'buscar_entidades_nombre');
    check(strpos($sqlSearch, 'WHERE (tt.ciud_id IN') !== false, 'buscar_entidades_nombre wraps OR filter in parentheses before area AND guard');
    check($resSearch['db']->statement->bindings[':person'] === [$expectedPers, PDO::PARAM_INT], 'buscar_entidades_nombre binds session person int');
    check($resSearch['db']->statement->bindings[':role'] === [$expectedRole, PDO::PARAM_INT], 'buscar_entidades_nombre binds session role int');

    $resHist = runOrdengiroController(
        'historial_entidad',
        ['entity_type' => 'ciudadano', 'entity_key' => '12', 'page' => 1, 'limit' => 10],
        $session,
        [['total' => '1', 'page' => '1', 'ogciud_id' => '000001-2026', 'orden_est' => 1, 'importe' => '10.00']]
    );
    check(count($resHist['db']->queries) === 1, 'historial_entidad executes one snapshot query');
    $sqlHist = $resHist['db']->queries[0];
    assertMultiAreaSql($sqlHist, 'historial_entidad');
    check($resHist['db']->statement->bindings[':person'] === [$expectedPers, PDO::PARAM_INT], 'historial_entidad binds session person int');
    check($resHist['db']->statement->bindings[':role'] === [$expectedRole, PDO::PARAM_INT], 'historial_entidad binds session role int');
}
echo "PASS S1: buscar_entidades_nombre and historial_entidad enforce multi-area scoping and role=9 bypass\n";

// 2b. get_ordenes_giro (consultar_documento)
$resDoc = runOrdengiroController(
    'get_ordenes_giro',
    ['documento' => '44556677', 'rol_id_SIGODT' => 9],
    $nonAdminSession,
    [['ogciud_id' => '000005-2026', 'proced_nom' => 'Licencia']]
);
check(count($resDoc['db']->queries) === 1, 'get_ordenes_giro executes query');
$sqlDoc = $resDoc['db']->queries[0];
assertMultiAreaSql($sqlDoc, 'get_ordenes_giro');
check((bool)preg_match('/\(\s*c\.ciud_numero_documento\s*=\s*:documento\s+OR\s+empr\.empr_ruc\s*=\s*:documento\s*\)\s*AND/i', $sqlDoc), 'get_ordenes_giro parenthesizes document/RUC OR filter');
check($resDoc['db']->statement->bindings[':person'] === [15, PDO::PARAM_INT], 'get_ordenes_giro binds non-admin person');
check($resDoc['db']->statement->bindings[':role'] === [2, PDO::PARAM_INT], 'get_ordenes_giro binds non-admin role');

$resDocAdmin = runOrdengiroController('get_ordenes_giro', ['documento' => '44556677'], $adminSession, []);
check($resDocAdmin['db']->statement->bindings[':person'] === [1, PDO::PARAM_INT] && $resDocAdmin['db']->statement->bindings[':role'] === [9, PDO::PARAM_INT], 'get_ordenes_giro binds admin role=9');
echo "PASS S1: get_ordenes_giro enforces multi-area scoping and parenthesized document/RUC filter\n";

// 2c. get_ordenes_mes & get_total_ordenes_mes (consultar_mes)
$resMes = runOrdengiroController(
    'get_ordenes_mes',
    ['search' => 'Permiso', 'page' => 2, 'limit' => 5],
    $nonAdminSession,
    [['ogciud_id' => '000009-2026', 'total' => 1]]
);
check(count($resMes['db']->queries) === 2, 'get_ordenes_mes runs list and count queries');
foreach ($resMes['db']->queries as $idx => $sqlMes) {
    assertMultiAreaSql($sqlMes, 'get_ordenes_mes query #' . ($idx + 1));
    $stmtMes = $resMes['db']->statements[$idx];
    check($stmtMes->bindings[':person'] === [15, PDO::PARAM_INT], 'get_ordenes_mes binds person on query #' . ($idx + 1));
    check($stmtMes->bindings[':role'] === [2, PDO::PARAM_INT], 'get_ordenes_mes binds role on query #' . ($idx + 1));
}
echo "PASS S1: get_ordenes_mes and get_total_ordenes_mes enforce multi-area scoping\n";

// 2d. get_orden_giro / get_orden_giro_by_id (consultar_og)
$resOg = runOrdengiroController(
    'get_orden_giro',
    ['ogciud_id' => '000010-2026'],
    $nonAdminSession,
    [] // Empty result when order belongs to an unauthorized area
);
check(count($resOg['db']->queries) === 1, 'get_orden_giro queries with area restriction');
assertMultiAreaSql($resOg['db']->queries[0], 'get_orden_giro_by_id');
check($resOg['db']->statement->bindings[':person'] === [15, PDO::PARAM_INT], 'get_orden_giro_by_id binds person');
check($resOg['db']->statement->bindings[':role'] === [2, PDO::PARAM_INT], 'get_orden_giro_by_id binds role');
check($resOg['body']['status'] === 'error', 'Unauthorized order returns status=error before fetching PDF');
echo "PASS S1: get_orden_giro_by_id enforces multi-area scoping\n";

// 2e. Legacy consultation operations in controller/ordengiro.php & models/OrdenGiro.php
$legacyOps = [
    ['consultarOrden', ['ordengiro_id' => '000001-2026']],
    ['getOrdenes', []],
    ['getTasasOrdenes', []],
    ['getTasasSemana', []],
    ['getgirosUsuarios', []],
    ['getgirosUsuarios_totales', []],
    ['getgirosUsuariosAreas', []],
    ['gettasasprocedimientos', ['area_id' => 4, 'tupa_id' => 1, 'proced_id' => 8]],
];

foreach ($legacyOps as [$op, $post]) {
    $resLegacy = runOrdengiroController($op, $post, $nonAdminSession, []);
    check(count($resLegacy['db']->queries) === 1, "Legacy operation $op executes one query");
    $sqlLegacy = $resLegacy['db']->queries[0];
    assertMultiAreaSql($sqlLegacy, "Legacy $op");
    check($resLegacy['db']->statement->bindings[':person'] === [15, PDO::PARAM_INT], "Legacy $op binds person");
    check($resLegacy['db']->statement->bindings[':role'] === [2, PDO::PARAM_INT], "Legacy $op binds role");
}
echo "PASS S1: all legacy consultation operations enforce multi-area scoping\n";
