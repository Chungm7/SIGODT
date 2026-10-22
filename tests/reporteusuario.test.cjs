const { test } = require('node:test');
const assert = require('node:assert/strict');
const report = require('../view/reporte_usuario/reporteusuario.js');

test('S4: local current-month dates do not drift through UTC', () => {
  assert.deepEqual(report.monthRange(new Date(2025, 0, 31, 23, 59)), { start: '2025-01-01', end: '2025-01-31' });
  assert.deepEqual(report.monthRange(new Date(2024, 1, 15)), { start: '2024-02-01', end: '2024-02-29' });
});
test('S2/S6/S7: export reuses filters, never page or emitter, accurate format', () => {
  for (const operation of ['print', 'pdf']) {
    const url = report.reportUrl({ start: '2025-01-01', end: '2025-01-31', state: 'emitidos', document: '1', procedure: '2', page: 9, emitter: 100, worker: 3, full: true }, operation);
    const query = new URL(url, 'https://example.invalid').searchParams;
    assert.equal(query.get('op'), operation);
    assert.equal(query.get('state'), 'emitidos');
    assert.equal(query.get('document'), '1');
    assert.equal(query.get('procedure'), '2');
    for (const key of ['page', 'emitter', 'worker', 'full']) assert.equal(query.has(key), false);
  }
});
test('S3/S5: cells render untrusted text without HTML and preserve multi labels', () => {
  const row = { appendChild(child) { this.child = child; } };
  const doc = { createElement() { return {}; } };
  report.cell(row, ['<script>', '=A1'], doc);
  assert.equal(row.child.textContent, '<script> / =A1');
  assert.equal(row.child.innerHTML, undefined);
});

