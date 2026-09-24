<?php
class Dashboard extends Conectar
{

    // 1. Total recaudado (óptimo para filtros de fecha)
    public function get_total_recaudado($fecha_ini = null, $fecha_fin = null, $estados = null)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT COALESCE(SUM(gtc.importe), 0) AS total
            FROM sc_giros.td_giro_tasa_ciudadano gtc
            INNER JOIN sc_giros.td_ordengirociud ogc ON gtc.girot_giro = ogc.ogciud_id
			inner join sc_giros.td_tasatciud ttc on ttc.tasatciud_id = gtc.tasaciud_id
            WHERE ttc.est = 4";
        $params = [];
        if ($fecha_ini && $fecha_fin) {
            $sql .= " AND ogc.fechacrea BETWEEN ? AND ?";
            $params[] = $fecha_ini;
            $params[] = $fecha_fin;
        }
        if ($estados && count($estados) > 0) {
            $in = implode(',', array_fill(0, count($estados), '?'));
            $sql .= " AND ogc.est IN ($in)";
            $params = array_merge($params, $estados);
        }
        $stmt = $conectar->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k + 1, $v);
        $stmt->execute();
        return $stmt->fetchAll();
    }


    // 2. Recaudación por área/dependencia
    public function get_recaudacion_por_area_estado($fecha_ini = null, $fecha_fin = null, $estados = null)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT 
            d.depe_id, 
            d.depe_denominacion, 
            d.depe_abreviatura,
            ogc.est AS estado,
            COALESCE(SUM(gtc.importe),0) AS total
        FROM sc_giros.td_giro_tasa_ciudadano gtc
        INNER JOIN sc_giros.td_ordengirociud ogc ON gtc.girot_giro = ogc.ogciud_id
        INNER JOIN sc_giros.td_tasatciud ttc ON gtc.tasaciud_id = ttc.tasatciud_id
        INNER JOIN sc_giros.td_procedciudadano pc ON ttc.tasatciud_procedciud = pc.procedciudadano_id
        INNER JOIN sc_giros.tm_procedimiento pr ON pc.proced_id = pr.proced_id
        INNER JOIN public.tb_dependencia d ON pr.proced_area = d.depe_id
        WHERE 1=1";
        $params = [];
        if ($fecha_ini && $fecha_fin) {
            $sql .= " AND ogc.fechacrea BETWEEN ? AND ?";
            $params[] = $fecha_ini;
            $params[] = $fecha_fin;
        }
        if ($estados && count($estados) > 0) {
            $in = implode(',', array_fill(0, count($estados), '?'));
            $sql .= " AND ogc.est IN ($in)";
            $params = array_merge($params, $estados);
        }
        $sql .= " GROUP BY d.depe_id, d.depe_denominacion, d.depe_abreviatura, ogc.est ORDER BY d.depe_denominacion, ogc.est";
        $stmt = $conectar->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k + 1, $v);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function get_pendiente_por_cobrar($fecha_ini = null, $fecha_fin = null, $estados = null)
    {
        $conectar = parent::conexion();
        parent::set_names();
        // Suponemos que la tabla td_giro_tasa_ciudadano tiene un campo 'pagado' (0=pte,1=ok)
        $sql = "SELECT COALESCE(SUM(gtc.importe), 0) AS pendiente
            FROM sc_giros.td_giro_tasa_ciudadano gtc
            INNER JOIN sc_giros.td_ordengirociud ogc ON gtc.girot_giro = ogc.ogciud_id
			inner join sc_giros.td_tasatciud ttc on ttc.tasatciud_id = gtc.tasaciud_id
            WHERE ttc.est = 2";
        $params = [];
        if ($fecha_ini && $fecha_fin) {
            $sql .= " AND ogc.fechacrea BETWEEN ? AND ?";
            array_push($params, $fecha_ini, $fecha_fin);
        }
        if ($estados && count($estados) > 0) {
            $in = implode(',', array_fill(0, count($estados), '?'));
            $sql .= " AND ogc.est IN ($in)";
            $params = array_merge($params, $estados);
        }
        $stmt = $conectar->prepare($sql);
        foreach ($params as $i => $v) {
            $stmt->bindValue($i + 1, $v);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }



    // 3. Recaudación por usuario
    public function get_recaudacion_por_usuario($fecha_ini = null, $fecha_fin = null, $estados = null)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT p.pers_id, p.pers_nombre, p.pers_apelpat, p.pers_apelmat, COALESCE(SUM(gtc.importe), 0) AS total
            FROM sc_giros.td_giro_tasa_ciudadano gtc
            INNER JOIN sc_giros.td_ordengirociud ogc ON gtc.girot_giro = ogc.ogciud_id
            INNER JOIN sc_escalafon.tb_persona p ON ogc.pers_id = p.pers_id
            WHERE 1=1";
        $params = [];
        if ($fecha_ini && $fecha_fin) {
            $sql .= " AND ogc.fechacrea BETWEEN ? AND ?";
            $params[] = $fecha_ini;
            $params[] = $fecha_fin;
        }
        if ($estados && count($estados) > 0) {
            $in = implode(',', array_fill(0, count($estados), '?'));
            $sql .= " AND ogc.est IN ($in)";
            $params = array_merge($params, $estados);
        }
        $sql .= " GROUP BY p.pers_id, p.pers_nombre, p.pers_apelpat, p.pers_apelmat ORDER BY total DESC";
        $stmt = $conectar->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k + 1, $v);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // 4. Órdenes de giro por estado
    public function get_ordenes_por_estado($fecha_ini = null, $fecha_fin = null, $estados = null)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT ogc.est, COUNT(*) as cantidad
            FROM sc_giros.td_ordengirociud ogc
            WHERE 1=1";
        $params = [];
        if ($fecha_ini && $fecha_fin) {
            $sql .= " AND ogc.fechacrea BETWEEN ? AND ?";
            $params[] = $fecha_ini;
            $params[] = $fecha_fin;
        }
        if ($estados && count($estados) > 0) {
            $in = implode(',', array_fill(0, count($estados), '?'));
            $sql .= " AND ogc.est IN ($in)";
            $params = array_merge($params, $estados);
        }
        $sql .= " GROUP BY ogc.est ORDER BY ogc.est";
        $stmt = $conectar->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k + 1, $v);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    // 5. Procedimientos por estado
    public function get_procedimientos_por_estado($fecha_ini = null, $fecha_fin = null, $estados = null)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT pc.est, COUNT(*) as cantidad
            FROM sc_giros.td_procedciudadano pc
            WHERE 1=1";
        $params = [];
        if ($fecha_ini && $fecha_fin) {
            $sql .= " AND pc.fechacrea BETWEEN ? AND ?";
            $params[] = $fecha_ini;
            $params[] = $fecha_fin;
        }
        if ($estados && count($estados) > 0) {
            $in = implode(',', array_fill(0, count($estados), '?'));
            $sql .= " AND pc.est IN ($in)";
            $params = array_merge($params, $estados);
        }
        $sql .= " GROUP BY pc.est ORDER BY pc.est";
        $stmt = $conectar->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k + 1, $v);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // 6. Procedimientos iniciados
    public function get_procedimientos_iniciados($fecha_ini = null, $fecha_fin = null, $estados = null)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT COUNT(*) as total FROM sc_giros.td_procedciudadano pc WHERE 1=1";
        $params = [];
        if ($fecha_ini && $fecha_fin) {
            $sql .= " AND pc.fechacrea BETWEEN ? AND ?";
            $params[] = $fecha_ini;
            $params[] = $fecha_fin;
        }
        if ($estados && count($estados) > 0) {
            $in = implode(',', array_fill(0, count($estados), '?'));
            $sql .= " AND pc.est IN ($in)";
            $params = array_merge($params, $estados);
        }
        $stmt = $conectar->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k + 1, $v);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    // En Dashboard.php (model)
    public function get_evolucion_mensual($fecha_ini = null, $fecha_fin = null, $estados = null)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "
            SELECT
                TO_CHAR(ogc.fechacrea, 'YYYY-MM') AS mes_anio,
                ogc.est,
                COALESCE(SUM(gtc.importe), 0) AS total
            FROM sc_giros.td_giro_tasa_ciudadano gtc
            INNER JOIN sc_giros.td_ordengirociud ogc
                ON gtc.girot_giro = ogc.ogciud_id
            WHERE 1=1
            ";
        $params = [];
        if ($fecha_ini && $fecha_fin) {
            $sql .= " AND ogc.fechacrea BETWEEN ? AND ?";
            $params[] = $fecha_ini;
            $params[] = $fecha_fin;
        }
        if ($estados && count($estados) > 0) {
            $in = implode(',', array_fill(0, count($estados), '?'));
            $sql .= " AND ogc.est IN ($in)";
            $params = array_merge($params, $estados);
        }
        $sql .= "
      GROUP BY mes_anio, ogc.est
      ORDER BY mes_anio, ogc.est
    ";
        $stmt = $conectar->prepare($sql);
        foreach ($params as $i => $v) {
            $stmt->bindValue($i + 1, $v);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }
    public function get_errores_por_usuario_dependencia($fecha_ini = null, $fecha_fin = null)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "
      SELECT
        p.pers_id,
        CONCAT(p.pers_nombre, ' ', p.pers_apelpat, ' ', p.pers_apelmat) AS usuario,
        d.depe_id,
        d.depe_denominacion AS dependencia,
        COUNT(*) AS errores
      FROM sc_giros.td_procedciudadano pc
      INNER JOIN sc_giros.tm_procedimiento pr
        ON pc.proced_id = pr.proced_id
      INNER JOIN public.tb_dependencia d
        ON pr.proced_area = d.depe_id
      INNER JOIN sc_escalafon.tb_persona p
        ON pc.usu_crea = p.pers_id
      WHERE pc.est = 0
    ";
        $params = [];
        if ($fecha_ini && $fecha_fin) {
            $sql .= " AND pc.fechacrea BETWEEN ? AND ?";
            array_push($params, $fecha_ini, $fecha_fin);
        }
        $sql .= "
      GROUP BY p.pers_id, usuario, d.depe_id, d.depe_denominacion
      ORDER BY usuario, dependencia
    ";
        $stmt = $conectar->prepare($sql);
        foreach ($params as $i => $v) {
            $stmt->bindValue($i + 1, $v);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }


    public function get_ordenes_por_dependencia_estado($fecha_ini = null, $fecha_fin = null, $estados = null)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "
    SELECT
        d.depe_id,
        d.depe_siglasdoc,
        d.depe_denominacion,
        ogc.est,
        COUNT(*) as total,
        SUM(CASE WHEN ogc.fechacrea::date = CURRENT_DATE THEN 1 ELSE 0 END) AS total_hoy,
        SUM(CASE WHEN ogc.fechacrea::date >= CURRENT_DATE - INTERVAL '6 days' THEN 1 ELSE 0 END) AS total_semana
    FROM sc_giros.td_ordengirociud ogc
    INNER JOIN sc_giros.td_giro_tasa_ciudadano gtc ON gtc.girot_giro = ogc.ogciud_id
    INNER JOIN sc_giros.td_tasatciud ttc ON ttc.tasatciud_id = gtc.tasaciud_id
    INNER JOIN sc_giros.td_procedciudadano pc ON ttc.tasatciud_procedciud = pc.procedciudadano_id
    INNER JOIN sc_giros.tm_procedimiento pr ON pc.proced_id = pr.proced_id
    INNER JOIN public.tb_dependencia d ON pr.proced_area = d.depe_id
    WHERE 1=1";
        $params = [];
        if ($fecha_ini && $fecha_fin) {
            $sql .= " AND ogc.fechacrea BETWEEN ? AND ?";
            $params[] = $fecha_ini;
            $params[] = $fecha_fin;
        }
        if ($estados && count($estados) > 0) {
            $in = implode(',', array_fill(0, count($estados), '?'));
            $sql .= " AND ogc.est IN ($in)";
            $params = array_merge($params, $estados);
        }
        $sql .= "
    GROUP BY d.depe_id, d.depe_siglasdoc, d.depe_denominacion, ogc.est
    ORDER BY d.depe_siglasdoc, ogc.est";
        $stmt = $conectar->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k + 1, $v);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function get_resumen_dependencia($depe_id, $fecha_ini = null, $fecha_fin = null, $estados = null)
    {
        $conectar = parent::conexion();
        parent::set_names();

        // 1. Órdenes diarias
        $sql1 = "SELECT TO_CHAR(ogc.fechacrea, 'YYYY-MM-DD') AS fecha, COUNT(DISTINCT ogc.ogciud_id) AS cantidad
        FROM sc_giros.td_ordengirociud ogc
        INNER JOIN sc_giros.td_giro_tasa_ciudadano gtc ON gtc.girot_giro = ogc.ogciud_id
        INNER JOIN sc_giros.td_tasatciud ttc ON ttc.tasatciud_id = gtc.tasaciud_id
        INNER JOIN sc_giros.td_procedciudadano pc ON ttc.tasatciud_procedciud = pc.procedciudadano_id
        INNER JOIN sc_giros.tm_procedimiento pr ON pc.proced_id = pr.proced_id
        INNER JOIN public.tb_dependencia d ON pr.proced_area = d.depe_id
        WHERE d.depe_id = ?";
        $params1 = [$depe_id];
        if ($fecha_ini && $fecha_fin) {
            $sql1 .= " AND ogc.fechacrea::date BETWEEN ? AND ?";
            $params1[] = $fecha_ini;
            $params1[] = $fecha_fin;
        } else {
            $sql1 .= " AND ogc.fechacrea::date >= CURRENT_DATE - INTERVAL '6 days'";
        }
        if ($estados && count($estados) > 0) {
            $in = implode(',', array_fill(0, count($estados), '?'));
            $sql1 .= " AND ogc.est IN ($in)";
            $params1 = array_merge($params1, $estados);
        }
        $sql1 .= " GROUP BY fecha ORDER BY fecha";
        $stmt1 = $conectar->prepare($sql1);
        foreach ($params1 as $k => $v) $stmt1->bindValue($k + 1, $v);
        $stmt1->execute();
        $ordenes_diarias = $stmt1->fetchAll(PDO::FETCH_ASSOC);

        // 2. Recaudación por procedimiento y tasa (monto total por cada combinación)
        $sql2 = "
        SELECT pr.proced_nom, ta.tasa_nom, COALESCE(SUM(gtc.importe),0) AS total
        FROM sc_giros.td_giro_tasa_ciudadano gtc
        INNER JOIN sc_giros.td_ordengirociud ogc ON gtc.girot_giro = ogc.ogciud_id
        INNER JOIN sc_giros.td_tasatciud ttc ON gtc.tasaciud_id = ttc.tasatciud_id
        INNER JOIN sc_giros.td_procedciudadano pc ON ttc.tasatciud_procedciud = pc.procedciudadano_id
        INNER JOIN sc_giros.tm_procedimiento pr ON pc.proced_id = pr.proced_id
        INNER JOIN public.tb_dependencia d ON pr.proced_area = d.depe_id
        INNER JOIN sc_giros.td_tasaproced tpr ON ttc.tasatciud_tasaproced = tpr.tasaproced_id
		INNER JOIN sc_giros.tm_tasa ta ON tpr.tasa_id = ta.tasa_id 
        WHERE d.depe_id = ?";
        if ($fecha_ini && $fecha_fin) {
            $sql2 .= " AND ogc.fechacrea::date BETWEEN ? AND ?";
        }
        $sql2 .= " GROUP BY pr.proced_nom, ta.tasa_nom ORDER BY pr.proced_nom, ta.tasa_nom";
        $stmt2 = $conectar->prepare($sql2);
        $stmt2->bindValue(1, $depe_id);
        if ($fecha_ini && $fecha_fin) {
            $stmt2->bindValue(2, $fecha_ini);
            $stmt2->bindValue(3, $fecha_fin);
        }
        $stmt2->execute();
        $recaudacion = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        // 3. Usuarios y sus totales de giro por dependencia (MONTO y CANTIDAD)
        $sql3 = "
        SELECT p.pers_id,
            p.pers_nombre || ' ' || p.pers_apelpat || ' ' || p.pers_apelmat AS usuario,
            COALESCE(SUM(gtc.importe),0) AS total,
            COUNT(DISTINCT ogc.ogciud_id) AS cantidad
        FROM sc_giros.td_giro_tasa_ciudadano gtc
        INNER JOIN sc_giros.td_ordengirociud ogc ON gtc.girot_giro = ogc.ogciud_id
        INNER JOIN sc_escalafon.tb_persona p ON ogc.pers_id = p.pers_id
        INNER JOIN sc_giros.td_tasatciud ttc ON gtc.tasaciud_id = ttc.tasatciud_id
        INNER JOIN sc_giros.td_procedciudadano pc ON ttc.tasatciud_procedciud = pc.procedciudadano_id
        INNER JOIN sc_giros.tm_procedimiento pr ON pc.proced_id = pr.proced_id
        INNER JOIN public.tb_dependencia d ON pr.proced_area = d.depe_id
        WHERE d.depe_id = ?";
        if ($fecha_ini && $fecha_fin) {
            $sql3 .= " AND ogc.fechacrea::date BETWEEN ? AND ?";
        }
        $sql3 .= " GROUP BY p.pers_id, usuario ORDER BY total DESC";
        $stmt3 = $conectar->prepare($sql3);
        $stmt3->bindValue(1, $depe_id);
        if ($fecha_ini && $fecha_fin) {
            $stmt3->bindValue(2, $fecha_ini);
            $stmt3->bindValue(3, $fecha_fin);
        }
        $stmt3->execute();
        $usuarios = $stmt3->fetchAll(PDO::FETCH_ASSOC);


        // 4. Giros por día para cada usuario (cuenta importe por día y usuario)
        foreach ($usuarios as $k => $usuario) {
            $sql4 = "
            SELECT TO_CHAR(ogc.fechacrea, 'YYYY-MM-DD') AS fecha,
                COALESCE(SUM(gtc.importe),0) AS total
            FROM sc_giros.td_giro_tasa_ciudadano gtc
            INNER JOIN sc_giros.td_ordengirociud ogc ON gtc.girot_giro = ogc.ogciud_id
            INNER JOIN sc_escalafon.tb_persona p ON ogc.pers_id = p.pers_id
            INNER JOIN sc_giros.td_tasatciud ttc ON gtc.tasaciud_id = ttc.tasatciud_id
            INNER JOIN sc_giros.td_procedciudadano pc ON ttc.tasatciud_procedciud = pc.procedciudadano_id
            INNER JOIN sc_giros.tm_procedimiento pr ON pc.proced_id = pr.proced_id
            INNER JOIN public.tb_dependencia d ON pr.proced_area = d.depe_id
            WHERE d.depe_id = ? AND p.pers_id = ?";
            if ($fecha_ini && $fecha_fin) {
                $sql4 .= " AND ogc.fechacrea::date BETWEEN ? AND ?";
            } else {
                $sql4 .= " AND ogc.fechacrea::date >= CURRENT_DATE - INTERVAL '6 days'";
            }
            $sql4 .= " GROUP BY fecha ORDER BY fecha";
            $stmt4 = $conectar->prepare($sql4);
            $stmt4->bindValue(1, $depe_id);
            $stmt4->bindValue(2, $usuario['pers_id']);
            if ($fecha_ini && $fecha_fin) {
                $stmt4->bindValue(3, $fecha_ini);
                $stmt4->bindValue(4, $fecha_fin);
            }
            $stmt4->execute();
            $usuarios[$k]['giros_por_dia'] = $stmt4->fetchAll(PDO::FETCH_ASSOC);
        }
        // 5. Resumen de procedimientos: nombre, código, cantidad de órdenes únicas y total S/
        $sql5 = "
            SELECT 
                pr.proced_nom, 
                pr.proced_cod,
                COUNT(DISTINCT ogc.ogciud_id) AS cantidad,
                COALESCE(SUM(gtc.importe),0) AS total
            FROM sc_giros.td_giro_tasa_ciudadano gtc
            INNER JOIN sc_giros.td_ordengirociud ogc ON gtc.girot_giro = ogc.ogciud_id
            INNER JOIN sc_giros.td_tasatciud ttc ON gtc.tasaciud_id = ttc.tasatciud_id
            INNER JOIN sc_giros.td_procedciudadano pc ON ttc.tasatciud_procedciud = pc.procedciudadano_id
            INNER JOIN sc_giros.tm_procedimiento pr ON pc.proced_id = pr.proced_id
            INNER JOIN public.tb_dependencia d ON pr.proced_area = d.depe_id
            WHERE d.depe_id = ?";
        if ($fecha_ini && $fecha_fin) {
            $sql5 .= " AND ogc.fechacrea::date BETWEEN ? AND ?";
        }
        $sql5 .= " GROUP BY pr.proced_nom, pr.proced_cod ORDER BY pr.proced_nom";
        $stmt5 = $conectar->prepare($sql5);
        $stmt5->bindValue(1, $depe_id);
        if ($fecha_ini && $fecha_fin) {
            $stmt5->bindValue(2, $fecha_ini);
            $stmt5->bindValue(3, $fecha_fin);
        }
        $stmt5->execute();
        $resumen_procedimientos = $stmt5->fetchAll(PDO::FETCH_ASSOC);
        return [
            "ordenes_diarias" => $ordenes_diarias,
            "recaudacion"     => $recaudacion,
            "usuarios"        => $usuarios,
            "resumen_procedimientos" => $resumen_procedimientos
        ];
    }
}
