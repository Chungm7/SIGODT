# SIINDI module transfer: contact and source services (partial)

> **Historical modified-port record, superseded by restoration.** The original `seguridad/` template has been restored; the local changes and PASS claims below are not current integration acceptance or activation instructions. See [restoration, proposals and flow comparison](siindi-security-restoration-and-proposals.md).

**Historical contact/services slice record.** The sections below describe the first delivered slice, not the current whole-feature state. The subsequent model/controller/login transfer is documented in `docs/siindi-backend-port.md`; original views/settings are being adapted in parallel. Independent backend verification confirmed one stale-authorization finalization defect; the bounded correction and targeted independent recheck now PASS. Scoped UI verification also PASS. Combined isolated checks passed all 13 PHP suites; Node passed 168/174, with only the six previously confirmed unrelated selector failures. Production activation and rendered-browser acceptance are not approved. Historical statements below such as “login untouched” or “S3 unimplemented” apply only to this initial slice.

## Source and manifest

Read-only source: `sisIndicadores/controller/usuario.php` (the sibling SIINDI checkout).

| Destination | Transfer | Localized changes |
| --- | --- | --- |
| `controller/usuario.php` | Modified transfer of source cases `mostrar_datos`, `update_telefono`, `update_emailp` | Retains source system-token request, `ws/mostrar.php`, `ws/actualizar.php`, `tipo_cambio` 1/2, and form POST contract. Uses the destination completed PHP session for identity. Adds POST/CSRF/input validation, verified HTTPS, bounded timeouts, strict provider errors, response allowlist and `usu_emails` → `usu_emaili` alias correction. Consolidates repeated cURL plumbing inside this controller and removes the duplicate provider executions. Existing administrative cases remain unchanged. |
| `seguridad/2fa/services/SmtpMailer.php` | Unchanged source copy | Byte-identical compatibility wrapper; source comparison confirms equality. |
| `seguridad/2fa/services/SigurMailer.php` | Modified source copy | Preserves all functions and both institutional HTML templates. Adapts destination SIGUR base/auth URL semantics, retains observed multipart `ws/correo-ss.php`, requires verified HTTPS/timeouts/provider acknowledgement, removes simulated success and detailed errors/logs, escapes template interpolation. |
| `seguridad/2fa/services/TwoFactorService.php` | Modified source copy | Preserves source functions, RFC 6238 parameters, SHA-256 key derivation and AES-GCM envelope/legacy decryption compatibility. Requires dedicated `APP_2FA_MASTER_KEY` of at least 32 bytes without JWT/static fallback, checks encryption errors, uses strict Base64 decoding. No key values added. |
| `tests/siindi_module_port_test.php` | New isolated executable tests | Exercises real controller dispatch and original mail wrapper with dummy configuration/models/cURL in scratch-only child processes, plus actual PHP crypto operations. No Git history, source checkout or external provider is required. |
| `docs/siindi-module-port.md` | New integration record | Partial manifest, caller contract and remaining work. |

Unchanged direct-copy path: `seguridad/2fa/services/SmtpMailer.php`. The source services are copied in their original directory/names, not replaced by a new core. No settings view, global configuration, model, dependency or schema has been modified in this slice. The source's dormant recovery-code helper functions are retained to preserve the source service, but no backup-code or disable endpoint/UI is introduced.

## Caller contract

The future transferred settings view must initialize a cryptographically random session `csrf_token` and send it as POST `csrf_token` for these three source operations. This slice does not add a token endpoint or alter the current settings screen.

- A completed session needs `usua_id_SIGODT` and `usua_dni_SIGODT`. Pending 2FA alone is denied.
- Posted `usu_doc`, `usu_tipo` and local IDs are ignored. SIGODT's employee account type is `P`.
- `update_telefono`: POST `dato`, exactly nine ASCII digits, preserving the source UI's length contract.
- `update_emailp`: POST `dato`, valid personal email, at most 254 bytes.
- Successful responses expose only `usu_celu01`, `usu_emailp`, `usu_emaili`. No role is mapped to a job title.
- Provider base uses existing `SIGUR_URL`; auth uses `SIGUR_AUTH_URL` or `<SIGUR_URL>/auth/`. Both must be HTTPS. System credentials use existing `SIST_INIC` and `SIST_PASS`, without embedded defaults.
- Transport/provider failures return a generic error and never simulated success. The three operations do not change local session state or local database data.

