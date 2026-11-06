# Restore the SIINDI security template; keep improvements as proposals

**The original template remains intact; the subsequently approved external integration passes isolated PHP and scoped independent security checks. Native acceptance and production activation are NOT complete.** Sixteen task-modified files under `seguridad/` were restored; all19 transferred files match SIINDI. Sections1–6 record that earlier restoration stage. Section7 describes the later, explicitly approved nine-file external adaptation. No host code changed in the restoration itself.

This is a restoration/proposal record, not an activation guide. Earlier `siindi-*-port.md` manifests describe the superseded modified port. Their historical PASS results do not establish current integration acceptance.

## 1. Restoration finding: Cargo was omitted (fixed later in S12)

SIINDI displays a field labelled **Cargo** for employee accounts, but its value is `perf_nombre` (profile name), not a demonstrated independent employment-position column:

- Original `view/setting/index.php:209–220`: employee field and `value=$userData->perf_nombre`; lines408–409 update it from `data.data.perf_nombre`.
- SIGODT `view/setting/index.php:17–23`: the local `$userData` object does not contain `perf_nombre`; its conditional field at200–211 therefore stays hidden.
- SIGODT `models/Usuario.php:153,211,267`: the authorized profile is queried and stored as `rol_nombre_SIGODT`.

Therefore this was an integration/display omission, **not proof that SIGODT lacks the datum**. Earlier claims that no mapping was available were too strong. Only DNI and Módulo/Sistema were requested exclusions. Replicating Cargo's source semantics would use the existing profile value; it must not invent a personnel-position column. No Cargo change was applied here because settings is outside the requested in-template rollback.

## 2. Restoration scope and preservation

Source: the existing read-only sibling `sisIndicadores/seguridad/` checkout. Its template Git status was clean; the original model/controller hashes matched the previously recorded transfer hashes. All target template files matched their staged preimages before replacement. The module did not exist at baseline `c6d6c5b`; its services were introduced by this task's commit `8f9d2ce1` and the remaining files by this task's staged port.

### Restored (16 files)

- `seguridad/2fa/{README,README_ESTRUCTURA}.md`
- `seguridad/2fa/controllers/TwoFactorController.php`
- `seguridad/2fa/models/TwoFactorModel.php`
- `seguridad/2fa/services/{TwoFactorService,SigurMailer}.php`
- `seguridad/2fa/assets/js/two_factor.js`
- `seguridad/2fa/views/{selector_metodo,desafio_login,enrolar_login,modal_enrolamiento,perfil_2fa,recuperar_acceso}.php`
- `seguridad/{actualizar_contrasena,primer_acceso,recuperar_contrasena}/views/index.php`

### Already identical; not rewritten (3 files)

`seguridad/2fa/services/SmtpMailer.php`, `seguridad/2fa/assets/css/two_factor.css`, `seguridad/2fa/assets/js/qrcode.min.js`.

### Explicitly preserved during restoration, before S12

`models/Usuario.php`, `controller/{usuario,clave}.php`, `index.php`, `view/setting/*`, shared visual assets, spinner, existing tests and configuration. Their existing port changes were not rolled back or reworked. Only the new parity test and explanatory/tracking documentation are outside-template additions/edits in this work unit.

Recovery evidence: pre-restoration staged tree `00d0eb14c523ed1652a831d712e537ce0ca92622` retains each old `seguridad/...` blob. This is evidence for selective recovery, not permission for a blanket restore/reset/clean. No database migration or stored-user-data rollback occurred.

## 3. Before and after: distinguish four states

The authentication baseline is the existing SIGODT implementation, not the rejected replacement Account/TwoFactor architecture. `c6d6c5b` restores the net application baseline after those foundations were removed. Git comparison from the parent of visual commit `a1e15aaa` through `c6d6c5b` shows no net changes in `index.php`, `models/Usuario.php`, `controller/usuario.php`, `config/jwt.php` or `config/conexion.php`. Visual standardization itself did not introduce the functional MFA gate.

