<?php
// Dependency-free contract tests: no configuration or live database is loaded.
class conectar {
    public static $db;
    public function conexion() { return self::$db; }
}
require __DIR__ . '/../models/OrdenGiro.php';
function check($condition, $message) {
    if (!$condition) { throw new RuntimeException($message); }
}
class StatementDouble {
    public $bindings = [];
    private $rows;
    public function __construct($rows) { $this->rows = $rows; }
    public function bindValue($key, $value, $type = null) { $this->bindings[$key] = [$value, $type]; }
    public function execute() { return true; }
    public function fetchAll($mode = null) { return $this->rows; }
}
class ConnectionDouble {
    public $sql;
    public $statement;
    public function __construct($rows) { $this->statement = new StatementDouble($rows); }
    public function prepare($sql) { $this->sql = $sql; return $this->statement; }
}
$db = new ConnectionDouble([
    ['total' => '1', 'page' => '1', 'entity_type' => 'empresa', 'entity_key' => 'ruc:00123', 'nombre' => 'Alias', 'ordenes' => '3']
]);
conectar::$db = $db;
$model = new Ordengiro();
$result = $model->buscar_entidades_nombre('  50%_!  ', 99, 10);
check($db->statement->bindings[':search'] === ['%50!%!_!!%', PDO::PARAM_STR], 'Literal substring escaping and trimming');
check($result['total'] === 1 && $result['page'] === 1 && $result['limit'] === 10, 'Database-clamped page metadata');
check($result['data'][0]['entity_key'] === 'ruc:00123', 'RUC text must preserve leading zeros');
check(!isset($result['data'][0]['page']), 'Metadata is not exposed as entity data');
check(strpos($db->sql, 'ILIKE :search ESCAPE') !== false, 'Parameterized PostgreSQL search');
check(strpos($db->sql, 'pairs AS') !== false && strpos($db->sql, 'SELECT DISTINCT entity_type, entity_key, ogciud_id') !== false, 'Shared distinct membership');
check(strpos($db->sql, 'ttc.tasatciud_procedciud = tt.procedciudadano_id') !== false, 'Correct owner association');
check(strpos($db->sql, 'MIN(nombre)') !== false, 'Deterministic display label');
check(!preg_match('/\b(?:og|tt|gt|ttc)\.est\s*(?:=|IN)/i', $db->sql), 'No state exclusion');
check(strpos($db->sql, 'empresa_razon_social') !== false && strpos($db->sql, 'empresa_ruc') !== false, 'Historical aliases and RUC available');
$db = new ConnectionDouble([['total' => '0', 'page' => '1', 'ogciud_id' => null]]);
conectar::$db = $db;
$result = $model->historial_entidad('empresa', 'ruc:00123', 100, 10);
check($result['data'] === [] && $result['total'] === 0 && $result['page'] === 1, 'Empty sentinel removed and page bounded');
check($db->statement->bindings[':key'][0] === 'ruc:00123', 'Typed identity bound, not interpolated');
check(strpos($db->sql, 'GROUP BY girot_giro') !== false && strpos($db->sql, 'SUM(importe)') !== false, 'Amounts grouped before identity join');
check(strpos($db->sql, 'fechacrea DESC NULLS LAST, ogciud_id DESC') !== false, 'Stable chronological order');
foreach ([['', 1, 10], ['x', 0, 10], ['x', 1, 101], [[], 1, 10]] as $args) {
    try { $model->buscar_entidades_nombre(...$args); throw new RuntimeException('Invalid search accepted'); }
    catch (InvalidArgumentException $expected) {}
}
foreach ([['empresa', 'ruc:'], ['ciudadano', 'ruc:1'], ['empresa', 'id:0'], ['staff', '1']] as $args) {
    try { $model->historial_entidad(...$args); throw new RuntimeException('Invalid key accepted'); }
    catch (InvalidArgumentException $expected) {}
}
// Exercise the actual controller rejection boundary in isolated PHP processes.
function endpoint($session, $post, $operation = 'buscar_entidades_nombre') {
    // An in-memory session handler prevents the test from creating session files.
    $sessionSetup = 'session_set_save_handler(function($p,$n){return true;},function(){return true;},function($id){return "";},function($id,$data){return true;},function($id){return true;},function($time){return 0;}); session_start(); ';
    $code = $sessionSetup . '$_SESSION=' . var_export($session, true) . '; $_GET=["op"=>' . var_export($operation, true) . ']; $_POST=' . var_export($post, true) . '; register_shutdown_function(function(){echo "\\nHTTP:".http_response_code();}); include ' . var_export(__DIR__ . '/../controller/ordengiro.php', true) . ';';
    return shell_exec(escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg($code));
}
$output = endpoint([], ['search' => 'Ana']);
check(strpos($output, 'HTTP:401') !== false && strpos($output, 'Inicie sesi') !== false, 'Unauthenticated controller rejects before config/DB');
$output = endpoint(['usua_id_SIGODT' => 1], ['search' => []]);
check(strpos($output, 'HTTP:400') !== false, 'Malformed controller input rejected before config/DB');
foreach ([
    ['entity_type' => 'ciudadano', 'entity_key' => 'ruc:123'],
    ['entity_type' => 'empresa', 'entity_key' => 'sin:0'],
    ['entity_type' => 'empresa', 'entity_key' => []],
    ['entity_type' => 'empresa', 'entity_key' => 'ruc:00123', 'page' => 0],
    ['entity_type' => 'empresa', 'entity_key' => 'ruc:00123', 'limit' => 101]
] as $input) {
    check(strpos(endpoint(['usua_id_SIGODT' => 1], $input, 'historial_entidad'), 'HTTP:400') !== false, 'Invalid history request rejected before config/DB');
}
foreach (['id:9', 'sin:7', 'ruc:00123'] as $key) {
    check(Ordengiro::validar_nombre_input('historial_entidad', ['entity_type' => 'empresa', 'entity_key' => $key])[1] === $key, 'Company fallback keys preserved');
}
// Fixture rows test the public response contract (the double does NOT execute SQL).
$fixtureRows = [];
foreach (range(0, 7) as $state) {
    $fixtureRows[] = ['total' => '8', 'page' => '1', 'ogciud_id' => (string)(100 - $state), 'orden_est' => $state, 'importe' => '25.00'];
}
$db = new ConnectionDouble($fixtureRows);
conectar::$db = $db;
$result = $model->historial_entidad('ciudadano', '12', 1, 10);
check(count($result['data']) === 8 && array_column($result['data'], 'orden_est') === range(0, 7), 'All known and unknown states returned unchanged');
check($db->statement->bindings[':page'] === [1, PDO::PARAM_INT] && $db->statement->bindings[':limit'] === [10, PDO::PARAM_INT], 'Pagination integers are bound');
check(array_column($result['data'], 'importe') === array_fill(0, 8, '25.00'), 'Decimal amounts preserved, not recomputed in PHP');
echo "PASS: model binding/pagination/query contracts, fixture responses and actual controller rejection (not PostgreSQL execution)\n";
echo 'Runtime: PHP ' . PHP_VERSION . '; PDO drivers: ' . implode(', ', PDO::getAvailableDrivers()) . "\n";