function fixtureDocument() {
  function element() {
    return {
      value: '', children: [], listeners: {}, hidden: false, disabled: false,
      appendChild(child) { this.children.push(child); },
      replaceChildren() { this.children = []; },
      addEventListener(name, callback) { this.listeners[name] = callback; },
      fire(name, target = this) { this.listeners[name]({ target, preventDefault() {} }); }
    };
  }
  const elements = new Map();
  return {
    createElement: element,
    getElementById(id) {
      if (id === 'reportCsv') return null;
      if (!elements.has(id)) elements.set(id, element());
      return elements.get(id);
    }
  };
}
function payload(page = 1) {
  return {
    page, total: 21, limit: 20, emitter: '<script>emitter</script>', generated_at: '2025-01-31 12:00:00',
    summary: { orders: 21, cancelled: 2, lines: 25, amount: '120.00' },
    options: { documents: [{ id: '1', label: '<b>Historical</b>' }], procedures: [{ id: '2', label: 'Procedure' }] },
    breakdown: [{ document: '<script>', code: 'A', procedure: 'Name', orders: 21, lines: 25, amount: '120.00' }],
    details: [{ order_id: '001', emitted_at: '2025-01-31', citizens: ['Citizen', 'Business'], documents: ['TUPA'], codes: ['A'], procedures: ['Name'], state_label: 'Girado', amount: '120.00' }]
  };
}
const flush = () => new Promise(resolve => setImmediate(resolve));
test('S1-S7: mounted UI renders server totals, paginates and exports applied filters safely', async t => {
  const calls = [];
  const oldFetch = globalThis.fetch;
  t.after(() => { globalThis.fetch = oldFetch; });
  globalThis.fetch = async (url, options) => {
    calls.push({ url, options });
    const page = Number(new URL(url, 'https://example.invalid').searchParams.get('page'));
    return { ok: true, json: async () => payload(page) };
  };
  const doc = fixtureDocument();
  const get = id => doc.getElementById(id);
  report.mount(doc);
  await flush();
  assert.equal(calls[0].options.credentials, 'same-origin');
  assert.equal(get('reportResults').hidden, false);
  assert.match(get('reportIdentity').textContent, /<script>emitter<\/script>/);
  assert.deepEqual(['reportOrders', 'reportCancelled', 'reportLines', 'reportAmount'].map(id => get(id).textContent), ['21', '2', '25', 'S/ 120.00']);
  assert.equal(get('reportSummary').innerHTML, undefined);
  assert.equal(get('reportDetails').children[0].children[2].textContent, 'Citizen / Business');
  assert.equal(get('reportDocument').children[1].textContent, '<b>Historical</b>');
  assert.equal(get('reportPrev').disabled, true);
  assert.equal(get('reportNext').disabled, false);
  get('reportNext').fire('click');
  await flush();
  assert.match(calls[1].url, /page=2/);
  assert.equal(get('reportNext').disabled, true);
  assert.equal(get('reportPrev').disabled, false);
  get('reportState').value = 'emitidos';
  get('reportDocument').value = '1';
  get('reportProcedure').value = '2';
  get('reportForm').fire('submit');
  await flush();
  assert.equal(get('reportCsv'), null);
  assert.match(get('reportPrint').href, /op=print.*state=emitidos.*document=1.*procedure=2/);
  assert.doesNotMatch(get('reportPrint').href, /page=/);
  get('reportForm').fire('change', get('reportDocument'));
  assert.equal(get('reportProcedure').value, '');
  assert.equal(get('reportResults').hidden, true);
});
test('S3/S4: changed filters invalidate stale responses and errors hide old exports', async t => {
  const oldFetch = globalThis.fetch;
  t.after(() => { globalThis.fetch = oldFetch; });
  let resolve;
  globalThis.fetch = () => new Promise(done => { resolve = done; });
  const doc = fixtureDocument();
  const get = id => doc.getElementById(id);
  report.mount(doc);
  get('reportForm').fire('change', get('reportEnd'));
  resolve({ ok: true, json: async () => payload() });
  await flush();
  assert.equal(get('reportResults').hidden, true);
  assert.match(get('reportMessage').textContent, /Filtros modificados/);
  globalThis.fetch = async () => ({ ok: false, json: async () => ({ error: 'Sesión no válida.' }) });
  get('reportForm').fire('submit');
  await flush();
  assert.equal(get('reportResults').hidden, true);
  assert.equal(get('reportMessage').textContent, 'Sesión no válida.');
});
test('S10: PDF fetches complete applied filters, disables duplicates, saves only current requests and permits manual retry', async t => {
  const oldFetch = globalThis.fetch, oldPdf = globalThis.ReportPdf;
  t.after(() => { globalThis.fetch = oldFetch; globalThis.ReportPdf = oldPdf; });
  const calls = [], saves = [];
  let resolvePdf, resolveFont;
  globalThis.ReportPdf = { loadFont: () => new Promise(done => { resolveFont = done; }), filename: () => 'safe.pdf', build: data => ({ save: name => saves.push([data, name]) }) };
  globalThis.fetch = async (url, options) => {
    calls.push({ url, options });
    if (url.includes('op=pdf')) return new Promise(done => { resolvePdf = done; });
    return { ok: true, json: async () => payload() };
  };
  const doc = fixtureDocument(), get = id => doc.getElementById(id);
  report.mount(doc);
  assert.equal(get('reportPdf').disabled, true);
  await flush();
  assert.equal(get('reportPdf').disabled, false);
  get('reportStart').value = '2099-12-31'; // Unapplied edits must not become export filters.
  get('reportPdf').fire('click'); get('reportPdf').fire('click');
  assert.equal(get('reportPdf').disabled, true);
  assert.match(get('reportPdf').textContent, /Generando/);
  assert.equal(calls.filter(c => c.url.includes('op=pdf')).length, 1);
  assert.doesNotMatch(calls.at(-1).url, /2099|page=|worker=|full=/);
  assert.deepEqual(calls.at(-1).options, { credentials: 'same-origin', cache: 'no-store' });
  resolvePdf({ ok: true, json: async () => payload() }); await flush();
  get('reportForm').fire('change'); resolveFont('font'); await flush();
  assert.equal(saves.length, 0);
  get('reportForm').fire('submit'); await flush();
  get('reportPdf').fire('click'); resolvePdf({ ok: false, status: 401, json: async () => ({ error: 'Sesión no válida.' }) }); await flush();
  assert.equal(saves.length, 0); assert.match(get('reportMessage').textContent, /Sesión/);
  assert.equal(get('reportPdf').disabled, true);
  get('reportForm').fire('submit'); await flush();
  globalThis.ReportPdf = undefined;
  get('reportPdf').fire('click'); await flush();
  assert.match(get('reportMessage').textContent, /PDF/);
  assert.equal(get('reportPdf').disabled, false);
  globalThis.ReportPdf = { loadFont: async () => 'font', filename: () => 'safe.pdf', build: () => { throw new Error('Fallo PDF'); } };
  get('reportPdf').fire('click'); resolvePdf({ ok: true, json: async () => payload() }); await flush();
  assert.match(get('reportMessage').textContent, /Fallo PDF/);
  assert.equal(get('reportPdf').disabled, false);
  globalThis.ReportPdf.build = data => ({ save: name => saves.push([data, name]) });
  get('reportPdf').fire('click'); resolvePdf({ ok: true, json: async () => payload() }); await flush();
  assert.equal(saves.length, 1); assert.equal(saves[0][1], 'safe.pdf');
});

