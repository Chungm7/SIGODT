const { test } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync, existsSync } = require('node:fs');
const home = readFileSync('view/inicio/index.php', 'utf8');
const css = existsSync('public/css/institutional-home.css') ? readFileSync('public/css/institutional-home.css', 'utf8') : '';
function rule(selector, declarations, source = css) {
  const escaped = selector.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  const block = source.match(new RegExp(escaped + '\\s*\\{([^}]+)\\}'));
  assert.ok(block, `Missing rule: ${selector}`);
  for (const declaration of declarations) assert.ok(block[1].includes(declaration), `${selector}: ${declaration}`);
}
test('Inicio alone loads overrides after shared head and removes additive Bootstrap spacing', () => {
  assert.match(home, /mainHead\.php[\s\S]*institutional-home\.css/);
  assert.match(home, /class="page-body institutional-home-body"/);
  assert.match(home, /<main class="institutional-home">/);
  assert.doesNotMatch(home, /container-xl py-3|row-cards g-4 mt-1|row align-items-center g-4/);
});
test('desktop container, hero and year reproduce SIINDI source geometry', () => {
  rule('.institutional-home-body', ['margin: 0;', 'padding: 0;']);
  rule('.institutional-home', ['max-width: 1347px;', 'padding: 20px 32px 52px;']);
  rule('.institutional-home .muni-welcome-hero', ['padding: 40px;', 'gap: 40px;', 'margin-bottom: 40px;']);
  rule('.institutional-home .muni-welcome-title', ['font-size: 30px;', 'line-height: 36px;', 'letter-spacing: -0.025em;']);
  rule('.institutional-home .muni-welcome-subtitle', ['line-height: 24px;']);
  rule('.institutional-home .muni-welcome-year-box', ['padding-left: 32px;', 'border-left: 1px solid var(--muni-text-aux);', 'margin: 0;', 'min-width: 0;']);
  rule('.institutional-home .muni-welcome-year-value', ['font-size: 30px;', 'line-height: 36px;']);
});
test('two SIGODT modules use reference gaps and internals without importing subsystem metrics', () => {
  rule('.institutional-home .home-module-grid', ['grid-template-columns: repeat(2, minmax(0, 1fr));', 'gap: 36px;']);
  rule('.institutional-home .muni-module-card', ['padding: 20px;', 'gap: 16px;']);
  rule('.institutional-home .home-module-identity', ['gap: 12px;', 'min-width: 0;']);
  rule('.institutional-home .muni-module-icon svg', ['width: 25px;', 'height: 25px;']);
  rule('.institutional-home .muni-module-title', ['font-size: 19px;', 'line-height: 24px;', 'overflow-wrap: anywhere;']);
  rule('.institutional-home .btn-muni-card-action', ['min-height: 40px;', 'padding: 10px 16px;']);
  assert.doesNotMatch(home, /service-indicators|FUNCIONALIDADES|Pestañas/);
});
test('tablet and mobile match reference breakpoints, with no accumulated year spacing', () => {
  const tablet = css.slice(css.indexOf('@media (max-width: 1024px)'));
  rule('.institutional-home .home-module-grid', ['gap: 24px;'], tablet);
  rule('.institutional-home .muni-welcome-hero', ['padding: 32px;'], tablet);
  const mobile = css.slice(css.indexOf('@media (max-width: 768px)'));
  rule('.institutional-home', ['padding: 24px 16px 36px;'], mobile);
  rule('.institutional-home .muni-welcome-hero', ['flex-direction: column;', 'padding: 26px 20px;', 'gap: 20px;'], mobile);
  rule('.institutional-home .muni-welcome-year-box', ['border-left: none;', 'padding-top: 16px;', 'width: 100%;', 'margin-top: 0;'], mobile);
  rule('.institutional-home .home-module-grid', ['grid-template-columns: 1fr;', 'gap: 20px;'], mobile);
});
