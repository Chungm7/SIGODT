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
if (!in_array($operation, ['json', 'csv', 'print', 'pdf'], true)) {
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
if ($operation === 'json' || $operation === 'pdf') {
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
<html lang="es"><head><meta charset="utf-8"><title>Reporte</title>
<style>
* { box-sizing: border-box; }
body { font: 11px/1.45 Arial, sans-serif; color: #182433; background: #eef1f4; margin: 0; }
.print-toolbar { padding: 16px 24px; background: white; border-bottom: 1px solid #ccd4dd; }
.print-toolbar button { min-height: 44px; padding: 8px 16px; cursor: pointer; }
.print-document { background: white; max-width: 1400px; margin: 24px auto; padding: 32px; }
.report-heading { border-bottom: 2px solid #206bc4; padding-bottom: 16px; }
.report-heading strong { color: #206bc4; font-size: 20px; letter-spacing: 1px; }
h1 { font-size: 24px; margin: 8px 0; } h2 { font-size: 15px; margin: 24px 0 8px; }
dl { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px 24px; margin: 16px 0; }
dl div { overflow-wrap: anywhere; } dt { color: #526171; } dd { margin: 2px 0 0; font-weight: bold; }
.report-note { padding: 8px 0; border-top: 1px solid #ccd4dd; }
table { border-collapse: collapse; table-layout: fixed; width: 100%; margin: 8px 0 16px; }
th, td { border-bottom: 1px solid #ccd4dd; padding: 7px 6px; text-align: left; vertical-align: top; overflow-wrap: anywhere; }
th { color: #174f85; background: #eef3f8; font-size: 10px; }
tbody tr:nth-child(even) { background: #f7f9fb; }
td:last-child, th:last-child { text-align: right; font-variant-numeric: tabular-nums; }
.breakdown :is(td, th):nth-last-child(-n+3) { text-align: right; }
@page { size: A4 landscape; margin: 12mm; }
@media print {
  body { background: white; font-size: 9pt; }
  .print-toolbar { display: none; }
  .print-document { margin: 0; padding: 0; max-width: none; }
  thead { display: table-header-group; }
  tr { break-inside: avoid; page-break-inside: avoid; }
  h2 { break-after: avoid; }
}
</style></head><body>
<div class="print-toolbar"><button type="button" onclick="window.print()">Imprimir / Guardar como PDF</button> <span>Seleccione el destino PDF en el diálogo del navegador.</span></div>
<main class="print-document">
<header class="report-heading"><strong>SIGODT</strong><h1>Reporte</h1><p>Órdenes emitidas · Detalle completo del filtro</p></header>
<dl aria-label="Contexto del reporte"><?php foreach (array_slice($metadata, 0, 5) as $row) { echo '<div><dt>' . reportEscape($row[0]) . '</dt><dd>' . reportEscape($row[1]) . '</dd></div>'; } ?></dl>
<h2>Resumen de emisiones</h2>
<dl aria-label="Totales del filtro"><?php foreach (array_slice($metadata, 5, 4) as $row) { echo '<div><dt>' . reportEscape($row[0]) . '</dt><dd>' . reportEscape($row[1]) . '</dd></div>'; } ?></dl>
<p class="report-note"><?php echo reportEscape($metadata[9][1]); ?></p>
<h2>Detalle completo filtrado</h2>
<table><colgroup><col style="width:9%"><col style="width:12%"><col style="width:18%"><col style="width:10%"><col style="width:10%"><col style="width:23%"><col style="width:9%"><col style="width:9%"></colgroup><thead><?php reportHtmlRow($headings, 'th'); ?></thead><tbody>
<?php foreach ($report['details'] as $row) { reportHtmlRow(reportDetailCells($row)); } ?>
</tbody></table>
<h2>Desglose por documento y procedimiento</h2>
<table class="breakdown"><colgroup><col style="width:18%"><col style="width:12%"><col style="width:37%"><col style="width:11%"><col style="width:10%"><col style="width:12%"></colgroup><thead><?php reportHtmlRow(['Documento', 'Código', 'Procedimiento', 'Órdenes (no aditivas)', 'Conceptos', 'Importe (S/)'], 'th'); ?></thead><tbody>
<?php foreach ($report['breakdown'] as $row) { reportHtmlRow(array_values($row)); } ?>
</tbody></table></main></body></html>
