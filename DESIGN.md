# Sistema de Diseño Institucional: Municipalidad Provincial de Chiclayo (SIGODT)

> **Design Read:** Aplicacion administrativa municipal para operadores y administradores de la Municipalidad Provincial de Chiclayo, en un lenguaje institucional sobrio de alta legibilidad, dial `ENERGY 2 / RHYTHM 2 / MOTION 1`.

## 1. Identidad Visual y Direccion (Figma `8gbckPW2QsY90xStrG8zzF`)

El Sistema de Gestion de Ordenes de Derecho de Tramite (SIGODT) adopta el sistema de diseño institucional de la Municipalidad Provincial de Chiclayo (MPCH), estructurado en los nodos de Figma `49:661` (Inicio y Shell Institucional), `41:2` (Login dividido con fotografia de Chiclayo), `52:1126` / `64:3` (Variantes de acceso institucional) y `62:521` / `64:687` (Tarjetas con franja institucional de seguridad).

### Three Dials (antislop Part 3)
- **ENERGY: 2 (Equilibrado):** Encabezado azul institucional solido (`#0066CC`), panel hero oscuro (`#1F2937`) y botones de accion amarillos (`#FBC02D` / `#FED84D`) con texto oscuro (`#0F172A`) que establecen jerarquia inmediata sin adornos superfluos.
- **RHYTHM: 2 (Consistente con pausas deliberadas):** Alternancia estructurada entre el banner de bienvenida oscuro, grillas de modulos de 3 columnas y paneles de consulta o tablas de datos sobre el lienzo celeste (`#E7F1FB`).
- **MOTION: 1 (Estados de interaccion estaticos):** Transiciones suaves de color y borde (`150ms-200ms ease`) exclusivamente en `:hover` y `:focus-visible`, evitando animaciones en bucle para priorizar la velocidad operativa en ventanilla.

---

## 2. Tokens de Color (`public/css/municipal-theme.css`)

| Token CSS | Valor Hex | Uso Institucional | Contraste WCAG AA |
| :--- | :--- | :--- | :--- |
| `--muni-brand-blue` | `#0066CC` | Header principal, footer institucional, encabezados de tabla, bordes activos | `5.57:1` con `#FFFFFF` (PASS AA) |
| `--muni-login-blue` | `#066FD1` | Boton primario de ingreso en login, enlaces institucionales, segmento primario de franja | `4.95:1` con `#FFFFFF` (PASS AA) |
| `--muni-profile-bg` | `#0167CC` | Fondo de la pildora de perfil de usuario en el header | `5.51:1` con `#FFFFFF` (PASS AA) |
| `--muni-profile-border` | `#9CCAEC` | Borde de `2px` en la pildora de perfil y boton de recarga de CAPTCHA | `3.15:1` no-texto sobre `#0066CC` |
| `--muni-action-yellow` | `#FBC02D` | Botones CTA primarios en modulos e indicador de rol en header/hero | `11.53:1` con `#0F172A` (PASS AAA) |
| `--muni-security-yellow` | `#FED84D` | Segmento secundario de la franja institucional de 6px y acento en login | `13.48:1` con `#0F172A` (PASS AAA) |
| `--muni-hero-dark` | `#1F2937` | Superficie del panel de bienvenida en Inicio (`#49:665`) | `14.68:1` con `#FFFFFF` (PASS AAA) |
| `--muni-page-bg` | `#E7F1FB` | Fondo general de pagina en modo claro y circulos de icono de `64x64px` | `13.84:1` con `#0F172A` (PASS AAA) |
| `--muni-card-bg` | `#FFFFFF` | Superficie de tarjetas, paneles de filtro y contenedores de tablas | `17.85:1` con `#0F172A` (PASS AAA) |
| `--muni-icon-border` | `#BAE6FD` | Borde de `1px` en contenedores circulares de iconos | Delimitacion estructural suave |
| `--muni-text-primary` | `#0F172A` | Titulos principales, cabeceras de tarjeta, texto sobre botones amarillos | `17.85:1` sobre `#FFFFFF` |
| `--muni-text-body` | `#475569` | Descripciones de modulos, texto secundario y celdas de tabla | `7.58:1` sobre `#FFFFFF` (PASS AAA) |
| `--muni-text-muted` | `#64748B` | Etiquetas secundarias y subtitulos de apoyo | `4.76:1` sobre `#FFFFFF` (PASS AA) |
| `--muni-text-aux` | `#94A3B8` | Etiquetas auxiliares sobre fondo oscuro (`#1F2937`, ratio `5.48:1`) y divisores | `5.48:1` sobre `#1F2937` (PASS AA) |
| `--muni-surface-muted` | `#F1F5F9` | Caja de CAPTCHA, cabeceras de tabla sutiles, prefijos de input y divisores | Superficie neutra de agrupacion |
| `--muni-border-muted` | `#CBD5E1` | Bordes de inputs de formulario (`#CBD5E1`) y divisores de tarjeta (`#E2E8F0`) | Delimitacion de controles |

### Estados Semanticos (Pildoras con punto indicador de `6x6px`)
- **Activo / Vigente / Girado (`success`):** Fondo `#ECFDF5`, borde `#A7F3D0`, punto `#10B981`, texto `#047857` (contraste `5.12:1` PASS AA).
- **En Proceso / Pendiente / Bloqueado (`warning`):** Fondo `#FFFBEB`, borde `#FDE68A`, punto `#F59E0B`, texto `#B45309` (contraste `5.02:1` PASS AA).
- **Inactivo / Anulado / Improcedente (`danger`):** Fondo `#FEF2F2`, borde `#FECACA`, punto `#EF4444`, texto `#B91C1C` (contraste `5.65:1` PASS AA).

