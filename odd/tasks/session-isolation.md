# Session isolation for SIGODT (Prevent cross-system logout)

## Objective and problem
Prevent session termination cross-talk when multiple systems run on the same host or IP (`10.10.10.16`). By default, PHP uses the cookie name `PHPSESSID` with `path=/`, causing concurrent systems on the same host to overwrite and destroy each other's sessions on logout. Isolate SIGODT to its own session cookie name `SIGODT_SESSID` and scope its cookie path to `/SIGODT/` (or application path).

## Authorized scope and constraints
- Branch: `feat/session-isolation` (branched from `dev`).
- Delivery: single work unit commit; under 400 lines; no push or PR authorized.
- Preserve existing authentication contracts (`$_SESSION` data keys remain untouched).
- All existing PHP test suites must continue to pass without regression.

## Acceptance criteria (Specs)
- **S1 (Session name and cookie scoping):** When session is initialized in `config/conexion.php`, `captcha.php`, `controller/ordengiro.php`, and `controller/reporte_usuario.php`, session name is set to `SIGODT_SESSID` and cookie parameters have `path` scoped to the application path (derived from `APP_URL` or `/SIGODT/`), `httponly=true`, and `samesite=Lax`.
- **S2 (Clean logout cookie deletion):** In `models/Usuario.php` (`logout`), destroy session and expire the `SIGODT_SESSID` cookie on the client with the matching cookie path.
- **S3 (Deterministic tests):** Add `tests/session_isolation_test.php` verifying session name, cookie parameters, and clean destruction.

## Tasks
- [x] **T1** | S1, S2, S3 | Route: `delegated` (odd-worker) | Risk: `medium` | Done: Implemented `Conectar::initSession()`, `Conectar::getCookiePath()`, session name `SIGODT_SESSID`, scoped cookie path with HttpOnly/SameSite, and client cookie expiration on logout. RED -> GREEN verified; all 17 PHP test suites pass | commit: `efec15c1`
- [x] **T2** | S1–S3 | Route: `inline` | Parent spot check passed (7/7 tests); fast-forward merged to `dev`.

## Log
- **L1:** User reported cross-system logout on shared server IP `10.10.10.16`. Chose Option A (isolating session name and cookie path) over migrating to JWT cookies.
- **L2:** Completed implementation in commit `efec15c1`. Verified with unit and full integration tests.
