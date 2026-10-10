<?php
// Application and SQL-shape contracts only; doubles do not execute PostgreSQL.
class Conectar {
    public static $db;
    public function conexion() { return self::$db; }
    public function set_names() {}
}
class Bitacora {}
require_once __DIR__ . '/../models/Procedimiento.php';
function check($condition, $message) {
    if (!$condition) throw new RuntimeException($message);
}
class OperationalStatement {
    public $bindings = [];
    private $db;
    public function __construct($db) { $this->db = $db; }
    public function bindValue($key, $value, $type = null) { $this->bindings[$key] = [$value, $type]; }
    public function execute() { return true; }
    public function fetchAll($mode = null) { return $this->db->rows; }
}
class OperationalConnection {
    public $queries = [], $rows = [], $statement;
    public function prepare($sql) {
        $this->queries[] = $sql;
        return $this->statement = new OperationalStatement($this);
    }
}
function endpoint($post, $session) {
    $_GET = ['op' => 'listar_proced_ciudadano_usuario']; $_POST = $post; $_SESSION = $session;
    $source = file_get_contents(__DIR__ . '/../controller/procedimiento.php');
    $source = preg_replace('/require_once\([^;]+;/', '', $source);
    $source = str_replace('exit;', 'return;', $source);
    ob_start();
    try { eval('?>' . $source); return json_decode(ob_get_contents(), true); }
    finally { ob_end_clean(); }
}
$db = new OperationalConnection(); Conectar::$db = $db;
check(method_exists('Procedimiento', 'operational_filters'), 'Operational list requires validated server-side filters');
check(method_exists('Procedimiento', 'get_operational_list'), 'Operational list requires a canonical authorized page');
$filters = Procedimiento::operational_filters([]);
check($filters['state'] === 'en_curso', 'Default must include pending and issued work');
check($filters['date_from'] === null && $filters['date_to'] === null, 'Default must not hide old pending work');
check($filters['start'] === 0 && $filters['length'] === 10, 'Bounded default page');
$request = ['proced_id' => '8', 'area_id' => '4', 'draw' => '3', 'start' => '210', 'length' => '10', 'state' => 'todos', 'search' => ['value' => 'ACME %_!']];
$parsed = Procedimiento::operational_filters($request);
check($parsed['search'] === '%ACME !%!_!!%', 'Search wildcards are literal, not executable patterns');
foreach ([['length' => '-1'], ['length' => '101'], ['start' => '-1'], ['draw' => []], ['state' => []], ['state' => '2'], ['search' => ['value' => []]], ['search' => ['value' => null]], ['search' => ['value' => str_repeat('a', 201)]], ['date_from' => "2026-10-10\0"], ['date_from' => '2026-02-30'], ['date_from' => '2026-10-10', 'date_to' => '2026-10-09']] as $bad) {
    $rejected = false;
    try { Procedimiento::operational_filters($bad); } catch (InvalidArgumentException $error) { $rejected = true; }
    check($rejected, 'Reject invalid page/state/search/date input: ' . json_encode($bad));
}
$legacy = Procedimiento::operational_filters(['sEcho' => '7', 'iDisplayStart' => '20', 'iDisplayLength' => '25', 'sSearch' => 'Name']);
check($legacy['draw'] === 7 && $legacy['start'] === 20 && $legacy['length'] === 25, 'Accept legacy DataTables page tokens');
$rejected = false;
try { Procedimiento::operational_filters(['date_from' => '0000-01-01']); } catch (InvalidArgumentException $error) { $rejected = true; }
check($rejected, 'Reject year zero before PostgreSQL date casting');
$model = new Procedimiento();
$db->rows = [['records_total' => 250, 'records_filtered' => 240, 'row_id' => null]];
$page = $model->get_operational_list('8', 7, 2, '4', $parsed);
check($page['data'] === [] && $page['recordsTotal'] === 250 && $page['recordsFiltered'] === 240, 'Out-of-range pages retain honest counts');
$sql = end($db->queries);
foreach (['authorized AS', 'tau.est = 1', 'tau.pers_id = q.person', 'q.role = 9', 'p.proced_id = q.procedure', 'p.proced_area = q.area', 'SELECT DISTINCT', 'order_rows AS', 'procedure_candidates AS', 'f.est IN (1,3)', 'valid_link.est = 1', 'valid_order.est NOT IN (0,3,6)', 'NOT EXISTS', 'UNION ALL', 'row_kind', 'filter_state', 'COUNT(*) FROM listing', 'COUNT(*) FROM filtered', 'ILIKE q.search', "ESCAPE '!'", 'LIMIT (SELECT page_size FROM params)', 'OFFSET (SELECT page_offset FROM params)', 'fechacrea DESC NULLS LAST, row_id'] as $clause) {
    check(strpos($sql, $clause) !== false, 'Missing canonical listing clause: ' . $clause);
}
foreach (['LIMIT 200', 'limit 200', 'd.est = 2', 'p.est = 1', 'GROUP BY ttc.est'] as $restriction) {
    check(strpos($sql, $restriction) === false, 'Do not truncate history or inherit creation lifecycle: ' . $restriction);
}
$history = substr($sql, strpos($sql, 'order_rows AS'), strpos($sql, 'procedure_candidates AS') - strpos($sql, 'order_rows AS'));
check(strpos($history, 'gt.est = 1') === false, 'Cancelled historical associations must remain visible');
check($db->statement->bindings[':person'][0] === 7 && $db->statement->bindings[':role'][0] === 2, 'Permission uses supplied session context');
check($db->statement->bindings[':offset'] === [210, PDO::PARAM_INT], 'Page beyond former 200-row cutoff is bound');
check($db->statement->bindings[':area'] === ['4', PDO::PARAM_INT], 'Posted area can only narrow selected procedure scope');
check($db->statement->bindings[':date_from'] === [null, PDO::PARAM_NULL], 'No implicit date cutoff');
check(strpos($sql, 'ACME') === false, 'Search text stays out of SQL');
check(strpos($sql, 'tc.empresa_razon_social, e.empr_razon_social, e.empr_nombre_comercial') !== false, 'Search includes historical and current company names');
check(strpos($sql, 'l.company_search ILIKE q.search') !== false, 'Company aliases participate in server search');
foreach ([['8 OR 1=1', 7, 2, '4'], ['8', [], 2, '4'], ['8', 7, null, '4'], ['8', 7, 2, []]] as $ids) {
    $before = count($db->queries);
    check($model->get_operational_list($ids[0], $ids[1], $ids[2], $ids[3], $parsed)['data'] === [], 'Invalid scope must produce no rows');
    check(count($db->queries) === $before, 'Invalid scope must not query');
}
$session = ['usua_id_SIGODT' => 7, 'rol_id_SIGODT' => 2];
// Fixtures are expected canonical query results, not an SQL interpreter.
$base = ['records_total' => 251, 'records_filtered' => 3, 'procedciudadano_id' => 11, 'procedciudadano_cod' => 'T-11', 'ciudadano_dni' => '<DOC>', 'nombre_completo' => '<script>ACME</script>', 'fechacrea' => '2026-10-10 10:00:00', 'total' => 2, 'pagadas' => 1];
$db->rows = [
    $base + ['row_id' => 'order:11:000001-2026', 'row_kind' => 'order', 'ogciud_id' => '000001-2026', 'est' => 0, 'filter_state' => 0],
    $base + ['row_id' => 'order:11:000002-2026', 'row_kind' => 'order', 'ogciud_id' => '000002-2026', 'est' => 1, 'filter_state' => 1],
    $base + ['row_id' => 'pending:11', 'row_kind' => 'pending', 'ogciud_id' => null, 'est' => 9, 'filter_state' => 9],
];
$response = endpoint($request + ['usua_id_SIGODT' => 99, 'rol_id_SIGODT' => 9], $session);
check($response['draw'] === 3 && $response['sEcho'] === 3, 'Echo validated draw for both contracts');
check($response['recordsTotal'] === 251 && $response['recordsFiltered'] === 3, 'Counts must not equal current page length');
check($response['data'] === $response['aaData'] && $response['iTotalDisplayRecords'] === 3, 'Legacy and modern contracts agree');
check(count($response['data']) === 3, 'Partial issuance/cancellation fixtures preserve order and pending rows');
check(strpos($response['data'][2][0], 'T-11') !== false, 'Pending row has a usable procedure identifier');
foreach ($response['data'] as $row) {
    check(strpos($row[2], '<script>') === false && strpos($row[2], '&lt;script&gt;') !== false, 'Escape company/person text');
}
check($db->statement->bindings[':person'][0] === 7 && $db->statement->bindings[':role'][0] === 2, 'Posted identity cannot expand permissions');
foreach ([0,1,3,4,5,6,9,2,42,null] as $state) {
    $db->rows = [$base + ['row_id' => 'order:11:X', 'row_kind' => 'order', 'ogciud_id' => 'X', 'est' => $state, 'filter_state' => in_array($state, [0,1,3,4,5,6], true) ? $state : null]];
    $row = endpoint($request, $session)['data'][0];
    if (!in_array($state, [0,1,3,4,5,6], true)) {
        check(strpos($row[4], 'Desconocido') !== false, 'Unknown order codes, including 2/9, must not be fabricated');
        check(strpos($row[7], 'disabled') !== false, 'Unknown state does not grant mutation affordance');
    }
}
$db->rows = [$base + ['row_id' => 'procedure:11', 'row_kind' => 'procedure', 'ogciud_id' => null, 'est' => 2, 'filter_state' => null]];
check(strpos(endpoint($request, $session)['data'][0][4], 'Desconocido') !== false, 'Procedure state 2 is not an order-state Girado');
$zero = $base; $zero['total'] = 0; $zero['pagadas'] = 0;
$db->rows = [$zero + ['row_id' => 'pending:11', 'row_kind' => 'pending', 'ogciud_id' => null, 'est' => 9, 'filter_state' => 9]];
check(strpos(endpoint($request, $session)['data'][0][5], '0.00%') !== false, 'Zero allocated fees must not divide by zero');
foreach ([[], ['usua_id_SIGODT' => []], ['usua_id_SIGODT' => 7]] as $badSession) {
    $before = count($db->queries);
    $response = endpoint($request, $badSession);
    check($response['data'] === [] && isset($response['error']), 'Reject missing/malformed session without leaking rows');
    check(count($db->queries) === $before, 'Rejected session must not query');
}
$before = count($db->queries);
check(endpoint(['proced_id' => '', 'draw' => '5'], $session)['recordsTotal'] === 0, 'No selection returns empty list');
check(count($db->queries) === $before, 'No selection does not query');
check(isset(endpoint($request + ['date_from' => []], $session)['error']), 'Malformed endpoint dates return safe errors');
$dated = Procedimiento::operational_filters(['state' => '4', 'date_from' => '2026-10-01', 'date_to' => '2026-10-10']);
$model->get_operational_list(8, 7, 9, null, $dated);
check($db->statement->bindings[':role'] === [9, PDO::PARAM_INT] && $db->statement->bindings[':area'] === [null, PDO::PARAM_NULL], 'Administrator keeps explicit role bypass without inferred area');
check($db->statement->bindings[':date_to'] === ['2026-10-10', PDO::PARAM_STR], 'Date endpoint uses bound date strings');
check(strpos(end($db->queries), "l.fechacrea < q.date_to + INTERVAL '1 day'") !== false, 'Inclusive end date uses exclusive next-day bound');
$db->rows = [['records_total' => 20, 'records_filtered' => 0, 'row_id' => null]];
check(endpoint($request, $session)['recordsFiltered'] === 0, 'No search matches preserve authorized total');
echo "Operational giro application/SQL-shape contracts passed (doubles only).\n";
