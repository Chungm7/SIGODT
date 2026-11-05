<?php
/**
 * Servicio de Envío de Correo Electrónico mediante API SIGUR (MPCH)
 * 100% PHP Nativo con cURL (Sin dependencias externas)
 * 
 * Municipalidad Provincial de Chiclayo
 */

class SigurMailer {

    /**
     * Envía el código de verificación de 6 dígitos mediante la API institucional de SIGUR.
     *
     * @param string $destinatario Correo electrónico destinatario
     * @param string $codigo       Código numérico de 6 dígitos
     * @param string $nombre       Nombre del trabajador/usuario
     * @param int    $expiraSeg    Segundos de validez (por defecto 120)
     * @return array ['success' => bool, 'message' => string, 'simulated' => bool]
     */
    public static function sendVerificationCode($destinatario, $codigo, $nombre = 'Servidor Municipal', $expiraSeg = 120) {
        $sistema = $_ENV['APP_NAME'] ?? 'SIGODT';
        $asunto  = "Código de Seguridad 2FA: {$codigo} - Municipalidad Provincial de Chiclayo";
        $mensaje = self::buildHtmlMessage($codigo, $nombre, $sistema, $expiraSeg);

        return self::enviarCorreoSigur($destinatario, $asunto, $mensaje);
    }

    /**
     * Envía una notificación de seguridad por correo alertando que se ha re-vinculado un nuevo dispositivo.
     *
     * @param string $destinatario Correo electrónico del usuario
     * @param string $nombre       Nombre completo del usuario
     * @return array ['success' => bool, 'message' => string, 'simulated' => bool]
     */
    public static function sendDeviceLinkedNotification($destinatario, $nombre = 'Servidor Municipal') {
        $sistema = $_ENV['APP_NAME'] ?? 'SIGODT';
        $asunto  = "Alerta de Seguridad: Nuevo dispositivo vinculado (2FA) - Municipalidad Provincial de Chiclayo";
        $mensaje = self::buildDeviceLinkedHtmlMessage($nombre, $sistema);

        return self::enviarCorreoSigur($destinatario, $asunto, $mensaje);
    }

