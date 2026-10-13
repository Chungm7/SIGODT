const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync('view/giros_ciudadano/usudetalleciudadano.js', 'utf8');
const markup = fs.readFileSync('view/giros_ciudadano/index.php', 'utf8');

function functionSource(name) {
  const match = source.match(new RegExp(`function ${name}\\([^]*?\\n\\}`));
  assert.ok(match, `Expected operational function ${name}`);
  return match[0];
}

function harness({ procedure = '8' } = {}) {
  const elements = new Map(), requests = [], delivered = [], reloads = [];
  let configuration, initializations = 0, draw = 0;
  function $(selector) {
    selector = String(selector);
    if (!elements.has(selector)) elements.set(selector, { value: '', markup: '', handlers: {} });
    const state = elements.get(selector);
    const api = {
      val(value) { if (value === undefined) return state.value; state.value = value; return api; },
      html(value) { if (value === undefined) return state.markup; state.markup = value; return api; },
      text(value) { if (value === undefined) return state.text; state.text = value; return api; },
      prop(key, value) { if (value === undefined) return state[key]; state[key] = value; return api; },
      attr(key, value) { state[key] = value; return api; },
      toggleClass(key, value) { state[key] = value; return api; },
      change(handler) { state.handlers.change = handler; return api; },
      on(event, handler) { state.handlers[event] = handler; return api; },
      each(handler) { handler.call(selector); return api; },
      select2() { return api; },
      ready(handler) { state.ready = handler; return api; },
      DataTable(options) {
        if (options) { configuration = options; initializations++; }
        return table;
      }
    };
    return api;
  }
  const table = { ajax: { reload(callback, resetPaging) {
    reloads.push(resetPaging);
    request();
    if (callback) callback();
  } } };
  function request(overrides = {}) {
    const input = { draw: ++draw, start: 20, length: 10, search: { value: 'Citizen %_' }, ...overrides };
    configuration.ajax(input, result => delivered.push(result));
    return input;
  }
  $.fn = { DataTable: { isDataTable: () => initializations > 0 } };
  $.ajax = options => {
    const pending = { ...options, aborted: false };
    requests.push(pending);
    return { abort() { pending.aborted = true; pending.error({}, 'abort'); } };
  };
  const document = {};
  const context = vm.createContext({ $, document, console, area_id: '4', proced_id: procedure,
    registrationPickerVersion: 0, registrationModalProcedure: null,
    giroListTable: null, giroListRequest: null, giroListVersion: 0, giroListRefreshMarkup: null,
    combo_tupa() {}, getdataload() {}, combo_areas() {},
    combo_proced() { $('#proced_id').val(''); }, calcularTotalTasas() {} });
  for (const name of ['giroListSelection', 'giroListStatus', 'giroListBusy', 'loadGiroList', 'cargardata', 'recargarTabla', 'cargardata_reload']) {
    vm.runInContext(functionSource(name), context);
  }
  const ready = source.match(/\$\(document\)\.ready\(function \(\) \{[^]*?\n\}\);/);
  assert.ok(ready, 'Ready handlers must remain discoverable');
  vm.runInContext(ready[0], context);
  $('#proced_id').val(procedure); $('#area_id').val('4');
  $('#giro_state').val('en_curso'); $('#btnRecargar').html('<svg>refresh</svg>');
  const run = code => vm.runInContext(code, context);
  const change = selector => elements.get(selector).handlers.change.call(selector);
  return { $, context, elements, requests, delivered, reloads, request, run, change,
    boot() { elements.get('[object Object]').ready(); },
    get configuration() { return configuration; }, get initializations() { return initializations; } };
}

function result(draw, count = 1) {
  return { draw, recordsTotal: count, recordsFiltered: count, data: count ? [['current']] : [] };
}

