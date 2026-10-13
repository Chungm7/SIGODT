const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

function harness() {
  const elements = new Map(), requests = [], tables = [], alerts = [], fields = [];
  const document = {}; let ready;
  function $(selector) {
    if (selector === document) return {ready(fn) { ready = fn; }};
    if (!elements.has(selector)) elements.set(selector, {value: '', markup: '', disabled: false, state: '3', modal: ''});
    const s = elements.get(selector);
    const api = {
      val(v) { if (!arguments.length) return s.value; s.value = String(v);
        if (selector === '#area_id' && s.markup.includes('value=') &&
            !new RegExp(`value=["']${s.value}["']`).test(s.markup)) s.value = '';
        return api; },
      html(v) { if (!arguments.length) return s.markup; s.markup = v; s.value = ''; return api; },
      prop(k, v) { if (arguments.length === 1) return s[k]; s[k] = v; return api; },
      attr() { return s.state; }, trigger() { return api; }, select2() { return api; },
      change(fn) { s.change = fn; return api; }, on() { return api; },
      modal(action) { s.modal = action; return api; },
      DataTable(options) { if (options) tables.push(options); return {destroy() {}, ajax: {reload() {}}}; },
      0: {reset() { $('#proced_area').val(''); }}
    }; return api;
  }
  $.fn = {DataTable: {isDataTable() { return false; }}};
  $.post = (url, data, success) => {
    if (typeof data === 'function') { success = data; data = undefined; }
    const r = {url, data, success}; requests.push(r);
    return {fail(fn) { r.error = fn; return this; }};
  };
  $.ajax = options => requests.push(options);
  const context = vm.createContext({$, document, FormData: class {append(k,v) { fields.push([k,v]); }}, Swal: {fire(o) { alerts.push(o); return {then(fn) { fn({value: true}); }}; }}});
  vm.runInContext(fs.readFileSync(`${__dirname}/../view/mnt_procedimiento/main.js`, 'utf8'), context);
  ready();
  return {$, context, requests, tables, alerts, fields,
    change(sel, value) { $(sel).val(value); elements.get(sel).change.call(sel); },
    scoped() { return requests.filter(r => r.url.includes('combo_tributos')).at(-1); },
    general() { return requests.filter(r => r.url.endsWith('area.php?op=combo')).at(-1); }};
}

test('list areas depend on selected document and reject stale responses', () => {
  const h = harness();
  assert.equal(h.general(), undefined);
  assert.equal(h.$('#area_id').prop('disabled'), true);
  h.change('#tupa_id', '7'); const old = h.scoped();
  assert.equal(old.data.tupa_id, '7');
  old.success('<option value="4">Eligible area</option>');
  h.change('#area_id', '4');
  h.change('#tupa_id', '9'); h.change('#tupa_id', '7'); const current = h.scoped();
  assert.equal(h.context.area_id, 0);
  assert.equal(h.tables.at(-1).ajax.data.area_id, 0);
  current.success('<option>current</option>'); old.success('<option>stale</option>');
  assert.match(h.$('#area_id').html(), /current/);
  h.change('#tupa_id', ''); current.success('<option>late</option>');
  assert.equal(h.$('#area_id').prop('disabled'), true);
  assert.doesNotMatch(h.$('#area_id').html(), /late/);
});

test('new procedure opens without list area and saves independent active area', () => {
  const h = harness(); h.change('#tupa_id', '7'); h.context.nuevoproced();
  assert.equal(h.$('#modalmantenimiento').prop('modal'), 'show');
  const r = h.general(); assert.ok(r, 'Creation must load all active areas');
  r.success('<option value="6">Area without procedures</option>');
  assert.equal(h.$('#proced_area').prop('disabled'), false);
  h.$('#proced_area').val('6');
  h.context.guardaryeditar({preventDefault() {}});
  assert.deepEqual(h.fields, [['area_id', '6'], ['tupa_id', '7']]);
  assert.equal(h.$('#area_id').val(), '');
});

