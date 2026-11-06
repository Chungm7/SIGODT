# SIINDI backend transfer — pending independent verification

> **Historical modified-port record, superseded by restoration.** The original `seguridad/` template has been restored; the local changes and PASS claims below are not current integration acceptance or activation instructions. See [restoration, proposals and flow comparison](siindi-security-restoration-and-proposals.md).

The original module files now occupy their original paths. No replacement account API, service layer, sidecar, schema or JWT session was added. This continues the delivered contact/services slice; those files were not edited. **Do not activate until the institutional prerequisites below are independently verified.**

## Modified transfer manifest

Source root, read only: `/home/renzosandovalmio/Proyectos/sisIndicadores/`. All three source files were read in full before implementation. None of these three transfers is byte-identical: the security and SIGODT integration corrections are deliberate.

| Destination / original source path | Source SHA-256 | Adaptation |
| --- | --- | --- |
| `seguridad/2fa/models/TwoFactorModel.php` | `684676c69d18a2a0219eaabdc6994325e70218bf918e1ddebb990cdc8c1c343d` | Original class/static functions and institutional tables retained; extends Conectar for protected connection access. Missing DB/schema throws. Stages secrets in controller session rather than the source's destructive enrollment upsert. Conditional replay/enrollment UPDATE plus rowCount and integer matched-step bindings; persistent shared attempt counter. Historical disable/recovery-code methods explicitly deny. |
| `seguridad/2fa/controllers/TwoFactorController.php` | `df02477dc0a9eb0f092732da7cb49d2b1aa14ced36d28871a1dd177b3e640eda` | Original operation names and success/message/redirect response shape; PHP identity/CSRF replaces JWT/cookies. Preserves email120s/cooldown5 and original TOTP service calls. Rejects pending initial enrollment, wrong-purpose challenges and failed delivery/persistence. No codes/secrets in audit. |
| `controller/clave.php` | `2f3cca2abc25b60f0c6923e6c2a9522159bbd8f96f5a37b12798a4e44fafd163` | Source getSigurSystemToken and authenticated password POST flow stay inside the original controller. Strict HTTPS/timeouts/provider acknowledgement; server-owned doc/typeP. Unsafe legacy recovery/first-access operations deny and return existing entry rather than manufacturing a temporary grant. Regular success returns settings, not logout. |

Existing `models/Usuario.php` retains CAPTCHA → reCAPTCHA → SIGUR → central blanqueamiento → local active permission8 ordering. Local person lookup uses the verified login document, cross-checking provider pers_id when present; institutional usu_id is not assumed to be local pers_id. Existing history SQL, area synchronization call and session population moved into the single same-model `finalizarLogin2FA()` method. A failed history insert now denies promotion rather than continuing. Logout and unrelated user methods are untouched.

`tests/sigur_login_test.php` retains every prior full-session assertion, adds primary-pending checks and exercises finalization separately with a fixture-created server verification receipt. Real factor dispatch/finalization is exercised by `tests/siindi_login_integration_test.php`; the legacy fixture is not evidence of factor validation by itself. `tests/sigur_blanqueamiento_test.php` is unchanged.

New files: `tests/siindi_login_integration_test.php`, `tests/siindi_password_controller_test.php`, and this document.

## Frontend contract

Use the existing `SIGODT_SESSID` session and a random32bytehex `$_SESSION['csrf_token']`. All module/password operations below are **POST**, carrying `csrf_token`. There is no JWT auth cookie or new token endpoint. Browser document, institutional/local ID, recipient and account type never select the account.

Primary success redirects to `seguridad/2fa/views/selector_metodo.php` and sets only server pending identity:

```text
$_SESSION['2fa_pending_SIGODT']
  usu_id        institutional SIGUR ID
  usu_doc       verified login document
  usu_tipo      P
  usu_nom       queried given name
  usu_apep      queried paternal surname
  usu_apem      queried maternal surname
  expires_at    creation + 300 seconds (>= expiry rejects)
  pers_id, user_data, ip, challenge_id   private integration fields
```

No `usua_id_SIGODT`, role, history or area provision is assigned before valid email or already-enrolled TOTP. Completion rotates session ID and consumes pending/verification/challenge state. The full session retains existing local `usua_id_SIGODT` and adds distinct institutional `sigur_usuario_id_SIGODT`, plus actual queried `pers_apelpat_SIGODT`/`pers_apelmat_SIGODT`. No cargo is inferred from role.

