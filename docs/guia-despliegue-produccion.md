# Guía Maestra de Pase a Producción: SIGODT (Rocky Linux)

> **Documento Operativo de Despliegue Definitivo**  
> **Destinatario:** Administradores de Sistemas / DevOps / Equipo de Desarrollo  
> **Plataforma:** Rocky Linux 8 / 9 | Apache HTTPD | PHP 8.x | PostgreSQL 14+  
> **Ruta del Proyecto:** `/var/www/html/SIGODT`  

---

## 1. Resumen de Componentes Clave Desplegados

Esta versión incorpora cuatro mejoras críticas de infraestructura y arquitectura:
1. **Correlativos Atómicos por Año:** Eliminación de colisiones y duplicados (`duplicate key 000001-2026`) mediante un contador transaccional serializado a nivel de motor PostgreSQL.
2. **Workers de Pagos y Extornos SATCH:** Detección continua de pagos (`buscar_automatizado.php`) y conciliación de extornos de ventanilla (`reconciliar_extornos.php`) con estrategia de doble velocidad y soporte de backfill histórico.
3. **Consulta en Dos Niveles de Empresas/RUC:** Búsqueda local en `public.tb_empresa` con fallback automático a la API SUNAT PIDE (`https://www.munichiclayo.gob.pe/Pide/Sunat/{ruc}`) y autorregistro instantáneo.
4. **Optimización de Índices:** Índices B-Tree compuestos sobre `sc_giros.td_ordengirociud` y tablas de trámites para acelerar las búsquedas operativas y el reporte de recaudación.

---

## 2. Diagnóstico Previo: ¿La Base de Datos de Producción ya tiene los Correlativos Atómicos?

Antes de tocar archivos o ejecutar scripts, debes saber en qué estado se encuentra la base de datos de producción respecto a los correlativos.

### 2.1 ¿Cuál era el problema anterior?
Anteriormente, el sistema usaba `sc_giros.ordengiro_id_sequence` y procedimientos con `SELECT MAX(...) + 1` no serializados. Si dos funcionarios emitían una orden al mismo milisegundo, ambos obtenían el mismo número, provocando una excepción de clave duplicada. Además, al cambiar de año, la secuencia requería reseteo manual propenso a errores.

### 2.2 ¿Qué implementa la solución atómica?
* **Tabla `sc_giros.giro_year_counter`:** Almacena el último correlativo emitido por cada año (`year PRIMARY KEY, last_value bigint`).
* **Función `sc_giros.allocate_giro_number(p_date)`:** Incrementa y formatea atómicamente el número (`027050-2026`) usando `INSERT ... ON CONFLICT (year) DO UPDATE SET last_value = c.last_value + 1 RETURNING last_value`. Serializa a nivel de base de datos hasta el commit.
* **Función `sc_giros.crear_ordengiro_ciud_id(p_usuario_id, p_comentario)`:** Inserta la cabecera y retorna el nuevo ID directamente a la misma conexión PHP sin dependencias de variables globales.

---

### 2.3 Script de Diagnóstico SQL (Ejecutar en producción)

Entra a PostgreSQL por consola (`psql -U postgres -d <nombre_bd>`) o desde pgAdmin y corre esta consulta unificada de diagnóstico:

```sql
SELECT 
    -- 1. Verifica si existe la tabla contadora
    EXISTS (
        SELECT 1 FROM information_schema.tables 
        WHERE table_schema = 'sc_giros' AND table_name = 'giro_year_counter'
    ) AS tabla_contador_existe,

    -- 2. Verifica si existe la función asignadora atómica
    EXISTS (
        SELECT 1 FROM pg_proc p 
        JOIN pg_namespace n ON n.oid = p.pronamespace 
        WHERE n.nspname = 'sc_giros' AND p.proname = 'allocate_giro_number'
    ) AS funcion_allocate_existe,

    -- 3. Verifica si existe la función de inserción directa
    EXISTS (
        SELECT 1 FROM pg_proc p 
        JOIN pg_namespace n ON n.oid = p.pronamespace 
        WHERE n.nspname = 'sc_giros' AND p.proname = 'crear_ordengiro_ciud_id'
    ) AS funcion_insert_existe,

    -- 4. Valor del contador actual para este año
    (SELECT last_value FROM sc_giros.giro_year_counter 
     WHERE year = EXTRACT(year FROM CURRENT_DATE)::integer) AS contador_actual_2026,

    -- 5. Máximo correlativo real existente en las órdenes de este año
    (SELECT COALESCE(MAX(split_part(ogciud_id, '-', 1)::bigint), 0) 
     FROM sc_giros.td_ordengirociud 
     WHERE split_part(ogciud_id, '-', 2)::integer = EXTRACT(year FROM CURRENT_DATE)::integer) AS max_orden_historica_2026;
```

