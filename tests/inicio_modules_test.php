<?php
require_once __DIR__ . '/../config/conexion.php';

function check($condition, $message) {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

// 1. Static inspection of view/inicio/index.php
$inicioFile = file_get_contents(__DIR__ . '/../view/inicio/index.php');

check(strpos($inicioFile, 'Registrar Órdenes de Giro') !== false, 'Registrar Órdenes de Giro must be present in view/inicio');
check(strpos($inicioFile, 'Auditoría de Órdenes de Giro') !== false, 'Auditoría de Órdenes de Giro must be present in view/inicio');
check(strpos($inicioFile, 'Consulta por Documento') === false, 'Consulta por Documento must NOT be present in view/inicio');
check(strpos($inicioFile, 'Consulta por Orden de Giro') === false, 'Consulta por Orden de Giro must NOT be present in view/inicio');
check(strpos($inicioFile, 'Conectar::esAdministrador()') !== false, 'Auditoría card must be guarded with Conectar::esAdministrador() in view/inicio');

// 2. Dynamic render simulation
function renderInicioModules($isAdmin) {
    $file = file_get_contents(__DIR__ . '/../view/inicio/index.php');
    // Extract the section aria-label="Módulos principales del sistema"
    preg_match('/<section aria-label="Módulos principales del sistema">([\s\S]*?)<\/section>/', $file, $matches);
    check(!empty($matches[1]), 'Modules section must be extractable');
    
    // Evaluate PHP in the context of simulated Conectar::esAdministrador()
    $code = $matches[1];
    if ($isAdmin) {
        $evaluated = preg_replace('/<\?php\s+if\s*\(Conectar::esAdministrador\(\)\)\s*\{\s*\?>([\s\S]*?)<\?php\s*\}\s*\?>/', '$1', $code);
    } else {
        $evaluated = preg_replace('/<\?php\s+if\s*\(Conectar::esAdministrador\(\)\)\s*\{\s*\?>([\s\S]*?)<\?php\s*\}\s*\?>/', '', $code);
    }
    return $evaluated;
}

$nonAdminModules = renderInicioModules(false);
check(strpos($nonAdminModules, 'Registrar Órdenes de Giro') !== false, 'Non-admin must see Registrar Órdenes de Giro card');
check(strpos($nonAdminModules, 'Auditoría de Órdenes de Giro') === false, 'Non-admin must NOT see Auditoría card');
check(strpos($nonAdminModules, 'Consulta por Documento') === false, 'Non-admin must NOT see Consulta por Documento');
check(strpos($nonAdminModules, 'Consulta por Orden de Giro') === false, 'Non-admin must NOT see Consulta por Orden de Giro');

$adminModules = renderInicioModules(true);
check(strpos($adminModules, 'Registrar Órdenes de Giro') !== false, 'Admin must see Registrar Órdenes de Giro card');
check(strpos($adminModules, 'Auditoría de Órdenes de Giro') !== false, 'Admin must see Auditoría card');
check(strpos($adminModules, 'Consulta por Documento') === false, 'Admin must NOT see Consulta por Documento');
check(strpos($adminModules, 'Consulta por Orden de Giro') === false, 'Admin must NOT see Consulta por Orden de Giro');

// 3. Static inspection of view/admin_giros_ciudadano/index.php
$adminGirosFile = file_get_contents(__DIR__ . '/../view/admin_giros_ciudadano/index.php');
check(preg_match('/if\s*\(\s*Conectar::esAdministrador\(\)\s*\)/', $adminGirosFile) === 1, 'admin_giros_ciudadano must be guarded by Conectar::esAdministrador()');

// 4. Static inspection of view/html/menu.php
$menuFile = file_get_contents(__DIR__ . '/../view/html/menu.php');
check(preg_match('/<\?php\s+if\s*\(\$isMntAllowed\)\s*\{\s*\?>\s*<a href="\.\.\/admin_giros_ciudadano\/"/', $menuFile) === 1, 'admin_giros_ciudadano link in menu must be guarded by $isMntAllowed');

echo "All inicio modules and admin_giros_ciudadano visibility tests passed successfully!\n";
