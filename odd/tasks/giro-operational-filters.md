# Operational giro filters

## Objective
Make pending work and issued orders discoverable without the fixed 200-row visibility limit. Default to En curso (pending without a valid order plus issued orders), with optional creation-date filters and server-side search/pagination.

## Scope and authorization
Local operational citizen giro listing only. Preserve current creation selectors, payment mutations, database routines, and other sections. User authorized eventual integration into dev and push; no deployment or PR creation. Preserve unrelated untracked files.

## Design boundaries
- Historical row identity: procedure/order; pending row identity: procedure/pending. Partial issuance may expose both.
- Cancellation retains historical links: pending eligibility examines valid associations rather than absence of any historical link.
- Pending fee states 1/3 follow supplied routines; avoid deriving pending for terminal procedures. Unknown states remain explicit, never default to Extornado.
- Counts and page data share the same authorized deduplicated relation. Authorization uses session person/role and selected area; history does not inherit document-vigente creation restrictions.
- Date means creation of the trámite, blank by default. Preserve existing database timestamp semantics; live timezone evidence unavailable.
- SQL doubles prove structure/bindings only, not live PostgreSQL execution.

## Delivery
Strategy: ask-on-risk resolved to feature-branch-chain; two coherent commit slices on feature/giro-operational-filters. No PR creation requested. Forecast: 700–1000 authored added/deleted lines; do not compress tests/code to meet budget. Running committed count: 573 authored lines (G1 native assessment). Branch base: 4d7cfdeee443a6167977ca2c534cc13e7e494678. Reviewed boundary: 9930e8feb9851c15da8c6616de738ec7c7172fa4. RDD: on (global). Each slice requires native assessment and due review before reporting completion.

## Tasks
- [x] G1 — Canonical authorized listing, state handling, real pagination/search, PHP regressions. Route: delegated; SQL/controller analysis crosses multi-file writer gate. Expected slice: 400–550 lines. Preserve legacy callers while frontend conversion remains pending.
- [ ] G2 — En curso and optional date controls, single DataTable/reload, Node tests and usage documentation. Route: delegated; frontend logic and tests cross multi-file writer gate. Expected slice: 300–450 lines.

## Acceptance and checks
Pending after cancellation and partial issuance remains visible; historical orders appear once per procedure/order; filters act on effective row state. Stable pagination/search reaches records beyond 200; authorized total/filtered counts are honest. Forged role/person/area cannot expand read access. Default dates blank; reload preserves selected procedure and filters. Unknown states are not mislabeled. Existing registration/catalog/Tributos regressions stay green.

Test-first: observe meaningful PHP/Node RED before implementing each task, then GREEN. Required checks: new PHP and Node operational tests; existing giro_registration_permissions_test.php, giro-registration.test.cjs, catalog_document_state_test.php, catalog-document-state.test.cjs, tributos_area_options_test.php, tributos-area-options.test.cjs; PHP lint changed files; node --check changed JS; git diff --check.

## Evidence and next step
G1 completed after parent spot-check, commit and acknowledged native review. G2 implementation and required self-verification are complete; its checkbox remains open until parent readback and commit/review. No commit, push, routine change or remote operation performed by the writer. Full recovery mirror topic: odd/giro-operational-filters/tasks.

