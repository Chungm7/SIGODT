/* reCAPTCHA v3 es una capa adicional; el servidor valida siempre el token. */
(function (root) {
  const SCRIPT_URL = 'https://www.google.com/recaptcha/api.js?render=';
  const ACTION = 'login';
  const EXECUTE_TIMEOUT = 10000;
  const MESSAGES = {
    script: 'No se pudo cargar la verificación de seguridad. Recargue la página e intente nuevamente.',
    execute: 'No se pudo completar la verificación de seguridad. Intente nuevamente.',
    pending: 'Verificando seguridad…'
  };

  function createRecaptcha({ form, tokenInput, status, siteKey, loadScript, getGrecaptcha, onFailure }) {
    // Sin clave del sitio la función está desactivada y el formulario no cambia.
    if (typeof siteKey !== 'string' || !/^[A-Za-z0-9_-]+$/.test(siteKey)) return null;
    let scriptState = 'loading';
    let busy = false;
    function show(message) { status.textContent = message; }
    function fail(message) {
      busy = false;
      tokenInput.value = '';
      show(message);
      if (onFailure) onFailure();
    }
    const loading = Promise.resolve().then(() => loadScript(SCRIPT_URL + encodeURIComponent(siteKey)))
      .then(() => { scriptState = 'ready'; }, () => { scriptState = 'error'; show(MESSAGES.script); });
    function requestToken() {
      const grecaptcha = getGrecaptcha();
      if (!grecaptcha || typeof grecaptcha.ready !== 'function' || typeof grecaptcha.execute !== 'function') {
        return Promise.reject(Error('grecaptcha'));
      }
      return new Promise((resolve, reject) => {
        const timer = setTimeout(() => reject(Error('timeout')), EXECUTE_TIMEOUT);
        grecaptcha.ready(() => {
          Promise.resolve(grecaptcha.execute(siteKey, { action: ACTION })).then(resolve, reject)
            .then(() => clearTimeout(timer));
        });
      });
    }
    form.addEventListener('submit', event => {
      // La validación del CAPTCHA de imagen se ejecuta antes y puede bloquear el envío.
      if (event.defaultPrevented) return;
      if (tokenInput.value !== '') return;
      event.preventDefault();
      if (busy) return;
      if (scriptState !== 'ready') { fail(scriptState === 'error' ? MESSAGES.script : MESSAGES.execute); return; }
      busy = true;
      show(MESSAGES.pending);
      requestToken().then(token => {
        if (typeof token !== 'string' || token === '') throw Error('token');
        tokenInput.value = token;
        busy = false;
        form.submit();
      }).catch(() => fail(MESSAGES.execute));
    });
    return { ready: loading };
  }

  function appendScript(src) {
    return new Promise((resolve, reject) => {
      const script = root.document.createElement('script');
      script.src = src;
      script.async = true;
      script.onload = resolve;
      script.onerror = () => reject(Error('script'));
      root.document.head.appendChild(script);
    });
  }

  if (typeof module !== 'undefined' && module.exports) module.exports = createRecaptcha;
  else root.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('loginForm');
    const tokenInput = document.getElementById('recaptchaToken');
    const status = document.getElementById('recaptchaStatus');
    if (!form || !tokenInput || !status) return;
    createRecaptcha({
      form, tokenInput, status,
      siteKey: form.getAttribute('data-recaptcha-site-key'),
      loadScript: appendScript,
      getGrecaptcha: () => root.grecaptcha,
      onFailure: () => { if (root.sigodtLoginCaptcha) root.sigodtLoginCaptcha.restart(); }
    });
  });
})(typeof window !== 'undefined' ? window : globalThis);
