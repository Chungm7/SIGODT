# Volumen 4: Vistas y Capa Frontend
**Sistema Integrado de Gestión de Órdenes de Derecho de Trámite (SIGODT)**  
**Municipalidad Provincial de Chiclayo (MPCH)**  

---

## 🎨 1. Arquitectura de la Interfaz de Usuario

La capa de presentación del sistema está construida sobre **Tabler UI (v1.0)**, un framework visual moderno basado en **Bootstrap 5**, complementado con componentes avanzados de experiencia de usuario en JavaScript:

```
Vistas (PHP / HTML5)
 ├── Estructura Base: Tabler UI + Tabler Icons (SVG)
 ├── Tablas Interactivas: DataTables.js (Responsive + Buttons Export Excel/Print)
 ├── Alertas y Modales: SweetAlert2 (Modales asíncronos) + Toastr (Notificaciones push)
 ├── Formularios Avanzados: Select2 (Combos con búsqueda predictiva)
 └── Renderizado de Comprobantes: FPDF (Ticket térmico 117 mm) + phpqrcode (Generador QR)
```

### Plantilla Maestra (Master Layout)
Para asegurar consistencia visual y reutilización de código, las vistas consumen componentes comunes ubicados en `view/html/`:
- **`mainHead.php`:** Centraliza las hojas de estilo (CSS de Tabler, FontAwesome, DataTables, SweetAlert2, Toastr) y metaetiquetas responsivas.
- **`mainProfile.php`:** Barra superior que muestra el avatar del usuario, nombre completo, rol asignado, selector de tema (modo claro / modo oscuro) y acceso directo a configuración de cuenta.
- **`menu.php`:** Barra de navegación principal que despliega dinámicamente los módulos según el perfil del usuario autenticado (Inicio, Procesos, Mantenimientos, Reportes).
- **`mainjs.php`:** Centraliza las librerías JavaScript globales (jQuery 3.6.0, Bootstrap bundle, DataTables, Select2, SweetAlert2).
- **`footer.php`:** Pie de página institucional con derechos reservados de la Municipalidad Provincial de Chiclayo.

---

## 🖥️ 2. Catálogo Exhaustivo de Vistas del Sistema

