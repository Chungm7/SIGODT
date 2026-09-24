<?php

class OrdenGiro extends Conectar
{
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
                    LOWER(CONCAT(pers_nombre, ' ', pers_apelpat, ' ', pers_apelmat)) LIKE LOWER(:search)
                    OR LOWER(ciud_numero_documento) LIKE LOWER(:search)
                    OR LOWER(proced_nom) LIKE LOWER(:search))";
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
    return $query->fetch(PDO::FETCH_ASSOC)['total'];
  }
  public function get_orden_giro_by_id($ogciud_id)
  {
    $conectar = parent::conexion();

    // Consulta los datos de la orden
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

    // Obtención del PDF desde el sistema externo
    $pdfUrl = "http://10.10.10.16/SIGODT/controller/rc.php?op=imprimirxid";
    $postData = http_build_query(["ogciud_id" => $ogciud_id]);

    $options = [
      "http" => [
        "header" => "Content-type: application/x-www-form-urlencoded",
        "method" => "POST",
        "content" => $postData
      ]
    ];
    $context = stream_context_create($options);
    $pdfContent = file_get_contents($pdfUrl, false, $context);

    if ($pdfContent === false) {
      return false;
    }

    // Convertir PDF a base64
    $pdfBase64 = base64_encode($pdfContent);
    $data["pdf_base64"] = $pdfBase64;

    return $data;
  }


}
?>