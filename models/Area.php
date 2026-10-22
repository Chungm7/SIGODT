<?php
    class Area extends Conectar{

        public function delete_area($depe_id){
            $conectar= parent::conexion();
            parent::set_names();
            $sql="UPDATE sc_giros.tm_area
                SET
                    est = 0
                WHERE
                    depe_id = ?";
            $sql=$conectar->prepare($sql);
            $sql->bindValue(1, $depe_id);
            $sql->execute();
            return $resultado=$sql->fetchAll();
        }

        public function get_area(){
            $conectar= parent::conexion();
            parent::set_names();
            $sql="SELECT depe_id,depe_denominacion, depe_codigo, depe_representante FROM tb_dependencia where depe_estado = 'A' order by depe_denominacion";
            $sql=$conectar->prepare($sql);
            $sql->execute();
            return $resultado=$sql->fetchAll();
        }

        public function get_tributos_areas($tupa_id){
            if ((!is_int($tupa_id) && !is_string($tupa_id)) ||
                filter_var($tupa_id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
                return [];
            }
            $conectar = parent::conexion();
            parent::set_names();
            $sql = "SELECT a.depe_id, a.depe_denominacion
                FROM tb_dependencia a
                WHERE a.depe_estado = 'A' AND EXISTS (
                    SELECT 1 FROM sc_giros.tm_procedimiento p
                    WHERE p.proced_area = a.depe_id AND p.proced_tupa = ? AND p.est = 1
                ) ORDER BY a.depe_denominacion";
            $stmt = $conectar->prepare($sql);
            $stmt->bindValue(1, $tupa_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        public function get_depe_id($depe_id){
            $conectar= parent::conexion();
            parent::set_names();
            $sql="SELECT depe_id, depe_denominacion FROM tb_dependencia where depe_estado = 'A' and depe_id =?";
            $sql=$conectar->prepare($sql);
            $sql->bindValue(1, $depe_id);
            $sql->execute();
            return $resultado=$sql->fetchAll();
        }
        public function insert_area_usu($depe_id, $pers_id) {
            $conectar = parent::conexion();
            parent::set_names();            
            $sql = "INSERT INTO sc_giros.td_areausu(pers_id, depe_id, est) VALUES (?, ?, 1) RETURNING areausu_id";
            $stmt = $conectar->prepare($sql);
            $stmt->bindValue(1, $pers_id);
            $stmt->bindValue(2, $depe_id);
            $stmt->execute();
        
           
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
            return $resultado;
        }
        public function eliminar_area_usu($areausu_id){
            $conectar= parent::conexion();
            parent::set_names();
            $sql="UPDATE sc_giros.td_areausu
                SET
                    est = 0
                WHERE
                    areausu_id = ?";
            $sql=$conectar->prepare($sql);
            $sql->bindValue(1, $areausu_id);
            $sql->execute();
            return $resultado=$sql->fetchAll();
        }
      
        

        

        /**
         * Auto-provisions default institutional dependency for a user on login.
         * Only provisions if the user has NO active areas, NO prior assignment
         * record for this dependency, and the dependency is active in public.tb_dependencia.
         */
        public function sincronizar_area_defecto($pers_id, $depe_id) {
            if ((!is_int($pers_id) && !is_string($pers_id)) ||
                filter_var($pers_id, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]) === false) {
                return false;
            }
            if ((!is_int($depe_id) && !is_string($depe_id)) ||
                filter_var($depe_id, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]) === false) {
                return false;
            }
            $pers_id = (int)$pers_id;
            $depe_id = (int)$depe_id;

            try {
                $conectar = parent::conexion();
                parent::set_names();

                // 1. Check if user already has any active area assignment
                $sqlActive = "SELECT 1 FROM sc_giros.td_areausu WHERE pers_id = ? AND est = 1 LIMIT 1";
                $stmtActive = $conectar->prepare($sqlActive);
                $stmtActive->bindValue(1, $pers_id, PDO::PARAM_INT);
                $stmtActive->execute();
                if ($stmtActive->fetch()) {
                    return false;
                }

                // 2. Check if a prior record exists for this depe_id (e.g. revoked by admin)
                $sqlPrior = "SELECT 1 FROM sc_giros.td_areausu WHERE pers_id = ? AND depe_id = ? LIMIT 1";
                $stmtPrior = $conectar->prepare($sqlPrior);
                $stmtPrior->bindValue(1, $pers_id, PDO::PARAM_INT);
                $stmtPrior->bindValue(2, $depe_id, PDO::PARAM_INT);
                $stmtPrior->execute();
                if ($stmtPrior->fetch()) {
                    return false;
                }

                // 3. Verify dependency is active in public.tb_dependencia
                $sqlDepe = "SELECT 1 FROM public.tb_dependencia WHERE depe_id = ? AND depe_estado = 'A' LIMIT 1";
                $stmtDepe = $conectar->prepare($sqlDepe);
                $stmtDepe->bindValue(1, $depe_id, PDO::PARAM_INT);
                $stmtDepe->execute();
                if (!$stmtDepe->fetch()) {
                    return false;
                }

                // 4. Provision default area
                $sqlInsert = "INSERT INTO sc_giros.td_areausu(pers_id, depe_id, est) VALUES (?, ?, 1) RETURNING areausu_id";
                $stmtInsert = $conectar->prepare($sqlInsert);
                $stmtInsert->bindValue(1, $pers_id, PDO::PARAM_INT);
                $stmtInsert->bindValue(2, $depe_id, PDO::PARAM_INT);
                $stmtInsert->execute();
                $resultado = $stmtInsert->fetch(PDO::FETCH_ASSOC);
                return $resultado ?: true;
            } catch (Throwable $e) {
                return false;
            }
        }
    }
?>