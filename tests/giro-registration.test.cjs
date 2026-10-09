const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const files = ['view/giros_ciudadano/usudetalleciudadano.js', 'view/admin_giros_ciudadano/admindetalleciudadano.js'];
function fn(source, name) {
  const match = source.match(new RegExp(`function ${name}\\([^]*?\\n\\}`));
  assert.ok(match, name); return match[0];
}
function harness() {
  const elements = new Map(), requests = [], alerts = [];
  function $(selector) {
    selector = String(selector);
    if (!elements.has(selector)) elements.set(selector, { value: '', markup: '', disabled: false, modalAction: '' });
    const state = elements.get(selector);
    const api = {
      0: {}, val(v) { if (v === undefined) return state.value; state.value = v; return api; },
      html(v) { if (v === undefined) return state.markup; state.markup = v; return api; },
      prop(k, v) { if (v === undefined) return state[k]; state[k] = v; return api; },
      modal(v) { state.modalAction = v; return api; },
      empty() { state.markup = ''; return api; }, change() { return api; }, trigger() { return api; },
      on(event, handler) { state.handler = handler; return api; },
      attr() { return api; }, removeAttr() { return api; }, text() { return api; },
      removeClass() { return api; }, addClass() { return api; }, focus() { return api; }, select() { return api; },
      show() { return api; }, hide() { return api; },
      DataTable() { return { ajax: { reload() { state.reloaded = true; } } }; }
    }; return api;
  }
  $.ajax = options => requests.push(options);
  const context = vm.createContext({ $, console, Swal: { fire: o => alerts.push(o) },
    document: { getElementById: id => ({ checked: false, value: '7' }) },
    FormData: class { append() {} set() {} }, registrationPickerVersion: 0, registrationModalProcedure: null,
    setTimeout() {}, mostrarDetalleProcedimiento(data) { $('#cardDetalleProcedimiento').html(data.label); } });
  return { $, elements, requests, alerts, context };
}
for (const file of files) {
  test(`${file}: stale picker responses cannot restore old options`, () => {
    const h = harness(); const source = fs.readFileSync(file, 'utf8');
    vm.runInContext(fn(source, 'combo_proced'), h.context);
    h.$('#area_id').val('4'); h.$('#tupa_id').val('2'); h.$('#proced_id').val('8');
    vm.runInContext("combo_proced('4', '2')", h.context);
    assert.equal(h.$('#proced_id').val(), '', 'clear old selection immediately');
    h.$('#area_id').val('6'); vm.runInContext("combo_proced('6', '2')", h.context);
    h.requests[1].success('<option>new</option>'); h.requests[0].success('<option>old</option>');
    assert.equal(h.$('#proced_id').html(), '<option>new</option>');
    assert.match(h.requests[0].url, /combo_tupa_tusne|registration_combo/);
    h.$('#area_id').val(''); vm.runInContext("combo_proced('', '2')", h.context);
    assert.equal(h.requests.length, 2, 'empty area must not request options');
    assert.equal(h.$('#proced_id').val(), '');
  });
}
test('Admin registration rejection preserves modal and does not reload history', () => {
  const h = harness(); const source = fs.readFileSync(files[1], 'utf8');
  h.$('#ciud_id').val('7'); h.$('#empr_id').val('');
  vm.runInContext(fn(source, 'detalleEditar'), h.context);
  h.context.e = { preventDefault() {} };
  vm.runInContext('detalleEditar(e)', h.context);
  assert.equal(h.requests[0].dataType, 'json');
  h.requests[0].success({ success: false, message: 'No autorizado' });
  assert.notEqual(h.elements.get('#modalmantenimiento')?.modalAction, 'hide');
  assert.notEqual(h.elements.get('#detalle_data')?.reloaded, true);
  assert.equal(h.alerts.at(-1).icon, 'error');
  assert.equal(h.$('#btnguardar').prop('disabled'), false);
});
test('Citizen preview discards stale responses and clears empty selection', () => {
  const h = harness(); const source = fs.readFileSync(files[0], 'utf8');
  const handlerSource = source.slice(source.indexOf("$('#proced_id').on('change'"), source.indexOf('function mostrarDetalleProcedimiento'));
  vm.runInContext(handlerSource, h.context);
  const handler = h.elements.get('#proced_id').handler;
  h.$('#area_id').val('4'); h.$('#proced_id').val('8'); handler.call('#proced_id');
  h.$('#proced_id').val('9'); handler.call('#proced_id');
  h.requests[1].success({ label: 'current' }); h.requests[0].success({ label: 'stale' });
  assert.equal(h.$('#cardDetalleProcedimiento').html(), 'current');
  h.$('#proced_id').val(''); handler.call('#proced_id');
  h.requests[1].success({ label: 'late' });
  assert.equal(h.$('#cardDetalleProcedimiento').html(), '');
});
test('Citizen modal only opens after a current authorized lookup', () => {
  const h = harness(); const source = fs.readFileSync(files[0], 'utf8');
  vm.runInContext(fn(source, 'nuevo'), h.context);
  h.$('#proced_id').val('8'); h.$('#area_id').val('4');
  vm.runInContext('nuevo()', h.context);
  assert.notEqual(h.elements.get('#modalmantenimiento')?.modalAction, 'show');
  h.context.registrationPickerVersion++;
  h.requests[0].success([{ proced_id: 8 }]);
  assert.notEqual(h.elements.get('#modalmantenimiento')?.modalAction, 'show');
  vm.runInContext('nuevo()', h.context); h.requests[1].success([]);
  assert.notEqual(h.elements.get('#modalmantenimiento')?.modalAction, 'show');
  assert.notEqual(h.$('#btnguardar').prop('disabled'), true, 'Rejected lookup cannot disable later historical editing');
  vm.runInContext('nuevo()', h.context);
  h.requests[2].success([{ proced_id: 8, proced_administradotipo: 'C' }]);
  assert.equal(h.elements.get('#modalmantenimiento').modalAction, 'show');
  assert.equal(h.$('#btnguardar').prop('disabled'), false);
});
test('Citizen preview and modal use guarded registration endpoints', () => {
  const source = fs.readFileSync(files[0], 'utf8');
  assert.match(source, /op=registration_detail/);
  assert.match(fn(source, 'nuevo'), /op=registration_show/);
  assert.match(fn(source, 'nuevo'), /registrationPickerVersion/);
});
