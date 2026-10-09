<?php
// Deterministic SQL/controller contracts using doubles only; no database integration.
class Conectar {
    public static $db;
    public function conexion() { return self::$db; }
    public function set_names() {}
}
class Bitacora {
    public static $calls = 0;
    public function get_max_id() { self::$calls++; return [['bita_id' => 1]]; }
    public function update_bitacora_grupo($id, $previous) { self::$calls++; }
}
require_once __DIR__ . '/../models/Procedimiento.php';
require_once __DIR__ . '/../models/Tupa.php';
function check($ok, $message) { if (!$ok) throw new RuntimeException($message); }
class RegistrationStatement {
    public $bindings = [];
    private $db, $sql;
    public function __construct($db, $sql) { $this->db = $db; $this->sql = $sql; }
    private function allowed() {
        // A fixture oracle, not a PostgreSQL SQL interpreter.
        if (strpos($this->sql, 'tau.pers_id = :person') === false) return $this->db->eligible;
        $f = $this->db->fixture;
        return $this->db->eligible && $f['area_state'] === 'A' && $f['level'] !== 5
            && !in_array($f['area'], [2,200], true) && $f['procedure_state'] === 1
            && $f['document_state'] === 2 && in_array($f['type'], ['TUPA','TUSNE'], true)
            && (($this->bindings[':role'] ?? null) == 9 || ($f['assigned'] && ($this->bindings[':person'] ?? null) == 7));
    }
    public function bindValue($key, $value, $type = null) { $this->bindings[$key] = $value; }
    public function bindParam($key, &$value, $type = null) { $this->bindings[$key] = $value; }
    public function execute() { return true; }
    public function fetchAll($mode = null) { return $this->allowed() ? [['proced_id' => 8, 'depe_id' => 4, 'depe_denominacion' => 'Area', 'proced_cod' => 'X', 'proced_nom' => 'Test', 'proced_tipoindvasc' => 'C']] : []; }
    public function fetch($mode = null) { return $this->db->eligible ? ['proced_id' => 8] : false; }
    public function fetchColumn() { return $this->db->insertAllowed && $this->allowed() ? 11 : false; }
}
class RegistrationConnection {
    public $queries = [], $statements = [];
    public $eligible = true, $insertAllowed = true;
    public $fixture = ['area_state' => 'A', 'level' => 3, 'area' => 4, 'procedure_state' => 1, 'document_state' => 2, 'type' => 'TUPA', 'assigned' => true];
    public function prepare($sql) { $this->queries[] = $sql; return $this->statements[] = new RegistrationStatement($this, $sql); }
}
function endpoint($op, $post, $session) {
    $_GET = ['op' => $op]; $_POST = $post; $_SESSION = $session;
    $source = file_get_contents(__DIR__ . '/../controller/procedimiento.php');
    $source = preg_replace('/require_once\([^;]+;/', '', $source);
    $source = str_replace('exit;', 'return;', $source);
    ob_start();
    try { eval('?>' . $source); return ob_get_contents(); }
    finally { ob_end_clean(); }
}
$db = new RegistrationConnection(); Conectar::$db = $db;
$model = new Procedimiento();
check(method_exists($model, 'get_registration_options'), 'Shared registration eligibility query required');
foreach ([9, 2] as $role) {
    $model->get_registration_options(7, $role, 4, 8);
    $sql = end($db->queries);
    foreach (["a.depe_estado = 'A'", 'a.nior_id != 5', 'a.depe_id NOT IN (2,200)', 'p.est = 1', 'd.est = 2', "d.tipo_doc IN ('TUPA','TUSNE')", 'tau.est = 1', 'tau.pers_id = :person', ':role = 9'] as $clause) {
        check(strpos($sql, $clause) !== false, 'Missing eligibility clause: ' . $clause);
    }
    check(end($db->statements)->bindings[':person'] === 7, 'Assignment uses session person ID');
}
$base = $db->fixture;
foreach (['area_state' => ['I'], 'level' => [5], 'area' => [2,200], 'procedure_state' => [0], 'document_state' => [0,1,3], 'type' => ['OTHER'], 'assigned' => [false]] as $field => $values) {
    foreach ($values as $value) {
        $db->fixture = $base; $db->fixture[$field] = $value;
        foreach ([2,9] as $role) {
            $expected = $field === 'assigned' && $role === 9;
            check((count($model->get_registration_options(7, $role, 4, 8)) > 0) === $expected, 'Fixture eligibility matrix: ' . $field);
        }
    }
}
$db->fixture = $base; $db->fixture['type'] = 'TUSNE';
check(count($model->get_registration_options(7, 9, 4, 8)) === 1, 'Vigente TUSNE offered');
check($model->get_registration_options(99, 2, 4, 8) === [], 'Assignment must belong to session person');
$db->fixture = $base;
$tupa = new tupa(); $tupa->get_registration_documents();
check(strpos(end($db->queries), "est = 2 AND tipo_doc IN ('TUPA','TUSNE')") !== false, 'Dedicated document selector only vigente');
$model->get_proced_area_tupa(4, 2);
check(strpos(end($db->queries), 'tau.pers_id') === false && strpos(end($db->queries), 'd.est = 2') === false, 'Maintenance combo remains unchanged');
foreach ([null, '', 0, -1, 'bad', [], '4 OR 1=1'] as $invalid) {
    $before = count($db->queries);
    check($model->get_registration_options(7, 2, $invalid, 8) === [], 'Malformed area denied');
    check(count($db->queries) === $before, 'Malformed area must not query');
}
$session = ['usua_id_SIGODT' => 7, 'rol_id_SIGODT' => 2];
$post = ['ciud_id' => 6, 'proced_id' => 8, 'area_id' => 4, 'rol_id_SIGODT' => 9, 'usua_id_SIGODT' => 99];
foreach ([[], ['usua_id_SIGODT' => 7], ['usua_id_SIGODT' => [], 'rol_id_SIGODT' => 9]] as $badSession) {
    Bitacora::$calls = 0;
    check(json_decode(endpoint('abrirproced', $post, $badSession), true)['success'] === false, 'Missing/malformed session denied');
    check(Bitacora::$calls === 0, 'Denied session has no audit effects');
}
foreach (['area_id', 'proced_id', 'ciud_id'] as $key) {
    foreach ([null, '', 0, -1, 'bad', []] as $invalid) {
        $badPost = $post; $badPost[$key] = $invalid; Bitacora::$calls = 0;
        check(json_decode(endpoint('abrirproced', $badPost, $session), true)['success'] === false, 'Invalid registration ID denied');
        check(Bitacora::$calls === 0, 'Invalid input has no audit effects');
    }
}
$db->eligible = false; Bitacora::$calls = 0; $before = count($db->queries);
check(json_decode(endpoint('abrirproced', $post, $session), true)['success'] === false, 'Forged/ineligible procedure denied');
check(Bitacora::$calls === 0 && count($db->queries) === $before + 1, 'Denial precedes audit, insert, code and rate effects');
check(strpos(endpoint('getAreas_usu', [], $session), '<option') !== false, 'Empty areas include safe placeholder');
check(strpos(endpoint('combo_tupa_tusne', ['area_id' => 4], $session), '<option') !== false, 'Empty procedures include safe placeholder');
check(json_decode(endpoint('registration_detail', ['area_id' => 4, 'proced_id' => 8], $session), true)['procedimiento'] === false, 'Unauthorized preview denied');
$db->eligible = true; $db->insertAllowed = false; $before = count($db->queries);
check(json_decode(endpoint('abrirproced', $post, $session), true)['success'] === false, 'Eligibility lost before insert denied');
$insert = $db->queries[$before + 1];
check(strpos($insert, 'INSERT INTO sc_giros.td_procedciudadano') !== false && strpos($insert, 'SELECT') !== false && strpos($insert, 'd.est = 2') !== false, 'Insert itself must guard eligibility');
check(count($db->queries) === $before + 2, 'Failed insert has no code/rate effects');
$db->insertAllowed = true;
check(json_decode(endpoint('abrirproced', $post, $session), true)['success'] === true, 'Eligible registration succeeds');
$bindings = $db->statements[$before + 1]->bindings;
check($bindings[':person'] === 7 && $bindings[':role'] === 2, 'Posted identity/role ignored');
echo "Giro registration contracts passed (SQL/controller doubles only).\n";
