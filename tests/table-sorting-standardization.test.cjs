const { test, describe } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');

describe('Table sorting standardization', () => {
  test('action columns are not orderable in existing DataTables', () => {
    const expectations = [
      ['view/mnt_tasas/main.js', /"orderable": false, "targets": \[2, 3\]/],
      ['view/mnt_procedimiento/main.js', /targets: \[7, 8\], orderable: false/],
      ['view/admin_giros_ciudadano/admindetalleciudadano.js', /targets: \[5, 6, 7, 8\], orderable: false/],
      ['view/mnt_tributos/main.js', /"targets": \[6, 7\], "orderable": false/],
      ['view/mnt_permisos/adminmntusuarea.js', /targets: 4, className: "text-center", orderable: false/],
      ['view/mnt_permisos/adminmntusuarea.js', /targets: 0, className: "text-center", orderable: false/],
      ['view/mnt_ciudadano/ciud.js', /orderable: false/]
    ];
    for (const [file, pattern] of expectations) {
      assert.match(readFileSync(file, 'utf8'), pattern, file);
    }
  });

  test('server-paged list in giros_ciudadano enables ordering with actions disabled', () => {
    const js = readFileSync('view/giros_ciudadano/usudetalleciudadano.js', 'utf8');
    assert.match(js, /serverSide: true/);
    assert.match(js, /order: \[\[3, "desc"\]\]/);
    assert.match(js, /targets: \[5, 6, 7\], orderable: false/);
  });

  test('server-paged consultas and report details wire data-sort headers for server ordering', () => {
    const mes = readFileSync('view/consultar_mes/index.php', 'utf8');
    assert.match(mes, /data-sort="orden"/);
    assert.match(mes, /data-sort="fecha"/);
    assert.match(mes, /sort=\$\{encodeURIComponent\(currentSort\)\}&dir=\$\{encodeURIComponent\(currentDir\)\}/);

    const nombreHtml = readFileSync('view/consultar_nombre/index.php', 'utf8');
    assert.match(nombreHtml, /data-sort="tipo"/);
    assert.match(nombreHtml, /data-sort="recibo"/);

    const nombreJs = readFileSync('view/consultar_nombre/consultarnombre.js', 'utf8');
    assert.match(nombreJs, /sort: entitySort, dir: entityDir/);
    assert.match(nombreJs, /sort: historySort, dir: historyDir/);

    const repHtml = readFileSync('view/reporte_usuario/index.php', 'utf8');
    assert.match(repHtml, /id="tableReportDetails"/);
    assert.match(repHtml, /data-sort="order"/);
    assert.match(repHtml, /data-sort="date"/);

    const repJs = readFileSync('view/reporte_usuario/reporteusuario.js', 'utf8');
    assert.match(repJs, /sort: detailSort,\s*dir: detailDir/);
  });

  test('consultar_documento initializes a DataTable and re-inits after each render', () => {
    const html = readFileSync('view/consultar_documento/index.php', 'utf8');
    assert.match(html, /\$table\.DataTable\(\{/);
    assert.match(html, /\.DataTable\(\)\.destroy\(\)/);
    assert.match(html, /columnDefs: \[\{ targets: \[0, 3, 9\], orderable: false \}\]/);
    assert.doesNotMatch(html, /<td colspan="10"/);
  });

  test('reporte_usuario breakdown is sortable and destroyed before re-render', () => {
    const js = readFileSync('view/reporte_usuario/reporteusuario.js', 'utf8');
    assert.match(js, /grid\.DataTable\(\)\.destroy\(\)/);
    assert.match(js, /grid\.DataTable\(\{/);
    assert.match(js, /row\.amount\]\), true\)/);
  });

  test('server-paged consultas keep plain tables (client sorting would be misleading)', () => {
    for (const file of ['view/consultar_mes/index.php', 'view/consultar_nombre/index.php', 'view/consultar_og/index.php']) {
      assert.doesNotMatch(readFileSync(file, 'utf8'), /\.DataTable\(/, file);
    }
  });

  test('municipal theme styles active arrow and hides arrows on non-orderable columns', () => {
    const css = readFileSync('public/css/municipal-theme.css', 'utf8');
    assert.match(css, /th\.sorting_asc:before,[\s\S]*?th\.sorting_desc:after \{\s*color: var\(--muni-brand-blue\);\s*opacity: 1;/);
    assert.match(css, /th\.sorting_disabled \{\s*cursor: default;/);
    assert.match(css, /th\.sorting_disabled:after \{\s*display: none !important;/);
  });
});
