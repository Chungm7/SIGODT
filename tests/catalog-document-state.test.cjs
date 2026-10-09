const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
function harness(file) {
  const alerts = [], elements = new Map();
  let request, html = '', reloads = 0, hidden = 0, state = 1;
  const $ = (selector) => ({
    val(value) { return arguments.length ? this : selector === '#area_id' || selector === '#tupa_id' ? '7' : ''; },
    on() {}, ready() {}, attr: () => String(state), trigger() { return this; },
    html(value) { html = value; },
    modal() { hidden++; },
    DataTable: () => ({ajax: {reload() { reloads++; }}}),
    0: {reset() {}},
  });
  $.ajax = options => { request = options; };
  $.post = (url, data, callback) => {
    if (typeof data === 'function') { callback = data; }
    request = {url, success: callback};
    return {fail(fn) { request.error = fn; }};
  };
  const context = vm.createContext({$, document: {}, FormData: class {append() {}}, Swal: {fire(options) {
    alerts.push(options); return {then(callback) { callback({value: true}); }};
  }}});
  vm.runInContext(fs.readFileSync(`${__dirname}/../view/${file}/main.js`, 'utf8'), context);
  return {context, alerts, request: () => request, html: () => html, reloads: () => reloads, hidden: () => hidden, setState: value => { state = value; }};
}
test('maintenance renders distinct lifecycle labels independently of block', () => {
  const h = harness('mnt_tupa'); h.context.cargarListaTupa();
  h.request().success(JSON.stringify({aaData: [1,2,3].map(est => ({est, tupa_id: est, tupa_block: '0', tupa_nom: 'Test', tupa_año: 2026}))}));
  for (const label of ['Inactivo', 'Vigente', 'En Proceso', 'En Edición']) assert.ok(h.html().includes(label), label);
});
test('duplication reports success only for a positive ID and describes active-only copy', () => {
  for (const response of [{success: true, tupa_id: 9}, {success: false}, {success: true, tupa_id: 0}, {success: true, tupa_id: 'bad'}, {}]) {
    const h = harness('mnt_tupa'); h.context.duplicar(7);
    assert.match(h.alerts[0].text, /activos/);
    h.request().success(response);
    assert.equal(h.alerts.at(-1).icon, response.tupa_id === 9 ? 'success' : 'error');
  }
  const h = harness('mnt_tupa'); h.context.duplicar(7); h.request().error();
  assert.equal(h.alerts.at(-1).icon, 'error');
});
test('catalog save rejection does not close modal or reload table', () => {
  const h = harness('mnt_procedimiento'); h.context.guardaryeditar({preventDefault() {}});
  h.request().success({success: false, message: 'Documento inactivo'});
  assert.equal(h.alerts.at(-1).icon, 'error'); assert.equal(h.reloads(), 0); assert.equal(h.hidden(), 0);
  h.request().success({success: true});
  assert.equal(h.alerts.at(-1).icon, 'success'); assert.equal(h.reloads(), 1); assert.equal(h.hidden(), 1);
});
test('new catalog procedure modal only opens for vigente or in-progress', () => {
  for (const state of [0,1,2,3,99]) {
    const h = harness('mnt_procedimiento'); h.setState(state); h.context.nuevoproced();
    assert.equal(h.hidden(), state === 2 || state === 3 ? 1 : 0);
    if (state !== 2 && state !== 3) assert.equal(h.alerts.at(-1).icon, 'warning');
  }
});
