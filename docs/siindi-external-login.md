# External SIINDI login wiring

Scope: T7/T12, S3, S4, S8, S10–S12. Authentication risk: item 2.
The binding API is [the frozen integration contract](siindi-external-integration-contract.md).
This is host compatibility, not a replacement Account/Auth core.

## Host behavior

- `Usuario::login()` retains CAPTCHA and reCAPTCHA before DB/provider access,
  SIGUR token/login exchange and active local system-8/person permission lookup.
  Institutional user 900 and local person 42 remain distinct in fixtures.
- Primary credentials call `beginPending()` with the existing 300-second host
  challenge. No history, area provision or full session exists at this point.
- The **unchanged original controller** performs email, existing TOTP or initial
  enrollment, emits its actual JWE session cookie, and redirects to host landing.
  Cookie issuance itself does not promote the PHP session.
- Stable bootstrap verifies completion and supplies the server-only receipt.
  `finalizarLogin2FA()` requires kind `siindi_completion` plus matching nonce,
  challenge, institutional account/document and unexpired receipt/pending state.
  No fabricated `method=email/totp` exists: the original payload has no branch label.
- Finalization reloads the actual active system-8/person/profile/department row.
  Changed role/department is used; revoked/mismatched identity fails closed.
  Source profile defaults (including Administrator) never authorize host access.
  History must persist before area/full session. Failure cannot promote; successful
  landing refresh cannot repeat history or provisioning. Session ID is rotated.
- A positive, authenticated SIGUR `status=200, success=true` whitening response
  clears module temporary state, starts `first_access` for 1900 seconds and
  redirects to `seguridad/primer_acceso/views/`. Provider-supplied UI URLs are not
  followed. Failed/ambiguous responses cannot create this grant.
- Logout calls `clearLogin()` before destroying the host session. Its temporary
  cookies are deleted only on `/SIGODT/`; `/sigur/` remains untouched.

## Executable evidence

`tests/siindi_external_login_test.php` creates scratch fixtures, copies the actual
stable connection/JWT/JWE/bridge, source controller/model/crypto/views and host
Usuario, and uses the real Composer crypto dependencies read-only. Only provider,
mail and PDO/Area effects are fake. A bounded isolated HTTP server captures real
`Set-Cookie` headers and carries that cookie into the host bootstrap. Landing uses
the existing host full-session guard without its unrelated HTML shell/assets.
`tests/siindi_login_integration_test.php` is the compatibility entry for this suite.

Cases cover primary pending, local first access, email/TOTP/initial-enrollment
completion, receipt correlation, logout, fresh/changed/revoked permission, failed
history, one promotion, stale/replayed/missing/wrong-account/document/nonce/type/
expired completion, untrusted method/origin, CAPTCHA layers, temporary-stage
isolation and original page entry. Rejections assert HTTP status/body, no server
stdout, only expected HTTP-server lifecycle stderr, and unchanged fixture state/
counters apart from explicit expected cleanup/history attempts.

Run each command in a fresh cleared-env, read-only, network-unshared sandbox;
never run target code on the host directly. Prefix below is part of each command:

```sh
timeout 30s bwrap --unshare-all --die-with-parent --ro-bind / / \
  --tmpfs /tmp --proc /proc --dev /dev \
  --ro-bind /dev/null "$PWD/.env" --ro-bind /dev/null "$PWD/.env.example" \
  --tmpfs "$PWD/.git" --clearenv --setenv PATH /usr/bin:/bin \
  --setenv HOME /tmp --chdir "$PWD" php -n tests/siindi_external_login_test.php
```

Other focused PHP entries use the same sandbox prefix, with `php` (normal extensions)
for the two legacy SIGUR suites. Source pins and preservation are independent of
fake-provider proof. No live provider, mail, DB, migration or production proof is
claimed. No Node command is run: the known 512-MiB V8 setup is invalid.

### Test-first record

Before Usuario wiring, the external suite exited 1 with intended failures for
pending alias/nonce, local whitening route, source completion, correlated receipt
and logout cleanup. Its initial completion fixture also emitted an undefined-mail
warning because the missing alias prevented source email; that fixture diagnostic
was corrected, not counted as behavior proof. GREEN subsequently covered all these
rules. Later triangulation adds the negative cases and actual page routes; it is
not retroactively claimed as separate pre-implementation RED for every case.

An additional observed RED for `whitening` rejected ambiguous provider
`success="false"` (exit 1); requiring the actual boolean acknowledgement made that
focused rule GREEN (exit 0).

### Foundation integration finding

