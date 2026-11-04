# Configuración: SIINDI visual alignment

`view/setting/index.php` now presents **CONFIGURACIÓN**, a Cuenta sidebar and three information cards. Only existing SIGODT capabilities are displayed.

## Source mapping (read-only reference)

Reference root: `/home/renzosandovalmio/Proyectos/sisIndicadores`.

| Reference | SIGODT adaptation |
| --- | --- |
| `view/setting/index.php`: sidebar and identity/contact/email cards | Same organization; full name + DNI in Mi Perfil, phone + role + module in Contacto y Cargo Institucional, existing email in Correo Electrónico |
| `view/html/mainHead.php:118–126` | Container max 1347px, padding 20px 32px 52px |
| `view/html/mainHead.php:825–997` | Panel min-height 580px; sidebar 224px; navigation 52px; content padding 28px/gap 24px; fields 42px with 48px icon prefix; 12px feedback |
| `view/html/mainHead.php:1073–1135` | 8px cards, 40px blue icon boxes, 15px headings, 12.5px subtitles, 20px bodies |
| `view/html/mainHead.php:1805–1850,1979–2002` | Breadcrumb/title, effective card-header padding 18px top/12px bottom, 5px institutional stripe (82% blue / 18% yellow) |
| `view/html/mainHead.php:2013–2040` | Sidebar stacks at 991px; container padding 24px 16px 36px at 768px; field columns stack below Bootstrap's 768px boundary |
| `view/setting/settings.js` and inline handlers | Inspected only; no script, endpoints or mutation controls copied |

## Preserved behavior and boundaries

The connection include, SIGODT session gate, all five session reads, trim/fallback expressions, six field IDs/labels/readonly semantics, escaped output, independent missing-contact conditions and unauthenticated redirect remain intact. Shared includes and destination links remain unchanged. No database/API/auth/permission changes.

No save, password, 2FA, avatar, Zimbra, email recovery, extra email fields or fake controls are introduced. Reference-required stars are omitted because these are readonly information fields, not a required submission form. Feedback is associated with its input via `aria-describedby`.

`public/css/institutional-settings.css` is loaded after the shared head only on this page. Every selector is rooted at `#institutional-settings`; old municipal settings classes and generic `.card`/`.card-header` selectors are avoided. Shared theme/head/shell remain outside this unit. Colors and font geometry follow reference source, with visible keyboard focus and reduced-motion support.

## Checks and pending proof

- `node --test tests/institutional-settings.test.cjs`: four source/structure/CSS isolation checks pass.
- `php tests/institutional-settings_test.php`: fallback, populated, escaped and independently missing contact render cases pass without loading config, shared includes, DB or a real session.
- `php -l view/setting/index.php` and `php -l tests/institutional-settings_test.php`: pass.
- `php tests/institutional-settings_test.php --preview`: generates `.playwright-mcp/institutional-settings-preview.html` with synthetic fallback data and local assets; no shared shell or real user data.
- `node --test tests/*.test.cjs`: 170 tests, 164 pass, six known baseline failures (four procedure-area-selectors, two tributos-area-options). No settings failures.

**Not browser proof:** source/cascade inspection and synthetic PHP rendering do not establish pixel parity or actual computed styles. Browser tooling is unavailable; desktop/mobile rendering, long-value readability and integration with the concurrent shared-shell changes still require browser review. The static preview is a convenience artifact, not a captured screenshot or browser verification.
