# Guía Operativa: Workers SATCH, Cron en Producción y Reconciliación de Extornos

Este documento constituye la guía definitiva para la operación, auditoría y puesta en producción de los workers de sincronización entre **SIGODT** y la **Caja de SATCH**:
1. **Worker actual de pagos (`buscar_automatizado.php`)**: cómo funciona internamente y cómo determinar su frecuencia de ejecución en el servidor.
2. **Worker de extornos (`reconciliar_extornos.php`)**: lógica de ciclo de vida (`1 → 4 → 6`), mitigación de ventana de riesgo e implementación del cron.
3. **Decisiones y checklist previo al pase a producción**.

---

## 1. Cómo Funciona el Worker Actual (`buscar_automatizado.php`)

El script [`api/buscar_automatizado.php`](file:///home/renzosandovalmio/Proyectos/SIGODT/api/buscar_automatizado.php) es el encargado de consultar en SATCH qué órdenes de giro giradas ya fueron canceladas en ventanilla para marcarlas como pagadas.

```text
[SIGODT: Orden en Estado 1 (Girada)]
              │
              ▼
   ¿Advisory Lock 987654334 libre? ──(No)──► Finaliza (evita ejecuciones simultáneas)
              │ (Sí)
              ▼
   ¿Token SATCH activo y vigente? ──(No)──► Genera nuevo Bearer token en tb_token_satch
              │ (Sí)
              ▼
   Bucle por bloques de 20 órdenes (est IN 1, 2) ORDER BY fechacrea DESC
              │
              ├─► Llama a get_data_satch.php?orden_de_giro=...
              │         │
              │         ▼
              │   API SATCH: getDatosReciboPorOrdenGiro/{orden}
              │         │
              │         ├─► Status 200: ¡Pagada!
              │         │     └─► DB SIGODT: Actualiza orden a estado 4 (Pagado)
              │         │                    Guarda recibo_nro (ej. 202600146459)
              │         │
              │         └─► Status 400: Aún sin pago en ventanilla
              │               └─► La añade a lista de errores en memoria ($ordenes_con_error)
              │                   La orden se mantiene en estado 1 (Girada)
              │                   Pasa a la siguiente orden del bloque
              │
              ▼
   Fin de órdenes pendientes ──► Libera Advisory Lock 987654334
```

### Detalle paso a paso:
1. **Advisory Lock de PostgreSQL (`987654334`)**: Garantiza concurrencia atómica. Si una ejecución previa aún está procesando órdenes, la nueva invocación sale pacíficamente sin duplicar peticiones.
2. **Gestión de Token SATCH**: Valida la vigencia de `access_token` en `sc_giros.tb_token_satch`. Si expiró (`NOW() > fechacrea + expires_in`), invoca [`api/SATCH/tokenGen.php`](file:///home/renzosandovalmio/Proyectos/SIGODT/api/SATCH/tokenGen.php) y guarda el nuevo token.
3. **Paginación en bloques de 20**:
   - Consulta `sc_giros.td_ordengirociud` buscando registros con `orden_est IN (1, 2)`.
   - Incorpora la condición `AND ogciud_id NOT IN (...)` con `$ordenes_con_error` acumuladas en memoria. Esto evita que una orden no pagada vuelva a salir una y otra vez en el mismo ciclo.
4. **Consulta al Wrapper SATCH**:
   - Llama internamente a [`api/SATCH/get_data_satch.php`](file:///home/renzosandovalmio/Proyectos/SIGODT/api/SATCH/get_data_satch.php).
   - **Si SATCH retorna 200 y `successful: true`**: La orden ya fue abonada en caja. En una transacción (`BEGIN / COMMIT`), actualiza `td_ordengirociud` a estado `4` (Pagado) y asocia `recibo_nro`.
   - **Si SATCH retorna 400**: Significa que **el administrado todavía no ha pagado en caja**. `file_get_contents` en PHP falla con `HTTP/1.1 400 Bad Request`, el script registra el aviso en log, excluye la orden del ciclo en memoria y continúa con las demás.

---

## 2. Cómo Averiguar Cada Cuánto se Ejecuta en Producción

En servidores Rocky Linux / CentOS / RHEL, revisa en orden de probabilidad:

### A. Si corre mediante Crontab clásico
Ejecuta como `root`:
```bash
# 1. Búsqueda directa en todos los crontabs del sistema
grep -rnE "(SIGODT|buscar_automatizado)" /var/spool/cron/ /etc/cron* /etc/crontab 2>/dev/null
```
Si el comando devuelve una línea, la primera columna de 5 campos indica la frecuencia:
- `*/5 * * * *` → **Cada 5 minutos** (frecuencia típica recomendada).
- `*/10 * * * *` → **Cada 10 minutos**.
- `0 * * * *` → **Cada hora**.
- `0 8 * * 1-5` → **Una vez al día a las 08:00 AM de lunes a viernes**.

Para revisar manualmente las tablas por usuario:
```bash
cat /var/spool/cron/root 2>/dev/null
cat /var/spool/cron/apache 2>/dev/null
cat /var/spool/cron/nginx 2>/dev/null
```

### B. Si corre mediante Systemd Timer (RHEL / Rocky 8 y 9)
```bash
# Listar todos los timers activos
systemctl list-timers --all | grep -iE "sigodt|php"

# Si encuentras el timer (ej. sigodt-sync.timer), ver su configuración exacta:
systemctl cat sigodt-sync.timer
```
En el archivo se observará `OnCalendar=*:0/5` (cada 5 minutos) o `OnUnitActiveSec=5m`.

### C. Si es invocado externamente vía HTTP (Servidor Central / Task Scheduler)
Si en el servidor no hay ningún cron configurado, significa que una máquina externa realiza peticiones HTTP periódicas vía `curl` o tarea programada. Puedes deducir la frecuencia revisando los logs de acceso de Apache:
```bash
grep "buscar_automatizado.php" /var/log/httpd/*access* 2>/dev/null | tail -n 20
```
*Calcula la diferencia de minutos entre una petición y la siguiente en la columna de fecha y hora.*

### D. Si corre como proceso en segundo plano (Daemon / Loop continuo)
```bash
ps aux | grep -iE "buscar_automatizado" | grep -v grep
```

---

## 3. Worker de Extornos (`reconciliar_extornos.php`)

### Ciclo de vida y dependencia técnica
El ciclo de vida de una orden en SIGODT respecto a caja es estrictamente secuencial:
$$\text{Girada (1)} \xrightarrow{\text{Caja SATCH paga}} \text{Pagada (4)} \xrightarrow{\text{Ventanilla extorna}} \text{Extornada (6)}$$

* **Una orden nunca se extorna directamente desde estado `1`**. Solo se puede extornar lo que previamente fue pagado.
* El worker actual (`buscar_automatizado.php`) solo atiende `est IN (1, 2)`. Una vez que una orden pasa a estado `4` (Pagada), **el worker actual jamás vuelve a consultarla**.
* Por esta razón, [`api/reconciliar_extornos.php`](file:///home/renzosandovalmio/Proyectos/SIGODT/api/reconciliar_extornos.php) consulta exclusivamente órdenes en estado `4`:
  * **Modo deslizante diario (`dias=N`)**: `WHERE est = 4 AND fechacrea >= CURRENT_DATE - ($dias || ' days')::interval`
  * **Modo alineación histórica inicial (`dias=0` o `dias=all`)**: `WHERE est = 4` (sin límite de fecha, analiza y sanea el histórico completo).

### Mitigación de la Ventana de Riesgo (Doble Velocidad)
Si un ciudadano paga a las 10:00 AM y a las 10:20 AM anulan/extornan la operación en caja, existe una ventana de vulnerabilidad: un funcionario en SIGODT podría continuar el trámite o expedir el documento asumiendo que sigue pagado.

Para mitigar este riesgo sin saturar la API de SATCH, se adopta la **Estrategia de Doble Velocidad**:

```text
┌─────────────────────────────────────────────────────────────────────────────┐
│ ESTRATEGIA DE CRON EN PRODUCCIÓN                                            │
├─────────────────────────────────────────────────────────────────────────────┤
│ 1. Flujo de Pagos Nuevos (buscar_automatizado.php):                         │
│    - Frecuencia: Cada 5 minutos (Todo el día)                               │
│    - Universo: Órdenes en estado 1 y 2                                      │
├─────────────────────────────────────────────────────────────────────────────┤
│ 2. Extornos Diurnos Calientes (reconciliar_extornos.php dias=2):            │
│    - Frecuencia: Cada 30 minutos (08:00 a 18:00, Lunes a Viernes)           │
│    - Universo: Pagadas emitidas hoy y ayer (ejecución ultrarrápida)         │
│    - Propósito: Anular de inmediato pagos cancelados en caja el mismo día   │
├─────────────────────────────────────────────────────────────────────────────┤
│ 3. Auditoría Nocturna Profunda (reconciliar_extornos.php dias=30):          │
│    - Frecuencia: 02:30 AM (Todos los días)                                  │
│    - Universo: Todas las órdenes pagadas de los últimos 30 días             │
│    - Propósito: Conciliar extornos administrativos o devoluciones tardías   │
└─────────────────────────────────────────────────────────────────────────────┘
```

### Configuración en Crontab de Producción (`crontab -e`)

```bash
# -----------------------------------------------------------------------------
# SIGODT: Sincronización con SATCH
# -----------------------------------------------------------------------------

# 1. Detección de nuevos pagos (Cada 5 minutos)
*/5 * * * * /usr/bin/php /var/www/html/SIGODT/api/buscar_automatizado.php >> /var/www/html/SIGODT/logs/cron_pagos.log 2>&1

# 2. Reconciliación de extornos recientes en horario de oficina (Cada 30 min, 8am-6pm, Lun-Vie)
*/30 8-18 * * 1-5 /usr/bin/php /var/www/html/SIGODT/api/reconciliar_extornos.php dias=2 >> /var/www/html/SIGODT/logs/cron_extornos.log 2>&1

# 3. Auditoría nocturna profunda de extornos del mes (Diario a las 02:30 AM)
30 2 * * * /usr/bin/php /var/www/html/SIGODT/api/reconciliar_extornos.php dias=30 >> /var/www/html/SIGODT/logs/cron_extornos_deep.log 2>&1
```

> **Aislamiento por Locks de PostgreSQL:**
> - `buscar_automatizado.php` opera con lock `987654334`.
> - `reconciliar_extornos.php` opera con lock `987654335`.
> Ambos scripts pueden coincidir en el mismo segundo sin bloquearse mutuamente.

---

## 4. Qué Hacer según lo que Encuentres en Producción

Cuando entres por SSH a la consola de producción (`Rocky Linux`), sigue esta ruta de decisión:

### Paso Previo: Ejecutar el escaneo
```bash
grep -rnE "(SIGODT|buscar_automatizado)" /var/spool/cron/ /etc/cron* /etc/crontab 2>/dev/null
```

---

### CASO 1: Si SÍ encuentras `buscar_automatizado.php` configurado
Significa que el worker de pagos ya está operando.
1. Anota con qué usuario corre y su frecuencia (ejemplo: cada 5 minutos en el `crontab` de `root`).
2. Abre ese mismo crontab (`crontab -e` con ese usuario).
3. Agrega **únicamente** las dos líneas del worker de extornos al final:
   ```bash
   # Reconciliación de extornos recientes (cada 30 min en horario de oficina)
   */30 8-18 * * 1-5 /usr/bin/php /var/www/html/SIGODT/api/reconciliar_extornos.php dias=2 >> /var/www/html/SIGODT/logs/cron_extornos.log 2>&1

   # Auditoría profunda nocturna de extornos del mes (2:30 AM)
   30 2 * * * /usr/bin/php /var/www/html/SIGODT/api/reconciliar_extornos.php dias=30 >> /var/www/html/SIGODT/logs/cron_extornos_deep.log 2>&1
   ```
4. Guarda y verifica con `crontab -l`.

---

### CASO 2: Si NO encuentras ninguno (Ningún worker está configurado)
Si el comando no arrojó nada y tampoco hay timers activos en `systemctl list-timers`, significa que los workers nunca fueron programados en cron (o solo se ejecutaban manualmente/por disparador externo).

Sigue estos pasos para dejarlos activos desde cero:

#### 1. Crear directorios de logs con permisos de escritura:
```bash
mkdir -p /var/www/html/SIGODT/logs /var/www/html/SIGODT/api/logs
chmod -R 775 /var/www/html/SIGODT/logs /var/www/html/SIGODT/api/logs
chown -R apache:apache /var/www/html/SIGODT/logs /var/www/html/SIGODT/api/logs
```

#### 2. Alineación Histórica Inicial (Initial Backfill Único):
Antes de dejar los crons periódicos desatendidos, es fundamental sincronizar y sanear la base de datos completa por única vez:

```bash
# Paso A: Poner al día todas las órdenes giradas pendientes (1 -> 4)
/usr/bin/php /var/www/html/SIGODT/api/buscar_automatizado.php

# Paso B: Auditar TODO el historial de órdenes pagadas contra SATCH (dias=0 sin límite de fecha)
/usr/bin/php /var/www/html/SIGODT/api/reconciliar_extornos.php dias=0
```
> **¿Por qué este orden?**  
> 1. El script de pagos pasa a estado `4` (Pagado) todas las órdenes que el ciudadano ya canceló en caja.
> 2. Inmediatamente después, el reconciliador con `dias=0` toma **todas las órdenes en estado 4 de la historia** y verifica contra SATCH si alguna fue extornada en el pasado para actualizarla a `6`.  
> 3. Al finalizar ambos comandos, **el sistema queda 100% alineado y saneado**. A partir de ahí, los crons periódicos solo necesitan vigilar la ventana corta (`dias=2` diurno / `dias=30` nocturno) y el de pagos sigue revisando continuamente las nuevas órdenes giradas.

#### 3. Instalar ambos workers en el Crontab:
Ejecuta:
```bash
crontab -e
```
Pega el bloque completo:
```bash
# =============================================================================
# SIGODT: Sincronización Automática con SATCH
# =============================================================================

# 1. Detección continua de órdenes pagadas en ventanilla (cada 5 minutos)
*/5 * * * * /usr/bin/php /var/www/html/SIGODT/api/buscar_automatizado.php >> /var/www/html/SIGODT/logs/cron_pagos.log 2>&1

# 2. Detección de extornos recientes en horario laboral (cada 30 min, 8am a 6pm, Lun-Vie)
*/30 8-18 * * 1-5 /usr/bin/php /var/www/html/SIGODT/api/reconciliar_extornos.php dias=2 >> /var/www/html/SIGODT/logs/cron_extornos.log 2>&1

# 3. Auditoría profunda nocturna de extornos del mes (todos los días a las 02:30 AM)
30 2 * * * /usr/bin/php /var/www/html/SIGODT/api/reconciliar_extornos.php dias=30 >> /var/www/html/SIGODT/logs/cron_extornos_deep.log 2>&1
```

#### 4. Confirmar que se registraron correctamente:
```bash
crontab -l
```

#### 5. Verificar que están corriendo en vivo:
Espera 5 minutos y revisa que los archivos de log se estén alimentando:
```bash
tail -f /var/www/html/SIGODT/logs/cron_pagos.log
tail -f /var/www/html/SIGODT/logs/cron_extornos.log
```

---

## 5. Checklist Técnico Previo al Despliegue en Producción

Antes de activar los crons en el servidor definitivo, verificar:

- [ ] **1. Archivo `.env` en Producción:**
  - `SATCH_API_URL`: Verificar que apunte a la URL de producción (no `api.test` ni puertos de desarrollo).
  - `SATCH_CLIENT_ID` y `SATCH_CLIENT_SECRET`: Credenciales oficiales emitidas por SATCH para el entorno productivo.
  - `SATCH_TOKEN_URL`: Endpoint de autenticación de producción.
- [ ] **2. Permisos del Directorio de Logs:**
  Asegurar que existan y tengan permisos de escritura para el usuario que ejecuta el cron (`root` o `apache`):
  ```bash
  mkdir -p /var/www/html/SIGODT/logs /var/www/html/SIGODT/api/logs
  chmod 775 /var/www/html/SIGODT/logs /var/www/html/SIGODT/api/logs
  ```
- [ ] **3. Prueba de Humo en Servidor (Dry-Run):**
  Ejecutar una prueba manual por consola antes de habilitar el cron:
  ```bash
  /usr/bin/php /var/www/html/SIGODT/api/reconciliar_extornos.php dias=2
  tail -n 30 /var/www/html/SIGODT/api/logs/reconciliacion_extornos_$(date +%Y_%m_%d).log
  ```
- [ ] **4. Política de Rotación de Logs (`logrotate`):**
  Para evitar que los archivos `.log` crezcan descontroladamente en el disco del servidor, crear `/etc/logrotate.d/sigodt-crons`:
  ```text
  /var/www/html/SIGODT/logs/*.log /var/www/html/SIGODT/api/logs/*.log {
      weekly
      missingok
      rotate 8
      compress
      notifempty
  }
  ```