| Flow | Before functional integration / after visual-only standardization | Modified SIINDI port, before this restoration | Restoration intermediate state, before S12 |
|---|---|---|---|
| **Login** | Existing CAPTCHA + reCAPTCHA → SIGUR → central blanqueamiento when required → local active system-8 permission → history, default area and completed `SIGODT_SESSID` → Inicio. No local second-factor stage. Root login design remained SIGODT's. | Same primary checks, with institutional/local identity separation, then a 300-second `2fa_pending_SIGODT` instead of a full session. Email or already-linked TOTP had to call `finalizarLogin2FA()` to create history/area/full session; authorization was refreshed there. | Host still creates that pending state, but the original selector expects `2fa_pending` JWT or `2fa_pending_user`. It does not recognize SIGODT's pending state and returns to login. Original JWT completion does not call SIGODT's finalizer. **Do not claim that login completes.** |
| **Forgotten password** | Root link goes to the existing central `sisSeguridad/view/USURecuperacionContra/index.php?sistema=siagth`. This was not a new local Reset implementation. | Root central link retained. Copied local recovery view was changed to redirect unconditionally; it was not equivalent to SIINDI's active local recovery workflow. | Root central link is still unchanged. The local view's original temporary JWT/blanqueamiento context, lookup and `auth/proxy.php` requests are restored, but the required host infrastructure is absent/incompatible. **Restored screen source does not mean active recovery.** |
| **First access / forced password reset** | SIGUR blanqueamiento response generated existing JWE/cookie and redirected to central `/sigur/view/clave/blanqueamiento.php` (1900-second central token). | Central route retained; copied local first-access screen always redirected to root. | Central host route remains intact. Original local screen/context is restored but still needs its host JWT/proxy. It is not newly activated by copying the file. |
| **Regular password update** | Settings was readonly and had no working source password/2FA controls introduced by visual standardization. Separately, legacy `controller/usuario.php?op=cambiar_contrasena` → `Usuario::cambiar_contrasena_API()` existed, constructing a central URL with password query parameters. Endpoint presence is not proof that the old UI used it successfully. | New source-shaped password screen used completed PHP session and POST/CSRF to `controller/clave.php?op=actualizar`, with source password fields; successful update returned to settings. Legacy operation was retained. | Restored original screen requires JWT and posts to `auth/proxy.php`, not the retained custom `controller/clave.php` contract. `auth/` is absent. **The new screen's regular update route is not operationally integrated.** Legacy and central host routes were not changed here. |
| **2FA** | No integrated local email/TOTP gate. Existing JWT was used for central blanqueamiento, not as a replacement for SIGODT's PHP session. | Source email/TOTP UI plus internally modified controller/model/services: initial enrollment during pending login denied, dedicated key/readiness gate, staged rebind, attempt/replay/audit changes. | Original source methods and first-enrollment-during-login behavior restored, as confirmed by the user. Original JWT/cookie/identity/DB assumptions are back. **Source behavior is restored; its end-to-end operation in SIGODT is not established.** |
| **Configuration** | Standardized visual cards remained readonly. | Source-shaped editable phone/personal email, institutional-email instructions, password and 2FA sections; DNI/Module omitted as requested, Cargo omitted without request. | Outside settings code is unchanged. Its embedded 2FA component now has original identity expectations; it may lack the identity supplied by the current host. Cargo remains omitted. |

### Evidence anchors

- Baseline `c6d6c5b:models/Usuario.php:112–124,140–220`: central reset branch, permission lookup, history/area/session and Inicio redirect.
- Baseline `c6d6c5b:index.php:122`: external recovery link; `c6d6c5b:models/Usuario.php:266–274` and `controller/usuario.php:36–42`: legacy password route.
- `docs/institutional-settings.md:19–23`: visual-only settings scope, no added password/2FA capabilities.
- Current `models/Usuario.php:171–188,192–282`: host pending identity and finalizer, unchanged by restoration.
- Original/current restored selector `seguridad/2fa/views/selector_metodo.php:16–33` and controller `:26–45,271–281`: expected pending context and JWT completion.
- Restored `seguridad/2fa/models/TwoFactorModel.php:17–24` vs `config/conexion.php:111`: public connection call versus protected host method.
- Original/restored password views: actualizar `:2–23,557`, primer acceso `:2–23,534`, recuperar `:25–50,605,693`: JWT/temporary contexts and proxy requests.

## 4. Proposals only — not implemented as local template changes

Restoring the source also restores its original implementation choices. The items below are recommendations to evaluate and, if approved, maintain in the source template as a reviewed version shared across systems. They are **not reasons to silently fork SIGODT's copy again**. Concrete exploitability and production impact have not been established by a live audit.

