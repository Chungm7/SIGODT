const { test, describe, beforeEach } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const themeScriptSource = fs.readFileSync(path.join(__dirname, '../public/js/demo-theme.js'), 'utf8');
const minScriptSource = fs.readFileSync(path.join(__dirname, '../public/js/demo-theme.min.js'), 'utf8');

function createMockEnvironment(options = {}) {
  const store = { ...(options.localStorage || {}) };
  const localStorageMock = {
    getItem: (key) => (key in store ? store[key] : null),
    setItem: (key, val) => { store[key] = String(val); },
    removeItem: (key) => { delete store[key]; },
    clear: () => { Object.keys(store).forEach(k => delete store[k]); },
    _store: store,
  };

  function createMockElement(tagName) {
    const attrs = {};
    const classes = new Set();
    return {
      tagName: tagName.toUpperCase(),
      attributes: attrs,
      classList: {
        add: (c) => classes.add(c),
        remove: (c) => classes.delete(c),
        contains: (c) => classes.has(c),
      },
      setAttribute: (k, v) => { attrs[k] = String(v); },
      removeAttribute: (k) => { delete attrs[k]; },
      getAttribute: (k) => attrs[k] || null,
      hasAttribute: (k) => k in attrs,
    };
  }

  const rootElement = createMockElement('html');
  let bodyElement = options.bodyInitiallyNull ? null : createMockElement('body');

  const listeners = {};
  const historyCalls = [];

  const context = {
    window: {},
    document: {
      documentElement: rootElement,
      get body() {
        return bodyElement;
      },
      set body(b) {
        bodyElement = b;
      },
      readyState: options.readyState || 'complete',
      title: 'SIGODT Test',
      addEventListener: (evt, fn) => {
        listeners[evt] = listeners[evt] || [];
        listeners[evt].push(fn);
      },
      dispatchEvent: (evtObj) => {
        (listeners[evtObj.type] || []).forEach(fn => fn(evtObj));
      },
    },
    localStorage: localStorageMock,
    URLSearchParams: class MockURLSearchParams {
      constructor(search) {
        this.params = new URLSearchParams(search || '');
      }
      get(k) { return this.params.get(k); }
      has(k) { return this.params.has(k); }
      delete(k) { return this.params.delete(k); }
      toString() { return this.params.toString(); }
    },
    URL: class MockURL {
      constructor(urlStr) {
        const u = new URL(urlStr);
        this.pathname = u.pathname;
        this.hash = u.hash;
        this.searchParams = u.searchParams;
      }
      get search() {
        const s = this.searchParams.toString();
        return s ? '?' + s : '';
      }
    },
  };

  context.window = {
    location: {
      href: options.url || 'http://localhost/SIGODT/view/giros_ciudadano/',
      search: options.search !== undefined ? options.search : (new URL(options.url || 'http://localhost/SIGODT/view/giros_ciudadano/').search),
    },
    history: {
      state: null,
      replaceState: (state, title, url) => {
        historyCalls.push({ state, title, url });
        context.window.location.href = 'http://localhost' + url;
        context.window.location.search = url.includes('?') ? url.substring(url.indexOf('?')) : '';
      },
    },
    bootstrap: null,
  };

  context.window.document = context.document;
  context.window.localStorage = localStorageMock;
  context.window.URLSearchParams = context.URLSearchParams;
  context.window.URL = context.URL;

  return {
    context,
    root: rootElement,
    getBody: () => bodyElement,
    setBody: (b) => { bodyElement = b; },
    createBody: () => {
      bodyElement = createMockElement('body');
      return bodyElement;
    },
    triggerEvent: (type, eventObj) => {
      (listeners[type] || []).forEach(fn => fn(eventObj));
    },
    localStorage: localStorageMock,
    historyCalls,
  };
}

