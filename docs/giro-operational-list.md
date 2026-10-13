# Find pending work and giro history

The citizen giro listing defaults to **En curso**: pending trámites without a valid issued order plus orders labeled Girado. It searches and pages on the server, without the previous 200-row visibility cutoff.

## Quick path
1. Select your area and procedure using the existing selectors.
2. Keep En curso for operational work, or select an individual state or Todos los estados for history.
3. Optionally choose creation dates; leave both blank for all dates. Search by procedure/order code, citizen identity or company identity, then navigate the pages.

**Actualizar listado** preserves the procedure, state, dates, search and current page. Changing filters starts at the first page. **Restablecer filtros** restores En curso and blank dates; it does not clear the procedure or DataTables search. Modal actions continue to reload the same table.

## State and date semantics
- Pendiente is derived from actionable fees without a valid issued association, not a stored order state. Cancellation can expose a pending row alongside cancelled history; partial issuance can expose pending and issued rows together.
- Individual filter choices are Girado, Pagado, Anulado, Usado and Extornado. Improcedente is no longer offered as a filter; existing historical rows keep their label and remain visible through Todos los estados. Unverified state codes retain Desconocido and are available through Todos los estados, not a fabricated state filter.
- Dates refer to **trámite creation**, never payment. Either endpoint is optional, the end date includes that calendar day, and a reversed range is rejected. Existing database timestamp semantics remain unchanged.
- Search uses literal text, not regular expressions or wildcard syntax; the server accepts at most 200 UTF-8 bytes. Pages allow 10, 25, 50 or 100 rows with fixed newest-first ordering.

## Access and recovery
History remains scoped to the session person/role and active area assignment; selecting an area only narrows access. It does not grant cross-area visibility. No procedure selected means an empty table and a selection instruction.

The refresh indicator follows request completion, not a timer. Older requests cannot restore rows after selection/filter changes. Errors clear the displayed rows and show a failure message: check the filters or refresh again after connectivity/session recovery. Requests time out after 30 seconds; no success is announced for failed or malformed responses.

## Verification limitations
PHP application/SQL-shape doubles and Node VM contracts cover authorization bindings, serialization, filters, paging parameters, singleton reload and stale/error responses. They do **not** execute PostgreSQL or a real browser. Live schema/query execution, timezone behavior, cancellation/partial issuance fixtures and browser accessibility remain integration checks. Native G1 review note R3-001 preserves the PostgreSQL limitation; it is nonblocking, not a new review request.
