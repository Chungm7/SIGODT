# Cómo funciona una orden de giro en SIGODT

Esta guía explica el flujo actual del sistema: registrar un trámite, asociar sus tasas, emitir una orden individual o agrupada, consultar el pago y registrar su uso, anulación o extorno. **Emitir una orden no significa pagarla**, y el estado de una orden no equivale necesariamente al estado de todo el trámite.

> Alcance: código del flujo ciudadano y las integraciones que actualizan sus órdenes. No se realizaron operaciones en la base de datos ni se inspeccionó su configuración real. Las referencias describen el código disponible, no garantizan qué versión está desplegada.

## 1. Resumen del proceso

El recorrido habitual que representan las pantallas y las integraciones es:

```text
Seleccionar área y procedimiento
    → Registrar el trámite del administrado
    → Asociar las referencias de sus tasas
    → Elegir conceptos y cantidades
    → Emitir una orden de giro
    → Imprimir o consultar la orden
    → Consultar el pago mediante SATCH
    → Registrar el uso mediante SISGI
```

La anulación y el extorno son rutas alternativas, no pasos obligatorios al final. La emisión puede ser parcial: un trámite puede tener varias órdenes y conceptos todavía pendientes.

**Este recorrido no es una máquina de estados global estricta.** Algunas rutas imponen transiciones concretas, pero otras permiten modificar el estado sin aplicar las mismas restricciones.

## 2. Entidades: qué es cada cosa

| Entidad | Qué representa | Referencia principal |
|---|---|---|
| Documento/TUPA/TUSNE | Catálogo al que pertenece un procedimiento | `sc_giros.tm_tupa` |
| Procedimiento del catálogo | Definición del servicio o trámite y su área | `sc_giros.tm_procedimiento` |
| Tasa del procedimiento | Concepto y monto configurados en el catálogo | `sc_giros.td_tasaproced` |
| Trámite del administrado | Instancia concreta del procedimiento para un ciudadano | `sc_giros.td_procedciudadano` |
| Tasa del trámite | Referencia de tasa asociada a esa instancia, con estado propio | `sc_giros.td_tasatciud` |
| Orden de giro | Documento emitido que reúne uno o varios conceptos | `sc_giros.td_ordengirociud` |
| Detalle de la orden | Asociación entre orden y tasa, con cantidad, importe y estado | `sc_giros.td_giro_tasa_ciudadano` |

No existe una equivalencia universal entre los códigos de estado de estas entidades. Por ejemplo, **`2` aparece como Girado para una tasa**, mientras que la vista operativa muestra **Girado para una orden con `1`**.

## 3. Registro del trámite y asociación de tasas

### 3.1 Selección del catálogo

La selección de área y procedimiento para registrar un trámite se valida con la sesión del usuario y sus áreas autorizadas. No basta con enviar un ID desde el navegador. Las reglas del catálogo de creación se distinguen de las consultas históricas: el listado operativo no elimina el historial únicamente porque el documento deje de estar vigente.

### 3.2 Creación de la instancia

La ruta `abrirproced` en `controller/procedimiento.php`:

1. Comprueba los identificadores y que el procedimiento sea una opción autorizada de registro.
2. Crea el trámite del ciudadano, con los datos de empresa si corresponde.
3. Asigna o actualiza el código del trámite.
4. Asocia las tasas mediante `updateTasas`.
5. Registra la operación en bitácora y devuelve el identificador y código.

**Límite de evidencia:** el INSERT de la instancia no establece explícitamente su estado inicial en el código inspeccionado. Su valor depende del esquema o valor predeterminado de la base de datos; no se debe afirmar cuál es sin consultar esa definición.

### 3.3 Las tasas no son una copia completa e inmutable

Al abrir el trámite se copian referencias a las tasas del catálogo en `td_tasatciud`, con estado `1`. No se copia una versión fija del nombre ni del monto.

Al emitir, el sistema consulta el **monto actualmente configurado** en `td_tasaproced.tasaproced_monto` y guarda el resultado de `monto × cantidad` en el detalle de la orden.

**Consecuencia:** no está garantizado por esta copia de referencias que un trámite abierto conserve el precio que tenía el catálogo al momento de su registro. El monto persistido en el detalle de una orden ya emitida es un dato distinto del monto actual del catálogo.

## 4. Estados del listado operativo

El selector de la pantalla es un **filtro**, no una acción que cambie estados.

