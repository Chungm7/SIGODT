<?php
// Render the shared navigation without a database or authenticated session.
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
    checkMenu(strpos($html, 'href="../admin_giros_ciudadano/"') !== false, 'Order audit remains under Processes');
    if (in_array($page, $queries, true)) {
        checkMenu(strpos($html, 'href="../' . $page . '/" class="dropdown-item active"') !== false, 'Current query remains active');
        checkMenu(preg_match('/<li class="nav-item dropdown active">\s*<a[^>]+href="#navbar-consultas"/', $html) === 1, 'Queries menu remains active');
    }
}
echo "Navigation menu tests passed.\n";
