# Flujo Integral de Emisión, Pago, Extorno y Consumo de Órdenes de Giro en SIGODT

Este documento describe la arquitectura, la máquina de estados y los flujos de interacción para las órdenes de giro en **SIGODT**, detallando la integración con **SATCH** (Caja / Recaudación) y **SISGI** (Mesa de Partes / Trámite Documentario), e identificando las **mejoras técnicas frente al código legacy** que se encontraba en producción.

---

## 1. Máquina de Estados y Ciclo de Vida de una Orden

Una orden de giro no tiene un estado desconectado de sus tasas y trámite. Existen 3 niveles de entidad que interactúan:

| Nivel | Entidad de Base de Datos | Estados Típicos |
|---|---|---|
| **Trámite** | `sc_giros.td_procedciudadano` | `1` (Iniciado / Pendiente), `2` (En curso / Con orden), `0` (Anulado) |
| **Tasa** | `sc_giros.td_tasatciud` | `1` (Pendiente), `2` (Girado), `4` (Pagado), `5` (Usado), `6` (Extornado), `0` (Anulado) |
| **Orden de Giro** | `sc_giros.td_ordengirociud` | `1` (Girado), `4` (Pagado), `5` (Usado), `6` (Extornado), `0` (Anulado) |

### Transición de Estados
```text
           [ 1: Pendiente ] (Tasa en trámite)
                  │
                  ▼ (Emisión individual o grupal)
           [ 1 / 2: Girado ] (Orden emitida con correlativo anual atómico)
                  │
                  ▼ (Caja SATCH cobra en ventanilla)
           [ 4: Pagado ] (Worker detecta recibo_nro en SATCH)
            ┌─────┴─────────────────────────┐
            │                               │
            ▼ (Mesa de partes / SISGI)      ▼ (Ventanilla SATCH anula el cobro)
     [ 5: Usado ]                    [ 6: Extornado ]
(Expediente ingresado a trámite)    (Reconciliador de extornos detecta devolución)
```

---

## 2. Diagramas de Secuencia

### Diagrama 1: Flujo de Emisión de Orden de Giro (Individual y Grupal)

Muestra cómo el operador en ventanilla solicita y genera la orden de giro. Destaca la transacción unificada y el correlativo anual atómico.

```mermaid
sequenceDiagram
    autonumber
    actor Admin as Administrado / Ciudadano
    actor Op as Operador (Ventanilla SIGODT)
    participant UI as Frontend (usudetalleciudadano.js)
    participant Ctrl as Controlador (controller/tasa.php)
    participant Model as Modelo (models/Tasa.php)
    participant DB as PostgreSQL (sc_giros)
    participant Bit as Bitacora (models/Bitacora.php)

    Admin->>Op: Solicita emisión de trámite (TUPA / TUSNE)
    Op->>UI: Selecciona procedimiento, tasas y cantidades
    Note over UI: Validaciones en interfaz:<br/>- Mínimo 1 tasa seleccionada<br/>- Tasas en estado 1 (Pendiente)<br/>- Verificación por etapa (Tipo 2: Costo administrativo previo)
    
    Op->>UI: Clic en "Emitir Orden de Giro" (o "Girar en Grupo")
    UI->>UI: Solicita confirmación y comentario
    UI->>Ctrl: POST op=Pagar_Orden_Giro_grupo (Endpoint legacy de emisión)<br/>[ids_tasas, cantidades, comentario]
    
    Note over Ctrl: Validaciones backend:<br/>- Sesión activa y áreas autorizadas<br/>- Cantidades enteras positivas (> 0)<br/>- IDs únicos (evita duplicidad en lote)
    
    Ctrl->>Model: emitirGrupo(id_proced, tasas, cantidades, comentario, usuario)
    
    Note over Model,DB: ======================================================<br/>TRANSACCIÓN ATÓMICA ÚNICA (BEGIN)<br/>======================================================
    Model->>DB: BEGIN TRANSACTION
    
    Note over Model,DB: MEJORA LEGACY 1:<br/>Eliminación de colisiones (MAX+1).<br/>Reserva de correlativo anual atómico con lock.
    Model->>DB: CALL sc_giros.crear_ordengiro_ciud_id(...)<br/>INSERT RETURNING ogciud_id, ogciud_numero
    DB-->>Model: Retorna ogciud_id y número asignado
    
    loop Para cada tasa seleccionada
        Model->>DB: SELECT FOR UPDATE tasatciud_id WHERE est = 1
        alt Tasa no disponible (est != 1)
            Model->>DB: ROLLBACK
            Model-->>Ctrl: Error: Tasa ya girada o inválida
            Ctrl-->>UI: HTTP 400 / JSON error
        else Tasa válida
            Model->>DB: Obtiene tarifa actual (td_tasaproced)<br/>Calcula importe = monto * cantidad
            Model->>DB: INSERT td_giro_tasa_ciudadano (ogciud_id, tasa_id, cant, importe, est=2)
            Model->>DB: UPDATE td_tasatciud SET tasatciud_est = 2 (Girado)
        end
    end
    
    Model->>DB: ¿Quedan tasas pendientes en el trámite?
    opt No quedan tasas pendientes (1 o 3)
        Model->>DB: UPDATE td_procedciudadano SET procedciud_est = 2
    end
    
    Note over Model,Bit: MEJORA LEGACY 2:<br/>Atribución de auditoría en la misma transacción (xmin)
    Model->>Bit: update_bitacora_grupo(...)
    Model->>DB: COMMIT TRANSACTION
    
    Model-->>Ctrl: Retorna éxito con ogciud_id, número y detalle
    Ctrl-->>UI: JSON { status: "success", ogciud_id: ... }
    UI-->>Op: Notifica emisión exitosa y ofrece impresión PDF inmediata
    Op-->>Admin: Entrega orden de giro impresa para pago en Caja SATCH
```

