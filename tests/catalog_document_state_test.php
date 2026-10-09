<?php
// Deterministic application-contract tests; no PostgreSQL connection is made.
class Conectar {
    public static $db;
    public function conexion() { return self::$db; }
    public function set_names() {}
}
class Bitacora {
    public static $writes = 0;
    public function get_max_id() { return [['bita_id' => 1]]; }
    public function update_bitacora($id) { self::$writes++; }
    public function update_bitacora_grupo($id, $previous) { self::$writes++; }
}
require_once __DIR__ . '/../models/Tupa.php';
require_once __DIR__ . '/../models/Procedimiento.php';
function check($condition, $message) {
    if (!$condition) throw new RuntimeException($message);
}
class CatalogStatement {
    public $bindings = [];
    private $db;
    private $sql;
    public function __construct($db, $sql) { $this->db = $db; $this->sql = $sql; }
    public function bindValue($key, $value, $type = null) { $this->bindings[$key] = $value; }
    public function execute() { if ($this->db->fail) throw new RuntimeException('Simulated failure'); return true; }
    public function fetchAll($mode = null) { return $this->db->rows; }
    public function rowCount() {
        // Models a conditional insert only, not PostgreSQL semantics.
        return strpos($this->sql, 'est IN (2,3)') !== false
            ? ($this->db->exists && in_array($this->db->state, [2,3], true) ? 1 : 0) : 1;
    }
}
class CatalogConnection {
    public $queries = [];
    public $rows = [];
    public $state = 1;
    public $fail = false;
    public $exists = true;
    public $statement;
    public function prepare($sql) { $this->queries[] = $sql; return $this->statement = new CatalogStatement($this, $sql); }
}
function endpoint($file, $op, $post) {
    $_GET = ['op' => $op]; $_POST = $post; $_SESSION = ['usua_id_SIGODT' => 7];
    $source = file_get_contents(__DIR__ . '/../controller/' . $file . '.php');
    // Remove dependency loading to avoid reading configuration or opening a connection.
    $source = preg_replace('/require_once\([^;]+;/', '', $source);
    ob_start();
    try { eval('?>' . $source); return ob_get_contents(); }
    finally { ob_end_clean(); }
}
$db = new CatalogConnection(); Conectar::$db = $db;
$model = new tupa();
$model->insert_tupa('New', '2026', 'TUPA');
check(strpos(end($db->queries), 'VALUES (?, ?, 3, ?)') !== false, 'New documents must start in progress');
foreach (['get_docs', 'get_tupa', 'get_tupa_tusnet'] as $method) {
    $model->$method();
    check(preg_match('/est IN \(1,\s*2,\s*3\)/', end($db->queries)), "$method must preserve inactive and include in-progress documents");
}
$model->get_tupa_id(7);
check(preg_match('/est IN \(1,\s*2,\s*3\)/', end($db->queries)), 'Maintenance getter includes in-progress');
$model->desactivar_tupa(7);
check(strpos(end($db->queries), 'est = 1') !== false, 'Deactivation remains inactive');
$model->delete_tupa(7);
check(strpos(end($db->queries), 'est = 0') !== false, 'Deletion remains zero');
$model->activar_tupa(7);
check(strpos(end($db->queries), 'est = 2') !== false, 'Activation remains vigente');
$post = ['proced_id' => '', 'tupa_id' => '7', 'area_id' => '4', 'proced_cod' => 'X', 'proced_nom' => 'Test', 'tipo_campo' => '2', 'tipo_administrado' => 'C', 'proced_tipoindvasc' => 'C', 'est' => 3];
foreach ([0,1,2,3,99,null] as $state) {
    $db->state = $state; Bitacora::$writes = 0;
    $response = json_decode(endpoint('procedimiento', 'guardaryeditar', $post), true);
    $allowed = in_array($state, [2,3], true);
    check(($response['success'] ?? null) === $allowed, 'Creation must use stored state, not posted state');
    check(Bitacora::$writes === ($allowed ? 1 : 0), 'Rejected creation must not audit success');
}
check($db->statement->bindings === [1 => '4', 2 => 'X', 3 => 'Test', 4 => '2', 5 => 'C', 6 => 'C', 7 => '7'], 'Conditional insert preserves parameter ordering');
$db->state = 3; $db->exists = false;
check(json_decode(endpoint('procedimiento', 'guardaryeditar', $post), true)['success'] === false, 'Missing stored document rejected');
$db->exists = true;
$db->fail = true;
check(json_decode(endpoint('procedimiento', 'guardaryeditar', $post), true)['success'] === false, 'Creation failure becomes application error');
$db->fail = false;
foreach (['', '0', '-1', 'abc', '7 OR 1=1', []] as $id) {
    $post['tupa_id'] = $id;
    $response = json_decode(endpoint('procedimiento', 'guardaryeditar', $post), true);
    check(($response['success'] ?? null) === false, 'Invalid document ID rejected');
}
$post['proced_id'] = '8'; $db->state = 1;
$response = json_decode(endpoint('procedimiento', 'guardaryeditar', $post), true);
check($response['success'] === true, 'Historical update remains available');
foreach ([99, '99', 0, -1, null, 'bad'] as $id) {
    $db->rows = [['duplicar_tupa' => $id]]; Bitacora::$writes = 0;
    $response = json_decode(endpoint('tupa', 'duplicar', ['tupa_id' => '7']), true);
    $allowed = $id === 99 || $id === '99';
    check(($response['success'] ?? null) === $allowed, 'Duplication requires positive returned ID');
    check(Bitacora::$writes === ($allowed ? 1 : 0), 'Invalid duplication result must not audit success');
}
$db->rows = []; check(json_decode(endpoint('tupa', 'duplicar', ['tupa_id' => '7']), true)['success'] === false, 'Empty duplication result rejected');
$db->fail = true;
check(json_decode(endpoint('tupa', 'duplicar', ['tupa_id' => '7']), true)['success'] === false, 'Database failure becomes duplication error');
$db->fail = false; $db->rows = [['duplicar_tupa' => 99]];
check(json_decode(endpoint('tupa', 'duplicar', ['tupa_id' => 'invalid']), true)['success'] === false, 'Invalid duplication source rejected');
$db->rows = [['tupa_id' => 7, 'tupa_nom' => 'Test', 'est' => 3, 'tupa_block' => '1']];
$html = endpoint('tupa', 'combo_ti', []);
check(strpos($html, "data-estado='1'") !== false && strpos($html, "data-document-state='3'") !== false && strpos($html, 'En Proceso') !== false, 'Maintenance selector keeps block distinct from lifecycle');
echo "Catalog document application contracts passed (database doubles only).\n";
