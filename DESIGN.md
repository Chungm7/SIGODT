# Sistema de Diseño Institucional: Municipalidad Provincial de Chiclayo (MPCH)

> **Guía Maestra y Plantilla de Especificación de Interfaces**: Documento normativo, arquitectura de componentes, tokens visuales y directrices para el desarrollo o estandarización de aplicaciones administrativas en la Municipalidad Provincial de Chiclayo.

---

## 1. Identidad Visual y Filosofía de Diseño (Figma `8gbckPW2QsY90xStrG8zzF`)

El sistema de diseño unifica todas las aplicaciones municipales bajo una misma identidad cívica y operativa. Diseñado para optimizar la velocidad y reducir la fatiga en tareas de ventanilla y fiscalización, se estructura a partir de los nodos oficiales del archivo Figma **Rediseño Interfaces Municipalidad**:

- **Nodo `49:661`**: Shell Institucional y Layout General (Header, Subnav y Lienzo).
- **Nodo `41:2`**: Login Institucional Dividido (Fotografía cívica, CAPTCHA y credenciales).
- **Nodo `49:665`**: Panel Hero de Bienvenida y Año de Gestión.
- **Nodo `156:27`**: Tarjeta Institucional de Módulo Operativo (Icono redondeado, badge de estado y botón CTA).
- **Nodo `103:241`**: Módulos de Configuración, Perfil y Seguridad (2FA).
- **Nodos `62:521` / `64:687`**: Tarjetas de filtro y consulta con franja institucional de seguridad.

### Three Dials (Calibración Visual)
- **ENERGY: 2 (Equilibrado):** Dominado por azul institucional (`#0066CC`), superficies limpias (`#FFFFFF` sobre `#E7F1FB`) y acentos de acción amarillos (`#FBC02D` / `#FED84D`) con contraste estricto sobre tipografía oscura (`#0F172A`).
- **RHYTHM: 2 (Consistente y predecible):** Estructura modular en grilla de 2 a 3 columnas, separación clara entre filtros y resultados, y cabeceras consistentes.
- **MOTION: 1 (Funcional y no invasivo):** Transiciones sutiles (`150ms-200ms ease`) en `:hover` y `:focus-visible`. Animaciones continuas reservadas exclusivamente para indicadores de carga (spinner institucional).

---

## 2. Tokens de Color (`public/css/municipal-theme.css`)

Tokens CSS oficiales con validación de contraste bajo la norma **WCAG 2.1 AA / AAA**:

| Token CSS | Valor Hex | Uso Institucional | Ratio / WCAG |
| :--- | :--- | :--- | :--- |
| `--muni-brand-blue` | `#0066CC` | Header principal, footer, cabeceras de tabla, elementos activos | `5.57:1` con `#FFF` (PASS AA) |
| `--muni-brand-blue-hover`| `#0052A3` | Hover en elementos con fondo azul principal | `8.26:1` con `#FFF` (PASS AAA) |
| `--muni-login-blue` | `#066FD1` | Botón primario de acceso en Login | `4.95:1` con `#FFF` (PASS AA) |
| `--muni-profile-bg` | `#0167CC` | Fondo de la píldora de usuario en cabecera | `5.51:1` con `#FFF` (PASS AA) |
| `--muni-profile-border` | `#9CCAEC` | Borde de píldora de usuario y botón CAPTCHA | `3.15:1` no-texto (PASS) |
| `--muni-action-yellow` | `#FBC02D` | Botón de acción, hover de tarjeta y rol en perfil | `11.53:1` con `#0F172A` (PASS AAA) |
| `--muni-security-yellow`| `#FED84D` | Franja institucional y acentos secundarios | `13.48:1` con `#0F172A` (PASS AAA) |
| `--muni-page-bg` | `#E7F1FB` | Fondo del lienzo en modo claro e items activos | `13.84:1` con `#0F172A` (PASS AAA) |
| `--muni-card-bg` | `#FFFFFF` | Superficie de tarjetas, modales y tablas | `17.85:1` con `#0F172A` (PASS AAA) |
| `--muni-nav-hover` | `#E1E8F0` | Fondo de hover en enlaces de menú y dropdowns | Contraste óptimo con texto negro |
| `--muni-text-heading` | `#111827` | Títulos principales de sección y tarjetas | `16.10:1` sobre `#FFF` (PASS AAA) |
| `--muni-text-primary` | `#0F172A` | Texto de lectura prioritario e iconos neutros | `17.85:1` sobre `#FFF` (PASS AAA) |
| `--muni-text-body` | `#475569` | Descripciones secundarias y celdas de tabla | `7.58:1` sobre `#FFF` (PASS AAA) |
| `--muni-text-muted` | `#64748B` | Etiquetas auxiliares, subtítulos y eyebrows | `4.76:1` sobre `#FFF` (PASS AA) |
| `--muni-border-muted` | `#E2E8F0` | Delimitadores de tarjetas, paneles y divisores | Separador neutro sutil |

