\if :{?first}
DO $$
DECLARE id text;
BEGIN
  id := sc_giros.crear_ordengiro_ciud_id(7,'first current year');
  IF id <> '000001-' || to_char(CURRENT_DATE,'YYYY') THEN RAISE EXCEPTION 'first current year %',id; END IF;
END $$;
INSERT INTO sc_giros.td_ordengirociud VALUES ('027049-2026',now(),1,'history',1),('999999-2025',now(),1,'history',1);
\else
\if :{?verify}
DO $$
DECLARE id text; before_count bigint;
BEGIN
  id := sc_giros.allocate_giro_number(DATE '2035-01-01');
  IF id <> '000001-2035' THEN RAISE EXCEPTION 'first year %', id; END IF;
  id := sc_giros.allocate_giro_number(DATE '2036-01-01');
  IF id <> '000001-2036' OR (SELECT last_value FROM sc_giros.giro_year_counter WHERE year=2035) <> 1 THEN RAISE EXCEPTION 'rollover'; END IF;
  id := sc_giros.allocate_giro_number(DATE '2026-10-16');
  IF id <> '027050-2026' THEN RAISE EXCEPTION 'historical continuation %', id; END IF;
  UPDATE sc_giros.giro_year_counter SET last_value=999999 WHERE year=2035;
  IF sc_giros.allocate_giro_number(DATE '2035-01-01') <> '1000000-2035' THEN RAISE EXCEPTION 'truncation'; END IF;
  SELECT count(*) INTO before_count FROM sc_giros.td_ordengirociud;
  BEGIN
    id := sc_giros.crear_ordengiro_ciud_id(99, 'rollback');
    INSERT INTO sc_giros.td_giro_tasa_ciudadano(tasaciud_id,girot_giro,cantidad,importe,est) VALUES (99,id,1,5,1);
    RAISE EXCEPTION 'late detail failure';
  EXCEPTION WHEN raise_exception THEN NULL;
  END;
  IF (SELECT count(*) FROM sc_giros.td_ordengirociud) <> before_count OR EXISTS (SELECT 1 FROM sc_giros.td_giro_tasa_ciudadano WHERE tasaciud_id=99) THEN RAISE EXCEPTION 'partial group'; END IF;
  BEGIN
    PERFORM sc_giros.allocate_giro_number(NULL);
    RAISE EXCEPTION 'null date accepted';
  EXCEPTION WHEN invalid_parameter_value THEN NULL;
  END;
END $$;
\else
CREATE SCHEMA sc_giros;
CREATE TABLE sc_giros.td_ordengirociud(ogciud_id text PRIMARY KEY, fechacrea timestamp, pers_id integer, ogciud_comentario text, est integer);
CREATE TABLE sc_giros.td_giro_tasa_ciudadano(fechacrea timestamp, tasaciud_id integer, girot_giro text REFERENCES sc_giros.td_ordengirociud, cantidad integer, importe numeric, est integer);
CREATE TABLE sc_giros.td_tasaproced(tasaproced_id integer PRIMARY KEY,tasaproced_monto numeric);
CREATE TABLE sc_giros.td_procedciudadano(procedciudadano_id integer PRIMARY KEY,ciud_id integer,est integer);
CREATE TABLE sc_giros.td_tasatciud(tasatciud_id integer PRIMARY KEY,tasatciud_tasaproced integer,tasatciud_procedciud integer,est integer);
CREATE SCHEMA sc_seguridad;
CREATE TABLE sc_seguridad.tb_bitacora(bita_id bigserial PRIMARY KEY,pers_id integer);
CREATE FUNCTION sc_seguridad.synthetic_audit() RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN INSERT INTO sc_seguridad.tb_bitacora(pers_id) VALUES(NULL); RETURN NEW; END $$;
CREATE TRIGGER synthetic_audit AFTER INSERT ON sc_giros.td_ordengirociud FOR EACH ROW EXECUTE FUNCTION sc_seguridad.synthetic_audit();
CREATE TRIGGER synthetic_audit AFTER INSERT ON sc_giros.td_giro_tasa_ciudadano FOR EACH ROW EXECUTE FUNCTION sc_seguridad.synthetic_audit();
CREATE TRIGGER synthetic_audit AFTER UPDATE ON sc_giros.td_tasatciud FOR EACH ROW EXECUTE FUNCTION sc_seguridad.synthetic_audit();
CREATE SEQUENCE sc_giros.ordengiro_id_sequence;
\if :legacy
INSERT INTO sc_giros.td_ordengirociud VALUES ('027049-2026',now(),1,'history',1),('999999-2025',now(),1,'history',1);
-- Reproduce the supplied text-MAX / first-six / restart group algorithm,
-- then the independent individual nextval allocator (no current-year reset).
DO $$
DECLARE yr text := '2026'; n bigint; id text;
BEGIN
  IF split_part((SELECT MAX(ogciud_id) FROM sc_giros.td_ordengirociud), '-', 2) <> yr THEN
    ALTER SEQUENCE sc_giros.ordengiro_id_sequence RESTART WITH 1;
  END IF;
  SELECT COALESCE(MAX(substring(ogciud_id FROM 1 FOR 6)::integer),0)+1 INTO n FROM sc_giros.td_ordengirociud WHERE ogciud_id LIKE '%-'||yr;
  INSERT INTO sc_giros.td_ordengirociud(ogciud_id) VALUES (lpad(n::text,6,'0')||'-'||yr);
  id := lpad(nextval('sc_giros.ordengiro_id_sequence')::text,6,'0')||'-'||yr;
  INSERT INTO sc_giros.td_ordengirociud(ogciud_id) VALUES (id);
  -- Another grouped emission resets the unrelated individual sequence again.
  IF split_part((SELECT MAX(ogciud_id) FROM sc_giros.td_ordengirociud), '-', 2) <> yr THEN
    ALTER SEQUENCE sc_giros.ordengiro_id_sequence RESTART WITH 1;
  END IF;
  RAISE NOTICE 'old textMAX year resets individual sequence to 1';
  id := lpad(nextval('sc_giros.ordengiro_id_sequence')::text,6,'0')||'-'||yr;
  INSERT INTO sc_giros.td_ordengirociud(ogciud_id) VALUES (id);
END $$;
\endif
\endif
\endif
