# Bitácora de Refactorización y Cambios Técnicos (SIGODT)

Este documento registra cronológicamente cada una de las modificaciones arquitectónicas y correcciones de código realizadas en el sistema SIGODT, detallando los archivos afectados, la justificación del cambio, el comportamiento previo vs. actual y las instrucciones para ajustar la lógica en caso de requerimientos futuros.

---

## [Fase P0] — Seguridad, Gestión de Entorno y Control de IP
**Fecha:** 2026-09-30  
**Rama:** `dev`  
**Objetivo:** Eliminar credenciales expuestas en texto plano, centralizar variables de entorno en `.env` y suprimir el bypass inseguro de IP para `sisSeguridad`.

### 1. Archivos Afectados
* `.gitignore` *(Nuevo)*
* `.env.example` *(Nuevo)*
* `composer.json` *(Nuevo)*
* `composer.lock` *(Nuevo)*
* `config/conexion.php`
* `api/db.php`
* `models/Usuario.php`
* `controller/ciudadano.php`
* `controller/ws_reniec.php`
* `controller/api_reload.php`
* `api/SATCH/tokenGen.php`
* `api/SATCH/get_data_satch.php`
* `api/api.php`
* `api/api_sisgi.php`
* `api/buscar_automatizado.php`

### 2. Detalle de los Cambios

#### A. Implementación de Composer y PHP-Dotenv
* **Antes:** No existía gestor de paquetes ni archivo de entorno. Las librerías estaban incrustadas manualmente y no había forma de cambiar puertos, hosts o credenciales sin editar directamente los archivos fuente.
* **Ahora:** Se configuró Composer y se instaló `vlucas/phpdotenv`. Se creó `.env.example` como plantilla para el equipo y se configuró `.gitignore` para impedir que `.env`, carpetas de dependencias (`vendor/`) o imágenes de ciudadanos se suban al repositorio.
* **Cómo ajustarlo:** Si se agregan nuevas variables institucionales, definirlas en `.env.example` y consumirlas mediante `Conectar::getEnv('VARIABLE', 'valor_por_defecto')`.

#### B. Conexión a Base de Datos (`config/conexion.php` y `api/db.php`)
* **Antes:** Contraseña maestra quemada (`postgres / Mpch*2023*`) apuntando rígidamente a `10.10.10.16`.
* **Ahora:** Tanto la clase `Conectar` (PDO) como `api/db.php` (`pg_connect`) leen `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` y `DB_PASS` desde el entorno.
* **Cómo ajustarlo:** Si cambian las credenciales de la base de datos municipal o se clona el proyecto en local, solo se edita el archivo `.env`.

#### C. Control de IP y Auditoría de Acceso (`models/Usuario.php` y `Conectar::getClientIp()`)
* **Antes:** Si la IP remota empezaba con `::` (IPv6 o localhost), el código forzaba la IP a `192.168.12.44` para evadir la validación perimetral de `sisSeguridad`.
* **Ahora:** Se centralizó la lógica en `Conectar::getClientIp()`:
  * En producción (`APP_ENV=production`): Captura la IP real del cliente respetando cabeceras `HTTP_X_FORWARDED_FOR` de proxies confiables sin suplantación.
  * En desarrollo (`APP_ENV=local` o `staging`): Si la conexión es loopback (`127.0.0.1` o `::1`), permite usar `DEV_MOCK_IP` configurado en `.env` para pruebas de conectividad con `sisSeguridad`.
* **Cómo ajustarlo:** Para pruebas locales en una nueva máquina, cambiar la variable `DEV_MOCK_IP` en `.env` sin alterar el código fuente.

#### D. Externalización de APIs Externas
* **PIDE / RENIEC / MINSA (`controller/ciudadano.php` y `controller/ws_reniec.php`):**
  * Se extrajeron las credenciales fijas (`20250001 / 20250001@`) y la clave de cifrado (`sgd*2023`) a `PIDE_RENIEC_USER`, `PIDE_RENIEC_PASS`, `PIDE_SECRET_KEY` y `PIDE_BASE_URL`.
* **SATCH OAuth (`api/SATCH/tokenGen.php` y `api/SATCH/get_data_satch.php`):**
  * Se extrajeron `client_id`, `client_secret`, usuario y contraseña a variables `SATCH_*`.
  * Se corrigió la zona horaria en respuestas JSON de `America/Bogota` a `America/Lima`.
