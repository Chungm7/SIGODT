<?php
// Run the public endpoint with a connection double; never load credentials or a live DB.
function check($condition, $message) {
    if (!$condition) { throw new RuntimeException($message); }
}
function reportEndpoint($session, $input, $op = 'json', $rows = []) {
    $setup = <<<'PHP'
session_set_save_handler(function(){return true;},function(){return true;},function(){return '';},function(){return true;},function(){return true;},function(){return 0;});
session_start();
class Conectar {
    public static $rows;
    protected function conexion() { return $this; }
    public function prepare($sql) {
        if (!str_contains($sql, 'og.pers_id = :emitter') || !str_contains($sql, 'og.fechacrea >= :start') || !str_contains($sql, 'og.fechacrea < :end')) { throw new Exception('Missing ownership/date predicate'); }
        if (preg_match('/\b(?:og|gt|ttc|pc|p|d)\.est\s*(?:=|IN)/i', $sql)) { throw new Exception('State exclusion'); }
        if (!str_contains($sql, 'LEFT JOIN sc_giros.td_giro_tasa_ciudadano') || !str_contains($sql, 'ttc.tasatciud_procedciud = pc.procedciudadano_id')) { throw new Exception('Wrong historical joins'); }
        if (!str_contains($sql, 'og.fechacrea DESC, og.ogciud_id DESC, gt.girot_id')) { throw new Exception('Unstable ordering'); }
        return $this;
    }
    public function execute($bindings) {
        if ($bindings !== [':emitter' => 7, ':start' => '2025-01-01', ':end' => '2025-02-01']) { throw new Exception('Incorrect bound ownership or inclusive date range'); }
        return true;
    }
    public function fetchAll($mode) { return self::$rows; }
}
PHP;
    $code = $setup . 'Conectar::$rows=' . var_export($rows, true) . ';$_SESSION=' . var_export($session, true) . ';$_GET=' . var_export(array_merge($input, ['op' => $op]), true) . ';register_shutdown_function(function(){fwrite(STDERR,"HTTP:".(http_response_code() ?: 200));});include ' . var_export(__DIR__ . '/../controller/reporte_usuario.php', true) . ';';
    $pipes = [];
    $process = proc_open([PHP_BINARY, '-r', $code], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    $out = stream_get_contents($pipes[1]); $err = stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    $exit = proc_close($process);
    check($exit === 0, 'Endpoint exit: ' . $err);
    return [$out, $err];
}
$period = ['start' => '2025-01-01', 'end' => '2025-01-31'];
$session = ['usua_id_SIGODT' => '7', 'nombre_completo_SIGODT' => '=Emisor <script>'];
foreach ([[], ['usua_id_SIGODT' => []], ['usua_id_SIGODT' => '1 OR 1=1'], ['usua_id_SIGODT' => 0]] as $invalid) {
    foreach (['json', 'print', 'pdf'] as $op) {
        check(str_contains(reportEndpoint($invalid, $period, $op)[1], 'HTTP:401'), 'S2: reject invalid sessions on every output');
    }
}
echo "PASS S2: unauthenticated/malformed boundary\n";
foreach ([[], ['start' => '2025-02-30', 'end' => '2025-03-01'], ['start' => [], 'end' => '2025-01-31'], ['start' => '2025-02-01', 'end' => '2025-01-01'], $period + ['document' => []], $period + ['procedure' => '-1'], $period + ['page' => 0], $period + ['state' => 'invalido']] as $invalid) {
    check(str_contains(reportEndpoint($session, $invalid)[1], 'HTTP:400'), 'S4: invalid range/filter/page/state');
}
echo "PASS S4: required real calendar range and typed filters\n";
$base = ['order_id' => '003-2025', 'emitted_at' => '2025-01-31 23:59:59', 'state' => '0', 'citizen' => '<img onerror=1>', 'document_id' => '1', 'document' => 'TUPA', 'procedure_id' => '10', 'code' => '=A1', 'procedure' => 'Licencia', 'amount' => '30.25', 'line_id' => '1'];
$rows = [$base, array_replace($base, ['line_id' => '2', 'amount' => '10.00']), array_replace($base, ['line_id' => '3', 'document_id' => '2', 'document' => 'Histórico', 'procedure_id' => '20', 'code' => 'B', 'procedure' => 'Permiso', 'citizen' => 'Empresa / Otro ciudadano', 'amount' => '5.50']), array_replace($base, ['order_id' => '002-2025', 'line_id' => '4', 'state' => '6', 'amount' => '2.00']), array_replace($base, ['order_id' => '001-2025', 'line_id' => '5', 'state' => '99', 'document_id' => '1', 'procedure_id' => '10', 'amount' => '15.00'])];
function result($session, $input, $rows) {
    [$out, $err] = reportEndpoint($session, $input, 'json', $rows);
    check($err === 'HTTP:200', 'Successful endpoint: ' . $err . ' ' . $out);
    $data = json_decode($out, true, 512, JSON_THROW_ON_ERROR);
    return $data;
}
$data = result($session, $period + ['limit' => 1, 'usua_id' => 999, 'emitter' => 999], $rows);
check($data['summary'] === ['orders' => 1, 'cancelled' => 2, 'lines' => 1, 'amount' => '15.00'], 'S1/S5: count active headers, sum persisted totals once, separate cancelled');
check(count($data['details']) === 1 && $data['total'] === 3 && $data['page'] === 1, 'S3: paginated unique headers');
check(count($data['details'][0]['citizens']) === 2 && count($data['details'][0]['procedures']) === 2, 'S3: preserve multi-citizen and multi-procedure labels');
check($data['details'][0]['state_label'] === 'Anulado', 'S5: current state');
check(count($data['options']['documents']) === 2, 'S4: historical document options');
check($data['breakdown'][0]['orders'] === 1 && $data['breakdown'][0]['amount'] === '15.00', 'S5: distinct breakdown counts and line totals');
$filtered = result($session, $period + ['document' => '2', 'procedure' => '20'], $rows);
check($filtered['summary'] === ['orders' => 0, 'cancelled' => 1, 'lines' => 0, 'amount' => '0.00'] && $filtered['details'][0]['amount'] === '5.50', 'S7: matching lines consistently aggregated');
check(result($session, $period + ['document' => '1', 'procedure' => '20'], $rows)['total'] === 0, 'S4/S7: inconsistent filters yield no matches');
$emitidosOnly = result($session, $period + ['state' => 'emitidos'], $rows);
check($emitidosOnly['total'] === 1 && $emitidosOnly['details'][0]['order_id'] === '001-2025', 'S5: emitidos filter excludes cancelled orders from details');
check($emitidosOnly['summary'] === ['orders' => 1, 'cancelled' => 0, 'lines' => 1, 'amount' => '15.00'], 'S5: emitidos summary has 0 cancelled and only active emissions');
check(count($emitidosOnly['breakdown']) === 1 && $emitidosOnly['breakdown'][0]['orders'] === 1, 'S5: emitidos breakdown only includes active orders');
$anuladosOnly = result($session, $period + ['state' => 'anulados'], $rows);
check($anuladosOnly['total'] === 2 && $anuladosOnly['summary']['orders'] === 0 && $anuladosOnly['summary']['cancelled'] === 2, 'S5: anulados filter includes only cancelled orders');
$pagado = result($session, $period, [array_replace($base, ['state' => '4'])]);
check($pagado['details'][0]['state_label'] === 'Pagado', 'S5: state 4 maps to Pagado');
$last = result($session, $period + ['page' => 999, 'limit' => 1], $rows);
check($last['page'] === 3 && $last['details'][0]['state_label'] === 'Estado 99', 'S3: page clamp and unknown state retained');
$girado = result($session, $period, [array_replace($base, ['state' => '1'])]);
check($girado['details'][0]['state_label'] === 'Girado', 'S5: emission state 1');
check(result($session, $period, [])['summary']['amount'] === '0.00', 'S1: empty report');
echo "PASS S1/S3/S4/S5/S7: amounts, unique headers, matching-line filters, pagination and historical labels\n";
check(str_contains(reportEndpoint($session, $period, 'csv')[1], 'HTTP:400'), 'S6: CSV export is disabled');
[$html] = reportEndpoint($session, $period + ['limit' => 1], 'print', $rows);
check(str_contains($html, '001-2025') && str_contains($html, '&lt;img onerror=1&gt;') && !str_contains($html, '<img onerror=1>'), 'S6: full escaped print HTML');
[$export] = reportEndpoint($session, $period + ['procedure' => '20'], 'print', $rows);
check(!str_contains($export, '002-2025') && str_contains($export, '5.50'), 'S6/S7: same filtered full export');
foreach ([[], $period + ['document' => []], $period + ['procedure' => '-1']] as $invalid) {
    check(reportEndpoint($session, $invalid, 'pdf')[1] === 'HTTP:400', 'PDF validates before database access');
}
[$pdf, $status] = reportEndpoint($session, $period + ['limit' => 1, 'page' => 2, 'emitter' => 999, 'worker' => 999, 'full' => 0], 'pdf', $rows);
$pdf = json_decode($pdf, true, 512, JSON_THROW_ON_ERROR);
check($status === 'HTTP:200' && count($pdf['details']) === 3 && $pdf['summary'] === $data['summary'], 'PDF uses full authorized results, not requested page/emitter/full');
[$pdfFiltered] = reportEndpoint($session, $period + ['procedure' => '20'], 'pdf', $rows);
check(json_decode($pdfFiltered, true)['summary'] === $filtered['summary'], 'PDF matching-line semantics');
echo "PASS S6/S10: full authorized PDF data and existing exports\n";
check(str_contains($html, '<strong>SIGODT</strong>') && str_contains($html, 'aria-label="Contexto del reporte"'), 'S9: institutional heading and grouped context');
check(str_contains($html, 'size: A4 landscape') && str_contains($html, 'table-layout: fixed') && str_contains($html, 'table-header-group'), 'S9: readable landscape print with repeated headers and proportional columns');
check(str_contains($html, 'break-inside: avoid') && !str_contains($html, 'text-overflow: ellipsis') && !str_contains($html, 'overflow: hidden'), 'S3/S9: keep short rows together without clipping long content');
check(str_contains($html, 'Imprimir / Guardar como PDF') && str_contains($html, '.print-toolbar { display: none; }'), 'S6/S9: honest browser PDF action hidden on paper');
$longName = str_repeat('Nombre sintético extenso & <seguro> ', 30);
[$longHtml] = reportEndpoint($session, $period, 'print', [array_replace($base, ['citizen' => $longName, 'procedure' => $longName])]);
check(substr_count($longHtml, htmlspecialchars($longName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')) >= 2, 'S3/S9: full long names and procedure content preserved');
check(substr_count(substr($html, strpos($html, '<thead>'), strpos($html, '</thead>') - strpos($html, '<thead>')), '<th>') === 8, 'S3: all eight detail columns');
echo "PASS S3/S6/S9: institutional print, complete columns, long content and pagination styling\n";
// Snapshot unchanged links and active groups, plus the new report entry.
foreach (['inicio', 'dashboard', 'giros_ciudadano', 'admin_giros_ciudadano', 'consultar_documento', 'consultar_nombre', 'consultar_og', 'mnt_ciudadano', 'mnt_empresa', 'mnt_tupa', 'mnt_procedimiento', 'mnt_tasas', 'mnt_tributos', 'mnt_permisos', 'reporte_usuario'] as $route) {
    $_SERVER['PHP_SELF'] = '/SIGODT/view/' . $route . '/index.php';
    ob_start(); include __DIR__ . '/../view/html/menu.php'; $menu = ob_get_clean();
    check(str_contains($menu, 'href="../' . $route . '/"'), 'Menu link preserved: ' . $route);
    $group = str_starts_with($route, 'mnt_') ? 'isMntActive' : (str_starts_with($route, 'consultar_') ? 'isConsultasActive' : (in_array($route, ['giros_ciudadano', 'admin_giros_ciudadano'], true) ? 'isProcesosActive' : ['inicio' => 'isInicioActive', 'dashboard' => 'isDashboardActive', 'reporte_usuario' => 'isReportActive'][$route]));
    foreach (['isInicioActive', 'isDashboardActive', 'isProcesosActive', 'isConsultasActive', 'isMntActive', 'isReportActive'] as $flag) {
        check($$flag === ($flag === $group), 'Menu active group preserved: ' . $route . ' / ' . $flag);
    }
    if (str_starts_with($route, 'mnt_') || str_starts_with($route, 'consultar_') || in_array($route, ['giros_ciudadano', 'admin_giros_ciudadano'], true)) {
        check(str_contains($menu, 'href="../' . $route . '/" class="dropdown-item active"'), 'Menu child active state: ' . $route);
    }
    check(substr_count($menu, 'href="../reporte_usuario/"') === 1, 'Report menu route is unique');
}
echo "PASS menu regression links/active states; no live PostgreSQL executed\n";