test('Controls expose known states, blank optional creation dates and accessible feedback', () => {
  assert.match(markup, /id="giro_state"/);
  for (const value of ['en_curso', '9', '1', '4', '0', '5', '6', 'todos']) {
    assert.match(markup, new RegExp(`value="${value}"`));
  }
  const stateSelector = markup.match(/<select[^>]*id="giro_state"[^>]*>[\s\S]*?<\/select>/)[0];
  assert.doesNotMatch(stateSelector, /Improcedente|value="3"/);
  assert.match(markup, /value="en_curso" selected/);
  for (const id of ['giro_date_from', 'giro_date_to']) {
    const input = markup.match(new RegExp(`<input[^>]*id="${id}"[^>]*>`));
    assert.ok(input); assert.doesNotMatch(input[0], /\brequired\b|value="[^"]+"/);
  }
  assert.match(markup, /registro del trámite, no al pago/);
  assert.match(markup, /id="giroListStatus"[^>]*aria-live="polite"/);
  assert.match(markup, /aria-label="Actualizar listado"/);
});

test('One modern server-side table preserves the selected procedure across reloads', () => {
  const h = harness(); h.run('cargardata()'); h.run('cargardata()');
  assert.equal(h.initializations, 1);
  assert.equal(h.configuration.serverSide, true); assert.equal(h.configuration.processing, true);
  assert.equal(h.configuration.info, true); assert.equal(h.configuration.pageLength, 10);
  assert.equal(h.context.proced_id, '8'); assert.equal(h.$('#proced_id').val(), '8');
  assert.equal(h.reloads.at(-1), true);
});

test('Every request forwards current filters and real DataTables search/page fields', () => {
  const h = harness(); h.run('cargardata()');
  h.$('#giro_date_from').val('2026-01-01'); h.$('#giro_date_to').val('2026-02-02');
  const input = h.request(); const request = h.requests.at(-1);
  assert.equal(request.data.draw, input.draw); assert.equal(request.data.start, 20);
  assert.equal(request.data.length, 10); assert.equal(request.data.search.value, 'Citizen %_');
  assert.equal(request.data.proced_id, '8'); assert.equal(request.data.area_id, '4');
  assert.equal(request.data.state, 'en_curso'); assert.equal(request.data.date_from, '2026-01-01');
  assert.equal(request.data.date_to, '2026-02-02');
  h.$('#proced_id').val('9'); h.$('#giro_state').val('todos'); h.request();
  assert.equal(h.requests.at(-1).data.proced_id, '9'); assert.equal(h.requests.at(-1).data.state, 'todos');
});

test('Blank procedure clears data locally without a request or false success', () => {
  const h = harness({ procedure: '' }); h.run('cargardata()'); const input = h.request();
  assert.equal(h.requests.length, 0); assert.equal(h.delivered.at(-1).draw, input.draw);
  assert.equal(h.delivered.at(-1).recordsTotal, 0);
  assert.match(h.$('#giroListStatus').text(), /Seleccione/);
  assert.equal(h.$('#btnRecargar').prop('disabled'), false);
});

test('Refresh waits for actual completion, preserves paging and supports modal reloads', () => {
  const h = harness(); h.run('cargardata()');
  let prevented = false; h.context.event = { preventDefault() { prevented = true; } };
  h.run('recargarTabla(event)');
  assert.equal(prevented, true); assert.equal(h.reloads.at(-1), false);
  assert.equal(h.$('#btnRecargar').prop('disabled'), true);
  const request = h.requests.at(-1); request.success(result(request.data.draw, 3));
  assert.equal(h.$('#btnRecargar').prop('disabled'), false);
  assert.equal(h.$('#btnRecargar').html(), '<svg>refresh</svg>');
  assert.match(h.$('#giroListStatus').text(), /3/);
  h.$('#detalle_data').DataTable().ajax.reload();
  assert.equal(h.$('#btnRecargar').prop('disabled'), true);
  assert.equal(h.initializations, 1);
});

