# Auto-provisioning of default user area on login

## Objective and problem
Users created in `sisSeguridad` already have an assigned institutional dependency (`depe_id`), but upon logging into SIGODT they cannot view or issue procedure orders until an administrator manually assigns them in `view/mnt_permisos/` (`sc_giros.td_areausu`). The objective is to automatically provision their default area upon login (Just-In-Time Provisioning) when they have no active area assignments, eliminating redundant manual setup while preserving domain-level multi-area flexibility and explicit administrative revocations.

## Authorized scope and constraints
- Add auto-provisioning logic to `models/Area.php` and invoke it from `models/Usuario.php::login()` upon successful authentication from `sisSeguridad`.
- Do not reactivate explicitly revoked assignments (`est = 0` for the same `depe_id`).
- Do not add duplicate assignments if the user already has active area assignments (`est = 1`).
- Validate that the target dependency exists and is active in `public.tb_dependencia` (`depe_estado = 'A'`).
- Ensure fail-safe execution: errors in auto-provisioning must never interrupt or fail the user's login.
- Preserve existing manual management in `view/mnt_permisos/` and all existing database schemas/tables.
- Include unit tests verifying all boundary conditions and lifecycle states.

## Tasks
- [x] T1: Implement `sincronizar_area_defecto` in `models/Area.php` and connect to `models/Usuario.php::login()`. Add deterministic PHP regression test.
  - Acceptance:
    - New user with 0 assignments gets `depe_id` inserted with `est = 1`.
    - User with active area is unaffected.
    - User with revoked area (`est = 0`) is not reactivated.
    - Invalid inputs and non-existent dependencies fail-safe without throwing.
  - Allowed files: `models/Area.php`, `models/Usuario.php`, `tests/auto_provision_area_test.php`.

## Verification
- Unit test suite: `php tests/auto_provision_area_test.php` (passed)
- Full PHP suite: `php tests/login_captcha_test.php && php tests/giro_registration_permissions_test.php && php tests/tributos_area_options_test.php && php tests/catalog_document_state_test.php && php tests/menu_test.php` (passed)
- Full Node suite: `node --test tests/*.test.cjs` (106/106 passed)
- PHP syntax checks: `php -l models/Area.php && php -l models/Usuario.php` (passed)