test('S3: summary titles, metric labels and warning notes adapt dynamically to the selected state filter', async t => {
  const oldFetch = globalThis.fetch;
  t.after(() => { globalThis.fetch = oldFetch; });
  globalThis.fetch = async () => ({ ok: true, json: async () => payload(1) });

  const doc = fixtureDocument();
  const get = id => doc.getElementById(id);
  report.mount(doc);
  await flush();

  // Test 1: Solo Pagados ('4')
  get('reportState').value = '4';
  get('reportForm').fire('submit');
  await flush();
  assert.equal(get('reportSummaryTitle').textContent, 'Resumen de órdenes pagadas');
  assert.equal(get('reportOrdersLabel').textContent, 'Total de órdenes pagadas');
  assert.equal(get('reportLinesLabel').textContent, 'Total de tasas pagadas');
  assert.equal(get('reportAmountLabel').textContent, 'Importe pagado');
  assert.equal(get('reportWarningStrong').textContent, 'Importe efectivamente recaudado en caja.');

  // Test 2: Solo Girados ('1')
  get('reportState').value = '1';
  get('reportForm').fire('submit');
  await flush();
  assert.equal(get('reportSummaryTitle').textContent, 'Resumen de órdenes pendientes de pago');
  assert.equal(get('reportOrdersLabel').textContent, 'Total de órdenes giradas');
  assert.equal(get('reportAmountLabel').textContent, 'Importe pendiente');
  assert.match(get('reportWarningStrong').textContent, /no equivale a dinero recaudado/);

  // Test 3: Solo Anulados ('anulados')
  get('reportState').value = 'anulados';
  get('reportForm').fire('submit');
  await flush();
  assert.equal(get('reportSummaryTitle').textContent, 'Resumen de órdenes anuladas y extornadas');
  assert.equal(get('reportAmountLabel').textContent, 'Importe anulado / extornado');

  // Test 4: Todos los estados ('todos')
  get('reportState').value = 'todos';
  get('reportForm').fire('submit');
  await flush();
  assert.equal(get('reportSummaryTitle').textContent, 'Resumen general de órdenes');
  assert.equal(get('reportOrdersLabel').textContent, 'Total de órdenes');
  assert.equal(get('reportAmountLabel').textContent, 'Importe total');

  // Test 5: getSummaryCopy standalone contracts
  const pagados = report.getSummaryCopy('4');
  assert.equal(pagados.title, 'Resumen de órdenes pagadas');
  assert.equal(pagados.amountLabel, 'Importe pagado');
  const emitidos = report.getSummaryCopy('emitidos');
  assert.equal(emitidos.title, 'Resumen de emisiones');
  assert.equal(emitidos.amountLabel, 'Importe emitido');
});
