# In-progress TUPA documents and giro eligibility

## Objective
Introduce a distinct En Proceso lifecycle state for new TUPA/TUSNE documents while allowing catalog procedure creation only on En Proceso or Vigente documents. New giro trámite registration must only offer and accept enabled procedures from Vigente TUPA/TUSNE documents in eligible areas, subject to role-specific area assignments.

## Problem and rationale
Document state 1 currently means both new and deactivated/inactive/archived. Renaming 1 would accidentally reopen archived catalogs. Giro area selectors disagree with available procedures; admin registration has a legacy broader picker. User confirmed state separation and creation policy.

## Scope and constraints
- Preserve existing state 1 as Inactivo/archived, state 2 as Vigente, state 0 as soft-deleted. Use distinct En Proceso value 3: user-provided PostgreSQL catalog evidence confirms integer `est`, no CHECK, and installed duplication function using 3; new documents start there. Do not migrate existing state-1 records without data evidence. Deactivation remains state 1.
- En Proceso permits new catalog procedure creation but never new giro trámite registration. Vigente also permits new catalog procedure creation. Other states do not. Preserve current historical catalog browsing/update/delete unless the established block policy applies; do not silently expand restrictions beyond creation.
- Both giro-facing new-trámite screens should offer only enabled procedures of Vigente TUPA/TUSNE, with active dependency, `nior_id != 5`, IDs 2 and 200 excluded. Role 9 has global eligible scope; other roles require an enabled `td_areausu` assignment matching session person ID. Revalidate on registration server side. Avoid changing generic maintenance getters.
- Do not alter existing/historical trámite payment or giro generation workflows; no live DB or server 10.10.10.16 access, no credentials. Keep distinct `tupa_block` meaning. User-provided installed function `sc_giros.duplicar_tupa(p_tupa_id integer)` returns the new ID and inserts state 3, cloning active procedures/taxes/requirements. Preserve other agent's untracked `database/functions/duplicar_tupa.sql` and `tests/tupa_duplicar_test.php`; do not edit them or silently claim database integration verified. Database write verification needs a separately authorized isolated database.
- No commit/push/PR unless explicitly requested; preserve clean baseline and report pending work-unit commit.

## Tasks
- [ ] T1 — Add distinct En Proceso state and constrain catalog creation. Implemented in `models/Tupa.php`, `models/Procedimiento.php`, `controller/tupa.php`, `controller/procedimiento.php`, `view/mnt_tupa/main.js`, `view/mnt_procedimiento/main.js` with new PHP/Node tests. Writer observed RED (initial state and three UI assertions), GREEN (`php tests/catalog_document_state_test.php`, `node --test tests/catalog-document-state.test.cjs` 4/4), plus PHP/JS syntax and preserved `php tests/tupa_duplicar_test.php`; parent spot-check reran PHP catalog test successfully. Real DB duplication and UI browser checks remain unverified. Work-unit commit pending explicit user permission; do not check off before closure.
- [ ] T2 — Align giro new-trámite availability and permissions. Check both screens' selectors and empty/error responses, model eligibility and server authorization including role 9 vs assignments, focused tests. Evidence: pending.
- [ ] T3 — Verify integrated behavior and report boundaries. Check syntax, deterministic tests, focused UI verification as available; record failed/skipped checks and review readiness. Evidence: pending.

## Acceptance criteria
New documents start in En Proceso; existing inactive records stay inactive. Catalog creation succeeds only for En Proceso/Vigente, never archived/deleted. Giro area and procedure pickers offer only eligible selectable procedures; non-role9 assignments are preserved; forged IDs cannot register new giro trámites; admin new-trámite screen agrees with backend. Empty choices remain safe. Historical payments and maintenance remain unaffected.

## Progress and next step
T1 behavior is implemented and focused checks pass; work-unit commit is pending explicit user permission. T2 and T3 have not started. Other agent's untracked SQL/test files remain untouched. Real PostgreSQL write integration remains unverified and requires an isolated authorized database. Next: resolve commit authorization to close T1 and continue T2; do not claim complete until verified.