## Evidence and limits

The executable fixture covers owned identity despite malicious posted identity, authenticated/pending/anonymous contexts, POST and CSRF rejection before provider calls, malformed contact input, exactly one token request and one provider call, TLS and timeout options, malformed/denied/HTTP/transport failures, response filtering, the source institutional-email alias, and existing `listar_sistemas` dispatch. Rejections preserve the session exactly.

Service fixtures also cover both source mail entry points, actual delivery-stage as well as auth-stage failures, the exact multipart endpoint/fields, escaped display name, RFC SHA1 six-digit vector, source secret length, consumed/expired TOTP rejection, missing dedicated key despite JWT availability, GCM roundtrip/envelope/tampering/wrong-key rejection, and local otpauth URI. The service's in-memory last-step comparison does **not** prove persisted atomic replay protection; the model/controller work is still outstanding.

This is not browser or real-provider proof. Dummy cURL functions establish request construction, not certificate validity or live response compatibility. The OS sandbox denies external networking and masks `.env` and Git metadata. Shared SIGUR/local identity mapping remains unproven outside these fixtures.

## Remaining T7 work

No S1–S6 is fully closed by this slice. S1/S2/S4/S6 have only the direct source contact seam prepared. S3 remains unimplemented: the unchanged login still completes a session after primary credentials.

The source module model/controller/views/assets, five-block settings transfer and scoped source CSS, source password screens/controller, same-model mandatory-login finalizer, expiring pending identity, email/TOTP attempt accounting, atomic replay checks (including enrollment), safe staged rebinding, audit redaction, environment-name documentation, and their executable integration tests are all outstanding. The dedicated-key requirement is implemented in the copied service but is not integrated into an active login gate. Do not deploy this slice as the complete account/2FA feature.

### Next contiguous source transfer

Resume with these exact untouched source → destination paths (source prefix `/home/renzosandovalmio/Proyectos/sisIndicadores/`):

1. `seguridad/2fa/models/TwoFactorModel.php` → same path: protected destination connection, explicit failure, same-account counters, conditional step UPDATE plus rowCount, staged enrollment preserving active secret.
2. `seguridad/2fa/controllers/TwoFactorController.php` → same path: source operation names, destination PHP identity/CSRF, purpose-bound expiry, redacted audit and no enrollment login bypass.
3. `seguridad/2fa/views/{selector_metodo,desafio_login,enrolar_login,modal_enrolamiento,perfil_2fa,recuperar_acceso}.php` → same individual filenames; read every full source view before transfer. Selector/challenge guards must match pending vs completed session contexts.
4. `seguridad/2fa/assets/css/two_factor.css`, `seguridad/2fa/assets/js/{qrcode.min,two_factor}.js` → same individual paths. QR asset must remain byte-identical.
5. `seguridad/2fa/{README,README_ESTRUCTURA}.md` → same individual paths with localized integration/security corrections, not historical backup-code policy.

The coupled destination login seam remains **untouched** in `models/Usuario.php`; add its public behavior fixtures in `tests/siindi_login_integration_test.php` before editing it. Preserve and meaningfully update `tests/sigur_login_test.php` and `tests/sigur_blanqueamiento_test.php` only when the approved mandatory-gate redirect changes. No baseline differential was needed for the current slice.

After that coupled seam, untouched source → destination paths are `controller/clave.php`, `seguridad/{actualizar_contrasena,primer_acceso,recuperar_contrasena}/views/index.php`, `view/setting/index.php`, `view/setting/settings.js`. Source CSS is `view/html/mainHead.php` lines 825–1183 plus effective tokens → destination `public/css/institutional-settings.css`; do not copy global head. `public/componente-spinner/spinner.php` is permitted only if the standalone views require its source include. Remaining destination tests are `tests/siindi_settings_port.test.cjs`, `tests/institutional-settings.test.cjs`, `tests/institutional-settings_test.php`. `.env.example` still needs names-only configuration documentation, without reading `.env`.

No shared login helper changed, so a baseline differential of unrequested sibling login flows is not applicable to this slice. Existing SIGUR login/blanqueamiento tests remain untouched. Full-suite validation is deferred to the final integration seam, as requested.

Activation must still prove institutional table availability, shared identity/key compatibility, verified TLS and real institutional delivery without migrations, real-user access or secret inspection by the writer.
