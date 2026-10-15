const {test} = require('node:test');
const assert = require('node:assert/strict');
const {spawnSync} = require('node:child_process');
const fs = require('node:fs');
const vm = require('node:vm');

function listing(type, states) {
  const fixture = states.map((est, i) => ({proced_tipocampo: type, est,
    tasatciud_id: i + 10, tasaproced_pos: i + 1, tasa_nom: `Fee ${i}`,
    tasaproced_monto: i ? '35.50' : '18.00', is_multiplica: i === 2 ? 1 : 0}));
  const php = `class Tasa { function listar_proceds_tasa_x_procedciudadano($id) { return json_decode(getenv('FEE_FIXTURE'), true); } }
class Empresa {} class Procedimiento {} class Bitacora {}
$_GET = ['op'=>'listar_proceds_tasa_x_procedciudadano_usu'];
$_POST = ['proceciudadano_id'=>7, 'est'=>1];
$source = file_get_contents('controller/tasa.php');
$source = preg_replace('/require_once\\([^;]+;/', '', $source);
eval('?>' . $source);`;
  const r = spawnSync('php', ['-d', 'display_errors=stderr', '-r', php], {
    cwd: `${__dirname}/..`, encoding: 'utf8', env: {...process.env, FEE_FIXTURE: JSON.stringify(fixture)}});
  assert.equal(r.status, 0, r.stderr);
  assert.equal(r.stderr, '');
  const response = JSON.parse(r.stdout);
  assert.equal(response.iTotalRecords, states.length);
  return response;
}

function harness(response) {
  const elements = new Map(), handlers = [], requests = [], alerts = [];
  let rows = [];
  const document = {getElementById() { return null; }, addEventListener() {}};
  const all = '#tasaListContainer .list-group-item';
  function $(selector) {
    if (selector === document) return {ready() {}, on(events, target, fn) { handlers.push({target, fn}); }};
    if (selector && typeof selector === 'object') return api([selector]);
    if (selector === all || selector === '.list-group-item') return api(rows);
    if (selector.startsWith('#tasaListContainer input')) {
      return api(rows.map(r => r.checkbox).filter(c => c.grupo === 'posteriores' && !c.disabled));
    }
    if (!elements.has(selector)) elements.set(selector, {value: '', checked: false});
    return api([elements.get(selector)]);
  }
  function api(items) {
    return {
      length: items.length,
      each(fn) { items.forEach(x => fn.call(x)); return this; },
      find(s) { return api(items.flatMap(x => s.includes('checkbox') ? [x.checkbox] : x.quantity ? [x.quantity] : [])); },
      data(k) { return items[0]?.[k]; },
      val(v) { if (!arguments.length) return items[0]?.value; items.forEach(x => x.value = v); return this; },
      prop(k, v) { if (arguments.length === 1) return items[0]?.[k]; items.forEach(x => x[k] = v); return this; },
      html(v) { if (!arguments.length) return items[0]?.html; items.forEach(x => x.html = v); return this; },
      attr(k, v) { items.forEach(x => x[k.replace('data-', '')] = v); return this; },
      show() { items.forEach(x => x.visible = true); return this; },
      hide() { items.forEach(x => x.visible = false); return this; },
      on(event, fn) { items.forEach(x => x.handler = fn); return this; },
      off() { return this; }, appendTo() { return this; }, modal() { return this; },
      addClass() { return this; }
    };
  }
  $.ajax = o => {
    rows = response.aaData.map(row => {
      const input = row[0];
      const checkbox = {value: String(row[8]), estado: row[7], monto: 35.5,
        checked: /\schecked\b/.test(input), disabled: /\sdisabled\b/.test(input)};
      for (const match of input.matchAll(/data-([\w-]+)='([^']*)'/g)) {
        checkbox[match[1]] = /^\d+$/.test(match[2]) ? Number(match[2]) : match[2];
      }
      return {checkbox, quantity: row[6] == 1 ? {value: '1'} : null};
    });
    o.success(response);
  };
  $.post = (...args) => { requests.push(args); return {fail() {}}; };
  const context = vm.createContext({$, document, console, Swal: {fire(o) { alerts.push(o); }},
    bootstrap: {Modal: class {show() {}}}});
  vm.runInContext(fs.readFileSync(`${__dirname}/../view/giros_ciudadano/usudetalleciudadano.js`, 'utf8'), context);
  $('#estado_procedimiento').val('1');
  context.listarTasas(7);
  return {context, $, rows, requests, alerts, button: elements.get('#IDpagarGrupo'),
    confirm() { elements.get('#btn-action').handler({preventDefault() {}}); },
    toggle(index, checked) {
      rows[index].checkbox.checked = checked;
      // Exercise the public delegated checkbox event, registered outside ready if necessary.
      const groupHandler = handlers.find(h => h.target.includes('data-grupo'));
      assert.ok(groupHandler, 'Joint selection handler registered');
      groupHandler.fn.call(rows[index].checkbox);
    }};
}

