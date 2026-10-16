-- Paused-emission deployment only. See docs/giro-numbering.md before applying.
BEGIN;
SET LOCAL lock_timeout = '5s';
SET LOCAL statement_timeout = '30s';
LOCK TABLE sc_giros.td_ordengirociud IN ACCESS EXCLUSIVE MODE;
DO $$
BEGIN
    IF current_setting('server_version_num')::integer < 140000 THEN
        RAISE EXCEPTION 'PostgreSQL 14+ required';
    END IF;
    IF EXISTS (SELECT 1 FROM sc_giros.td_ordengirociud
               WHERE ogciud_id IS NULL OR ogciud_id !~ '^[0-9]+-[0-9]{4}$'
                  OR split_part(ogciud_id, '-', 1)::numeric NOT BETWEEN 1 AND 9223372036854775806
                  OR split_part(ogciud_id, '-', 2)::integer NOT BETWEEN 1 AND 9999) THEN
        RAISE EXCEPTION 'Invalid historical giro identifiers; review without renumbering';
    END IF;
    IF NOT EXISTS (
        SELECT 1 FROM pg_catalog.pg_index i
        JOIN pg_catalog.pg_attribute a ON a.attrelid=i.indrelid AND a.attnum=ANY(i.indkey)
        WHERE i.indrelid='sc_giros.td_ordengirociud'::regclass
          AND i.indisunique AND i.indisvalid AND i.indpred IS NULL
          AND i.indexprs IS NULL AND i.indnkeyatts=1 AND a.attname='ogciud_id'
    ) THEN
        RAISE EXCEPTION 'Unique header identifier constraint required';
    END IF;
    -- Bounded varchar columns could reject seven-digit prefixes. Do not silently
    -- alter dependencies; the operator must review header/detail column types.
    IF EXISTS (
        SELECT 1 FROM pg_catalog.pg_attribute
        WHERE (attrelid='sc_giros.td_ordengirociud'::regclass AND attname='ogciud_id'
            OR attrelid='sc_giros.td_giro_tasa_ciudadano'::regclass AND attname='girot_giro')
          AND (atttypid NOT IN ('text'::regtype, 'varchar'::regtype) OR atttypmod <> -1)
    ) THEN
        RAISE EXCEPTION 'Unbounded text/varchar identifiers required in header and details';
    END IF;
END $$;
CREATE TABLE IF NOT EXISTS sc_giros.giro_year_counter (
    year integer PRIMARY KEY CHECK (year BETWEEN 1 AND 9999),
    last_value bigint NOT NULL CHECK (last_value > 0)
);
LOCK TABLE sc_giros.giro_year_counter IN ACCESS EXCLUSIVE MODE;
INSERT INTO sc_giros.giro_year_counter AS c(year, last_value)
SELECT split_part(ogciud_id, '-', 2)::integer,
       max(split_part(ogciud_id, '-', 1)::bigint)
FROM sc_giros.td_ordengirociud
GROUP BY split_part(ogciud_id, '-', 2)::integer
ON CONFLICT (year) DO UPDATE SET last_value=greatest(c.last_value, EXCLUDED.last_value);

CREATE OR REPLACE FUNCTION sc_giros.allocate_giro_number(p_date date)
RETURNS text LANGUAGE plpgsql SECURITY INVOKER SET search_path = pg_catalog AS $$
DECLARE y integer; n bigint;
BEGIN
    y := extract(year FROM p_date)::integer;
    IF y IS NULL OR y NOT BETWEEN 1 AND 9999 THEN
        RAISE EXCEPTION 'Invalid allocation date' USING ERRCODE='22023';
    END IF;
    INSERT INTO sc_giros.giro_year_counter AS c(year,last_value) VALUES(y,1)
    ON CONFLICT (year) DO UPDATE SET last_value=c.last_value+1
    RETURNING last_value INTO n;
    RETURN repeat('0', greatest(0,6-length(n::text))) || n::text || '-' || repeat('0', greatest(0,4-length(y::text))) || y::text;
END $$;

CREATE OR REPLACE FUNCTION sc_giros.crear_ordengiro_ciud_id(p_usuario_id integer, p_comentario text)
RETURNS text LANGUAGE plpgsql SECURITY INVOKER SET search_path = pg_catalog AS $$
DECLARE id text;
BEGIN
    IF p_usuario_id IS NULL OR p_usuario_id <= 0 OR p_comentario IS NULL THEN
        RAISE EXCEPTION 'Invalid header input' USING ERRCODE='22023';
    END IF;
    INSERT INTO sc_giros.td_ordengirociud(ogciud_id,fechacrea,pers_id,ogciud_comentario,est)
    VALUES(sc_giros.allocate_giro_number(CURRENT_DATE),CURRENT_TIMESTAMP,p_usuario_id,upper(p_comentario),1)
    RETURNING ogciud_id INTO id;
    RETURN id;
END $$;

-- Keep the user-supplied external CALL(integer,text) signature, without global lookup.
CREATE OR REPLACE PROCEDURE sc_giros.crear_ordengiro_ciud(IN p_usuario_id integer, IN p_comentario text)
LANGUAGE plpgsql SECURITY INVOKER SET search_path = pg_catalog AS $$
BEGIN
    PERFORM sc_giros.crear_ordengiro_ciud_id(p_usuario_id,p_comentario);
END $$;
REVOKE ALL ON sc_giros.giro_year_counter FROM PUBLIC;
REVOKE ALL ON FUNCTION sc_giros.allocate_giro_number(date) FROM PUBLIC;
REVOKE ALL ON FUNCTION sc_giros.crear_ordengiro_ciud_id(integer,text) FROM PUBLIC;
REVOKE ALL ON PROCEDURE sc_giros.crear_ordengiro_ciud(integer,text) FROM PUBLIC;
COMMIT;
