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
    private function enteroPositivo($value)
    {
        if ((!is_int($value) && !is_string($value)) || filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2147483647]]) === false) {
            throw new InvalidArgumentException('Identificador o cantidad inválida.');
        }
        return (int) $value;
    }

    public function Pagar_Order_Giro($tasatciudadano_id, $usu_id, $comentario, $cantidad = 1)
    {
        $resultado = $this->emitirGrupo([$tasatciudadano_id], [$cantidad], $usu_id, $comentario);
        return ['success' => true, 'ogciud_id' => $resultado['ogciud_id'],
            'importe' => $resultado['importes'][0], 'cantidad' => (int) $cantidad];
    }

    // One connection owns the header, every detail/state change and trigger audit.
    public function emitirGrupo($ids, $cantidades, $usu_id, $comentario)
    {
        $usu_id = $this->enteroPositivo($usu_id);
        if (!is_array($ids) || !is_array($cantidades) || !$ids || count($ids) !== count($cantidades) || !is_string($comentario)) {
            throw new InvalidArgumentException('Selección inválida.');
        }
        $ids = array_map([$this, 'enteroPositivo'], array_values($ids));
        $cantidades = array_map([$this, 'enteroPositivo'], array_values($cantidades));
        if (count(array_unique($ids)) !== count($ids)) {
            throw new InvalidArgumentException('Tasas repetidas.');
        }
        $conectar = parent::conexion(true);
        parent::set_names();
        try {
            $conectar->beginTransaction();
            $bitaStmt = $conectar->prepare("SELECT COALESCE(MAX(bita_id), 0) FROM sc_seguridad.tb_bitacora");
            $bitaStmt->execute();
            $bitaMin = (int) $bitaStmt->fetchColumn();

            $orden = $this->crearOrdenini($usu_id, $comentario, $conectar)[0]['ogciud_id'];
            $importes = [];
            // Stable lock order reduces deadlocks for overlapping selections.
            $indices = array_keys($ids);
            usort($indices, function ($a, $b) use ($ids) { return $ids[$a] <=> $ids[$b]; });
            foreach ($indices as $i) {
                $importes[$i] = $this->emitirDetalle($conectar, $ids[$i], $orden, $cantidades[$i]);
            }
            // Only audit rows created by THIS transaction, bounded by PK index to avoid full table scans.
            $audit = $conectar->prepare("UPDATE sc_seguridad.tb_bitacora SET pers_id = ?
                WHERE bita_id > ?
                  AND (pers_id IS NULL OR pers_id = 0)
                  AND xmin::text::bigint = (txid_current() % 4294967296)");
            $audit->execute([$usu_id, $bitaMin]);
            $conectar->commit();
            return ['ogciud_id' => $orden, 'importes' => $importes, 'data' => array_map(function ($id) { return [$id]; }, $ids)];
        } catch (Throwable $e) {
            if ($conectar->inTransaction()) $conectar->rollBack();
            throw $e;
        }
    }

    private function emitirDetalle($conectar, $tasatciudadano_id, $newOgciudId, $cantidad)
    {
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
        FOR UPDATE OF ttc
        ";
        $stmt3 = $conectar->prepare($sql3);
        $stmt3->execute([':tasatciud_id' => $tasatciudadano_id]);
        $datos = $stmt3->fetch(PDO::FETCH_ASSOC);
        if (!$datos) {
            throw new Exception("La tasa no existe o no está en estado Pendiente.");
        }
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
        if ($stmt4->rowCount() !== 1) {
            throw new RuntimeException('La tasa ya no está pendiente.');
        }

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

        return $importe;
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
    public function crearOrdenini($usu_id, $comentario, $conectar = null)
    {
        if ($conectar === null) {
            $conectar = parent::conexion();
            parent::set_names();
        }
        $stmt = $conectar->prepare("SELECT sc_giros.crear_ordengiro_ciud_id(?, ?) AS ogciud_id");
        $stmt->execute([$usu_id, $comentario]);
        $id = $stmt->fetchColumn();
        if (!$id) throw new RuntimeException('No se pudo crear la orden.');
        return [['ogciud_id' => $id]];
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
        $conectar = null;
        try {
            $id = $this->enteroPositivo($tasatciudadano_id);
            $cantidad = $this->enteroPositivo($p_cantidad);
            $conectar = parent::conexion();
            parent::set_names();
            $conectar->beginTransaction();
            $this->emitirDetalle($conectar, $id, $p_ordengiro_id, $cantidad);
            $conectar->commit();
            return true;
        } catch (Throwable $e) {
            if ($conectar !== null && $conectar->inTransaction()) $conectar->rollBack();
            return $e->getMessage();
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
