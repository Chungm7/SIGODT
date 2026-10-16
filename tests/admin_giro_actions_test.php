<?php
// Exercise the controller's public listing response using read-only model fixtures.
class Bitacora {}
class Procedimiento {
    public static $rows = [];
    public function get_procedciudadano_id_admin($id) { return self::$rows; }
}
function check($condition, $message) {
    if (!$condition) throw new RuntimeException($message);
}
$base = ['proceciudadano_id' => 42, 'procedciudadano_cod' => 'T-42', 'ciudadano_dni' => '12345678', 'nombre_completo' => 'Test Citizen', 'fechacrea' => '2026-10-16 10:00:00', 'pagadas' => 1, 'total' => 2];
foreach ([1, 2, 3] as $state) Procedimiento::$rows[] = $base + ['est' => $state];
$_GET = ['op' => 'listar_proced_ciudadano'];
$_POST = ['proced_id' => 8];
$source = file_get_contents(__DIR__ . '/../controller/procedimiento.php');
$source = preg_replace('/require_once\([^;]+;/', '', $source);
ob_start();
eval('?>' . $source);
$output = ob_get_clean();
$response = json_decode($output, true, 512, JSON_THROW_ON_ERROR);
check($response['iTotalRecords'] === 3 && $response['iTotalDisplayRecords'] === 3, 'Listing counts remain unchanged');
foreach ($response['aaData'] as $index => $row) {
    check(strpos(implode('', $row), 'setProcedencia') === false, 'Listing must not offer a procedure state toggle');
    check(count($row) === 9, 'Listing matches nine visible columns');
    check(array_slice($row, 0, 4) === ['T-42', '12345678', 'Test Citizen', '2026-10-16 10:00:00'], 'Identity and date remain unchanged');
    check(strpos($row[5], '50.00%') !== false, 'Progress remains unchanged');
    foreach ([6 => 'editar(42)', 7 => 'eliminar(42)', 8 => 'ver(42)'] as $column => $action) {
        check(strpos($row[$column], $action) !== false, 'Existing action retains aligned column: ' . $action);
    }
    check(strpos($row[8], 'data-estado="' . [1, 2, 3][$index] . '"') !== false, 'Rate action retains historical state');
}
check(strpos($response['aaData'][2][4], 'Improcedente') !== false, 'Historical state remains readable');
$view = file_get_contents(__DIR__ . '/../view/admin_giros_ciudadano/index.php');
check(strpos($view, '>Procedencia</th>') === false, 'Obsolete header is removed');
$script = file_get_contents(__DIR__ . '/../view/admin_giros_ciudadano/admindetalleciudadano.js');
check(strpos($script, 'setProcedencia') === false, 'Obsolete click handler is removed');
echo "Administrative giro action tests passed.\n";
