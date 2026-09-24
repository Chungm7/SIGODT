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
      
        

        
    }
?>