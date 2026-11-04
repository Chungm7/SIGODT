# Authenticated account API (T1)

S1–S2/S4–S5 foundation only. No legacy route, login, permission, history,
area provisioning, first-access or recovery behavior is modified. No DNI or
Module/System display field is exposed by this API. Identity still exists server-side.

## HTTP handoff for T3

Endpoint: `controller/account.php?op=<operation>`. Replies are JSON with
`Cache-Control: no-store`. GET requests accept no input fields. POST accepts
form-urlencoded fields only; query parameters other than `op` are rejected.

| Operation | Method | Fields | Success `data` |
| --- | --- | --- | --- |
| `csrf` | GET | none | `csrf_token` |
| `profile` | GET | none | `names`, `surnames`, `display_name`, `role`, `position`, `phone`, `institutional_email`, `personal_email` |
| `update_phone` | POST | `csrf_token`, `phone` | `phone` |
| `update_personal_email` | POST | `csrf_token`, `personal_email` | `personal_email` |
| `change_password` | POST | `csrf_token`, `current_password`, `new_password`, `confirm_password` | `transition: "settings"` |

Successful response: HTTP 200, `{"success":true,"data":{...}}`.
Failures: `{"success":false,"error":"<code>"}` with the status below.

- 401 `authentication_required`: missing/malformed fully authenticated identity.
- 404 `operation_not_found`; 405 `method_not_allowed` (with `Allow`).
- 400 `unexpected_field`: extra fields, including identity/role/recipient overrides.
- 403 `csrf_invalid`: missing or incorrect POST CSRF token.
- 422 `invalid_phone`, `invalid_personal_email`, `invalid_password`.
- 502 `account_service_unavailable`: provider/configuration/ownership/response failure;
  no upstream messages, exception details or credentials returned.

CSRF is a random 32-byte hex token stored at `$_SESSION['account_csrf']` and
submitted as `csrf_token`. Only `csrf` generates it. Failed requests never refresh
contact state. Successful contact writes and profile reads synchronize
`pers_celu01_SIGODT`, `pers_emailp_SIGODT`, `pers_emailm_SIGODT` as applicable.
Phone is exactly nine ASCII digits; email must pass PHP validation and be at most
254 bytes. Password confirmation must match; new password follows reference UI:
minimum eight characters, uppercase/lowercase/digit/special, with a defensive
1024-byte ceiling. Passwords are not trimmed, persisted, returned or logged.

## Identity and observed display mapping

`Conectar::initSession()` owns `SIGODT_SESSID`. Current destination login sets
`usua_id_SIGODT` to local `pers_id` only after active `sist_id=8` permission lookup;
`usua_dni_SIGODT` is `pers_dni`. Login hardcodes SIGUR `usu_tipo=P`; there is no
session type field, so this adapter preserves that constant. The server-owned
principal is `['id'=>positive int, 'document'=>eight-digit string, 'type'=>'P']`.
No pending identity is searched, copied or promoted by this handler. T4 must not
set `usua_id_SIGODT` before completing second factor.

Names map to existing `pers_nombre_SIGODT`, `pers_apellidos_SIGODT` and
`nombre_completo_SIGODT`; role maps to `rol_nombre_SIGODT`. Role is **not** a job
position. `position` is explicitly null: current login has no observed job-title
field, and this work does not guess a hidden DB column or perform a DB lookup.

Reference `controller/usuario.php:mostrar_datos` exposes `usu_celu01`,
`usu_emailp`, `usu_emails`; these map respectively to phone, personal and
institutional email. Reference recovery SQL calls the institutional DB field
`pers_emailm`, but that is not evidence of a second SIGUR response alias.
The adapter uses the observed `usu_emails`, not an invented `usu_emailm` fallback.
Missing institutional mail fails closed for OTP dispatch. If returned `usu_id` or
`usu_doc` is present, a mismatch rejects the profile. Live compatibility of these
aliases remains an activation check; no actual account data was read.

## Injectable APIs for T4/tests

