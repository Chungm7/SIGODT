<?php
/**
 * Servicio de Envío de Correo Electrónico (Wrapper de compatibilidad)
 * Redirige llamadas a SigurMailer para utilizar la API central de correo de SIGUR (MPCH).
 * 
 * Municipalidad Provincial de Chiclayo
 */

require_once __DIR__ . '/SigurMailer.php';

class SmtpMailer {

    /**
     * Envía el código de verificación de 6 dígitos mediante la API institucional de SIGUR.
     * Mantiene compatibilidad con implementaciones anteriores.
     */
    public static function sendVerificationCode($destinatario, $codigo, $nombre = 'Servidor Municipal', $expiraSeg = 120) {
        return SigurMailer::sendVerificationCode($destinatario, $codigo, $nombre, $expiraSeg);
    }

    /**
     * Envía notificación por re-vinculación de nuevo dispositivo.
     */
    public static function sendDeviceLinkedNotification($destinatario, $nombre = 'Servidor Municipal') {
        return SigurMailer::sendDeviceLinkedNotification($destinatario, $nombre);
    }
}
