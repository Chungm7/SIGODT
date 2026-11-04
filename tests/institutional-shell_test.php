<?php
// Render shared components without database access or a real user session.
function renderShell($admin = false, $page = 'inicio', $sessionOverrides = []) {
    $_ENV['SIS_NOM'] = 'Sistema de Gestión de Órdenes de Derecho de Trámite';
    $_SESSION = [
        'usua_id_SIGODT' => 1,
        'nombre_completo_SIGODT' => 'Usuario de prueba',
        'rol_nombre_SIGODT' => $admin ? 'ADMINISTRADOR' : 'OPERADOR',
        'rol_id_SIGODT' => $admin ? 9 : 8,
        'usua_foto_SIGODT' => '../../public/img/perfil.jpeg',
    ];
    $_SESSION = array_replace($_SESSION, $sessionOverrides);
    $_SERVER['PHP_SELF'] = '/SIGODT/view/' . $page . '/index.php';
    ob_start();
    foreach (['mainProfile', 'menu', 'footer'] as $component) {
        include __DIR__ . '/../view/html/' . $component . '.php';
    }
    return ob_get_clean();
}
if (in_array('--preview', $argv ?? [], true)) {
    echo '<!doctype html><html lang="es"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<link rel="stylesheet" href="/public/css/tabler.min.css"><link rel="stylesheet" href="/public/css/municipal-theme.css"><link rel="stylesheet" href="/public/css/institutional-shell.css">';
    echo '<body><div class="page">' . renderShell(true) . '</div><script src="/public/js/tabler.min.js"></script></body></html>';
    exit;
}
function checkShell($condition, $message) {
    if (!$condition) throw new RuntimeException($message);
}
$html = renderShell();
foreach (['app-global-header', 'app-header-container', 'user-profile-pill', 'app-global-footer', 'app-footer-container', 'mpch-subsys-navbar'] as $class) {
    checkShell(str_contains($html, $class), 'Missing reference structure: ' . $class);
}
foreach (['../inicio/', '../dashboard/', '../giros_ciudadano/', '../consultar_documento/', '../consultar_nombre/', '../consultar_og/', '../reporte_usuario/', '../setting/', '../../view/html/logout.php'] as $href) {
    checkShell(str_contains($html, 'href="' . $href . '"'), 'SIGODT action missing: ' . $href);
}
checkShell(str_contains($html, 'USUARIO DE PRUEBA'), 'Session user remains visible');
checkShell(str_contains($html, 'OPERADOR'), 'Session role remains visible');
checkShell(str_contains($html, 'SIGODT-2026 V 1.0'), 'SIGODT footer identity remains unchanged');
checkShell(!str_contains($html, 'portal_transportes') && !str_contains($html, 'portal_sanidad'), 'Reference business routes must not be copied');
checkShell(!str_contains($html, '../mnt_permisos/') && !str_contains($html, '../admin_giros_ciudadano/'), 'Operator navigation remains restricted');
$admin = renderShell(true, 'mnt_permisos');
checkShell(str_contains($admin, '../mnt_permisos/') && str_contains($admin, '../admin_giros_ciudadano/'), 'Administrator actions remain available');
checkShell(str_contains($html, 'data-bs-target="#navbar-menu"') && str_contains($html, 'id="navbar-menu"'), 'Mobile collapse contract remains intact');
checkShell(str_contains($admin, 'class="dropend"') && str_contains($admin, 'data-bs-auto-close="outside"'), 'Nested Bootstrap navigation remains intact');
$head = file_get_contents(__DIR__ . '/../view/html/mainHead.php');
checkShell(str_contains($head, 'public/css/institutional-shell.css'), 'Shared head must load reference styles');
$custom = renderShell(false, 'setting', [
    'nombre_completo_SIGODT' => '<Test & User>',
    'usua_foto_SIGODT' => '../../public/img/custom-avatar.png',
]);
checkShell(str_contains($custom, '&lt;TEST &amp; USER&gt;'), 'Profile names remain escaped');
checkShell(str_contains($custom, '../../public/img/custom-avatar.png'), 'Session avatar is retained');
checkShell(substr_count($custom, '&lt;TEST &amp; USER&gt;') === 2, 'Escaped identity is available in pill and narrow-screen dropdown');
checkShell(str_contains($custom, 'profile-mobile-identity') && str_contains($custom, 'd-sm-none'), 'Narrow profile dropdown retains identity fallback');
checkShell(str_contains($html, 'width="24" height="27" alt="Escudo MPCH"'), 'Footer logo retains municipal asset with reference dimensions');
$guest = renderShell(false, 'inicio', ['usua_id_SIGODT' => null]);
checkShell(str_contains($guest, 'INVITADO') && str_contains($guest, 'SIN PERFIL'), 'Guest identity remains available');
checkShell(str_contains($guest, '../../public/img/perfil.jpeg'), 'Guest avatar fallback is retained');
echo "Institutional shell tests passed.\n";
