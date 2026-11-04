# Institutional shell

SIGODT reuses the visual geometry of sisIndicadores, not its business menu:

- A nonsticky blue header with an 80px minimum inner container plus 1px bottom border (81px desktop outer height), 44px white receipt icon tile, yellow SIGODT identity and blue user pill.
- Centered header, navigation and footer containers (maximum 1347px, 32px side padding), with a navigation-only exception at desktop widths ≥1400px (maximum 1320px, 16px side padding).
- Sticky white secondary navigation with rounded pale-blue active states and gray hover states.
- An 84px minimum blue footer retaining SIGODT's municipal logo, identity and version text.

`public/css/institutional-shell.css` loads after `municipal-theme.css`; its overrides are limited to the shared shell. Existing shared theme rules are unchanged. Bootstrap stacking remains below modal backdrops. At widths ≤768px the header stacks with 14px vertical padding, 14px gap and 20px side padding; the title stays visible. A second row keeps SIGODT's hamburger beside the profile. Pill identity remains visible from 576px; below that, escaped name/role are available inside the dropdown. Nested maintenance dropdowns remain in flow. Medium-width navigation can wrap.

## Height parity

SIINDI's `view/html/mainHead.php` defines an 80px minimum header container and a secondary `.mpch-subsys-nav-list` with `min-height: 52px`, `padding: 4px 0` and a 1px outer border. Its secondary wrapper is not a Tabler `.navbar` (`view/html/menu.php`). SIGODT retains that Bootstrap class for its existing controls, but it inherited Tabler's `min-height: 3.5rem` (56px at the default 16px root), making the single-row bar 3px taller than the reference's 53px.

The scoped secondary wrapper now resets that inherited minimum to zero and puts the reference's 52px minimum on the list. Its 40px links (8px vertical padding, 14.5px labels at the inherited 1.4285714286 line-height, 19px icons) fit within the list. No line-height override is needed. Tabler's brand padding also fits within the primary 80px container, so the primary header needs no change. There are no fixed heights or overflow caps: wrapped rows and expanded mobile menus can grow.

Evidence is deterministic CSS-cascade arithmetic, **not browser-computed geometry**: secondary before 56px, after 53px, reference 53px; desktop primary unchanged at 81px. The local Node environment could not resolve Playwright, Playwright Core, Playwright Test or Puppeteer; no browser measurements were obtained. The regression test checks these bounded rules using repository-local CSS only. SIGODT now follows SIINDI's stacked auto-height layout at `max-width: 768px`, with an added control row for its existing hamburger/profile actions, so equal mobile outer heights are not asserted. Browser checks of real desktop and mobile layouts remain necessary.

## Wide desktop navigation alignment

Only at viewport widths ≥1400px, `#navbar-menu .mpch-subsys-navbar > .container-xl` uses SIINDI's 1320px maximum width and 16px horizontal padding. Relative to the previous centered 1347px/32px container, the first link moves 2.5px left: `(1347 - 1320) / 2 + 16 - 32 = -2.5px`. This is CSS-derived arithmetic, not a browser measurement or a hardcoded offset.

Below 1400px the existing 1347px/32px base remains; the ≤768px 20px side-padding override is unchanged. The banner/header and footer do not receive this exception. Navigation height (53px for a single row), markup, links, role controls and mobile dropdown behavior are unchanged. Browser verification remains unavailable.

## Header/footer element parity

Reference sources: sisIndicadores `view/html/mainProfile.php`, `footer.php`, and `mainHead.php` (profile rules, footer rules and mobile block at line 2015). Its dropdown is 230px minimum, radius 12px, padding 6px, items 9px × 14px, shadow `0 10px 25px rgba(15,23,42,.15)`, divider margin 6px × 4px, and logout hover/focus `#fee2e2` / `#dc2626`.

SIGODT's earlier differences came from `municipal-theme.css` important dropdown declarations (240px/radius10/padding8) and Bootstrap footer columns stacking below 992px. Scoped `.app-global-header .muni-profile-dropdown` important overrides beat those earlier selectors, including logout icon hover/focus. Tabler `.icon`/`.icon-sm` CSS overrides SVG attributes: the shell explicitly pins the receipt icon to 1.25rem and chevron to 16px/stroke2.5, cancelling `ms-1`'s important margin. Profile metadata gains 4px right padding and role tracking .05em.

The footer uses flex until ≤768px, then a centered column/gap10 with padding18 vertically and20 horizontally. Desktop vertical padding is zero; municipal image is24×27 with contain; municipal text is14/600/20 with -.025em tracking and copy14/500/20 at .95 opacity. Assets and SIGODT copy are unchanged.

## Preserved business boundary

Session identity and avatar retrieval PHP are unchanged, including the database-backed missing-avatar lookup and guest fallback. All SIGODT links, role checks, active-page detection, logout/settings actions, collapse identifiers and nested Bootstrap dropend attributes are retained. No sisIndicadores application routes, permissions or JavaScript were copied. The reference repository was read-only.

## Checks

```sh
node --test tests/institutional-shell-elements.test.cjs tests/institutional-shell-height.test.cjs
php tests/institutional-shell_test.php
php tests/menu_test.php
node --test tests/*.test.cjs
php -l view/html/mainProfile.php
php -l view/html/menu.php
php -l view/html/footer.php
php -l view/html/mainHead.php
php -l tests/institutional-shell_test.php
```

Height-correction validation: the new cascade test first reproduced `56 !== 53`, then passed all 3 checks after the CSS change. Both PHP shell/menu tests passed. Continuation validation: element tests first failed all four requested rule groups, then all seven shell Node tests passed; PHP shell/menu tests and touched-file lint passed. The full Node suite passed 156/162 tests; the same six pre-existing failures remain in `procedure-area-selectors.test.cjs` (4) and `tributos-area-options.test.cjs` (2), with no new failures.

Generate an isolated administrator preview without credentials, real sessions or a database:

```sh
php tests/institutional-shell_test.php --preview > .playwright-mcp/institutional-shell-preview.html
```

Serve the project locally and open that preview to verify desktop/mobile profile dropdowns, collapse, maintenance flyouts, active/hover states and clipping. The preview uses synthetic identity data and root-relative assets; it does not verify authenticated routing or backend authorization.