---

### 2.4 Matriz de Interpretación del Diagnóstico

| Escenario Detectado | Significado | Acción a Realizar |
|---|---|---|
| `tabla_contador_existe = false` ó `funcion_allocate_existe = false` | **La BD NO tiene la solución atómica.** Sigue en el modelo antiguo con riesgo de colisión. | **Ejecutar la migración completa** (Ver Paso 4.1). |
| Tablas y funciones existen, pero `contador_actual_2026 < max_orden_historica_2026` | La migración se aplicó antes, pero hubo órdenes emitidas por fuera o el seed quedó desfasado. | **Ejecutar el Re-seed de sincronización** (Ver Paso 4.2). |
| Tablas y funciones existen, y `contador_actual_2026 >= max_orden_historica_2026` | **La base de datos YA ESTÁ 100% LISTA Y ALINEADA.** | Saltar el paso de migración y continuar al despliegue de código. |

---

## 3. Preparación del Servidor y Variables de Entorno

### 3.1 Archivo `.env` en Producción
Asegurar que `/var/www/html/SIGODT/.env` contenga los valores correctos para el entorno productivo:

```dotenv
# Base de Datos
DB_HOST=localhost
DB_PORT=5432
DB_NAME=sigodt_db
DB_USER=sigodt_prod_user
DB_PASS=tu_password_seguro

# API SATCH (Producción - no usar api.test ni puertos 81)
SATCH_API_URL=http://satch.gob.pe/api/v1/recibos/getDatosReciboPorOrdenGiro/
SATCH_AUTH_URL=http://satch.gob.pe/oauth/token
SATCH_CLIENT_ID=credencial_produccion_cliente
SATCH_CLIENT_SECRET=credencial_produccion_secreto
SATCH_RECONCILIATION_DAYS=15

# SUNAT / PIDE (Servicio de Consulta RUC de la MPCH)
PIDE_SUNAT_URL=https://www.munichiclayo.gob.pe/Pide/Sunat/
```

### 3.2 Permisos de Directorios en Rocky Linux
```bash
# Permisos del directorio web
chown -R apache:apache /var/www/html/SIGODT
find /var/www/html/SIGODT -type d -exec chmod 755 {} \;
find /var/www/html/SIGODT -type f -exec chmod 644 {} \;

# Carpetas de logs con permisos de escritura para Apache y CLI:
mkdir -p /var/www/html/SIGODT/logs /var/www/html/SIGODT/api/logs
chmod -R 775 /var/www/html/SIGODT/logs /var/www/html/SIGODT/api/logs
chown -R apache:apache /var/www/html/SIGODT/logs /var/www/html/SIGODT/api/logs
```

---

## 4. Paso a Paso del Despliegue en Producción

Sigue este orden cronológico estricto:

### FASE 1: Pausar Emisión y Respaldar Base de Datos (Ventana de Mantenimiento de 5 min)
1. **Poner pantalla de mantenimiento:** (o pausar temporalmente el VirtualHost si es horario no laboral).
2. **Generar backup preventivo de PostgreSQL:**
   ```bash
   pg_dump -U postgres -d <nombre_bd> -F c -b -v -f /root/backup_sigodt_pre_deploy_$(date +%Y%m%d_%H%M%S).dump
   ```

---

### FASE 2: Descargar Código Actualizado
```bash
cd /var/www/html/SIGODT
git fetch origin
git checkout dev  # o la rama productiva acordada
git pull origin dev
```

---

### FASE 3: Smoke Tests de Código PHP (Validación Inmediata)
Verifica que las clases y contratos sintácticos no tengan errores tras la descarga:
```bash
php /var/www/html/SIGODT/tests/buscar_automatizado_test.php
php /var/www/html/SIGODT/tests/reconciliar_extornos_test.php
php /var/www/html/SIGODT/tests/auto_provision_area_test.php
```
> **Resultado requerido:** Todos deben arrojar `PASS: ... contracts verified successfully.` en verde.

---

### FASE 4: Aplicación de Migraciones de Base de Datos

#### Caso 4.1: Si el diagnóstico indicó que NO EXISTE el módulo de correlativos:
Ejecuta la migración oficial (está protegida en transacción atómica con `lock_timeout = 5s`):
```bash
psql -U postgres -d <nombre_bd> -f /var/www/html/SIGODT/database/migrations/20261016_atomic_giro_numbering.sql
```