| Opción | Qué muestra actualmente |
|---|---|
| En curso | Pendientes calculados y órdenes Giradas. Es el filtro inicial. |
| Pendiente | Filas calculadas con trabajo pendiente de emisión; identificador de presentación `9`. |
| Girado | Órdenes con estado `1`. |
| Pagado | Órdenes con estado `4`. |
| Anulado | Órdenes con estado `0`; también trámites con ese estado sin una orden histórica asociada. |
| Usado | Órdenes con estado `5`; también trámites con ese estado sin una orden histórica asociada. |
| Extornado | Órdenes con estado `6`; también trámites con ese estado sin una orden histórica asociada. |
| Todos los estados | Historial completo dentro del procedimiento y acceso autorizado; incluye códigos desconocidos y registros Improcedentes. |

**Improcedente (`3`) ya no se ofrece como opción del filtro.** Sus registros y su interpretación interna no se eliminaron. En Todos los estados pueden conservar esa etiqueta.

Para filas sin orden, la consulta reconoce como estados de filtro del trámite `0`, `3`, `5` y `6`; no convierte automáticamente los estados internos `1` o `2` del trámite en estados de orden. Los códigos no reconocidos se muestran como **Desconocido**, en lugar de asignarles una etiqueta inventada.

### 4.1 Cuándo se calcula Pendiente

El trámite debe tener estado interno `1` o `2` y cumplir una de estas condiciones:

- Tener alguna tasa en estado `1` o `3` sin una asociación activa con una orden considerada válida para bloquear una nueva emisión.
- Ser un trámite con estado `1` que todavía no tenga tasas.

Una asociación activa con una orden cuyo estado no sea `0`, `3` o `6` bloquea que esa tasa se considere pendiente. Por tanto, anulación, improcedencia y extorno pueden dejar de bloquear la condición de pendiente, **pero no bastan por sí solos**: también deben cumplirse los estados requeridos del trámite y de la tasa.

### 4.2 Por qué un trámite aparece en varias filas

- **Emisión parcial:** algunas tasas tienen orden y otras siguen pendientes. Puede haber una fila Girado y otra Pendiente.
- **Varias órdenes:** cada combinación trámite/orden tiene una fila histórica.
- **Anulación:** puede conservarse una fila de la orden anulada y, si se cumplen las condiciones, aparecer otra fila Pendiente.

La vista no fuerza un único estado global por trámite.

### 4.3 Progreso y fechas

El progreso se calcula con las tasas: cuenta como pagadas las de estados `2`, `4` o `5`, y como total las que no están en `0`. **Esto incluye el estado `2`, que el detalle de tasas etiqueta Girado.** La barra no debe interpretarse sin matices como prueba de cobro confirmado.

Las fechas del filtro corresponden al **registro del trámite**, no a pago ni emisión de la orden. Sin fechas, se consulta todo el historial del estado elegido. Sin procedimiento seleccionado, no se consulta el listado.

## 5. Emisión individual y en grupo

### 5.1 Diferencia entre emitir y pagar

En este flujo, nombres internos como `Pagar_Orden_Giro_grupo` y mensajes que dicen Pagar pueden corresponder a **emisión**. La asociación a una orden cambia la tasa de Pendiente (`1`) a Girado (`2`); no acredita Pagado (`4`).

### 5.2 Emisión individual

La ruta ciudadana `Pagar_Orden_Giro` llama al método de emisión individual de `models/Tasa.php`. En esta ruta se ve directamente el SQL de creación:

1. Busca una tasa existente en estado Pendiente (`1`) y consulta su monto actual.
2. Actualiza esa tasa a `2` (Girado).
3. Genera un identificador de orden con secuencia y año, del tipo `000001-2026`.
4. Inserta la cabecera con estado `1` y usuario/comentario.
5. Inserta el detalle con cantidad, importe calculado y estado `1`.
6. Si no quedan tasas del trámite en `1` o `3`, actualiza el trámite a `2`.
7. Confirma la transacción y devuelve el ID de la orden.

A diferencia del grupo, la creación individual visible usa su propio identificador generado. No necesita buscar después la última orden global. Esto no convierte toda la gestión de secuencias en una garantía de concurrencia: el método también contiene lógica de mantenimiento de la secuencia, que no se probó con usuarios simultáneos.

### 5.3 Girar en grupo

