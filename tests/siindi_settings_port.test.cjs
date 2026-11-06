const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const crypto = require('node:crypto');
const root = path.join(__dirname, '..');
const read = p => fs.readFileSync(path.join(root, p), 'utf8');

test('original module organization, local assets and source operations', () => {
  for (const f of ['selector_metodo', 'desafio_login', 'enrolar_login', 'modal_enrolamiento', 'perfil_2fa', 'recuperar_acceso']) {
    const s = read(`seguridad/2fa/views/${f}.php`);
    assert.match(s, /config\/conexion.php/);
    assert.match(s, /JwtHandler|\$_SESSION/);
  }
  const selector = read('seguridad/2fa/views/selector_metodo.php');
  for (const token of ['method-box', 'modalCodigoCorreo', 'iniciarFlujoCorreo', 'solicitar_codigo_correo', 'verificar_codigo_correo']) assert.ok(selector.includes(token), token);
  assert.match(selector, /2fa_pending_user/);
  assert.match(read('seguridad/2fa/views/enrolar_login.php'), /2fa_pending_user/);
  assert.match(read('seguridad/2fa/views/recuperar_acceso.php'), /form2FARecovery/);
  assert.match(read('seguridad/2fa/assets/css/two_factor.css'), /@media \(max-width: 768px\)/);
  const qr = read('seguridad/2fa/assets/js/qrcode.min.js');
  assert.ok(qr.includes('QRCode.CorrectLevel'));
  for (const [p, hash] of [
    ['seguridad/2fa/assets/js/qrcode.min.js', 'c541ef06327885a8415bca8df6071e14189b4855336def4f36db54bde8484f36'],
    ['seguridad/2fa/assets/css/two_factor.css', 'be4c55326257eb43140db2318b206b6900d3f732202469c7ae3a73c219663099']
  ]) assert.equal(crypto.createHash('sha256').update(read(p)).digest('hex'), hash);
  for (const f of ['actualizar_contrasena', 'primer_acceso', 'recuperar_contrasena']) {
    const s = read(`seguridad/${f}/views/index.php`);
    assert.match(s, /main-card/);
    assert.match(s, /auth\/proxy.php/);
    assert.match(s, /api: 'clave.php\?op=/);
    assert.match(s, /JwtHandler/);
    assert.match(s, /public\/js\/jquery.min.js/);
  }
});

test('settings posts original fields directly, suppresses duplicate writes, and escapes provider data', () => {
  const elements = new Map();
  const calls = [];
  let finish;
  const button = {};
  const $ = key => {
    if (!elements.has(key)) elements.set(key, {disabled: false, value: '', textValue: '', prop(name, value) { if (arguments.length === 1) return this[name]; this[name] = value; return this; }, val(v) { if (arguments.length === 0) return this.value; this.value = v; return this; }, css() { return this; }, text(v) { this.textValue = v; return this; }, ready() {} });
    return elements.get(key);
  };
  $.post = (url, data, callback) => {
    calls.push({url, data, callback});
    return {fail() { return this; }, always(fn) { finish = fn; return this; }};
  };
  const context = {window: {SETTINGS_BASE_URL: '/fixture/', SETTINGS_CSRF_TOKEN: 'fixture-token'}, document: {}, $, Swal: {fire() {}}};
  vm.runInNewContext(read('view/setting/settings.js'), context);
  context.guardarContacto(button, 'update_telefono', '000000000');
  context.guardarContacto(button, 'update_telefono', '000000000');
  assert.equal(calls.length, 1);
  assert.equal(calls[0].url, '/fixture/controller/usuario.php?op=update_telefono');
  assert.equal(calls[0].data.dato, '000000000');
  assert.equal(calls[0].data.csrf_token, 'fixture-token');
  assert.deepEqual(Object.keys(calls[0].data).sort(), ['csrf_token', 'dato']);
  finish();
  assert.equal($(button).disabled, false);
  context.cargarDatos();
  calls[1].callback({success: true, data: {usu_celu01: '<img onerror=x>', usu_emailp: '<svg onload=x>', usu_emaili: 'synthetic@example.invalid'}});
  assert.ok($('#txt-telefono').textValue.includes('<img onerror=x>'));
  assert.ok($('#txt-emailp').textValue.includes('<svg onload=x>'));
});

test('source inline handlers remain syntactically executable after localized PHP interpolation', () => {
  for (const file of ['selector_metodo', 'desafio_login', 'enrolar_login', 'modal_enrolamiento', 'perfil_2fa']) {
    const source = read(`seguridad/2fa/views/${file}.php`)
      .replace(/<\?php[\s\S]*?\?>/g, '')
      .replace(/<\?=[\s\S]*?\?>/g, 'null');
    for (const match of source.matchAll(/<script\b[^>]*>([\s\S]*?)<\/script>/g)) {
      const js = match[1];
      new vm.Script(js, {filename: file});
    }
  }
});

test('original challenge disables its submit button and renders upstream messages as text', () => {
  let requests = 0;
  const box = { classList: { remove() {} }, textContent: '' };
  const button = { disabled: false, innerHTML: '' };
  const input = { value: '123456', focus() {} };
  const elements = { codigo_2fa: input, btnSubmit2FA: button, alert2FA: box };
  const context = { window: { TWO_FACTOR_CSRF_TOKEN: 'fixture' }, document: { getElementById: id => elements[id] }, $: { ajax(options) { requests++; assert.equal(options.data.codigo_2fa, '123456'); } }, setTimeout() {} };
  vm.runInNewContext(read('seguridad/2fa/assets/js/two_factor.js'), context);
  context.window.TwoFactorApp.submitChallenge();
  assert.equal(button.disabled, true);
  assert.equal(requests, 1);
  context.window.TwoFactorApp.showAlert('<img src=x onerror=attack()>', 'danger');
  assert.equal(box.textContent, '<img src=x onerror=attack()>');
});
