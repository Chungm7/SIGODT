/* La validación del navegador ayuda al usuario; el servidor decide siempre. */
(function (root) {
  function createCaptcha({ form, input, image, submit, refresh, status, fetch }) {
    let state = 'error';
    let generation = 0;
    let timer;
    function fail(token) {
      if (token !== generation) return;
      clearTimeout(timer);
      state = 'error';
      image.onload = image.onerror = null;
      image.removeAttribute('src');
      input.disabled = true;
      input.setCustomValidity('Actualice el CAPTCHA para continuar.');
      submit.disabled = true;
      refresh.disabled = false;
      status.textContent = 'No se pudo cargar el CAPTCHA. Pulse actualizar para reintentar.';
    }
    async function load() {
      // Serializar peticiones evita que imágenes y sesiones queden desordenadas.
      if (state === 'loading' || state === 'submitting') return;
      const token = ++generation;
      state = 'loading';
      input.value = '';
      input.disabled = true;
      input.setCustomValidity('Espere a que cargue el CAPTCHA.');
      submit.disabled = refresh.disabled = true;
      image.onload = image.onerror = null;
      image.removeAttribute('src');
      status.textContent = 'Cargando CAPTCHA…';
      const abort = new AbortController();
      timer = setTimeout(() => { abort.abort(); fail(token); }, 15000);
      try {
        const response = await fetch('captcha.php?action=generateCaptcha', { cache: 'no-store', signal: abort.signal });
        if (!response.ok) throw Error('HTTP');
        const data = await response.json();
        if (!data || typeof data.captcha !== 'string' || !/^data:image\/png;base64,[A-Za-z0-9+/]+={0,2}$/.test(data.captcha)) throw Error('Imagen inválida');
        if (token !== generation || state !== 'loading') return;
        image.onload = () => {
          if (token !== generation || state !== 'loading') return;
          clearTimeout(timer);
          state = 'ready';
          input.disabled = submit.disabled = refresh.disabled = false;
          input.setCustomValidity('');
          status.textContent = 'Ingrese los seis caracteres de la imagen. Vigencia: 5 minutos.';
        };
        image.onerror = () => fail(token);
        image.src = data.captcha;
      } catch (_) { fail(token); }
    }
    input.addEventListener('input', () => {
      input.value = input.value.toUpperCase();
      if (state === 'ready') input.setCustomValidity('');
    });
    refresh.addEventListener('click', load);
    form.addEventListener('submit', event => {
      if (state !== 'ready') { event.preventDefault(); return; }
      input.setCustomValidity(/^[A-Z2-9]{6}$/.test(input.value) ? '' : 'Ingrese los seis caracteres del CAPTCHA.');
      if (!/^[A-Z2-9]{6}$/.test(input.value) || !form.reportValidity()) {
        event.preventDefault();
        form.reportValidity();
        return;
      }
      state = 'submitting';
      submit.disabled = refresh.disabled = true;
      status.textContent = 'Verificando…';
    });
    return { refresh: load };
  }
  if (typeof module !== 'undefined' && module.exports) module.exports = createCaptcha;
  else root.addEventListener('DOMContentLoaded', () => {
    const controller = createCaptcha({
      form: document.getElementById('loginForm'), input: document.getElementById('captchaInput'),
      image: document.getElementById('captchaImage'), submit: document.getElementById('loginSubmit'),
      refresh: document.getElementById('btcap'), status: document.getElementById('captchaStatus'),
      fetch: root.fetch.bind(root)
    });
    controller.refresh();
    root.addEventListener('pageshow', event => { if (event.persisted) root.location.reload(); });
  });
})(typeof window !== 'undefined' ? window : globalThis);