La agrupación representa **una orden común con varios conceptos seleccionados del trámite**, no varios ciudadanos agrupados ni una orden distinta para cada concepto.

Ejemplo ilustrativo:

| Concepto | Monto unitario | Cantidad | Importe |
|---|---:|---:|---:|
| Costo de servicio | S/ 22 | 1 | S/ 22 |
| Tasa adicional | S/ 10 | 2 | S/ 20 |
| **Total ilustrativo** | | | **S/ 42** |

El navegador:

1. Recoge las casillas marcadas y sus cantidades; usa `1` si no hay cantidad indicada.
2. Rechaza una selección vacía.
3. Comprueba que las tasas seleccionadas estén en `1` y que el trámite no esté en `0` o `3`.
4. Solicita un comentario y confirmación.
5. Envía los IDs y cantidades al endpoint grupal.

El servidor:

1. Comprueba la presencia de los arrays serializados y que sus tamaños coincidan.
2. Llama una vez a `crearOrdenini`.
3. Obtiene un identificador mediante `obtenerultimovalor`.
4. Asocia cada tasa a esa misma orden mediante `insertarTasaGiros`, guardando cantidad e importe.
5. Devuelve éxito o error; no devuelve el ID de la orden en esa respuesta.

Tras el éxito, la interfaz recarga datos y ofrece imprimir. Si ya no quedan tasas del trámite en estados `1` o `3`, el método de asociación actualiza el trámite a `2`.

### 5.4 Costo inicial y emisión por etapas

Hay tratamiento especial por `proced_tipocampo`:

- **Tipo 2:** la primera tasa del listado (costo administrativo) tiene acción individual de emisión cuando está pendiente. Una vez Girada (`2`), Pagada (`4`) o Usada (`5`), se habilitan y preseleccionan juntas las demás tasas **Pendientes (`1`)**. Marcar o desmarcar cualquiera selecciona o deselecciona todo ese grupo; no se ofrece emisión individual para ellas. El costo administrativo y las tasas no pendientes quedan fuera de la selección grupal. Antes de girar el costo, o si está Anulado (`0`), Improcedente (`3`) o Extornado (`6`), el grupo permanece bloqueado.
- **Tipo 3:** las casillas también se deshabilitan y no se ofrece emisión individual en esa rama.
- **Otros tipos:** una tasa pendiente puede tener emisión individual y las casillas pueden permitir selección manual.

**No se exige pago confirmado del costo administrativo:** basta con que esté Girado (`2`). Por ejemplo, con costo Girado y TUC/Registro Pendientes, ambas tasas se seleccionan juntas y se emiten en una sola orden, conservando la cantidad de cada concepto. Incluso si solo queda una tasa pendiente, el botón debe reflejarla y permitir girarla.

Esta regla de selección se aplica en la interfaz ciudadana; el endpoint grupal no incorpora aquí una nueva validación de etapas ni de pertenencia del grupo. La rama Tipo 2 admite identificadores de tipo y estado numéricos o cadenas. Las demás ramas conservan sus comparaciones y condiciones existentes.

### 5.5 Botón visible con una sola tasa Girada

El código actual de `listarTasas` muestra el botón grupal únicamente si hay más de una fila de tasas. Cuenta todas las filas, no solo las pendientes. La función de totales considera las pendientes habilitadas para emisión grupal; actualiza el importe y deshabilita el botón cuando no hay selección, aunque solo quede una o ninguna pendiente.

Una captura con una sola tasa Girada y el botón visible no coincide con esa condición del código revisado. No se confirmó la causa: podría ser una versión desplegada distinta u otro comportamiento del DOM. **No demuestra que sea válido volver a girar esa tasa.**

## 6. Pago y extorno mediante SATCH

La integración `api/SATCH/get_data_satch.php` consulta información externa y aplica cambios a la orden:

- El camino de pago identificado lleva órdenes de `1` o `2` a **`4`**.
- Una respuesta externa cuyo estado, convertido a minúsculas, sea **`extornado`** lleva la orden a **`6`**, sin exigir un estado previo concreto en esa rama. Las otras respuestas externas exitosas se interpretan como pago, sujetas a la validación del estado previo de la orden.
- Propaga el estado a las tasas relacionadas. En la rama de pago, si el trámite consultado está anulado (`0`), omite esa actualización de tasas; por tanto, tampoco aquí la sincronización es incondicional.
- Actualiza el trámite cuando todas sus tasas tienen el mismo estado; la selección del trámite vinculado usa `LIMIT 1`.

