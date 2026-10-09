# Consulta por Nombre / Razón Social

En **Consultas → Consulta por Nombre / Razón Social**, ingrese parte de un nombre de ciudadano, razón social o nombre comercial. La búsqueda no distingue mayúsculas y trata `%`, `_` y `!` como texto literal.

1. Revise las entidades y el **total de órdenes** antes de abrir un historial.
2. Compare documento e identidad: dos personas o empresas con el mismo nombre no necesariamente son la misma entidad.
3. Seleccione **Ver historial**. Se muestran órdenes de todas las fechas y estados, diez por página, de más reciente a más antigua (ID descendente para fechas iguales).
4. Use **Ver / Imprimir** para enviar el ID mediante POST al servicio existente, en una pestaña nueva. Los IDs número-año (por ejemplo, `000123-2026`) se envían sin alterar sus ceros iniciales.
5. **Volver a resultados** conserva la búsqueda, sus filas y su página. Una nueva búsqueda reemplaza los resultados anteriores.

## Identidades y totales

- Ciudadanos: `ciud_id`, no el personal emisor de la orden (`pers_id`).
- Empresas: RUC actual no vacío, recortado en los extremos; en su ausencia, RUC histórico del trámite. Se conserva como texto, incluyendo ceros iniciales.
- Sin RUC: `id:<empr_id>`. Sin ID ni RUC pero con razón social histórica: `sin:<procedciudadano_id>`, presentado como **Sin identificar**. Un trámite sin ID ni identidad empresarial histórica no crea una empresa.
- Varios IDs empresariales con el mismo RUC convergen. Las razones sociales/nombres comerciales actuales y las razones sociales históricas son alias buscables, no claves de agrupación. La etiqueta es el mínimo nombre no vacío de los alias (ordenación de PostgreSQL), una selección determinista, no una garantía de nombre vigente.
- Los alias coincidentes seleccionan claves primero. Después se cuentan **todos** los pares distintos entidad/orden de esas claves, no sólo las filas cuyo nombre coincide. Una orden puede figurar en más de una entidad; no se deben sumar los resultados como total global de órdenes.
- El historial usa los mismos pares: una fila por orden. El importe es el total de las filas reales de giro-tasa de la orden, deduplicadas por `girot_id`, antes de unir las identidades. Incluye todas sus tasas, no sólo las vinculadas al alias buscado.
- Estados: Anulado, Pendiente, Girado, Improcedente, Pagado, Usado, Extornado (0–6). Cualquier otro valor muestra **Desconocido** con su código.

## Contrato técnico

Dos operaciones POST en `controller/ordengiro.php`:

| Operación | Parámetros |
| --- | --- |
| `buscar_entidades_nombre` | `search` (texto no vacío, máximo 200 bytes UTF-8), `page`, `limit` |
| `historial_entidad` | `entity_type` (`ciudadano` / `empresa`), `entity_key`, `page`, `limit` |

`page` inicia en 1 (máximo solicitado 1.000.000.000); `limit` entre 1 y 100, por defecto 10. Las respuestas incluyen `data`, `total`, `page`, `limit`; página solicitada fuera del total se ajusta a la última, o 1 si está vacío. Un único statement suministra filas, total y página en el mismo snapshot. Dos peticiones separadas pueden observar cambios concurrentes en los datos.

Sólo estas operaciones nuevas añaden el control explícito de sesión: HTTP 401 JSON sin sesión, 400 para criterio/clave/paginación inválidos antes de cargar la conexión, 500 con mensaje genérico para errores. Consultas parametrizadas; nombre buscado no se interpola en SQL. Las filas del navegador se construyen mediante `textContent`; respuestas antiguas y errores antiguos no sustituyen la vista actual.

