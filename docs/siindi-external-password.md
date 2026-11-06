# Local SIINDI password entries

T7/T12 · S3, S4, S8, S10–S12 · Risk item 2 (authentication).

## Delivered boundary

The original `seguridad/` screens remain unchanged. The existing PHP session bridge, central JWE implementation and configuration remain parent-owned. This is a compatibility client, not a new authentication core or local OTP authority.

- `index.php?recuperar=si` collects the document using the existing login shell, image CAPTCHA and optional reCAPTCHA scripts. The regular login field names, labels and script behavior are retained. Its POST dispatches to `models/Reset.php`.
- `models/Reset.php` proves configured same-origin POST before provider access. It validates the document and existing CAPTCHA/reCAPTCHA, obtains a system token, then POSTs `usu_doc`/`usu_tipo=P` to `sigur/ws/reset.php`, as the source procedural client does. Returned identity must match the document. A rejected lookup preserves previous authority; only a positive lookup consumes CAPTCHA, calls `clearLogin()` and starts a separate `recovery` context. This avoids an older pending-login cleanup erasing a newer reset context.
- `auth/proxy.php` accepts only four exact POST selectors: `clave.php?op=actualizar`, `token`, `validar_token`, `blanqueamiento` (each with the complete `clave.php?op=` prefix). Origin is checked before authority lookup or CSRF forwarding. Paths, URLs, extra query fields, refresh and legacy navigation selectors are not relayed. The original screens navigate back with ordinary links or close their modal; no new cancel endpoint is needed.
- `controller/clave.php` retains its direct POST + CSRF guard. It uses the bridge's private identity, never posted document/type. `token` requires recovery/recovery_sent and preserves source methods `1`/`2`; explicit acknowledged sending advances recovery only once. `validar_token` requires recovery_sent and a six-character alphanumeric code. Explicit acknowledgement advances to first_access, never full login. `blanqueamiento` requires first_access and the source `pers_pass_nueva` field. Only acknowledged success consumes the grant and returns the local login.
- Regular `actualizar` still requires completed PHP-owned institutional/local identity, `usu_pass_actual`/`usu_pass_nueva`, verified bounded transport and strict boolean provider success. It returns settings without logout. Failed temporary operations leave the stored grant unchanged.
- Configured provider host roots and existing `/sigur/` bases normalize to one `/sigur/`; credentials remain in POST bodies, not URLs. Transport is HTTPS-only, no redirects, TLS peer/host verification, finite connection/request limits. No credentials or provider errors are echoed.
- Cargo now displays the existing `rol_nombre_SIGODT` as source `perf_nombre`, escaped. No database column, privilege mapping, DNI or Module display was added. Settings JavaScript is untouched.

## Acknowledgements and limits

Temporary operations normalize only explicit original-client values: `success` true/`"true"`/1/`"1"`, or, when success is absent, `status` 200/`"200"`/true. Explicit failure takes precedence. Unstructured text and empty/ambiguous HTTP200 bodies never grant reset authority. Unlike the permissive source fallback, HTTP status alone is not OTP proof. Lookup identity data is the original reset client's positive result, with added account/type correlation at the host boundary.

Method2 is copied from the source; its actual delivery semantics are not established by these fixtures. Central backend code is unavailable but is not required to port its observed client. No live provider, mail, database, browser, key interoperability or deployment proof is claimed. Original plaintext audit/key/mail choices remain proposal-only. Authentication completion/fresh system8 role checks belong to the independent login unit and frozen bridge, not this unit.

## Verification evidence

All target execution used a foreground 30-second `bwrap --unshare-all` read-only mount, cleared environment, masked project `.env`, `.env.example`, `.git`, and scratch `/tmp` fixtures. Fake cURL, JWE and DB replace external effects. Child fixtures assert process exit0, empty stderr and a structured stdout envelope, then status/body/session/request-count invariants. Source views execute unchanged in that fixture through real bridge routing.

Initial RED: `tests/siindi_external_password_test.php` exited255 because the public `auth/proxy.php` was absent. After wiring, the same focused suite passed. Self-review then added a behavior-level rejection for lookup data accompanied by explicit `status:false`: RED exit255 (`explicit lookup denial overrides identity data`), followed by GREEN exit0 after making explicit status denial override returned identity, preserving the prior session on rejection. This is aggregate endpoint RED evidence, not separately observed pre-implementation RED for every individual rule. Additional root/view cases initially exposed fixture defects (relative cwd, absent fake DB and source environment label); these were corrected in fixtures, not by changing source templates or weakening rejection assertions. They are not claimed as production RED evidence.