### G1 proof
- RED: `php tests/giro_operational_list_test.php` exited 255 before behavior edits: `Operational list requires validated server-side filters`.
- GREEN: the new PHP application/SQL-shape contracts passed after implementation. Fixtures verify serialization and query structure, not PostgreSQL semantics.
- Regression checks passed: `php tests/giro_registration_permissions_test.php`, `php tests/catalog_document_state_test.php`, `php tests/tributos_area_options_test.php`.
- Node checks passed: `node --test tests/giro-registration.test.cjs tests/catalog-document-state.test.cjs tests/tributos-area-options.test.cjs` (3 files passed).
- Syntax checks passed: PHP lint for `models/Procedimiento.php`, `controller/procedimiento.php`, and `tests/giro_operational_list_test.php`; `node --check view/giros_ciudadano/usudetalleciudadano.js`.
- `git diff --check` passed. No source-mutating normalizer ran after the final checks.
- Worker source/test diff: 370 additions and 157 deletions (527 authored lines), excluding this parent-owned feature document. Exceeding the approximate 400-line heuristic preserves one coherent SQL/controller boundary and its tests; feature-branch-chain was already selected.
- Date edge-case RED/GREEN: PHP accepts year zero whereas the PostgreSQL date contract does not. A failing year-zero validation contract was observed, then fixed before the final full required checks.
- Historical rows deduplicate procedure/order associations; per-fee pending detection excludes inactive/cancelled/improper/extornado issued associations. Pending is derived only for actionable procedure states 1/2 and fee states 1/3, with an initial empty-fee procedure fallback.
- Unknown order/procedure states are not assigned a fabricated label; active associations with unverified nonterminal codes conservatively suppress pending without claiming payment semantics. Unknowns remain reachable through `todos`.
- Selected-procedure access uses session role/person and active assignment (role 9 bypass); an optional validated area only narrows scope. Current catalog lifecycle does not limit historical reads. Counts, search and page use the same authorized relation.
- Company schema confirmed in existing models: current `empr_id` relationship and historical `empresa_ruc`/`empresa_razon_social`. Search includes current/historical company aliases and citizen identity.
- Tiny legacy-frontend wiring reads the selected procedure/area for every request; both modern and legacy DataTables response keys remain available. G2 filter controls and reload redesign are intentionally pending.
- Commit: `9930e8feb9851c15da8c6616de738ec7c7172fa4`; parent PHP spot-check passed. Native assessment: medium, 573 authored lines, review due `slice_budget_reached`. User granted candidate review; native reliability approved and exact acknowledgement burned `review-f42a8691f37f60d5`. This commit is the reviewed boundary and slice 1.
- Nonblocking R3-001: SQL doubles do not execute PostgreSQL. Preserve as pending integration coverage, without reopening the completed review. Browser behavior and timezone/schema integration remain unverified; no remote access was used.


### G2 proof
- RED before behavior writes: `node --test tests/giro-operational-list.test.cjs` exited 1. Direct runner diagnostics exposed the missing `giro_state` controls and operational request functions (9 failing contracts).
- Additional behavioral RED: a wrong draw/count response announced `Listado actualizado: 1 registros...` instead of failure; the new malformed-response contract failed before validation was added.
- GREEN: `node --test tests/giro-operational-list.test.cjs` passed; direct `node tests/giro-operational-list.test.cjs` confirmed all 10 individual contracts passed. The configured `--test` runner summarizes by test file.
- Full Node regression command passed (4 files): `node --test tests/giro-operational-list.test.cjs tests/giro-registration.test.cjs tests/catalog-document-state.test.cjs tests/tributos-area-options.test.cjs`.
- PHP checks passed: `php tests/giro_operational_list_test.php`, `php tests/giro_registration_permissions_test.php`, `php tests/catalog_document_state_test.php`, `php tests/tributos_area_options_test.php`.
- Syntax/whitespace passed: `php -l view/giros_ciudadano/index.php`, `node --check view/giros_ciudadano/usudetalleciudadano.js`, `git diff --check`. No required final check failed or was skipped. No source-mutating normalization followed these checks.
- En curso defaults to pending/Girado; all known individual state choices and Todos are explicit. Both optional creation-date controls start blank, with labels distinguishing creation from payment.
- One modern server-side DataTable forwards current filters and DataTables draw/search/page parameters on every request. Filter changes reset paging; manual refresh preserves paging and selections. Restoring state/date defaults deliberately preserves procedure and DataTables search.
- Version/snapshot checks, cancellation of superseded requests and existing guarded procedure options prevent stale selection repaint. Existing modal `ajax.reload()` callers retain the same guarded transport. Refresh/error feedback follows actual request completion; malformed contracts cannot report success.
- Usage/semantics/recovery and integration limitations are documented in `docs/giro-operational-list.md`. No performance percentage, live PostgreSQL execution, browser behavior, WCAG compliance or timezone integration claim is made. R3-001 remains a nonblocking integration limitation.
- G2 is slice 2; commit and native assessment/review remain parent-owned and pending. Rollback boundary is the G2 markup/transport, Node test and usage documentation; G1 backend behavior remains independently usable through its legacy-compatible contract.
- Runtime harness: Node VM/deterministic AJAX doubles only. Live browser/PostgreSQL harness unavailable within this authorized local task; no remote access, database mutation, routine change or other-section edit occurred.
- G2 authored diff: 541 source/test/usage-doc added/deleted lines, plus this recovery document. Duplicate legacy table removal and proportional race/error tests explain the planning-heuristic overage; the approved two-slice feature-branch-chain strategy is unchanged.