**Asignar permisos al usuario de base de datos de SIGODT (Obligatorio):**
```sql
-- Ejecutar en psql como postgres:
GRANT USAGE ON SCHEMA sc_giros TO sigodt_prod_user;
GRANT SELECT, INSERT, UPDATE ON sc_giros.giro_year_counter TO sigodt_prod_user;
GRANT EXECUTE ON FUNCTION sc_giros.allocate_giro_number(date) TO sigodt_prod_user;
GRANT EXECUTE ON FUNCTION sc_giros.crear_ordengiro_ciud_id(integer, text) TO sigodt_prod_user;
GRANT EXECUTE ON PROCEDURE sc_giros.crear_ordengiro_ciud(integer, text) TO sigodt_prod_user;
```

#### Caso 4.2: Si ya existía pero el contador estaba desfasado (Re-Seed seguro):
Ejecuta únicamente esta instrucción SQL para nivelar el contador al valor máximo real:
```sql
INSERT INTO sc_giros.giro_year_counter AS c(year, last_value)
SELECT split_part(ogciud_id, '-', 2)::integer,
       MAX(split_part(ogciud_id, '-', 1)::bigint)
FROM sc_giros.td_ordengirociud
GROUP BY split_part(ogciud_id, '-', 2)::integer
ON CONFLICT (year) DO UPDATE SET last_value = GREATEST(c.last_value, EXCLUDED.last_value);
```

#### Caso 4.3: Aplicar migración de índices de optimización (Recomendado):
```bash
psql -U postgres -d <nombre_bd> -f /var/www/html/SIGODT/database/migrations/20261019_optimize_giros_and_search_indexes.sql
```

---

### FASE 5: Saneamiento y Alineación Histórica Inicial (Backfill Único)

Antes de programar las tareas automáticas en cron, ejecuta manualmente la alineación completa:

```bash
# 1. Smoke test de conectividad en vivo con SATCH (prueba 1 día):
/usr/bin/php /var/www/html/SIGODT/api/reconciliar_extornos.php dias=1
tail -n 15 /var/www/html/SIGODT/api/logs/reconciliacion_extornos_$(date +%Y_%m_%d).log

# 2. Poner al día todas las órdenes giradas pendientes de pago (1 -> 4):
/usr/bin/php /var/www/html/SIGODT/api/buscar_automatizado.php

# 3. Auditar TODO el historial de órdenes pagadas contra SATCH (Reversión de extornos pasados a estado 6):
/usr/bin/php /var/www/html/SIGODT/api/reconciliar_extornos.php dias=0
```
*(Al culminar estos pasos, la base de datos queda 100% consistente y sincronizada con el SATCH).*

---

### FASE 6: Configurar las Tareas Programadas (Crontab)

Abre la tabla de tareas con `crontab -e` (como `root` o el usuario operador autorizado) y agrega el bloque:

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

Confirmar que se guardó:
```bash
crontab -l
```

---

### FASE 7: Pruebas Funcionales Post-Despliegue (Postflight Check)

1. **Reabrir el sistema:** Quitar la pantalla de mantenimiento.
2. **Prueba de Emisión de Orden de Giro:**
   * Ingresar como usuario de ventanilla / mesa de partes.
   * Emitir una orden de giro ciudadana individual o grupal.
   * **Verificar:** Que el número emitido continúe estrictamente la numeración correlativa (`last_value + 1`) y aparezca el botón de impresión sin errores de base de datos.
3. **Prueba de Consulta SUNAT PIDE:**
   * En el modal de trámite o empresa, buscar un RUC que no exista en la base local (ej. de un nuevo proveedor).
   * **Verificar:** Que el spinner consulte a SUNAT, complete automáticamente la Razón Social, Estado y Condición, y registre la empresa en `public.tb_empresa`.
4. **Monitoreo de Logs:**
   * Ejecutar `tail -f /var/www/html/SIGODT/logs/cron_pagos.log`.
   * Verificar que a los 5 minutos el worker despierte, procese su bloque sin errores fatales y libere su lock.

---

## 5. Matriz de Reversión y Contingencia (Rollback)

* **Si la migración de base de datos falla al ejecutarse:**
  La migración `20261016_atomic_giro_numbering.sql` está encapsulada en un bloque transaccional `BEGIN / COMMIT` con `statement_timeout = '30s'` y `lock_timeout = '5s'`. Si ocurre cualquier error, PostgreSQL hace rollback automático y no deja ningún cambio sucio en la base de datos.
* **Límite crítico sobre Rollback posterior:**
  Una vez que el nuevo código haya emitido órdenes con la nueva función `allocate_giro_number`, **NO se debe restaurar el código ni los procedimientos antiguos**. Regresar a la vieja secuencia o procedimiento causaría colisiones inmediatas con los números que la nueva tabla contadora ya incrementó.