describe('Theme Switcher - demo-theme.js', () => {
  test('does not throw when executed in <head> while document.body is null', () => {
    const env = createMockEnvironment({
      bodyInitiallyNull: true,
      readyState: 'loading',
      localStorage: { tablerTheme: 'dark' },
    });

    assert.doesNotThrow(() => {
      vm.runInNewContext(themeScriptSource, env.context);
    });

    // documentElement should already have dark theme
    assert.equal(env.root.getAttribute('data-bs-theme'), 'dark');
    assert.ok(env.root.classList.contains('theme-dark'));
    assert.equal(env.getBody(), null);

    // Now body is created and DOMContentLoaded fires
    const body = env.createBody();
    env.context.document.readyState = 'interactive';
    env.triggerEvent('DOMContentLoaded', {});

    // Body should now also have dark theme
    assert.equal(body.getAttribute('data-bs-theme'), 'dark');
    assert.ok(body.classList.contains('theme-dark'));
  });

  test('switches from dark to light mode via click on ?theme=light', () => {
    const env = createMockEnvironment({
      localStorage: { tablerTheme: 'dark' },
    });

    vm.runInNewContext(themeScriptSource, env.context);

    const body = env.getBody();
    assert.equal(env.root.getAttribute('data-bs-theme'), 'dark');
    assert.equal(body.getAttribute('data-bs-theme'), 'dark');

    // Simulate clicking the light theme link (sun icon)
    let defaultPrevented = false;
    const linkElement = {
      getAttribute: (name) => (name === 'href' ? '?theme=light' : null),
    };
    const clickEvent = {
      type: 'click',
      target: {
        closest: (selector) => (selector === 'a[href*="theme="]' ? linkElement : null),
      },
      preventDefault: () => { defaultPrevented = true; },
    };

    env.triggerEvent('click', clickEvent);

    assert.ok(defaultPrevented, 'Click should prevent default navigation');
    assert.equal(env.localStorage.getItem('tablerTheme'), 'light');
    assert.equal(env.root.getAttribute('data-bs-theme'), null);
    assert.equal(env.root.classList.contains('theme-dark'), false);
    assert.equal(body.getAttribute('data-bs-theme'), null);
    assert.equal(body.classList.contains('theme-dark'), false);
  });

  test('switches from light to dark mode via click on ?theme=dark', () => {
    const env = createMockEnvironment({
      localStorage: { tablerTheme: 'light' },
    });

    vm.runInNewContext(themeScriptSource, env.context);

    const body = env.getBody();
    assert.equal(env.root.getAttribute('data-bs-theme'), null);
    assert.equal(body.getAttribute('data-bs-theme'), null);

    let defaultPrevented = false;
    const linkElement = {
      getAttribute: (name) => (name === 'href' ? '?theme=dark' : null),
    };
    const clickEvent = {
      type: 'click',
      target: {
        closest: (selector) => (selector === 'a[href*="theme="]' ? linkElement : null),
      },
      preventDefault: () => { defaultPrevented = true; },
    };

    env.triggerEvent('click', clickEvent);

    assert.ok(defaultPrevented);
    assert.equal(env.localStorage.getItem('tablerTheme'), 'dark');
    assert.equal(env.root.getAttribute('data-bs-theme'), 'dark');
    assert.ok(env.root.classList.contains('theme-dark'));
    assert.equal(body.getAttribute('data-bs-theme'), 'dark');
    assert.ok(body.classList.contains('theme-dark'));
  });

  test('cleans ?theme= URL parameter and updates localStorage when arriving via URL', () => {
    const env = createMockEnvironment({
      url: 'http://localhost/SIGODT/view/giros_ciudadano/?theme=dark',
      search: '?theme=dark',
      localStorage: { tablerTheme: 'light' },
    });

    vm.runInNewContext(themeScriptSource, env.context);

    assert.equal(env.localStorage.getItem('tablerTheme'), 'dark');
    assert.equal(env.root.getAttribute('data-bs-theme'), 'dark');
    assert.ok(env.historyCalls.length > 0, 'history.replaceState should have been called');
    assert.equal(env.historyCalls[0].url, '/SIGODT/view/giros_ciudadano/');
  });

  test('programmatic window.tablerTheme API works as expected', () => {
    const env = createMockEnvironment({
      localStorage: { tablerTheme: 'light' },
    });

    vm.runInNewContext(themeScriptSource, env.context);
    const api = env.context.window.tablerTheme;

    assert.ok(api, 'tablerTheme API should be exposed on window');
    assert.equal(api.get(), 'light');

    api.toggle();
    assert.equal(api.get(), 'dark');
    assert.equal(env.root.getAttribute('data-bs-theme'), 'dark');
    assert.equal(env.localStorage.getItem('tablerTheme'), 'dark');

    api.toggle();
    assert.equal(api.get(), 'light');
    assert.equal(env.root.getAttribute('data-bs-theme'), null);
    assert.equal(env.localStorage.getItem('tablerTheme'), 'light');
  });

  test('minified script behaves identically', () => {
    const env = createMockEnvironment({
      url: 'http://localhost/SIGODT/view/giros_ciudadano/?theme=dark',
      search: '?theme=dark',
    });

    assert.doesNotThrow(() => {
      vm.runInNewContext(minScriptSource, env.context);
    });

    assert.equal(env.root.getAttribute('data-bs-theme'), 'dark');
    assert.ok(env.root.classList.contains('theme-dark'));
    assert.equal(env.localStorage.getItem('tablerTheme'), 'dark');

    env.context.window.tablerTheme.toggle();
    assert.equal(env.root.getAttribute('data-bs-theme'), null);
    assert.equal(env.root.classList.contains('theme-dark'), false);
    assert.equal(env.localStorage.getItem('tablerTheme'), 'light');
  });

  test('select2.css provides clear option visibility and dark mode parity with form-select', () => {
    const select2Css = fs.readFileSync(path.join(__dirname, '../view/html/global/css/select2.css'), 'utf8');

    // Dark mode dropdown background is dark (#182433), not white
    assert.match(select2Css, /\[data-bs-theme="dark"\]\s+\.select2-dropdown[\s\S]*?background-color:\s*#182433/);

    // Dark mode closed selector background is #151f2c (matching .form-select)
    assert.match(select2Css, /\[data-bs-theme="dark"\]\s+\.select2-container\s+\.select2-selection--single[\s\S]*?background-color:\s*#151f2c/);

    // Unhovered options in dark mode have bright white text (#fcfdfe), ensuring readability
    assert.match(select2Css, /\[data-bs-theme="dark"\]\s+\.select2-container--default\s+\.select2-results__option[\s\S]*?color:\s*#fcfdfe/);

    // Highlighted/hovered option is Tabler blue (#206bc4) with white text
    assert.match(select2Css, /\.select2-container--default\s+\.select2-results__option--highlighted[\s\S]*?background-color:\s*#206bc4/);

    // Search field inside dark dropdown has dark background and light text
    assert.match(select2Css, /\[data-bs-theme="dark"\]\s+\.select2-search--dropdown\s+\.select2-search__field[\s\S]*?background-color:\s*#151f2c/);
    assert.match(select2Css, /\[data-bs-theme="dark"\]\s+\.select2-search--dropdown\s+\.select2-search__field[\s\S]*?color:\s*#fcfdfe/);

    // Light mode has dark text for options (#182433)
    assert.match(select2Css, /\.select2-container--default\s+\.select2-results__option\s*\{[\s\S]*?color:\s*#182433/);
  });

  test('Breadcrumb.css provides white text for active section in dark mode', () => {
    const breadcrumbCss = fs.readFileSync(path.join(__dirname, '../public/css/Breadcrumb.css'), 'utf8');

    // Active breadcrumb item where user is located must be white in dark mode
    assert.match(breadcrumbCss, /\[data-bs-theme="dark"\]\s+\.breadcrumb-item\.active[\s\S]*?color:\s*#ffffff/);
    assert.match(breadcrumbCss, /\[data-bs-theme="dark"\]\s+\.breadcrumb\s+span[\s\S]*?color:\s*#ffffff/);
    assert.match(breadcrumbCss, /\[data-bs-theme="dark"\]\s+\.breadcrumb-item\[aria-current="page"\][\s\S]*?color:\s*#ffffff/);

    // Navigable links in dark mode are clear light blue (#4299e1)
    assert.match(breadcrumbCss, /\[data-bs-theme="dark"\]\s+\.breadcrumb\s+a[\s\S]*?color:\s*#4299e1/);

    // Dividers and icons in dark mode are clean #869ab8
    assert.match(breadcrumbCss, /\[data-bs-theme="dark"\]\s+\.breadcrumb-item\s*\+\s*\.breadcrumb-item::before[\s\S]*?color:\s*#869ab8/);
  });
});
