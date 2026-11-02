<?php
require_once __DIR__ . '/../config/conexion.php';
// Render the shared navigation.
function renderMenu($page) {
    $_SERVER['PHP_SELF'] = '/SIGODT/view/' . $page . '/index.php';
    ob_start();
    include __DIR__ . '/../view/html/menu.php';
    return ob_get_clean();
}
function checkMenu($condition, $message) {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}
$queries = ['consultar_documento', 'consultar_nombre', 'consultar_og'];
foreach (array_merge(['inicio', 'giros_ciudadano'], $queries) as $page) {
    $html = renderMenu($page);
    checkMenu(strpos($html, 'href="../consultar_mes/"') === false, 'Monthly audit link must not appear');
    checkMenu(strpos($html, 'Auditoría Mensual de Órdenes') === false, 'Monthly audit label must not appear');
    foreach ($queries as $query) {
        checkMenu(strpos($html, 'href="../' . $query . '/"') !== false, 'Existing query link remains: ' . $query);
    }
    checkMenu(strpos($html, 'href="../admin_giros_ciudadano/"') === false, 'Order audit must not appear for unauthenticated user');
    if (in_array($page, $queries, true)) {
        checkMenu(strpos($html, 'href="../' . $page . '/" class="dropdown-item active"') !== false, 'Current query remains active');
        checkMenu(preg_match('/<li class="nav-item dropdown active">\s*<a[^>]+href="#navbar-consultas"/', $html) === 1, 'Queries menu remains active');
    }
    checkMenu(strpos($html, 'Mantenimientos') === false, 'Mantenimientos menu must not appear for unauthenticated user');
}

// Non-admin session (digitador)
$_SESSION = ['usua_id_SIGODT' => 8, 'rol_id_SIGODT' => 8];
$htmlOperator = renderMenu('giros_ciudadano');
checkMenu(strpos($htmlOperator, 'Mantenimientos') === false, 'Mantenimientos menu must not appear for non-admin user');
checkMenu(strpos($htmlOperator, 'href="../admin_giros_ciudadano/"') === false, 'Order audit must not appear for non-admin user');

// Admin session
$_SESSION = ['usua_id_SIGODT' => 1, 'rol_id_SIGODT' => 9];
$htmlAdmin = renderMenu('giros_ciudadano');
checkMenu(strpos($htmlAdmin, 'Mantenimientos') !== false, 'Mantenimientos menu must appear for admin user');
checkMenu(strpos($htmlAdmin, 'href="../mnt_procedimiento/"') !== false, 'Procedimientos link must appear for admin user');
checkMenu(strpos($htmlAdmin, 'href="../mnt_tributos/"') !== false, 'Tributos link must appear for admin user');
checkMenu(strpos($htmlAdmin, 'href="../admin_giros_ciudadano/"') !== false, 'Order audit must appear for admin user');

$_SESSION = [];
echo "Navigation menu tests passed.\n";