| Source operation | POST fields besides CSRF | Authorized context / result |
| --- | --- | --- |
| `solicitar_codigo_correo`, `enviar_codigo_correo` | Optional `accion` matching server workflow | Pending: login challenge bound to pending challenge_id. Completed: profile `revinculacion` challenge. Recipient from institutional DB. Success keeps `correo_enmascarado`, `expira_seg=120`, message. Missing address keeps `sin_correo`. |
| `verificar_codigo_correo` | `codigo_correo`; optional matching `accion` | Server challenge owns purpose/doc/ID/expiry. Pending login success finalizes and redirects `view/inicio/`. Profile success only sets expiring `2fa_revinculacion_ok` and returns `revinculacion:true`. A rebind code cannot complete login. |
| `verificar_desafio` | `codigo_2fa` | Pending only, already enabled matching institutional enrollment. Original SHA1/6digit/30s, tolerance ±2. Atomic persisted matched step must succeed before finalization. Success redirects `view/inicio/`. |
| `iniciar_enrolamiento` | None | Completed only. Initial linking allowed. Active replacement also needs verified institutional email grant. Secret/QR staged in server `2fa_enrollment`, active DB secret unchanged. Original secret/secret_fmt/otp_uri/account/issuer response. |
| `confirmar_enrolamiento` | `codigo_confirmacion` | Completed + staged secret + unexpired replacement grant when applicable. Conditional replacement compares observed old secret/step and institutional ID; first confirmation persists its consumed step. Success redirects settings, never grants a pending login. |
| `desactivar_2fa` | None | Explicit unavailable failure; no fake disable or backup operation. |

`controller/clave.php` operations:

- `actualizar`: completed session with local ID, institutional ID and document; source `usu_pass_actual`, `usu_pass_nueva`, and CSRF. Confirmation remains frontend validation; no additional backend confirmation field. Success `{success:true,message,enlace:<settings>}`. Never logout.
- `blanqueamiento`, `token`, `validar_token`: explicit `{success:false,message,redirect:<existing index.php>}`. Ordinary/pending session is not a temporary reset credential. The existing index/central SIGUR blanqueamiento/public recovery route remains unchanged. The three copied views must guard absent dedicated context back to the existing root entry.
- `regresar`, `regresar_actualizar`: POST+CSRF; original success/enlace keys to index/settings respectively, without altering credentials or issuing grants.

## Activation prerequisites — names only

- Existing `APP_2FA_MASTER_KEY`: dedicated key of at least32 bytes; existing service retains SIINDI SHA256 derivation and AES-GCM envelope. Never populate from JWT fallback.
- New `APP_2FA_SHARED_WRITERS_READY`: defaults denied; **only string `1` enables module dispatch**. This is an operator attestation gate, not proof. Set only after independent verification that ALL institutional writers use compatible atomic consumed-step and enrollment semantics. Local SQL alone does not coordinate an unmodified shared SIINDI/SIGUR writer.
- Existing `SIGUR_URL`: HTTPS base including `/sigur/`; existing optional `SIGUR_AUTH_URL`, otherwise `<SIGUR_URL>/auth/`; existing `SIST_INIC`, `SIST_PASS`. No credential defaults for the password controller.
- Existing `public.tb_seguridad_usuario_2fa` and `public.tb_seguridad_auditoria_login_2fa` with original seus_/seal_ columns, unique document, correct institutional-ID mapping, actual encrypted-record/key compatibility and permissions. No schema/migrations were created or executed.

The safety tool denied reading `.env.example`, despite its declared edit authorization. It was not bypassed or edited; the parent must arrange the approved template-only update. No `.env` or real configuration values were read.

## Observed verification

All target execution used the mandated read-only, network-unshared bwrap mount, cleared environment, masked `.git`/`.env`, scratch-only fixture copies, wall30/CPU20/512MiB/process4096/fd64/file1MiB/scratch64MiB limits. The committed tests need neither Git history nor the external source checkout. Dummy provider/counters are not real services or PostgreSQL.

- Password RED: executable fixture exit255 because copied `controller/clave.php` was absent; GREEN: `Password controller: PASS` after transfer.
- Login RED assertion was observed before implementation, but the initial CAPTCHA fixture contained excluded digit1. Correcting it happened after implementation. **A clean, meaningful preimplementation login RED was therefore not established; do not report strict TDD compliance for that seam.** Subsequent GREEN and triangulation use valid CAPTCHA and the actual login/model/controller methods.
- `tests/siindi_login_integration_test.php`: PASS. Distinct900/42 IDs; pending-only primary success; CAPTCHA/reCAPTCHA/access/permission/central-blanqueamiento preservation; valid email and prior TOTP completion; session rotation/once-only promotion; wrong/expired/replayed/identity/CSRF/key/activation/DB/history/conditional-update failures; alternate matched-step integer persistence; staged/confirmed/raced/expired rebind; pending enrollment denial; real-mail acknowledgement/cooldown/120s challenge shape; unsupported disable.
- `tests/siindi_password_controller_test.php`: PASS. Actual public handler with dummy transport: owned identity/typeP, exact source password fields, POST/CSRF, no credential URL, strict provider JSON/HTTP/transport errors, TLS/timeouts and existing recovery denial.
- `tests/sigur_login_test.php`: PASS8/8 including all original completed-session assertions and the new pending selector redirect.
- `tests/sigur_blanqueamiento_test.php`: PASS5/5 unchanged.
- PHP `-l`: PASS for both copied 2FA files, copied password controller, Usuario model, both new tests and updated legacy login test.
- `git diff --check` on the owned tracked/integration files: PASS. Untracked files also passed syntax validation.
- Conditional sibling-flow baseline differential: N/A; no shared CAPTCHA/reCAPTCHA/login helper or sibling login flow changed.

