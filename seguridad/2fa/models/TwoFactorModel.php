<?php
/**
 * Modelo de Acceso a Datos para el Módulo de Doble Factor (2FA)
 * Motor: PostgreSQL (utilizando la clase Conectar del proyecto)
 * Nomenclatura oficial: tb_seguridad_usuario_2fa (seus_) y tb_seguridad_auditoria_login_2fa (seal_)
 * 
 * Municipalidad Provincial de Chiclayo (MPCH)
 */

if (file_exists(__DIR__ . '/../../../config/conexion.php')) {
    require_once __DIR__ . '/../../../config/conexion.php';
} else {
    require_once __DIR__ . '/../../config/conexion.php';
}
require_once __DIR__ . '/../services/TwoFactorService.php';

class TwoFactorModel {

    /**
     * Obtiene el registro 2FA de un usuario por su DNI (seus_usuario_doc).
     */
    public static function getRecordByDoc($usu_doc) {
        try {
            $con = (new Conectar())->conexion();
            $stmt = $con->prepare("
                SELECT seus_id, seus_usuario_id, seus_usuario_doc, seus_llave_secr, 
                       seus_esta_vinc, seus_enrol_conf, 
                       seus_marca_tiem, seus_intentos_fall, seus_bloqueo_temp
                FROM public.tb_seguridad_usuario_2fa
                WHERE seus_usuario_doc = :usu_doc
                LIMIT 1
            ");
            $stmt->execute([':usu_doc' => trim($usu_doc)]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Exception $e) {
            error_log("[TwoFactorModel::getRecordByDoc] Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Verifica si el usuario tiene el 2FA activado y si se encuentra bloqueado por fuerza bruta.
     * @return array ['enabled' => bool, 'locked' => bool, 'lock_minutes' => int, 'data' => array|null]
     */
    public static function checkStatus($usu_doc) {
        $record = self::getRecordByDoc($usu_doc);
        if (!$record || empty($record['seus_esta_vinc'])) {
            return [
                'enabled'      => false,
                'locked'       => false,
                'lock_minutes' => 0,
                'data'         => null
            ];
        }

        // Verificar si está bloqueado temporalmente
        $isLocked = false;
        $lockMinutes = 0;
        if (!empty($record['seus_bloqueo_temp'])) {
            $lockedUntilTs = strtotime($record['seus_bloqueo_temp']);
            $nowTs = time();
            if ($lockedUntilTs > $nowTs) {
                $isLocked = true;
                $lockMinutes = ceil(($lockedUntilTs - $nowTs) / 60);
            }
        }

        return [
            'enabled'      => true,
            'locked'       => $isLocked,
            'lock_minutes' => $lockMinutes,
            'data'         => $record
        ];
    }

    /**
     * Inicia o reinicia el proceso de enrolamiento guardando el secreto cifrado con seus_esta_vinc = FALSE.
     * Guarda directamente seus_usuario_id y seus_usuario_doc provenientes de SIGUR.
     */
    public static function initEnrollment($usu_id, $usu_doc, $plainSecret) {
        try {
            $con = (new Conectar())->conexion();
            $encryptedSecret = TwoFactorService::encryptSecret($plainSecret);

            // Upsert (Insertar o actualizar si ya existe por seu_usuario_doc)
            $stmt = $con->prepare("
                INSERT INTO public.tb_seguridad_usuario_2fa (
                    seus_usuario_id, seus_usuario_doc, seus_llave_secr, seus_esta_vinc, 
                    seus_enrol_conf, seus_intentos_fall, 
                    seus_bloqueo_temp, seus_updated_at
                ) VALUES (
                    :usu_id, :usu_doc, :secret_key, FALSE, 
                    NULL, 0, 
                    NULL, CURRENT_TIMESTAMP
                )
                ON CONFLICT (seus_usuario_doc) DO UPDATE SET
                    seus_usuario_id   = EXCLUDED.seus_usuario_id,
                    seus_llave_secr   = EXCLUDED.seus_llave_secr,
                    seus_esta_vinc    = FALSE,
                    seus_enrol_conf   = NULL,
                    seus_marca_tiem   = 0,
                    seus_intentos_fall = 0,
                    seus_bloqueo_temp = NULL,
                    seus_updated_at   = CURRENT_TIMESTAMP
            ");

            return $stmt->execute([
                ':usu_id'     => $usu_id,
                ':usu_doc'    => $usu_doc,
                ':secret_key' => $encryptedSecret
            ]);
        } catch (Exception $e) {
            error_log("[TwoFactorModel::initEnrollment] Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Consulta el correo institucional del trabajador en el esquema sc_escalafon.
     */
    public static function getEmailByDni($dni) {
        try {
            $con = (new Conectar())->conexion();
            $stmt = $con->prepare("SELECT pers_emailm FROM sc_escalafon.tb_persona WHERE pers_dni = :dni LIMIT 1");
            $stmt->execute([':dni' => trim($dni)]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return !empty($row['pers_emailm']) ? trim($row['pers_emailm']) : null;
        } catch (Exception $e) {
            error_log("[TwoFactorModel::getEmailByDni] Error al consultar sc_escalafon.tb_persona: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Confirma el enrolamiento exitoso y activa seus_esta_vinc = TRUE.
     */
    public static function confirmEnrollment($usu_doc) {
        try {
            $con = (new Conectar())->conexion();
            $stmt = $con->prepare("
                UPDATE public.tb_seguridad_usuario_2fa
                SET seus_esta_vinc     = TRUE,
                    seus_enrol_conf    = CURRENT_TIMESTAMP,
                    seus_intentos_fall = 0,
                    seus_bloqueo_temp  = NULL,
                    seus_updated_at    = CURRENT_TIMESTAMP
                WHERE seus_usuario_doc = :usu_doc
            ");

            return $stmt->execute([
                ':usu_doc' => $usu_doc
            ]);
        } catch (Exception $e) {
            error_log("[TwoFactorModel::confirmEnrollment] Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Registra un inicio de sesión 2FA exitoso (resetea fallos y actualiza timestamp anti-replay).
     */
    public static function recordSuccess($usu_doc, $usedTimestamp) {
        try {
            $con = (new Conectar())->conexion();
            $stmt = $con->prepare("
                UPDATE public.tb_seguridad_usuario_2fa
                SET seus_intentos_fall = 0,
                    seus_bloqueo_temp  = NULL,
                    seus_marca_tiem    = :last_ts,
                    seus_updated_at    = CURRENT_TIMESTAMP
                WHERE seus_usuario_doc = :usu_doc
            ");
            return $stmt->execute([
                ':usu_doc' => $usu_doc,
                ':last_ts' => $usedTimestamp
            ]);
        } catch (Exception $e) {
            error_log("[TwoFactorModel::recordSuccess] Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Registra un intento fallido y activa bloqueo por 15 minutos si llega a 5 fallos.
     * @return array ['attempts' => int, 'locked' => bool, 'lock_minutes' => int]
     */
    public static function recordFailedAttempt($usu_doc) {
        try {
            $con = (new Conectar())->conexion();
            
            // Incrementar contador
            $stmt = $con->prepare("
                UPDATE public.tb_seguridad_usuario_2fa
                SET seus_intentos_fall = seus_intentos_fall + 1,
                    seus_bloqueo_temp = CASE 
                        WHEN seus_intentos_fall + 1 >= 5 THEN CURRENT_TIMESTAMP + INTERVAL '15 minutes'
                        ELSE seus_bloqueo_temp 
                    END,
                    seus_updated_at = CURRENT_TIMESTAMP
                WHERE seus_usuario_doc = :usu_doc
                RETURNING seus_intentos_fall, seus_bloqueo_temp
            ");
            $stmt->execute([':usu_doc' => $usu_doc]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($res) {
                $attempts = (int)$res['seus_intentos_fall'];
                $isLocked = !empty($res['seus_bloqueo_temp']) && strtotime($res['seus_bloqueo_temp']) > time();
                return [
                    'attempts'     => $attempts,
                    'locked'       => $isLocked,
                    'lock_minutes' => $isLocked ? 15 : 0
                ];
            }
        } catch (Exception $e) {
            error_log("[TwoFactorModel::recordFailedAttempt] Error: " . $e->getMessage());
        }

        return ['attempts' => 1, 'locked' => false, 'lock_minutes' => 0];
    }

    /**
     * Actualiza la lista de códigos de respaldo tras el uso de uno de ellos.
     */
    public static function updateRecoveryCodes($usu_doc, array $updatedCodes) {
        try {
            $con = (new Conectar())->conexion();
            $stmt = $con->prepare("
                UPDATE public.tb_seguridad_usuario_2fa
                SET seus_codigos_resp = :codes::jsonb,
                    seus_intentos_fall = 0,
                    seus_bloqueo_temp = NULL,
                    seus_updated_at   = CURRENT_TIMESTAMP
                WHERE seus_usuario_doc = :usu_doc
            ");
            return $stmt->execute([
                ':usu_doc' => $usu_doc,
                ':codes'   => json_encode($updatedCodes)
            ]);
        } catch (Exception $e) {
            error_log("[TwoFactorModel::updateRecoveryCodes] Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Desactiva el 2FA del usuario (requiere re-enrolamiento si desea reactivarlo).
     */
    public static function disable2FA($usu_doc) {
        try {
            $con = (new Conectar())->conexion();
            $stmt = $con->prepare("
                UPDATE public.tb_seguridad_usuario_2fa
                SET seus_esta_vinc    = FALSE,
                    seus_llave_secr   = '',
                    seus_intentos_fall = 0,
                    seus_bloqueo_temp = NULL,
                    seus_updated_at   = CURRENT_TIMESTAMP
                WHERE seus_usuario_doc = :usu_doc
            ");
            return $stmt->execute([':usu_doc' => $usu_doc]);
        } catch (Exception $e) {
            error_log("[TwoFactorModel::disable2FA] Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Registra un evento en la tabla inmutable de auditoría.
     */
    public static function logAudit($usu_id, $usu_doc, $evento, $detalles = []) {
        try {
            $con = (new Conectar())->conexion();
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Desconocido';

            $stmt = $con->prepare("
                INSERT INTO public.tb_seguridad_auditoria_login_2fa (
                    seal_usuario_id, seal_usuario_doc, seal_evento, 
                    seal_direccion_ip, seal_agente_usua, seal_detalles, seal_created_at
                ) VALUES (
                    :usu_id, :usu_doc, :evento, 
                    :ip, :ua, :detalles::jsonb, CURRENT_TIMESTAMP
                )
            ");
            $stmt->execute([
                ':usu_id'   => $usu_id ?: null,
                ':usu_doc'  => $usu_doc,
                ':evento'   => $evento,
                ':ip'       => substr($ip, 0, 45),
                ':ua'       => $ua,
                ':detalles' => json_encode($detalles)
            ]);
        } catch (Exception $e) {
            error_log("[TwoFactorModel::logAudit] Error al registrar auditoría: " . $e->getMessage());
        }
    }
}