La impresión conserva el contrato POST con un único campo `ogciud_id`. La generación en `models/Tasa.php` rellena la secuencia positiva a un mínimo de seis dígitos (no recorta secuencias más largas) y añade `-` más el año de cuatro dígitos. Se conserva también la compatibilidad numérica positiva ya cubierta por la suite existente; esto no confirma la existencia de registros históricos de ese formato. IDs malformados, nulos, objetos o marcado HTML se rechazan antes de construir o enviar el formulario, sin normalizar el texto recibido.

Los handlers anteriores, el shim `controller/orden_giro.php`, consultas mensuales, configuración y esquema no se modifican.

## Verificación y límites

```sh
php tests/consultar_nombre_test.php
node --test tests/consultarnombre.test.cjs
```

La prueba PHP usa doubles PDO/statement y procesos aislados con sesiones en memoria para comprobar binding literal/tipado, validación, rechazo real del controlador, metadata, filas sintéticas y construcción del SQL. **No ejecuta PostgreSQL**: los checks de estructura y los resultados de doubles no prueban semántica de JOIN, agrupación de RUC/alias, deduplicación de tasas o concordancia real conteo/historial. La suite Node ejecuta renderizado, estados, impresión, paginación y navegación con DOM/fetch sintéticos, incluyendo respuestas tardías; no sustituye una revisión visual en navegador.

Pendiente en un entorno autorizado: ejecutar el SQL con fixtures PostgreSQL aisladas y validar nombres/esquema/tipos reales. No se accedió a credenciales ni a una base de datos viva. No hay runner visual de navegador disponible en las herramientas de esta tarea; Tabler, includes compartidos y sesión desplegada requieren aceptación manual.

### Aceptación manual breve

1. Sin sesión, comprobar 401 JSON en ambas operaciones. Con sesión, buscar blanco, `%`, `_`, un nombre con tildes y un texto con marcado HTML; el marcado debe verse como texto.
2. En fixtures controladas, crear dos ciudadanos homónimos, empresas homónimas sin RUC con IDs diferentes, y una empresa histórica sin ID/RUC. Verificar separación por clave y etiqueta explícita para la última.
3. Asociar dos IDs empresariales al mismo RUC con ceros iniciales y nombres diferentes, incluyendo alias histórico y fallback histórico de RUC. Buscar cada alias y comprobar que la misma clave muestra todo su historial, no sólo coincidencias.
4. Añadir varias tasas a una orden, importes iguales en tasas distintas y múltiples asociaciones de la misma entidad. Confirmar una sola fila/conteo por orden e importe total que incluye cada `girot_id` una sola vez. Comparar conteo con todos los IDs únicos de páginas del historial.
5. Incluir estados 0–6 y otro código, tasas/trámites inactivos, fechas antiguas y fechas iguales. Confirmar inclusión completa, etiquetas y orden estable. Solicitar página fuera del total y conjunto vacío: metadata y filas deben coincidir.
6. Probar más de diez entidades y órdenes, navegación anterior/siguiente, buscar de nuevo mientras carga, y volver durante una petición tardía. Conservar resultados/página y no aceptar respuestas antiguas. Revisar móvil y escritorio, foco/teclado, estados vacío/cargando/error.
7. Imprimir una orden activa y una inactiva. El POST debe contener sólo `ogciud_id` y abrir otra pestaña. **Limitación existente:** el endpoint de impresión llama a `RC::get_datos_giro_id`, que consulta detalles sólo para estados 1–5. Las órdenes en estados 0 (Anulado), 6 (Extornado) u otros códigos se incluyen en el historial, pero ese backend no devuelve sus detalles de impresión. Esta función no modifica ese backend.

## Alcance de revisión

Una unidad coherente en ocho archivos: menú, modelo, controlador, vista/JS, dos suites y esta guía. Supera la heurística orientativa de 400 líneas para mantener legibles las consultas, los estados UI y las pruebas; no se minifica ni se omiten pruebas para reducir el diff. Sin commits ni cambios de dependencias/esquema/configuración.
