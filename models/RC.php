<?php
class RC extends Conectar
{
    public function get_datos_giro($tasatciudadano_id)
    {
        try {
            $conectar = parent::conexion();
            parent::set_names();
            $sql = " SELECT 
                    og.ogciud_id,
                    og.recibo_nro,
                    TO_CHAR(og.fechacrea, 'YYYY-MM-DD') AS fecha,
                    TO_CHAR(og.fechacrea, 'HH12:MI:SS AM') AS hora,
                    CONCAT(tu.pers_nombre, ' ', tu.pers_apelpat, ' ', tu.pers_apelmat) AS nombre_completo,
                    CONCAT(c.ciud_nombre, ' ', c.ciud_primer_apellido, ' ', c.ciud_segundo_apellido) AS nombre_ciudadano,
                    c.ciud_numero_documento AS ciudadano_dni,
                    gt.importe,
                    gt.cantidad,
                    t.proced_nom,
                    ta.depe_denominacion,
                    ts.tasa_nom,
                    CAST(t.proced_tipoindvasc AS character varying),
                    tt.empresa_ruc,
                    tt.empresa_razon_social,
                    empr.empr_ruc,
                    empr.empr_razon_social,
                    empr.empr_direccion,
                    empr.empr_nombre_comercial,
                    tm.tupa_nom as proced_tupa,
                    CAST(og.ogciud_comentario AS character varying) AS ogciud_comentario,
                    c.ciud_domicilio_real,
                    c.tido_id,
					lm.lomu_direccion,
                    lm.lomu_denominacion
                FROM 
                    sc_giros.td_ordengirociud og
                INNER JOIN 
                    sc_escalafon.tb_persona tu ON og.pers_id = tu.pers_id
                INNER JOIN 
                    sc_giros.td_giro_tasa_ciudadano gt ON gt.girot_giro = og.ogciud_id
                INNER JOIN 
                    sc_giros.td_tasatciud ttc ON gt.tasaciud_id = ttc.tasatciud_id
                INNER JOIN 
                    sc_giros.td_procedciudadano tt ON ttc.tasatciud_procedciud = tt.procedciudadano_id
                INNER JOIN 
                    public.tb_ciudadano c ON c.ciud_id = tt.ciud_id
                INNER JOIN 
                    sc_giros.tm_procedimiento t ON tt.proced_id = t.proced_id
                INNER JOIN 
                    sc_giros.td_tasaproced tst ON ttc.tasatciud_tasaproced = tst.tasaproced_id
                INNER JOIN 
                    sc_giros.tm_tasa ts ON tst.tasa_id = ts.tasa_id
                INNER JOIN 
                    tb_dependencia ta ON t.proced_area = ta.depe_id
				Inner join 
					sc_escalafon.tb_local_municipal lm on lm.lomu_id  = ta.lomu_id
                INNER JOIN 
                    sc_giros.tm_tupa tm ON t.proced_tupa = tm.tupa_id
                LEFT JOIN 
                    public.tb_empresa empr ON empr.empr_id = tt.empr_id
                WHERE 
                    ttc.tasatciud_id = ? AND (og.est = 1 OR og.est = 2 OR og.est = 3 OR og.est = 4 OR og.est = 5);";
            $stmt = $conectar->prepare($sql);
            $stmt->bindValue(1, $tasatciudadano_id);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            echo "Error en la consulta: " . $e->getMessage();
            return null;
        }
    }
    public function get_datos_giro_id($ogciud_id)
    {
        try {
            $conectar = parent::conexion();
            parent::set_names();
            $sql = "SELECT 
            og.ogciud_id,
            og.est orden_est,
            tt.est as procedimiento_est,
            TO_CHAR(og.fechacrea, 'YYYY-MM-DD') AS fecha,
            TO_CHAR(og.fechacrea, 'HH12:MI:SS AM') AS hora,
            CONCAT(tu.pers_nombre, ' ', tu.pers_apelpat, ' ', tu.pers_apelmat) AS nombre_completo,
            CONCAT(c.ciud_nombre, ' ', c.ciud_primer_apellido, ' ', c.ciud_segundo_apellido) AS nombre_ciudadano,
            c.ciud_numero_documento AS ciudadano_dni,
            c.ciud_domicilio_real,
            c.tido_id,
            tm.tupa_nom as proced_tupa,
            t.proced_nom,
            ta.depe_denominacion,
            ts.tasa_nom,
            gt.importe,
            gt.cantidad,
            t.proced_tipoindvasc,
            tt.empresa_ruc,
            tt.empresa_razon_social,
            empr.empr_id,
            empr.empr_ruc,
            empr.empr_razon_social,
            empr.empr_nombre_comercial,
            empr.empr_direccion,
            og.ogciud_comentario,
            lm.lomu_direccion,
            lm.lomu_denominacion
        FROM 
            sc_giros.td_ordengirociud og
        INNER JOIN 
            sc_escalafon.tb_persona tu ON og.pers_id = tu.pers_id
        INNER JOIN 
            sc_giros.td_giro_tasa_ciudadano gt ON gt.girot_giro = og.ogciud_id
        INNER JOIN 
            sc_giros.td_tasatciud ttc ON gt.tasaciud_id = ttc.tasatciud_id
        INNER JOIN 
            sc_giros.td_procedciudadano tt ON ttc.tasatciud_procedciud = tt.procedciudadano_id
        INNER JOIN 
            public.tb_ciudadano c ON c.ciud_id = tt.ciud_id
        INNER JOIN 
            sc_giros.tm_procedimiento t ON tt.proced_id = t.proced_id
        INNER JOIN 
            sc_giros.td_tasaproced tst ON ttc.tasatciud_tasaproced = tst.tasaproced_id
        INNER JOIN 
            sc_giros.tm_tasa ts ON tst.tasa_id = ts.tasa_id
        INNER JOIN 
            tb_dependencia ta ON t.proced_area = ta.depe_id
        Inner join 
			sc_escalafon.tb_local_municipal lm on lm.lomu_id  = ta.lomu_id
        INNER JOIN 
            sc_giros.tm_tupa tm ON t.proced_tupa = tm.tupa_id
        LEFT JOIN 
            public.tb_empresa empr ON empr.empr_id = tt.empr_id
        WHERE 
        og.ogciud_id = ?  AND (og.est = 1 OR og.est = 2 OR og.est = 3 OR og.est = 4 OR og.est = 5)
                order by og.fechacrea desc";
            $stmt = $conectar->prepare($sql);
            $stmt->bindValue(1, $ogciud_id,  PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            echo "Error en la consulta: " . $e->getMessage();
            return null;
        }
    }
    public function get_datos_giro_empr($tasatempr_id)
    {
        try {
            $conectar = parent::conexion();
            parent::set_names();
            $sql = "SELECT * FROM sc_giros.obtener_ordenes_giro_por_tasatempresa_t(?)";
            $stmt = $conectar->prepare($sql);
            $stmt->bindValue(1, $tasatempr_id);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            echo "Error en la consulta: " . $e->getMessage();
            return null;
        }
    }
    public function get_giros($procedciud)
    {
        try {
            $conectar = parent::conexion();
            parent::set_names();
            $sql = "SELECT DISTINCT ON (toc.ogciud_id)
                toc.ogciud_id,
	            toc.recibo_nro,
                to_char(toc.fechacrea, 'YYYY-MM-DD HH24:MI') AS fecha,
                toc.ogciud_comentario,
				toc.pers_id,
				CONCAT(tu.pers_nombre, ' ', tu.pers_apelpat, ' ', tu.pers_apelmat) AS nombre_completo,
                toc.est
         FROM sc_giros.td_ordengirociud toc
         INNER JOIN sc_giros.td_giro_tasa_ciudadano gtc ON gtc.girot_giro = toc.ogciud_id
         INNER JOIN sc_giros.td_tasatciud tte ON tte.tasatciud_id = gtc.tasaciud_id
		 INNER JOIN 
                sc_escalafon.tb_persona tu ON toc.pers_id = tu.pers_id
         WHERE tasatciud_procedciud = ? and toc.est in (1,2,3,4,5)
         ORDER BY toc.ogciud_id, toc.fechacrea DESC;";
            $stmt = $conectar->prepare($sql);
            $stmt->bindValue(1, $procedciud);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            echo "Error en la consulta: " . $e->getMessage();
            return null;
        }
    }
    public function get_giros_total($depe_id)
    {
        try {
            $conectar = parent::conexion();
            parent::set_names();
            $sql = "SELECT 
            og.ogciud_id,
            og.est orden_est,
            tt.est as procedimiento_est,
            TO_CHAR(og.fechacrea, 'YYYY-MM-DD') AS fecha,
            TO_CHAR(og.fechacrea, 'HH12:MI:SS AM') AS hora,
            CONCAT(tu.pers_nombre, ' ', tu.pers_apelpat, ' ', tu.pers_apelmat) AS nombre_completo,
            CONCAT(c.ciud_nombre, ' ', c.ciud_primer_apellido, ' ', c.ciud_segundo_apellido) AS nombre_ciudadano,
            c.ciud_numero_documento AS ciudadano_dni,
            tm.tupa_nom as proced_tupa,
            t.proced_nom,
            ta.depe_denominacion,
            STRING_AGG(ts.tasa_nom, ', ') AS tasas_concatenadas,
            SUM(gt.importe) AS total_monto,
            t.proced_tipoindvasc,
            empr.empr_ruc,
            empr.empr_razon_social,
            empr.empr_nombre_comercial,
            og.ogciud_comentario
        FROM 
            sc_giros.td_ordengirociud og
        INNER JOIN 
            sc_escalafon.tb_persona tu ON og.pers_id = tu.pers_id
        INNER JOIN 
            sc_giros.td_giro_tasa_ciudadano gt ON gt.girot_giro = og.ogciud_id
        INNER JOIN 
            sc_giros.td_tasatciud ttc ON gt.tasaciud_id = ttc.tasatciud_id
        INNER JOIN 
            sc_giros.td_procedciudadano tt ON ttc.tasatciud_procedciud = tt.procedciudadano_id
        INNER JOIN 
            public.tb_ciudadano c ON c.ciud_id = tt.ciud_id
        INNER JOIN 
            sc_giros.tm_procedimiento t ON tt.proced_id = t.proced_id
        INNER JOIN 
            sc_giros.td_tasaproced tst ON ttc.tasatciud_tasaproced = tst.tasaproced_id
        INNER JOIN 
            sc_giros.tm_tasa ts ON tst.tasa_id = ts.tasa_id
        INNER JOIN 
            tb_dependencia ta ON t.proced_area = ta.depe_id
        INNER JOIN 
            sc_giros.tm_tupa tm ON t.proced_tupa = tm.tupa_id
        LEFT JOIN 
            public.tb_empresa empr ON empr.empr_id = tt.empr_id     
            WHERE 
     t.proced_area = ?    AND og.fechacrea >= CURRENT_DATE - INTERVAL '30 day' 
        GROUP BY 
            og.ogciud_id,
            og.est,
            tt.est,
            og.fechacrea,
            tu.pers_nombre,
            tu.pers_apelpat,
            tu.pers_apelmat,
            c.ciud_nombre,
            c.ciud_primer_apellido,
            c.ciud_segundo_apellido,
            c.ciud_numero_documento,
            tm.tupa_nom,
            t.proced_nom,
            ta.depe_denominacion,
            t.proced_tipoindvasc,
            empr.empr_ruc,
            empr.empr_razon_social,
            empr.empr_nombre_comercial,
            og.ogciud_comentario
        ORDER BY 
            og.fechacrea DESC;
        ";
            $stmt = $conectar->prepare($sql);
            $stmt->bindValue(1, $depe_id, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            echo "Error en la consulta: " . $e->getMessage();
            return null;
        }
    }

    public function cambiar_comentario($comentario, $ogciud_id)
    {
        try {
            $conectar = parent::conexion();
            parent::set_names();
            $sql = "UPDATE  sc_giros.td_ordengirociud set  ogciud_comentario  = ? 
                where ogciud_id = ?";
            $stmt = $conectar->prepare($sql);
            $stmt->bindValue(1, $comentario, PDO::PARAM_STR);
            $stmt->bindValue(2, $ogciud_id, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            echo "Error en la consulta: " . $e->getMessage();
            return null;
        }
    }
}
