# Correlativo anual atómico de órdenes ciudadanas

**La migración y el código se despliegan juntos, con la emisión pausada. Esta entrega no autoriza ni ejecuta cambios en una base real.** Las rutas ciudadanas individual y grupal usan un contador transaccional por año; una orden con varios conceptos consume un solo número. El historial no se renumera.

## Contrato y revisión rápida

| Regla | Implementación |
|---|---|
| Año independiente | `giro_year_counter(year PRIMARY KEY, last_value bigint)` |
| Una autoridad | `allocate_giro_number(date)` usa `INSERT … ON CONFLICT … UPDATE … RETURNING` |
| Fecha de producción | `crear_ordengiro_ciud_id(integer,text)` usa `CURRENT_DATE` de PostgreSQL en ambas rutas; el navegador no puede cambiarla |
| Formato | `000001-2026`, `000001-2027`; `1000000-2026` no se trunca |
| Continuación | Solo la migración calcula máximos numéricos por año: `027049-2026` continúa en `027050-2026` |
| Rerun | Conserva el mayor contador existente/histórico; nunca modifica IDs o detalles |
| Propiedad | El `INSERT RETURNING` devuelve la cabecera de la propia conexión, nunca la última orden global |
| Atomicidad | Un PDO confirma cabecera, todos los detalles, estados y atribución de auditoría; cualquier excepción revierte todo, incluido el contador |
| Compatibilidad SQL | Permanece `CALL sc_giros.crear_ordengiro_ciud(integer,text)` como wrapper invoker; no devuelve ID a consumidores antiguos |
| Sin migración | Error controlado HTTP 500; **no hay fallback** a secuencia ni MAX+1 |

El contador serializa las emisiones del mismo año hasta el commit. El primer número de otro año es 1 y no altera años anteriores. Una transacción abortada puede reutilizar su número, pues nunca llegó a persistirse una orden confirmada. El agotamiento de `bigint` produce error y rollback, no reinicio.

## Preflight manual obligatorio antes del despliegue

El operador debe revisar y registrar estos puntos **sin ejecutar automáticamente la migración**:

1. Pausar todos los emisores y drenar transacciones: web, workers, integraciones y otras aplicaciones. Inventariar rutinas, defaults, triggers y dependencias de `sc_giros.ordengiro_id_sequence`, `td_ordengirociud` y `crear_ordengiro_ciud`. Comparar la definición real del procedimiento, firma, nombres de argumentos, columnas, grants, propietario y efectos adicionales con el wrapper propuesto. No está demostrado que el repositorio contenga todos los escritores.
2. Confirmar PostgreSQL 14+, esquemas confiables y propietario de migración. El rol web no debe poder crear objetos en esos esquemas. Revisar RLS, triggers de cabecera/detalle/estados y bitácora, así como cualquier trigger que vuelva a calcular o sobrescriba el ID.
3. Revisar IDs históricos (prefijo positivo, guion, cuatro dígitos de año, año 1–9999, prefijo dentro de bigint con espacio para incrementar). Variantes con ceros iniciales se conservan: el seed usa `split_part(...,1)::bigint`, no los primeros seis caracteres ni orden textual. Los inválidos bloquean el preflight; no se corrigen ni renumeran automáticamente. Confirmar una clave única válida exclusivamente sobre `ogciud_id`.
4. Confirmar que cabecera `ogciud_id` y detalle `girot_giro` son `text` o `varchar` **sin límite** y que sus FK, índices y otros consumidores admiten más de seis dígitos. La migración falla si detecta columnas limitadas: cualquier ampliación requiere revisión aparte, no truncamiento.
5. Verificar permisos del rol real y auditoría. El código atribuye únicamente filas huérfanas de `sc_seguridad.tb_bitacora` cuyo `xmin` corresponde a la transacción propia, no un rango global de IDs. Validar esto con los triggers reales: subtransacciones internas u otros mecanismos de auditoría pueden necesitar adaptación revisada. No se inventó un trigger productivo ni se garantizó auditoría de escritores desconocidos.

### Permisos y aplicación coordinada

- El migrador requiere permisos para bloquear la cabecera, crear/actualizar el contador y reemplazar las rutinas. Un procedimiento existente con otra firma, propietario o dependencias incompatibles exige resolver el preflight antes, no eliminarlo a ciegas.
- La migración es una transacción con `lock_timeout=5s`, `statement_timeout=30s` y locks exclusivos de cabecera/contador. Un fallo revierte todo. No puede sustituir la pausa coordinada: un escritor antiguo que espere el lock podría emitir inseguramente después.
- Todas las rutinas son `SECURITY INVOKER`, con `search_path=pg_catalog` y tablas/rutinas calificadas. No hay SQL dinámico ni nuevas superficies security-definer. Se revoca PUBLIC sobre contador y rutinas afectadas; revisar también grants explícitos heredados.
- Antes de reabrir, el operador debe otorgar al rol de emisión autorizado `USAGE` en `sc_giros`, `SELECT/INSERT/UPDATE` en `giro_year_counter` y `EXECUTE` en las funciones y wrapper que utilice. Conserva los permisos existentes necesarios en cabeceras, detalles, tasas y trámites. La atribución requiere `SELECT/UPDATE` de bitácora, incluido `xmin`. No se codifica un nombre de rol desconocido ni se concede acceso a PUBLIC.
- Aplicar `database/migrations/20261016_atomic_giro_numbering.sql`, desplegar PHP/JS coordinados, comprobar grants y realizar una prueba autorizada antes de reabrir. Verificar que dos rutas continúan el mismo contador y que no queda ningún escritor antiguo usando la secuencia. **Hasta ese inventario y prueba real, no se afirma una corrección completa del servidor.**

