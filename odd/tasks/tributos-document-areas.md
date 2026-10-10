# Document-scoped Tributos areas

## Objective and problem
Show only areas with active procedures in the document selected in Tributos. The current generic area selector offers empty area/document combinations.

## Authorized scope and constraints
- Change Tributos selectors only; preserve general areas, procedure creation and giro registration.
- Include procedures without assigned taxes; keep En Proceso documents usable.
- Preserve existing untracked `.playwright-mcp/` and `tests/tupa_duplicar_test.php`.
- Local implementation and tests only; no deployment or remote operations.
- Technical artifacts use English; extend existing Spanish UI copy professionally.

## Tasks
- [x] T1: Implement document-scoped areas with isolated endpoint, reset cascading selections, and prevent stale asynchronous responses; add deterministic PHP/Node regression tests.
  - Route: delegated direct, authorized gpt-6.1-sol worker; preparation and multiple non-trivial files trigger delegation.
  - Acceptance: active dependency with active procedure in selected document; no tax prerequisite; no Vigente-only restriction; unchanged shared area endpoint; no stale child selections after document/area changes.
  - Allowed files: `models/Area.php`, `controller/area.php`, `view/mnt_tributos/main.js`, `tests/tributos_area_options_test.php`, `tests/tributos-area-options.test.cjs`.
  - Tests: observe new regression tests RED before source edits, GREEN after implementation, refactor while green.

## Verification
```text
php tests/tributos_area_options_test.php
node --test tests/tributos-area-options.test.cjs
php tests/catalog_document_state_test.php
php tests/giro_registration_permissions_test.php
node --test tests/catalog-document-state.test.cjs tests/giro-registration.test.cjs
php -l models/Area.php
php -l controller/area.php
php -l tests/tributos_area_options_test.php
node --check view/mnt_tributos/main.js
git diff --check
```
Database doubles do not prove live PostgreSQL semantics. Browser/server checks remain pending deployment authorization. Parent spot-checks one reported command. RDD is on; native risk/consent determines review after the work-unit commit.

## Delivery and progress
- Branch: `feature/tributos-document-areas`; base: `c0ed7f6e`.
- Delivery strategy: ask-on-risk; forecast 280–370 authored changed lines, generated files excluded. No PR/push/merge authorized.
- Commit: pending; one Conventional Commit for the coherent behavior and its tests/docs.
- RDD assessment and review: pending; no approval inferred.
- Progress: implementation complete. Initial PHP regression failed with missing document-scoped query; initial Node regressions failed before source edits. Final PHP and all six Node behavioral cases passed, plus every listed regression/syntax/whitespace check. Parent reran the new PHP contracts successfully.
- Verification limits: PHP/DOM doubles only; live database/browser pending. Optional post-implementation baseline replay via Node subprocess was blocked by EPERM; initial observed RED evidence remains valid. No required final check failed.
- Authored change: worker 250 lines (60 source additions, 6 deletions, 184 test additions), plus this task document; below delivery threshold.
- Next step: commit the work unit and assess its committed native-review range.
- Engram mirror: `odd/tributos-document-areas/tasks`; save/readback required at each progress update.
