const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const read = file => fs.readFileSync(`${__dirname}/../${file}`, 'utf8');
const css = read('public/css/institutional-shell.css');
const profile = read('view/html/mainProfile.php');
const footer = read('view/html/footer.php');
// Source contracts only: intentionally not a browser/layout or full CSS engine.
function rule(selector, source = css.split('@media')[0]) {
  const blocks = [...source.replace(/\/\*[\s\S]*?\*\//g, '').matchAll(/([^{}]+)\{([^{}]*)\}/g)]
    .filter(m => m[1].split(',').some(s => s.trim() === selector));
  assert.ok(blocks.length, `Missing rule ${selector}`);
  return Object.fromEntries(blocks.map(m => m[2]).join(';').split(';').filter(s => s.includes(':')).map(s => {
    const i = s.indexOf(':'); return [s.slice(0, i).trim(), s.slice(i + 1).trim()];
  }));
}
function expectRule(selector, expected, source) {
  const actual = rule(selector, source);
  for (const [key, value] of Object.entries(expected)) assert.equal(actual[key], value, `${selector}: ${key}`);
}
test('only wide desktop navigation uses SIINDI container geometry', () => {
  const selector = '#navbar-menu .mpch-subsys-navbar > .container-xl';
  expectRule(selector, { 'max-width': '1347px', padding: '0 32px' });
  expectRule('.app-header-container', { 'max-width': '1347px', padding: '0 32px' });
  expectRule('.app-footer-container', { 'max-width': '1347px', padding: '0 32px' });
  const mobile = css.split('@media (max-width: 768px)')[1];
  expectRule(selector, { 'padding-left': '20px', 'padding-right': '20px' }, mobile);

  // This bounded media block must contain only the menu container, not the banner.
  const wide = css.match(/@media\s*\(min-width:\s*1400px\)\s*\{\s*(#navbar-menu \.mpch-subsys-navbar > \.container-xl\s*\{[^{}]*\})\s*\}/);
  assert.ok(wide, 'Missing navbar-only override at min-width: 1400px');
  assert.deepEqual(rule(selector, wide[1]), {
    'max-width': '1320px', 'padding-left': '16px', 'padding-right': '16px',
  });
  const containerRules = [...css.matchAll(/#navbar-menu \.mpch-subsys-navbar > \.container-xl\s*\{/g)];
  assert.equal(containerRules.length, 3, 'Only base, wide desktop and existing mobile container rules');
});
const dropdown = '.app-global-header .muni-profile-dropdown';
test('profile dropdown overrides municipal important geometry and interaction colors', () => {
  expectRule(dropdown, { 'min-width': '230px !important', 'border-radius': '12px !important', padding: '6px !important', 'box-shadow': '0 10px 25px rgba(15, 23, 42, .15) !important' });
  expectRule(`${dropdown} .dropdown-item`, { padding: '9px 14px !important', 'border-radius': '8px' });
  expectRule(`${dropdown} .dropdown-divider`, { margin: '6px 4px !important', 'border-top': '1px solid #e2e8f0 !important' });
  for (const state of ['hover', 'focus']) {
    expectRule(`${dropdown} .dropdown-item:${state}`, { 'background-color': '#e2e8f0 !important' });
    expectRule(`${dropdown} .dropdown-item.muni-item-danger:${state}`, { 'background-color': '#fee2e2 !important', color: '#dc2626 !important' });
    expectRule(`${dropdown} .dropdown-item.muni-item-danger:${state} .icon`, { stroke: '#dc2626 !important' });
  }
});
test('profile and brand pin effective Tabler geometry, not SVG attributes', () => {
  expectRule('.app-global-header .user-meta-info', { 'padding-right': '4px' });
  expectRule('.app-global-header .user-role-badge', { 'letter-spacing': '.05em' });
  expectRule('.app-global-header .user-chevron', { width: '16px', height: '16px', 'stroke-width': '2.5', 'margin-left': '0 !important' });
  expectRule('.app-global-header .header-icon-box .icon', { width: '1.25rem', height: '1.25rem' });
  assert.match(profile, /icon-tabler-receipt-2/);
});
test('footer uses reference flex geometry instead of Bootstrap 992px stacking', () => {
  assert.doesNotMatch(footer, /col-lg|class="row/);
  expectRule('.app-global-footer', { padding: '0' });
  expectRule('.app-global-footer .app-footer-container', { display: 'flex', 'justify-content': 'space-between' });
  expectRule('.app-global-footer .muni-footer-brand img', { width: '24px', height: '27px', 'object-fit': 'contain' });
  expectRule('.app-global-footer .muni-footer-brand', { 'font-size': '14px', 'font-weight': '600', 'line-height': '20px', 'letter-spacing': '-.025em' });
  expectRule('.app-global-footer .muni-footer-copy', { 'font-size': '14px', 'font-weight': '500', 'line-height': '20px', opacity: '.95' });
});
test('reference mobile stack keeps title, hamburger and accessible identity fallback', () => {
  const mobile = css.split('@media (max-width: 768px)')[1];
  assert.ok(mobile, 'Reference mobile breakpoint must include 768px');
  expectRule('.app-global-header', { height: 'auto', padding: '14px 0' }, mobile);
  expectRule('.app-header-container', { 'flex-direction': 'column', gap: '14px', padding: '0 20px', 'min-height': 'auto' }, mobile);
  expectRule('.app-global-footer', { padding: '18px 0' }, mobile);
  expectRule('.app-global-footer .app-footer-container', { 'flex-direction': 'column', gap: '10px', padding: '0 20px' }, mobile);
  assert.doesNotMatch(css, /\.header-brand-title[^{}]*\{[^}]*display:\s*none/);
  assert.match(profile, /user-meta-info d-none d-sm-flex/);
  assert.match(profile, /profile-mobile-identity[^"\n]*d-sm-none/);
  assert.match(profile, /data-bs-target="#navbar-menu"/);
  assert.match(profile, /aria-controls="navbar-menu"/);
});
