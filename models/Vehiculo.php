<?php
    class vehiculo extends Conectar{

        public function insert_vehiculo($vehiculo_nom){
            $conectar= parent::conexion();
            parent::set_names();
            $sql="INSERT INTO sc_giros.tm_vehiculo(vehiculo_nom,est) VALUES (?,1);";
            $sql=$conectar->prepare($sql);
            $sql->bindValue(1, $vehiculo_nom);
            $sql->execute();
            return $resultado=$sql->fetchAll();
        }
        public function get_vehiculo_x_placa($vahiculo_placa){
            $conectar= parent::conexion();
            parent::set_names();
            $sql='SELECT
            tiv.tiv_id, 
            tv.vehi_id, 
            tv.vehi_placa, 
            tv.vehi_nummotor, 
            tv.est, 
            tv.vehi_annofab, 
            ciud.ciud_nombre, 
            tc.color_nombre, 
            emp.empr_razon_social
        FROM 
            sc_transito_transporte.td_vehiculo tv
        INNER JOIN 
            sc_transito_transporte."td_TIV" tiv ON tv.vehi_id = tiv.tiv_vehiculo
        INNER JOIN 
            sc_transito_transporte.td_lista_propietarios pr ON pr.tiv_id = tiv.tiv_id
        INNER JOIN 
            public.tb_ciudadano ciud ON ciud.ciud_id = pr.ciud_id
        INNER JOIN 
            sc_transito_transporte.td_colorvehiculo tcv ON tv.vehi_id = tcv.vehiculo_id
        INNER JOIN 
            sc_transito_transporte.tm_color tc ON tcv.color_id = tc.color_id
        INNER JOIN 
            sc_transito_transporte.td_emprflota emprf ON emprf.emprflot_tiv = tiv.tiv_vehiculo
        INNER JOIN 
            public.tb_empresa emp ON emp.empr_id = emprf.emprflot_empr
        WHERE 
            tv.vehi_placa = ?;
        ';
            $sql=$conectar->prepare($sql);
            $sql->bindValue(1, $vahiculo_placa);
            $sql->execute();
            return $resultado=$sql->fetchAll();
        }   
        public function get_vehiculo_x_empresa($empr_id){
                $conectar= parent::conexion();
                parent::set_names();
                $sql='SELECT 
                tiv.tiv_id, 
                tv.vehi_placa, 
                tv.vehi_nummotor, 
                tv.est, 
                tv.vehi_annofab, 
                ciud.ciud_nombre, 
                tc.color_nombre, 
                emp.empr_razon_social
            FROM 
                sc_transito_transporte.td_vehiculo tv
            INNER JOIN 
                sc_transito_transporte."td_TIV" tiv ON tv.vehi_id = tiv.tiv_vehiculo
            INNER JOIN 
                sc_transito_transporte.td_lista_propietarios pr ON pr.tiv_id = tiv.tiv_id
            INNER JOIN 
                public.tb_ciudadano ciud ON ciud.ciud_id = pr.ciud_id
            INNER JOIN 
                sc_transito_transporte.td_colorvehiculo tcv ON tv.vehi_id = tcv.vehiculo_id
            INNER JOIN 
                sc_transito_transporte.tm_color tc ON tcv.color_id = tc.color_id
            INNER JOIN 
                sc_transito_transporte.td_emprflota emprf ON emprf.emprflot_tiv = tiv.tiv_vehiculo
            INNER JOIN 
                public.tb_empresa emp ON emp.empr_id = emprf.emprflot_empr
            WHERE 
                emp.empr_id = ?;
            ';
            $sql=$conectar->prepare($sql);
            $sql->bindValue(1, $empr_id);
            $sql->execute();
            return $resultado=$sql->fetchAll();
        } 
        public function get_vehiculo_x_grupo($tasatempr_id){
            $conectar= parent::conexion();
            parent::set_names();
            $sql='SELECT DISTINCT tv.vehi_placa
            FROM sc_giros.td_grupoproced_vehiculos tgv
            INNER JOIN sc_transito_transporte."td_TIV" tiv ON tiv.tiv_id = tgv.tiv_id 
            INNER JOIN sc_transito_transporte.td_vehiculo tv ON tv.vehi_id = tiv.tiv_vehiculo 
            INNER JOIN sc_giros.td_tasatempr tte ON tte.tasatempr_id = ?
            WHERE tgv.grupoproced_procedempresa = tte.tasatempr_procedempr';
        $sql=$conectar->prepare($sql);
        $sql->bindValue(1, $tasatempr_id);
        $sql->execute();
        return $resultado=$sql->fetchAll();
    } 
        public function get_vehiculo(){
            $conectar= parent::conexion();
            parent::set_names();
            $sql="SELECT * FROM sc_giros.tm_vehiculo WHERE est = 1";
            $sql=$conectar->prepare($sql);
            $sql->execute();
            return $resultado=$sql->fetchAll();
        }

        public function get_prced_empr_id(){
            $conectar= parent::conexion();
            parent::set_names();
            $sql="SELECT procedempr_id FROM sc_giros.td_procedempresa WHERE est = 1 order by procedempr_id desc limit 1";
            $sql=$conectar->prepare($sql);
            $sql->execute();
            return $resultado=$sql->fetchAll();
        }
        public function insert_vehiculo_grupo($tiv_id, $grupoproced_procedempresa) {
            $conectar = parent::conexion();
            parent::set_names();            
            $sql = "INSERT INTO sc_giros.td_grupoproced_vehiculos(tiv_id, grupoproced_procedempresa) VALUES (?, ?) RETURNING grupoproced_id";
            $stmt = $conectar->prepare($sql);
            $stmt->bindValue(1, $tiv_id);
            $stmt->bindValue(2, $grupoproced_procedempresa);
            $stmt->execute();
        
           
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
            return $resultado;
        }
     
      
        

        
    }
?>