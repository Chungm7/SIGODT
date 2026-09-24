<?php
class Procedimiento extends Conectar
{

    public function insert_proced($proced_tupa, $proced_area, $proced_cod, $proced_nom, $proced_campo, $proced_administradotipo, $proced_tipoindvasc)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "INSERT INTO sc_giros.tm_procedimiento(proced_tupa, proced_area, proced_cod, proced_nom, fechacrea, est, proced_tipocampo, proced_administradotipo, proced_tipoindvasc) VALUES (?,?,?,?, NOW(),'1',?, ?,?);";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $proced_tupa);
        $sql->bindValue(2, $proced_area);
        $sql->bindValue(3, $proced_cod);
        $sql->bindValue(4, $proced_nom);
        $sql->bindValue(5, $proced_campo);
        $sql->bindValue(6, $proced_administradotipo);
        $sql->bindValue(7, $proced_tipoindvasc);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }




    // 🔹 Método para crear un procedimiento ciudadano
    public function crearProcedCiud($ciud_id, $proced_id, $empr_id = null, $usu_crea, $empresa_ruc = null, $empresa_razon_social = null)
    {
        $conectar = parent::conexion();
        parent::set_names();

        $sql = "INSERT INTO sc_giros.td_procedciudadano (
                    ciud_id,
                    proced_id,
                    empr_id,
                    usu_crea,
                    empresa_ruc,
                    empresa_razon_social
                ) VALUES (?, ?, ?, ?, ?, ?)
                RETURNING procedciudadano_id";

        $stmt = $conectar->prepare($sql);
        $stmt->bindParam(1, $ciud_id, PDO::PARAM_INT);
        $stmt->bindParam(2, $proced_id, PDO::PARAM_INT);
        $stmt->bindParam(3, $empr_id, PDO::PARAM_INT);
        $stmt->bindParam(4, $usu_crea, PDO::PARAM_INT);
        $stmt->bindParam(5, $empresa_ruc, PDO::PARAM_STR);
        $stmt->bindParam(6, $empresa_razon_social, PDO::PARAM_STR);

        $stmt->execute();

        // Obtener el ID insertado
        return $stmt->fetchColumn();
    }

    // 🔹 Método para actualizar el código del procedimiento ciudadano
    public function actualizarCod($proced_id, $procedciudadanoID)
    {
        $conectar = parent::conexion();
        parent::set_names();

        $sql = "UPDATE sc_giros.td_procedciudadano
                SET procedciudadano_cod = (
                    SELECT proced_cod FROM sc_giros.tm_procedimiento WHERE proced_id = ?
                ) || '_' || ?
                WHERE procedciudadano_id = ?
                RETURNING procedciudadano_cod"; // 🔹 Retorna el código generado

        $stmt = $conectar->prepare($sql);
        $stmt->bindParam(1, $proced_id, PDO::PARAM_INT);
        $stmt->bindParam(2, $procedciudadanoID, PDO::PARAM_INT);
        $stmt->bindParam(3, $procedciudadanoID, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return $stmt->fetchColumn(); // 🔹 Retorna el nuevo código si el update fue exitoso
        }
        return false;
    }


    // 🔹 Método para insertar tasas asociadas al trámite del ciudadano
    public function updateTasas($proced_id, $procedciudadanoID)
    {
        $conectar = parent::conexion();
        parent::set_names();

        $sql = "INSERT INTO sc_giros.td_tasatciud (
                    tasatciud_tasaproced,
                    tasatciud_procedciud,
                    est
                )
                SELECT tt.tasaproced_id, ?, 1
                FROM sc_giros.td_tasaproced tt
                WHERE tt.proced_id = ? AND tt.est = 1
                RETURNING tasatciud_id"; // 🔹 Retorna IDs de las tasas insertadas

        $stmt = $conectar->prepare($sql);
        $stmt->bindParam(1, $procedciudadanoID, PDO::PARAM_INT);
        $stmt->bindParam(2, $proced_id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return $stmt->fetchAll(PDO::FETCH_COLUMN); // 🔹 Retorna IDs de tasas insertadas
        }
        return false;
    }



    public function actualizarprocedciudadano($procedciudadano_id, $ciud_id, $empr_id)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "UPDATE sc_giros.td_procedciudadano
            SET ciud_id = ?,
            empr_id= ?
            WHERE procedciudadano_id=?;";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $ciud_id, PDO::PARAM_INT);
        $sql->bindValue(2, $empr_id !== '' ? $empr_id : null, PDO::PARAM_INT);
        $sql->bindValue(3, $procedciudadano_id);

        $sql->execute();
        return $resultado = $sql->fetchAll();
    }

    public function update_proced($proced_id, $proced_cod, $proced_nom, $proced_campo, $proced_administradotipo, $proced_tipoindvasc)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "UPDATE sc_giros.tm_procedimiento
                SET
                proced_cod = ?,
                proced_nom = ?,
                proced_tipocampo = ?,
                proced_administradotipo = ?,
                proced_tipoindvasc = ?
                WHERE
                proced_id = ?";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $proced_cod);
        $sql->bindValue(2, $proced_nom);
        $sql->bindValue(3, $proced_campo);
        $sql->bindValue(4, $proced_administradotipo);
        $sql->bindValue(5, $proced_tipoindvasc);
        $sql->bindValue(6, $proced_id);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }


    public function delete_proced($proced_id)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "UPDATE sc_giros.tm_procedimiento
                SET
                    est = 0
                WHERE
                    proced_id = ?";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $proced_id);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }
    public function delete_proced_ciudadano($ciudadano, $orden_giro)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "CALL sc_giros.eliminar_proced_ciudadano(?,?);";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $ciudadano);
        $sql->bindValue(2, $orden_giro);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }
    public function cambiar_procedencia_proced_ciudadano($ciudadano)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "CALL sc_giros.cambiar_procedencia_proced_ciudadano(?);";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $ciudadano);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }

    public function get_proced_area_tupa($area_id, $tupa_id)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT t.proced_id, t.proced_cod, t.proced_nom, t.fechacrea, a.depe_denominacion, t.proced_tipocampo, t.proced_administradotipo, t.proced_tipoindvasc 
            FROM sc_giros.tm_procedimiento t
            INNER JOIN tb_dependencia a ON t.proced_area = a.depe_id
            WHERE t.est = 1 and t.proced_area = ? and t.proced_tupa = ? order by proced_nom asc ;";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $area_id);
        $sql->bindValue(2, $tupa_id);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }
    public function get_proced_area_tupa_tusne($area_id)
    {
        $conectar = parent::conexion();
        parent::set_names();

        $sql = "
            SELECT 
                p.proced_id,
                p.proced_cod,
                p.proced_nom,
                p.fechacrea,
                a.depe_denominacion,
                p.proced_tipocampo,
                p.proced_administradotipo,
                p.proced_tipoindvasc
            FROM sc_giros.tm_procedimiento p
            INNER JOIN tb_dependencia a 
                ON p.proced_area = a.depe_id
            WHERE 
                p.est = 1
                AND p.proced_area = :area_id
                AND p.proced_tupa IN (
                    SELECT tupa_id
                    FROM sc_giros.tm_tupa
                    WHERE est = 2
                    AND tipo_doc IN ('TUPA','TUSNE')
                )
            ORDER BY p.proced_nom ASC;
        ";

        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(':area_id', $area_id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listar_proceds($area_id, $tupa_id)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT t.proced_id, t.proced_cod, t.proced_nom, t.fechacrea, a.depe_denominacion, t.proced_tipocampo, t.proced_administradotipo, t.proced_tipoindvasc
            FROM sc_giros.tm_procedimiento t
            INNER JOIN tb_dependencia a ON t.proced_area = a.depe_id
            WHERE t.est = 1 and t.proced_area = ? and t.proced_tupa = ?;";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $area_id);
        $sql->bindValue(2, $tupa_id);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }
    public function get_tipoProc($procedimiento_id)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT proced_tipoindvasc 
            FROM sc_giros.tm_procedimiento 
            WHERE proced_id = ?;";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $procedimiento_id);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }
    public function get_proces_by_id($procedimiento_id)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT pro.proced_id, pro.proced_cod, pro.proced_nom, pro.fechacrea, pro.est, pro.proced_area, 
            pro.proced_tupa, pro.proced_tipocampo, pro.proced_administradotipo, pro.proced_tipoindvasc, tde.depe_denominacion
            FROM sc_giros.tm_procedimiento pro
            inner join tb_dependencia tde on tde.depe_id = pro.proced_area
            WHERE proced_id = ?";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $procedimiento_id);
        $sql->execute();

        // Devuelve un array asociativo
        return $sql->fetchAll(PDO::FETCH_ASSOC);
    }

    public function get_proced()
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT t.proced_id, t.proced_cod, t.proced_nom, t.fechacrea, a.area_nom
            FROM sc_giros.tm_procedimiento t
            INNER JOIN sc_giros.tm_area a ON t.proced_area = a.area_id
            WHERE t.est = 1";
        $sql = $conectar->prepare($sql);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }
    public function get_proced_cantTasas($proced_id)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT COUNT(tt.tasaproced_id) AS cantidad
            FROM sc_giros.td_tasaproced tt
            WHERE tt.proced_id = ? AND tt.est = 1;";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $proced_id);
        $sql->execute();
        return $resultado = $sql->fetchColumn();
    }
    public function get_procedciudadano_id($proced_id)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = " SELECT
        og.ogciud_id,
        tc.procedciudadano_id,
        tc.procedciudadano_cod,
        tc.proced_id,
        ciu.ciud_nombre AS ciudadano_nombre,
        ciu.ciud_numero_documento AS ciudadano_dni,
        CONCAT(ciu.ciud_nombre, ' ', ciu.ciud_primer_apellido, ' ', ciu.ciud_segundo_apellido) AS nombre_completo,
        tc.fechacrea,
        CASE
            WHEN og.est IS NOT NULL THEN og.est
            WHEN ttc.est = 0 THEN 0
            ELSE og.est
        END::INT AS est,
        COUNT(*) FILTER (WHERE tt.est = 1)::int AS total,
        COALESCE(SUM(CASE WHEN ttc.est IN (2,4,5) THEN 1 ELSE 0 END), 0)::int AS pagadas
        FROM
            sc_giros.td_tasatciud ttc
        LEFT JOIN
            sc_giros.td_tasaproced tt ON ttc.tasatciud_tasaproced = tt.tasaproced_id
        LEFT JOIN
            sc_giros.td_procedciudadano tc ON ttc.tasatciud_procedciud = tc.procedciudadano_id
        LEFT JOIN
            public.tb_ciudadano ciu ON tc.ciud_id= ciu.ciud_id

        LEFT JOIN 
            sc_giros.td_giro_tasa_ciudadano gt ON gt.tasaciud_id = ttc.tasatciud_id

        LEFT JOIN 
            sc_giros.td_ordengirociud og ON gt.girot_giro = og.ogciud_id

        WHERE
            tc.proced_id = ?
            AND tc.est IN (0,1,2,3,4,5,6)
        GROUP BY
            og.ogciud_id,
            tc.procedciudadano_id,
            tc.procedciudadano_cod,
            ciu.ciud_nombre,
            tc.proced_id,
            ciu.ciud_numero_documento,
            CONCAT(ciu.ciud_nombre, ' ', ciu.ciud_primer_apellido, ' ', ciu.ciud_segundo_apellido),
            tc.fechacrea,
            ttc.est
        ORDER BY
            tc.fechacrea DESC limit 200; ";
            $sql = $conectar->prepare($sql);
            $sql->bindValue(1, $proced_id);
            $sql->execute();
            return $resultado = $sql->fetchAll();
    }
    public function get_procedciudadano_id_admin($proced_id)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT * FROM sc_giros.get_proced_info_admin(?);";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $proced_id);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }


    public function get_procedempresaid($proced_id)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT * FROM sc_giros.get_procedempresa_info(?);";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $proced_id);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }
    public function get_procedciudadano_idprocedciudadano($procedciudadano_id)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = 'SELECT empr.empr_ruc, empr.empr_razon_social, empr.empr_nombre_comercial,tc.procedciudadano_id,tbc.ciud_domicilio_real, tbc.ciud_id, tbc.ciud_nombre, tbc.ciud_primer_apellido, tbc.ciud_segundo_apellido, tbc.ciud_numero_documento, tt.proced_tipoindvasc, vh.vehi_placa, tbc.ciud_sexo,tbc.ciud_fecha_nac
            FROM sc_giros.td_procedciudadano tc
            INNER JOIN public.tb_ciudadano tbc ON tc.ciud_id = tbc.ciud_id
			inner join sc_giros.tm_procedimiento tt ON tc.proced_id = tt.proced_id
			left join sc_transito_transporte."td_TIV" tiv ON tc.tiv_id = tiv.tiv_id
			left join sc_transito_transporte.td_vehiculo vh ON tiv.tiv_vehiculo= vh.vehi_id
			left join public.tb_empresa empr ON empr.empr_id= tc.empr_id
            WHERE procedciudadano_id = ?';
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $procedciudadano_id);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }
    public function get_proced_ciudadano_x_idciudadano($ciudadano_id)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT * FROM sc_giros.get_procedciudadano_info(?) order by fechacrea DESC";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $ciudadano_id);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }
    public function get_Areas()
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT * from sc_giros.tm_area
                WHERE est = 1;";
        $sql = $conectar->prepare($sql);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }
    public function get_Areas_usu($usu_id)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT * from sc_giros.td_areausu tau
            inner join tb_dependencia ta on tau.depe_id = ta.depe_id
                            WHERE tau.est = 1 and tau.pers_id = ?;";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $usu_id);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }
    public function get_todas_areas()
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT DISTINCT ta.depe_id, ta.depe_denominacion 
            FROM sc_giros.tm_procedimiento tau
            INNER JOIN tb_dependencia ta ON tau.proced_area = ta.depe_id;";
        $sql = $conectar->prepare($sql);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }
    public function get_proced_id($proced_id)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT * FROM sc_giros.tm_procedimiento WHERE est = 1 AND proced_id = ?";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $proced_id);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }
    public function get_ciudadano($ciudadano)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT c.ciudadano_nombre,
            c.ciudadano_apep,
            c.ciudadano_apem,
            c.ciudadano_dni
            FROM sc_giros.td_procedciudadano tc
            inner join sc_giros.tm_ciudadano c on procedciudadano_ciudadano = c.ciudadano_id
            where tc.ciudadano = ?";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $ciudadano);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }

    public function delete_proced_tasa($tasaproced_id)
    {
        $conectar = parent::conexion();
        parent::set_names();

        try {

            $sql = "CALL sc_giros.eliminartasaproced(?)";
            $stmt = $conectar->prepare($sql);
            $stmt->bindParam(1, $tasaproced_id, PDO::PARAM_INT);
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
            return false;
        }
    }



    public function insert_proced_tasa($proced_id, $tasa_id)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "INSERT INTO sc_giros.td_tasaproced(tasa_id, proced_id, est) VALUES (?, ?, 1) RETURNING tasaproced_id";
        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $tasa_id);
        $stmt->bindValue(2, $proced_id);
        $stmt->execute();


        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado;
    }

    public function update_cod_ref()
    {
        $conectar = parent::conexion();
        parent::set_names();

        // Actualizamos el campo cod_ref si cumple las condiciones
        $updateSql = "UPDATE sc_giros.td_tasaproced
                  SET cod_ref = CONCAT('TP', p.proced_tupa, '|', p.proced_area, '|', tp.proced_id, '|', tp.tasa_id)
                  FROM sc_giros.tm_procedimiento p
                  WHERE sc_giros.td_tasaproced.proced_id = p.proced_id
                  AND sc_giros.td_tasaproced.est = 1
                  AND sc_giros.td_tasaproced.cod_ref IS NULL";
        $updateStmt = $conectar->prepare($updateSql);
        $updateStmt->execute();
    }
    public function update_tasaproced($tasaproced_id, $tasaproced_pos, $tasaproced_monto, $cod_ref, $desc_tasa, $is_multiplica)
    {
        try {
            $conectar = parent::conexion();
            parent::set_names();

            // Llamamos al procedimiento almacenado con los parámetros actualizados
            $sql = "CALL sc_giros.actualizarposmonto(?, ?, ?, ?, ?, ?)";
            $stmt = $conectar->prepare($sql);
            $stmt->bindValue(1, $tasaproced_id, PDO::PARAM_INT);
            $stmt->bindValue(2, $tasaproced_pos, PDO::PARAM_INT);
            $stmt->bindValue(3, $tasaproced_monto, PDO::PARAM_STR);
            $stmt->bindValue(4, $cod_ref, PDO::PARAM_STR); // cod_ref como texto
            $stmt->bindValue(5, $desc_tasa, PDO::PARAM_STR);
            $stmt->bindValue(6, $is_multiplica, PDO::PARAM_INT); // El valor booleano 0 o 1
            $stmt->execute();

            // Si todo sale bien, retornamos un mensaje de éxito
            $response = ['success' => true];
        } catch (PDOException $e) {
            $mensajeError = $e->getMessage();

            // Ahora validamos si el mensaje contiene el mensaje deseado y lo mostramos en ese caso
            if (strpos($mensajeError, 'La posición ya existe para este trámite') !== false) {
                echo "Error: " . 'La posición ya existe para este trámite';
            } else {
                echo "Error: " . $mensajeError;
            }
            $response = ['success' => false, 'error' => $mensajeError];
        }
        echo json_encode($response);
    }


    public function get_tasaproced_id($tasaproced_id)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT td_tasaproced.tasaproced_id,
                   tm_tasa.tasa_nom,
                   td_tasaproced.desc_tasa,
                   tm_procedimiento.proced_nom,
                   td_tasaproced.tasaproced_pos,
                   td_tasaproced.tasaproced_monto,
                   td_tasaproced.cod_ref,
                   td_tasaproced.is_multiplica    -- Asegúrate de seleccionar el valor de is_multiplica
            FROM sc_giros.td_tasaproced 
            inner join sc_giros.tm_tasa on td_tasaproced.tasa_id=tm_tasa.tasa_id 
            inner join sc_giros.tm_procedimiento on td_tasaproced.proced_id=tm_procedimiento.proced_id 
            WHERE td_tasaproced.est = 1 
            and td_tasaproced.tasaproced_id =?";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $tasaproced_id);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }

    public function get_data_proced_ciud($proceciudadano_id)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT pc.*, pr.*, ciud.ciud_numero_documento, ciud.ciud_primer_apellido, ciud.ciud_segundo_apellido, 
                           ciud.ciud_nombre, ciud.ciud_foto, ciud.ciud_fecha_nac, ciud.ciud_sexo, 
                           emp.empr_ruc, emp.empr_razon_social
                    FROM sc_giros.td_procedciudadano pc 
                    INNER JOIN sc_giros.tm_procedimiento pr ON pr.proced_id = pc.proced_id
                    INNER JOIN tb_ciudadano ciud ON ciud.ciud_id = pc.ciud_id
                    LEFT JOIN tb_empresa emp ON emp.empr_id = pc.empr_id 
                    WHERE pc.procedciudadano_id = ?";
        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $proceciudadano_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function get_procedimiento_detalle($proced_id)
    {
        $conectar = parent::conexion();
        parent::set_names();

        // 1. Obtener datos del procedimiento
        $sql1 = "SELECT proced_id, proced_cod, proced_nom, fechacrea, est, proced_area, proced_tupa, proced_tipocampo, proced_administradotipo, proced_tipoindvasc
             FROM sc_giros.tm_procedimiento
             WHERE proced_id = ?";
        $stmt1 = $conectar->prepare($sql1);
        $stmt1->bindValue(1, $proced_id);
        $stmt1->execute();
        $procedimiento = $stmt1->fetch(PDO::FETCH_ASSOC);

        // 2. Obtener tasas asociadas al procedimiento
        $sql2 = "SELECT t.tasaproced_id, t.tasa_id, t.proced_id, t.tasaproced_pos, t.tasaproced_monto, t.est, t.desc_tasa, t.cod_ref, t.tasa_partida, t.is_multiplica,
                ta.tasa_nom, t.is_multiplica
             FROM sc_giros.td_tasaproced t
             INNER JOIN sc_giros.tm_tasa ta ON t.tasa_id = ta.tasa_id
             WHERE t.proced_id = ?";
        $stmt2 = $conectar->prepare($sql2);
        $stmt2->bindValue(1, $proced_id);
        $stmt2->execute();
        $tasas = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        return [
            "procedimiento" => $procedimiento,
            "tasas" => $tasas
        ];
    }
}