A continuación se detalla cada una de las vistas presentes en la carpeta [`view/`](file:///home/victorchung/PhpstormProjects/MPCH_Ordenes_Giro_SIGODT/view/) y en la raíz del proyecto:

### 2.1. Módulo de Autenticación y Acceso
- **`index.php` (Pantalla de Login):**
  - Fondo dinámico institucional de Chiclayo con overlay estilizado.
  - Formulario de credenciales con validación en vivo: limita el campo usuario a un máximo de 8 dígitos numéricos mediante JavaScript (`limitarADigitosDNI`).
  - Toggle para mostrar/ocultar contraseña en texto claro.
  - Enlace de recuperación de credenciales redirigido al servicio institucional `sisSeguridad`.
  - **Widget de Captcha Dinámico:** Integra recarga asíncrona mediante `fetch('captcha.php?action=generateCaptcha')` para evitar ataques de fuerza bruta automatizados.
  - Visualización de mensajes de error parametrizados (`?m=1` al `11`), alertando sobre credenciales erróneas, IP no autorizada, cuenta inactiva o fuera de horario laboral.
- **`captcha.php` (Servicio de Captcha):**
  - Genera mediante la librería gráfica PHP GD una imagen distorsionada con ruido y almacena el token en `$_SESSION['captcha']`.

---

### 2.2. Módulo de Procesos (Emisión de Órdenes de Giro)
- **`view/giros_ciudadano/index.php` (Emisión y Detalle de Giros):**
  - **Es la pantalla neurálgica del sistema**, operada por el personal de ventanilla.
  - **Panel Lateral de Rendimiento:** Muestra en tiempo real las métricas del girador:
    - *Total Órdenes Históricas*
    - *Total Órdenes Emitidas Hoy*
    - *Total Órdenes Emitidas Ayer*
  - **Formulario de Selección de Administrado:**
    - Selector de tipo de persona: Natural (DNI, CEE, CPP) o Jurídica (Empresa / RUC).
    - Campo de búsqueda interactiva: al presionar enter o perder el foco, dispara peticiones AJAX que consultan la base local o consumen la PIDE (RENIEC / SUNAT) completando automáticamente nombres, apellidos, razón social y dirección.
  - **Selector Normativo en Cascada:**
    - Carga el TUPA vigente activo.
    - Filtra los procedimientos habilitados para el área del operador (`td_areausu`).
    - Al seleccionar un procedimiento, renderiza dinámicamente la grilla de tasas aplicables (`modaltasas.php`).
  - **Componente de Liquidación y Pago:**
    - Modales asociados: `modal_pago.php` (confirmación de emisión y captura de comentarios justificatorios) y `modalmantenimiento.php` (edición rápida de direcciones).
- **`view/admin_giros_ciudadano/index.php` (Gestión Administrativa de Giros):**
  - Vista para supervisores y administradores de recaudación.
  - Permite auditar órdenes de cualquier dependencia, ver montos globales e invocar el modal `modaleditcomentario.php` para corregir o agregar observaciones justificatorias en órdenes ya generadas.
- **`view/working/index.php` (Módulo en Construcción):**
  - Pantalla con ilustración vectorial que informa a los usuarios que el submódulo de *Registros Trabajadores* se encuentra en fase de desarrollo.

---

### 2.3. Módulos de Mantenimiento de Catálogos (CRUDs)
- **`view/mnt_ciudadano/index.php` (Padrón de Ciudadanos):**
  - Administrado por `ciud.js`.
  - DataTable server-side con buscador integral (por DNI, nombres y apellidos concatenados).
  - Permite verificar la fotografía almacenada en Base64 y actualizar domicilios reales.
- **`view/mnt_empresa/index.php` (Padrón de Empresas):**
  - Administrado por `empresa.js`.
  - DataTable con listado de razones sociales, RUCs, nombres comerciales, estado fiscal (Activo / Baja) y categoría tributaria (Empresa, Mercado, Libre).
- **`view/mnt_tupa/index.php` (Gestión de TUPA):**
  - Administrado por `main.js` y `modalmantenimiento.php`.
  - Operaciones clave:
    - Registrar nuevo TUPA asignando denominación y año fiscal.
    - Activar TUPA vigente (estado `2`).
    - Bloquear / desbloquear TUPA contra alteraciones accidentales (`tupa_block`).
    - **Botón de Duplicación Masiva:** Clona todo el árbol de procedimientos y tasas de un TUPA anterior hacia el año fiscal entrante con un solo clic.
- **`view/mnt_procedimiento/index.php` (Catálogo de Procedimientos):**
  - Administrado por `main.js` y `modalmantenimiento.php`.
  - Permite clasificar cada trámite por: Área responsable, Código TUPA, Denominación oficial, Tipo de administrado permitido (`C`, `E` o `D`) y campos obligatorios.
- **`view/mnt_tasas/index.php` (Catálogo de Tasas Base):**
  - Administrado por `main.js` y `tasa.php`.
  - Mantiene el catálogo de nombres de tasas y define si son de naturaleza fija o multiplicativa.
- **`view/mnt_tributos/index.php` (Matriz de Costos y Partidas):**
  - Administrado por `main.js`, `modalmantenimiento.php` y `modaltasamonto.php`.
  - Es la vista donde se cruza un Procedimiento con una Tasa, asignándole su valor monetario oficial en Soles (S/.), su código de referencia contable (`cod_ref`) exigido por el SATCH y su orden de aparición en el ticket.
- **`view/mnt_permisos/index.php` (Asignación de Usuarios a Áreas):**
  - Administrado por `adminmntusuarea.js`, `modalmantenimiento.php` y `modalusu.php`.
  - Permite que el administrador asigne a qué dependencias municipales (`tb_dependencia`) tiene potestad de girar cada usuario (`td_areausu`), impidiendo que un operador de Transporte emita tasas de Urbanismo o viceversa.

---

### 2.4. Módulos de Consulta, Reportes y Fiscalización
- **`view/consultar_documento/index.php` (Consulta por Documento del Administrado):**
  - Administrado por `otros_registros.js`.
  - Formulario de búsqueda rápida por DNI, RUC, Carnet de Extranjería o CPP.
  - Lista histórica de todas las órdenes emitidas a nombre del administrado con badges de estado (GIRADO, PAGADO, USADO, ANULADO) y botón de reimpresión de comprobante.
- **`view/consultar_og/index.php` (Consulta por Código de Orden):**
  - Permite buscar directamente por el código correlativo de orden (`000000-AAAA`) o por el número de recibo de caja (`recibo_nro`).
- **`view/consultar_mes/index.php` (Auditoría Mensual de Órdenes):**
  - Reporte consolidado de todas las órdenes generadas en el mes actual con paginación server-side, montos totales y nombre del girador que autorizó la operación.
- **`view/dashboard/index.php` (Tablero de Control y Métricas):**
  - Administrado por `dashboard.js`.
  - Gráficos interactivos y tarjetas KPI con filtros por rango de fechas y estado:
    - *Total Recaudado (S/.)*
    - *Monto Pendiente de Cobro en SATCH*
    - *Recaudación desglosada por Gerencia / Dependencia*
    - *Ranking de operadores con mayor emisión*
    - *Gráfico de evolución mensual de recaudación*
    - *Reporte de incidencias y cancelaciones por usuario*

---

### 2.5. Vistas Auxiliares
- **`view/setting/index.php`:** Módulo de perfil personal donde el usuario consulta sus datos de escalafón y puede cambiar su contraseña institucional consumiendo el API de seguridad.
- **`view/404/index.php`:** Pantalla de error con diseño Tabler que captura accesos no autorizados, parámetros inválidos o sesiones expiradas.

---

## 🖨️ 3. Generación y Renderizado de Comprobantes (PDF + QR)

La emisión física del comprobante de pago está centralizada en [`controller/rc.php`](file:///home/victorchung/PhpstormProjects/MPCH_Ordenes_Giro_SIGODT/controller/rc.php):

- **Formato:** Ticket térmico de papel continuo (ancho: **117 mm**, alto: **550 mm**), diseñado específicamente para las impresoras térmicas de ventanilla de la MPCH.
- **Librería de Maquetación:** FPDF (`public/plantilla_reporte.php`).
- **Contenido del Ticket:**
  1. Logotipo oficial de la Municipalidad Provincial de Chiclayo (`public/logo144.png`).
  2. Encabezado institucional con RUC `20141784901` y dirección del local municipal emisor.
  3. Código identificador de la orden de giro (ej. `013341-2026`).
  4. Fecha y hora exacta de emisión.
  5. Denominación del TUPA, área emisora y nombre del operador de ventanilla.
  6. Datos del administrado (DNI/RUC, nombres o razón social, domicilio).
  7. Tabla detallada de tasas, cantidades, montos unitarios y total general en soles (S/.).
  8. **Estampado de Código QR Dinámico:**
     - Generado al vuelo mediante la librería `controller/phpqrcode/qrlib.php`.
     - Contiene la cadena identificadora de la orden para que el escáner de código de barras bidimensional del cajero en el SATCH cargue automáticamente la orden en su pantalla sin digitación manual.
