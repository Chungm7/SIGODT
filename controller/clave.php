<?php
/**
 * Controlador de Gestión de Claves y Reseteo — portado de SIINDI.
 * Autoridad de OTP y contraseñas exclusivamente en SIGUR; contexto temporal en PHP.
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/conexion.php';
Conectar::initSession();
require_once __DIR__ . '/../helpers/SiindiSessionBridge.php';

/** Accept either configured host root or the existing /sigur/ base convention. */
function sigurPasswordBase(): string {
    $base = rtrim(Conectar::getEnv('SIGUR_URL', ''), '/');
    return preg_match('~/sigur$~', $base) ? $base . '/' : $base . '/sigur/';
}

function sigurPasswordRequest(string $url, array $fields, ?string $token = null, int $timeout = 20): ?array {
    if (!filter_var($url, FILTER_VALIDATE_URL) || parse_url($url, PHP_URL_SCHEME) !== 'https'
        || parse_url($url, PHP_URL_USER) !== null || parse_url($url, PHP_URL_PASS) !== null) return null;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
    if ($token !== null) curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Content-Type: application/x-www-form-urlencoded']);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    $body = curl_exec($ch);
    $ok = !curl_errno($ch) && curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200;
    curl_close($ch);
    $data = $ok ? json_decode(trim((string)$body, "\xEF\xBB\xBF \t\n\r\0\x0B"), true) : null;
    return is_array($data) ? $data : null;
}

/** Only explicit values observed in the original client, never bare HTTP 2xx. */
function sigurPasswordAcknowledged(?array $data): bool {
    if (!$data) return false;
    if (array_key_exists('success', $data)) return in_array($data['success'], [true, 'true', 1, '1'], true);
    return in_array($data['status'] ?? null, [200, '200', true], true);
}

if (defined('SIINDI_PASSWORD_LIBRARY_ONLY') && SIINDI_PASSWORD_LIBRARY_ONLY) return;
$sigur_url = sigurPasswordBase();

/** Obtiene el Bearer Token institucional del sistema ante SIGUR. */
function getSigurSystemToken($baseUrl) {
    $authUrl = Conectar::getEnv('SIGUR_AUTH_URL', $baseUrl . 'auth/');
    $inic = Conectar::getEnv('SIST_INIC', '');
    $pass = Conectar::getEnv('SIST_PASS', '');
    if (!$inic || !$pass || !filter_var($authUrl, FILTER_VALIDATE_URL) || parse_url($authUrl, PHP_URL_SCHEME) !== 'https' || parse_url($authUrl, PHP_URL_USER) !== null) { return null; }
    $json = sigurPasswordRequest($authUrl, ['sist_inic'=>$inic, 'sist_pass'=>$pass], null, 15);
    $token = $json['data']['token'] ?? null;
    return is_string($token) && $token !== '' && !preg_match('/[\r\n]/', $token) ? $token : null;
}

