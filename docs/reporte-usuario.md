# Consulte sus órdenes emitidas

En **Mis emisiones**, cada trabajador consulta exclusivamente las órdenes que emitió con su sesión actual. No existe selector de trabajador. El reporte es de solo lectura y no modifica órdenes ni catálogos.

## Consulta rápida

1. Abra **Mis emisiones** en el menú. El periodo inicial es el mes actual.
2. Ajuste **Desde** y **Hasta**; ambos días están incluidos según la fecha de emisión, no la fecha de pago.
3. Opcionalmente seleccione un documento y un procedimiento. Pulse **Consultar** después de cada cambio.
4. Revise las cuatro métricas del resumen, el desglose y el detalle paginado. Los botones **Anterior** y **Siguiente**, al pie del detalle, no cambian los totales. En pantallas estrechas, desplace las tablas horizontalmente para ver todas las columnas.
5. Use **Descargar PDF**, **Exportar CSV para Excel** o **Imprimir / PDF** para obtener todos los resultados del filtro aplicado, no solo la página visible.

## Cómo interpretar los resultados

| Dato | Significado |
|---|---|
| Total de órdenes emitidas | Cada número de orden coincidente se cuenta una sola vez, aunque tenga varias tasas, ciudadanos o procedimientos. |
| Órdenes anuladas y extornadas | Órdenes coincidentes cuyo estado actual es Anulado (0) o Extornado (6). También están incluidas en el total emitido. |
| Conceptos coincidentes | Cantidad de líneas de detalle, no cantidad de unidades ni de órdenes. |
| Importe emitido | Suma del importe total persistido de cada concepto coincidente. Ya incluye su cantidad; no se multiplica nuevamente ni se usa la tarifa actual. **No equivale a dinero recaudado.** |
| Desglose | Agrupa conceptos por documento y procedimiento. Sus conteos de órdenes **no son aditivos**: una misma orden puede aparecer en varios grupos. |
| Estado actual | 1 significa Girado; 0 Anulado; 6 Extornado. Otros códigos se conservan como «Estado N», sin excluirlos. |

**Los filtros restringen conceptos antes de sumar.** Si una orden tiene S/ 20 en un procedimiento y S/ 30 en otro, filtrar el primero muestra una orden y S/ 20, no S/ 50. La misma regla se aplica al resumen, desglose, detalle y exportaciones. Una combinación documento/procedimiento sin conceptos coincidentes devuelve cero resultados.

El detalle conserva todos los nombres distintos de ciudadanos/razones sociales, documentos, códigos y procedimientos coincidentes, separados por «/», sin repetir el importe por cada nombre. Se ordena por fecha de emisión descendente y número de orden descendente.

## Histórico y filtros

- Los documentos y procedimientos disponibles provienen de sus propias órdenes del periodo, incluidos los catálogos inactivos; los procedimientos se restringen al documento seleccionado.
- No se excluyen órdenes ni conceptos por su estado actual.
- Una cabecera histórica sin detalle aparece sin filtros opcionales con importe S/ 0.00 y etiquetas «Sin…». No coincide con un documento o procedimiento concreto.
- Los nombres corresponden a los datos disponibles actualmente y a la razón social histórica de respaldo; no constituyen una instantánea certificada del catálogo al emitir.
- Cambiar un filtro oculta el resultado anterior hasta volver a consultar. Una selección sin coincidencias se muestra explícitamente, no se sustituye silenciosamente por «Todos».

## Exportaciones

**CSV para Excel** es un archivo de texto UTF-8 con separador punto y coma, no un archivo XLSX. Si Excel no separa columnas automáticamente, importe el archivo como UTF-8 y seleccione «punto y coma». Incluye emisor, periodo, filtros por ID, fecha de generación, resumen, detalle completo y desglose. Los textos que podrían ejecutarse como fórmulas se prefijan con apóstrofo.

**Descargar PDF** genera directamente un archivo A4 horizontal con texto seleccionable, las ocho columnas completas, resumen y desglose. Incluye emisor, periodo, filtros por ID y fecha de generación; repite encabezados y numera las páginas. Los nombres y códigos largos se ajustan sin recortarse; las filas excepcionalmente largas pueden continuar en otra página. Un reporte vacío también produce un PDF legible. El nombre del archivo usa solo las fechas (`emisiones-AAAA-MM-DD-AAAA-MM-DD.pdf`), nunca nombres personales.

El botón se desactiva mientras genera el archivo. Cambiar filtros o iniciar otra consulta invalida la descarga pendiente. Si falla la carga o generación, el mensaje explica el error; reintente manualmente o use la impresión alternativa. No hay reintentos automáticos.

**Imprimir / PDF** abre una página HTML con todos los resultados filtrados. Pulse **Imprimir / Guardar como PDF** y seleccione la impresora o el destino PDF del navegador. El documento incluye encabezado SIGODT, contexto del filtro, resumen y tablas completas; está preparado para A4 horizontal con encabezados de tabla repetidos. La barra de impresión no aparece en papel. Los nombres largos se ajustan en varias líneas, sin recortarse. Los textos se presentan escapados, sin ejecutar contenido de nombres o catálogos.

Cada exportación vuelve a consultar la misma sesión y los mismos filtros. Si los datos cambian entre consulta y exportación, los resultados reflejan ese nuevo momento; no son un cierre contable.

## Si no puede consultar

- **Sesión no válida:** vuelva a iniciar sesión.
- **Fechas inválidas:** indique fechas reales y un inicio no posterior al fin.
- **Sin resultados:** amplíe el periodo o retire los filtros opcionales.
- **No se pudo generar el reporte:** intente nuevamente; si persiste, comuníquelo al administrador.

## Verificación técnica

Las pruebas PHP con conexión simulada ejercitan el controlador público con una conexión simulada y comprueban propiedad de sesión, parámetros SQL, agregados, filtros, paginación y exportaciones. No ejecutan PostgreSQL ni sustituyen una comprobación con navegador y datos reales. El reporte usa una lectura de los conceptos del periodo autorizado para construir opciones y agregados consistentes; rangos grandes requieren memoria proporcional al detalle recuperado, también en el navegador al exportar PDF.

### Dependencias PDF locales

Solo esta vista carga jsPDF UMD **4.2.1**, seguido de AutoTable **5.0.9** y el generador del reporte. AutoTable declara compatibilidad `^2 || ^3 || ^4` con jsPDF. Los archivos son distribuciones oficiales npm intactas (MIT); el TTF PT Sans proviene de un commit fijo de Google Fonts (OFL-1.1). La fuente se solicita al mismo origen y se incrusta en el PDF para preservar ñ y tildes. No se usan CDN, HTML capturado, imágenes ni recursos remotos en esta función. No se migraron otras dependencias existentes.

Consulte `public/lib/report-pdf-fonts/provenance.json` para versiones, URL de origen, SHA256 de cada distribución/licencia/fuente e integridad npm SHA512 verificada al descargar. Las licencias se conservan junto a los archivos; `README.md` en ese directorio explica la actualización manual.

`node --test tests/reporteusuario*.test.cjs tests/reportepdf.test.cjs` ejecuta las distribuciones reales en un entorno VM de navegador y comprueba los bytes PDF. Cuando está disponible, `pdftotext` valida texto Unicode, múltiples páginas, encabezados y numeración. La comprobación visual del navegador y la base PostgreSQL real requieren un entorno configurado; estas pruebas no las sustituyen.