### Píldoras de Estado Semánticas (Indicador circular `6x6px`)

```html
<!-- Activo / Vigente / Procesado -->
<span class="muni-status-pill status-active">Activo</span>

<!-- Pendiente / En proceso / Observado -->
<span class="muni-status-pill status-warning">En Proceso</span>

<!-- Inactivo / Anulado / Rechazado -->
<span class="muni-status-pill status-danger">Anulado</span>
```

- **`status-active`**: Fondo `#ECFDF5`, borde `#A7F3D0`, punto `#10B981`, texto `#047857` (`5.12:1` PASS AA).
- **`status-warning`**: Fondo `#FFFBEB`, borde `#FDE68A`, punto `#F59E0B`, texto `#B45309` (`5.02:1` PASS AA).
- **`status-danger`**: Fondo `#FEF2F2`, borde `#FECACA`, punto `#EF4444`, texto `#B91C1C` (`5.65:1` PASS AA).

---

## 3. Tipografía y Jerarquía Visual

Familia tipográfica base: **Roboto** / **Inter** con renderizado local sin dependencias externas:

- **Login Título Principal:** `clamp(2rem, 3.2vw, 2.75rem)` (`600 SemiBold`), `#FFFFFF`.
- **Hero Bienvenida:** `clamp(1.5rem, 2.5vw, 1.875rem)` (`800 ExtraBold`), saludo en `#FFFFFF`, nombre en `#93C5FD`, y año de gestión alineado al extremo derecho.
- **Títulos de Página (`.page-title`):** `1.5rem` (`24px`, `700 Bold`), color `#0F172A`.
- **Títulos de Tarjeta (`.muni-module-title`, `.card-title`):** `1.25rem` (`20px`, `700 Bold`), color `#111827`.
- **Etiquetas Eyebrow (`.muni-module-eyebrow`):** `10px` (`500 Medium`, `letter-spacing: 0.1em`), tipografía monoespaciada en mayúsculas, color `#64748B`.
- **Cuerpo y Formularios:** `0.875rem` (`14px`, `400 Regular`), color `#475569`.
- **Tablas y Listados:** `0.75rem` - `0.8125rem` (`12px-13px`), cabeceras en mayúsculas sobre `#0066CC` con texto blanco.
- **Título de Pestaña Dinámico:** Centralizado vía `APP_NAME` del entorno (`<title><?= htmlspecialchars(Conectar::getEnv('APP_NAME', 'SISTEMA')); ?></title>`).

---

## 4. Componentes Base y Plantillas de Código

### 4.1 Shell Estructural de una Vista (`view/*/index.php`)

Toda pantalla interna en un sistema municipal debe respetar esta estructura canónica de inclusión:

