<?php
// SQL/controller contracts using doubles; no live PostgreSQL connection.
class Conectar {
    public static $db;
    public function conexion() { return self::$db; }
    public function set_names() {}
}
class Bitacora {}
require_once __DIR__ . '/../models/Area.php';
function check($condition, $message) {
    if (!$condition) throw new RuntimeException($message);
}
class TributosStatement {
    public $bindings = [];
    private $db;
    public function __construct($db) { $this->db = $db; }
    public function bindValue($key, $value, $type = null) { $this->bindings[$key] = [$value, $type]; }
    public function execute() { return true; }
    public function fetchAll($mode = null) { return $this->db->rows; }
}
class TributosConnection {
    public $queries = [], $rows = [], $statement;
    public function prepare($sql) {
        $this->queries[] = $sql;
        return $this->statement = new TributosStatement($this);
    }
}
function endpoint($op, $post) {
    $_GET = ['op' => $op]; $_POST = $post;
    $source = file_get_contents(__DIR__ . '/../controller/area.php');
    $source = preg_replace('/require_once\([^;]+;/', '', $source);
    ob_start();
    try { eval('?>' . $source); return ob_get_contents(); }
    finally { ob_end_clean(); }
}
$db = new TributosConnection(); Conectar::$db = $db;
$model = new Area();
check(method_exists($model, 'get_tributos_areas'), 'Tributos requires a document-scoped area query');
$db->rows = [['depe_id' => 4, 'depe_denominacion' => 'Area without taxes']];
check($model->get_tributos_areas('7') === $db->rows, 'Return eligible area rows unchanged');
$sql = end($db->queries);
foreach (["a.depe_estado = 'A'", 'EXISTS', 'sc_giros.tm_procedimiento p', 'p.proced_area = a.depe_id', 'p.proced_tupa = ?', 'p.est = 1', 'ORDER BY a.depe_denominacion'] as $clause) {
    check(strpos($sql, $clause) !== false, 'Missing document-scoped eligibility: ' . $clause);
}
// EXISTS gives one dependency row even when several active procedures qualify.
check(stripos($sql, 'JOIN') === false, 'Existence filtering must not multiply area rows');
foreach (['tm_tasa', 'tasaproced', 'tm_tupa', 'nior_id', 'td_areausu', 'NOT IN'] as $restriction) {
    check(stripos($sql, $restriction) === false, 'Do not import tax, lifecycle or giro restrictions: ' . $restriction);
}
check($db->statement->bindings === [1 => ['7', PDO::PARAM_INT]], 'Document ID is bound as an integer');
foreach ([null, '', 0, '0', -1, '-1', 'bad', '7 OR 1=1', [], true, 7.5] as $invalid) {
    $before = count($db->queries);
    check($model->get_tributos_areas($invalid) === [], 'Invalid document must return no areas');
    check(count($db->queries) === $before, 'Invalid document must not query');
}
$db->rows = [['depe_id' => 4, 'depe_denominacion' => "Area <script> & 'quoted'"]];
$html = endpoint('combo_tributos', ['tupa_id' => '7']);
check(strpos($html, "<option value=''>Seleccione</option>") !== false, 'Always include a blank placeholder');
check(strpos($html, "value='4'") !== false, 'Render returned area ID');
check(strpos($html, '&lt;script&gt; &amp; &#039;quoted&#039;') !== false, 'Escape dependency labels');
check($db->statement->bindings[1] === ['7', PDO::PARAM_INT], 'Endpoint passes selected document to model');
$db->rows = [];
check(endpoint('combo_tributos', ['tupa_id' => '7']) === "<option value=''>Seleccione</option>", 'Empty result still clears old choices');
foreach ([[], ['tupa_id' => ''], ['tupa_id' => []], ['tupa_id' => 'bad']] as $post) {
    $before = count($db->queries);
    check(endpoint('combo_tributos', $post) === "<option value=''>Seleccione</option>", 'Missing/invalid document returns placeholder');
    check(count($db->queries) === $before, 'Malformed endpoint input must not query');
}
$db->rows = [['depe_id' => 4, 'depe_denominacion' => 'General area']];
check(strpos(endpoint('combo', []), 'General area') !== false, 'General combo remains available without document');
check(end($db->queries) === "SELECT depe_id,depe_denominacion, depe_codigo, depe_representante FROM tb_dependencia where depe_estado = 'A' order by depe_denominacion", 'General area query remains unchanged');
echo "Tributos area contracts passed (database doubles only).\n";
