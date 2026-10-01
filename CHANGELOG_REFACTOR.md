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

---

## [Fase P2] — Arquitectura y Deuda Técnica: Unificación de Modelos y Controladores
**Fecha:** 2026-09-30  
**Rama:** `dev`  
**Objetivo:** Consolidar clases y controladores duplicados para respetar el principio de única fuente de verdad (Single Source of Truth), prevenir colisiones fatales de clases en PHP (`Fatal Error: Cannot declare class...`) y estandarizar el consumo desde las vistas.

### 1. Archivos Afectados
* `models/OrdenGiro.php`
* `models/OrdenGiro_rep.php`
* `controller/ordengiro.php`
* `controller/orden_giro.php`
* `view/consultar_documento/index.php`
* `view/consultar_mes/index.php`
* `view/consultar_og/index.php`
* `models/Ciudadano.php`
* `models/Ciudadano_mnt.php`
* `controller/ciudadano.php`
* `controller/ciudadano_mnt.php`
* `view/mnt_ciudadano/ciud.js`
* `models/Empresa.php`
* `models/Empresa_mnt.php`
* `controller/empresa.php`
* `controller/empresa_mnt.php`
* `view/mnt_empresa/empresa.js`
* `controller/TCPDF-main/` *(Eliminado)*

### 2. Detalle de los Cambios

#### A. Consolidación de Órdenes de Giro (`models/OrdenGiro.php` y `controller/ordengiro.php`)
* **Antes:**
  * Existían dos modelos declarando la misma clase: `models/OrdenGiro.php` (emisión y reportes de tasas) y `models/OrdenGiro_rep.php` (consultas ciudadanas, reportes mensuales y búsqueda de órdenes para PDF).
  * `controller/orden_giro.php` atendía 3 casos aislados (`get_ordenes_giro`, `get_ordenes_mes`, `get_orden_giro`) y las vistas `view/consultar_documento/`, `view/consultar_mes/` y `view/consultar_og/` apuntaban a este controlador secundario.
  * La obtención de PDF en `get_orden_giro_by_id` tenía la URL `http://10.10.10.16/SIGODT/` quemada en código.
* **Ahora:**
  1. Se fusionaron los métodos `get_ordenes_giro()`, `get_ordenes_mes()`, `get_total_ordenes_mes()` y `get_orden_giro_by_id()` dentro de `models/OrdenGiro.php`. Se agregó `class_alias('Ordengiro', 'OrdenGiro')` para compatibilidad completa de nombres.
  2. En `get_orden_giro_by_id()`, la URL del PDF ahora se construye dinámicamente mediante `Conectar::getEnv('APP_URL')`.
  3. Se incorporaron los 3 casos correspondientes en `controller/ordengiro.php`.
  4. Las vistas (`consultar_documento`, `consultar_mes`, `consultar_og`) se actualizaron para llamar directamente a `controller/ordengiro.php`.
  5. `models/OrdenGiro_rep.php` y `controller/orden_giro.php` se convirtieron en shims de retrocompatibilidad que delegan a los archivos principales.
* **Cómo ajustarlo:** Cualquier nuevo reporte o filtro sobre órdenes de giro debe agregarse únicamente en `models/OrdenGiro.php` y exponerse en `controller/ordengiro.php`.

#### B. Consolidación de Ciudadano (`models/Ciudadano.php` y `controller/ciudadano.php`)
* **Antes:**
  * `models/Ciudadano.php` manejaba búsquedas por documento y registro para giros.
  * `models/Ciudadano_mnt.php` declaraba otra clase `Ciudadano` para mantenimiento DataTables, paginación, edición e inactivación.
  * `controller/ciudadano_mnt.php` y `view/mnt_ciudadano/ciud.js` trabajaban desvinculados del controlador central.
* **Ahora:**
  1. Se incorporaron en `models/Ciudadano.php` los métodos: `listarCiudadanos()`, `mostrar()`, `list_ciudadano()`, `get_total_ciudadano()`, `get_tito_doc()`, `existeDocumento()`, `insertar()`, `editar()` e `inactivar()`.
  2. Se integraron los casos `listar`, `listar_tipos`, `listar_tabla`, `crear`, `mostrar`, `editar` y `cambiar_estado` en `controller/ciudadano.php`.
  3. `view/mnt_ciudadano/ciud.js` apunta ahora directamente a `controller/ciudadano.php`.
  4. `models/Ciudadano_mnt.php` y `controller/ciudadano_mnt.php` funcionan como shims delegadores.
* **Cómo ajustarlo:** Nuevos campos en el formulario de ciudadanos deben mapearse en los métodos `insertar()` y `editar()` de `models/Ciudadano.php`.

#### C. Consolidación de Empresa (`models/Empresa.php` y `controller/empresa.php`)
* **Antes:**
  * `models/Empresa.php` atendía consultas de RUC, giros comerciales y agrupaciones de procedimientos vehiculares.
  * `models/Empresa_mnt.php` declaraba otra clase `Empresa` con la lógica de CRUD y DataTables.
  * `controller/empresa_mnt.php` y `view/mnt_empresa/empresa.js` operaban en paralelo.
* **Ahora:**
  1. Se integraron en `models/Empresa.php` los métodos: `listarEmpresas()`, `list_empresa()`, `get_total_empresa()`, `existeRuc()`, `insertar()`, `editar()`, `inactivar()` y `mostrar()`.
  2. Se añadieron los casos `listar`, `listar_tabla`, `crear`, `editar`, `cambiar_estado` y `mostrar` en `controller/empresa.php`.
  3. `view/mnt_empresa/empresa.js` apunta directamente a `controller/empresa.php`.
  4. `models/Empresa_mnt.php` y `controller/empresa_mnt.php` quedaron convertidos en shims.
* **Cómo ajustarlo:** Reglas de validación adicionales para RUC o actividades económicas deben implementarse en `models/Empresa.php`.

#### D. Depuración de Librerías Huérfanas (`controller/TCPDF-main/`)
* **Antes:** La carpeta `controller/TCPDF-main/` (29 MB, 375 archivos) permanecía en el árbol fuente desde el commit inicial (`feat: init repo`). No era referenciada ni invocada por ningún script del sistema, ya que la emisión de comprobantes se realiza íntegramente mediante FPDF (`public/plantilla_reporte.php`).
* **Ahora:** Se eliminó por completo `controller/TCPDF-main/` del repositorio, liberando 29 MB y reduciendo significativamente el tiempo de clonación y despliegue.
* **Cómo ajustarlo:** Si en el futuro se requieren capacidades avanzadas de renderizado no soportadas por FPDF, la integración debe realizarse a través de Composer (`composer require tecnickcom/tcpdf` o `dompdf/dompdf`), nunca copiando repositorios manuales dentro de `controller/`.
