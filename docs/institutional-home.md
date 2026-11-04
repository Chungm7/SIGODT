# Inicio alineado con SIINDI

Inicio carga `public/css/institutional-home.css` después del head compartido, únicamente en `view/inicio/index.php`. No modifica el shell ni `municipal-theme.css`.

## Medidas tomadas del código de referencia

Referencia de solo lectura: `sisIndicadores/view/inicio/index.php` y `sisIndicadores/view/html/mainHead.php` (contenedor, hero, tarjetas y responsive).

| Elemento | SIINDI → SIGODT |
| --- | --- |
| Contenedor | Máximo 1347px; padding 20px 32px 52px. Se eliminan márgenes de `.page-body` y el padding extra de Bootstrap. |
| Banner | Padding 40px; gap 40px; margen inferior 40px. Ya coinciden fondo #1F2937, borde, radio 8px y sombra en el tema existente. |
| Texto | Título 30/36px, peso 800, tracking -0.025em; subtítulo 16/24px. Se mantienen colores institucionales existentes. |
| Año | Divisor #94A3B8, padding izquierdo 32px; número 30/36px. |
| Tarjetas | Gap 36px; padding interno 20px y gap 16px; identidad gap 12px; icono 50px/radio 14px existente con SVG 25px; título 19/24px. |
| Hasta 1024px | Banner padding 32px y grid gap 24px. |
| Hasta 768px | Contenedor 24px 16px 36px; banner vertical, padding 26px 20px, gap 20px; título 24/30px; año con divisor superior y padding 16px; grid de una columna, gap 20px. |

Adaptaciones deliberadas: dos columnas para las dos acciones SIGODT, no tres subsistemas; títulos completos con salto de línea, no elipsis; sin altura mínima 280px ni métricas artificiales. No se agrega ilustración: el hero de referencia contiene texto y año, sin imagen. No hay encabezado de módulos visible en la referencia ni se añade uno.

## Límites preservados

Sesión, nombre/rol escapados, año dinámico, guard `Conectar::esAdministrador()`, enlaces y acciones originales se conservan. Header, navbar, footer y otras vistas no se editan. Las reglas existentes de modo oscuro siguen vigentes; los overrides nuevos son de geometría.

## Verificación observada

- `node --test tests/institutional-home.test.cjs`: RED inicial, exit 1, cuatro contratos fallan por ausencia de hoja/markup.
- `node --test tests/inicio-hero.test.cjs tests/institutional-home.test.cjs`: exit 0, 7/7.
- `php tests/inicio_modules_test.php`: exit 0; simulación de visibilidad administrador/no administrador, no sesión real.
- `php -l view/inicio/index.php`: exit 0.
- `node --test tests/*.test.cjs`: exit 1, 160/166; los seis fallos coinciden con el baseline comunicado: cuatro en `procedure-area-selectors.test.cjs`, dos en `tributos-area-options.test.cjs`. No se modifican.
- `git diff --check`: exit 0.

Los contratos inspeccionan fuente CSS/markup; no prueban geometría computada en navegador. Pendiente revisión visual autenticada en escritorio, tablet y móvil (incluidos nombres largos y ambos roles). No se reintenta Browser MCP ni se instalan librerías, según la limitación indicada por el orquestador.
