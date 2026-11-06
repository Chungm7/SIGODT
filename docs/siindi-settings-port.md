# SIINDI settings and seguridad UI transfer

> **Historical modified-port record, superseded by restoration.** The original `seguridad/` template has been restored; the local changes and PASS claims below are not current integration acceptance or activation instructions. See [restoration, proposals and flow comparison](siindi-security-restoration-and-proposals.md).

## Scope and provenance

Source root (read only): `/home/renzosandovalmio/Proyectos/sisIndicadores/`. Every corresponding source view, JS, CSS and spinner was read fully before transfer. The parent performed the literal native-write copies and verified all 15 byte-identical at copy time; subsequent changes below localize integration. No shell-based mutation, dependencies, global shell/theme/auth changes, contact/services edits, staging or commits were performed by the UI writer.

Source and destination paths are identical unless noted. SHA-256 values below are **source** hashes, not assertions that modified transfers remain identical.

| Path | Source SHA-256 | Destination treatment |
| --- | --- | --- |
| `seguridad/2fa/views/selector_metodo.php` | `823856f973fc1152138c52766800dad242c0538ba3aba4e7ade51f95b52a11a7` | Modified: pending PHP session/expiry, no document display, local assets, CSRF, first-enrollment explanation, real email/TOTP choice. Original cards/modal/timers/handlers retained. |
| `seguridad/2fa/views/desafio_login.php` | `ede6cc02207d17e975fc89e03c8f66724105810b0930072d19a217afc30f14bb` | Modified: pending previously-linked challenge vs completed rebind context, no document display/JWT, local assets, CSRF. Source email-recovery modal/handlers retained; enrollment modal included only for completed account. |
| `seguridad/2fa/views/enrolar_login.php` | `88a7328ac36b09cf2fe13f21e83b5b6bf8ca03a875cb0d1866c4ea64fb32d42e` | Modified: completed account only, pending goes to selector with explanation; source full book HTML/design/handlers retained, local assets/CSRF/text errors/busy guard. |
| `seguridad/2fa/views/modal_enrolamiento.php` | `78f7ecf5f13e8b59b6723b0ad41117d1d594b1fb67db890b377e5a2ddc0dec2c` | Modified: authenticated guard, local logo, CSRF, DOM-ready load ordering, busy guards, text-only errors and secret clearing on close. Original horizontal modal retained. |
| `seguridad/2fa/views/perfil_2fa.php` | `1d17b73dbac7152b00064ba6f8390b02d95e2ede81e16ad2a8f6a6fe8c516ea3` | Modified: completed session ownership, source status/activation/modal, active real email-rebind link; remove commented dormant disable action. |
| `seguridad/2fa/views/recuperar_acceso.php` | `09243e27dbf607ea2309787891b430d4ab1a04099c8be1b84258bab7cb0c96e8` | Modified exception: historical nonfunctional backup-code page becomes redirect to real selector/settings/entry. No fake backup controls. |
| `seguridad/2fa/assets/css/two_factor.css` | `be4c55326257eb43140db2318b206b6900d3f732202469c7ae3a73c219663099` | **Unchanged exact copy**, including original comments and mobile book styles. |
| `seguridad/2fa/assets/js/qrcode.min.js` | `c541ef06327885a8415bca8df6071e14189b4855336def4f36db54bde8484f36` | **Unchanged exact copy**; no remote QR, installation, minification or license/comment removal. Available source asset has no separate license header. |
| `seguridad/2fa/assets/js/two_factor.js` | `869c14a43be882d7aaf106970e3a42f17f218546b5dc0d2e3e0494cf5041cdd6` | Modified: source challenge/QR/clipboard helpers retained, CSRF and duplicate-submit suppression, upstream textContent; historical unused backup-code handlers removed. |
| `seguridad/actualizar_contrasena/views/index.php` | `5beb844e3a1b2c63d9b4c9b92cc09ccd57cfd0630b8f5b987a002ad2cf606ff3` | Modified: source full HTML/styles/rules/visibility/submit handlers, completed guard, direct password POST+CSRF and busy suppression, local fully-based assets. |
| `seguridad/primer_acceso/views/index.php` | `2576b0e0a963108e1e9892b59b5a29f338963f2ccd2dd332e2448387f955e36f` | Modified: source full HTML/styles/handlers retained but entry fails closed to central index; no local temporary reset authority. Direct controller fields/CSRF retained for provenance, not an active public flow. |
| `seguridad/recuperar_contrasena/views/index.php` | `a8b670f5174cc102fb0fea3be432bd1445c44c46cc6f63ce1781a7516c142219` | Modified: source full HTML/styles/handlers retained behind central-entry redirect; remove JWT and unreachable direct DB lookup, local assets/direct controller/CSRF. No new public recovery API. |
| `view/setting/index.php` | `c76ed3abcbb3767475a99856407d81ec59451b15dc8e3209ebfd178a36def07e` | Modified: original five-block markup in existing SIGODT wrapper/head/profile/menu/footer/mainjs. Real separate name session fields; phone/personal email editable, institutional email readonly/Zimbra, password link and source 2FA profile. No document/Module display. Cargo conditional only for a real field (not synthesized from role; current session supplies none). Inline source contact handlers localized/moved into settings.js. |
| `view/setting/settings.js` | `1bd3541025b5d56fc5655bc8f6cb8c93ee626b76a0a9abd055594b4054610aad` | Modified: original Zimbra manual instructions, without document reference. Original settings contact operations/IDs/feedback now direct POST+CSRF, text-only provider strings, busy/error recovery. |
| `public/componente-spinner/spinner.php` | `8f2ff29435aac277eb29dce21657d1fa50377e193945448603b6e3c5ae014897` | **Unchanged exact copy**, required by source standalone views; shared `view/html/spinner.php` untouched. |
| `view/html/mainHead.php` → `public/css/institutional-settings.css` | `27c8edfbd302191dad0d51881f658b3698f899084f8f37d965338963c869568c` | Source lines 825–1183 + status pill 651–673 + necessary token values adapted under `#institutional-settings`; existing matching page geometry/spacing/mobile parity retained. Shared head itself not copied or edited. |
| `seguridad/2fa/README.md` | `41959a8b3702618e06d7932a65f2f25a2df3b4ec00e71ea446fc249313b1ae12` | Sanitized source-aligned integration documentation, not literal copy: excludes unsafe configuration examples, JWT/first-enrollment bypass, simulated delivery, raw OTP audit and destructive SQL advice. |
| `seguridad/2fa/README_ESTRUCTURA.md` | `3071591b2a8491d6ed5cff145a1baee9ac8c9f61d4ad331494f56702d7b325a6` | Sanitized original organization guide with actual integration paths/policy, without obsolete hook/SQL/credential instructions. |