GREEN: sandbox `php -n tests/siindi_password_controller_test.php` reports `External password entries: PASS` and `Password controller: PASS`. It includes direct/proxy CSRF, full-session identity/no logout, Origin/method/selector/input/stage rejections, send/resend, explicit success variants, ambiguous replies, failures retaining grants, consumed success, local root initiation, account mismatch, lifecycle replacement and original recovery/first-access view guards. Settings-only synthetic renderer passes escaped session fields and Cargo. Original19-file pin test passes.

Node is unavailable under the previously invalid512MiB V8 configuration; it was not retried. Node test expectations were aligned to actual unchanged source/proxy behavior rather than invented direct-controller CSRF or duplicate-submit modifications inside the template. No Node execution result is claimed.

## Self-review verdict

- S3: scoped PASS — recovery/first-access cannot produce a full login or bypass mandatory2FA. Login-factor completion remains the other unit's responsibility.
- S4: scoped PASS — existing regular password fields, security transport and settings/no-logout behavior retained; no business-model/config/shared-helper edits.
- S8: PASS — no module/source writes; original19 pins pass.
- S10: scoped PASS — local initiation, original recovery and first-access screens are the user entries, with server-stage guards.
- S11: scoped PASS — original system-token/POST reset and password protocol reused; central authority remains external, live proof absent.
- S12: scoped PASS — only assigned outside runtime files plus tests/docs changed; no commits or activation.

Final cross-unit/native acceptance remains parent-owned. Per-rule independent RED evidence, Node execution and real integration/browser checks remain limitations, not fabricated PASS claims.

### Exact focused commands and exits

The first command was observed RED exit255 (missing public proxy), then GREEN exit0; the later explicit-lookup-denial regression was observed RED255 then GREEN0 with this same command. Its final expanded suite exits0. The other commands below exit0. All were foreground executions with a 30-second tool timeout as well as `timeout 30s`.

```sh
timeout 30s bwrap --unshare-all --die-with-parent --ro-bind / / --dev /dev --proc /proc --tmpfs /tmp --ro-bind /dev/null /home/renzosandovalmio/Proyectos/SIGODT/.env --ro-bind /dev/null /home/renzosandovalmio/Proyectos/SIGODT/.env.example --tmpfs /home/renzosandovalmio/Proyectos/SIGODT/.git --clearenv --setenv PATH /usr/bin:/bin php -n tests/siindi_external_password_test.php

timeout 30s bwrap --unshare-all --die-with-parent --ro-bind / / --dev /dev --proc /proc --tmpfs /tmp --ro-bind /dev/null /home/renzosandovalmio/Proyectos/SIGODT/.env --ro-bind /dev/null /home/renzosandovalmio/Proyectos/SIGODT/.env.example --tmpfs /home/renzosandovalmio/Proyectos/SIGODT/.git --clearenv --setenv PATH /usr/bin:/bin php -n tests/siindi_password_controller_test.php

timeout 30s bwrap --unshare-all --die-with-parent --ro-bind / / --dev /dev --proc /proc --tmpfs /tmp --ro-bind /dev/null /home/renzosandovalmio/Proyectos/SIGODT/.env --ro-bind /dev/null /home/renzosandovalmio/Proyectos/SIGODT/.env.example --tmpfs /home/renzosandovalmio/Proyectos/SIGODT/.git --clearenv --setenv PATH /usr/bin:/bin php -n tests/institutional-settings_test.php

timeout 30s bwrap --unshare-all --die-with-parent --ro-bind / / --dev /dev --proc /proc --tmpfs /tmp --ro-bind /dev/null /home/renzosandovalmio/Proyectos/SIGODT/.env --ro-bind /dev/null /home/renzosandovalmio/Proyectos/SIGODT/.env.example --tmpfs /home/renzosandovalmio/Proyectos/SIGODT/.git --clearenv --setenv PATH /usr/bin:/bin php -n tests/siindi_template_parity_test.php
```

Scoped PHP lint: eight modified/new PHP files pass; scoped `git diff --check` passes. Expanded negative fixtures include typed/array/oversized input, expired grant, and enabled reCAPTCHA failure/success (injected fake verifier, no Google requests). Invalid browser input or Origin performs no provider calls and preserves session state; denied reCAPTCHA performs exactly one fake security check, zero SIGUR calls, and preserves prior authority.
