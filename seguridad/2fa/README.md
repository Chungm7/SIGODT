# Módulo de Autenticación de Doble Factor (2FA Multi-Método)
### Municipalidad Provincial de Chiclayo (MPCH)
**Versión 2.0 (Octubre 2026) — Arquitectura de Doble Acceso: Correo Institucional & Google Authenticator**

Guía técnica integral y documentación para desarrolladores. Este módulo es **100% portable, desacoplado y autónomo**. Para implementarlo en cualquier sistema de la municipalidad (satélites de SIGUR), basta con copiar la carpeta `modulo_2fa/` e integrar un gancho de pocas líneas en el login.

---

## 📑 Tabla de Contenidos
1. [Flujo Funcional de Acceso](#1-flujo-funcional-de-acceso)
   - [Selector de Método (Tarjetas visuales)](#a-selector-de-método-tarjetas-visuales)
   - [Método 1: Correo Institucional (Código OTP 6 dígitos en 120s)](#b-método-1-correo-institucional-código-otp-6-dígitos-en-120s)
   - [Método 2: Dispositivo Móvil (Google / Microsoft Authenticator)](#c-método-2-dispositivo-móvil-google--microsoft-authenticator)
   - [Re-vinculación de Celular por Pérdida o Cambio](#d-re-vinculación-de-celular-por-pérdida-o-cambio)
2. [Estructura de Archivos del Módulo](#2-estructura-de-archivos-del-módulo)
3. [Guía de Integración Rápida en Otros Proyectos (10 Minutos)](#3-guía-de-integración-rápida-en-otros-proyectos-10-minutos)
4. [Base de Datos y Auditoría Pura](#4-base-de-datos-y-auditoría-pura)
   - [Esquema de Tablas (PostgreSQL public)](#a-esquema-de-tablas-postgresql-public)
   - [Estados de Auditoría en Español (seal_evento)](#b-estados-de-auditoría-en-español-seal_evento)
   - [Estructura de Trazabilidad JSON (seal_detalles)](#c-estructura-de-trazabilidad-json-seal_detalles)
   - [Instrucciones para el Administrador de Base de Datos (DBA)](#d-instrucciones-para-el-administrador-de-base-de-datos-dba)
5. [Servicio de Correo Institucional SIGUR (SigurMailer)](#5-servicio-de-correo-institucional-sigur-sigurmailer)
   - [Modo Simulación / Pruebas Locales](#a-modo-simulación--pruebas-locales)
   - [Configuración en Producción](#b-configuración-en-producción)
6. [Seguridad Criptográfica y Preguntas Frecuentes](#6-seguridad-criptográfica-y-preguntas-frecuentes)

---

## 1. Flujo Funcional de Acceso

El módulo entra en acción inmediatamente después de que el usuario ingresa sus credenciales en el login y SIGUR valida que su usuario y contraseña son correctos.

### A. Selector de Método (Tarjetas visuales)
Al superar el login inicial, el sistema redirige a `modulo_2fa/views/selector_metodo.php`, donde se despliegan **2 tarjetas interactivas**:
1. **Ingresar por Correo Institucional**:
   - Pensado para funcionarios que no disponen de su celular en el momento o prefieren validar su acceso desde su bandeja de correo oficial `@munichiclayo.gob.pe`.
2. **Ingresar con Dispositivo Móvil**:
   - Valida la identidad mediante la aplicación móvil *Google Authenticator* o *Microsoft Authenticator* (estándar RFC 6238 TOTP).

---

### B. Método 1: Correo Institucional (Código OTP 6 dígitos en 120s)
1. El sistema realiza una consulta de **solo lectura** al esquema de escalafón municipal:
   ```sql
   SELECT pers_emailm FROM sc_escalafon.tb_persona WHERE pers_dni = 'DNI_DEL_USUARIO' LIMIT 1;
   ```
2. Genera un **código numérico criptográficamente seguro de 6 dígitos** (ej: `482910`).
3. Despacha un correo formal con la identidad gráfica de la MPCH.
4. En pantalla se abre un modal con un **contador regresivo de 120 segundos** (2 minutos) con barra de progreso animada.
5. Si el usuario ingresa el código correcto dentro de los 120 segundos:
   - Se emite su cookie de sesión definitiva JWT.
   - El sistema lo redirige de inmediato a `view/inicio/`.
6. Si transcurren los 120 segundos, el código caduca automáticamente y se habilita el botón de reenvío con temporizador de enfriamiento (*cooldown*).

---

### C. Método 2: Dispositivo Móvil (Google / Microsoft Authenticator)
- **Primera vez (Usuario no vinculado)**:
  - Se abre la interfaz de **Libro Horizontal** (`enrolar_login.php`).
  - Lado izquierdo: Pasos 1 y 2 con badges de descarga y el **código QR renderizado 100% en Canvas local** (sin internet), además de la clave secreta manual.
  - Lado derecho: Paso 3 con casillero gigante para los 6 dígitos que muestra el celular.
  - **Sin códigos de respaldo**: Al confirmar los 6 dígitos por primera vez, el usuario **ingresa directamente al sistema** sin modales molestos de códigos de recuperación.
- **Siguientes accesos (Usuario ya vinculado)**:
  - Se despliega la pantalla de desafío numérico (`desafio_login.php`) solicitando los 6 dígitos que genera la app en ese instante.

---

### D. Re-vinculación de Celular por Pérdida o Cambio
Si un usuario cambió de celular, lo extravió o formateó su equipo:
1. En la pantalla del desafío TOTP (`desafio_login.php`) pulsa en el enlace inferior:
   > **"¿Cambiaste de teléfono? Vincular nuevo dispositivo"**
2. Se abre un modal de seguridad que envía un **código de 6 dígitos a su correo institucional** (120s de validez).
3. Al validar el código de correo, se certifica su identidad y se abre el modal con un **nuevo código QR** para vincular su nuevo teléfono.
4. El teléfono anterior queda revocado y el nuevo queda activo de forma inmediata.

---

## 2. Estructura de Archivos del Módulo

```text
modulo_2fa/
│
├── README.md                           <- Guía técnica para desarrolladores
│
├── database/
│   └── script_bd_2fa.sql               <- Script DDL oficial para PostgreSQL (esquema public)
│
├── services/
│   ├── TwoFactorService.php            <- Motor RFC 6238 TOTP, Base32 y cifrado AES-256-GCM
│   └── SmtpMailer.php                  <- Cliente SMTP nativo en PHP con sockets (cero librerías externas)
│
├── models/
│   └── TwoFactorModel.php              <- Modelo PDO: consulta a escalafón, auditoría y bloqueos
│
├── controllers/
│   └── TwoFactorController.php         <- Endpoints AJAX: solicitar_codigo_correo, verificar_codigo_correo,
│                                          verificar_desafio, iniciar_enrolamiento, confirmar_enrolamiento
│
├── views/
│   ├── selector_metodo.php             <- Pantalla principal con las 2 tarjetas (Correo vs Dispositivo)
│   ├── desafio_login.php               <- Vista de desafío TOTP de 6 dígitos con enlace a re-vincular
│   ├── enrolar_login.php               <- Vista completa de enrolamiento horizontal (Libro MPCH)
│   ├── modal_enrolamiento.php          <- Modal horizontal reutilizable para cualquier vista o perfil
│   └── perfil_2fa.php                  <- Componente embebible para la sección de perfil de usuario
│
└── assets/
    ├── css/
    │   └── two_factor.css              <- Estilos del libro horizontal, inputs gigantes y tarjetas
    └── js/
        ├── qrcode.min.js               <- Librería local para generación de QR en Canvas (David Shim)
        └── two_factor.js               <- Helpers JavaScript: render QR local, temporizadores y portapapeles
```

---

## 3. Guía de Integración Rápida en Otros Proyectos (10 Minutos)

### Paso 1: Copiar la carpeta
Copia la carpeta `modulo_2fa/` completa en la raíz del nuevo sistema municipal (ej. `sisTramite/modulo_2fa/`).

### Paso 2: Base de Datos
- Si el nuevo sistema comparte la base de datos de PostgreSQL con un sistema que ya tiene las tablas: **¡No ejecutes nada!** Las tablas ya están disponibles.
- Si utiliza una base de datos propia, ejecuta `modulo_2fa/database/script_bd_2fa.sql`.

### Paso 3: Intercepción en el Login (`models/Auth.php`)
En el archivo donde se verifica la respuesta de SIGUR tras validar credenciales con éxito, reemplaza la emisión directa de la sesión por:

```php
// =========================================================================
// INTERCEPCIÓN DE DOBLE FACTOR DE AUTENTICACIÓN (2FA MULTI-MÉTODO)
// =========================================================================
$local2faFile = __DIR__ . '/../modulo_2fa/models/TwoFactorModel.php';
if (file_exists($local2faFile)) {
    try {
        require_once $local2faFile;

        // Guardar token temporal con los datos del usuario autenticado por SIGUR
        $tokenPending = $jwt->encrypt($param, '2fa_pending', 600); // 10 minutos
        $jwt->setCookie("2fa_pending", $tokenPending, 0, "/" . $_ENV["APP_NAME"] . "/");
        $jwt->setCookie("2fa_pending", $tokenPending, 0, "/");
        $_SESSION['2fa_pending_user'] = (array)$param;

        // Redirigir al selector de método (Correo vs Dispositivo)
        header("Location: " . Conectar::ruta() . 'modulo_2fa/views/selector_metodo.php');
        exit();
    } catch (Exception $e) {
        error_log("[Auth 2FA] Error al redirigir al selector: " . $e->getMessage());
    }
}
```

---

## 4. Base de Datos y Auditoría Pura

### A. Esquema de Tablas (PostgreSQL `public`)

#### 1. `public.tb_seguridad_usuario_2fa`
| Campo | Tipo | Descripción |
| :--- | :--- | :--- |
| `seus_id` | SERIAL PK | Clave primaria. |
| `seus_usuario_id` | INTEGER NOT NULL | ID del usuario entregado por SIGUR. |
| `seus_usuario_doc` | VARCHAR(15) NOT NULL | DNI del usuario (UNIQUE). |
| `seus_llave_secr` | TEXT NOT NULL | Secreto Base32 cifrado con AES-256-GCM. |
| `seus_esta_vinc` | BOOLEAN NOT NULL | `TRUE` si completó vinculación de celular. |
| `seus_enrol_conf` | TIMESTAMPTZ | Fecha/hora de vinculación o re-vinculación. |
| `seus_marca_tiem` | BIGINT NOT NULL | Paso Unix TOTP consumido (Anti-Replay). |
| `seus_intentos_fall` | INTEGER NOT NULL | Intentos fallidos consecutivos. |
| `seus_bloqueo_temp` | TIMESTAMPTZ | Fecha de expiración de bloqueo (15 min). |

#### 2. `public.tb_seguridad_auditoria_login_2fa`
Bitácora de seguridad inmutable.

---

### B. Estados de Auditoría en Español (`seal_evento`)

Para facilitar las auditorías internas de seguridad informática, todos los estados se guardan en **español claro y explícito**:

| Evento (`seal_evento`) | Significado de Auditoría |
| :--- | :--- |
| `SOLICITUD_CODIGO_CORREO` | Usuario solicitó envío de código OTP de 6 dígitos a su correo institucional (120s). |
| `VERIFICACION_EXITOSA_CORREO` | Acceso exitoso al sistema tras validar el código recibido por correo. |
| `VERIFICACION_FALLIDA_CORREO` | Código de correo erróneo o tiempo límite de 120 segundos superado. |
| `VERIFICACION_EXITOSA_DISPOSITIVO` | Acceso exitoso con código de 6 dígitos de Google Authenticator. |
| `VERIFICACION_FALLIDA_DISPOSITIVO` | Código TOTP incorrecto o celular fuera de hora sincronizada. |
| `VINCULACION_DISPOSITIVO_INICIADA` | Generación de nuevo código QR y clave secreta para enrolar teléfono. |
| `PRIMERA_VINCULACION_DISPOSITIVO` | Enlace inicial de teléfono celular confirmado exitosamente por el usuario. |
| `SOLICITUD_RE_VINCULACION_DISPOSITIVO` | Usuario solicitó cambiar de celular y se le despachó código al correo institucional. |
| `RE_VINCULACION_CONFIRMADA` | Re-vinculación exitosa de un nuevo teléfono tras validar identidad por correo. |
| `BLOQUEO_TEMPORAL` | Cuenta congelada preventivamente por 15 minutos al alcanzar 5 intentos erróneos. |
| `CIERRE_SESION` | Desactivación o cierre de sesión del doble factor. |

---

### C. Estructura de Trazabilidad JSON (`seal_detalles`)

Cada registro almacena en el campo `seal_detalles` (JSONB) información detallada:

```json
{
  "metodo": "CORREO_INSTITUCIONAL",
  "sistema": "sisIndicadores",
  "codigo_ingresado": "482910",
  "correo_destino": "j***@munichiclayo.gob.pe",
  "tiempo_restante_seg": 87,
  "intentos": 1,
  "motivo": null,
  "ip": "192.168.1.45",
  "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64)..."
}
```

---

### D. Instrucciones para el Administrador de Base de Datos (DBA)

#### Opción 1: Si ya tenías las tablas de la versión anterior
No necesitas borrar tus datos ni recrear las tablas. El DBA solo debe correr estas dos líneas:
```sql
ALTER TABLE public.tb_seguridad_usuario_2fa DROP COLUMN IF EXISTS seus_codigos_resp;
ALTER TABLE public.tb_seguridad_auditoria_login_2fa ALTER COLUMN seal_evento TYPE VARCHAR(60);
```

#### Opción 2: Si es una instalación limpia
Ejecuta íntegramente el script:
[`modulo_2fa/database/script_bd_2fa.sql`](file:///home/italo/PhpstormProjects/sisIndicadores/modulo_2fa/database/script_bd_2fa.sql)

---

## 5. Servicio de Correo Institucional SIGUR (`SigurMailer`)

El envío de correos electrónicos se encuentra integrado directamente con la **API Central de Correo Institucional de SIGUR (MPCH)**:
- Escrito en **PHP nativo con cURL** (cero dependencias de Composer ni librerías pesadas externas).
- Autenticación institucional mediante `POST /sigur/auth/` con las credenciales del sistema (`SIST_INIC` y `SIST_PASS`).
- Obtención de token Bearer y despacho seguro mediante `POST /sigur/ws/correo.php`.
- Parámetros estandarizados enviados al Web Service:
  - `destino`: Correo electrónico del funcionario o trabajador (obtenido de `sc_escalafon.tb_persona`).
  - `titulo`: Asunto formal del mensaje (`Código de Seguridad 2FA: XXXXXX - Municipalidad Provincial de Chiclayo`).
  - `mensaje`: Plantilla HTML con diseño institucional MPCH, caja destacada con el código de 6 dígitos, temporizador de 120 segundos y avisos de seguridad.
- Mantiene compatibilidad con `SmtpMailer.php` como wrapper transparente.

### A. Variables requeridas en `.env`
Para que el servicio de correo opere, el sistema consumidor solo requiere tener definidas las credenciales ante SIGUR en su archivo `.env`:

```env
# URL Base del servidor SIGUR (o BASE_URL del servidor institucional)
BASE_URL=http://10.10.10.16/
# SIGUR_URL=http://10.10.10.16/

# Credenciales del Sistema asignadas por SIGUR
SIST_INIC=SIINDI
SIST_PASS=sgd*2025**
```

### B. Modo de Respaldo / Pruebas Locales
Si las credenciales de SIGUR no están disponibles o el entorno local está desconectado de la red municipal:
- Detecta la indisponibilidad de la API.
- En entornos de desarrollo/pruebas (`APP_ENV=pruebas` o `local`), genera el código OTP, registra el evento en logs y lo retorna en la respuesta de depuración para que ningún desarrollador se bloquee durante las pruebas.

---

## 6. Seguridad Criptográfica y Preguntas Frecuentes

### ¿Se requiere cuenta de Google para usar Google Authenticator?
**No.** Google Authenticator es simplemente un lector del estándar abierto RFC 6238. No envía datos a Google ni requiere que el funcionario inicie sesión con una cuenta de correo personal o corporativa.

### ¿Se altera la tabla de personas o escalafón?
**No.** La consulta a `sc_escalafon.tb_persona` es estrictamente de solo lectura (`SELECT pers_emailm`). El módulo nunca ejecuta `INSERT`, `UPDATE` o `DELETE` sobre dicho esquema.

### ¿Cómo resetear el 2FA de un usuario bloqueado para pruebas?
El desarrollador o DBA puede resetear el estado con:
```sql
UPDATE public.tb_seguridad_usuario_2fa 
SET seus_intentos_fall = 0, seus_bloqueo_temp = NULL, seus_updated_at = CURRENT_TIMESTAMP 
WHERE seus_usuario_doc = 'TU_DNI';
```

---

**Municipalidad Provincial de Chiclayo — Gerencia de Tecnología y Sistemas de Información**