* **APIs Internas (`api.php`, `api_sisgi.php`, `buscar_automatizado.php`, `api_reload.php`):**
  * Se reemplazaron las URLs e IPs fijas hardcodeadas (`http://10.10.10.16/`, `http://216.244.171.252/`) por llamadas dinámicas a `Conectar::ruta()` y `SIS_SEGURIDAD_URL`.


---

## [Fase P1] — Integridad de Datos, Concurrencia y Depuración
**Fecha:** 2026-09-30  
**Rama:** `dev`  
**Objetivo:** Eliminar condiciones de carrera en auditoría forense, evitar bloqueos de catálogo por DDL en runtime y suprimir endpoints duplicados obsoletos.

### 1. Archivos Afectados
* `api/api_.php` *(Eliminado)*
* `models/Tasa.php`
* `models/Bitacora.php`

### 2. Detalle de los Cambios

#### A. Eliminación de Endpoint Residual (`api/api_.php`)
* **Antes:** Existía una copia de respaldo manual (`api_.php`) expuesta en la carpeta pública `/api/`. Presentaba fuga de sesiones (el `exit()` prevenía el logout hacia `sisSeguridad`), utilizaba zona horaria de Bogotá y representaba un vector de ataque.
* **Ahora:** Se eliminó físicamente del repositorio. Todas las peticiones deben dirigirse al endpoint saneado [`api/api.php`](api/api.php).
* **Cómo ajustarlo:** No requiere ajuste. Cualquier cliente que invoque este archivo debe actualizar su URL a `api/api.php`.

#### B. Eliminación de Bloqueo DDL en Emisión de Órdenes (`models/Tasa.php`)
* **Antes:** En el método `insert_girotasaciud`, se evaluaba `SELECT SUBSTRING(MAX(ogciud_id) FROM 8 FOR 4)`. Debido a la comparación lexicográfica de cadenas alfanuméricas, la condición de año nuevo evaluaba a `true` en casi todas las transacciones, ejecutando un `ALTER SEQUENCE ... RESTART WITH` dentro del ciclo HTTP y generando bloqueos exclusivos (`AccessExclusiveLock`) en PostgreSQL.
* **Ahora:**
  1. Se verifica la existencia de órdenes en el año actual mediante una consulta indexada rápida: `SELECT 1 FROM sc_giros.td_ordengirociud WHERE ogciud_id LIKE :mask LIMIT 1`.
  2. En el 99.99% de las transacciones (cuando ya existen órdenes emitidas en el año), se omite el DDL por completo.
  3. Si es estrictamente la primera orden del año nuevo, el reinicio de la secuencia se ejecuta protegido mediante un candado atómico de transacción (`pg_advisory_xact_lock(987654340)`) con doble comprobación, impidiendo que múltiples operadores colisionen.
* **Cómo ajustarlo:** Si en el futuro se migra a secuencias anuales independientes (ej. `sc_giros.ordengiro_id_sequence_YYYY`), solo se requerirá cambiar la función generadora en este bloque.

#### C. Protección contra Condiciones de Carrera en Auditoría (`models/Bitacora.php`)
* **Antes:** Tras los triggers de inserción en base de datos, PHP consultaba `SELECT MAX(bita_id)` sin filtrar y le asignaba el usuario de sesión. Si dos operadores guardaban al mismo tiempo, el segundo sobreescribía la traza de auditoría del primero.
* **Ahora:**
  1. `update_bitacora($pers_id)` busca específicamente el último registro huérfano (`WHERE pers_id IS NULL OR pers_id = 0`).
  2. La actualización condiciona la escritura con `AND (pers_id IS NULL OR pers_id = 0)`, garantizando que nunca se sobreescriba un registro que ya fue reclamado por otro funcionario.
  3. `update_bitacora_grupo($pers_id, $menor)` aplica la misma protección dentro del rango de IDs.
* **Cómo ajustarlo:** Si más adelante se actualizan los triggers de base de datos para que lean directamente variables de sesión de PostgreSQL (`SET LOCAL app.current_user_id`), las llamadas a `update_bitacora` podrán retirarse gradualmente sin afectar la lógica.
