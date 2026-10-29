# Login reCAPTCHA v3 (coexists with image CAPTCHA)

## Objective and problem
Add Google reCAPTCHA v3 to the login as an additional server-validated layer. The existing mandatory image CAPTCHA (`helpers/Captcha.php`) stays. The user already registered the domain and holds the site key and secret key.

## Authorized scope and constraints
- Branch: `feat/login-recaptcha-v3` (from `dev`). Delivery: `auto-chain` / `feature-branch-chain`; no push or PR authorized.
- Coexistence: both checks must pass. reCAPTCHA failure never enables login (fail-closed). Image CAPTCHA behavior is unchanged.
- Secrets: `RECAPTCHA_SECRET_KEY` and `RECAPTCHA_SITE_KEY` live only in the git-ignored `.env`. The agent must never read, print, or commit real keys; tests use fakes. `.env.example` gets placeholder entries only.
- Config via `Conectar::getEnv`: `RECAPTCHA_ENABLED` (default off when keys are missing), `RECAPTCHA_SITE_KEY`, `RECAPTCHA_SECRET_KEY`, `RECAPTCHA_MIN_SCORE` (default 0.5), `RECAPTCHA_EXPECTED_HOSTNAME` (optional).
- Validate before DB and sisSeguridad cURL, same boundary as the image CAPTCHA.
- Pre-existing unrelated changes must stay untouched.

## Acceptance criteria
- S1: `helpers/Recaptcha.php` verifies a token against `https://www.google.com/recaptcha/api/siteverify` (injectable HTTP client, timeout) and requires `success === true`, `action === 'login'`, expected hostname when configured, and `score >= min`. Empty, malformed, wrong-action, low-score, wrong-host, network failure, and non-JSON responses all fail closed.
- S2: `Usuario::login` rejects before DB/cURL when reCAPTCHA is enabled and the token is invalid; the image CAPTCHA check still runs. Disabled mode skips only the reCAPTCHA check.
- S3: Frontend loads the v3 script with the site key, requests a token with action `login` on submit, fills hidden `recaptchaToken`, shows useful error state, and never submits without a token when enabled. Site key is exposed from env, never the secret.
- S4: `.env.example` documents the variables with placeholders; usage notes added; no secret in repo or tests.

## Checks
Deterministic PHP tests (RED then GREEN) with a fake HTTP client, Node DOM test for the JS, existing `tests/login_captcha_test.php` and `tests/sigur_login_test.php` still pass, `php -l`, `git diff --check`. Independent verifier (Risk: high, authentication/secrets). Real Google verification and browser behavior are unverified without live keys and deployment.

## Tasks
- [x] T1 | S1,S2 | delegated writer | done: helper + login integration, RED->GREEN | commit: ff763dbd
- [x] T2 | S3,S4 | delegated writer | done: frontend, env example, docs, Node 13/13 | commit: 6ee11703 (Node RED reconstructed after code)
- [x] T3 | S1-S4 | independent verifier PASS (high) + parent spot check passed; no blocking findings

## Log
- L1: User chose coexistence with the image CAPTCHA and confirmed the keys exist in the reCAPTCHA console. Keys are to be placed by the user in `.env`; never pasted in chat.
- L2: T1/T2 committed (ff763dbd, 6ee11703, about 290 authored lines each). Independent verifier PASS. Low notes: set RECAPTCHA_EXPECTED_HOSTNAME in production; no local replay cache (Google enforces single use). Real Google verification and browser behavior unverified (need live keys/domain).
