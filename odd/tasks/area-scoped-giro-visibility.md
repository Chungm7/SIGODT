# Area-scoped giro visibility across Consultas, Auditoria, and RC

## Objective and problem
Ensure users assigned to one or more areas (`sc_giros.td_areausu` with `est = 1`) can only view, query, audit, and print "órdenes de giro" belonging to their assigned area(s), with full global access reserved exclusively for the Administrator role (`rol_id_SIGODT = 9`). Currently, only the operational registration view (`view/giros_ciudadano/`) enforces multi-area membership, while Consultation views (`Consultas`), the Audit table endpoint (`view/admin_giros_ciudadano/` -> `op=listar_proced_ciudadano`), and Receipt/Print endpoints (`controller/rc.php`) either lack area filtering or rely on the legacy single-area session variable (`$_SESSION["usu_depe_id_SIGODT"]`).

## Authorized scope and constraints
- Branch: `feat/area-scoped-giro-visibility` (branched from `dev`).
- Delivery strategy: `auto-chain` with cached project chain strategy `feature-branch-chain` (reused from `odd/tasks/personal-emission-report.md`; no push or PR authorized).
  - Running authored changed lines: `1177` across Slice A + Slice B (`594` in T1 + `583` in T2).
  - Slice boundaries:
    - **Slice A (Consultas multi-area scoping):** commit `2e6f37dd` (`594` authored lines: `348` production lines in `controller/ordengiro.php` + `models/OrdenGiro.php`, `246` test lines in `tests/area_scoped_giro_visibility_test.php`).
    - **Slice B (Auditoría, Procedimiento detail, and RC/Print multi-area scoping):** commit `fd144d06` (immediate parent `2e6f37dd`; `583` authored lines: `384` production lines in `controller/procedimiento.php`, `models/Procedimiento.php`, `controller/rc.php`, `models/RC.php`, and `199` test lines in `tests/area_scoped_giro_visibility_test.php`).
- Unrelated dirty file `view/reporte_usuario/index.php` must remain untouched and uncommitted.
- Reject unauthenticated requests (`empty($_SESSION["usua_id_SIGODT"])`) before querying data.
- Role `9` (`(int)($_SESSION["rol_id_SIGODT"] ?? 0) === 9`) bypasses the `sc_giros.td_areausu` restriction.
- Non-admin users (`role !== 9`) must only see procedures/orders whose area (`p.proced_area`) exists in `sc_giros.td_areausu` with `tau.pers_id = :person AND tau.depe_id = p.proced_area AND tau.est = 1`.

## Acceptance criteria (Specs)
- **S1 (Consultas multi-area scoping):** In `controller/ordengiro.php` and `models/OrdenGiro.php`, all consultation operations (`get_ordenes_giro`, `buscar_entidades_nombre`, `historial_entidad`, `get_orden_giro` / `get_orden_giro_by_id`, `get_ordenes_mes` / `get_total_ordenes_mes`, and legacy consultation operations `consultarOrden`, `getOrdenes`, `getTasasOrdenes`, `getTasasSemana`, `getgirosUsuarios`, `getgirosUsuariosAreas`) require an active session (`usua_id_SIGODT`, `rol_id_SIGODT`) and restrict results to orders whose procedure area (`proced_area`) is in the user's active `sc_giros.td_areausu` rows unless `role === 9`.
- **S2 (Auditoría & Procedimiento detail scoping):** In `controller/procedimiento.php` and `models/Procedimiento.php`, `op=listar_proced_ciudadano` (`get_procedciudadano_id_admin`), `op=get_data_proced_ciud` (`get_procedciudadano_idprocedciudadano`), `op=mostrar_procedciudadano`, and `op=listaConsulta` require an active session and enforce multi-area authorization via `sc_giros.td_areausu` (with `role === 9` bypass).
- **S3 (Impresión / RC multi-area scoping):** In `controller/rc.php` and `models/RC.php`, `op=imprimir`, `op=imprimirxid`, `op=listargiros`, and `op=listargiros_total` (`get_girostotal`) require an active session and enforce multi-area authorization via `sc_giros.td_areausu` (with `role === 9` bypass), replacing the legacy single-area `$_SESSION["usu_depe_id_SIGODT"]` filter in `get_girostotal`.

## Applicable checks
- Focused PHP test suite (`php tests/area_scoped_giro_visibility_test.php`) using RED -> GREEN test-first flow.
- Existing PHP test suites (`for f in tests/*_test.php; do php "$f"; done`) and `php -l` syntax checks on all touched PHP files.
- `git diff --check` cleanliness.
- Independent read-only verifier (Risk tier: `high` — authorization and multi-area access control).

## Tasks
- [x] **T1** | S1 | Route: `delegated` (`odd-worker`, Writer + Preparation triggers) | Risk: `high` | Done: Enforced multi-area (`sc_giros.td_areausu` + `role = 9` bypass) filtering and session validation across Consultation endpoints (`controller/ordengiro.php`, `models/OrdenGiro.php`); RED observed (exit 255), GREEN 6/6 S1 test groups + 16/16 PHP suites passed; parent spot check passed | commit: `2e6f37dd` (Slice A, 594 authored lines)
- [x] **T2** | S2, S3 | Route: `delegated` (`odd-worker`, Writer + Preparation triggers) | Risk: `high` | Done: Enforced multi-area (`sc_giros.td_areausu` + `role = 9` bypass) filtering across Auditoría/Procedimiento detail and RC/Print endpoints (`controller/procedimiento.php`, `models/Procedimiento.php`, `controller/rc.php`, `models/RC.php`); RED observed (exit 255), GREEN S1–S3 + 16/16 PHP suites passed; parent spot check passed | commit: `fd144d06` (Slice B, 583 authored lines)
- [x] **T3** | S1, S2, S3 | Route: `delegated` (`odd-verifier`, Independent verifier for `high` risk tier + parent spot check) | Risk: `high` | Done: Independent read-only verifier PASS on S1–S3 across `2e6f37dd` and `fd144d06`; all 16 PHP test suites, `php -l` syntax checks, and `git diff --check` exit 0; final parent spot check passed. Live PostgreSQL/browser acceptance not exercised in CLI environment | commits: `2e6f37dd`, `fd144d06`

## Log
- **L1:** Initial read-only audit found multi-area filtering (`sc_giros.td_areausu` with `role = 9` bypass) enforced in `view/giros_ciudadano/` (`Procedimiento::get_operational_list`), but missing in `Consultas` (`OrdenGiro`), `Auditoría` (`Procedimiento::get_procedciudadano_id_admin`), and `RC` (`imprimir`, `imprimirxid`, `listargiros`, and single-area `get_girostotal`).
- **L2:** User authorized enforcing multi-area visibility (`sc_giros.td_areausu` with `rol_id_SIGODT = 9` bypass) across Consultas, Auditoría, and Impresión/RC.
- **L3:** T1 completed by `odd-worker` in commit `2e6f37dd` (`594` authored lines: `348` production, `246` tests). Parent spot check (`php tests/area_scoped_giro_visibility_test.php`) passed. Reused cached `feature-branch-chain` strategy (Slice A = `2e6f37dd`, Slice B = T2 commit).
- **L4:** T2 completed by `odd-worker` in commit `fd144d06` (`583` authored lines: `384` production, `199` tests). Parent spot check (`php tests/area_scoped_giro_visibility_test.php`) passed.
- **L5:** T3 independent verification (`odd-verifier`) returned PASS for S1–S3, 16/16 PHP test suites, syntax checks, and diff cleanliness. Final parent spot check passed. Unrelated dirty file `view/reporte_usuario/index.php` preserved untouched.
