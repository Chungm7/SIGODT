const { test, describe } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');

describe('Hero Welcome Banner UI Standardization', () => {
  test('view/inicio/index.php displays greeting and user name on separate lines without raw br', () => {
    const html = readFileSync('view/inicio/index.php', 'utf8');
    assert.match(html, /class="d-block muni-welcome-greeting">¡Bienvenido al sistema,/);
    assert.match(html, /class="d-block muni-welcome-user">[\s\S]*?mb_strtoupper\(\$nombre_completo\)/);
    assert.match(html, /class="muni-welcome-role">[\s\S]*?mb_strtoupper\(\$perfil\)/);
    assert.doesNotMatch(html, /<h2 class="muni-welcome-title">[\s\S]*?<br>/);
  });

  test('view/inicio/index.php pushes the year box and divider to the far right', () => {
    const html = readFileSync('view/inicio/index.php', 'utf8');
    const css = readFileSync('public/css/institutional-home.css', 'utf8');
    assert.match(html, /class="home-welcome-copy"/);
    assert.match(html, /class="muni-welcome-year-box"/);
    assert.match(css, /\.institutional-home \.muni-welcome-hero\s*\{[^}]*justify-content:\s*space-between;/);
    assert.match(css, /\.institutional-home \.muni-welcome-year-box\s*\{[^}]*flex-shrink:\s*0;/);
  });

  test('public/css/municipal-theme.css defines right-aligned year box with divider', () => {
    const css = readFileSync('public/css/municipal-theme.css', 'utf8');
    assert.match(css, /\.muni-welcome-year-box\s*\{[\s\S]*?text-align:\s*right;/);
    assert.match(css, /\.muni-welcome-year-box\s*\{[\s\S]*?align-items:\s*flex-end;/);
    assert.match(css, /\.muni-welcome-year-box\s*\{[\s\S]*?border-left:\s*1px solid/);
  });
});