Actual `desafio_login.php` reads only the `2fa_pending` cookie, unlike the selector,
enrollment and controller which accept the PHP pending alias. A bridge that only
projects the alias and deletes the cookie redirects the existing-TOTP challenge
page to root. The added public `views` rule observed HTTP302 instead of HTTP200.
This was reported to the parent with a bounded reproduction; no foundation or
source-template file was changed by the login writer. The parent corrected the
projection to issue an encrypted `type=2fa_pending` cookie for the source view.
Subsequent parent self-review keeps its contents identity-only: the private nonce
remains solely in the PHP alias, and controller processing ignores the browser
pending cookie. A nonce from browser pending data must never become factor proof.
Residual aliases/cookies are also discarded for completed host identities. After the parent's fixed/validated notice, the exact
`views` reproduction became GREEN (exit 0), as did the full 11-rule integration
entry. This proves actual challenge-page entry, not merely direct-controller TOTP.

### Final self-review verdict (bounded login unit)

- **S3 PASS:** mandatory email/existing TOTP/original first enrollment complete only
  through real source cookie → verified host landing; all original pending pages
  now enter successfully, including the cookie-only TOTP challenge.
- **S4 PASS:** local/institutional identity separation, actual fresh system-8 role/
  department, history/area sequencing and existing session fields retained.
- **S8 PASS:** 19 destination pins and 19 read-only source hashes match; no template
  or source edits. All writer changes are on its seven authorized surfaces.
- **S10 PASS within this unit:** authoritative whitening reaches the local original
  first-access screen; ordinary/pending contexts cannot grant password authority.
  Recovery/password operation acceptance belongs to the disjoint parent/peer unit.
- **S11/S12 PASS within scope:** reuse existing client protocol and stable bridge;
  no invented OTP provider, global Auth replacement or activation claim.

Final own evidence: integration11/11, actual-page reproduction, legacy SIGUR8/8,
central crypto/blanqueamiento5/5, render inputs, PHP lint and source pins. The mixed
legacy index/render seams were initially deferred while the peer owned changing
files, then executed after the parent explicitly released them. No remaining own
seam deferral. Full feature/native acceptance remains the parent's responsibility.
The HTTP fixture explicitly excludes existing PHP deprecation diagnostics from its
error level; all other PHP diagnostics fail. No deprecation output was observed in
these recorded commands; this is not a vendor deprecation-cleanliness claim.

## Original-template expectations versus proposals

Old tests targeted an internally modified module. They now exercise original
controller/cookie completion and host guards. Original pending enrollment is
allowed (S3); profile/modal/password route authorization belongs to host bootstrap,
not newly inserted template guards. Rendering fixtures provide original source
cookie/alias fields and preserve existing settings escaping/field exclusions.

The following former assertions are **not** claims about the preserved template:
forbidden pending enrollment, staged/conditional enrollment SQL, per-purpose
challenge/rebind grants, atomic conditional source replay SQL, dedicated-key/
shared-writer gates, failed-mail OTP cleanup, modal lifecycle hardening and removal
of original plaintext OTP audit fields. Those are proposal-only. Existing source
key/mail/audit/enrollment/replay decisions have not been silently changed to make
host tests pass. See [restoration/proposals](siindi-security-restoration-and-proposals.md).

## Independent finding H1 and bounded correction

The initial positive enrollment fixture incorrectly left `seus_esta_vinc=true`, so it proved that a primary-only actor could replace an existing factor, not just perform first setup. Independent verification blocked S3 on this actual original-controller route. That result supersedes the initial self-review enrollment assurance above.

The external bridge now denies pending enrollment/replacement when the existing row is linked. It reads enrollment metadata with throw-on-error connection behavior (a DB failure cannot mean no enrollment), correlates owner identity, and requires confirmation to follow an eligible initialization in the same private login challenge. The initialization intent is cleared on new lifecycle or completed promotion. Full authenticated users keep the original replacement workflow. Nothing under `seguridad/` changed.

The focused `enrollment-eligibility` public HTTP rule was observed RED (linked-factor initialization accepted), then GREEN. It checks linked initialize/confirm rejection before stored-factor/business effects, missing or stale initialization, lookup failure, first setup without a prior row, and replacement after completed MFA. The positive `totp-enrollment` rule now explicitly uses an unlinked row for first setup; existing-TOTP verification still uses a linked row. The full PHP regression passed31/31 after correction. The same verifier's H1-only targeted recheck PASSED (enrollment-eligibility, totp-enrollment and source19 pins, each exit0 with empty stderr), not a new sweep. Cross-system concurrency and live shared data remain unverified; no global atomicity is claimed.

## Limits and review focus

- Source `sisIndicadores` and all 19 `seguridad/` pins must remain unchanged.
- Real institutional schema, provider response variants, key custody, browser/UI,
  concurrency and real mail remain activation checks. The unavailable central
  backend is not an implementation blocker and no local OTP authority is invented.
- Original factor persistence and host history/area are not a cross-DB transaction.
  A valid factor may be consumed before fresh authorization/history rejects login.
- Review the receipt boundary, fresh role/department query, temporary authority,
  module-only cookie paths and actual challenge-page entry. Passing fake fixtures
  does not close native acceptance or authorize activation.
