<?php
class RC extends Conectar
{
    public function get_datos_giro($tasatciudadano_id, $pers_id = 0, $rol_id = 0)
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
                    ttc.tasatciud_id = :tasatciud_id
                    AND (og.est = 1 OR og.est = 2 OR og.est = 3 OR og.est = 4 OR og.est = 5)
                    AND (:role = 9 OR EXISTS (
                        SELECT 1 FROM sc_giros.td_areausu tau
                        WHERE tau.depe_id = t.proced_area AND tau.est = 1 AND tau.pers_id = :person
                    ));";
            $stmt = $conectar->prepare($sql);
            $stmt->bindValue(":tasatciud_id", (int) $tasatciudadano_id, PDO::PARAM_INT);
            $stmt->bindValue(":role", (int) $rol_id, PDO::PARAM_INT);
            $stmt->bindValue(":person", (int) $pers_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            echo "Error en la consulta: " . $e->getMessage();
            return null;
        }
    }
    public function get_datos_giro_id($ogciud_id, $pers_id = 0, $rol_id = 0)
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
        og.ogciud_id = :ogciud_id AND (og.est = 1 OR og.est = 2 OR og.est = 3 OR og.est = 4 OR og.est = 5)
        AND (:role = 9 OR EXISTS (
            SELECT 1 FROM sc_giros.td_areausu tau
            WHERE tau.depe_id = t.proced_area AND tau.est = 1 AND tau.pers_id = :person
        ))
                order by og.fechacrea desc";
            $stmt = $conectar->prepare($sql);
            $stmt->bindValue(":ogciud_id", (string) $ogciud_id, PDO::PARAM_STR);
            $stmt->bindValue(":role", (int) $rol_id, PDO::PARAM_INT);
            $stmt->bindValue(":person", (int) $pers_id, PDO::PARAM_INT);
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
    public function get_giros($procedciud, $pers_id = 0, $rol_id = 0)
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
         INNER JOIN sc_giros.td_procedciudadano tt ON tte.tasatciud_procedciud = tt.procedciudadano_id
         INNER JOIN sc_giros.tm_procedimiento t ON tt.proced_id = t.proced_id
         INNER JOIN
                sc_escalafon.tb_persona tu ON toc.pers_id = tu.pers_id
         WHERE tasatciud_procedciud = :procedciud
           AND toc.est in (1,2,3,4,5)
           AND (:role = 9 OR EXISTS (
               SELECT 1 FROM sc_giros.td_areausu tau
               WHERE tau.depe_id = t.proced_area AND tau.est = 1 AND tau.pers_id = :person
           ))
         ORDER BY toc.ogciud_id, toc.fechacrea DESC;";
            $stmt = $conectar->prepare($sql);
            $stmt->bindValue(":procedciud", (int) $procedciud, PDO::PARAM_INT);
            $stmt->bindValue(":role", (int) $rol_id, PDO::PARAM_INT);
            $stmt->bindValue(":person", (int) $pers_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            echo "Error en la consulta: " . $e->getMessage();
            return null;
        }
    }
    public function get_giros_total($depe_id = null, $pers_id = 0, $rol_id = 0)
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
     (:role = 9 OR EXISTS (
         SELECT 1 FROM sc_giros.td_areausu tau
         WHERE tau.depe_id = t.proced_area AND tau.est = 1 AND tau.pers_id = :person
     )) AND og.fechacrea >= CURRENT_DATE - INTERVAL '30 day'
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
            $stmt->bindValue(":role", (int) $rol_id, PDO::PARAM_INT);
            $stmt->bindValue(":person", (int) $pers_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            echo "Error en la consulta: " . $e->getMessage();
            return null;
        }
    }

    public function get_girostotal($pers_id = 0, $rol_id = 0)
    {
        return $this->get_giros_total(null, $pers_id, $rol_id);
    }

    public function get_rc($tasatciudadano_id, $pers_id = 0, $rol_id = 0)
    {
        return $this->get_datos_giro($tasatciudadano_id, $pers_id, $rol_id);
    }

    public function get_rc_x_id($ogciud_id, $pers_id = 0, $rol_id = 0)
    {
        return $this->get_datos_giro_id($ogciud_id, $pers_id, $rol_id);
    }

    public function get_tasas_x_ogciud($ogciud_id, $pers_id = 0, $rol_id = 0)
    {
        return $this->get_datos_giro_id($ogciud_id, $pers_id, $rol_id);
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

    public static function toIso($str)
    {
        if (!is_string($str) || $str === '') {
            return '';
        }
        if (function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($str, 'ISO-8859-1', 'UTF-8');
        }
        if (function_exists('iconv')) {
            return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $str);
        }
        return @utf8_decode($str);
    }

    public function generar_pdf_orden_giro($ogciud_id, $pers_id = 0, $rol_id = 0)
    {
        $datos = $this->get_datos_giro_id($ogciud_id, $pers_id, $rol_id);
        if (empty($datos)) {
            return false;
        }

        require_once dirname(__DIR__) . "/public/plantilla_reporte.php";

        $detallesGenerales = $datos[0];
        $pdf = new PDF("P", "mm", array(117, 550));
        $pdf->AliasNbPages();
        $pdf->AddPage();

        $posXImagenCentrada = ((117) / 2) - 10;
        $pdf->SetX($posXImagenCentrada);
        $logoPath = dirname(__DIR__) . '/public/logo144.png';
        if (file_exists($logoPath)) {
            $pdf->Image($logoPath, $pdf->GetX() - 5, $pdf->GetY() - 5, 0, 30);
        }
        $pdf->Ln(30);
        $pdf->SetFont("Arial", "B", 14);
        $pdf->Cell(0, 5, self::toIso("MUNICIPALIDAD PROVINCIAL DE CHICLAYO"), 0, 1, 'C');
        $pdf->Ln(4);
        $pdf->SetFont("Arial", "", 12);
        $pdf->Cell(0, 5, "RUC: 20141784901", 0, 1, 'C');
        $texto1 = ($detallesGenerales["lomu_denominacion"] ?? '') . ' ' . ($detallesGenerales["lomu_direccion"] ?? '');

        $pdf->SetFont("Arial", "", 11);
        $pdf->MultiCell(0, 5, self::toIso($texto1), 0, 'C');

        $pdf->Ln(4);
        $pdf->SetFont("Arial", "B", 14);
        $pdf->Cell(0, 5, self::toIso("Orden de Giro electrónico: " . ($detallesGenerales["ogciud_id"] ?? '')), 0, 1, 'C');
        $pdf->Ln(8);
        $pdf->SetFont("Arial", "B", 12);
        $pdf->Cell(30, 5, self::toIso("FECHA EMISIÓN:  "));
        $pdf->SetFont("Arial", "", 12);
        $pdf->SetX(42);
        $pdf->Cell(0, 5, "    " . ($detallesGenerales["fecha"] ?? '') . "   " . ($detallesGenerales["hora"] ?? ''));
        $pdf->SetFont("Arial", "B", 12);
        $pdf->Ln(6);
        $pdf->Cell(12, 5, self::toIso("NORMA: "));
        $pdf->SetFont("Arial", "", 12);
        $pdf->SetX(28);
        $pdf->Cell(0, 5, self::toIso($detallesGenerales["proced_tupa"] ?? ''));
        $pdf->Ln(6);
        $pdf->SetFont("Arial", "B", 12);
        $pdf->Cell(12, 5, self::toIso("AREA: "));
        $pdf->SetFont("Arial", "", 12);
        $areacell = self::toIso($detallesGenerales["depe_denominacion"] ?? '');
        $pdf->SetX(25);
        $pdf->MultiCell(0, 5, $areacell, 0, 'L');
        $pdf->Ln(6);
        $pdf->SetFont("Arial", "B", 12);
        $pdf->Cell(0, 5, self::toIso("OP: "));
        $pdf->SetFont("Arial", "", 12);
        $pdf->SetX(18);
        $pdf->MultiCell(0, 5, self::toIso($detallesGenerales["nombre_completo"] ?? ''), 0, 'L');
        $pdf->Ln(2);
        $pdf->Cell(0, 5, "........................................................................................");
        $direccion = "";
        $ruc = !empty($detallesGenerales["empr_ruc"])
            ? $detallesGenerales["empr_ruc"]
            : ($detallesGenerales["empresa_ruc"] ?? '');
        if (!empty($ruc)) {
            $direccion = self::toIso($detallesGenerales["empr_direccion"] ?? '');
            $pdf->Ln(8);
            $pdf->SetFont("Arial", "B", 12);
            $pdf->Cell(0, 5, "RUC: ");
            $pdf->SetX(22);
            $pdf->SetFont("Arial", "", 12);
            $pdf->Cell(0, 5, $ruc);
            if (!empty($detallesGenerales["empr_ruc"])) {
                $pdf->Ln(5);
                $pdf->SetFont("Arial", "B", 12);
                $pdf->Cell(0, 5, "Razon social: ");
                $pdf->SetX(38);
                $pdf->SetFont("Arial", "", 12);
                $pdf->MultiCell(0, 5, self::toIso($detallesGenerales["empr_razon_social"] ?? ''), 0, 'L');
                $pdf->Ln(5);
                $pdf->SetFont("Arial", "B", 12);
                $pdf->Cell(0, 5, "Nombre Com: ");
                $pdf->SetX(39);
                $pdf->SetFont("Arial", "", 12);
                $pdf->MultiCell(0, 5, self::toIso($detallesGenerales["empr_nombre_comercial"] ?? ''), 0, 'L');
                $pdf->Ln(5);
                $pdf->SetFont("Arial", "B", 12);
                $pdf->Cell(0, 5, "Direccion : ");
                $pdf->Ln(5);
                $pdf->SetFont("Arial", "", 12);
                $pdf->MultiCell(0, 5, $direccion, 0, 'L');
            } else {
                $pdf->Ln(5);
                $pdf->SetFont("Arial", "B", 12);
                $pdf->Cell(0, 5, "Razon social: ");
                $pdf->SetX(38);
                $pdf->SetFont("Arial", "", 12);
                $pdf->MultiCell(0, 5, self::toIso($detallesGenerales["empresa_razon_social"] ?? ''), 0, 'L');
            }
            $pdf->Ln(2);
            $pdf->Cell(0, 5, "........................................................................................");
        }
        $tidoId = isset($detallesGenerales["tido_id"]) ? (int)$detallesGenerales["tido_id"] : 0;
        if ($tidoId === 1) {
            $pdf->Ln(5);
            $pdf->SetFont("Arial", "B", 12);
            $pdf->Cell(0, 5, "DNI:");
            $pdf->SetX(20);
            $pdf->SetFont("Arial", "", 12);
            $pdf->Cell(0, 5, ($detallesGenerales["ciudadano_dni"] ?? ''));
            $pdf->Ln(5);
        } else if ($tidoId === 2) {
            $pdf->Ln(5);
            $pdf->SetFont("Arial", "B", 12);
            $pdf->Cell(0, 5, "N° Pasaporte: ");
            $pdf->SetX(42);
            $pdf->SetFont("Arial", "", 12);
            $pdf->Cell(0, 5, ($detallesGenerales["ciudadano_dni"] ?? ''));
            $pdf->Ln(5);
        } else if ($tidoId === 3) {
            $pdf->Ln(5);
            $pdf->SetFont("Arial", "B", 12);
            $pdf->Cell(0, 5, "CEE: ");
            $pdf->SetX(22);
            $pdf->SetFont("Arial", "", 12);
            $pdf->Cell(0, 5, ($detallesGenerales["ciudadano_dni"] ?? ''));
            $pdf->Ln(5);
        } else if ($tidoId === 4) {
            $pdf->Ln(5);
            $pdf->SetFont("Arial", "B", 12);
            $pdf->Cell(0, 5, self::toIso("CARNÉ CPP: "));
            $pdf->SetX(38);
            $pdf->SetFont("Arial", "", 12);
            $pdf->Cell(0, 5, ($detallesGenerales["ciudadano_dni"] ?? ''));
            $pdf->Ln(5);
        }

        if ($direccion === '') {
            $pdf->SetFont("Arial", "B", 12);
            $pdf->Cell(0, 5, self::toIso("Dirección: "));
            $pdf->Ln(5);
            $pdf->SetFont("Arial", "", 12);
            $pdf->MultiCell(0, 5, self::toIso($detallesGenerales["ciud_domicilio_real"] ?? ''), 0, 'L');
            $pdf->Ln(5);
        }

        $nombreciudadano = self::toIso("SR(A). " . ($detallesGenerales["nombre_ciudadano"] ?? ''));
        $pdf->MultiCell(0, 5, $nombreciudadano, 0, 'J');
        $pdf->Cell(0, 5, "........................................................................................");
        $pdf->Ln(5);
        $pdf->SetFont("Arial", "B", 12);
        $pdf->Cell(0, 5, self::toIso('UD. Está tramitando: '));
        $pdf->Ln(8);
        $pdf->SetFont("Arial", "", 12);
        $nombreproced = self::toIso($detallesGenerales["proced_nom"] ?? '');

        $pdf->MultiCell(0, 5, $nombreproced, 0, 'J');
        $total = 0;
        $pdf->Cell(0, 5, "........................................................................................");
        $pdf->Ln(5);
        $pdf->SetFont("Arial", "B", 12);
        $pdf->Cell(0, 5, self::toIso("Tasa"));
        $pdf->SetX(80);
        $pdf->SetFont("Arial", "B", 12);
        $pdf->Cell(0, 5, self::toIso("Importe"));
        $pdf->Ln(8);
        $pdf->SetWidths(array(67, 30));
        foreach ($datos as $detalle) {
            $pdf->SetFont("Arial", "", 12);
            $importeNum = isset($detalle["importe"]) ? (float)$detalle["importe"] : 0.0;
            $importeFormateado = number_format($importeNum, 2);

            $tasaNombre = self::toIso($detalle["tasa_nom"] ?? '');
            if (!empty($detalle["cantidad"]) && (int)$detalle["cantidad"] > 1) {
                $tasaNombre .= " (x " . $detalle["cantidad"] . ")";
            }

            $pdf->Row2(array($tasaNombre, "  S/ " . $importeFormateado));
            $total += $importeNum;
            $pdf->Ln(2);
        }

        $pdf->Cell(0, 5, "........................................................................................");
        $pdf->Ln(5);

        $totalFormateado = number_format($total, 2);
        if ($total == 0) {
            $pdf->SetX(10);
            $pdf->MultiCell(0, 5, self::toIso("El presente recibo y el costo de este procedimiento es gratuito por las razones que sean necesarias con el número de recibo: " . ($detallesGenerales["recibo"] ?? '')), 0, 'C');
        } else {
            $pdf->SetX(67);
            $pdf->Cell(0, 5, "Total:  S/ " . $totalFormateado, 'J');
        }
        $pdf->Ln(15);
        if (!empty($detallesGenerales["ogciud_comentario"])) {
            $pdf->SetFont("Arial", "B", 12);
            $pdf->Cell(0, 5, "Comentario: ");
            $pdf->Ln(7);
            $pdf->SetFont("Arial", "", 12);

            $pdf->MultiCell(0, 5, self::toIso($detallesGenerales["ogciud_comentario"]), 0, 'J');
            $pdf->Ln(5);
            $pdf->Cell(0, 5, "........................................................................................");
            $pdf->Ln(10);
        }

        $pdf->SetFont("Arial", "", 12);
        $pdf->MultiCell(0, 5, self::toIso("¡ANTES DE SALIR, VERIFIQUE EL NOMBRE, EL MONTO Y LOS CONCEPTOS SEAN LOS CORRECTOS!"), 0, 'C');
        $pdf->Ln(7);

        return $pdf->Output('S');
    }
}