test('Late success/error after selection changes never inject stale rows or messages', () => {
  const h = harness(); h.run('cargardata()'); h.request(); const old = h.requests.at(-1);
  h.$('#proced_id').val('9'); h.run('cargardata()'); const current = h.requests.at(-1);
  assert.equal(old.aborted, true);
  current.success(result(current.data.draw));
  const count = h.delivered.length, status = h.$('#giroListStatus').text();
  old.success(result(old.data.draw, 99)); old.error({}, 'error');
  assert.equal(h.delivered.length, count); assert.equal(h.$('#giroListStatus').text(), status);
  h.$('#proced_id').val(''); h.run('cargardata()');
  current.success(result(current.data.draw, 99));
  assert.equal(h.delivered.at(-1).recordsTotal, 0);
});

test('Snapshot guards reject changed filters even before another draw starts', () => {
  const h = harness(); h.run('cargardata()'); h.request(); const old = h.requests.at(-1);
  h.$('#giro_state').val('4'); old.success(result(old.data.draw));
  assert.equal(h.delivered.length, 0);
});

test('HTTP and semantic errors clear rows, release refresh and report failure safely', () => {
  const h = harness(); h.run('cargardata()'); h.request();
  h.requests.at(-1).error({ status: 500 }, 'error');
  assert.equal(h.delivered.at(-1).recordsFiltered, 0);
  assert.equal(h.$('#btnRecargar').prop('disabled'), false);
  assert.match(h.$('#giroListStatus').text(), /No se pudo/);
  h.request(); const request = h.requests.at(-1);
  request.success({ ...result(request.data.draw), error: '<script>unsafe</script>' });
  assert.equal(h.delivered.at(-1).recordsTotal, 0);
  assert.doesNotMatch(h.$('#giroListStatus').text(), /<script>/);
  assert.equal(h.$('#btnRecargar').prop('disabled'), false);
});

test('Filter changes reset paging; reset and area cascade retain a single table', () => {
  const h = harness(); h.boot();
  for (const id of ['#giro_state', '#giro_date_from', '#giro_date_to']) {
    h.change(id); assert.equal(h.reloads.at(-1), true);
  }
  h.$('#giro_state').val('4'); h.$('#giro_date_from').val('2026-01-01');
  h.elements.get('#giroResetFilters').handlers.click();
  assert.equal(h.$('#giro_state').val(), 'en_curso'); assert.equal(h.$('#giro_date_from').val(), '');
  assert.equal(h.$('#giro_date_to').val(), ''); assert.equal(h.$('#proced_id').val(), '8');
  const old = h.requests.at(-1); h.change('#area_id');
  assert.equal(h.$('#proced_id').val(), ''); assert.equal(h.delivered.at(-1).recordsTotal, 0);
  old.success(result(old.data.draw)); assert.equal(h.delivered.at(-1).recordsTotal, 0);
  assert.equal(h.initializations, 1);
});

test('Malformed draw/count contracts cannot announce a successful refresh', () => {
  const h = harness(); h.run('cargardata()');
  for (const invalid of [
    draw => ({ ...result(draw), draw: draw - 1 }),
    draw => ({ draw, data: [], recordsTotal: 1 }),
    draw => ({ ...result(draw), recordsFiltered: -1 })
  ]) {
    h.request(); const request = h.requests.at(-1); request.success(invalid(request.data.draw));
    assert.match(h.$('#giroListStatus').text(), /No se pudo/);
    assert.equal(h.delivered.at(-1).recordsTotal, 0);
    assert.equal(h.$('#btnRecargar').prop('disabled'), false);
  }
});


test('Operational cancellation copy says Anular without changing the existing action', () => {
  assert.match(markup, />Anular<\/th>/);
  const action = functionSource('eliminar');
  assert.match(action, /title: "Anular trámite"/);
  assert.match(action, /text: "¿Desea anular el trámite\?"/);
  assert.match(action, /text: "El trámite se anuló correctamente\."/);
  assert.doesNotMatch(action, /(?:title|text): "[^"]*(?:[Ee]liminar|[Ee]liminación|Eliminó)/);
  assert.match(action, /op=eliminar_proced_ciudadano/);
});
