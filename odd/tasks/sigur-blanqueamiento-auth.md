# SIGUR Central Authentication & Password Whitening Redirection

## Objective and problem
When a user attempts to log in to SIGODT and the central municipal security service (`sisSeguridad`) detects that the user requires password whitening (`$detalle === "Blanqueamiento de clave"`), SIGODT must securely authenticate the user's intent, generate an encrypted cross-system JWE/JWT token, set the requisite authentication cookie scoped to the central security module (`/sigur/`), and redirect the user to SIGUR's password whitening portal (`view/clave/blanqueamiento.php`).

## Authorized scope and constraints
- Install and configure required cryptographic packages (`firebase/php-jwt`, `web-token/jwt-framework`, `web-token/jwt-encryption`, `spomky-labs/aes-key-wrap`).
- Implement JWE compact serialization (RFC 7516) using `A256KW` key encryption and `A256CBC-HS512` content encryption in `config/jwe.php`.
- Implement `JwtHandler` class in `config/jwt.php` combining HS256 JWT signature and JWE encryption/decryption, as well as secure cookie configuration (`HttpOnly`, `SameSite=Lax`, HTTPS detection).
- Configure environment variables in `.env` and `.env.example` (`JWT_SECRET`, `JWE_SECRET`, `SIGUR_URL`, `BASE_URL`, `DOMAIN`, `APP_NAME`).
- Update `models/Usuario.php` in `login()` to intercept `$detalle === "Blanqueamiento de clave"`, construct the payload (`usu_doc`, `usu_tipo='P'`, `success=true`, `blanqueamiento=2`), encrypt token with 1900s TTL, issue cookie `blanqueamiento` on path `/sigur/`, and redirect to `$sigurUrl . "view/clave/blanqueamiento.php"`.
- Implement automated test suite in `tests/sigur_blanqueamiento_test.php` validating token symmetry, structure, payload preservation, security cookie options, and mock detection.
- Maintain English docstrings, comments, tests, and documentation.
- No AI attribution in commit messages.

## Tasks
- [x] T1: Install JWT and JWE composer dependencies and configure advisory policies.
  - Acceptance:
    - `firebase/php-jwt`, `web-token/jwt-framework`, `web-token/jwt-encryption`, and `spomky-labs/aes-key-wrap` installed cleanly.
    - Autoload generates without conflict.
  - Allowed files: `composer.json`, `composer.lock`.

- [x] T2: Create configuration modules `config/jwe.php` and `config/jwt.php`.
  - Acceptance:
    - `config/jwe.php` defines `encryptJWE` and `decryptJWE` with `A256KW` and `A256CBC-HS512`.
    - `config/jwt.php` defines `JwtHandler` with `encrypt`, `decrypt`, and `setCookie` (HttpOnly, SameSite=Lax, secure if HTTPS).
  - Allowed files: `config/jwe.php`, `config/jwt.php`.

- [x] T3: Update environment configuration.
  - Acceptance:
    - `.env` and `.env.example` include SIGUR variables (`JWT_SECRET`, `JWE_SECRET`, `SIGUR_URL`, `BASE_URL`, `DOMAIN`, `APP_NAME`).
  - Allowed files: `.env`, `.env.example`.

- [x] T4: Integrate password whitening redirection in `models/Usuario.php`.
  - Acceptance:
    - Intercepts `$detalle == "Blanqueamiento de clave"`.
    - Signs & encrypts token with payload `usu_doc`, `usu_tipo`, `success`, `blanqueamiento`.
    - Sets cookie `blanqueamiento` on `/sigur/` for 1900 seconds.
    - Redirects to `$sigurUrl . "view/clave/blanqueamiento.php"`.
  - Allowed files: `models/Usuario.php`.

- [x] T5: Comprehensive automated test suite.
  - Acceptance:
    - Validates JWE encryption/decryption symmetry and tamper resilience.
    - Validates token structure (5 compact parts, alg: A256KW, enc: A256CBC-HS512).
    - Validates payload preservation.
    - Validates cookie security options.
    - End-to-end mock response detection and redirection with cookie issuance.
  - Allowed files: `tests/sigur_blanqueamiento_test.php`.

## Verification
- Test suite: `php tests/sigur_blanqueamiento_test.php` (5/5 suites passed)
- Regression suites: `php tests/login_captcha_test.php && php tests/auto_provision_area_test.php && php tests/menu_test.php` (passed)
- PHP syntax checks: `php -l config/jwe.php && php -l config/jwt.php && php -l models/Usuario.php && php -l tests/sigur_blanqueamiento_test.php` (passed)