test('new modal preselects list area but blank creation area prevents submission', () => {
  const h = harness(); h.change('#tupa_id', '7'); h.scoped().success('<option>area</option>');
  h.change('#area_id', '4'); h.context.nuevoproced();
  h.general().success('<option value="4">Area</option>');
  assert.equal(h.$('#proced_area').val(), '4');
  h.$('#proced_area').val(''); const before = h.requests.length;
  h.context.guardaryeditar({preventDefault() {}});
  assert.equal(h.requests.length, before); assert.equal(h.alerts.at(-1).icon, 'warning');
});

test('document changes invalidate open creation area responses and submission', () => {
  const h = harness(); h.change('#tupa_id', '7'); h.context.nuevoproced(); const old = h.general();
  h.change('#tupa_id', '9'); old.success('<option>stale area</option>');
  assert.doesNotMatch(h.$('#proced_area').html(), /stale/);
  h.$('#proced_area').val('6'); const before = h.requests.length;
  h.context.guardaryeditar({preventDefault() {}});
  assert.equal(h.requests.length, before);
});

test('area load failure prevents creation and listing failure leaves empty filter', () => {
  const h = harness(); h.change('#tupa_id', '7'); h.scoped().error();
  assert.equal(h.$('#area_id').prop('disabled'), true);
  h.context.nuevoproced(); h.general().error();
  assert.equal(h.$('#proced_area').prop('disabled'), true);
  const before = h.requests.length; h.context.guardaryeditar({preventDefault() {}});
  assert.equal(h.requests.length, before);
});

test('editing retains original area without loading unrestricted creation options', () => {
  const h = harness(); h.change('#tupa_id', '7'); h.context.editar(12);
  h.requests.at(-1).success(JSON.stringify({proced_id: 12, proced_area: 4, proced_nom: 'Existing', proced_cod: 'P01'}));
  assert.equal(h.$('#proced_area').val(), '4');
  assert.equal(h.$('#proced_area').prop('disabled'), true);
  assert.equal(h.general(), undefined);
});

test('first creation success refreshes scoped areas and selects the new area', () => {
  const h = harness(); h.change('#tupa_id', '7');
  h.scoped().success('<option value="">Seleccione</option>');
  h.context.nuevoproced(); h.general().success('<option value="6">New area</option>');
  h.$('#proced_area').val('6'); h.context.guardaryeditar({preventDefault() {}});
  h.requests.at(-1).success({success: true});
  assert.equal(h.scoped().data.tupa_id, '7');
  h.scoped().success('<option value="6">Now eligible</option>');
  assert.equal(h.$('#area_id').val(), '6');
  assert.equal(h.tables.at(-1).ajax.data.area_id, '6');
  assert.equal(h.$('#modalmantenimiento').prop('modal'), 'hide');
});

test('edit save preserves original area and refreshes that document listing', () => {
  const h = harness(); h.change('#tupa_id', '7'); h.context.editar(12);
  h.requests.at(-1).success(JSON.stringify({proced_id: 12, proced_area: 4}));
  h.context.guardaryeditar({preventDefault() {}});
  assert.deepEqual(h.fields, [['area_id', '4'], ['tupa_id', '7']]);
  h.requests.at(-1).success({success: true});
  h.scoped().success('<option value="4">Original area</option>');
  assert.equal(h.tables.at(-1).ajax.data.area_id, '4');
});

test('deleting last procedure refreshes areas and removes the empty area', () => {
  const h = harness(); h.change('#tupa_id', '7');
  h.scoped().success('<option value="4">Area</option>'); h.change('#area_id', '4');
  const previous = h.scoped(); h.context.eliminar(12);
  const deletion = h.requests.at(-1);
  assert.match(deletion.url, /procedimiento.php\?op=eliminar/);
  assert.equal(deletion.data.proced_id, 12);
  deletion.success('');
  assert.notEqual(h.scoped(), previous, 'Deletion must refresh area eligibility');
  h.scoped().success('<option value="">Seleccione</option>');
  assert.equal(h.$('#area_id').val(), '');
  assert.equal(h.context.area_id, 0);
  assert.equal(h.tables.at(-1).ajax.data.area_id, 0);
});
