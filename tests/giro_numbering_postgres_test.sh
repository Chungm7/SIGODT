#!/usr/bin/env bash
# Only a disposable, network-isolated synthetic database; never uses PGHOST/PGDATABASE.
set -euo pipefail
cd "$(dirname "$0")/.."
image=postgres:17
if ! docker image inspect "$image" >/dev/null 2>&1; then
  echo 'SKIP: cached postgres:17 / Docker unavailable; no SQL proof' >&2
  exit 77
fi
name="sigodt-giro-test-$$"
socket_dir=$(mktemp -d /tmp/sigodt-giro-synthetic.XXXXXX)
chmod 777 "$socket_dir"
# Only a fresh synthetic socket directory is shared; no repository or data mount.
docker run --rm -d --name "$name" --network none --mount "type=bind,source=$socket_dir,target=/synthetic" -e POSTGRES_HOST_AUTH_METHOD=trust "$image" postgres -c unix_socket_directories=/var/run/postgresql,/synthetic >/dev/null
trap 'docker stop -t 1 "$name" >/dev/null' EXIT
# One bounded readiness wait inside the disposable container, not a host/server probe.
docker exec "$name" sh -c 'for i in 1 2 3 4 5; do pg_isready -U postgres >/dev/null && exit 0; sleep 1; done; exit 1'
docker exec -i "$name" psql -X -U postgres -v ON_ERROR_STOP=1 -v legacy="${1:-false}" < tests/giro_numbering_postgres_test.sql
if [[ ${1:-false} == true ]]; then exit 1; fi
docker exec -i "$name" psql -X -U postgres -v ON_ERROR_STOP=1 < database/migrations/20261016_atomic_giro_numbering.sql
docker exec -i "$name" psql -X -U postgres -v ON_ERROR_STOP=1 -v first=true < tests/giro_numbering_postgres_test.sql
docker exec -i "$name" psql -X -U postgres -v ON_ERROR_STOP=1 < database/migrations/20261016_atomic_giro_numbering.sql
# Repeat migration against allocated counters as well as historical data.
docker exec -i "$name" psql -X -U postgres -v ON_ERROR_STOP=1 -v verify=true < tests/giro_numbering_postgres_test.sql
php tests/giro_numbering_test.php --postgres-socket "$socket_dir"
snapshot_sql="SELECT json_build_array((SELECT json_agg(h ORDER BY ogciud_id) FROM sc_giros.td_ordengirociud h),(SELECT json_agg(c ORDER BY year) FROM sc_giros.giro_year_counter c),(SELECT json_agg(d ORDER BY tasaciud_id) FROM sc_giros.td_giro_tasa_ciudadano d))::text"
before=$(docker exec "$name" psql -X -At -U postgres -v ON_ERROR_STOP=1 -c "$snapshot_sql")
docker exec -i "$name" psql -X -U postgres -v ON_ERROR_STOP=1 < database/migrations/20261016_atomic_giro_numbering.sql
after=$(docker exec "$name" psql -X -At -U postgres -v ON_ERROR_STOP=1 -c "$snapshot_sql")
[[ "$before" == "$after" ]] || { echo 'Rerun changed persisted identifiers/details/counters' >&2; exit 1; }
docker exec "$name" psql -X -U postgres -v ON_ERROR_STOP=1 -c "DO \$\$ BEGIN IF (SELECT last_value FROM sc_giros.giro_year_counter WHERE year=2026) < 27050 THEN RAISE EXCEPTION 'counter decreased'; END IF; END \$\$;"
# Real model calls on independent PHP/PDO connections: six single, six group.
docker exec "$name" psql -X -U postgres -v ON_ERROR_STOP=1 -c "INSERT INTO sc_giros.td_procedciudadano SELECT 1000+i,1,1 FROM generate_series(1,12) i; INSERT INTO sc_giros.td_tasatciud SELECT 1000+2*i+j,1,1000+i,1 FROM generate_series(1,12) i CROSS JOIN generate_series(0,1) j;" >/dev/null
pids=()
for i in {1..12}; do
  php tests/giro_numbering_test.php --postgres-socket "$socket_dir" concurrent "$i" &
  pids+=("$!")
done
for pid in "${pids[@]}"; do wait "$pid"; done
docker exec "$name" psql -X -U postgres -v ON_ERROR_STOP=1 -c "DO \$\$ BEGIN IF (SELECT count(DISTINCT girot_giro) FROM sc_giros.td_giro_tasa_ciudadano WHERE tasaciud_id BETWEEN 1002 AND 1025) <> 12 OR (SELECT count(*) FROM sc_giros.td_giro_tasa_ciudadano WHERE tasaciud_id BETWEEN 1002 AND 1025) <> 18 OR EXISTS (SELECT 1 FROM sc_giros.td_giro_tasa_ciudadano d JOIN sc_giros.td_ordengirociud h ON h.ogciud_id=d.girot_giro WHERE d.tasaciud_id BETWEEN 1002 AND 1025 AND h.pers_id<>(d.tasaciud_id-1000)/2) THEN RAISE EXCEPTION 'ownership'; END IF; END \$\$;"
echo "PostgreSQL allocation, model transactions, rerun and separate-connection ownership checks passed. Synthetic socket directory: $socket_dir"
