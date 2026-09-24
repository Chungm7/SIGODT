<?php
    class Requerimientos extends Conectar{

        public function insert_requerimientos($requerimientos_nom){
            $conectar= parent::conexion();
            parent::set_names();
            $sql="INSERT INTO sc_giros.tm_requerimientos(req_nom,est) VALUES (?,1);";
            $sql=$conectar->prepare($sql);
            $sql->bindValue(1, $requerimientos_nom);
            $sql->execute();
            return $resultado=$sql->fetchAll();
        }

        public function update_requerimientos($requerimientos_id,$requerimientos_nom,){
            $conectar= parent::conexion();
            parent::set_names();
            $sql="UPDATE sc_giros.tm_requerimientos
                SET
                    req_nom = ?
                WHERE
                    req_id = ?";
            $sql=$conectar->prepare($sql);
            $sql->bindValue(1, $requerimientos_nom);
            $sql->bindValue(2, $requerimientos_id);
            $sql->execute();
            return $resultado=$sql->fetchAll();
        }

        public function delete_requerimientos($requerimientos_id){
            $conectar= parent::conexion();
            parent::set_names();
            $sql="UPDATE sc_giros.tm_requerimientos
                SET
                    est = 0
                WHERE
                    req_id = ?";
            $sql=$conectar->prepare($sql);
            $sql->bindValue(1, $requerimientos_id);
            $sql->execute();
            return $resultado=$sql->fetchAll();
        }


        public function get_requerimientos(){
            $conectar= parent::conexion();
            parent::set_names();
            $sql="SELECT * FROM sc_giros.tm_requerimientos WHERE est = 1";
            $sql=$conectar->prepare($sql);
            $sql->execute();
            return $resultado=$sql->fetchAll();
        }

        public function get_requerimientos_id($requerimientos_id){
            $conectar= parent::conexion();
            parent::set_names();
            $sql="SELECT * FROM sc_giros.tm_requerimientos WHERE est = 1 AND req_id = ?";
            $sql=$conectar->prepare($sql);
            $sql->bindValue(1, $requerimientos_id);
            $sql->execute();
            return $resultado=$sql->fetchAll();
        }
        public function get_requerimientos_editar($requerimientos_id){
            $conectar= parent::conexion();
            parent::set_names();
            $sql="SELECT * FROM sc_giros.td_reqproced tdr
            inner join sc_giros.tm_requerimientos tr on tr.req_id = tdr.reqproced_req
            WHERE tdr.est = 1 AND tdr.reqproced_id = ?";
            $sql=$conectar->prepare($sql);
            $sql->bindValue(1, $requerimientos_id);
            $sql->execute();
            return $resultado=$sql->fetchAll();
        }
        public  function get_proced_requerimientos_x_id($proced_id, $tupa_id,$area_id ){
            $conectar= parent::conexion();
            parent::set_names();
            $sql="SELECT
            trq.reqproced_id,
            tr.req_nom,
            trq.reqproced_pos,
            tt.est
        FROM
            sc_giros.td_reqproced trq
        INNER JOIN
            sc_giros.tm_requerimientos  tr ON trq.reqproced_req =tr.req_id
        INNER JOIN
            sc_giros.tm_procedimiento ttm ON trq.reqproced_proced = ttm.proced_id
        INNER JOIN
            sc_giros.tm_tupa  tt ON ttm.proced_tupa = tt.tupa_id
        WHERE
        ttm.proced_id = ?
            AND trq.est = 1
            AND ttm.proced_tupa = ?
            AND ttm.proced_area = ?;";
            $sql=$conectar->prepare($sql);
            $sql->bindValue(1, $proced_id);
            $sql->bindValue(2, $tupa_id);
            $sql->bindValue(3, $area_id);
            $sql->execute();
            return $resultado=$sql->fetchAll();
        }
      /*   public  function listar_proceds_requerimientos_x_procedciudadano($procedciudadano_id){
            $conectar= parent::conexion();
            parent::set_names();
            $sql="SELECT ttc.requerimientostciudadano_id, t.requerimientos_nom, tt.requerimientosproced_pos, tt.requerimientosproced_monto, ttc.est, tm.proced_tipocampo
            FROM sc_giros.td_requerimientostciudadano ttc
            INNER JOIN sc_giros.td_procedciudadano tc ON ttc.requerimientostciudadano_procedciudadano = tc.procedciudadano_id
			inner join sc_giros.tm_proced tm on tc.procedciudadano_proced = tm.proced_id
            INNER JOIN sc_giros.td_requerimientosproced tt ON ttc.requerimientostciudadano_requerimientosproced = tt.requerimientosproced_id
            INNER JOIN sc_giros.tm_requerimientos t ON tt.requerimientos_id = t.requerimientos_id
            WHERE tc.procedciudadano_id = ? AND tc.est IN (1, 2,3) and ttc.est IN (1, 2,3)
            ORDER BY tt.requerimientosproced_pos;";
            $sql=$conectar->prepare($sql);
            $sql->bindValue(1, $procedciudadano_id);
            $sql->execute();
            return $resultado=$sql->fetchAll(PDO::FETCH_ASSOC);
        } */
        public function delete_proced_req($reqproced_id) {
            $conectar = parent::conexion();
            parent::set_names();
        
            try {

                $sql = "CALL sc_giros.eliminarreqproced(?)";
                $stmt = $conectar->prepare($sql);
                $stmt->bindParam(1, $reqproced_id, PDO::PARAM_INT);
                $stmt->execute();
                return true;
            } catch (PDOException $e) {
                echo "Error: " . $e->getMessage();
                return false;
            }
        }
        public function get_requerimientos_modal($proced_id){
            $conectar= parent::conexion();
            parent::set_names();
                $sql="SELECT * 
                FROM sc_giros.tm_requerimientos
                WHERE est = 1
                AND req_id NOT IN (
                    SELECT reqproced_req
                    FROM sc_giros.td_reqproced 
                    WHERE reqproced_proced = ? 
                    AND est = 1
                );";
            $sql=$conectar->prepare($sql);
            $sql->bindValue(1, $proced_id);
            $sql->execute();
            return $resultado=$sql->fetchAll();
        }
        public function update_reqproced($reqproced_id, $reqproced_pos) {
            try {
                $conectar = parent::conexion();
                parent::set_names();
                
                $sql = "CALL sc_giros.actualizarposreq(?, ?)";
                $stmt = $conectar->prepare($sql);
                $stmt->bindValue(1, $reqproced_id, PDO::PARAM_INT);
                $stmt->bindValue(2, $reqproced_pos, PDO::PARAM_INT);
                $stmt->execute();
                $response = ['success' => true];
                
            } catch (PDOException $e) {
                $mensajeError = $e->getMessage();
                
                // Ahora validamos si el mensaje contiene el mensaje deseado y lo mostramos en ese caso
                if (strpos($mensajeError, 'La nueva posición es inválida') !== false) {
                    echo "Error: " . 'La posición ya existe para este trámite';
                } else {
                    echo "Error: " . $mensajeError;
                }
                $response = ['success' => false, 'error' => $mensajeError];
                
            }
            echo json_encode($response);
        }
        public function insert_proced_req($proced_id, $tasa_id) {
            $conectar = parent::conexion();
            parent::set_names();            
            $sql = "INSERT INTO sc_giros.td_reqproced(reqproced_req, reqproced_proced, est) VALUES (?, ?, 1) RETURNING reqproced_id";
            $stmt = $conectar->prepare($sql);
            $stmt->bindValue(1, $tasa_id);
            $stmt->bindValue(2, $proced_id);
            $stmt->execute();
        
           
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
            return $resultado;
        }
        

        
    }
?>