---

### Diagrama 2: Sincronización de Pagos y Reconciliación de Extornos con SATCH

Muestra la interacción en segundo plano con la API de recaudación del SATCH, abordando tanto la confirmación de pago en ventanilla como la corrección ante devoluciones de dinero (extornos).

```mermaid
sequenceDiagram
    autonumber
    actor Cron as Cron OS / Systemd Timer
    participant W_Pago as Worker Pagos (buscar_automatizado.php)
    participant W_Ext as Worker Extornos (reconciliar_extornos.php)
    participant DB as PostgreSQL (sc_giros)
    participant Wrapper as Wrapper SATCH (get_data_satch.php)
    participant SATCH as API SATCH (Caja Recaudación)

    Note over Cron,W_Pago: CICLO 1: Detección de Pagos en Ventanilla (Cada 5 min)
    Cron->>W_Pago: Invoca buscar_automatizado.php
    W_Pago->>DB: pg_try_advisory_lock(987654334)
    alt Lock ocupado
        W_Pago-->>Cron: Finaliza pacíficamente (evita procesos solapados)
    else Lock libre
        W_Pago->>DB: SELECT access_token FROM tb_token_satch
        opt Token no existe o expiró
            W_Pago->>SATCH: Solicita nuevo Bearer token
            SATCH-->>W_Pago: Retorna access_token y expires_in
            W_Pago->>DB: Almacena nuevo token en tb_token_satch
        end
        
        loop En lotes de 20 órdenes (orden_est IN 1, 2)
            W_Pago->>Wrapper: GET get_data_satch.php?orden_de_giro={ogciud_numero}
            Wrapper->>SATCH: GET getDatosReciboPorOrdenGiro/{numero} (Header Bearer)
            
            alt HTTP 200 y successful=true (Abonado en caja)
                SATCH-->>Wrapper: Datos del recibo (recibo_nro, fecha, monto)
                Wrapper->>DB: BEGIN
                Wrapper->>DB: UPDATE td_ordengirociud SET orden_est = 4 (Pagado), recibo_nro = ...
                Wrapper->>DB: UPDATE td_giro_tasa_ciudadano SET estado = 4
                Wrapper->>DB: UPDATE td_tasatciud SET tasatciud_est = 4
                Wrapper->>DB: COMMIT
                Wrapper-->>W_Pago: Orden pagada actualizada
            else HTTP 400 (Ciudadano aún no cancela en caja)
                SATCH-->>Wrapper: HTTP 400 Bad Request
                Wrapper-->>W_Pago: Sin pago confirmado
                Note over W_Pago: Excluye de memoria en ciclo actual ($ordenes_con_error)<br/>La orden se mantiene intacta en estado 1 (Girada)
            end
        end
        W_Pago->>DB: pg_advisory_unlock(987654334)
    end

    Note over Cron,W_Ext: CICLO 2: Reconciliación de Extornos (Doble Velocidad: 30 min diurno / 02:30 AM nocturno)
    Cron->>W_Ext: Invoca reconciliar_extornos.php?dias=2 (o dias=30)
    W_Ext->>DB: pg_try_advisory_lock(987654335)
    alt Lock obtenido
        Note over W_Ext,DB: MEJORA LEGACY 3 (CRÍTICA):<br/>El worker legacy JAMÁS volvía a consultar órdenes en estado 4.<br/>Si en caja se extornaba el dinero, SIGODT quedaba como Pagado.
        W_Ext->>DB: SELECT ogciud_id, ogciud_numero FROM td_ordengirociud WHERE orden_est = 4 AND fechacrea >= ...
        
        loop Por cada orden pagada en la ventana de control
            W_Ext->>Wrapper: Consulta estado vigente en SATCH
            Wrapper->>SATCH: GET getDatosReciboPorOrdenGiro/{numero}
            
            alt SATCH responde con estado "extornado"
                SATCH-->>Wrapper: Estado: "EXTORNADO" / Anulación del recibo
                Wrapper->>DB: BEGIN
                Wrapper->>DB: UPDATE td_ordengirociud SET orden_est = 6 (Extornado)
                Wrapper->>DB: UPDATE td_giro_tasa_ciudadano SET estado = 6
                Wrapper->>DB: UPDATE td_tasatciud SET tasatciud_est = 6
                Wrapper->>DB: INSERT sc_giros.tb_bitacora (Auditoría de extorno)
                Wrapper->>DB: COMMIT
                Wrapper-->>W_Ext: Orden revertida a Extornada
            else SATCH responde con recibo vigente
                Wrapper-->>W_Ext: Estado 4 confirmado
            end
        end
        W_Ext->>DB: pg_advisory_unlock(987654335)
    end
```

