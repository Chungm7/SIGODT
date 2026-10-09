# Order owner search

## Objective
Add a Consultas subsection to search citizen names or company business names, show complete distinct order counts, and open paginated history with existing print behavior and visual conventions.

## Scope and constraints
- All order states included; no date/state exclusion.
- Citizens keyed by ciud_id. Companies grouped by trimmed nonblank RUC with historical fallback; missing RUC uses company ID or explicitly unidentified procedure ID, never business name.
- Search aliases select identities; counts include their complete history. One row per order; amount aggregation must avoid rate duplication.
- Existing Spanish Tabler presentation, responsive tables, breadcrumbs and print POST action.
- Two new endpoints require session validation, parameter validation, prepared queries and safe error responses.
- Preserve existing changes in index.php, view/html/mainHead.php, view/html/mainProfile.php.
- No dependency, schema or configuration changes, push, PR or deployment. User now explicitly authorized a feature-only commit and read-only query verification on test server 10.10.10.16. Credentials must remain private; connect only to that host, bounded queries in a READ ONLY transaction, no remote writes.

## Tasks
- [x] T1 Implement coherent search/history UI and endpoints with dependency-free test-first regression coverage and usage documentation. Status: done for implementation and focused checks; runtime acceptance tracked separately.
- [x] T2 Independently verify focused tests, syntax, protected-file baseline and review implementation limitations. Status: done; independent checks pass, inherited CRLF incident resolved with one-shot convention-aware check.
- [x] T3 Perform user-owned native review if consent permits, resolve scoped blockers and report verified outcome. Status: done. Native review approved and exact acknowledgement consumed authority.
- [ ] T4 Verify PostgreSQL query behavior. Status: partial; user manually executed provided SQL equivalents successfully for one citizen: search count 5 equals five unique history rows, all state 1. Company grouping, other states and actual application's pagination SQL remain pending. Agent remote verification stopped on backend-address mismatch; no further remote connections.
- [ ] T5 Perform browser visual/runtime acceptance after deployment. Status: pending; deployment not requested.
- [ ] T6 Commit only feature files, tests, documentation and tracking after query results reviewed; preserve preexisting unrelated changes. Status: done; feature commit 89894e30c7887adddab7e04f8612984a02e5056c on feat/consulta-por-nombre contains only the eight feature files. Test-server query verification remains blocked and must not be reported passed.

- [x] T7 Fix print action to accept actual number-year order identifiers without losing leading zeros; regression tests, focused checks, native review and separate authorized commit. Status: done. Commit 707a2d3b24454cd22f7276570911fddd4e0e993d. Writer observed Node RED 6 pass/3 fail then GREEN 9/9; PHP tests, JS syntax and scoped diff check pass. Parent spot check Node 9/9. ID format evidence models/Tasa.php:217–221 pads minimum six digits plus year. Native review review-fbd2b57258dfed2a approved and acknowledgement burned authority. No backend/deployment changes.

## Acceptance and checks
Counts equal unique history membership; namesakes separate; company aliases and same RUC converge; missing RUC safely separate; all statuses visible; stable pagination; safe DOM rendering; authenticated endpoints; reusable print action.
Required: git diff --check; PHP lint of edited PHP files and test; node --check new JS; php tests/consultar_nombre_test.php; node --test tests/consultarnombre.test.cjs. Observe RED then GREEN for applicable deterministic behavior. Live PostgreSQL execution and browser visual checks must be reported pending if unavailable; mock/query-construction tests do not prove deployed SQL.

## Evidence
Read-only explorer mapped associations via td_giro_tasa_ciudadano -> td_tasatciud -> td_procedciudadano. No authoritative DDL or existing runner found. RDD enabled globally. Writer observed PHP RED (undefined method) and Node RED (missing module), then PHP GREEN and Node 6/6 GREEN. All PHP/JS syntax checks pass; protected diff hash unchanged. git diff --check returned exit 2; verifier identified inherited CRLF flagged as trailing whitespace in candidate model/controller and preexisting protected-file changes. Raw protected hash unchanged. Convention-aware one-shot diff check requested; no normalization or persistent Git configuration changes authorized. PostgreSQL and real browser checks pending.

## Next step
Next: authorized runtime acceptance of PostgreSQL and browser. Native review review-4bfdda1047caa401 approved; acknowledgement returned authority burned for target sha256:f7d726a929f3d9d84699fb31dbc0fcd44ab259826553ff6438667a306d36ffcf, consumed revision sha256:2c49d3daaa2da5d9ab596989830d81c64637659a81bd1cd2c8979924a98030a1. Advisory R3-sql-behavior-unproved is informational and tracked by T4, not a blocker or correction. Independent verifier confirmed syntax, PHP tests, Node 6/6, protected hash and convention-aware scoped diff exit 0. New untracked feature files have no trailing whitespace. Static SQL review found no definite defect but cannot prove deployed execution. Documentation corrected: actual print endpoint restricts details to states 1–5; states 0,6,unknown remain visible but lack printable details from legacy backend. Commit evidence: 89894e30c7887adddab7e04f8612984a02e5056c — feat(consultas): add name and business order history search. Eight files, 751 insertions, 2 deletions; cached convention-aware whitespace check passed. Protected raw diff hash unchanged before commit. No push/deployment. Next: manual enterprise/all-state/application pagination checks and browser acceptance after user-authorized deployment. T7 print fix completed and committed; live print/PDF rendering remains unverified. Preserve protected changes. Synthetic example 000123-2026 must submit unchanged; malformed IDs must not submit. Actual legacy ID-generation references must guide validation. Browser acceptance remains pending.
