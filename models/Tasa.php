<?php
class tasa extends Conectar
{

    public function insert_tasa($tasa_nom, $tasa_tipo)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "INSERT INTO sc_giros.tm_tasa(tasa_nom,multiplica,est) VALUES (?,?,1);";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $tasa_nom);
        $sql->bindValue(2, $tasa_tipo);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }

    public function update_tasa($tasa_id, $tasa_nom, $tasa_tipo)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "UPDATE sc_giros.tm_tasa
                SET
                    tasa_nom = ?,
                    multiplica = ?
                WHERE
                    tasa_id = ?";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $tasa_nom);
        $sql->bindValue(2, $tasa_tipo);
        $sql->bindValue(3, $tasa_id);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }

    public function delete_tasa($tasa_id)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "UPDATE sc_giros.tm_tasa
                SET
                    est = 0
                WHERE
                    tasa_id = ?";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $tasa_id);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }

    public function cancelar_ordenGiro($tasatciudadano_id)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "call sc_giros.cancelar_pago_tasa(?)";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $tasatciudadano_id);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }
    public function Pagar_Order_Giro_trabajador($tasatciudadano_id, $usu_id, $comentario)
    {
        $conectar = parent::conexion();
        parent::set_names();

        // Iniciar la transacción
        $conectar->beginTransaction();

        try {
            $sql = "SELECT sc_giros.fn_pagar_tasa_trabajador(?, ?, ?) as ogciud_id;";
            $stmt = $conectar->prepare($sql);
            $stmt->bindParam(1, $tasatciudadano_id, PDO::PARAM_INT);
            $stmt->bindParam(2, $usu_id, PDO::PARAM_INT);
            $stmt->bindParam(3, $comentario, PDO::PARAM_STR);
            $stmt->execute();

            // Obtener los resultados de la función SQL
            $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Confirmar la transacción
            $conectar->commit();

            return $resultado;
        } catch (PDOException $e) {
            // En caso de error, deshacer la transacción
            $conectar->rollback();
            throw new Exception("Error al pagar la orden: " . $e->getMessage());
        }
    }



    public function get_nmr_recibo_trabajador()
    {

        $conectar = parent::conexion();
        parent::set_names();

        // Preparar la llamada al procedimiento almacenado
        $sql = "SELECT sc_giros.obtener_valor_recibo() AS valor_recibo;";
        $sql = $conectar->prepare($sql);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }

    public function update_nmr_recibo_trabajador($recibo_nro, $ogciud_id)
    {
        try {
            $conectar = parent::conexion();
            parent::set_names();

            // Iniciar la transacción
            $conectar->beginTransaction();

            // Preparar la consulta para actualizar el número de recibo
            $sql = "UPDATE sc_giros.td_ordengirociud SET recibo_nro = ? WHERE ogciud_id = ?";
            $stmt = $conectar->prepare($sql);
            $stmt->bindParam(1, $recibo_nro, PDO::PARAM_INT);
            $stmt->bindParam(2, $ogciud_id, PDO::PARAM_INT);
            $stmt->execute();

            // Confirmar la transacción
            $conectar->commit();

            // No es necesario realizar un fetchAll() ya que UPDATE devuelve el número de filas afectadas o true/false

            return true; // Opcionalmente podrías retornar algún indicador de éxito
        } catch (PDOException $e) {
            // En caso de error, deshacer la transacción
            $conectar->rollback();
            throw new Exception("Error al actualizar el número de recibo del trabajador: " . $e->getMessage());
        }
    }

    public function obtenerultimovalor()
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "select ogciud_id from sc_giros.td_ordengirociud  order by fechacrea desc limit 1;";
        $sql = $conectar->prepare($sql);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }
    public function Pagar_Order_Giro($tasatciudadano_id, $usu_id, $comentario,  $cantidad = 1)
    {
        $conectar = parent::conexion();
        parent::set_names();
        try {
            // 1. Iniciar la transacción
            $conectar->beginTransaction();

            // 2. Normalizar comentario
            $comentario = mb_strtoupper($comentario, 'UTF-8');

            // 3. Obtener año actual
            $currentYear = date('Y');

            // 4. Verificar si es la primera orden del año para sincronizar la secuencia
            // En operaciones normales (ya existen órdenes este año), se omite el DDL por completo para evitar AccessExclusiveLock
            $sqlCheck = "SELECT 1 FROM sc_giros.td_ordengirociud WHERE ogciud_id LIKE :mask LIMIT 1";
            $stmtCheck = $conectar->prepare($sqlCheck);
            $stmtCheck->execute([':mask' => '%-' . $currentYear]);
            $hasOrdersThisYear = $stmtCheck->fetchColumn();

            if (!$hasOrdersThisYear) {
                // Adquirir advisory lock transaccional (987654340) para sincronización atómica
                $conectar->exec("SELECT pg_advisory_xact_lock(987654340)");

                // Doble comprobación con el candado adquirido
                $stmtCheck->execute([':mask' => '%-' . $currentYear]);
                if (!$stmtCheck->fetchColumn()) {
                    $sqlMax = "
                        SELECT COALESCE(MAX(SUBSTRING(ogciud_id FROM 1 FOR 6)::INTEGER), 0) + 1 AS next_val
                        FROM sc_giros.td_ordengirociud
                        WHERE ogciud_id LIKE :mask
                    ";
                    $stmtMax = $conectar->prepare($sqlMax);
                    $stmtMax->execute([':mask' => '%-' . $currentYear]);
                    $nextVal = (int)$stmtMax->fetchColumn();
                    if ($nextVal < 1) {
                        $nextVal = 1;
                    }
                    $conectar->exec("ALTER SEQUENCE sc_giros.ordengiro_id_sequence RESTART WITH {$nextVal}");
                }
            }

            // 5. Obtener datos de tasa y procedimiento
            $sql3 = "
            SELECT 
                tc.ciud_id        AS ciudadano_id,
                tt.tasaproced_monto AS importe
            FROM sc_giros.td_tasaproced tt
            JOIN sc_giros.td_tasatciud ttc ON tt.tasaproced_id = ttc.tasatciud_tasaproced
            JOIN sc_giros.td_procedciudadano tc ON ttc.tasatciud_procedciud = tc.procedciudadano_id
            WHERE ttc.tasatciud_id = :tasatciud_id
              AND ttc.est = 1
        ";
            $stmt3 = $conectar->prepare($sql3);
            $stmt3->execute([':tasatciud_id' => $tasatciudadano_id]);
            $datos = $stmt3->fetch(PDO::FETCH_ASSOC);
            if (!$datos) {
                throw new Exception("La tasa no existe o no está en estado Pendiente.");
            }
            $ciudadanoId = $datos['ciudadano_id'];
            $importe     = $datos['importe'];
            

            // 6. Marcar la tasa como pagada (est = 2)
            $sql4 = "
            UPDATE sc_giros.td_tasatciud
            SET est = 2
            WHERE tasatciud_id = :tasatciud_id
              AND est = 1
        ";
            $stmt4 = $conectar->prepare($sql4);
            $stmt4->execute([':tasatciud_id' => $tasatciudadano_id]);

            // 7. Generar nuevo ogciud_id: seq() formato '000000-YYYY'
            $seq = $conectar
                ->query("SELECT nextval('sc_giros.ordengiro_id_sequence')::TEXT")
                ->fetchColumn();
            $newOgciudId = str_pad($seq, 6, '0', STR_PAD_LEFT) . '-' . $currentYear;

            // 8. Insertar en td_ordengirociud
            $sql5 = "
            INSERT INTO sc_giros.td_ordengirociud
                (ogciud_id, fechacrea, pers_id, ogciud_comentario, est)
            VALUES
                (:ogciud_id, CURRENT_TIMESTAMP, :pers_id, :comentario, 1)
        ";
            $stmt5 = $conectar->prepare($sql5);
            $stmt5->execute([
                ':ogciud_id'  => $newOgciudId,
                ':pers_id'    => $usu_id,
                ':comentario' => $comentario
            ]);

            $importeTotal = $importe * $cantidad;
            // 9. Insertar en td_giro_tasa_ciudadano
            $sql6 = "
            INSERT INTO sc_giros.td_giro_tasa_ciudadano
                (fechacrea, tasaciud_id, girot_giro, cantidad, importe, est)
            VALUES
                (CURRENT_TIMESTAMP, :tasaciud_id, :girot_giro, :cantidad, :importe, 1)
        ";
            $stmt6 = $conectar->prepare($sql6);
            $stmt6->execute([
                ':tasaciud_id' => $tasatciudadano_id,
                ':girot_giro'  => $newOgciudId,
                ':cantidad'    => $cantidad,
                ':importe'     => $importeTotal   
            ]);

            // 10. Verificar si quedan tasas pendientes para este procedciudadano
            $sql7 = "
            SELECT 1
            FROM sc_giros.td_tasatciud
            WHERE tasatciud_procedciud = (
                SELECT tasatciud_procedciud
                FROM sc_giros.td_tasatciud
                WHERE tasatciud_id = :tasatciud_id
            )
              AND est IN (1, 3)
            LIMIT 1
        ";
            $stmt7 = $conectar->prepare($sql7);
            $stmt7->execute([':tasatciud_id' => $tasatciudadano_id]);
            $hayPendientes = (bool) $stmt7->fetchColumn();

            // 11. Si no hay pendientes, actualizar estado del procedciudadano a 2
            if (!$hayPendientes) {
                $sql8 = "
                UPDATE sc_giros.td_procedciudadano
                SET est = 2
                WHERE procedciudadano_id = (
                    SELECT tasatciud_procedciud
                    FROM sc_giros.td_tasatciud
                    WHERE tasatciud_id = :tasatciud_id
                )
            ";
                $stmt8 = $conectar->prepare($sql8);
                $stmt8->execute([':tasatciud_id' => $tasatciudadano_id]);
            }

            // 12. Confirmar la transacción
            $conectar->commit();

            return [
                'success'      => true,
                'ogciud_id'    => $newOgciudId,
                'importe'      => $importe,
                'cantidad'     => $cantidad
            ];
        } catch (Exception $e) {
            // Rollback y rethrow
            $conectar->rollBack();
            throw new Exception("Error en Pagar_Order_Giro: " . $e->getMessage());
        }
    }

    public function Pagar_Order_Giro_Empresa($tasatempresa_id, $usu_id, $cantidad, $comentario)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "CALL sc_giros.pagar_tasa_empresa(?,?,?,?)";
        $sql = $conectar->prepare($sql);
        $sql->bindParam(1, $tasatempresa_id, PDO::PARAM_STR);
        $sql->bindParam(2, $usu_id, PDO::PARAM_INT);
        $sql->bindParam(3, $cantidad, PDO::PARAM_INT);
        $sql->bindParam(4, $comentario, PDO::PARAM_STR);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }
    public function crearOrdenini($usu_id, $comentario)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "CALL sc_giros.crear_ordengiro_ciud(?,?)";
        $sql = $conectar->prepare($sql);
        $sql->bindParam(1, $usu_id, PDO::PARAM_INT);
        $sql->bindParam(2, $comentario, PDO::PARAM_STR);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }
    public function crearOrdenini_empr($usu_id, $comentario)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "CALL sc_giros.crear_ordengiro_empresa(?,?)";
        $sql = $conectar->prepare($sql);
        $sql->bindParam(1, $usu_id, PDO::PARAM_INT);
        $sql->bindParam(2, $comentario, PDO::PARAM_STR);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }

    public function obtenerultimovalor_empr()
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "select ogempr_id from sc_giros.td_ordengiroempr  order by ogempr_id desc limit 1;";
        $sql = $conectar->prepare($sql);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }
    public function obtenerid_proceempresa($tasaempr)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "select procedempr_id from sc_giros.td_procedempresa  tp
            inner join sc_giros.td_tasatempr te on te.tasatempr_procedempr = tp.procedempr_id
            where te.tasatempr_id= ?
            order by procedempr_id desc limit 1;";
        $sql = $conectar->prepare($sql);
        $sql->bindParam(1, $tasaempr, PDO::PARAM_INT);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }
    public function obteneridciud($precedciud)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT tc.ciud_id
            FROM sc_giros.td_procedciudadano tc
            WHERE tc.procedciudadano_id = ?;";
        $sql = $conectar->prepare($sql);
        $sql->bindParam(1, $precedciud, PDO::PARAM_STR);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }
    public function insertarTasaGiros($tasatciudadano_id, $p_ordengiro_id, $p_cantidad)
    {
        $conectar = parent::conexion();
        parent::set_names();

        try {
            // Iniciar la transacción
            $conectar->beginTransaction();

            // Obtener el importe de la tasa multiplicado por la cantidad
            $sql = "SELECT tasaproced_monto * :p_cantidad AS v_importe
                FROM sc_giros.td_tasaproced tp
                INNER JOIN sc_giros.td_tasatciud ttc ON tp.tasaproced_id = ttc.tasatciud_tasaproced
                WHERE ttc.tasatciud_id = :tasatciudadano_id";

            $stmt = $conectar->prepare($sql);
            $stmt->bindParam(':p_cantidad', $p_cantidad, PDO::PARAM_INT);
            $stmt->bindParam(':tasatciudadano_id', $tasatciudadano_id, PDO::PARAM_INT);
            $stmt->execute();

            $v_importe = $stmt->fetchColumn();

            // Verificar si el importe es nulo
            if (!$v_importe) {
                throw new Exception('No se pudo obtener el importe para la tasa con ID: ' . $tasatciudadano_id);
            }

            // Insertar datos en td_giro_tasa_ciudadano
            $sql = "INSERT INTO sc_giros.td_giro_tasa_ciudadano (fechacrea, tasaciud_id, girot_giro, cantidad, importe, est)
                VALUES (CURRENT_TIMESTAMP, :tasatciudadano_id, :p_ordengiro_id, :p_cantidad, :v_importe, 1)";

            $stmt = $conectar->prepare($sql);
            $stmt->bindParam(':tasatciudadano_id', $tasatciudadano_id, PDO::PARAM_INT);
            $stmt->bindParam(':p_ordengiro_id', $p_ordengiro_id, PDO::PARAM_STR);
            $stmt->bindParam(':p_cantidad', $p_cantidad, PDO::PARAM_INT);
            $stmt->bindParam(':v_importe', $v_importe, PDO::PARAM_STR);
            $stmt->execute();

            // Actualizar el estado de la tasa a 2 en td_tasatciud
            $sql = "UPDATE sc_giros.td_tasatciud
                SET est = 2
                WHERE tasatciud_id = :tasatciudadano_id AND est = 1";

            $stmt = $conectar->prepare($sql);
            $stmt->bindParam(':tasatciudadano_id', $tasatciudadano_id, PDO::PARAM_INT);
            $stmt->execute();

            // Verificar si hay tasas pendientes en estado 1 para el mismo procedciudadano
            $sql = "SELECT 1
                FROM sc_giros.td_tasatciud
                WHERE tasatciud_procedciud = (
                    SELECT tasatciud_procedciud
                    FROM sc_giros.td_tasatciud
                    WHERE tasatciud_id = :tasatciudadano_id
                ) AND est IN (1, 3)";

            $stmt = $conectar->prepare($sql);
            $stmt->bindParam(':tasatciudadano_id', $tasatciudadano_id, PDO::PARAM_INT);
            $stmt->execute();

            // Si no hay tasas pendientes, actualizar el estado del procedciudadano
            if ($stmt->rowCount() == 0) {
                $sql = "UPDATE sc_giros.td_procedciudadano
                    SET est = 2
                    WHERE procedciudadano_id = (
                        SELECT tasatciud_procedciud
                        FROM sc_giros.td_tasatciud
                        WHERE tasatciud_id = :tasatciudadano_id
                    )";

                $stmt = $conectar->prepare($sql);
                $stmt->bindParam(':tasatciudadano_id', $tasatciudadano_id, PDO::PARAM_INT);
                $stmt->execute();
            }

            // Confirmar la transacción
            $conectar->commit();

            return true;
        } catch (Exception $e) {
            // Si ocurre algún error, revertir la transacción y devolver el mensaje de error
            $conectar->rollBack();
            error_log("Error al insertar la tasa con ID: " . $tasatciudadano_id . " - " . $e->getMessage());
            return $e->getMessage();  // Retornar el mensaje de error para mostrarlo en el controlador
        }
    }





    public function insertarTasaGiros_empresa($tasatempresa_id, $p_ordengiro_id, $p_cantidad, $yeartext)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "CALL sc_giros.crear_giro_tasa_empresa(?,?,?,?)";
        $sql = $conectar->prepare($sql);
        $sql->bindParam(1, $tasatempresa_id, PDO::PARAM_INT);
        $sql->bindParam(2, $p_ordengiro_id, PDO::PARAM_STR);
        $sql->bindParam(3, $p_cantidad, PDO::PARAM_INT);
        $sql->bindParam(4, $yeartext, PDO::PARAM_STR);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }

    public function get_tasa()
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT * FROM sc_giros.tm_tasa WHERE est = 1";
        $sql = $conectar->prepare($sql);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }

    public function get_tasa_id($tasa_id)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT * FROM sc_giros.tm_tasa WHERE est = 1 AND tasa_id = ?";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $tasa_id);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }
    public function get_proced_tasa_x_id($proced_id, $tupa_id, $area_id)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT
            td_tasaproced.tasaproced_id,
            tm_tasa.tasa_nom,
            td_tasaproced.desc_tasa,
            td_tasaproced.tasaproced_monto,
            td_tasaproced.tasaproced_pos,
            td_tasaproced.cod_ref,
            tm_tupa.tupa_block,
            sc_giros.tm_tupa.est,
            td_tasaproced.is_multiplica
        FROM
            sc_giros.td_tasaproced
        INNER JOIN
            sc_giros.tm_tasa ON sc_giros.td_tasaproced.tasa_id = tm_tasa.tasa_id
        INNER JOIN
            sc_giros.tm_procedimiento ON sc_giros.td_tasaproced.proced_id = tm_procedimiento.proced_id
        INNER JOIN
            sc_giros.tm_tupa ON sc_giros.tm_procedimiento.proced_tupa = sc_giros.tm_tupa.tupa_id
        WHERE
            tm_procedimiento.proced_id = ?
            AND td_tasaproced.est = 1
            AND tm_procedimiento.proced_tupa = ?
            AND tm_procedimiento.proced_area = ?;
        ;";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $proced_id);
        $sql->bindValue(2, $tupa_id);
        $sql->bindValue(3, $area_id);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }
    public function listar_proceds_tasa_x_procedciudadano($proceciudadano_id)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = 'SELECT ttc.tasatciud_id, t.tasa_nom, tt.tasaproced_pos, tt.tasaproced_monto, ttc.est,  tt.is_multiplica, tm.proced_tipocampo
            FROM sc_giros.td_tasatciud ttc
            INNER JOIN sc_giros.td_procedciudadano tc ON ttc.tasatciud_procedciud = tc.procedciudadano_id
			inner join sc_giros.tm_procedimiento tm on tc.proced_id = tm.proced_id
            INNER JOIN sc_giros.td_tasaproced tt ON ttc.tasatciud_tasaproced = tt.tasaproced_id
            INNER JOIN sc_giros.tm_tasa t ON tt.tasa_id = t.tasa_id
            WHERE tc.procedciudadano_id = ? AND tc.est IN (0,1,2,3,4,5,6) and ttc.est IN (0,1,2,3,4,5,6)
            ORDER BY tt.tasaproced_pos;';
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $proceciudadano_id);
        $sql->execute();
        return $resultado = $sql->fetchAll(PDO::FETCH_ASSOC);
    }
    public function listar_proceds_tasa_x_procedempresa($proceempresa_id)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = 'SELECT 
            tte.tasatempr_id, 
            t.tasa_nom, 
            tt.tasaproced_pos, 
            tt.tasaproced_monto, 
            tte.est, 
            tm.proced_tipocampo,
            t.multiplica,
            t.multiempo
        FROM 
            sc_giros.td_tasatempr tte
        INNER JOIN 
            sc_giros.td_procedempresa tp ON tte.tasatempr_procedempr = tp.procedempr_id
        INNER JOIN 
            sc_giros.tm_procedimiento tm ON tp.procedempr_proced = tm.proced_id
        INNER JOIN 
            sc_giros.td_tasaproced tt ON tte.tasatempr_tasaproced = tt.tasaproced_id
        INNER JOIN 
            sc_giros.tm_tasa t ON tt.tasa_id = t.tasa_id
        WHERE 
            tp.procedempr_id = ? 
            AND tp.est IN (1, 2, 3) 
            AND tte.est IN (1, 2, 3)
        ORDER BY 
            tt.tasaproced_pos;';
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $proceempresa_id);
        $sql->execute();
        return $resultado = $sql->fetchAll(PDO::FETCH_ASSOC);
    }
    public function get_tasa_modal($proced_id)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT * FROM sc_giros.tm_tasa
                WHERE est = 1
                AND tasa_id not in (select tasa_id from sc_giros.td_tasaproced where proced_id=? AND est=1)";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $proced_id);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }
}
