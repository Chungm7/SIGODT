<?php
class Ordengiro extends conectar
{
    public function get_orden_id($orden_id)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = 'SELECT 
                        og.ogciud_id,
                        TO_CHAR(og.fechacrea, \'YYYY-MM-DD\') AS fecha,
                        TO_CHAR(og.fechacrea, \'HH12:MI:SS AM\') AS hora,
                        CONCAT(tu.pers_nombre, \' \', tu.pers_apelpat, \' \', tu.pers_apelmat) AS nombre_completo,
                        CONCAT(c.ciud_nombre, \' \', c.ciud_primer_apellido, \' \', c.ciud_segundo_apellido) AS nombre_ciudadano,
                        c.ciud_numero_documento AS ciudadano_dni,
                        gt.importe,
                        t.proced_nom,
                        ta.depe_denominacion AS area_nom,
                        ts.tasa_nom,
                        t.proced_tipoindvasc,
                        empr.empr_ruc,
                        empr.empr_razon_social,
                        empr.empr_direccion,
                        tm.tupa_nom as proced_tupa,
                        og.ogciud_comentario,
                        c.ciud_domicilio_real,
                        c.tido_id
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
                        og.ogciud_id = ? AND og.est = 1';
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $orden_id);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }
    public function get_ordenes()
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = 'SELECT * from sc_giros.td_ordengirociud';
        $sql = $conectar->prepare($sql);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }
    public function get_tasas_ordenes()
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = 'SELECT tt.tasatciud_id, tt.tasatciud_tasaproced, tt.est as estadotasat, tt.tasatciud_procedciud, gtc.girot_id, gtc.fechacrea, gtc.tasaciud_id, gtc.cantidad, gtc.importe, gtc.est as estadotasagirada, gtc.girot_giro
            FROM sc_giros.td_tasatciud tt
            LEFT JOIN sc_giros.td_giro_tasa_ciudadano gtc ON gtc.tasaciud_id = tt.tasatciud_id
            ORDER BY tt.tasatciud_id ASC;
            ';
        $sql = $conectar->prepare($sql);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }

