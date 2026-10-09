const { test } = require('node:test');
const assert = require('node:assert/strict');
const ui = require('../view/consultar_nombre/consultarnombre.js');

class Element {
  constructor(tag) { this.tagName = tag; this.children = []; this.textContent = ''; this.listeners = {}; }
  appendChild(child) { this.children.push(child); return child; }
  replaceChildren(...children) { this.children = children; }
  addEventListener(name, fn) { this.listeners[name] = fn; }
  setAttribute(key, value) { this[key] = value; }
  remove() { this.removed = true; }
  submit() { this.submitted = true; }
  set innerHTML(value) { throw new Error('Unsafe HTML rendering'); }
}
const doc = { createElement: tag => new Element(tag), body: new Element('body') };
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
    get('entitiesNext').listeners.click();
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