## Self-review and limits

S1/S2/S4/S6: original module location, names, operations and institucional tables retained; only local guards, session transport and necessary security corrections changed. No account helpers, alternate controller, migrations, dependency changes, global auth changes or contact/services edits.

S3: mandatory pending gate and one model finalizer verified in isolated fixtures. S5: original three backend sources read fully; source hashes above. Frontend is separately owned and not verified here.

One self-review correction batch tightened password CSRF length and changed failed session-history persistence from source fail-open to denial; focused tests/lint and legacy login remained green. Test-fixture corrections (invalid CAPTCHA and pre-existing PHP8.4 nullable-syntax deprecations) are disclosed, not production-code changes. Fixture child suppresses only E_DEPRECATED for unchanged helpers; other warnings/errors fail its JSON/exit checks.

Independent Risk item2/security and item4/business-integration verification is still required. In particular:

1. Fixture PDO does **not** prove real PostgreSQL concurrency, row locking, uniqueness or coordination with other institutional writers. Do not enable the readiness gate from these tests alone.
2. Real SIGUR/local identity mapping, schema availability/key custody, live TLS/provider acknowledgements and real institutional mail delivery are unverified. No live DB/network/account was used.
3. Existing Area::sincronizar_area_defecto behavior is deliberately preserved, including its false return for both no-op and internal caught errors; this slice does not refactor it or make history/area/factor persistence one cross-connection transaction. Verify those business effects independently. Invalid factors are rejected before any history/area work.
4. Browser was already unavailable and not retried. Full-suite/frontend seam verification and native review remain parent-owned. No stage/commit/tracker/native action occurred.
5. `.env.example` template update and strict preimplementation login RED evidence remain incomplete; this is a partial handoff, not deployment approval.

## Targeted verifier correction: fresh authorization at MFA completion

Independent probe `mw2ryv2n-b-5vsw` found that the finalizer checked only existence of an active permission, then installed cached pending role8/depe92 despite current role2/depe93. This candidate-caused blocker is corrected in one bounded batch for S3/S4/S6; no controller, service, frontend, Area helper or schema change was made.

`finalizarLogin2FA()` now loads the complete current person/profile/department row using the existing institutional joins: `sc_escalafon.tb_persona`, `sc_seguridad.tb_permiso`, `sc_seguridad.tb_perfil`. It binds `:pers_id` as integer to the server-pending local person ID and `:dni` as string to its verified document. Filters remain local active person, `perm.sist_id = 8`, and `perm.perm_estado = 'A'`. The institutional SIGUR ID remains separately bound to the already-verified server factor receipt; it is never substituted for the local person ID. Fetched local ID/document are cross-checked before history, area or full-session assignment.

All subsequent business/session work uses the fetched row (`$userData = $current`), never pending cached authorization. Revocation/no matching person denies completion before history/area/session promotion. Legitimate factor consumption already persisted by the controller is deliberately retained on such denial; this correction does not undo replay protection or change factor/session distinctions.

Observed test-first evidence for **this correction only**:

- RED: valid primary CAPTCHA and valid public `verificar_desafio` TOTP, changed DB profile2/CONSULTA/depe93, exited255 on `Fresh permission must install role2/CONSULTA/depe93 and area42,93, not cached role8/depe92` before the model change.
- GREEN: same actual-handler integration suite passes after the minimal finalizer query/row-source correction.
- Triangulation: both email and TOTP completion use fresh role2/nameCONSULTA/depe93 and exact area call `[42,93]`; local42/institutional900 remain distinct. Revoked permission and wrong current local person produce no full session, history or area calls while preserving legitimate factor consumption. The fixture asserts trusted query bindings and active system8/profile joins.
- Preserved checks: mandatory-login/module integration PASS; unchanged legacy login8/8 and blanqueamiento5/5 PASS. Both changed PHP files pass lint. All target execution used the same mandatory OS sandbox described above. Password/contact/services were untouched and their separate fixtures were not rerun for this finalizer-only correction.

The meaningful RED/GREEN above does not retroactively repair the original port's missing preimplementation login RED. Independent targeted recheck remains required; no activation or live-concurrency/provider claim is made.
