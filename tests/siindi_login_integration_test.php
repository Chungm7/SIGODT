<?php
/**
 * Public login integration entry; all cases share the isolated HTTP fixture.
 *
 * This deliberately uses the original controller's API and cookie-completion
 * behavior, not the removed modified-template method/CSRF/finalizer hooks.
 * Business regressions remain executable: CAPTCHA/provider/permission rejection,
 * distinct IDs, fresh role/department, history failure, one promotion, email,
 * existing TOTP, initial enrollment, receipt/cookie expiry/account/nonce/replay,
 * logout and temporary-stage isolation. Origin is enforced by the host bootstrap.
 *
 * Modified-template-only expectations (pending enrollment forbidden, staged
 * enrollment, local OTP purpose/challenge/rebind policy, shared-writer gate,
 * conditional source SQL and mail-error OTP cleanup) are not original behavior.
 * They remain proposals, not claims about an unchanged template. See
 * docs/siindi-external-login.md. No assertions require source modifications.
 */
require __DIR__ . '/siindi_external_login_test.php';
