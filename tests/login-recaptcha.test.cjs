const { test } = require('node:test');
const assert = require('node:assert/strict');
const createRecaptcha = require('../public/js/login-recaptcha.js');
const createCaptcha = require('../public/js/login-captcha.js');

function fixture(options = {}) {
  const listeners = {};
  const form = { addEventListener: (name, fn) => { listeners[name] = fn; }, submitted: 0, submit() { this.submitted++; } };
  const tokenInput = { value: '' };
  const status = { textContent: '' };
  const loaded = [];
  const calls = { failures: 0, executed: [] };
  const grecaptcha = options.grecaptcha === undefined ? {
    ready: fn => fn(),
    execute: async (key, opts) => { calls.executed.push({ key, opts }); return 'fake-token_1'; }
  } : options.grecaptcha;
  const controller = createRecaptcha({
    form, tokenInput, status,
    siteKey: 'siteKey' in options ? options.siteKey : 'fake-site-key',
    loadScript: options.loadScript || (async src => { loaded.push(src); }),
    getGrecaptcha: () => grecaptcha,
    onFailure: () => { calls.failures++; }
  });
  function submit(defaultPrevented = false) {
    const event = { defaultPrevented, prevented: false, preventDefault() { this.prevented = true; } };
    listeners.submit(event);
    return event;
  }
  return { controller, form, tokenInput, status, loaded, calls, submit };
}
const flush = () => new Promise(resolve => setImmediate(resolve));

test('disabled mode: no site key means no script, no listener', () => {
  for (const siteKey of [null, '', undefined, 'bad key"><script>']) {
    const f = fixture({ siteKey });
    assert.equal(f.controller, null);
    assert.deepEqual(f.loaded, []);
  }
});

test('enabled mode loads the v3 script with the site key only', async () => {
  const f = fixture();
  await f.controller.ready;
  assert.deepEqual(f.loaded, ['https://www.google.com/recaptcha/api.js?render=fake-site-key']);
});

test('submit requests a login token, fills the hidden field and submits once', async () => {
  const f = fixture();
  await f.controller.ready;
  const event = f.submit();
  assert.equal(event.prevented, true, 'native submit waits for the token');
  await flush();
  assert.deepEqual(f.calls.executed, [{ key: 'fake-site-key', opts: { action: 'login' } }]);
  assert.equal(f.tokenInput.value, 'fake-token_1');
  assert.equal(f.form.submitted, 1);
});

test('image CAPTCHA rejection short-circuits reCAPTCHA', async () => {
  const f = fixture();
  await f.controller.ready;
  const event = f.submit(true);
  assert.equal(event.prevented, false);
  await flush();
  assert.deepEqual(f.calls.executed, []);
  assert.equal(f.form.submitted, 0);
});

test('script load failure shows a Spanish error and never submits', async () => {
  const f = fixture({ loadScript: async () => { throw Error('blocked'); } });
  await f.controller.ready;
  assert.match(f.status.textContent, /No se pudo cargar la verificación/);
  const event = f.submit();
  assert.equal(event.prevented, true);
  await flush();
  assert.equal(f.form.submitted, 0);
  assert.equal(f.calls.failures, 1);
});

test('execute failures and empty tokens never submit and release the form', async () => {
  const failing = [
    { ready: fn => fn(), execute: async () => { throw Error('execute'); } },
    { ready: fn => fn(), execute: async () => '' },
    { ready: fn => fn(), execute: async () => ({}) },
    null,
    {}
  ];
  for (const grecaptcha of failing) {
    const f = fixture({ grecaptcha });
    await f.controller.ready;
    f.submit();
    await flush();
    assert.equal(f.form.submitted, 0);
    assert.equal(f.tokenInput.value, '');
    assert.match(f.status.textContent, /No se pudo completar la verificación/);
    assert.equal(f.calls.failures, 1);
  }
});

test('execute timeout fails closed', async (t) => {
  t.mock.timers.enable({ apis: ['setTimeout'] });
  const f = fixture({ grecaptcha: { ready: fn => fn(), execute: () => new Promise(() => {}) } });
  await f.controller.ready;
  f.submit();
  t.mock.timers.tick(10000);
  await flush();
  assert.equal(f.form.submitted, 0);
  assert.equal(f.calls.failures, 1);
});

test('duplicate submits while a token is pending trigger one execute', async () => {
  let resolve;
  const executed = [];
  const f = fixture({ grecaptcha: { ready: fn => fn(), execute: (key) => { executed.push(key); return new Promise(r => { resolve = r; }); } } });
  await f.controller.ready;
  f.submit();
  const second = f.submit();
  assert.equal(second.prevented, true);
  assert.equal(executed.length, 1);
  resolve('tok');
  await flush();
  assert.equal(f.form.submitted, 1);
});

test('image CAPTCHA controller can restart after a reCAPTCHA failure', async () => {
  const listeners = {};
  const form = { addEventListener: (n, fn) => { listeners[n] = fn; }, reportValidity: () => true };
  const input = { value: '', disabled: false, setCustomValidity() {}, addEventListener() {} };
  const image = { removeAttribute() { this.src = ''; } };
  const submit = {};
  const refresh = { addEventListener() {} };
  const status = {};
  const fetch = async () => ({ ok: true, json: async () => ({ captcha: 'data:image/png;base64,aW1hZ2U=' }) });
  const controller = createCaptcha({ form, input, image, submit, refresh, status, fetch });
  await controller.refresh();
  image.onload();
  input.value = 'ABC234';
  listeners.submit({ preventDefault() {} });
  assert.equal(submit.disabled, true, 'captcha is locked while submitting');
  await controller.restart();
  assert.equal(submit.disabled, true, 'locked until the new image decodes');
  image.onload();
  assert.equal(submit.disabled, false, 'new challenge re-enables the form');
});