    /**
     * Envía cualquier correo mediante los endpoints de SIGUR:
     * 1. POST sigur/auth/ -> Obtener Bearer Token institucional
     * 2. POST sigur/ws/correo-ss.php -> Enviar correo (contrato observado en SIINDI)
     *
     * @param string $destinatario Correo electrónico de destino
     * @param string $asunto       Título / Asunto del mensaje
     * @param string $mensajeHtml  Cuerpo del correo en formato HTML
     * @return array ['success' => bool, 'message' => string, 'simulated' => bool]
     */
    public static function enviarCorreoSigur($destinatario, $asunto, $mensajeHtml) {
        // SIGODT's existing SIGUR_URL already includes /sigur/; never append it twice.
        $baseUrl = rtrim((string)($_ENV['SIGUR_URL'] ?? ''), '/') . '/';
        $authUrl = $_ENV['SIGUR_AUTH_URL'] ?? ($baseUrl . 'auth/');
        $sistInic = $_ENV['SIST_INIC'] ?? '';
        $sistPass = $_ENV['SIST_PASS'] ?? '';
        $failure = [
            'success' => false,
            'message' => 'No se pudo enviar el correo de seguridad institucional.',
            'simulated' => false
        ];

        // Missing configuration is an error in every environment, not simulated delivery.
        if (!is_string($sistInic) || $sistInic === '' || !is_string($sistPass) || $sistPass === ''
            || !is_string($destinatario) || !filter_var($destinatario, FILTER_VALIDATE_EMAIL)
            || !is_string($asunto) || preg_match('/[\r\n]/', $asunto)
            || parse_url($baseUrl, PHP_URL_SCHEME) !== 'https'
            || !is_string($authUrl) || parse_url($authUrl, PHP_URL_SCHEME) !== 'https'
            || !parse_url($baseUrl, PHP_URL_HOST) || !parse_url($authUrl, PHP_URL_HOST)) {
            return $failure;
        }

        try {
            // 1. OBTENER TOKEN DE AUTENTICACIÓN EN SIGUR
            $authData = [
                'sist_inic' => $sistInic,
                'sist_pass' => $sistPass
            ];
            $ch = curl_init($authUrl);
            if ($ch === false) { return $failure; }
            try {
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($authData));
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
                $authResponse = curl_exec($ch);
                $authError = curl_errno($ch);
                $authHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            } finally {
                curl_close($ch);
            }
            if ($authResponse === false || $authError || $authHttpCode < 200 || $authHttpCode >= 300) {
                return $failure;
            }
            $authJson = json_decode($authResponse, true);
            $token = $authJson['data']['token'] ?? null;
            if (!is_string($token) || $token === '' || preg_match('/[\r\n]/', $token)) {
                return $failure;
            }

            // 2. DESPACHAR EL CORREO A TRAVÉS DE SIGUR
            $mailUrl = $baseUrl . 'ws/correo-ss.php';
            $postData = [
                'destino' => trim($destinatario),
                'titulo'  => trim($asunto),
                'mensaje' => $mensajeHtml
            ];
            $api = curl_init($mailUrl);
            if ($api === false) { return $failure; }
            try {
                curl_setopt($api, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($api, CURLOPT_POST, true);
                curl_setopt($api, CURLOPT_HTTPHEADER, [
                    'Authorization: Bearer ' . $token
                ]);
                // Preserve SIINDI's observed multipart field contract.
                curl_setopt($api, CURLOPT_POSTFIELDS, $postData);
                curl_setopt($api, CURLOPT_CONNECTTIMEOUT, 5);
                curl_setopt($api, CURLOPT_TIMEOUT, 15);
                curl_setopt($api, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($api, CURLOPT_SSL_VERIFYHOST, 2);
                curl_setopt($api, CURLOPT_FOLLOWLOCATION, false);
                $correoResponse = curl_exec($api);
                $correoError = curl_errno($api);
                $httpCode = curl_getinfo($api, CURLINFO_HTTP_CODE);
            } finally {
                curl_close($api);
            }
            if ($correoResponse === false || $correoError || $httpCode < 200 || $httpCode >= 300) {
                return $failure;
            }
            $correoJson = json_decode($correoResponse, true);
            if (is_array($correoJson) && ($correoJson['success'] ?? null) === true) {
                return [
                    'success' => true,
                    'message' => 'Correo enviado exitosamente.',
                    'simulated' => false
                ];
            }
        } catch (Throwable $e) {
            // No recipient, OTP, credentials, provider response or transport details in errors/logs.
            return $failure;
        }
        return $failure;
    }

    /**
     * Construye la plantilla HTML del correo adaptada al diseño institucional de MPCH.
     *
     * @param string $codigo    Código de 6 dígitos
     * @param string $nombre    Nombre del usuario
     * @param string $sistema   Nombre del sistema
     * @param int    $expiraSeg Segundos de validez
     * @return string Bloque HTML del mensaje
     */
    public static function buildHtmlMessage($codigo, $nombre, $sistema, $expiraSeg = 120) {
        $nombreSeguro = htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8');
        $sistemaSeguro = htmlspecialchars($sistema, ENT_QUOTES, 'UTF-8');
        $codigo = htmlspecialchars((string)$codigo, ENT_QUOTES, 'UTF-8');
        $expiraSeg = max(1, (int)$expiraSeg);
        $minutos = ceil($expiraSeg / 60);

        return <<<HTML
<tr>
    <td style="padding:30px;font-size:13px;color:#333;line-height:20px;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">

        <h2 style="color:#0b62b3;text-align:center;margin-top:0;margin-bottom:20px;">
            Autenticación de Doble Factor (2FA)
        </h2>

        <div style="text-align:left;">
            Estimado(a):
            <b>{$nombreSeguro}</b>
            <br><br>
        </div>

        <p style="text-align:justify;margin:0 0 15px 0;">
            La <b>Municipalidad Provincial de Chiclayo</b> le informa que se ha generado una solicitud de verificación para el acceso al sistema institucional <b>{$sistemaSeguro}</b>.
            <br><br>
            Su código de verificación de <b>6 dígitos</b> es:
            <br>
        </p>

        <div style="text-align:center;margin:25px 0;">
            <div style="display:inline-block;background-color:#f0f7ff;border:2px dashed #0b62b3;border-radius:8px;padding:12px 30px;">
                <span style="font-family:'SFMono-Regular',Consolas,monospace;font-size:32px;font-weight:bold;letter-spacing:8px;color:#0b62b3;">{$codigo}</span>
            </div>
        </div>

        <div style="background-color:#fff8e6;border-left:4px solid #f59e0b;padding:12px 15px;margin:20px 0;border-radius:4px;text-align:left;">
            <p style="margin:0;font-size:12px;color:#854d0e;line-height:18px;">
                ⏱️ <b>Tiempo de validez:</b> Este código es de uso único y expirará en <b>{$expiraSeg} segundos</b> ({$minutos} minutos). Pasado este tiempo, deberá solicitar un nuevo código.
            </p>
        </div>

        <p style="text-align:justify;margin:0 0 20px 0;">
            Por medidas de seguridad, no comparta este código con ninguna persona. Si usted no ha solicitado este acceso, comuníquese de inmediato con la Gerencia de Tecnología o el Administrador del Sistema.
            <br><br>
            Atentamente,
            <br><br>
        </p>

        <div style="text-align:center;">
            <b>Gerencia de Tecnología de la Información y Estadística</b>
            <br>
            Municipalidad Provincial de Chiclayo
        </div>

    </td>
</tr>
HTML;
    }

    /**
     * Construye la plantilla HTML para la alerta de seguridad por re-vinculación de dispositivo.
     *
     * @param string $nombre  Nombre del usuario
     * @param string $sistema Nombre del sistema
     * @return string Bloque HTML del mensaje
     */
    public static function buildDeviceLinkedHtmlMessage($nombre, $sistema) {
        $nombreSeguro = htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8');
        $sistemaSeguro = htmlspecialchars($sistema, ENT_QUOTES, 'UTF-8');
        $fecha = date('d/m/Y H:i:s');
        $ip = htmlspecialchars((string)($_SERVER['REMOTE_ADDR'] ?? 'No registrada'), ENT_QUOTES, 'UTF-8');

        return <<<HTML
<tr>
    <td style="padding:30px;font-size:13px;color:#333;line-height:20px;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">

        <h2 style="color:#0b62b3;text-align:center;margin-top:0;margin-bottom:20px;">
            Alerta de Seguridad (2FA)
        </h2>

        <div style="text-align:left;">
            Estimado(a):
            <b>{$nombreSeguro}</b>
            <br><br>
        </div>

        <p style="text-align:justify;margin:0 0 15px 0;">
            Le informamos que se ha completado satisfactoriamente la <b>vinculación de un nuevo dispositivo móvil</b> para la autenticación de doble factor (2FA) en el sistema institucional <b>{$sistemaSeguro}</b>.
        </p>

        <div style="background-color:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:15px;margin:20px 0;">
            <table style="width:100%;font-size:12px;color:#475569;border-collapse:collapse;">
                <tr>
                    <td style="padding:5px 0;width:120px;"><b>Fecha y Hora:</b></td>
                    <td style="padding:5px 0;">{$fecha}</td>
                </tr>
                <tr>
                    <td style="padding:5px 0;"><b>Dirección IP:</b></td>
                    <td style="padding:5px 0;">{$ip}</td>
                </tr>
                <tr>
                    <td style="padding:5px 0;"><b>Método:</b></td>
                    <td style="padding:5px 0;">Google Authenticator (RFC 6238)</td>
                </tr>
            </table>
        </div>

        <div style="background-color:#fef2f2;border-left:4px solid #ef4444;padding:12px 15px;margin:20px 0;border-radius:4px;text-align:left;">
            <p style="margin:0;font-size:12px;color:#991b1b;line-height:18px;">
                ⚠️ <b>¿No fuiste tú?</b> Si no has realizado esta acción, tu cuenta podría estar comprometida. Notifica de forma inmediata a la Gerencia de Tecnología o al Administrador del Sistema para revocar el acceso y proteger tu cuenta institucional.
            </p>
        </div>

        <p style="text-align:justify;margin:0 0 20px 0;">
            Si usted reconoció e inició este proceso, no necesita realizar ninguna acción adicional.
            <br><br>
            Atentamente,
            <br><br>
        </p>

        <div style="text-align:center;">
            <b>Gerencia de Tecnología de la Información y Estadística</b>
            <br>
            Municipalidad Provincial de Chiclayo
        </div>

    </td>
</tr>
HTML;
    }
}
