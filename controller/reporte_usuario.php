<?php
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
$emitter = $_SESSION['usua_id_SIGODT'] ?? null;
if ((!is_string($emitter) && !is_int($emitter)) || !preg_match('/^[1-9][0-9]*$/D', (string)$emitter) ||
    filter_var($emitter, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
    http_response_code(401);
    echo json_encode(['error' => 'Sesión no válida. Inicie sesión nuevamente.']);
    exit;
}
$operation = $_GET['op'] ?? 'json';
if (!in_array($operation, ['json', 'csv', 'print'], true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Operación inválida.']);
    exit;
}
ob_start();
try {
    if (!class_exists('Conectar', false)) { require_once __DIR__ . '/../config/conexion.php'; }
    require_once __DIR__ . '/../models/ReporteUsuario.php';
    // The only emitter source is the validated session, never a request parameter.
    $filters = ReporteUsuario::validate($_GET);
    $report = (new ReporteUsuario())->report($emitter, $_GET, $operation !== 'json');
    $name = $_SESSION['nombre_completo_SIGODT'] ?? '';
    $report['emitter'] = is_string($name) && trim($name) !== '' ? $name : 'Emisor ' . $emitter;
    $report['generated_at'] = date('Y-m-d H:i:s');
    ob_end_clean();
} catch (InvalidArgumentException $error) {
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['error' => $error->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
} catch (Throwable $error) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo generar el reporte. Intente nuevamente.']);
    exit;
}
if ($operation === 'json') {
    echo json_encode($report, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}
$metadata = [
    ['Emisor', $report['emitter']],
    ['Periodo inclusivo', $filters['start'] . ' al ' . $filters['end']],
    ['Documento (ID)', $filters['document'] ?? 'Todos'],
    ['Procedimiento (ID)', $filters['procedure'] ?? 'Todos'],
    ['Fecha de generación', $report['generated_at']],
    ['Total de órdenes emitidas', $report['summary']['orders']],
    ['Órdenes anuladas y extornadas', $report['summary']['cancelled']],
    ['Conceptos coincidentes', $report['summary']['lines']],
    ['Importe emitido (S/)', $report['summary']['amount']],
    ['Advertencia', 'No equivale a dinero recaudado. Solo conceptos coincidentes; los conteos del desglose no son aditivos.']
];
$headings = ['N.º de orden', 'Fecha y hora de emisión', 'Ciudadano / Razón social', 'Documento', 'Código del procedimiento', 'Nombre del procedimiento', 'Estado actual', 'Importe (S/)'];
function reportDetailCells($row) {
    return [$row['order_id'], $row['emitted_at'], implode(' / ', $row['citizens']), implode(' / ', $row['documents']),
        implode(' / ', $row['codes']), implode(' / ', $row['procedures']), $row['state_label'], $row['amount']];
}
if ($operation === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="reporte-emisiones.csv"');
    $stream = fopen('php://output', 'w');
    fwrite($stream, "\xEF\xBB\xBF");
    // Treat every value as text; neutralize formulas even after whitespace/control prefixes.
    $write = function ($cells) use ($stream) {
        $safe = array_map(function ($value) {
            $value = (string)$value;
            return preg_match('/^[\s\p{Z}\x00-\x20\x{FEFF}]*[=+@-]/u', $value) || preg_match('/^[\t\r\n]/', $value) ? "'" . $value : $value;
        }, $cells);
        fputcsv($stream, $safe, ';', '"', '');
    };
    foreach ($metadata as $row) { $write($row); }
    $write([]); $write($headings);
    foreach ($report['details'] as $row) { $write(reportDetailCells($row)); }
    $write([]); $write(['Documento', 'Código', 'Procedimiento', 'Órdenes (no aditivas)', 'Conceptos', 'Importe (S/)']);
    foreach ($report['breakdown'] as $row) { $write(array_values($row)); }
    fclose($stream);
    exit;
}
header('Content-Type: text/html; charset=utf-8');
function reportEscape($value) { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function reportHtmlRow($cells, $tag = 'td') {
    echo '<tr>';
    foreach ($cells as $cell) { echo '<' . $tag . '>' . reportEscape($cell) . '</' . $tag . '>'; }
    echo '</tr>';
}
?>
<!doctype html>
<html lang="es"><head><meta charset="utf-8"><title>Reporte de mis emisiones</title>
<style>
body { font: 14px sans-serif; margin: 2rem; } table { border-collapse: collapse; width: 100%; margin: 1rem 0; }
th, td { border: 1px solid #777; padding: .4rem; text-align: left; overflow-wrap: anywhere; }
@media print { button { display: none; } thead { display: table-header-group; } tr { break-inside: avoid; } @page { size: landscape; } }
</style></head><body>
<h1>Reporte de mis emisiones</h1>
<button type="button" onclick="window.print()">Imprimir / Guardar como PDF</button>
<dl><?php foreach ($metadata as $row) { echo '<dt>' . reportEscape($row[0]) . '</dt><dd>' . reportEscape($row[1]) . '</dd>'; } ?></dl>
<h2>Detalle completo filtrado</h2>
<table><thead><?php reportHtmlRow($headings, 'th'); ?></thead><tbody>
<?php foreach ($report['details'] as $row) { reportHtmlRow(reportDetailCells($row)); } ?>
</tbody></table>
<h2>Desglose por documento y procedimiento</h2>
<table><thead><?php reportHtmlRow(['Documento', 'Código', 'Procedimiento', 'Órdenes (no aditivas)', 'Conceptos', 'Importe (S/)'], 'th'); ?></thead><tbody>
<?php foreach ($report['breakdown'] as $row) { reportHtmlRow(array_values($row)); } ?>
</tbody></table></body></html>