```php
<?php
require_once("../../config/conexion.php");
// Validar sesión o permisos específicos
if (isset($_SESSION["usua_id_SIGODT"])) {
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <?php require_once("../html/mainHead.php"); ?>
</head>
<body>
  <div class="page">
    <div class="wrapper">
      <?php require_once("../html/mainProfile.php"); ?>
      <?php require_once("../html/menu.php"); ?>
      <div class="page-wrapper">
        <div class="page-body">
          <div class="container-xl py-3">
            <!-- Contenido específico de la vista -->
          </div>
        </div>
        <?php require_once("../html/footer.php"); ?>
      </div>
    </div>
  </div>
  <?php require_once("../html/mainjs.php"); ?>
</body>
</html>
<?php
} else {
  header("Location:" . Conectar::ruta() . "view/404/");
}
?>
```

---

### 4.2 Barra de Navegación Secundaria y Submenús (`menu.php`)

Reglas de interacción implementadas para el navbar institucional (`.muni-subnav`):

1. **Sección Activa**:
   - Fondo: `var(--muni-page-bg)` (`#E7F1FB`).
   - Radio: `border-radius: 8px`.
   - Texto e icono: `var(--muni-brand-blue)` (`#0066CC`) en peso `700`.
   - Hover activo: fondo `#E1E8F0` manteniendo tipografía e icono azul.
   - Sin rayita azul inferior (`display: none !important;`).
2. **Secciones Inactivas**:
   - Texto e iconos: color neutro `#475569` / `#0F172A`.
   - Hover inactivo: fondo `var(--muni-nav-hover, #E1E8F0)` con `border-radius: 8px`, conservando texto e icono neutro (sin cambiar a azul).
3. **Control de Acceso**:
   - Secciones de administración o auditoría se condicionan estrictamente mediante `Conectar::esAdministrador()`.

---

### 4.3 Menú Desplegable de Perfil (`mainProfile.php`, Nodo `49:747`)

- **Píldora de usuario (`.muni-profile-pill`)**: Fondo `#0167CC`, borde de `2px solid #9CCAEC`, cápsula completa (`border-radius: 9999px`), foto de trabajador (`36x36px`) y rol en amarillo (`#FBC02D`). Sus estilos están estrictamente aislados para evitar sobreescrituras desde el subnav.
- **Acción "Configuración"**:
  - En reposo: fondo blanco, texto e icono en `var(--muni-text-primary)` (`#0F172A`).
  - En `:hover` / `:focus`: fondo `#E1E8F0` y texto/icono se mantienen en negro/neutro (sin cambiar a azul).
- **Acción "Cerrar Sesión" (`.muni-item-danger`)**:
  - Texto e icono en rojo `#d63939`, con fondo de hover `rgba(214, 57, 57, 0.08)`.

---

### 4.4 Tarjeta Institucional de Módulo Operativo (Figma Nodo `156:27`)

Estructura HTML exacta para tarjetas de navegación e inicio:

```html
<div class="col-12 col-md-6">
  <article class="muni-module-card">
    <!-- Cabecera: Icono, Eyebrow, Titulo y Estado -->
    <div class="muni-module-header">
      <div class="d-flex align-items-center gap-3">
        <span class="muni-module-icon" aria-hidden="true">
          <!-- Icono SVG representativo de 24x24px -->
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
            <path d="M14 3v4a1 1 0 0 0 1 1h4" />
            <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" />
          </svg>
        </span>
        <div class="muni-module-info">
          <span class="muni-module-eyebrow">OPCIÓN</span>
          <h3 class="muni-module-title">Nombre del Módulo</h3>
        </div>
      </div>
      <span class="muni-status-pill status-active flex-shrink-0">Activo</span>
    </div>

    <!-- Cuerpo: Descripcion descriptiva y concisa -->
    <div class="muni-module-body">
      <p class="muni-module-desc">
        Descripción clara y concisa del procedimiento u operación que gestiona este módulo.
      </p>
    </div>

    <!-- Footer: Boton de Accion Premium con hover #FBC02D -->
    <div class="muni-module-footer">
      <a href="../ruta_modulo/" class="btn btn-muni-card-action">
        <span>ACCEDER AL MÓDULO</span>
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path stroke="none" d="M0 0h24v24H0z" fill="none" />
          <path d="M5 12l14 0" />
          <path d="M13 18l6 -6" />
          <path d="M13 6l6 6" />
        </svg>
      </a>
    </div>
  </article>
</div>
```