El worker `api/buscar_automatizado.php` selecciona para consulta automática órdenes en **`1` o `2`**. Por tanto, **órdenes ya Pagadas o Usadas no se vuelven a consultar automáticamente por ese worker**, aunque el sistema externo pudiera informar después un extorno.

Esto no prueba que no exista otra llamada manual o integración que consulte esas órdenes. Solo delimita la cobertura de esa ruta automática.

## 7. Uso mediante SISGI y cambios mediante API genérica

La ruta SISGI inspeccionada exige **Pagado (`4`) → Usado (`5`)** y rechaza el trámite anulado (`0`). Es una transición concreta de esa integración, no una garantía compartida por todos los endpoints.

La ruta genérica `api/api.php` permite enviar un estado sin la misma validación de transición. Además, su propagación a tasas usa una comparación estricta contra códigos enteros: los valores POST recibidos normalmente como cadenas no cumplen esa comparación.

**Consecuencia:** actualizar la orden por la API genérica no garantiza que sus tasas y trámite queden sincronizados de la misma forma que en otras rutas.

## 8. Anulación, impresión y bitácora

### 8.1 Anulación del trámite u orden seleccionada

La acción visible **Anular** llama a `controller/procedimiento.php?op=eliminar_proced_ciudadano` con el ID del trámite y la orden seleccionada, si corresponde. El modelo ejecuta:

```sql
CALL sc_giros.eliminar_proced_ciudadano(?, ?);
```

No se encontró la definición de esa rutina en los archivos locales revisados. **No está probado aquí qué registros cambia, qué estados previos admite ni cómo trata un trámite con varias órdenes.** El nombre del método no demuestra que borre físicamente el historial.

La auditoría ya no muestra la columna **Procedencia** ni las acciones **Marcar improcedente** y **Habilitar trámite**. Los estados históricos siguen siendo consultables; este cambio no modifica datos. La ruta interna que llama a `sc_giros.cambiar_procedencia_proced_ciudadano(?)` permanece en el backend, también sin definición local disponible.

### 8.2 Cancelación de una tasa

`models/Tasa.php` contiene una llamada a `sc_giros.cancelar_pago_tasa(?)`, y el JavaScript conserva una solicitud con operación `CancelarPago`. Sin embargo, esa operación no tiene un handler identificado en el `controller/tasa.php` actual. Por tanto, **no debe describirse como un flujo completo y comprobado de la versión revisada**.

La anulación local y el extorno informado por SATCH no son la misma acción. Tampoco se puede afirmar que anular reabra automáticamente todas las tasas: hay que conocer la rutina SQL y comprobar luego las condiciones de Pendiente del listado.

### 8.3 Consulta e impresión

- **Ver órdenes de giros** consulta `controller/rc.php?op=listargiros` por ID del trámite. Presenta sus órdenes y acciones, incluidos comentario e impresión.
- La impresión por tasas usa `controller/rc.php?op=imprimir`, enviando uno o varios IDs de tasas.
- La impresión por cabecera usa `controller/rc.php?op=imprimirxid`, enviando `ogciud_id`.
- El controlador obtiene datos mediante el modelo `RC` y genera el documento PDF.

El flujo de emisión ofrece imprimir inmediatamente después del éxito. **Imprimir una orden o ticket no demuestra que se haya cobrado ni que se haya utilizado.** Además, algunas etiquetas del controlador de recibos interpretan los códigos `1`/`2` de forma distinta a la vista operativa; no se deben unificar sus significados solo por el nombre mostrado.

### 8.4 Bitácora

Los controladores llaman a `Bitacora::update_bitacora` o `update_bitacora_grupo` después de determinadas operaciones. Estos métodos asignan el usuario a registros pendientes de auditoría: buscan registros sin persona asignada y actualizan uno o un rango.

El código presupone registros creados por mecanismos de base de datos, pero no se inspeccionaron las definiciones de esos triggers. No está probado que cada cambio de todas las integraciones tenga auditoría completa, ni que la selección del último registro/rango sea una asociación inequívoca con la solicitud actual bajo concurrencia.

### 8.5 Qué significa llegar al final

Desde el punto de vista de la integración SISGI, **Usado (`5`)** representa el consumo de una orden previamente Pagada. No prueba por sí solo que un expediente externo haya terminado, que se haya entregado un documento o que se hayan completado todos los pasos administrativos del servicio.