| Proposal | What was removed from the local template copy | Status / approval needed |
|---|---|---|
| **Dedicated crypto key and strict decoding/error handling** | Modified `TwoFactorService.php` required dedicated `APP_2FA_MASTER_KEY` ≥32 bytes, removed JWT/static fallback, checked encryption failures and strict Base64. | Proposal to the template owner. Existing-record/key compatibility must be established before any change. |
| **Verified transport and truthful mail results** | Modified `SigurMailer.php` imposed TLS verification/timeouts/provider acknowledgement, escaped interpolation and removed simulated success/detail leakage. | Proposal; original source mail behavior is restored. No real mail or TLS/provider validation was performed. |
| **Persistent replay/attempt safeguards** | Conditional consumed-step UPDATE/row count and shared counters changed model semantics. | Proposal; requires coordination with all writers, not a local-only assertion. No migration or live shared-writer proof. |
| **Staged Authenticator replacement** | Active secret was retained until new-device confirmation; verified-email rebind grant/purpose/expiry added. | Proposal; original enrollment/rebind behavior now retained. |
| **Audit minimization** | Removed codes/secrets from audit events. | Proposal to the template; do not log actual codes while evaluating. |
| **CSRF and server-owned identity** | PHP/CSRF/identity checks were introduced inside original controller/views. | Proposal for the common template; host integration requirements must be separated from changing the template's own API. |
| **New activation attestation** | `APP_2FA_SHARED_WRITERS_READY=1` was required inside the module dispatch. | **Withdrawn local gate**, not an original prerequisite. It was not a substitute for shared-writer verification and must not be reintroduced without approval. |
| **Denying first enrollment during login** | Modified controller forced email login first, then full-account enrollment. | **Rejected for this integration**: user confirmed preserving current original v2 first enrollment during login. Not a planned restriction. |
| **Legacy disable/recovery methods and redirects** | Internal methods were made unavailable and copied recovery/first-access entries unconditionally redirected. | Original files restored. Any removal/deprecation requires a separately approved template policy; do not equate copying dormant legacy helpers with enabling a v2 backup-code service. |
| **Rewrite template documentation/defaults** | Local README rewrites and selected defaults such as the 300-second host pending interval blurred source and destination contracts. | Original module README files restored. Keep project-specific integration records outside the template. |

Additional **outside-template** changes remain from the prior port: contact/password controllers use server-owned identity, POST/CSRF, transport/error validation and source field mapping; `Usuario` separates institutional/local IDs, defers session/history/area and refreshes authorization. These were not reverted by an instruction limited to the internal template. Their continued presence is not acceptance of the overall integration.

## 5. What source documentation actually authorizes/describes

- Original `seguridad/2fa/README.md:5,119–151`: copy module and add the login hook; its example pending token lasts600 seconds. The README calls the folder `modulo_2fa/`; the actual source is `seguridad/2fa/`. No second folder/version was invented.
- Same README `:122`: if sharing the existing PostgreSQL tables, **“¡No ejecutes nada!”** No SQL was executed here.
- Same README `:42–65`: source email challenge and first-enrollment behavior. The user accepted original v2 first enrollment during login.
- Original `doc/GUIA_ARQUITECTURA_MULTISISTEMAS.md:12–15`: host owns PHP/JWT/roles; adaptation at asset/API/DB boundaries, without altering integrated domain logic. This is a general host guide, not proof of direct compatibility with SIGODT.
- Original `doc/2FA/05_PLAN_MIGRACION_A_SIGUR.md:20,34`: “Copia Exacta”/“Copiar y pegar” refers to the cryptographic service in a future central migration, not proven compatibility of every host screen.
- Earlier optional-2FA/backup-code proposals, the v2 README and future central plan contain different policies/TTLs/key names. Do not silently combine them into a new policy. Preserve the actual current template; propose any exception explicitly.

## 6. Historical restoration-phase verification and blockers

All target execution used a read-only, network-unshared sandbox, cleared environment, masked `.env`, `.env.example` and `.git`, scratch-only temporary space and CPU/time/file/descriptor limits. No real users, DB/provider calls, SMTP, browser or production activation was used.