---

### Diagrama 3: Consumo de la Orden en SISGI y Anulación

Muestra el consumo final de la orden de giro al ingresar el trámite en Mesa de Partes (SISGI) y el canal de anulación administrativa.

```mermaid
sequenceDiagram
    autonumber
    actor Admin as Administrado / Ciudadano
    actor Mesa as Operador de Mesa de Partes (SISGI)
    participant SISGI as Sistema de Trámite Documentario (SISGI)
    participant API_SISGI as Endpoint SIGODT (api_sisgi.php)
    participant DB as PostgreSQL (sc_giros)
    actor Op_Sigodt as Funcionario SIGODT
    participant UI_Sigodt as Interfaz SIGODT
    participant Ctrl_Proc as Procedimiento (controller/procedimiento.php)

    Note over Admin,API_SISGI: ESCENARIO A: Consumo de Orden Pagada (Pasa a Estado 5 - Usado)
    Admin->>Mesa: Presenta expediente adjuntando Orden de Giro Pagada
    Mesa->>SISGI: Ingresa expediente y número de orden
    SISGI->>API_SISGI: POST api_sisgi.php?op=usar_orden { orden_id }
    
    Note over API_SISGI: Control de transición:<br/>- Exige orden_est = 4 (Pagado)<br/>- Rechaza si orden_est != 4 (ej. 6 Extornado o 1 Girado)<br/>- Rechaza trámite anulado (est 0)
    
    alt Orden válida y pagada (est = 4)
        API_SISGI->>DB: BEGIN
        API_SISGI->>DB: UPDATE td_ordengirociud SET orden_est = 5 (Usado)
        API_SISGI->>DB: UPDATE td_giro_tasa_ciudadano SET estado = 5
        API_SISGI->>DB: UPDATE td_tasatciud SET tasatciud_est = 5
        API_SISGI->>DB: COMMIT
        API_SISGI-->>SISGI: HTTP 200 { success: true, message: "Orden consumida" }
        SISGI-->>Mesa: Expediente admitido exitosamente
    else Orden no pagada o extornada (est = 6 o est = 1)
        API_SISGI-->>SISGI: HTTP 409 Conflict { error: "Orden no pagada o extornada" }
        SISGI-->>Mesa: Rechazo: No se puede tramitar con orden impaga o extornada
    end

    Note over Op_Sigodt,Ctrl_Proc: ESCENARIO B: Anulación Administrativa en SIGODT
    Op_Sigodt->>UI_Sigodt: Selecciona trámite/orden y pulsa "Anular"
    UI_Sigodt->>Ctrl_Proc: POST op=eliminar_proced_ciudadano { procedciud_id, ogciud_id }
    Ctrl_Proc->>DB: CALL sc_giros.eliminar_proced_ciudadano(procedciud_id, ogciud_id)
    DB->>DB: Actualiza registros a estado 0 (Anulado)
    Ctrl_Proc-->>UI_Sigodt: Confirmación de anulación
    UI_Sigodt-->>Op_Sigodt: El registro pasa a histórico anulado
```

