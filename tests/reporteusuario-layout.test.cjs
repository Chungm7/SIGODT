const { test } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const view = readFileSync('view/reporte_usuario/index.php', 'utf8');

test('S9: report follows shared shell, navigable header and named report cards', () => {
  assert.match(view, /demo-theme\.min\.js/);
  assert.match(view, /breadcrumb[\s\S]*href="\.\.\/inicio\/"[\s\S]*aria-current="page">Mis emisiones/);
  assert.match(view, /class="page-title">Reporte de mis emisiones/);
  for (const title of ['Filtros de consulta', 'Resumen de emisiones', 'Desglose por documento y procedimiento', 'Detalle de órdenes']) {
    assert.ok(view.includes(`class="card-title">${title}</h2>`), title);
  }
  assert.equal((view.match(/table-responsive/g) || []).length, 2);
  assert.equal((view.match(/table card-table table-vcenter/g) || []).length, 2);
  assert.match(view, /card-footer[\s\S]*id="reportPrev"[\s\S]*id="reportPage"[\s\S]*id="reportNext"/);
});

test('S3/S6/S9: metrics, complete columns, distinct exports and accessible table regions', () => {
  for (const id of ['reportOrders', 'reportCancelled', 'reportLines', 'reportAmount']) {
    assert.match(view, new RegExp(`<dd[^>]*id="${id}"`));
  }
  for (const label of ['Ciudadano / Razón social', 'Nombre del procedimiento', 'Estado actual', 'Importe (S/)']) assert.ok(view.includes(label));
  assert.match(view, /id="reportCsv" class="btn btn-outline-secondary"/);
  assert.match(view, /id="reportPrint"[^>]*target="_blank" rel="noopener"/);
  assert.equal((view.match(/tabindex="0" role="region" aria-label=/g) || []).length, 2);
  const css = readFileSync('view/reporte_usuario/reporteusuario.css', 'utf8');
  assert.match(css, /\.report-page[\s\S]*min-height: 44px/);
  assert.match(css, /\.report-page[\s\S]*overflow-wrap: anywhere/);
  assert.doesNotMatch(css, /text-overflow:\s*ellipsis|overflow:\s*hidden/);
});