---

## 3. Tipografia (`Inter` Local)

Se utiliza la familia tipografica **Inter** servida localmente desde `public/lib/inter/inter.css` con caracteristicas OpenType `"cv03", "cv04", "cv11"` para distinguir caracteres alfanumericos en codigos de orden, DNI, RUC y CAPTCHA.

- **Titulo de Login (Panel Izquierdo):** `clamp(2rem, 3.2vw, 2.75rem)` (`600 SemiBold`), `line-height: 1.15`, color `#FFFFFF`.
- **Titulo de Bienvenida (Hero Inicio):** `clamp(1.5rem, 2.5vw, 1.875rem)` (`800 ExtraBold`), `line-height: 1.2`, color `#FFFFFF` con nombre de usuario en `#93C5FD`.
- **Titulo de Pagina (`.page-title`):** `1.5rem` (`24px`, `700 Bold`), `letter-spacing: -0.02em`, color `#0F172A`.
- **Titulo de Modulo / Tarjeta (`.card-title`):** `1.125rem` - `1.25rem` (`18px-20px`, `700 Bold`), color `#0F172A`.
- **Cuerpo y Formularios:** `0.875rem` (`14px`, `400 Regular` / `500 Medium`), color `#475569`.
- **Tablas Operativas:** `0.75rem` - `0.8125rem` (`12px-13px`), encabezados en `#0066CC` (`700 Bold`) y celdas con alineacion tabular en importes.

---

## 4. Reglas de Componentes

1. **Login Institucional Dividido (`index.php`, Nodo `41:2`):**
   - Panel izquierdo con fotografia de Chiclayo (`public/img/fondo_2.png`), superposicion de gradiente oscuro de tres paradas (`rgba(7, 29, 52, 0.05)` a `rgba(7, 29, 52, 0.94)`) para garantizar contraste de texto blanco en toda la superficie, titulo institucional, descripcion en `#EAF3FC` y barra de acento amarilla (`64x4px`, `#FED84D`).
   - Panel derecho sobre `#FFFFFF` con escudo MPCH (`88px`), controles de `50px` de altura con iconos integrados, fila de CAPTCHA con contenedor `#F1F5F9` y boton de recarga `#EFF7FD` (`50x50px`), y boton primario `#066FD1` de `52px` de altura.
2. **Header y Footer Institucionales (`mainProfile.php` Nodos `49:747` y `footer.php` Nodo `49:736`):**
   - Header en `#0066CC` con contenedor de logo blanco de `44x44px` (`border-radius: 12px`) y pildora de perfil (`#0167CC`, borde `2px solid #9CCAEC`, `border-radius: 9999px`) mostrando nombre en blanco y cargo en `#FBC02D`.
   - Barra de navegacion secundaria sobre `#FFFFFF` con estado activo en `#0066CC` (texto e icono) sin barra inferior.
   - Footer en `#0066CC` con borde superior `rgba(96, 165, 250, 0.4)`, escudo municipal a la izquierda y creditos de version a la derecha.
3. **Tarjetas de Modulos y Mantenimientos:**
   - Radio de borde consistente de `8px`, sombra de elevacion sutil `0px 1px 2px 0px rgba(0, 0, 0, 0.05)` y borde `1px solid #E2E8F0`.
   - En modales y tarjetas de seguridad (`view/setting/index.php`), franja superior bicolor de `6px` (`#066ED0` + `#FED84D` en los ultimos `110px`).

---

## 5. Registro de Decisiones de Diseño en Una Linea (Regla R-31)

- **Por que este color (`#0066CC` azul institucional y `#FBC02D` amarillo accion):** Representan la identidad civica oficial de la Municipalidad Provincial de Chiclayo y aseguran contraste WCAG AA/AAA con texto blanco sobre azul y texto `#0F172A` sobre amarillo.
- **Por que este layout (Login dividido 55/45 e interna con barra superior y lienzo `#E7F1FB`):** El login dividido contextualiza la plataforma municipal sin distraer del formulario, mientras que la navegacion horizontal maximiza el ancho util para tablas anchas de ordenes de giro y tributos.
- **Por que esta tipografia (`Inter` local con `cv03`, `cv04`, `cv11`):** Desambigua digitos y letras similares en codigos de expediente, DNI, RUC y CAPTCHA sin depender de CDNs externos.
- **Por que este espaciado (Escala base de `8px`, rellenos de `24px-40px` en hero y `44px-52px` en controles):** Garantiza objetivos tactiles minimos de `44x44px` (R-03) y separa visualmente los bloques de filtros de los resultados tabulares.
- **Por que usar tarjetas (Contenedores `#FFFFFF` sobre `#E7F1FB`):** Agrupan logicamente cada tarea operativa (busqueda, resumen metrico, tabla de resultados) sobre el fondo celeste institucional para reducir la fatiga visual en jornadas de ventanilla.
- **Por que usar estos iconos y pildoras de estado (SVG de trazo funcional + pildoras con punto de `6px`):** Los iconos refuerzan la accion del control (buscar, imprimir, recargar CAPTCHA) y los puntos de estado comunican vigencia real del tramite o catalogo TUPA sin animaciones distractivas.
