<?php
// Focused settings-only renderer: no dependency on changing peer login fixtures.
namespace SiindiViewFixture {
    function check($ok, $message) { if (!$ok) throw new \RuntimeException($message); }
    function render($path, $session) {
        $source = file_get_contents(__DIR__ . '/../' . $path);
        $source = preg_replace('/require_once\([^;]+;/', '', $source);
        $source = preg_replace('/include\s+\$twoFactorPerfilFile\s*;/', '', $source);
        $dir = sys_get_temp_dir() . '/settings-render-' . bin2hex(random_bytes(5));
        mkdir($dir, 0700);
        file_put_contents($dir . '/view.php', $source);
        file_put_contents($dir . '/run.php', '<?php class Conectar { static function initSession(){} static function ruta(){return "/SIGODT/";} } $_SESSION=json_decode(stream_get_contents(STDIN),true); require __DIR__."/view.php";');
        $p = proc_open([PHP_BINARY,'-n',$dir . '/run.php'], [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']], $pipes);
        fwrite($pipes[0], json_encode($session)); fclose($pipes[0]);
        $out=stream_get_contents($pipes[1]); $err=stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]);
        check(proc_close($p)===0 && $err==='', 'settings fixture stderr/stdout: '.$err.$out);
        return $out;
    }
}
namespace {
$session = [
    'usua_id_SIGODT' => 1, 'sigur_usuario_id_SIGODT' => 2, 'usua_dni_SIGODT' => '00000000',
    'pers_nombre_SIGODT' => '<Synthetic & Example>', 'pers_apelpat_SIGODT' => '"Family"',
    'pers_apelmat_SIGODT' => '<Second>', 'pers_emailm_SIGODT' => 'synthetic@example.invalid',
    'pers_emailp_SIGODT' => '<bad&mail>', 'rol_nombre_SIGODT' => '<Actual profile>',
];
$html = \SiindiViewFixture\render('view/setting/index.php', $session);
foreach (['&lt;Synthetic &amp; Example&gt;', '&quot;Family&quot;', '&lt;Second&gt;', 'value="synthetic@example.invalid"', '&lt;bad&amp;mail&gt;'] as $value) {
    \SiindiViewFixture\check(str_contains($html, $value), 'Escaped genuine session field: ' . $value);
}
\SiindiViewFixture\check(str_contains($html, 'value="&lt;Actual profile&gt;"'), 'Cargo uses the existing profile label, escaped');
\SiindiViewFixture\check(!str_contains($html, '00000000'), 'Identity document remains server-side');
foreach (['telefono', 'emailp'] as $id) {
    preg_match('/<input\b[^>]*id="' . $id . '"[^>]*>/', $html, $match);
    \SiindiViewFixture\check(!empty($match) && !preg_match('/disabled|readonly/', $match[0]), 'Approved editable contact: ' . $id);
}
preg_match('/<input\b[^>]*id="emaili"[^>]*>/', $html, $match);
\SiindiViewFixture\check(!empty($match) && str_contains($match[0], 'disabled'), 'Institutional email readonly');
\SiindiViewFixture\check(str_contains($html, 'cambiar-clave') && str_contains($html, 'seguridad/actualizar_contrasena/views/'), 'Real transplanted password action');
echo "PASS institutional settings: actual fields, approved contact actions, password link, escaped synthetic render\n";
}