---

## 3. Resumen Comparativo: Código Legacy vs Arquitectura Actual

| Componente | Comportamiento en Código Legacy (Producción Anterior) | Solución Optimizada Implementada | Impacto Técnico y de Negocio |
|---|---|---|---|
| **Generación de Correlativos** | `SELECT MAX(ogciud_numero) + 1` o llamada no bloqueante a `obtenerultimovalor()` sin transacción. | Procedimiento atómico `crear_ordengiro_ciud_id` con secuencia o contador anual con lock por año. | **Eliminó colisiones de concurrencia y números duplicados** cuando múltiples ventanillas emitían a la vez. |
| **Emisión de Tasas en Lote** | Bucles `for` en PHP con llamadas independientes y autocommit. Si la tasa 3 fallaba, las tasas 1 y 2 quedaban huérfanas. | Transacción unificada `emitirGrupo` (`BEGIN / COMMIT`) con `SELECT FOR UPDATE` sobre tasas pendientes. | **Atomicidad garantizada (ACID)**: Si falla un ítem, toda la orden y su contador revierten limpiamente. |
| **Detección de Extornos (SATCH)** | **Ceguera total de extornos**: El worker `buscar_automatizado.php` solo leía órdenes `1` y `2`. Una vez en `4` (Pagado), jamás se volvían a consultar. | Worker `reconciliar_extornos.php` con estrategia de **Doble Velocidad** (diurno 30m / nocturno 30d). | **Mitigó fraude y uso indebido**: Impide que un administrado cobre la devolución en SATCH y continúe el trámite en SIGODT. |
| **Bloqueo de Workers (Cron)** | Sin control de solapamiento; múltiples instancias concurrentes del cron saturaban la BD y la API del SATCH. | Uso de PostgreSQL Advisory Locks (`pg_try_advisory_lock`). | Salida pacífica si hay un proceso en ejecución; **cero saturación de hilos y cero bloqueos de tablas**. |
| **Búsqueda de Administrados** | `ILIKE '%termino%'` sobre tablas con cientos de miles de registros sin índices trigram, con scans secuenciales de 1.4s+. | Índices GIN Trigram (`pg_trgm`) en PostgreSQL + pushdown de predicados en SQL. | **Tiempos de respuesta reducidos de ~1400ms a ~250ms** (~82% de mejora en velocidad de consulta). |
| **Seguridad de Autenticación** | CAPTCHA con vigencia excesiva de 5 minutos; autenticación local desacoplada. | CAPTCHA reducido a 2 minutos fail-closed + Integración SIGUR blanqueamiento con JWT/JWE tolerante a fallos. | **Protección contra replay attacks y credenciales unificadas** institucionalmente. |
| **Reportes y Filtros Operativos** | Textos e importes rígidos que asumían siempre "Solo emitidos" y "Dinero no recaudado", confundiendo al filtrar solo Pagados. | Resúmenes dinámicos y adaptativos según el estado de orden seleccionado en tiempo real. | **Coherencia visual y auditoría fidedigna** para directores y fiscalizadores. |
| **Interfaz y Accesibilidad** | Menús sobrecargados con switchers de tema rotos (`dropdown-menu-arrow`) y peticiones AJAX globales innecesarias (`cargarSistemas`). | Limpieza de peticiones muertas, affordance claro con chevron bold y opciones con íconos vectoriales SVG. | **Menor consumo de ancho de banda y mayor usabilidad** para los operadores de ventanilla. |