Una orden puede terminar operativamente como usada, anulada o extornada según la ruta. No existe en el código revisado una sola transición final obligatoria compartida por todos los endpoints.

## 9. Riesgos y límites que afectan la interpretación

| Punto | Comportamiento observado | Consecuencia posible |
|---|---|---|
| Última orden global | La emisión grupal recupera la última orden por fecha, sin vincular la consulta a la solicitud que la creó | Asociación incorrecta bajo concurrencia |
| Transacción por tasa | En la emisión grupal, cada asociación puede confirmar de forma independiente | Grupo incompleto si falla una tasa posterior |
| Validación backend grupal | No reproduce de forma robusta estado pendiente, pertenencia, cantidades positivas e IDs únicos | La validación de la pantalla no es una barrera suficiente |
| Importe cero | El método interpreta un importe falsy como error | Conceptos gratuitos pueden fallar en esa ruta |
| Referencias de catálogo | No se guarda precio/nombre inmutable al abrir el trámite | El monto al emitir puede diferir del configurado al registrar |
| Barra de progreso | Incluye tasas Giradas entre las contadas como pagadas | Puede aparentar pago sin confirmación externa |
| Sincronización externa | El worker consulta solo órdenes `1`/`2` | No detecta automáticamente extornos posteriores de órdenes `4`/`5` |
| Rutas de actualización distintas | No comparten un validador único de transiciones | No hay una secuencia global garantizada |

Los riesgos son observaciones del código, no prueba de incidentes en producción. No se inspeccionaron triggers, restricciones ni definiciones completas de rutinas SQL en la base real.

## 10. Referencias y evidencia pendiente

- [Uso del listado operativo](giro-operational-list.md).

| Archivo / referencia aproximada | Evidencia |
|---|---|
| `controller/procedimiento.php`, operación `abrirproced` | Validación, apertura, código y asociación de tasas |
| `models/Procedimiento.php:87–153` | Alta y copia de referencias de tasas |
| `models/Procedimiento.php:217–244` | Llamadas de anulación y procedencia |
| `models/Procedimiento.php:393–490` | Acceso, progreso, filas históricas, Pendiente y filtros |
| `controller/procedimiento.php:530–543` | Etiquetas operativas y progreso |
| `controller/tasa.php:93–161` | Endpoint de emisión agrupada |
| `controller/tasa.php:428–560` | Estados de tasas, casillas y acciones por tipo |
| `models/Tasa.php:134–141` | Recuperación de la última orden global |
| `models/Tasa.php:188–285` | Emisión individual, montos y estados |
| `models/Tasa.php:313–322,370–454` | Rutina de cabecera y asociación grupal por tasa |
| `view/giros_ciudadano/usudetalleciudadano.js:735–797,1310–1460` | Listado de tasas y confirmación grupal |
| `controller/rc.php`, operaciones `imprimir`, `imprimirxid`, `listargiros` | Consulta e impresión |
| `models/Bitacora.php` | Asignación de usuario a auditoría pendiente |
| `api/SATCH/get_data_satch.php:48–64,115–134` y función `actualizarOrden` | Pago/extorno y propagación |
| `api/buscar_automatizado.php:222–226` | Selección de órdenes a sincronizar |
| `api/api_sisgi.php:228–318` | Ruta de consumo Pagado → Usado |
| `api/api.php:294–414` | Ruta genérica y comparación estricta de estados |

Los números de línea son orientativos y pueden desplazarse con futuras ediciones. Para confirmar las partes delegadas a la base se requieren, como mínimo:

- `sc_giros.crear_ordengiro_ciud`.
- `sc_giros.fn_pagar_tasa_trabajador` y `sc_giros.obtener_valor_recibo`, presentes como llamadas de otras rutas de pago; no se reconstruye aquí su lógica interna.
- `sc_giros.cancelar_pago_tasa`.
- `sc_giros.eliminar_proced_ciudadano`.
- `sc_giros.cambiar_procedencia_proced_ciudadano`.
- Defaults, restricciones y triggers de las tablas operacionales y de bitácora.

Para cerrar las incertidumbres se necesitan las definiciones reales de las rutinas llamadas, los valores predeterminados y restricciones de las tablas y pruebas de integración aisladas. No es suficiente que existan tests con dobles de SQL para demostrar la ejecución de PostgreSQL o el comportamiento externo.