### Rollback: límite importante

Antes de nuevas emisiones, un fallo de la propia transacción de migración no deja cambios. Tras instalar, mantener la emisión pausada mientras se decide recuperación. **Después de asignar números nuevos no se debe restaurar el asignador antiguo, reiniciar la secuencia ni bajar contadores.** Una reversión de aplicación que restaure el viejo PHP tampoco es segura. Conservar contador/rutinas e historial y preparar una corrección hacia adelante compatible. Un rerun no es un rollback: solo vuelve a elevar seeds cuando corresponde.

## PHP, errores e interfaz

`emitirGrupo` valida IDs/cantidades enteros positivos, selección no vacía, tamaños iguales y sin IDs repetidos antes de abrir la transacción. Reutiliza la consulta de tarifa, bloqueo de tasa pendiente, cálculo monto × cantidad, transición 1→2 y comprobación de pendientes 1/3 del camino individual. No añade reglas de elegibilidad por etapas o pertenencia. `insertarTasaGiros` conserva una entrada independiente reutilizando ese detalle y su contrato público legado: `true` al confirmar o un string de error ante validación/tasa inexistente/no pendiente/fallo de detalle, con rollback cuando corresponde. `emitirGrupo` no consume esos strings: usa el detalle interno que lanza excepciones y revierte el grupo completo; el controlador grupal ya no ejecuta el helper en conexiones separadas.

Las respuestas ciudadanas conservan mensajes/impresión exitosos y expiración de sesión. Entrada malformada devuelve 400; errores internos devuelven 500 con JSON genérico sin SQL, comentario ni datos personales. Esto incluye el fallo de conexión: únicamente la emisión ciudadana atómica llama `Conectar::conexion(true)` para propagar la excepción al controlador. El parámetro opcional es `false` por defecto; los demás consumidores conservan la conexión exitosa y el comportamiento legado de imprimir diagnóstico y terminar ante error. No se cambiaron DSN, entorno, credenciales ni ajustes de conexión. El frontend pide JSON explícito, tiene timeout de 30s y libera modal/spinner/confirmación tanto en error HTTP como transporte o parseo. Descarta el handler de confirmación anterior y recarga la selección antes de permitir otra emisión. **No reintenta automáticamente**: un timeout no demuestra que el servidor haya abortado.

La selección de costo administrativo y tasas posteriores no se rediseña. Las rutas trabajador/empresa conservan su comportamiento: llaman `fn_pagar_tasa_trabajador`, `obtener_valor_recibo`, `pagar_tasa_empresa`, `crear_ordengiro_empresa` y `crear_giro_tasa_empresa`, cuyas dependencias SQL internas no están inventariadas aquí. No se eliminó ni reseteó `ordengiro_id_sequence`. El JS administrativo usa los nombres distintos `Pagar_Order_Giro` y `Pagar_Ordern_Giro_grupo`, no los endpoints ciudadanos modificados; corregir esos nombres sería otro cambio de comportamiento y queda fuera de alcance.

## Evidencia reproducible y límites

```sh
php tests/giro_numbering_test.php
node --test tests/giro-numbering.test.cjs
node --test tests/giro-staged-selection.test.cjs
bash tests/giro_numbering_postgres_test.sh true # RED esperado: duplicate key 000001-2026
bash tests/giro_numbering_postgres_test.sh      # GREEN aislado
```

El runner usa exclusivamente una imagen `postgres:17` ya almacenada, contenedor `--rm --network none`, sin puertos ni datos reales. Solo monta un directorio nuevo de sockets sintéticos `/tmp/sigodt-giro-synthetic.*`; no lee configuración, credenciales ni bases existentes. Detiene su propio contenedor al salir. El directorio de sockets vacío puede quedar para limpieza del operador. No instala ni descarga nada. Sin imagen/daemon devuelve skip explícito (77); sin PDO pgsql o si la base no inicia, falla sin inventar evidencia.

- **PostgreSQL real:** reproducción de reset/duplicado legado; años arbitrarios/rollover; seed 27049→27050; prefijo de siete dígitos; rerun; wrapper CALL; modelo PDO real con cantidades/estados, rollback tardío de grupo y auditoría, entrada rechazada sin efectos y rutina ausente sin fallback. Doce procesos PHP/PDO separados mezclan seis emisiones individuales y seis grupales; se verifican IDs únicos, 18 detalles y propiedad por usuario sintético.
- **Dobles PHP:** rollback de detalle/auditoría, guard pendiente, JSON/HTTP/sesión y ausencia de SQL/PII en respuesta; regresiones de rutas hermanas y contrato `true`/error-string del helper independiente. El probe del conector extrae únicamente el método real como fuente, sustituye el constructor PDO por uno sintético y usa `getEnv` stub: nunca ejecuta el cargador de configuración ni abre una conexión. Comprueba éxito/error por defecto, propagación opt-in y HTTP500 genérico atravesando el modelo/controlador reales en ambos endpoints.
- **Harness Node:** funciones públicas de emisión individual/grupal, 500/timeout/parseo, éxito/impresión y selección de tasas existente. No equivale a una sesión de navegador ni reproduce Bootstrap real.
- **Diferencial de esta entrega:** anterior HEAD y candidato ejecutados en memoria con el mismo fixture público de controlador: trabajador, empresa, mostrar, insertar/eliminar tasa y los dos nombres administrativos sin handler; exit/stdout/stderr idénticos. Los tests guardados no consultan historia Git.

No hay prueba de producción, ni autorización para ejecutarla. Revisión independiente y decisión de rollout pertenecen al coordinador.