```php
new SigurAccountClient(?callable $env = null, ?callable $transport = null);
$client->currentAccount(array $identity): array;
$client->updatePhone(array $identity, string $phone): array;
$client->updatePersonalEmail(array $identity, string $email): array;
$client->changePassword(array $identity, string $current, string $new): array;
$client->sendInstitutionalOtpMail(array $identity, string $code,
    string $name = 'Servidor Municipal', int $expires = 120): array;
```

`sendInstitutionalOtpMail` is an internal PHP seam, **not** an account HTTP op.
T4 supplies its server-held pending principal and owns challenge authorization,
expiry, attempts and resend limits. The adapter resolves institutional recipient
from SIGUR for that principal; there is no arbitrary recipient argument. It sends
the supplied six-digit code through `ws/correo-ss.php` (`destino`, `titulo`,
`mensaje`), returning `['success'=>true,'simulated'=>false]` only on confirmed
provider success. No retry, simulated delivery or SMTP fallback exists.

Environment getter signature: `($key, $default = null)`; default delegates to
`Conectar::getEnv`. Required variable **names**: `SIGUR_URL`, `SIST_INIC`,
`SIST_PASS`; optional `SIGUR_AUTH_URL`. No credential defaults. `SIGUR_URL`
is destination's SIGUR application root ending `/sigur/` (a terminal `/ws/` is
normalized); unlike some SIINDI callers, do not configure a host root expecting
another implicit `/sigur/` append. HTTPS is required, including an auth override.
An existing HTTP deployment needs verified HTTPS configuration before activation;
this adapter never silently downgrades credential transport.

Auth POST `auth/`: `sist_inic`, `sist_pass`; token is `data.token`.
Account POST `ws/mostrar.php`: `usu_doc`, `usu_tipo`.
Contact POST `ws/actualizar.php`: same identity plus `tipo_cambio=1` (phone)
or `2` (personal email), `dato`. Password POST
`controller/clave.php?op=actualizar`: identity, `usu_pass_actual`, `usu_pass_nueva`.
All authorized calls use Bearer token, never query-string credentials. Each
operation authenticates once, then issues each downstream request exactly once.

The transport callback receives `url`, `method`, `fields`, `headers`,
`connect_timeout=5`, `timeout=20`, `verify_peer=true`, `verify_host=2`,
`follow_redirects=false`; returns `['status'=>int HTTP status,'body'=>string JSON]`.
Default cURL enforces TLS, no redirects, bounded timeouts and a 1 MiB response cap.
Only HTTP 2xx plus JSON `success: true` is accepted; optional body `status` must
be 200. Missing, malformed, contradictory or negative results fail closed.
`SigurAccountException` messages are stable codes:
`account_configuration_missing`, `account_service_unavailable`,
`institutional_email_unavailable`; invalid direct arguments throw
`InvalidArgumentException`. Provider rejection is not converted to fake success.

The regular password reference returns to settings and clears only its residual
blanqueamiento cookie; it does not mandate logout. This API returns
`transition: settings`, leaves the current authenticated principal intact and
adds no cross-application cookie/logout policy. Existing SIGODT first-access
blanqueamiento and recovery entry points remain untouched.

Define `ACCOUNT_API_LIBRARY_ONLY` before requiring `controller/account.php` in
isolated tests, then use:
`account_api_handle(array &$session, string $method, string $op, array $input, $client)`
returning `['status'=>int,'body'=>array]`, or
`account_api_render(...)` to emit the exact HTTP/JSON response. No live config is
loaded in library mode. Injected provider methods must throw on failure (same
contract as `SigurAccountClient`), not return false success arrays.

## Verification and review boundary

Standalone tests: `php tests/account_api_test.php` and
`php tests/sigur_account_client_test.php`; all checks run in a read-only,
network-unshared bwrap sandbox, dummy principals/credentials only.
Review Risk item 2: authentication/credentials, server-owned identity and CSRF.
This coherent foundation exceeds the 400-line advisory because transport,
public handler, negative tests and integration contracts must remain reviewable
together; no shared/legacy implementation was edited. Rollback boundary is these
six new files. T3/T4 integration and live deployment compatibility remain parent-owned.
