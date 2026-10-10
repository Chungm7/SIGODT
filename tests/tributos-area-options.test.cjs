const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

function harness() {
  const elements = new Map(), requests = [], tables = [];
  let ready;
  const document = {};
  function $(selector) {
    if (selector === document) return {ready(callback) { ready = callback; }};
    selector = String(selector);
    if (!elements.has(selector)) elements.set(selector, {value: '', markup: '', disabled: false});
    const state = elements.get(selector);
    const api = {
      val(value) { if (!arguments.length) return state.value; state.value = value; return api; },
      html(value) { if (!arguments.length) return state.markup; state.markup = value; state.value = ''; return api; },
      prop(key, value) { if (arguments.length === 1) return state[key]; state[key] = value; return api; },
      change(callback) { state.change = callback; return api; },
      trigger() { return api; }, select2() { return api; }, on() { return api; },
      DataTable(options) { if (options) tables.push(options); return {destroy() {}}; }
    };
    return api;
  }
  $.fn = {DataTable: {isDataTable() { return false; }}};
  $.ajax = options => { requests.push(options); };
  $.post = (url, data, success) => {
    if (typeof data === 'function') { success = data; data = undefined; }
    const request = {url, data, success}; requests.push(request);
    return {fail(callback) { request.error = callback; return this; }};
  };
  const context = vm.createContext({$, document, console: {error() {}}});
  vm.runInContext(fs.readFileSync(`${__dirname}/../view/mnt_tributos/main.js`, 'utf8'), context);
  ready();
  return {
    $, context, tables,
    requests: op => requests.filter(request => request.url.includes(`?op=${op}`)),
    change(selector, value) { $(selector).val(value); elements.get(selector).change.call(selector); }
  };
}
function selectDocument(h, id = '7') { h.change('#tupa_id', id); return h.requests('combo_tributos').at(-1); }

test('initial and blank document keep child selectors empty without an area request', () => {
  const h = harness();
  assert.equal(h.requests('combo_tributos').length, 0);
  assert.equal(h.$('#area_id').prop('disabled'), true);
  assert.equal(h.$('#proced_id').prop('disabled'), true);
  assert.equal(h.requests('combo').some(request => request.url.includes('area.php')), false);
  selectDocument(h, '');
  assert.equal(h.requests('combo_tributos').length, 0);
});

test('document change scopes areas and resets child values and table filters immediately', () => {
  const h = harness();
  h.$('#area_id').val('4'); h.$('#proced_id').val('8');
  h.context.area_id = '4'; h.context.proced_id = '8';
  const request = selectDocument(h);
  assert.equal(request.data.tupa_id, '7');
  assert.equal(h.$('#area_id').val(), ''); assert.equal(h.$('#proced_id').val(), '');
  assert.equal(h.context.area_id, 0); assert.equal(h.context.proced_id, 0);
  assert.deepEqual({...h.tables.at(-1).ajax.data}, {tupa_id: '7', area_id: 0, proced_id: 0});
  request.success('<option value="4">No-tax area</option>');
  assert.match(h.$('#area_id').html(), /No-tax area/);
  assert.equal(h.$('#area_id').prop('disabled'), false);
});

test('superseded area responses cannot restore options even across A to B to A', () => {
  const h = harness(); const old = selectDocument(h, '7');
  selectDocument(h, '9'); const current = selectDocument(h, '7');
  current.success('<option>current</option>'); old.success('<option>stale</option>');
  old.error?.({}, 'error', 'old failure');
  assert.match(h.$('#area_id').html(), /current/);
  assert.equal(h.$('#area_id').prop('disabled'), false);
  selectDocument(h, ''); current.success('<option>late</option>');
  assert.doesNotMatch(h.$('#area_id').html(), /late/);
  assert.equal(h.$('#area_id').prop('disabled'), true);
});

test('area change resets selected procedure and rejects stale procedure responses', () => {
  const h = harness(); selectDocument(h).success('<option>areas</option>');
  h.$('#proced_id').val('8'); h.context.proced_id = '8';
  h.change('#area_id', '4');
  const old = h.requests('combo').find(request => request.url.includes('procedimiento.php'));
  assert.equal(old.data.area_id, '4'); assert.equal(old.data.tupa_id, '7');
  assert.equal(h.$('#proced_id').val(), ''); assert.equal(h.context.proced_id, 0);
  assert.equal(h.$('#proced_id').prop('disabled'), true);
  assert.equal(h.tables.at(-1).ajax.data.proced_id, 0);
  h.change('#area_id', '6');
  const current = h.requests('combo').at(-1);
  current.success('<option>current procedure</option>'); old.success('<option>stale procedure</option>');
  assert.match(h.$('#proced_id').html(), /current procedure/);
  selectDocument(h, '9'); current.success('<option>late procedure</option>');
  assert.doesNotMatch(h.$('#proced_id').html(), /late/);
});

test('blank area invalidates pending procedure response and sends no new request', () => {
  const h = harness(); selectDocument(h).success('<option>areas</option>');
  h.change('#area_id', '4'); const pending = h.requests('combo').at(-1);
  const count = h.requests('combo').length;
  h.change('#area_id', ''); pending.success('<option>late</option>');
  assert.equal(h.requests('combo').length, count);
  assert.equal(h.$('#proced_id').prop('disabled'), true);
  assert.doesNotMatch(h.$('#proced_id').html(), /late/);
});

test('current request failures leave child selections empty and disabled', () => {
  const h = harness(); selectDocument(h).error({}, 'error', 'failure');
  assert.equal(h.$('#area_id').val(), ''); assert.equal(h.$('#area_id').prop('disabled'), true);
  selectDocument(h).success('<option>areas</option>'); h.change('#area_id', '4');
  h.requests('combo').at(-1).error({}, 'error', 'failure');
  assert.equal(h.$('#proced_id').val(), ''); assert.equal(h.$('#proced_id').prop('disabled'), true);
});