**Reglas CSS asociadas:**
- Contenedor de icono: `50x50px`, `border-radius: 14px`, fondo `#E7F1FB`, borde `#BAE6FD`, icono azul `#0066CC`.
- Botón `.btn-muni-card-action`: Fondo `#FFFBEB`, borde `1px solid #FF9500`, radio `10px`, sombra suave.
- **Interacción Hover requerida**: El relleno cambia a `#FBC02D !important;` conservando tipografía e icono oscuro `#111827`.

---

### 4.5 Componente Spinner Institucional MPCH (`view/html/spinner.php`)

Reemplaza de forma global el texto estándar «Procesando...» de DataTables y solicitudes AJAX prolongadas por un indicador de carga cívico.

```html
<!-- Inclusión automática en view/html/mainjs.php -->
<?php require_once __DIR__ . '/spinner.php'; ?>
```

**Características técnicas:**
1. **Fondo difuminado**: `backdrop-filter: blur(5px); background: rgb(0 0 0 / 30%);` centrado en pantalla completa sin oscurecer bruscamente la interfaz.
2. **Escudo Municipal**: Centrado en `public/componente-spinner/public/img/logo_mpch.png` (`65x75px` con `object-fit: contain`).
3. **Anillos concéntricos `#wifi-loader`**: 3 círculos SVG en rotación con los colores institucionales azul `#0068EB` y dorado `#EBB400`.
4. **Funciones Globales**:
   ```javascript
   showSpinner(); // Muestra el overlay y bloquea scroll
   hideSpinner(); // Oculta el overlay y restaura scroll
   ```
5. **Interceptores automáticos**:
   - Evento DataTables `processing.dt`: Muestra el spinner al solicitar datos al servidor y lo oculta al finalizar el dibujado.
   - Evento jQuery `ajaxStop`: Garantiza que el spinner se oculte automáticamente ante cualquier petición HTTP finalizada o fallida.
   - Regla CSS `div.dataTables_processing { display: none !important; }` para asegurar que el antiguo mensaje de texto no interfiera.

---

### 4.6 Tablas de Datos (DataTables) y Formularios de Búsqueda

1. **Ordenamiento Seguro en Servidor**:
   - Columnas de acción (editar, eliminar, anular, imprimir) deben tener `"orderable": false`.
   - Solo se habilita ordenamiento en columnas reales de datos (códigos, fechas, ciudadanos, montos).
   - Servidor debe validar campos con lista blanca estricta para evitar inyección SQL en la cláusula `ORDER BY`.
2. **Dimensiones de Controles**:
   - Inputs y botones de búsqueda principales: Altura estándar de `40px` (`min-height: 40px`).
   - Selectores y botones en línea de formulario: Altura alineada de `38px` con Select2.

---

### 4.7 Breadcrumb y Encabezados de Página Institucional (Estándar SIINDI)

Estructura canónica de navegación y títulos contextuales en cada vista operativa:

