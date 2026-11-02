<?php
require_once __DIR__ . '/../config/conexion.php';

function check($condition, $message) {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

// 1. Verify existence of spinner component and logo asset
$spinnerFile = __DIR__ . '/../view/html/spinner.php';
check(file_exists($spinnerFile), 'view/html/spinner.php must exist');

$logoPath = __DIR__ . '/../public/componente-spinner/public/img/logo_mpch.png';
check(file_exists($logoPath), 'public/componente-spinner/public/img/logo_mpch.png must exist');
check(filesize($logoPath) > 1000, 'logo_mpch.png must not be empty');

// 2. Verify contents of spinner.php
$content = file_get_contents($spinnerFile);

check(strpos($content, 'id="spinnerOverlay"') !== false, 'spinnerOverlay must be present');
check(strpos($content, 'id="wifi-loader"') !== false, 'wifi-loader must be present');
check(strpos($content, 'class="imgMuni-background"') !== false, 'imgMuni-background must be present');
check(strpos($content, 'function showSpinner()') !== false, 'showSpinner() function must be defined');
check(strpos($content, 'function hideSpinner()') !== false, 'hideSpinner() function must be defined');
check(strpos($content, 'processing.dt') !== false, 'processing.dt DataTables integration must be present');
check(strpos($content, 'div.dataTables_processing') !== false, 'CSS hiding default dataTables_processing must be present');

// 3. Verify mainjs.php includes spinner.php
$mainjs = file_get_contents(__DIR__ . '/../view/html/mainjs.php');
check(strpos($mainjs, 'spinner.php') !== false, 'view/html/mainjs.php must include spinner.php');

// 4. Verify municipal-theme.css hides default dataTables_processing
$themeCss = file_get_contents(__DIR__ . '/../public/css/municipal-theme.css');
check(strpos($themeCss, 'div.dataTables_processing') !== false, 'municipal-theme.css must hide div.dataTables_processing');

// 5. Simulate execution/render of spinner.php
ob_start();
include $spinnerFile;
$rendered = ob_get_clean();

check(strpos($rendered, 'id="spinnerOverlay"') !== false, 'Rendered spinner must output spinnerOverlay element');
check(strpos($rendered, 'public/componente-spinner/public/img/logo_mpch.png') !== false, 'Rendered spinner must link to logo_mpch.png');

echo "All MPCH Institutional Spinner tests passed successfully!\n";