$op = $_GET['op'] ?? '';
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !is_string($_SESSION['csrf_token'] ?? null) || strlen($_SESSION['csrf_token']) !== 64 || !is_string($_POST['csrf_token'] ?? null) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success'=>false, 'message'=>'Solicitud no autorizada.']);
    exit;
}
switch ($op) {
    case 'actualizar':
        if (empty($_SESSION['usua_id_SIGODT']) || empty($_SESSION['sigur_usuario_id_SIGODT']) || empty($_SESSION['usua_dni_SIGODT'])) {
            echo json_encode(['success'=>false, 'message'=>'Sesión no válida o expirada.']);
            break;
        }
        $usu_doc = $_SESSION['usua_dni_SIGODT'];
        $usu_tipo = 'P';
        $usu_pass_actual = $_POST['usu_pass_actual'] ?? '';
        $usu_pass_nueva = $_POST['usu_pass_nueva'] ?? '';
        if (!is_string($usu_pass_actual) || !is_string($usu_pass_nueva) || $usu_pass_actual === '' || $usu_pass_nueva === '' || strlen($usu_pass_actual) > 1024 || strlen($usu_pass_nueva) > 1024) {
            echo json_encode(['success'=>false, 'message'=>'Debe ingresar su contraseña actual y la nueva contraseña.']);
            break;
        }
        if (!filter_var($sigur_url, FILTER_VALIDATE_URL) || parse_url($sigur_url, PHP_URL_SCHEME) !== 'https' || parse_url($sigur_url, PHP_URL_USER) !== null || !($token = getSigurSystemToken($sigur_url))) {
            echo json_encode(['success'=>false, 'message'=>'No se pudo obtener autorización del servidor de seguridad SIGUR.']);
            break;
        }
        $postData = compact('usu_doc', 'usu_tipo', 'usu_pass_actual', 'usu_pass_nueva');
        $ch = curl_init($sigur_url . 'controller/clave.php?op=actualizar');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Content-Type: application/x-www-form-urlencoded']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        $resp = curl_exec($ch);
        $ok = !curl_errno($ch) && curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200;
        curl_close($ch);
        $jsonResp = $ok ? json_decode((string)$resp, true) : null;
        if (is_array($jsonResp) && ($jsonResp['success'] ?? null) === true) {
            echo json_encode(['success'=>true, 'message'=>'Contraseña actualizada correctamente.', 'enlace'=>Conectar::ruta() . 'view/setting/']);
        } else {
            echo json_encode(['success'=>false, 'message'=>'No se pudo actualizar la contraseña en el servidor de seguridad.']);
        }
        break;
    case 'blanqueamiento':
    case 'token':
    case 'validar_token':
        $context = SiindiSessionBridge::passwordContext();
        $stages = ['token'=>['recovery','recovery_sent'], 'validar_token'=>['recovery_sent'], 'blanqueamiento'=>['first_access']];
        if (!$context || !in_array($context['stage'], $stages[$op], true)) {
            http_response_code(403);
            echo json_encode(['success'=>false,'message'=>'Sesión no válida o expirada.']);
            break;
        }
        $field = ['token'=>'metodo','validar_token'=>'codigo','blanqueamiento'=>'pers_pass_nueva'][$op];
        $value = $_POST[$field] ?? null;
        $valid = is_string($value) && match ($op) {
            'token'=>in_array($value, ['1','2'], true),
            'validar_token'=>preg_match('/^[A-Za-z0-9]{6}$/D', $value) === 1,
            default=>$value !== '' && strlen($value) <= 1024,
        };
        if (!$valid) {
            http_response_code(400);
            echo json_encode(['success'=>false,'message'=>'Debe completar los campos obligatorios.']);
            break;
        }
        $fields = ['usu_doc'=>$context['identity']['usu_doc'], 'usu_tipo'=>'P', $field=>$op === 'validar_token' ? strtoupper($value) : $value];
        $token = getSigurSystemToken($sigur_url);
        $response = $token ? sigurPasswordRequest($sigur_url . 'controller/clave.php?op=' . $op, $fields, $token) : null;
        if (!sigurPasswordAcknowledged($response)) {
            echo json_encode(['success'=>false,'message'=>'No se pudo completar la operación en el servidor de seguridad.']);
            break;
        }
        $result = ['success'=>true,'message'=>'Operación completada correctamente.'];
        if ($op === 'token' && $context['stage'] === 'recovery') SiindiSessionBridge::advancePasswordContext('recovery_sent');
        if ($op === 'validar_token') {
            SiindiSessionBridge::advancePasswordContext('first_access');
            $result['enlace'] = $result['redirect'] = Conectar::ruta() . 'seguridad/primer_acceso/views/';
        }
        if ($op === 'blanqueamiento') {
            SiindiSessionBridge::clearPasswordContext();
            $result['enlace'] = $result['redirect'] = Conectar::ruta() . 'index.php';
        }
        echo json_encode($result);
        break;
    case 'regresar':
        echo json_encode(['success'=>true, 'enlace'=>Conectar::ruta() . 'index.php']);
        break;
    case 'regresar_actualizar':
        echo json_encode(['success'=>true, 'enlace'=>Conectar::ruta() . 'view/setting/']);
        break;
    default:
        echo json_encode(['success'=>false, 'message'=>'Operación de seguridad no reconocida.']);
}
