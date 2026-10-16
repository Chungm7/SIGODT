const { test } = require('node:test');
const assert = require('node:assert/strict');
const report = require('../view/reporte_usuario/reporteusuario.js');

test('S4: local current-month dates do not drift through UTC', () => {
  assert.deepEqual(report.monthRange(new Date(2025, 0, 31, 23, 59)), { start: '2025-01-01', end: '2025-01-31' });
  assert.deepEqual(report.monthRange(new Date(2024, 1, 15)), { start: '2024-02-01', end: '2024-02-29' });
});
test('S2/S6/S7: export reuses filters, never page or emitter, accurate format', () => {
  const url = report.reportUrl({ start: '2025-01-01', end: '2025-01-31', document: '1', procedure: '2', page: 9, emitter: 100 }, 'csv');
  const query = new URL(url, 'https://example.invalid').searchParams;
  assert.equal(query.get('op'), 'csv');
  assert.equal(query.get('document'), '1');
  assert.equal(query.get('procedure'), '2');
  assert.equal(query.has('page'), false);
  assert.equal(query.has('emitter'), false);
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
  assert.match(get('reportSummary').textContent, /21.*2.*25.*120.00/);
  assert.equal(get('reportDetails').children[0].children[2].textContent, 'Citizen / Business');
  assert.equal(get('reportDocument').children[1].textContent, '<b>Historical</b>');
  assert.equal(get('reportPrev').disabled, true);
  assert.equal(get('reportNext').disabled, false);
  get('reportNext').fire('click');
  await flush();
  assert.match(calls[1].url, /page=2/);
  assert.equal(get('reportNext').disabled, true);
  assert.equal(get('reportPrev').disabled, false);
  get('reportDocument').value = '1';
  get('reportProcedure').value = '2';
  get('reportForm').fire('submit');
  await flush();
  assert.match(get('reportCsv').href, /op=csv.*document=1.*procedure=2/);
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
