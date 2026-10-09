# Login CAPTCHA enforcement

## Objective
Make the existing login CAPTCHA mandatory server-side, without affecting unrelated pending work in the original worktree.

## Scope and constraints
Feature branch: fix/login-captcha-enforcement; original base: dev (8a61504c). Integrated into dev; temporary SIGODT-captcha-fix worktree removed cleanly.
Keep the existing Spanish UI and external authentication contract. Do not expose the answer, allow bypass on rendering failures, or reuse a submitted challenge. Validate before database/external authentication work. User now authorizes integration into dev and removal of the feature worktree. Commit the verified unit and merge without losing dev's newer login branding; remove only a clean worktree after checks. No push or PR.

## Tasks
- [x] T1 (verified): Implement and self-verify the complete CAPTCHA enforcement unit with regression tests and usage notes. Work-unit commit cb3ce66.
- [x] T2 (verified): Independently verify the unit and complete native review. Evidence included in work-unit commit cb3ce66.
- [ ] T3 (pending/environment-blocked): Verify real PNG generation, persistent production session across requests, browser behavior, and authorized successful login in the deployment environment. User confirmed test-server CLI GD enabled; web runtime remains unverified.
- [x] T4 (verified): Commit reviewed CAPTCHA unit, integrate into clean dev (5334c9a1), verify merged behavior and preserve current login branding, then remove clean worktree. Parent Git orchestration; delegated verification task mv15nk30-4-htuk. Feature commit cb3ce66; merge commit 2f8f2a62.

## Acceptance criteria
Empty, missing, malformed, wrong, refreshed, reused, and expired challenges fail closed. Matching session challenge permits the existing credential flow but never grants authentication itself. Challenges are generated securely, expire, and are consumed on submission. Generation JSON contains an image only (no answer). Form gives useful required/loading/error feedback, and refresh clears the old response. CAPTCHA service failure never enables login. No live credentials in tests.

## Checks
Applicable deterministic PHP tests: observe RED, then GREEN; test the actual login rejection boundary before DB/cURL. JS/DOM checks where feasible. PHP lint and HTTP generation contract including GD/rendering failure where feasible. Real successful login against sisSeguridad requires authorized test credentials and environment; not assumed verified.

## Progress / evidence
T1 delegated to gentle-ai-worker (multi-file write trigger), task mv12o0k2-1-msbc completed. Observed RED: PHP exit 255 (missing CAPTCHA reached credential boundary), Node exit 1 (module absent). GREEN: PHP regression/HTTP/session contracts passed; Node 4 tests passed; five PHP lints and diff-check passed. Parent reran PHP suite with exit 0. Secure randomness, five-minute expiry, consumption before DB/cURL, answer-free JSON, and loading/error UI implemented across eight authorized files. Original worktree changes remain untouched.

Limitations: GD absent; real PNG generation success unavailable, missing-GD 503 verified. No live DB/sisSeguridad login or visual browser checks. index.php LF normalization adds diff noise. T2 delegated independent verification (security-sensitive spot check), task mv1330hy-3-vak6 completed: PHP suite exit 0, Node four tests exit 0, five PHP lints exit 0, diff-check exit 0. No severe actionable defects found. Native consolidated review approved and exact acknowledgement completed for review-631b12662b19a724; authority burned for the eight-file candidate. All new behavior/test/doc paths explicitly selected for native review; ODD task document excluded.

HTTP tests use an in-memory handler that discards writes: they do NOT prove production cross-request cookie/session persistence. Successful generation uses renderer substitutes. Browser DOM substitutes do NOT prove visual behavior. These gaps remain under T3 and are not claimed verified.

## Integration evidence
Merged with -Xignore-space-at-eol, without conflicts; current dev branding preserved. Independent verifier observed exit 0 for both PHP suites, Node CAPTCHA 4/4 and consultation 26/26, five PHP lints, staged/unstaged diff-check, and empty unmerged index. Native integration review review-f29666b0e02831b1 approved; exact acknowledgement burned authority. Informational warning R3-random-refresh-assertion at tests/login_captcha_test.php:76-77 remains separate follow-up (no correction requested). Parent reran PHP CAPTCHA suite after merge with exit 0. Clean worktree removed without force; feature branch retained. No push performed.

## Next step
T3 still requires web PHP GD, real session storage/cookies, browser access, and authorized test credentials. User confirmed GD enabled in test-server CLI. No dependency installs or live authentication authorized. dev integration is complete locally; publication and deployment remain separate user decisions.
