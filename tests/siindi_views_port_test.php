<?php
namespace SiindiViewFixture;
// Only sanitized template evaluation: config, DB, shared shell and providers are never loaded.
class Redirect extends \RuntimeException {}
function header($value) { if (str_starts_with($value, 'Location:')) throw new Redirect($value); }
class Conectar {
    public static function ruta() { return '/fixture/'; }
    public static function initSession() {}
}
class JwtHandler {
    public function decrypt($token) { return (object)['data'=>(object)($token === 'render-pending' ? ($_SESSION['2fa_pending_user'] ?? []) : ($_SESSION['fixture_cookie_identity'] ?? []))]; }
}
class TwoFactorModel {
    public static function checkStatus($doc) { return ['enabled' => !empty($_SESSION['fixture_enabled']), 'data' => []]; }
    public static function getEmailByDni($doc) { return 'synthetic@example.invalid'; }
}
function render($path, $session) {
    $_SESSION = $session;
    $_COOKIE = [];
    $_ENV['SIST_INIC'] = 'SIGODT';
    $_ENV['APP_NAME'] = 'SIGODT';
    // Render-only source inputs; actual projection/expiry/route guards are exercised
    // with the real host bootstrap in siindi_external_login_test.php.
    if (!empty($session['usua_id_SIGODT'])) {
        $_COOKIE['session'] = 'render-only';
        $_SESSION['fixture_cookie_identity'] = ['usu_doc'=>$session['usua_dni_SIGODT'], 'usu_nom'=>$session['pers_nombre_SIGODT'], 'usu_apep'=>$session['pers_apelpat_SIGODT']];
    }
    $pending = $session['2fa_pending_SIGODT'] ?? null;
    if ($pending && $pending['expires_at'] > time()) {
        $_SESSION['2fa_pending_user'] = $pending;
        $_COOKIE['2fa_pending'] = 'render-pending';
    }
    $source = file_get_contents(__DIR__ . '/../' . $path);
    if ($source === false) throw new \RuntimeException('Missing copied view: ' . $path);
    $source = preg_replace('/require_once\s*(?:\([^;]+\)|[^;]+);/', '', $source);
    ob_start();
    try {
        eval('namespace SiindiViewFixture; ?>' . $source);
        return ob_get_clean();
    } catch (Redirect $e) {
        ob_end_clean();
        return $e->getMessage();
    } catch (\Throwable $e) {
        ob_end_clean();
        throw $e;
    }
}
function check($ok, $why) { if (!$ok) throw new \RuntimeException($why); }
$pending = ['2fa_pending_SIGODT' => ['usu_id' => 27, 'usu_doc' => '00000000', 'usu_nom' => '<Synthetic & Test>', 'usu_apep' => 'Family', 'expires_at' => time() + 300]];
$full = ['usua_id_SIGODT' => 3, 'sigur_usuario_id_SIGODT' => 27, 'usua_dni_SIGODT' => '00000000', 'pers_nombre_SIGODT' => '<Synthetic & Test>', 'pers_apelpat_SIGODT' => 'Family', 'pers_apelmat_SIGODT' => 'Second'];
// Template partials do not own host authorization; do not invent internal guards.
foreach (['view/setting/index.php'] as $view) {
    check(str_starts_with(render($view, $pending), 'Location:'), 'Pending cannot render authenticated surface: ' . $view);
    check(str_starts_with(render($view, []), 'Location:'), 'Anonymous cannot render: ' . $view);
}
foreach (['selector_metodo', 'desafio_login'] as $view) {
    $path = "seguridad/2fa/views/$view.php";
    $session = $pending;
    if ($view === 'desafio_login') $session['fixture_enabled'] = true;
    $html = render($path, $session);
    if ($view === 'selector_metodo') check(str_contains($html, '&lt;Synthetic &amp; Test&gt;'), 'Escaped pending name: ' . $view);
    else check(str_contains($html, '00****00'), 'Original challenge renders masked document, not the name');
    check(!str_contains($html, '00000000'), 'Document must remain server-side');
    check(!str_contains($html, 'mainProfile') && !str_contains($html, 'settings-card-panel'), 'No privileged shell');
    check(!str_contains($html, 'txtManualSecret') && !str_contains($html, 'qrContainer2FA'), 'Selector/challenge does not embed enrollment secret');
    $expired = $session; $expired['2fa_pending_SIGODT']['expires_at'] = time() - 1;
    check(str_starts_with(render($path, $expired), 'Location: /fixture/'), 'Expired projection returns entry');
}
$settings = render('view/setting/index.php', $full);
check(str_contains($settings, '&lt;Synthetic &amp; Test&gt;'), 'Settings escape real session fields');
foreach (['Mi Perfil', 'Correos Electrónicos', 'Contraseña de Acceso', 'cambiar-tlf', 'cambiar-emailp', 'cambiar-clave'] as $token) check(str_contains($settings, $token), $token);
check(!preg_match('/DNI|Módulo \/ Sistema|perfil_modulo|perfil_dni/', $settings), 'Removed displays');
$profile = render('seguridad/2fa/views/perfil_2fa.php', $full);
check(str_contains($profile, 'Activar 2FA Ahora'), 'Authenticated activation');
$profile = render('seguridad/2fa/views/perfil_2fa.php', $full + ['fixture_enabled' => true]);
check(str_contains($profile, 'ACTIVADO') && str_contains($profile, 'Recuperación de Acceso:</strong> Correo Institucional'), 'Active source recovery channel label');
$modal = render('seguridad/2fa/views/modal_enrolamiento.php', $full);
check(str_contains($modal, 'modal2FAEnroll') && str_contains($modal, 'txtManualSecret'), 'Full-only original enrollment modal');
check(str_contains($modal, "$('#txtManualSecret').text(resp.secret_fmt || resp.secret)"), 'Original modal displays acknowledged source enrollment secret');
$book = render('seguridad/2fa/views/enrolar_login.php', $pending);
check(str_contains($book, 'enroll-left-col') && str_contains($book, 'qrContainerLogin'), 'Pending account retains original enrollment book');
check(str_contains(render('seguridad/2fa/views/enrolar_login.php', $pending), 'qrContainerLogin'), 'Original first enrollment remains available during pending login');
// Ordinary/pending sessions are denied recovery/first-access by the actual host
// bootstrap, before the original screens' cookie readers run. That regression
// lives in external login's origin-captcha-stages rule, not sanitized rendering.
echo "PASS SIINDI render inputs: original enrollment, escaped synthetic fields and settings; actual host guards tested separately\n";
