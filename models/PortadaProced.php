<?php
    class Portadaproced extends Conectar {
        public function get_datos_portada($procedciudadano_id) {
            try {
                $conectar = parent::conexion();
                parent::set_names();
                $sql = "SELECT 
                tt.proced_nom, 
                ta.area_nom,
                ciud_numero_documento, 
                vh.vehi_placa,
                tt.proced_tipoindvasc,
                tc.fechacrea,
                tc.proced_id,
				tc.procedciudadano_npago,
                CONCAT(ciud_nombre,' ', ciud_primer_apellido, ' ', ciud_segundo_apellido) AS nombre_completo
            FROM 
                sc_giros.td_procedciudadano tc
            INNER JOIN 
                public.tb_ciudadano ON tb_ciudadano.ciud_id = tc.ciud_id
            INNER JOIN 
                sc_giros.tm_procedimiento tt ON tt.proced_id = tc.proced_id
            INNER JOIN 
            sc_giros.tm_area ta ON ta.area_id = tt.proced_area
			 left JOIN 
            sc_transito_transporte.".'"td_TIV"'." tiv ON tc.tiv_id = tiv.tiv_id
			left JOIN 
            sc_transito_transporte.td_vehiculo vh ON tiv.tiv_vehiculo= vh.vehi_id
            WHERE 
                procedciudadano_id = ? ";
                $stmt = $conectar->prepare($sql);
                $stmt->bindValue(1, $procedciudadano_id);
                $stmt->execute();
                return $stmt->fetchAll();
            } catch (Exception $e) {
                echo "Error en la consulta: " . $e->getMessage();
                return null; 
            }
        }
        public function get_datos_requerimito_procedid($proced_id) {
            try {
                $conectar = parent::conexion();
                parent::set_names();
                $sql = "SELECT * from sc_giros.td_reqproced tdrt
                inner join sc_giros.tm_requerimientos  tmr on  tmr.req_id = tdrt.reqproced_req
                where tdrt.est = 1 and tdrt.reqproced_proced = ?  and tmr.est = 1 order by tdrt.reqproced_pos asc";
                $stmt = $conectar->prepare($sql);
                $stmt->bindValue(1, $proced_id);
                $stmt->execute();
                return $stmt->fetchAll();
            } catch (Exception $e) {
                echo "Error en la consulta: " . $e->getMessage();
                return null; 
            }
        }
        public function get_datos_info($proced_id) {
            try {
                $conectar = parent::conexion();
                parent::set_names();
                $sql = "SELECT *
                FROM sc_giros.tm_procedimiento tt
                INNER JOIN tb_dependencia ta ON ta.depe_id = tt.proced_area
                WHERE tt.est = 1 AND tt.proced_id= ?;               
                ";
                $stmt = $conectar->prepare($sql);
                $stmt->bindValue(1, $proced_id);
                $stmt->execute();
                return $stmt->fetchAll();
            } catch (Exception $e) {
                echo "Error en la consulta: " . $e->getMessage();
                return null; 
            }
        }
        public function get_tasas_info($proced_id) {
            try {
                $conectar = parent::conexion();
                parent::set_names();
                $sql = "SELECT ta.tasa_nom, tst.tasaproced_pos, tst.tasaproced_monto  
                FROM sc_giros.tm_procedimiento tt
                INNER JOIN sc_giros.td_tasaproced tst ON tst.proced_id = tt.proced_id
                INNER JOIN sc_giros.tm_tasa ta ON ta.tasa_id = tst.tasa_id
                WHERE tt.est = 1 AND tt.proced_id = ?  and tst.est = 1
                ORDER BY tst.tasaproced_pos ASC;";
                $stmt = $conectar->prepare($sql);
                $stmt->bindValue(1, $proced_id);
                $stmt->execute();
                return $stmt->fetchAll();
            } catch (Exception $e) {
                echo "Error en la consulta: " . $e->getMessage();
                return null; 
            }
        }
        public function get_npago($procedciudadano_id) {
            try {
                $conectar = parent::conexion();
                parent::set_names();
                $sql = "SELECT procedciudadano_npago from sc_giros.td_procedciudadano
                where procedciudadano_id = ?";
                $stmt = $conectar->prepare($sql);
                $stmt->bindValue(1, $procedciudadano_id);
                $stmt->execute();
                return $stmt->fetchAll();
            } catch (Exception $e) {
                echo "Error en la consulta: " . $e->getMessage();
                return null; 
            }
        }
        public function actualizar_npago($cod, $procedciudadano_id) {
            try {
                $conectar = parent::conexion();
                parent::set_names();
                $sql = "UPDATE sc_giros.td_procedciudadano set procedciudadano_npago = ? where procedciudadano_id = ?";
                $stmt = $conectar->prepare($sql);
                $stmt->bindValue(1, $cod);
                +$stmt->bindValue(2, $procedciudadano_id);
                $stmt->execute();
                return $stmt->fetchAll();
            } catch (Exception $e) {
                echo "Error en la consulta: " . $e->getMessage();
                return null; 
            }
        }
    }
    
?>