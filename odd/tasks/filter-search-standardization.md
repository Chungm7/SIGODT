# Filter and Search Section Standardization

## Objective
Standardize the visual and structural design of search and filter sections across all system views and subviews according to institutional patterns (Variante A: single/quick search criteria; Variante B: multi-criteria filter grid), matching the established design in `consultar_documento` and `reporte_usuario` with `muni-stripe-top`, `breadcrumb-arrows`, and `btn-muni-action`.

## Scope and authorization
Target views:
- Queries: `view/consultar_og/index.php`, `view/consultar_mes/index.php`, `view/consultar_nombre/index.php`
- Dashboard: `view/dashboard/index.php`
- Processes: `view/giros_ciudadano/index.php`, `view/admin_giros_ciudadano/index.php`
- Catalogs / Maintenance: `view/mnt_procedimiento/index.php`, `view/mnt_tributos/index.php`
Preserve all existing JavaScript IDs, form event bindings, and query parameter contracts.

## Design boundaries
- Card containers use `card shadow-sm muni-stripe-top mb-3`.
- Card headers strictly define `<h3 class="card-title fw-bold mb-0">` with either "Criterio de Búsqueda" (direct searches) or "Filtros de consulta" (multi-criteria).
- Action buttons use institutional gold `.btn-muni-action` with uppercase labels (`BUSCAR`, `CONSULTAR`, `FILTRAR`) and Tabler SVG icons.
- Search input fields use `input-group` with `input-group-text` Tabler icon addons.
- Breadcrumbs conform to standard Tabler `<ol class="breadcrumb breadcrumb-arrows mb-3">`.
- No disruption to existing DOM IDs (`#searchForm`, `#searchBtn`, `#ordenGiroForm`, `#btnBuscarOrden`, `#searchInput`, `#nombreForm`, `#fechaIni`, `#fechaFin`, `#filtrarBtn`, `#area_id`, `#proced_id`, etc.).

## Tasks
- [x] T1 — Standardize Queries module (`consultar_og`, `consultar_mes`, `consultar_nombre`).
- [x] T2 — Standardize Dashboard filter controls (`view/dashboard/index.php`).
- [x] T3 — Standardize Processes views (`giros_ciudadano`, `admin_giros_ciudadano`).
- [x] T4 — Standardize Catalogs filter cards (`mnt_procedimiento`, `mnt_tributos`).
- [x] T5 — Automated test suite verifying markup standard across all views and regression tests.

## Evidence and verification
- Suite `tests/filter-search-standardization.test.cjs`: 8 tests covering stripe headers, action buttons, breadcrumbs, and preserved DOM IDs across all updated views.
- Full regression run: 61/61 passing tests across functional and theme suites. Zero regressions.
