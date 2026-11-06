# Estructura y Guía de Integración: Módulo Desacoplado 2FA Multi-Método

**Entidad:** Municipalidad Provincial de Chiclayo (MPCH)  
**Sistema:** `sisIndicadores` / Módulo Temporal Portable  
**Destino Futuro:** Core Central de Seguridad (**SIGUR**)  
**Versión:** 2.0 (Octubre 2026) — Acceso por Correo Institucional & Google Authenticator  

---

## 1. Organización de Carpetas del Módulo (`modulo_2fa/`)

Todo el desarrollo se encuentra concentrado de manera autónoma dentro de la carpeta `modulo_2fa/` para que no genere acoplamiento ni dependencias rígidas:

```text
modulo_2fa/
│
├── database/
│   └── script_bd_2fa.sql             <- Script DDL PostgreSQL (tablas public.tb_seguridad_*)
│
├── services/
│   ├── TwoFactorService.php          <- Motor criptográfico puro: TOTP RFC 6238, Base32, AES-256-GCM
│   ├── SigurMailer.php               <- Cliente de envío de correos vía API SIGUR (auth + ws/correo.php)
│   └── SmtpMailer.php                <- Wrapper de compatibilidad para SigurMailer
│
├── models/
│   └── TwoFactorModel.php            <- Modelo PDO: consulta a escalafón (solo lectura), auditoría y bloqueos
│
├── controllers/
│   └── TwoFactorController.php       <- Endpoints AJAX: solicitar_codigo_correo, verificar_codigo_correo,
│                                        verificar_desafio, iniciar_enrolamiento, confirmar_enrolamiento
│
├── views/
│   ├── selector_metodo.php           <- Selector inicial con 2 tarjetas (Correo Institucional vs Dispositivo)
│   ├── desafio_login.php             <- Pantalla de ingreso del código de 6 dígitos de Google Authenticator
│   ├── enrolar_login.php             <- Enrolamiento horizontal estilo libro (sin códigos de respaldo)
│   ├── modal_enrolamiento.php        <- Modal horizontal reutilizable (ej. para re-vincular)
│   └── perfil_2fa.php                <- Componente embebible para la sección de Perfil del Usuario
│
├── assets/
│   ├── css/
│   │   └── two_factor.css            <- Estilos para tarjetas, temporizador de 120s, libro e inputs gigantes
│   └── js/
│       ├── qrcode.min.js             <- Generación local de QR en Canvas (David Shim)
│       └── two_factor.js             <- Lógica cliente 100% local (temporizadores, AJAX, portapapeles)
│
├── README.md                         <- Documentación completa de integración
└── README_ESTRUCTURA.md              <- Este documento explicativo
```

---

## 2. Respuestas a Preguntas Clave de Arquitectura

### A. ¿Se necesita una cuenta de Google para usar Google Authenticator?
> [!NOTE]
> **NO, en lo absoluto.**  
> Ni el funcionario ni la municipalidad necesitan cuenta de Google, ni correos Gmail, ni Google Workspace, ni pagar licencias.  
> La app calcula el código de 6 dígitos matemáticamente utilizando el reloj interno de su procesador y la clave secreta que leyó del QR (estándar RFC 6238). Funciona incluso sin internet ni datos móviles.

### B. ¿Cómo funciona el acceso por Correo Institucional?
1. Consulta el correo institucional en la tabla de personas de escalafón:
   ```sql
   SELECT pers_emailm FROM sc_escalafon.tb_persona WHERE pers_dni = 'DNI';
   ```
2. Genera un código OTP de **6 dígitos puramente numéricos** con vigencia estricta de **120 segundos**.
3. Se envía mediante `SigurMailer.php` a través de la API oficial de SIGUR (`sigur/auth/` y `sigur/ws/correo.php`). Si aún no están configuradas las credenciales o en pruebas locales, entra automáticamente en **modo simulación** para no trabar el desarrollo ni las pruebas.
4. Al ingresar los 6 dígitos dentro de los 120s, ingresa inmediatamente al sistema.

### C. ¿Qué pasó con los códigos de respaldo?
Fueron **eliminados por completo**. Ahora la recuperación es autoservicio mediante la validación por **Correo Institucional**: si el usuario cambió de teléfono o lo extravió, pulsa "¿Cambiaste de teléfono? Vincular nuevo dispositivo", valida su identidad mediante el código de 6 dígitos que le llega a su correo y automáticamente se le abre el modal para escanear un nuevo código QR con su nuevo celular.

---

## 3. Instrucciones para el Administrador de Base de Datos (DBA)

El script SQL actualizado se encuentra en:  
[`modulo_2fa/database/script_bd_2fa.sql`](file:///home/italo/PhpstormProjects/sisIndicadores/modulo_2fa/database/script_bd_2fa.sql)

### Si ya habías ejecutado el script anterior:
No necesitas borrar nada. Solo pídele al DBA que ejecute:
```sql
ALTER TABLE public.tb_seguridad_usuario_2fa DROP COLUMN IF EXISTS seus_codigos_resp;
ALTER TABLE public.tb_seguridad_auditoria_login_2fa ALTER COLUMN seal_evento TYPE VARCHAR(60);
```

### Eventos de Auditoría (100% en Español):
- `SOLICITUD_CODIGO_CORREO`
- `VERIFICACION_EXITOSA_CORREO`
- `VERIFICACION_FALLIDA_CORREO`
- `VERIFICACION_EXITOSA_DISPOSITIVO`
- `VERIFICACION_FALLIDA_DISPOSITIVO`
- `VINCULACION_DISPOSITIVO_INICIADA`
- `PRIMERA_VINCULACION_DISPOSITIVO`
- `SOLICITUD_RE_VINCULACION_DISPOSITIVO`
- `RE_VINCULACION_CONFIRMADA`
- `BLOQUEO_TEMPORAL`
- `CIERRE_SESION`

---

## 4. Cómo Activar / Desactivar el Módulo (Cero Dependencias)

En [models/Auth.php](file:///home/italo/PhpstormProjects/sisIndicadores/models/Auth.php):

```php
// ========================================================
// GANCHO MODULAR 2FA (FASE AISLADA)
// ========================================================
$local2faFile = __DIR__ . '/../modulo_2fa/models/TwoFactorModel.php';
if (file_exists($local2faFile)) {
    try {
        require_once $local2faFile;
        $tokenPending = $jwt->encrypt($param, '2fa_pending', 600); // 10 minutos
        $jwt->setCookie("2fa_pending", $tokenPending, 0, "/" . $_ENV["APP_NAME"] . "/");
        $jwt->setCookie("2fa_pending", $tokenPending, 0, "/");
        $_SESSION['2fa_pending_user'] = (array)$param;

        header("Location: " . Conectar::ruta() . 'modulo_2fa/views/selector_metodo.php');
        exit();
    } catch (Exception $e) {
        error_log("[Auth] Error 2FA modular: " . $e->getMessage());
    }
}
```

* **Para desactivar:** Simplemente se comenta o elimina este bloque de 10 líneas.

---

**Municipalidad Provincial de Chiclayo — Gerencia de Tecnología y Sistemas de Información**
