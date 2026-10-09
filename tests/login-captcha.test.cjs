const { test } = require('node:test');
const assert = require('node:assert/strict');
const createCaptcha = require('../public/js/login-captcha.js');
function fixture(fetch) {
  const listeners = {};
  const form = { addEventListener: (name, fn) => { listeners[name] = fn; }, reportValidity: () => true };
  const input = { value: 'OLD', disabled: false, setCustomValidity(message) { this.error = message; }, addEventListener() {} };
  const image = { removeAttribute() { this.src = ''; } };
  const submit = {};
  const refresh = { addEventListener() {} };
  const status = {};
  const controller = createCaptcha({ form, input, image, submit, refresh, status, fetch });
  return { controller, input, image, submit, refresh, status, listeners };
}
const response = { ok: true, json: async () => ({ captcha: 'data:image/png;base64,aW1hZ2U=' }) };
test('timeout stays closed even when an ignored abort resolves later', async (t) => {
  t.mock.timers.enable({ apis: ['setTimeout'] });
  let resolve;
  const f = fixture(() => new Promise(r => { resolve = r; }));
  const pending = f.controller.refresh();
  t.mock.timers.tick(15000);
  assert.equal(f.submit.disabled, true);
  assert.equal(f.refresh.disabled, false);
  resolve(response);
  await pending;
  assert.equal(f.image.src, '');
  assert.equal(f.submit.disabled, true);
});
test('loading, image decode, required input and single submission', async () => {
  let resolve;
  let calls = 0;
  const f = fixture(() => { calls++; return new Promise(r => { resolve = r; }); });
  const pending = f.controller.refresh();
  f.controller.refresh();
  assert.equal(calls, 1, 'overlapping refresh must not race server sessions');
  assert.equal(f.input.value, '');
  assert.equal(f.submit.disabled, true);
  resolve(response);
  await pending;
  assert.equal(f.submit.disabled, true, 'JSON alone must not enable login');
  f.image.onload();
  assert.equal(f.submit.disabled, false);
  let prevented = false;
  f.listeners.submit({ preventDefault() { prevented = true; } });
  assert.equal(prevented, true, 'empty CAPTCHA blocked');
  f.input.value = 'ABC234';
  prevented = false;
  f.listeners.submit({ preventDefault() { prevented = true; } });
  assert.equal(prevented, false);
  assert.equal(f.submit.disabled, true);
  f.listeners.submit({ preventDefault() { prevented = true; } });
  assert.equal(prevented, true, 'duplicate submission blocked');
});
test('refresh clears answer and stale image callbacks cannot enable login', async () => {
  const f = fixture(async () => response);
  await f.controller.refresh();
  const oldLoad = f.image.onload;
  f.image.onload();
  f.input.value = 'ABC234';
  const pending = f.controller.refresh();
  oldLoad();
  assert.equal(f.input.value, '');
  assert.equal(f.submit.disabled, true);
  await pending;
  f.image.onerror();
  assert.equal(f.submit.disabled, true);
  assert.match(f.status.textContent, /No se pudo/);
});
test('network, HTTP and malformed JSON failures stay closed and allow retry', async () => {
  for (const fetch of [async () => { throw Error('network'); }, async () => ({ ok: false }), async () => ({ ok: true, json: async () => { throw Error('JSON'); } }), async () => ({ ok: true, json: async () => ({ captcha: 'bad' }) })]) {
    const f = fixture(fetch);
    await f.controller.refresh();
    assert.equal(f.submit.disabled, true);
    assert.equal(f.refresh.disabled, false);
    assert.equal(f.input.disabled, true);
    let prevented = false;
    f.listeners.submit({ preventDefault() { prevented = true; } });
    assert.equal(prevented, true);
  }
});