    public function get_tasas_semana()
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = 'SELECT 
            tt.tasatciud_id, 
            tt.tasatciud_tasaproced, 
            tt.est AS estadotasat, 
            tt.tasatciud_procedciud, 
            gtc.girot_id, 
            gtc.fechacrea, 
            gtc.tasaciud_id, 
            gtc.cantidad, 
            gtc.importe, 
            gtc.est AS estadotasagirada, 
            gtc.girot_giro
        FROM 
            sc_giros.td_tasatciud tt
        LEFT JOIN 
            sc_giros.td_giro_tasa_ciudadano gtc ON gtc.tasaciud_id = tt.tasatciud_id
        WHERE 
            (EXTRACT(WEEK FROM gtc.fechacrea) = EXTRACT(WEEK FROM CURRENT_DATE) AND EXTRACT(YEAR FROM gtc.fechacrea) = EXTRACT(YEAR FROM CURRENT_DATE)) 
            OR gtc.fechacrea IS NULL
        ORDER BY 
            tt.tasatciud_id ASC;';
        $sql = $conectar->prepare($sql);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }
    public function get_ordenes_Usuario()
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT DISTINCT  oc.ogciud_id,
        oc.pers_id,
        oc.fechacrea,
        ta.depe_denominacion as area_nom,
        ta.depe_id as area_id,
        CONCAT(sp.pers_nombre, ' ', sp.pers_apelpat, ' ', sp.pers_apelmat) AS nombrecompleto
        FROM sc_giros.td_ordengirociud oc
        INNER JOIN sc_escalafon.tb_persona sp ON oc.pers_id = sp.pers_id
		INNER JOIN 
                sc_giros.td_giro_tasa_ciudadano gt ON gt.girot_giro = oc.ogciud_id
		INNER JOIN 
		sc_giros.td_tasatciud ttc ON ttc.tasatciud_id= gt.tasaciud_id
		INNER JOIN 
		sc_giros.td_tasaproced tp ON ttc.tasatciud_tasaproced = tp.tasaproced_id
		INNER JOIN 
		sc_giros.tm_procedimiento tpm ON tpm.proced_id = tp.proced_id
		 INNER JOIN 
            tb_dependencia ta ON tpm.proced_area = ta.depe_id";
        $sql = $conectar->prepare($sql);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }
    public function get_ordenes_total_usuario($pers_id)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT
                    (SELECT COUNT(DISTINCT oc.ogciud_id)
                     FROM sc_giros.td_ordengirociud oc
                     INNER JOIN sc_escalafon.tb_persona sp ON oc.pers_id = sp.pers_id
                     INNER JOIN sc_giros.td_giro_tasa_ciudadano gt ON gt.girot_giro = oc.ogciud_id
                     INNER JOIN sc_giros.td_tasatciud ttc ON ttc.tasatciud_id = gt.tasaciud_id
                     INNER JOIN sc_giros.td_tasaproced tp ON ttc.tasatciud_tasaproced = tp.tasaproced_id
                     INNER JOIN sc_giros.tm_procedimiento tpm ON tpm.proced_id = tp.proced_id
                     INNER JOIN tb_dependencia ta ON tpm.proced_area = ta.depe_id
                     WHERE oc.pers_id = ?) AS total_general,
                    (SELECT COUNT(DISTINCT oc.ogciud_id)
                     FROM sc_giros.td_ordengirociud oc
                     INNER JOIN sc_escalafon.tb_persona sp ON oc.pers_id = sp.pers_id
                     INNER JOIN sc_giros.td_giro_tasa_ciudadano gt ON gt.girot_giro = oc.ogciud_id
                     INNER JOIN sc_giros.td_tasatciud ttc ON ttc.tasatciud_id = gt.tasaciud_id
                     INNER JOIN sc_giros.td_tasaproced tp ON ttc.tasatciud_tasaproced = tp.tasaproced_id
                     INNER JOIN sc_giros.tm_procedimiento tpm ON tpm.proced_id = tp.proced_id
                     INNER JOIN tb_dependencia ta ON tpm.proced_area = ta.depe_id
                     WHERE oc.pers_id = ? AND DATE(oc.fechacrea) = CURRENT_DATE) AS total_dia,
                    (SELECT COUNT(DISTINCT oc.ogciud_id)
                     FROM sc_giros.td_ordengirociud oc
                     INNER JOIN sc_escalafon.tb_persona sp ON oc.pers_id = sp.pers_id
                     INNER JOIN sc_giros.td_giro_tasa_ciudadano gt ON gt.girot_giro = oc.ogciud_id
                     INNER JOIN sc_giros.td_tasatciud ttc ON ttc.tasatciud_id = gt.tasaciud_id
                     INNER JOIN sc_giros.td_tasaproced tp ON ttc.tasatciud_tasaproced = tp.tasaproced_id
                     INNER JOIN sc_giros.tm_procedimiento tpm ON tpm.proced_id = tp.proced_id
                     INNER JOIN tb_dependencia ta ON tpm.proced_area = ta.depe_id
                     WHERE oc.pers_id = ? AND DATE(oc.fechacrea) = CURRENT_DATE - INTERVAL '1 day') AS total_ayer";
        $sql = $conectar->prepare($sql);
        $sql->execute([$pers_id, $pers_id, $pers_id]);
        return $resultado = $sql->fetchAll();
    }
    
   
   
    public function get_ordenes_Usuario_area()
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT 
        oc.ogciud_id,
        oc.pers_id,
        oc.fechacrea,
        ta.depe_denominacion  as area_nom,
        tp.tasa_id,
        tp.cod_ref,
        ts.tasa_nom,
        tpm.proced_nom,
        ta.depe_id  as area_id,
        ttc.tasatciud_id, 
        ttc.tasatciud_tasaproced,
        ttc.est as estadotasat, 
        ttc.tasatciud_procedciud, 
        gt.girot_id,
        gt.fechacrea, 
        gt.tasaciud_id, 
        gt.cantidad, 
        gt.importe, 
        gt.est as estadotasagirada, 
        gt.girot_giro,
        CONCAT(sp.pers_nombre, ' ', sp.pers_apelpat, ' ', sp.pers_apelmat) AS nombrecompleto
    FROM 
        sc_giros.td_tasatciud ttc
    LEFT JOIN 
        sc_giros.td_tasaproced tp ON ttc.tasatciud_tasaproced = tp.tasaproced_id
    LEFT JOIN 
        sc_giros.tm_tasa ts ON ts.tasa_id = tp.tasa_id
    LEFT JOIN 
        sc_giros.tm_procedimiento tpm ON tpm.proced_id = tp.proced_id
    INNER JOIN tb_dependencia ta ON tpm.proced_area = ta.depe_id
    LEFT JOIN 
        sc_giros.td_giro_tasa_ciudadano gt ON gt.tasaciud_id = ttc.tasatciud_id
    LEFT JOIN 
        sc_giros.td_ordengirociud oc ON oc.ogciud_id = gt.girot_giro
    LEFT JOIN 
        sc_escalafon.tb_persona sp ON oc.pers_id = sp.pers_id
            where  tp.est=1";
         $stmt = $conectar->prepare($sql);
         $stmt->execute();
         return $resultado = $stmt->fetchAll();
    }

    public function get_Tasas_Area($area_id, $tupa_id, $proced_id)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT 
        oc.ogciud_id,
        oc.pers_id,
        oc.fechacrea,
        ta.depe_denominacion as area_nom,
        tp.tasa_id,
        tp.cod_ref,
        ts.tasa_nom,
        tpm.proced_nom,
        ttc.est as estadotasat, 
        gt.girot_id,
        gt.fechacrea, 
        gt.tasaciud_id, 
        gt.cantidad, 
        gt.importe, 
        gt.est as estadotasagirada, 
        gt.girot_giro
    FROM 
        sc_giros.td_tasatciud ttc
    LEFT JOIN 
        sc_giros.td_tasaproced tp ON ttc.tasatciud_tasaproced = tp.tasaproced_id
    LEFT JOIN 
        sc_giros.tm_tasa ts ON ts.tasa_id = tp.tasa_id
    LEFT JOIN 
        sc_giros.tm_procedimiento tpm ON tpm.proced_id = tp.proced_id
    INNER JOIN tb_dependencia ta ON tpm.proced_area = ta.depe_id
    LEFT JOIN 
        sc_giros.td_giro_tasa_ciudadano gt ON gt.tasaciud_id = ttc.tasatciud_id
    LEFT JOIN 
        sc_giros.td_ordengirociud oc ON oc.ogciud_id = gt.girot_giro
    LEFT JOIN 
        sc_escalafon.tb_persona sp ON oc.pers_id = sp.pers_id
            where  tp.est=1 and tpm.proced_tupa = ? and tpm.proced_id = ? and tpm.proced_area= ?";
         $stmt = $conectar->prepare($sql);
         $stmt->bindValue(1, $tupa_id);
         $stmt->bindValue(2, $proced_id);
         $stmt->bindValue(3, $area_id);
        $stmt->execute();
         return $resultado = $stmt->fetchAll();
    }

    public function get_ordenes_giro($documento)
    {
        $conectar = parent::conexion();
        $sql = "SELECT 
                  og.ogciud_id,
                  og.est orden_est,
                  og.recibo_nro,
                  tt.est as procedimiento_est,
                  TO_CHAR(og.fechacrea, 'YYYY-MM-DD') AS fecha,
                  TO_CHAR(og.fechacrea, 'HH12:MI:SS AM') AS hora,
                  CONCAT(tu.pers_nombre, ' ', tu.pers_apelpat, ' ', tu.pers_apelmat) AS nombre_girador,
                  CONCAT(c.ciud_nombre, ' ',c.ciud_primer_apellido,' ', c.ciud_segundo_apellido) as ciud_nombre,
                  c.ciud_numero_documento AS ciudadano_doc,
                  c.ciud_domicilio_real,
                  c.tido_id,
                  tido.tido_descripcion,
                  tm.tupa_nom as proced_tupa,
                  ta.depe_denominacion,
                  t.proced_id,
                  t.proced_nom,
                  tst.cod_ref,
                  ts.tasa_nom,
                  gt.importe,
                  t.proced_tipoindvasc,
                  empr.empr_ruc,
                  empr.empr_razon_social,
                  empr.empr_direccion,
                  og.ogciud_comentario
                FROM 
                  sc_giros.td_ordengirociud og
                INNER JOIN sc_escalafon.tb_persona tu ON og.pers_id = tu.pers_id
                INNER JOIN sc_giros.td_giro_tasa_ciudadano gt ON gt.girot_giro = og.ogciud_id
                INNER JOIN sc_giros.td_tasatciud ttc ON gt.tasaciud_id = ttc.tasatciud_id
                INNER JOIN sc_giros.td_procedciudadano tt ON ttc.tasatciud_procedciud = tt.procedciudadano_id
                INNER JOIN public.tb_ciudadano c ON c.ciud_id = tt.ciud_id
                INNER JOIN sc_giros.tm_procedimiento t ON tt.proced_id = t.proced_id
                INNER JOIN sc_giros.td_tasaproced tst ON ttc.tasatciud_tasaproced = tst.tasaproced_id
                INNER JOIN sc_giros.tm_tasa ts ON tst.tasa_id = ts.tasa_id
                INNER JOIN tb_dependencia ta ON t.proced_area = ta.depe_id
                INNER JOIN sc_giros.tm_tupa tm ON t.proced_tupa = tm.tupa_id
                LEFT JOIN public.tb_empresa empr ON empr.empr_id = tt.empr_id
                LEFT JOIN public.tb_tipo_documento tido ON c.tido_id = tido.tido_id
                WHERE c.ciud_numero_documento = ? OR empr.empr_ruc = ? order by og.fechacrea desc";
        $query = $conectar->prepare($sql);
        $query->bindValue(1, $documento);
        $query->bindValue(2, $documento);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function get_ordenes_mes($search = "", $limit = 10, $offset = 0)
    {
        $conectar = parent::conexion();

        $where = "WHERE EXTRACT(MONTH FROM og.fechacrea) = EXTRACT(MONTH FROM CURRENT_DATE) 
                      AND EXTRACT(YEAR FROM og.fechacrea) = EXTRACT(YEAR FROM CURRENT_DATE)";

        if (!empty($search)) {
            $where .= " AND (
                        CONCAT(c.ciud_nombre, ' ', c.ciud_primer_apellido, ' ', c.ciud_segundo_apellido) ILIKE :search
                        OR c.ciud_numero_documento ILIKE :search
                        OR t.proced_nom ILIKE :search)";
        }

        $sql = "SELECT 
                    og.ogciud_id,
                    og.est orden_est,
                    og.recibo_nro,
                    TO_CHAR(og.fechacrea, 'YYYY-MM-DD') AS fecha,
                    TO_CHAR(og.fechacrea, 'HH12:MI:SS AM') AS hora,
                    CONCAT(tu.pers_nombre, ' ', tu.pers_apelpat, ' ', tu.pers_apelmat) AS nombre_girador,
                    CONCAT(c.ciud_nombre, ' ', c.ciud_primer_apellido, ' ', c.ciud_segundo_apellido) AS ciud_nombre,
                    c.ciud_numero_documento AS ciudadano_doc,
                    t.proced_nom,
                    gt.importe
                FROM sc_giros.td_ordengirociud og
                INNER JOIN sc_escalafon.tb_persona tu ON og.pers_id = tu.pers_id
                INNER JOIN sc_giros.td_giro_tasa_ciudadano gt ON gt.girot_giro = og.ogciud_id
                INNER JOIN sc_giros.td_procedciudadano tt ON gt.tasaciud_id = tt.procedciudadano_id
                INNER JOIN public.tb_ciudadano c ON c.ciud_id = tt.ciud_id
                INNER JOIN sc_giros.tm_procedimiento t ON tt.proced_id = t.proced_id
                $where
                ORDER BY og.fechacrea DESC
                LIMIT :limit OFFSET :offset";

        $query = $conectar->prepare($sql);

        if (!empty($search)) {
            $query->bindValue(':search', "%$search%", PDO::PARAM_STR);
        }

        $query->bindValue(':limit', $limit, PDO::PARAM_INT);
        $query->bindValue(':offset', $offset, PDO::PARAM_INT);
        $query->execute();

        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function get_total_ordenes_mes($search = "")
    {
        $conectar = parent::conexion();

        $where = "WHERE EXTRACT(MONTH FROM og.fechacrea) = EXTRACT(MONTH FROM CURRENT_DATE) 
                  AND EXTRACT(YEAR FROM og.fechacrea) = EXTRACT(YEAR FROM CURRENT_DATE)";

        if (!empty($search)) {
            $where .= " AND (
                        LOWER(CONCAT(tu.pers_nombre, ' ', tu.pers_apelpat, ' ', tu.pers_apelmat)) LIKE LOWER(:search)
                        OR LOWER(c.ciud_numero_documento) LIKE LOWER(:search)
                        OR LOWER(t.proced_nom) LIKE LOWER(:search))";
        }

        $sql = "SELECT COUNT(*) AS total FROM sc_giros.td_ordengirociud og
                INNER JOIN sc_escalafon.tb_persona tu ON og.pers_id = tu.pers_id
                INNER JOIN sc_giros.td_giro_tasa_ciudadano gt ON gt.girot_giro = og.ogciud_id
                INNER JOIN sc_giros.td_procedciudadano tt ON gt.tasaciud_id = tt.procedciudadano_id
                INNER JOIN public.tb_ciudadano c ON c.ciud_id = tt.ciud_id
                INNER JOIN sc_giros.tm_procedimiento t ON tt.proced_id = t.proced_id
                $where";

        $query = $conectar->prepare($sql);

        if (!empty($search)) {
            $query->bindValue(':search', "%$search%", PDO::PARAM_STR);
        }

        $query->execute();
        $row = $query->fetch(PDO::FETCH_ASSOC);
        return $row ? (int)$row['total'] : 0;
    }

    public function get_orden_giro_by_id($ogciud_id)
    {
        $conectar = parent::conexion();

        $sql = "SELECT 
                      og.ogciud_id,
                      og.est orden_est,
                      og.recibo_nro,
                      TO_CHAR(og.fechacrea, 'YYYY-MM-DD') AS fecha,
                      TO_CHAR(og.fechacrea, 'HH12:MI:SS AM') AS hora,
                      CONCAT(tu.pers_nombre, ' ', tu.pers_apelpat, ' ', tu.pers_apelmat) AS nombre_girador,
                      CONCAT(c.ciud_nombre, ' ', c.ciud_primer_apellido, ' ', c.ciud_segundo_apellido) AS ciud_nombre,
                      c.ciud_numero_documento AS ciudadano_doc,
                      t.proced_nom,
                      tt.est as procedimiento_est,
                      gt.importe
                  FROM sc_giros.td_ordengirociud og
                  LEFT JOIN sc_escalafon.tb_persona tu ON og.pers_id = tu.pers_id
                  LEFT JOIN sc_giros.td_giro_tasa_ciudadano gt ON gt.girot_giro = og.ogciud_id
                  LEFT JOIN sc_giros.td_procedciudadano tt ON gt.tasaciud_id = tt.procedciudadano_id
                  LEFT JOIN public.tb_ciudadano c ON c.ciud_id = tt.ciud_id
                  LEFT JOIN sc_giros.tm_procedimiento t ON tt.proced_id = t.proced_id
                  WHERE og.ogciud_id = ?";

        $query = $conectar->prepare($sql);
        $query->bindValue(1, $ogciud_id, PDO::PARAM_STR);
        $query->execute();
        $data = $query->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            return false;
        }

        $baseUrl = Conectar::getEnv('APP_URL', 'http://10.10.10.16/SIGODT');
        $pdfUrl = rtrim($baseUrl, '/') . "/controller/rc.php?op=imprimirxid";
        $postData = http_build_query(["ogciud_id" => $ogciud_id]);

        $options = [
            "http" => [
                "header" => "Content-type: application/x-www-form-urlencoded",
                "method" => "POST",
                "content" => $postData,
                "timeout" => 10
            ]
        ];
        $context = stream_context_create($options);
        $pdfContent = @file_get_contents($pdfUrl, false, $context);

        if ($pdfContent === false) {
            return false;
        }

        $data["pdf_base64"] = base64_encode($pdfContent);

        return $data;
    }
}

if (!class_exists('OrdenGiro', false)) {
    class_alias('Ordengiro', 'OrdenGiro');
}
