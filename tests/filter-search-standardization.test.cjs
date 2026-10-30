const { test, describe } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');

describe('Filter and Search Cards Institutional Standardization', () => {
  test('view/consultar_og/index.php: standardized search card and elements', () => {
    const html = readFileSync('view/consultar_og/index.php', 'utf8');

    // Filter card container and header
    assert.match(html, /class="card shadow-sm muni-stripe-top mb-3"/);
    assert.match(html, /<div class="card-header py-2">\s*<h3 class="card-title fw-bold mb-0">Criterio de Búsqueda<\/h3>\s*<\/div>/);

    // Form controls wrapped with input-groups and Tabler icons
    assert.match(html, /icon-tabler-hash[\s\S]*?id="ordenNumero"/);
    assert.match(html, /icon-tabler-calendar[\s\S]*?id="ordenAnio"/);

    // Search button styling, uppercase text, and state elements
    assert.match(html, /id="btnBuscarOrden"[^>]*class="[^"]*btn-muni-action[^"]*"/);
    assert.match(html, /<span id="textBuscar">BUSCAR<\/span>/);
    assert.match(html, /id="spinnerBuscar"/);
    assert.match(html, /id="iconBuscar"/);

    // Critical DOM IDs preserved
    const criticalIds = [
      'ordenGiroForm',
      'ordenNumero',
      'ordenAnio',
      'btnBuscarOrden',
      'errorMessage',
      'ordenGiroInfo',
      'ordenGiroPdf',
      'openPdfBtn'
    ];
    for (const id of criticalIds) {
      assert.ok(html.includes(`id="${id}"`), `Expected critical DOM ID "${id}" in consultar_og`);
    }
  });

  test('view/consultar_mes/index.php: dedicated top filter card and separate results card', () => {
    const html = readFileSync('view/consultar_mes/index.php', 'utf8');

    // Dedicated filter card
    assert.match(html, /class="card shadow-sm muni-stripe-top mb-3"/);
    assert.match(html, /<div class="card-header py-2">\s*<h3 class="card-title fw-bold mb-0">Criterio de Búsqueda<\/h3>\s*<\/div>/);
    assert.match(html, /icon-tabler-search[\s\S]*?id="searchInput"/);

    // Separate results card
    assert.match(html, /<div class="card shadow-sm mb-3">\s*<div class="card-header py-2">\s*<h3 class="card-title fw-bold mb-0">Órdenes Emitidas en el Mes<\/h3>\s*<\/div>/);

    // Critical DOM IDs preserved
    const criticalIds = ['searchInput', 'ordenesMesList', 'infoPaginacion', 'pagination'];
    for (const id of criticalIds) {
      assert.ok(html.includes(`id="${id}"`), `Expected critical DOM ID "${id}" in consultar_mes`);
    }
  });

  test('view/consultar_nombre/index.php: input-group with icon and uppercase action button', () => {
    const html = readFileSync('view/consultar_nombre/index.php', 'utf8');

    // Filter card container and header
    assert.match(html, /class="card shadow-sm muni-stripe-top mb-3"/);
    assert.match(html, /<div class="card-header py-2">\s*<h3 class="card-title fw-bold mb-0">Criterio de Búsqueda<\/h3>\s*<\/div>/);

    // Search input wrapped in input-group with icon
    assert.match(html, /<div class="input-group">[\s\S]*?icon-tabler-user[\s\S]*?id="nombreSearch"/);

    // Action button with btn-muni-action and uppercase BUSCAR label
    assert.match(html, /<button type="submit" class="btn btn-muni-action w-100">[\s\S]*?BUSCAR\s*<\/button>/);

    // Critical DOM IDs preserved
    const criticalIds = [
      'nombreForm',
      'nombreSearch',
      'nombreHelp',
      'nombreMessage',
      'nombreResults',
      'nombreEntities',
      'nombreHistory',
      'nombreOrders'
    ];
    for (const id of criticalIds) {
      assert.ok(html.includes(`id="${id}"`), `Expected critical DOM ID "${id}" in consultar_nombre`);
    }
  });

  test('view/dashboard/index.php: filter card container and institutional filter button', () => {
    const html = readFileSync('view/dashboard/index.php', 'utf8');

    // Card container and header
    assert.match(html, /class="card shadow-sm muni-stripe-top mb-3"/);
    assert.match(html, /<div class="card-header py-2">\s*<h3 class="card-title fw-bold mb-0">Filtros de consulta<\/h3>\s*<\/div>/);

    // Filter button with btn-muni-action, icon-tabler-filter, and FILTRAR label
    assert.match(html, /class="[^"]*btn-muni-action[^"]*"[^>]*id="filtrarBtn"|id="filtrarBtn"[^>]*class="[^"]*btn-muni-action[^"]*"/);
    assert.match(html, /icon-tabler-filter/);
    assert.match(html, /<span>FILTRAR<\/span>/);

    // Critical DOM IDs preserved
    const criticalIds = ['fechaIni', 'fechaFin', 'filtrarBtn', 'badgesEstados'];
    for (const id of criticalIds) {
      assert.ok(html.includes(`id="${id}"`), `Expected critical DOM ID "${id}" in dashboard`);
    }
  });

  test('view/giros_ciudadano/index.php: breadcrumb-arrows, stripe top, and action button', () => {
    const html = readFileSync('view/giros_ciudadano/index.php', 'utf8');

    // Standard Tabler breadcrumbs
    assert.match(html, /<ol class="breadcrumb breadcrumb-arrows mb-3" aria-label="breadcrumbs">/);
    assert.doesNotMatch(html, /<nav class="breadcrumb mb-3">/);

    // Registration card with muni-stripe-top mb-3
    assert.match(html, /class="card shadow-sm muni-stripe-top mb-3"[\s\S]*?Registrar Orden de Derecho de Trámite/);

    // Action button with btn-muni-action and uppercase NUEVO
    assert.match(html, /class="[^"]*btn-muni-action[^"]*"[^>]*id="add_button"|id="add_button"[^>]*class="[^"]*btn-muni-action[^"]*"/);
    assert.match(html, /id="add_button"[\s\S]*?<span>NUEVO<\/span>|class="[^"]*btn-muni-action[^"]*"[\s\S]*?<span>NUEVO<\/span>/);

    // Critical DOM IDs preserved
    const criticalIds = [
      'area_id',
      'proced_id',
      'add_button',
      'tupa_id',
      'usua_dni_SIGODT',
      'usu_depe_id_SIGODT',
      'cardDetalleProcedimiento',
      'giro_state',
      'btnRecargar'
    ];
    for (const id of criticalIds) {
      assert.ok(html.includes(`id="${id}"`), `Expected critical DOM ID "${id}" in giros_ciudadano`);
    }
  });

  test('view/admin_giros_ciudadano/index.php: card header, stripe top, and action button', () => {
    const html = readFileSync('view/admin_giros_ciudadano/index.php', 'utf8');

    // Card 1 styling and header
    assert.match(html, /class="card shadow-sm muni-stripe-top mb-3"/);
    assert.match(html, /<div class="card-header py-2">\s*<h3 class="card-title fw-bold mb-0">Filtros de consulta<\/h3>\s*<\/div>/);

    // Action button with btn-muni-action
    assert.match(html, /class="[^"]*btn-muni-action[^"]*"[^>]*id="add_button"|id="add_button"[^>]*class="[^"]*btn-muni-action[^"]*"/);

    // Critical DOM IDs preserved
    const criticalIds = ['tupa_id', 'area_id', 'proced_id', 'print_button', 'add_button', 'btnRecargar'];
    for (const id of criticalIds) {
      assert.ok(html.includes(`id="${id}"`), `Expected critical DOM ID "${id}" in admin_giros_ciudadano`);
    }
  });

  test('view/mnt_procedimiento/index.php: standardized filter card header', () => {
    const html = readFileSync('view/mnt_procedimiento/index.php', 'utf8');

    // Filter card header above card-body
    assert.match(html, /class="card shadow-sm muni-stripe-top mb-3"[\s\S]*?<div class="card-header py-2">\s*<h3 class="card-title fw-bold mb-0">Filtros de consulta<\/h3>\s*<\/div>[\s\S]*?<div class="card-body">/);

    // Critical DOM IDs preserved
    const criticalIds = ['tupa_id', 'area_id', 'btnnuevoproced', 'proceds_data'];
    for (const id of criticalIds) {
      assert.ok(html.includes(`id="${id}"`), `Expected critical DOM ID "${id}" in mnt_procedimiento`);
    }
  });

  test('view/mnt_tributos/index.php: standardized filter card header', () => {
    const html = readFileSync('view/mnt_tributos/index.php', 'utf8');

    // Filter card header above card-body
    assert.match(html, /class="card shadow-sm muni-stripe-top mb-3"[\s\S]*?<div class="card-header py-2">\s*<h3 class="card-title fw-bold mb-0">Filtros de consulta<\/h3>\s*<\/div>[\s\S]*?<div class="card-body">/);

    // Critical DOM IDs preserved
    const criticalIds = ['tupa_id', 'area_id', 'proced_id', 'botonRegistrarNuevo', 'detalle_data'];
    for (const id of criticalIds) {
      assert.ok(html.includes(`id="${id}"`), `Expected critical DOM ID "${id}" in mnt_tributos`);
    }
  });

  test('view/mnt_ciudadano: standardized search card, results card, and event handlers', () => {
    const html = readFileSync('view/mnt_ciudadano/index.php', 'utf8');
    const js = readFileSync('view/mnt_ciudadano/ciud.js', 'utf8');

    // Dedicated search card
    assert.match(html, /class="card shadow-sm muni-stripe-top mb-3"/);
    assert.match(html, /<div class="card-header py-2">\s*<h3 class="card-title fw-bold mb-0">Criterio de Búsqueda<\/h3>\s*<\/div>/);
    assert.match(html, /icon-tabler-id[\s\S]*?id="search"/);

    // Dedicated results card with proper title
    assert.match(html, /<div class="card shadow-sm mb-3">\s*<div class="card-header py-2">\s*<h3 class="card-title fw-bold mb-0">Listado General de Administrados<\/h3>\s*<\/div>/);

    // Action button with btn-muni-action and uppercase BUSCAR
    assert.match(html, /class="[^"]*btn-muni-action[^"]*"[^>]*id="btnBuscarCiudadano"|id="btnBuscarCiudadano"[^>]*class="[^"]*btn-muni-action[^"]*"/);
    assert.match(html, /BUSCAR/);

    // Critical DOM IDs preserved
    const criticalIds = ['formSearchCiudadano', 'search', 'btnBuscarCiudadano', 'tabla-ciudadano'];
    for (const id of criticalIds) {
      assert.ok(html.includes(`id="${id}"`), `Expected critical DOM ID "${id}" in mnt_ciudadano`);
    }

    // JS handlers
    assert.match(js, /\$\(["']#search["']\)\.on\(["']input["']/);
    assert.match(js, /\$\(["']#formSearchCiudadano["']\)\.on\(["']submit["']/);
    assert.match(js, /\$\(["']#btnBuscarCiudadano["']\)\.on\(["']click["']/);
    assert.match(js, /tabla\.ajax\.reload\(\)/);
  });

  test('view/mnt_empresa: standardized search card, results card, and event handlers', () => {
    const html = readFileSync('view/mnt_empresa/index.php', 'utf8');
    const js = readFileSync('view/mnt_empresa/empresa.js', 'utf8');

    // Dedicated search card
    assert.match(html, /class="card shadow-sm muni-stripe-top mb-3"/);
    assert.match(html, /<div class="card-header py-2">\s*<h3 class="card-title fw-bold mb-0">Criterio de Búsqueda<\/h3>\s*<\/div>/);
    assert.match(html, /icon-tabler-building[\s\S]*?id="search"/);

    // Dedicated results card with proper title
    assert.match(html, /<div class="card shadow-sm mb-3">\s*<div class="card-header py-2">\s*<h3 class="card-title fw-bold mb-0">Listado General de Empresas<\/h3>\s*<\/div>/);

    // Action button with btn-muni-action and uppercase BUSCAR
    assert.match(html, /class="[^"]*btn-muni-action[^"]*"[^>]*id="btnBuscarEmpresa"|id="btnBuscarEmpresa"[^>]*class="[^"]*btn-muni-action[^"]*"/);
    assert.match(html, /BUSCAR/);

    // Critical DOM IDs preserved
    const criticalIds = ['formSearchEmpresa', 'search', 'btnBuscarEmpresa', 'tabla-empresa'];
    for (const id of criticalIds) {
      assert.ok(html.includes(`id="${id}"`), `Expected critical DOM ID "${id}" in mnt_empresa`);
    }

    // JS handlers
    assert.match(js, /\$\(["']#search["']\)\.on\(["']input["']/);
    assert.match(js, /\$\(["']#formSearchEmpresa["']\)\.on\(["']submit["']/);
    assert.match(js, /\$\(["']#btnBuscarEmpresa["']\)\.on\(["']click["']/);
    assert.match(js, /tabla\.ajax\.reload\(\)/);
  });

  test('view/mnt_tasas: standardized search card, results card, dom Brtip, and event handlers', () => {
    const html = readFileSync('view/mnt_tasas/index.php', 'utf8');
    const js = readFileSync('view/mnt_tasas/main.js', 'utf8');

    // Dedicated search card
    assert.match(html, /class="card shadow-sm muni-stripe-top mb-3"/);
    assert.match(html, /<div class="card-header py-2">\s*<h3 class="card-title fw-bold mb-0">Criterio de Búsqueda<\/h3>\s*<\/div>/);
    assert.match(html, /icon-tabler-coin[\s\S]*?id="searchTasa"/);

    // Dedicated results card with proper title
    assert.match(html, /<div class="card shadow-sm mb-3">\s*<div class="card-header py-2">\s*<h3 class="card-title fw-bold mb-0">Listado de Tasas Registradas<\/h3>\s*<\/div>/);

    // Action button with btn-muni-action and uppercase BUSCAR
    assert.match(html, /class="[^"]*btn-muni-action[^"]*"[^>]*id="btnBuscarTasa"|id="btnBuscarTasa"[^>]*class="[^"]*btn-muni-action[^"]*"/);
    assert.match(html, /BUSCAR/);

    // Critical DOM IDs preserved
    const criticalIds = ['formSearchTasa', 'searchTasa', 'btnBuscarTasa', 'tasa_data', 'add_button'];
    for (const id of criticalIds) {
      assert.ok(html.includes(`id="${id}"`), `Expected critical DOM ID "${id}" in mnt_tasas`);
    }

    // DataTables dom updated to 'Brtip' to eliminate redundant search box
    assert.match(js, /dom:\s*['"]Brtip['"]/);
    assert.doesNotMatch(js, /dom:\s*['"]Bfrtip['"]/);

    // JS handlers
    assert.match(js, /\$\(["']#searchTasa["']\)\.on\(["']keyup input["']/);
    assert.match(js, /\$\(["']#formSearchTasa["']\)\.on\(["']submit["']/);
    assert.match(js, /\$\(["']#btnBuscarTasa["']\)\.on\(["']click["']/);
    assert.match(js, /\$\(['"]#tasa_data['"]\)\.DataTable\(\)\.search\(\$\(['"]#searchTasa['"]\)\.val\(\)\)\.draw\(\)/);
  });
});