```html
<!-- Breadcrumb Contextual -->
<div class="mpch-subsys-breadcrumb">
  <a href="../inicio/" class="mpch-bc-sys text-decoration-none" title="Ir al Inicio">SIGODT</a>
  <span class="mpch-bc-sep">
    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 6l6 6l-6 6" /></svg>
  </span>
  <span class="mpch-bc-sec">Módulo</span>
  <span class="mpch-bc-sep">
    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 6l6 6l-6 6" /></svg>
  </span>
  <span class="mpch-bc-cur">Sección Actual</span>
</div>

<!-- Encabezado de la Página (Título y Descripción) -->
<div class="page-header page-header-context d-print-none mb-3">
  <div class="row align-items-center">
    <div class="col">
      <h2 class="page-title text-uppercase">TÍTULO DE LA SECCIÓN</h2>
      <div class="text-secondary mt-1" style="font-size: 14px;">Descripción clara de las operaciones y alcance de la sección.</div>
    </div>
    <!-- Botón de acción opcional alineado a la derecha -->
    <div class="col-auto ms-auto d-print-none">
      <button class="btn btn-muni-action">...</button>
    </div>
  </div>
</div>
```

**Reglas de estilo y espaciado:**
- `.page-wrapper > .page-body`: `margin-top: 0 !important; padding-top: 0 !important;` para balance simétrico superior/inferior.
- `.mpch-subsys-breadcrumb`: `padding: 24px 0 2px 0 !important; font-size: 13.5px; line-height: 20px; gap: 6px;`.
- `.mpch-bc-sys`: `color: #0066CC; font-weight: 700; text-transform: uppercase;`.
- `.mpch-bc-sep`: Chevron derecho SVG `15x15px`, color `#475569`.
- `.mpch-bc-sec`: `color: #334155; font-weight: 600;`.
- `.mpch-bc-cur`: `color: #0F172A; font-weight: 700;`.
- `.page-title`: `font-size: 20px !important; font-weight: 700 !important; line-height: 32px !important; color: #0F172A !important; text-transform: uppercase;`.
- `.page-header .text-secondary`: `font-size: 14px !important; color: #475569 !important; margin-top: 4px;`.


---

## 5. Guía de Replicación para Nuevos Sistemas Municipales

Para trasladar este sistema de diseño a un nuevo proyecto o módulo dentro de la infraestructura de la MPCH:

### Paso 1: Variables de Entorno (`.env`)
Configurar los identificadores del sistema en el archivo `.env`:
```env
APP_NAME="MI_SISTEMA"
APP_URL="http://10.10.10.16/MI_SISTEMA/"
SIS_VER="1.0.0"
BASE_URL="http://10.10.10.16/"
```

### Paso 2: Estructura de Assets Compartidos
Copiar los directorios base en la raíz del nuevo sistema:
```text
MI_SISTEMA/
├── public/
│   ├── css/
│   │   └── municipal-theme.css         # Hoja de estilos con variables y diseño MPCH
│   ├── componente-spinner/
│   │   └── public/img/logo_mpch.png    # Escudo oficial transparente en alta resolución
│   └── tabler/                         # Core Tabler y tipografía Roboto/Inter
├── view/
│   └── html/
│       ├── mainHead.php                # Meta, títulos con APP_NAME y hojas de estilo
│       ├── mainProfile.php             # Cabecera azul y píldora de usuario
│       ├── menu.php                    # Barra de navegación secundaria institucional
│       ├── footer.php                  # Pie de página institucional
│       ├── mainjs.php                  # Scripts globales y enlace al spinner
│       └── spinner.php                 # Componente overlay institucional
```

### Paso 3: Aislamiento de Sesiones
En `config/conexion.php`, aislar la cookie de sesión usando el identificador del sistema para evitar cierres de sesión cruzados en el servidor compartido:
```php
session_name("{$appName}_SESSID");
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/' . $appName . '/',
    'httponly' => true,
    'samesite' => 'Lax'
]);
```

### Paso 4: Creación de Nuevas Vistas
Utilizar la plantilla canónica del apartado **4.1**, incorporando tarjetas operativas según el estándar **4.4** para secciones de navegación, y tarjetas con franja de seguridad (`.card.shadow-sm.muni-stripe-top`) para módulos de consulta y mantenimiento.
