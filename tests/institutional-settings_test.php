<?php
// Evaluate only the template with synthetic session values; never load DB/config or shared includes.
function renderSettings(array $session): string {
    $_SESSION = $session;
    $source = file_get_contents(__DIR__ . '/../view/setting/index.php');
    $source = preg_replace('/require_once\([^;]+\);/', '', $source);
    ob_start();
    eval('?>' . $source);
    return ob_get_clean();
}
function checkSettings(bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException($message);
}
$base = ['usua_id_SIGODT' => 1];
$html = renderSettings($base);
checkSettings(str_contains($html, '>CONFIGURACIÓN</h1>'), 'Expected CONFIGURACIÓN heading');
checkSettings(preg_match_all('/<input\b[^>]*\sreadonly\b/', $html) === 6, 'All six original fields must remain readonly');
foreach (['Usuario', 'Sin perfil', 'No cuenta con correo electrónico registrado', 'No cuenta con número de teléfono registrado', 'SIGODT - Órdenes de Derecho de Trámite'] as $text) {
    checkSettings(str_contains($html, $text), 'Missing fallback: ' . $text);
}
$filled = renderSettings($base + [
    'nombre_completo_SIGODT' => '<Synthetic & Example>', 'usua_dni_SIGODT' => '<DNI>',
    'rol_nombre_SIGODT' => '<Role>', 'pers_emailm_SIGODT' => '  synthetic@example.invalid  ',
    'pers_celu01_SIGODT' => '  000000000  ',
]);
foreach (['&lt;Synthetic &amp; Example&gt;', '&lt;DNI&gt;', '&lt;Role&gt;', 'value="synthetic@example.invalid"', 'value="000000000"'] as $text) {
    checkSettings(str_contains($filled, $text), 'Escaping/trim contract: ' . $text);
}
checkSettings(!str_contains($filled, 'No cuenta con'), 'No missing-contact warnings for populated values');
$partial = renderSettings($base + ['pers_emailm_SIGODT' => '  ', 'pers_celu01_SIGODT' => '000000000']);
checkSettings(str_contains($partial, 'No cuenta con correo') && !str_contains($partial, 'No cuenta con número'), 'Independent empty-contact conditions');
checkSettings(!preg_match('/<(form|button)\b/', $html), 'Do not add reference mutation controls');
// Preview is synthetic and generated from this same template, without a real session or database.
if (in_array('--preview', $argv ?? [], true)) {
    $preview = preg_replace('/<script\b[^>]*>.*?<\/script>/s', '', $html);
    $preview = str_replace('../../public/', '../public/', $preview);
    $preview = str_replace('<head>', '<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><link rel="stylesheet" href="../public/css/tabler.min.css"><link rel="stylesheet" href="../public/lib/roboto/roboto.css">', $preview);
    file_put_contents(__DIR__ . '/../.playwright-mcp/institutional-settings-preview.html', $preview);
}
echo "Institutional settings render checks passed (fallback, populated, escaped and partial contact).\n";
