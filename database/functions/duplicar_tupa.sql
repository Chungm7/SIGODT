-- =============================================================================
-- Función: sc_giros.duplicar_tupa(p_tupa_id integer)
-- Descripción: Clona íntegramente un documento normativo (TUPA o TUSNE) junto con
--              todos sus procedimientos activos, sus tasas y sus requisitos,
--              generando los nuevos identificadores y referencias contables.
--              El nuevo documento se crea en estado 3 ('En Proceso') y desbloqueado ('0').
-- Retorno: integer (ID del nuevo documento creado en sc_giros.tm_tupa)
-- =============================================================================

CREATE OR REPLACE FUNCTION sc_giros.duplicar_tupa(p_tupa_id integer)
RETURNS integer
LANGUAGE plpgsql
AS $$
DECLARE
    r_orig RECORD;
    r_proc RECORD;
    v_nuevo_tupa_id integer;
    v_nuevo_proced_id integer;
BEGIN
    -- 1. Validar existencia y consistencia del documento normativo de origen
    IF p_tupa_id IS NULL OR p_tupa_id <= 0 THEN
        RAISE EXCEPTION 'Identificador de documento no válido: %', p_tupa_id;
    END IF;

    SELECT tupa_nom, tupa_año, tipo_doc
    INTO r_orig
    FROM sc_giros.tm_tupa
    WHERE tupa_id = p_tupa_id;

    IF NOT FOUND THEN
        RAISE EXCEPTION 'El documento normativo de origen con ID % no existe.', p_tupa_id;
    END IF;

    -- 2. Insertar cabecera del nuevo documento
    -- Estado inicial: 3 (En Proceso) para preparación de catálogo antes de vigencia.
    -- Bloqueo: '0' (Desbloqueado para permitir edición).
    INSERT INTO sc_giros.tm_tupa (
        tupa_nom,
        tupa_año,
        est,
        tupa_block,
        tipo_doc
    ) VALUES (
        COALESCE(r_orig.tupa_nom, 'DOCUMENTO') || ' (Copia)',
        COALESCE(r_orig.tupa_año, EXTRACT(YEAR FROM CURRENT_DATE)::integer),
        3,
        '0',
        COALESCE(r_orig.tipo_doc, 'TUPA')
    )
    RETURNING tupa_id INTO v_nuevo_tupa_id;

    -- 3. Clonar procedimientos y sus dependencias (tasas y requisitos)
    -- Política de catálogo: Se clonan exclusivamente procedimientos activos (est = 1),
    -- excluyendo trámites dados de baja/eliminados (est = 0).
    FOR r_proc IN
        SELECT
            proced_id,
            proced_cod,
            proced_nom,
            proced_area,
            proced_tipocampo,
            proced_administradotipo,
            proced_tipoindvasc
        FROM sc_giros.tm_procedimiento
        WHERE proced_tupa = p_tupa_id
          AND est = 1
        ORDER BY proced_id
    LOOP
        -- 3.1. Insertar el nuevo procedimiento vinculado al nuevo documento
        INSERT INTO sc_giros.tm_procedimiento (
            proced_tupa,
            proced_area,
            proced_cod,
            proced_nom,
            fechacrea,
            est,
            proced_tipocampo,
            proced_administradotipo,
            proced_tipoindvasc
        ) VALUES (
            v_nuevo_tupa_id,
            r_proc.proced_area,
            r_proc.proced_cod,
            r_proc.proced_nom,
            NOW(),
            1,
            r_proc.proced_tipocampo,
            r_proc.proced_administradotipo,
            r_proc.proced_tipoindvasc
        )
        RETURNING proced_id INTO v_nuevo_proced_id;

        -- 3.2. Clonar tasas activas del procedimiento (est = 1)
        INSERT INTO sc_giros.td_tasaproced (
            tasa_id,
            proced_id,
            tasaproced_pos,
            tasaproced_monto,
            est,
            desc_tasa,
            cod_ref,
            tasa_partida,
            is_multiplica,
            tasa_rubro
        )
        SELECT
            tp.tasa_id,
            v_nuevo_proced_id,
            tp.tasaproced_pos,
            tp.tasaproced_monto,
            1,
            tp.desc_tasa,
            'TP' || v_nuevo_tupa_id || '|' || r_proc.proced_area || '|' || v_nuevo_proced_id || '|' || tp.tasa_id,
            tp.tasa_partida,
            tp.is_multiplica,
            tp.tasa_rubro
        FROM sc_giros.td_tasaproced tp
        WHERE tp.proced_id = r_proc.proced_id
          AND tp.est = 1;

        -- 3.3. Clonar requisitos activos del procedimiento (est = 1)
        INSERT INTO sc_giros.td_reqproced (
            reqproced_req,
            reqproced_proced,
            reqproced_pos,
            est
        )
        SELECT
            rp.reqproced_req,
            v_nuevo_proced_id,
            rp.reqproced_pos,
            1
        FROM sc_giros.td_reqproced rp
        WHERE rp.reqproced_proced = r_proc.proced_id
          AND rp.est = 1;

    END LOOP;

    RETURN v_nuevo_tupa_id;
END;
$$;
