# Volumen 2: Evaluación Técnica y Calidad del Código
**Sistema Integrado de Gestión de Órdenes de Derecho de Trámite (SIGODT)**  
**Municipalidad Provincial de Chiclayo (MPCH)**  

---

## 📊 1. Veredicto Técnico: ¿Qué tan bueno es el sistema?

Para responder objetivamente a la pregunta **"¿Qué tan bueno es?"**, se debe diferenciar entre **su valor funcional para la institución** y **su calidad técnica / nivel de seguridad en el código**.

| Criterio | Calificación (1 - 10) | Estado | Justificación |
| :--- | :---: | :---: | :--- |
| **Cumplimiento Funcional** | **8.5 / 10** | 🟢 Bueno | Resuelve con exactitud la problemática de liquidación tributaria, interoperabilidad estatal (PIDE) y control de uso único de recibos en la MPCH. |
| **Seguridad de la Información** | **2.5 / 10** | 🔴 Crítico | Presencia de credenciales administrativas hardcodeadas, IPs fijas, suplantación potencial de IP y llaves de cifrado estáticas. |
| **Arquitectura de Software** | **4.0 / 10** | 🟡 Deficiente | Código PHP procedural mezclado con clases PDO, sin framework formal, sin autoloading moderno PSR-4 y con endpoints duplicados. |
| **Rendimiento y Concurrencia** | **4.5 / 10** | 🟡 Regular | Posee buenos mecanismos en batch (`pg_try_advisory_lock`), pero arriesga bloqueos severos mediante `ALTER SEQUENCE` en runtime y race conditions en auditoría. |
| **Mantenibilidad y Limpieza** | **3.5 / 10** | 🔴 Bajo | Bibliotecas externas incrustadas manualmente (`TCPDF-main`, `phpqrcode`) sin Composer, nombres de sesión inconsistentes y alta duplicidad de SQL. |
| **Promedio General Ponderado** | **4.6 / 10** | ⚠️ **Operativo pero con Alto Riesgo Técnico** |

> **Conclusión Ejecutiva:**  
> El sistema es **altamente valioso y funcional en el plano operativo del municipio**, pero **muy frágil, vulnerable y técnico-dependiente en el plano informático**. Requiere una remediación urgente de seguridad y una refactorización de arquitectura para garantizar su sostenibilidad institucional.

---

## 🌟 2. Puntos Fuertes y Fortalezas Técnicas

A pesar de sus carencias de diseño moderno, el código exhibe aciertos de ingeniería destacables:

1. **Uso de Transacciones ACID en PostgreSQL:**
   - En operaciones críticas como el pago de tasas (`Tasa.php`, `api.php`, `get_data_satch.php`), el código implementa bloques `$conectar->beginTransaction()` y `COMMIT / ROLLBACK`. Esto previene que una orden quede marcada como pagada si falla la actualización de las tasas hijas o del expediente.
2. **Control de Concurrencia Asíncrona con Advisory Locks:**
   - En el script de sincronización desatendida (`api/buscar_automatizado.php`), se utiliza `pg_try_advisory_lock(987654330)`. Esto evita que múltiples invocaciones del programador de tareas (cron o servidor web) colapsen la base de datos o procesen la misma orden en paralelo.
3. **Alto Nivel de Interoperabilidad Gubernamental (PIDE):**
   - No depende de entrada manual de datos de ciudadanos o empresas. Consume APIs oficiales de RENIEC, SUNAT, SUNARP y Migraciones, elevando la fiabilidad de los datos y cumpliendo las directivas de interoperabilidad de la PCM (Presidencia del Consejo de Ministros).
4. **Generación Integral de Tickets con Código QR:**
   - Utiliza FPDF y PHPQRCode para emitir comprobantes de 117mm con formato ticket de impresión térmica, facilitando que el administrado pague en caja simplemente escaneando el QR con el lector óptico del cajero SATCH.
5. **Interfaz de Usuario Limpia y Adaptativa:**
   - La adopción del template **Tabler UI** (basado en Bootstrap 5) ofrece un entorno visual moderno, con búsquedas asíncronas vía DataTables y confirmaciones elegantes con SweetAlert2.

---

## 🚨 3. Vulnerabilidades Críticas y Debilidades del Código

A continuación se detallan los riesgos de seguridad y errores de diseño encontrados en el código fuente:

### 3.1. Credenciales de Acceso Expuestas en Texto Plano (Hardcoded Secrets)
- **Base de Datos PostgreSQL:** En [`config/conexion.php`](file:///home/victorchung/PhpstormProjects/MPCH_Ordenes_Giro_SIGODT/config/conexion.php#L8) y [`api/db.php`](file:///home/victorchung/PhpstormProjects/MPCH_Ordenes_Giro_SIGODT/api/db.php#L2-L6):
  ```php
  $conectar = $this->dbh = new PDO("pgsql:host=10.10.10.16;dbname=db_simcix", "postgres", "Mpch*2023*");
  ```
  El usuario maestro `postgres` y su contraseña están grabados directamente en el repositorio.
- **Credenciales Maestras PIDE / RENIEC:** En [`controller/ciudadano.php`](file:///home/victorchung/PhpstormProjects/MPCH_Ordenes_Giro_SIGODT/controller/ciudadano.php#L152-L153):
  ```php
  $usuario = '20250001';
  $contrasena = '20250001@';
  ```
- **Credenciales OAuth SATCH:** En [`api/SATCH/tokenGen.php`](file:///home/victorchung/PhpstormProjects/MPCH_Ordenes_Giro_SIGODT/api/SATCH/tokenGen.php#L6-L14):
  ```php
  $username = 'satchapitest';
  $password = 'rQ4iDSYbHpq6c1D';
  'username' => 'mpch@test.com',
  'password' => 'sX2kHKCQ27yjoi4N'
  ```
- **Riesgo:** Cualquier usuario con acceso de lectura al repositorio, respaldo o servidor web obtiene control total sobre la base de datos municipal y los servicios interconectados del Estado.

---

### 3.2. Falsificación de IP y Bypass de Control de Acceso
En [`models/Usuario.php`](file:///home/victorchung/PhpstormProjects/MPCH_Ordenes_Giro_SIGODT/models/Usuario.php#L16-L23):
```php
$ip = $_SERVER['REMOTE_ADDR'];
if (strpos($ip, '::') === 0) {
    $ip = '::1';
    // REEMPLAZAR POR LA DIRECCIÓN IP DE LA PC
    $ip = "192.168.12.44";
}
```
Si el usuario se conecta por IPv6 o localhost, el sistema suplanta arbitrariamente la IP por `192.168.12.44`. Como el servicio `sisSeguridad` valida que la IP esté autorizada en el padrón municipal, esta alteración burla la política de seguridad perimetral institucional.

---

### 3.3. Condición de Carrera Severa en Auditoría (`sc_seguridad.tb_bitacora`)
En [`models/Bitacora.php`](file:///home/victorchung/PhpstormProjects/MPCH_Ordenes_Giro_SIGODT/models/Bitacora.php#L6-L17):
```php
public function update_bitacora($pers_id) {
    $bita_id = $this->get_max_id()[0]["bita_id"]; // SELECT MAX(bita_id)
    $sql = "UPDATE sc_seguridad.tb_bitacora SET pers_id = ? WHERE bita_id = ?;";
    // ...
}
```
- **Falla de Diseño:** La base de datos dispara un trigger que inserta una fila en `tb_bitacora` cuando se crea una entidad. Luego, la aplicación PHP consulta el `MAX(bita_id)` para asignarle el usuario que realizó la acción.
- **Consecuencia en Producción:** Si dos funcionarios en ventanillas distintas operan con un milisegundo de diferencia, el usuario A sobreescribirá la bitácora del usuario B. Esto invalida cualquier peritaje forense o auditoría legal de la Contraloría.

---

### 3.4. Ejecución de DDL (`ALTER SEQUENCE`) en Tiempo de Ejecución
En [`models/Tasa.php`](file:///home/victorchung/PhpstormProjects/MPCH_Ordenes_Giro_SIGODT/models/Tasa.php#L168-L181):
```php
if ($currentYear !== $lastYear) {
    $sql2 = "SELECT COALESCE(MAX(SUBSTRING(ogciud_id FROM 1 FOR 6)::INTEGER), 0) + 1 AS next_val ...";
    $conectar->exec("ALTER SEQUENCE sc_giros.ordengiro_id_sequence RESTART WITH {$nextVal}");
}
```
- **Riesgo:** Ejecutar sentencias DDL (`ALTER SEQUENCE`) dentro de una transacción HTTP en un sistema concurrido genera bloqueos exclusivos de catálogo (`AccessExclusiveLock`) en PostgreSQL. Si dos operadores emiten órdenes simultáneamente en año nuevo, el sistema generará deadlocks o reseteos inconsistentes.

---

### 3.5. Llave de Cifrado Estática y Cifrado Inseguro (ECB)
En [`controller/ws_reniec.php`](file:///home/victorchung/PhpstormProjects/MPCH_Ordenes_Giro_SIGODT/controller/ws_reniec.php#L8-L19):
```php
class TokenHelper {
    const SECRET_KEY = 'sgd*2023';
    public static function encrypt(string $plain): string {
        return openssl_encrypt($plain, 'AES-128-ECB', self::SECRET_KEY);
    }
}
```
- El modo `ECB` (Electronic Codebook) es criptográficamente inseguro porque patrones idénticos en el texto plano producen bloques cifrados idénticos.
- La clave `sgd*2023` es débil, pública y estática.

---

### 3.6. Dispersión, Duplicidad de Archivos y Dependencias sin Gestor
1. **Modelos Duplicados:** Existen [`models/OrdenGiro.php`](file:///home/victorchung/PhpstormProjects/MPCH_Ordenes_Giro_SIGODT/models/OrdenGiro.php) y [`models/OrdenGiro_rep.php`](file:///home/victorchung/PhpstormProjects/MPCH_Ordenes_Giro_SIGODT/models/OrdenGiro_rep.php), así como [`models/Ciudadano.php`](file:///home/victorchung/PhpstormProjects/MPCH_Ordenes_Giro_SIGODT/models/Ciudadano.php) y [`models/Ciudadano_mnt.php`](file:///home/victorchung/PhpstormProjects/MPCH_Ordenes_Giro_SIGODT/models/Ciudadano_mnt.php). Tienen métodos repetidos y consultas SQL divergentes.
2. **Librerías Vendor en Controladores:** La librería TCPDF fue copiada directamente dentro de [`controller/TCPDF-main/`](file:///home/victorchung/PhpstormProjects/MPCH_Ordenes_Giro_SIGODT/controller/TCPDF-main/) y PHPQRCode en [`controller/phpqrcode/`](file:///home/victorchung/PhpstormProjects/MPCH_Ordenes_Giro_SIGODT/controller/phpqrcode/), ocupando miles de archivos en el repositorio en vez de manejarse vía `composer.json`.
3. **Inconsistencia de Sesiones:** En `controller/ws_reniec.php` se evalúa `$_SESSION["usua_id_siagth"]`, mientras que en el resto del proyecto se usa `$_SESSION["usua_id_SIGODT"]`. Esto denota copiado y pegado de código de otro sistema sin homogenización.

---

## 🛠️ 4. Hoja de Ruta y Recomendaciones de Modernización

Para llevar SIGODT a un estándar de calidad y seguridad de nivel gubernamental (NTP - Normas Técnicas Peruanas de Software del Estado), se recomienda un plan en 3 fases:

### Fase 1: Remediación Urgente de Seguridad (Inmediata)
1. **Externalizar Credenciales:** Implementar `vlucas/phpdotenv` o leer variables de entorno nativas de Apache/Nginx (`.env`), eliminando toda contraseña de la base de datos y de las APIs del código.
2. **Corrección de IP:** Eliminar el hardcoding de `192.168.12.44` y validar correctamente la IP cliente en entornos proxy o reversos usando `HTTP_X_FORWARDED_FOR` con filtrado de proxies confiables.
3. **Seguridad de APIs:** Restringir el acceso a `api/api.php` y `api/buscar_automatizado.php` mediante Bearer Tokens o Basic Auth dinámico, y limitar su consumo exclusivamente por IP de SATCH / Caja.

### Fase 2: Robustecimiento de Base de Datos y Concurrencia (Mediano Plazo)
1. **Refactorizar la Generación de Códigos de Giro:** Reemplazar el `ALTER SEQUENCE` por una secuencia anual independiente o una función PL/pgSQL que maneje de forma segura la numeración atómica (`SELECT nextval(...)`).
2. **Refactorizar Auditoría:** Usar variables de sesión PostgreSQL (`SET LOCAL app.current_user_id = ...`) en los triggers de `sc_seguridad.tb_bitacora`, eliminando el `SELECT MAX(bita_id)` desde PHP.
3. **Unificar Modelos y Controladores:** Fusionar los modelos y controladores repetidos (`Ciudadano` / `Ciudadano_mnt`, `Empresa` / `Empresa_mnt`, `OrdenGiro` / `OrdenGiro_rep`).

### Fase 3: Modernización Arquitectónica (Largo Plazo)
1. Migrar la capa backend a un framework moderno estructurado (**Laravel** o **Symfony**), aprovechando Eloquent ORM, migraciones de base de datos, sistema de colas (Redis/RabbitMQ) para el cron desatendido de SATCH, y pruebas unitarias automáticas (PHPUnit).
2. Centralizar la autenticación municipal mediante OAuth 2.0 / OpenID Connect institucional en lugar de consultas cURL directas a endpoints SOAP/REST antiguos.
