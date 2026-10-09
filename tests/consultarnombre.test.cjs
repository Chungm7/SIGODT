const { test } = require('node:test');
const assert = require('node:assert/strict');
const ui = require('../view/consultar_nombre/consultarnombre.js');

class Element {
  constructor(tag) { this.tagName = tag; this.children = []; this.textContent = ''; this.value = ''; this.listeners = {}; }
  appendChild(child) { this.children.push(child); return child; }
  replaceChildren(...children) { this.children = children; }
  addEventListener(name, fn) { this.listeners[name] = fn; }
  setAttribute(key, value) { this[key] = value; }
  remove() { this.removed = true; }
  submit() { this.submitted = true; }
  set innerHTML(value) { throw new Error('Unsafe HTML rendering'); }
}
const doc = { createElement: tag => new Element(tag), body: new Element('body') };
const flush = async () => { for (let i = 0; i < 10; i++) await Promise.resolve(); };
function harness(t, abort = true) {
  const elements = new Map();
  const document = { ...doc, getElementById(id) {
    if (!elements.has(id)) elements.set(id, new Element('div'));
    return elements.get(id);
  } };
  const get = id => document.getElementById(id);
  let now = 0, serial = 0;
  const timers = new Map(), requests = [];
  const saved = { fetch: global.fetch, setTimeout: global.setTimeout, clearTimeout: global.clearTimeout, AbortController: global.AbortController };
  global.setTimeout = (fn, delay) => { const id = ++serial; timers.set(id, { fn, at: now + delay }); return id; };
  global.clearTimeout = id => timers.delete(id);
  global.fetch = (url, options) => new Promise((resolve, reject) => requests.push({ url, options, resolve, reject }));
  if (!abort) global.AbortController = undefined;
  t.after(() => Object.assign(global, saved));
  ui.mount(document);
  return { get, requests,
    tick(ms) { now += ms; for (const [id, timer] of timers) if (timer.at <= now) { timers.delete(id); timer.fn(); } },
    input(value) { get('nombreSearch').value = value; get('nombreSearch').listeners.input({}); },
    submit() { get('nombreForm').listeners.submit({ preventDefault() {} }); },
    respond(index, data = [], page = 1, total = 0) { requests[index].resolve({ ok: true, json: async () => ({ data, page, total, limit: 10 }) }); }
  };
}
test('component cells are empty but missing receipt retains scalar dash', () => {
  const rows = new Element('tbody');
  ui.renderEntities(doc, rows, [{ entity_type: 'ciudadano', entity_key: '1' }], () => {});
  assert.equal(rows.children[0].children[4].textContent, '');
  ui.renderOrders(doc, rows, [{ ogciud_id: '000123-2026', recibo_nro: null, importe: 1, orden_est: 4 }]);
  assert.equal(rows.children[0].children[3].textContent, '—');
  for (const index of [5, 6]) assert.equal(rows.children[0].children[index].textContent, '');
});
test('typing burst debounces exactly 400ms and counts trimmed Unicode characters', t => {
  const h = harness(t);
  h.input('  😀😀  '); h.tick(400); assert.equal(h.requests.length, 0);
  h.input('Alp'); h.tick(250); h.input('Alpha'); h.tick(399);
  assert.equal(h.requests.length, 0);
  h.tick(1); assert.equal(h.requests.length, 1);
  assert.equal(new URLSearchParams(h.requests[0].options.body).get('search'), 'Alpha');
  assert.notEqual(h.get('nombreSearch').disabled, true);
  h.input('  😀😀😀  '); h.tick(400);
  assert.equal(h.requests.length, 2);
  assert.equal(new URLSearchParams(h.requests[1].options.body).get('search'), '😀😀😀');
});
test('manual short search is immediate and cancels pending auto request', t => {
  const h = harness(t);
  h.input('Alpha'); h.submit(); assert.equal(h.requests.length, 1);
  h.tick(400); assert.equal(h.requests.length, 1);
  h.input(' A '); h.submit(); assert.equal(h.requests.length, 2);
  assert.equal(new URLSearchParams(h.requests[1].options.body).get('search'), 'A');
  h.tick(400); assert.equal(h.requests.length, 2);
});
for (const abort of [true, false]) test('edit immediately invalidates pending responses, abort fallback=' + !abort, async t => {
  const h = harness(t, abort);
  h.input('Alpha'); h.submit();
  h.input('Bravo');
  if (abort) assert.equal(h.requests[0].options.signal.aborted, true);
  h.respond(0, [{ nombre: 'Obsolete' }], 1, 1); await flush();
  assert.doesNotMatch(h.get('nombreMessage').textContent, /1 entidades/);
  assert.equal(h.get('entitiesPage').textContent, '');
  assert.equal(h.get('nombreResults')['aria-busy'], 'false');
  h.tick(400); assert.equal(h.requests.length, 2);
  h.input(' '); h.requests[1].reject(new Error('AbortError')); await flush();
  h.tick(400); assert.equal(h.requests.length, 2);
  assert.equal(h.get('nombreHistory').hidden, true);
  assert.equal(h.get('historyIdentity').textContent, '');
  assert.equal(h.get('entitiesNext').disabled, true);
  assert.doesNotMatch(h.get('nombreMessage').textContent, /AbortError/);
});
test('shortening and blank submit clear results and never auto request', async t => {
  const h = harness(t);
  h.input('Alpha'); h.submit();
  h.respond(0, [{ entity_type: 'ciudadano', entity_key: '1', nombre: 'Alpha' }], 1, 1); await flush();
  h.input('Al'); h.tick(400);
  assert.equal(h.requests.length, 1);
  assert.equal(h.get('entitiesPage').textContent, '');
  assert.match(h.get('nombreMessage').textContent, /3 caracteres/);
  h.input('   '); h.submit(); h.tick(400);
  assert.equal(h.requests.length, 1);
  assert.match(h.get('nombreMessage').textContent, /nombre válido/);
});
test('IME composition never auto submits partial text', t => {
  const h = harness(t);
  h.get('nombreSearch').listeners.compositionstart();
  h.input('漢字名'); h.tick(400); h.submit(); assert.equal(h.requests.length, 0);
  h.get('nombreSearch').listeners.compositionend();
  h.tick(399); assert.equal(h.requests.length, 0);
  h.tick(1); assert.equal(h.requests.length, 1);
});
test('editing history clears selection, stale history and metadata; changed term resets page', async t => {
  const h = harness(t);
  h.input('Alpha'); h.submit();
  h.respond(0, [{ entity_type: 'ciudadano', entity_key: '1', nombre: 'Alpha' }], 2, 11); await flush();
  h.get('nombreEntities').children[0].children[4].children[0].listeners.click();
  h.input('Bravo');
  if (h.requests[1].options.signal) assert.equal(h.requests[1].options.signal.aborted, true);
  h.respond(1, [{ ogciud_id: '000123-2026', importe: 1 }], 1, 1); await flush();
  h.get('historyBack').listeners.click();
  assert.equal(h.get('historyPage').textContent, '');
  assert.equal(h.get('entitiesPage').textContent, '');
  h.tick(400);
  assert.equal(new URLSearchParams(h.requests[2].options.body).get('page'), '1');
  assert.equal(new URLSearchParams(h.requests[2].options.body).get('search'), 'Bravo');
});
test('names are literal text, typed identities and counts remain distinct', () => {
  const rows = new Element('tbody');
  const selected = [];
  ui.renderEntities(doc, rows, [
    { entity_type: 'ciudadano', entity_key: '1', nombre: '<img onerror=alert(1)>', documento: '001', ordenes: 3 },
    { entity_type: 'ciudadano', entity_key: '2', nombre: '<img onerror=alert(1)>', documento: '002', ordenes: 1 }
  ], row => selected.push(row.entity_key));
  assert.equal(rows.children.length, 2);
  assert.equal(rows.children[0].children[1].textContent, '<img onerror=alert(1)>');
  rows.children[0].children[4].children[0].listeners.click();
  assert.deepEqual(selected, ['1']);
});
test('every known order state plus explicit unknown', () => {
  assert.deepEqual(Array.from({ length: 7 }, (_, i) => ui.state(i).label),
    ['Anulado', 'Pendiente', 'Girado', 'Improcedente', 'Pagado', 'Usado', 'Extornado']);
  assert.equal(ui.state(91).label, 'Desconocido (91)');
  assert.equal(ui.state(null).label, 'Desconocido (—)');
});
test('late responses cannot replace current results or cleared history', async () => {
  const gate = ui.requestGate();
  let resolveOld;
  const old = gate.run(() => new Promise(resolve => { resolveOld = resolve; }));
  const current = gate.run(() => Promise.resolve('new'));
  assert.deepEqual(await current, { current: true, data: 'new' });
  resolveOld('old');
  assert.equal((await old).current, false);
  const pending = gate.run(() => Promise.resolve('history'));
  gate.invalidate();
  assert.equal((await pending).current, false);
});
test('printing uses existing POST new-tab contract without HTML interpolation', () => {
  ui.printOrder(doc, '123');
  const form = doc.body.children.at(-1);
  assert.equal(form.method, 'post');
  assert.equal(form.target, '_blank');
  assert.equal(form.action, '../../controller/rc.php?op=imprimirxid');
  assert.equal(form.children[0].name, 'ogciud_id');
  assert.equal(form.children[0].value, '123');
  assert.ok(form.submitted && form.removed);
  ui.printOrder(doc, '<script>');
  assert.equal(doc.body.children.at(-1), form, 'Malformed order IDs do not print');
});
test('history print button submits number-year ID unchanged with leading zeros', () => {
  const document = { createElement: tag => new Element(tag), body: new Element('body') };
  const rows = new Element('tbody');
  ui.renderOrders(document, rows, [{ ogciud_id: '000123-2026', importe: '10', orden_est: 1 }]);
  rows.children[0].children[6].children[0].listeners.click();
  assert.equal(document.body.children.length, 1);
  const form = document.body.children[0];
  assert.equal(form.method, 'post');
  assert.equal(form.target, '_blank');
  assert.equal(form.action, '../../controller/rc.php?op=imprimirxid');
  assert.equal(form.children.length, 1);
  assert.equal(form.children[0].type, 'hidden');
  assert.equal(form.children[0].name, 'ogciud_id');
  assert.equal(form.children[0].value, '000123-2026');
  assert.ok(form.submitted && form.removed);
});
test('printing accepts longer generated sequences and existing numeric compatibility', () => {
  for (const id of ['1000000-2026', '123', 123]) {
    const document = { createElement: tag => new Element(tag), body: new Element('body') };
    ui.printOrder(document, id);
    assert.equal(document.body.children.length, 1);
    assert.equal(document.body.children[0].children[0].value, String(id));
    assert.ok(document.body.children[0].submitted);
  }
});
test('invalid print IDs never construct or submit a form, including coercible objects', () => {
  const ids = [null, undefined, {}, { toString: () => '000123-2026' },
    { toString: () => '123' }, ['123'], true, 0, -1, 1.5, NaN, Infinity,
    Number.MAX_SAFE_INTEGER + 1, '', '0', '0123', '000000-2026', '123-2026',
    '000123-26', '000123-20260', '000123/2026', '000123-2026-extra',
    ' 000123-2026', '000123-2026 ', '000123-2026\n', '<script>',
    '000123-2026"><img src=x>', '１２３４５６-2026'];
  for (const id of ids) {
    const document = { createElement() { assert.fail('Invalid IDs must not create elements'); }, body: new Element('body') };
    ui.printOrder(document, id);
    assert.equal(document.body.children.length, 0);
  }
});
test('history shows one supplied order row, summed decimal amount and safe unknown state', () => {
  const rows = new Element('tbody');
  ui.renderOrders(doc, rows, [{ ogciud_id: '5', fecha: '2020-01-01', hora: '12:00:00', recibo_nro: '<img>', importe: '32.50', orden_est: '<script>' }]);
  assert.equal(rows.children.length, 1);
  assert.equal(rows.children[0].children[3].textContent, '<img>');
  assert.equal(rows.children[0].children[4].textContent, 'S/ 32.50');
  assert.equal(rows.children[0].children[5].children[0].textContent, 'Desconocido (<script>)');
  ui.renderOrders(doc, rows, []);
  assert.equal(rows.children[0].children[0].colSpan, 7);
});
test('mounted search paginates, keeps results on return and ignores late history', async () => {
  const elements = new Map();
  const document = { ...doc, getElementById(id) {
    if (!elements.has(id)) elements.set(id, new Element('div'));
    return elements.get(id);
  } };
  const get = id => document.getElementById(id);
  const requests = [];
  const previousFetch = global.fetch;
  global.fetch = (url, options) => new Promise(resolve => requests.push({ url, options, resolve }));
  const flush = () => new Promise(resolve => setImmediate(resolve));
  const respond = (index, data, status = 200) => requests[index].resolve({ ok: status === 200, status, json: async () => data });
  const row = { entity_type: 'empresa', entity_key: 'ruc:00123', nombre: 'Alias', documento: '00123', ordenes: 12 };
  try {
    ui.mount(document);
    get('nombreSearch').value = 'Alias';
    get('nombreForm').listeners.submit({ preventDefault() {} });
    assert.equal(new URLSearchParams(requests[0].options.body).get('search'), 'Alias');
    respond(0, { data: [row], total: 11, page: 1, limit: 10 });
    await flush();
    assert.equal(get('entitiesPrev').disabled, true);
    assert.equal(get('entitiesNext').disabled, false);
    get('nombreSearch').value = 'Uncommitted';
    get('entitiesNext').listeners.click();
    assert.equal(new URLSearchParams(requests[1].options.body).get('search'), 'Alias');
    assert.equal(new URLSearchParams(requests[1].options.body).get('page'), '2');
    respond(1, { data: [row], total: 11, page: 2, limit: 10 });
    await flush();
    get('nombreEntities').children[0].children[4].children[0].listeners.click();
    assert.equal(new URLSearchParams(requests[2].options.body).get('entity_key'), 'ruc:00123');
    respond(2, { data: [{ ogciud_id: '9', importe: '10', orden_est: 0 }], total: 12, page: 1, limit: 10 });
    await flush();
    assert.equal(get('historyNext').disabled, false);
    assert.equal(get('nombreOrders').children[0].children[5].children[0].textContent, 'Anulado');
    get('historyNext').listeners.click();
    get('historyBack').listeners.click();
    respond(3, { data: [], total: 12, page: 2, limit: 10 });
    await flush();
    assert.equal(get('nombreHistory').hidden, true);
    assert.equal(get('nombreResults').hidden, false);
    assert.match(get('entitiesPage').textContent, /Página 2/);
    assert.equal(get('nombreEntities').children[0].children[1].textContent, 'Alias');
    get('nombreSearch').value = 'Otra';
    get('nombreForm').listeners.submit({ preventDefault() {} });
    respond(4, {}, 401);
    await flush();
    assert.match(get('nombreMessage').textContent, /Inicie sesión/);
    assert.equal(get('entitiesNext').disabled, true);
  } finally { global.fetch = previousFetch; }
});
