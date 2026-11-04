const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

const shell = fs.readFileSync(path.join(__dirname, '../public/css/institutional-shell.css'), 'utf8');
const tabler = fs.readFileSync(path.join(__dirname, '../public/css/tabler.css'), 'utf8');

// Deliberately bounded cascade check, not a browser/layout engine. These exact
// selectors are loaded in this order by mainHead.php; shell's ID wins over theme.
function declarations(css, selector) {
  const result = {};
  for (const match of css.split('@media')[0].replace(/\/\*[\s\S]*?\*\//g, '').matchAll(/([^{}]+)\{([^{}]*)\}/g)) {
    if (match[1].trim() !== selector) continue;
    for (const entry of match[2].split(';')) {
      const colon = entry.indexOf(':');
      if (colon !== -1) result[entry.slice(0, colon).trim()] = entry.slice(colon + 1).trim();
    }
  }
  return result;
}
function px(value) {
  assert.match(value, /^(?:0|\d+(?:\.\d+)?(?:px|rem))$/);
  return parseFloat(value) * (value.endsWith('rem') ? 16 : 1);
}
const nav = '#navbar-menu .mpch-subsys-navbar';

test('single-row secondary bar matches SIINDI: 52px list plus 1px border', () => {
  const outer = { ...declarations(tabler, '.navbar'), ...declarations(shell, nav) };
  const list = { ...declarations(tabler, '.navbar .navbar-nav'), ...declarations(shell, `${nav} .navbar-nav`) };
  const link = declarations(shell, `${nav} .navbar-nav .nav-item > .nav-link`);
  // At the inherited body line-height (1.4285714286), the 14.5px label
  // and 19px icon fit inside the 40px link including its 8px vertical padding.
  const naturalLink = Math.max(14.5 * 1.4285714286, 19) + 2 * 8;
  const listHeight = Math.max(px(list['min-height']), Math.max(px(link['min-height']), naturalLink) + 2 * 4);
  const barHeight = Math.max(px(outer['min-height']), listHeight + 1);
  assert.equal(barHeight, 53, 'Tabler navbar minimum must not enlarge the reference list + border');
  assert.equal(listHeight, 52, 'reference minimum belongs to the list, not a fixed outer height');
});

test('primary header retains the reference 80px container plus border', () => {
  const header = declarations(shell, '.app-global-header');
  const container = declarations(shell, '.app-header-container');
  assert.equal(Math.max(px(header['min-height']), px(container['min-height']) + 1), 81);
  assert.match(header['border-bottom'], /^1px solid /);
  assert.equal(header.padding, '0');
});

test('secondary navigation can grow for wrapped rows and expanded mobile dropdowns', () => {
  assert.equal(declarations(shell, `${nav} .navbar-nav`)['flex-wrap'], 'wrap');
  for (const selector of [nav, `${nav} .navbar-nav`, `${nav} .navbar-nav .nav-item > .nav-link`]) {
    const rule = declarations(shell, selector);
    for (const property of ['height', 'max-height', 'overflow', 'overflow-y']) {
      assert.equal(rule[property], undefined, `${selector} must not cap or clip menu content`);
    }
  }
  assert.match(shell, /position: static !important/);
});