New UI-specific files: `tests/siindi_settings_port.test.cjs`, `tests/siindi_views_port_test.php`, this document. Updated existing tests: `tests/institutional-settings.test.cjs`, `tests/institutional-settings_test.php` now assert approved actual fields/actions, not the superseded readonly-six-field design.

## Exact HTTP/backend requirements

The final backend contract was read in `docs/siindi-backend-port.md`; the completed contact slice remains in `docs/siindi-module-port.md`.

- Contact: `controller/usuario.php?op=mostrar_datos|update_telefono|update_emailp`, **POST** `csrf_token`; updates carry `dato`. Successful data uses `usu_celu01`, `usu_emailp`, `usu_emaili`; no browser document/account type or source proxy `api:` envelope.
- Password: `controller/clave.php?op=actualizar`, POST `csrf_token`, `usu_pass_actual`, `usu_pass_nueva`; source success/message/enlace. Confirmation is client validation; no extra backend confirmation field required.
- 2FA: direct `seguridad/2fa/controllers/TwoFactorController.php`. Source operations/fields preserved: `solicitar_codigo_correo`, `verificar_codigo_correo(codigo_correo,accion)`, `verificar_desafio(codigo_2fa)`, `iniciar_enrolamiento`, `confirmar_enrolamiento(codigo_confirmacion)`. All POST+CSRF. Completed rebind uses `accion=revinculacion`; backend owns challenge purpose, grant and identity. Response keeps `success/message/redirect`, masked email/expiry and authenticated enrollment `secret/secret_fmt/otp_uri`.
- Pending: top-level `2fa_pending_SIGODT` with institutional `usu_id`, `usu_doc`, names and `expires_at`, never privileged shell/profile. Expired pending returns existing entry. Initial pending enrollment redirects selector and explains email-first linking.
- Full: existing `usua_id_SIGODT`, server document, separate real name fields and distinct `sigur_usuario_id_SIGODT`. The UI does not split a combined surname, map role to cargo, invent a DB column or choose the account by posted identity.
- First-access/recovery: no dedicated local temporary context is issued by the agreed backend; copied entries intentionally redirect existing root. Central SIGUR blanqueamiento/public recovery routes, index/config/JWT and business logic were not edited.

Backend activation requires the dedicated key and operator-attested `APP_2FA_SHARED_WRITERS_READY=1`, plus independently verified institutional schema/writer compatibility. UI does not create or bypass these gates. `.env`/`.env.example` were not read or edited by this writer.

## Verification and limits

Tests run in the required read-only/no-network bwrap sandbox with cleared environment, masked `.env`/Git/browser artifacts, scratch64MiB, wall30/CPU20/process4096/fd64/file1MiB limits. PHP AS512MiB; Node AS8GiB with heap512MiB and `--jitless`.

- RED observed: Node new source-module tests failed2/2 with ENOENT for not-yet-copied selector/JS; PHP render fixture failed255 for not-yet-copied profile. No production implementation preceded those observations.
- Focused GREEN: Node **7/7** organization/direct-contract/executable JS/XSS/duplicate-submit/CSS tests and both PHP sanitized pending/full/expired/escape/render fixtures **PASS**. One added JS syntax triangulation initially misparsed `?>` inside a script src attribute; its extraction now substitutes PHP before parsing tags. This was a test-only correction; no runtime JS defect was hidden. Source-frozen hashes independently protect the exact QR/CSS copies without a source-checkout or Git-history test dependency.
- Template fixtures replace config/model/shared includes with stable test doubles and synthetic sessions; they never inspect environment secrets, use live DB/provider accounts or authenticate real users.
- Focused PHP lint: **13/13 PASS**, covering all owned PHP views/spinner/tests. Parent owns final full-suite and independent security/native review.
- **Not browser proof:** browser was already unavailable and was not retried/installed. Actual visual/mobile rendering, QR camera scanning, live SIGUR/TLS/mail, shared PostgreSQL races/key custody and institutional deployment are unverified. These are activation/review obligations, not silently simulated success.

Risk item2 review focus: fail-closed pending/full/reset guards, no privileged pending shell, authenticated-only QR/secret visibility and clearing, purpose-bound email-rebind, direct POST/CSRF and text-only external feedback. Preserve source design/operations while reviewing these localized seams.