test('type 2 enables pending fees together after administrative Girado, without payment', () => {
  for (const type of [2, '2']) {
    for (const first of [2, '2', 4, 5]) {
      const response = listing(type, [first, 1, 1]);
      assert.doesNotMatch(response.aaData[1][0], /disabled/);
      assert.match(response.aaData[1][0], /checked/);
      assert.equal(response.aaData[1][5], '', 'No individual issuance for remaining fees');
      assert.match(response.aaData[0][0], /disabled/);
      assert.doesNotMatch(response.aaData[0][0], /checked/);
      const h = harness(response);
      assert.equal(h.button.disabled, false);
      assert.match(h.button.html, /\(2\) - S\/ 71.00/);
      h.toggle(1, false);
      assert.equal(h.rows[2].checkbox.checked, false);
      assert.equal(h.button.disabled, true);
      h.toggle(2, true);
      assert.equal(h.rows[1].checkbox.checked, true);
      assert.equal(h.rows[0].checkbox.checked, false);
      assert.equal(h.button.disabled, false);
      h.rows[2].quantity.value = '3'; h.context.calcularTotalTasas();
      assert.match(h.button.html, /S\/ 142.00/);
      h.context.pagargrupo();
      assert.equal(h.requests.length, 0, 'No issuance before confirmation');
      h.confirm();
      assert.equal(h.requests.length, 1);
      assert.match(h.requests[0][0], /Pagar_Orden_Giro_grupo$/);
      assert.deepEqual({...h.requests[0][1]}, {tasatciud_id: '11,12', cantidades: '1,3', comentario: ''});
    }
  }
});

test('type 2 blocks the group before issuance and excludes nonpending remaining fees', () => {
  for (const first of [0, 1, 3, 6]) {
    const response = listing(2, [first, 1, 1]);
    for (const row of response.aaData) {
      assert.match(row[0], /disabled/);
      assert.doesNotMatch(row[0], /checked/);
    }
    if (first === 1) assert.match(response.aaData[0][5], /pagar\(/);
    const h = harness(response);
    assert.equal(h.button.disabled, true);
    h.context.pagargrupo();
    assert.equal(h.requests.length, 0, 'Blocked group makes no issuance request');
    assert.equal(h.alerts.length, 1);
  }
  const h = harness(listing(2, [2, 4, 1]));
  assert.equal(h.rows[1].checkbox.checked, false);
  assert.equal(h.rows[1].checkbox.disabled, true);
  assert.match(h.button.html, /\(1\) - S\/ 35.50/);
  assert.equal(h.button.disabled, false);
  assert.equal(harness(listing(2, [2, 4, 5])).button.disabled, true);
});

test('ordinary individual/manual and type 3 grouped presentation remain available', () => {
  const normal = listing(1, [1, 1]);
  assert.match(normal.aaData[0][5], /pagar\(/);
  assert.doesNotMatch(normal.aaData[0][0], /disabled/);
  const h = harness(normal);
  assert.equal(h.button.disabled, false);
  h.rows[1].checkbox.checked = false;
  h.context.calcularTotalTasas();
  assert.equal(h.rows[0].checkbox.checked, true, 'Ordinary selection stays independent');
  assert.match(h.button.html, /\(1\) - S\/ 18.00/);
  const joint = listing(3, [1, 1]);
  assert.match(joint.aaData[0][0], /disabled checked/);
  assert.equal(joint.aaData[0][5], '');
  assert.equal(harness(joint).button.disabled, false);
});