| Check | Observed result |
|---|---|
| New pinned template artifact test, before restoration | RED exit1:16 mismatching files |
| `php tests/siindi_template_parity_test.php`, after restoration | GREEN exit0:19 original files match |
| PHP parser on all restored/copied module PHP | PASS14/14 |
| Existing central `sigur_blanqueamiento_test.php` | PASS5/5, exit0 |
| Existing modified-port `siindi_login_integration_test.php` | FAIL exit255: `Changed active permission still permits factor completion` |
| Existing modified-port `siindi_views_port_test.php` | FAIL exit255: `Pending cannot render authenticated surface: seguridad/2fa/views/perfil_2fa.php` |
| Node `--check` on original `two_factor.js` | Not evaluated: exit133, V8 CodeRange reservation failure under512MiB virtual-memory cap; not a JS syntax verdict and not retried |
| Outside application-file preservation | Hashes unchanged after template replacement; docs/test/tracking intentionally updated separately |
| Independent security verification / native review | Independent S8/S9 restoration/documentation PASS:19/19 target/source/pinned hashes identical;16 differences from recovery tree; no extra application rollback or severe restoration-caused finding. Current S3 source enrollment restored, but integration BLOCKED. Native review remains unavailable and not approved |

The old integration tests describe the modified PHP contract. Their failures cannot be called PASS, dismissed as unrelated pre-existing failures, or fixed by changing the original template again. Static contract mismatches also independently establish that the remaining host integration is incomplete. No old tests were deleted/weakened to create a green result. Full UI/business suites and live activation checks are not claimed as run for this restoration.

### Historical implementation decision (subsequently approved in S12)

Keep `seguridad/` identical. Approve the minimum **external host integration** scope only after reviewing: pending JWT/cookie handoff, source JWT-to-SIGODT finalization with fresh system-8 authorization/history/area, connection contract and original proxy/password contexts. Cargo display mapping is a separate outside-template correction. This document describes missing contracts, not an already proven adapter or permission to replace SIGODT's architecture. Any indispensable internal exception needs its own explanation and explicit approval. Do not disable the mandatory factor, bypass CAPTCHA, change global auth/configuration, activate providers or deploy to make a test pass.

## 7. Approved external integration — current code stage

The nine-file outside-only adaptation is implemented. See [shared contract](siindi-external-integration-contract.md), [login evidence](siindi-external-login.md) and [password evidence](siindi-external-password.md).

| Flow | Current behavior verified in isolated fixtures |
|---|---|
| Login | Existing CAPTCHA/reCAPTCHA and SIGUR/local system8 checks → private challenge → original screens/controller → real JWE completion → correlated host landing → fresh authorization, history, area and isolated PHP session. No Cookie/dfa/iat-only promotion. |
| Original TOTP challenge | Identity-only pending cookie supports its cookie-only reader. Completion nonce stays server-only; producer ignores browser pending data. |
| First Authenticator setup | Allowed for eligible unlinked/no-record identity during pending login; confirmation belongs to the same initialized private challenge. |
| Existing linked factor | Primary credentials cannot overwrite it through enrollment. Existing factor must first complete authentication; full authenticated replacement retains original behavior. |
| Forgotten password | Local document/CAPTCHA/reCAPTCHA entry → original-protocol central lookup → private recovery context → original SIINDI recovery screen and exact proxy. |
| First access/reset | Authoritative login blanqueamiento or explicit central code validation → private first_access grant → original local screen → acknowledged password change → consumed grant/login. Ordinary or pending sessions alone cannot reset. |
| Regular update | Completed server identity and CSRF/Origin boundary → source password fields → acknowledged central update → settings, no logout. |
| Cargo | Existing rol_nombre_SIGODT supplied as source perf_nombre; no personnel column or privilege mapping invented. |

Final full isolated PHP regression:31/31 PASS. Shared connector/giro differential: base/current stdout, stderr and exit0 identical. Original19 source pins preserved.

Independent verification initially blocked H1: the positive enrollment fixture actually replaced an existing linked factor using primary-only authority. One bounded correction, entirely outside the template, now protects linked factors and binds initial confirmation to an eligible private challenge. The same verifier's H1-only recheck PASSED, preserving genuine first setup and full-account rebind. No new sweep or source-template fix was substituted.

Historical individual RED evidence is incomplete for some rules and was not recreated. Node/browser/live services/schema/key/shared-writer compatibility remain unverified. PHP CLI -n can emit unchanged-vendor JWE deprecations; no dependencies were changed. Native reviewer configuration has not been bypassed/retried; there is no native approval, production activation or deployment claim. SIGUR backend source is not required to port its existing SIINDI client.
