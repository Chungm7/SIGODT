<?php
class ciudadano extends conectar
{

    public function get_ciudadano_x_doc($ciudadano_dni, $tido_id )
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT ciud_id, ciud_domicilio_real, ciud_foto, ciud_numero_documento, ciud_primer_apellido, ciud_segundo_apellido, ciud_nombre, ciud_fecha_nac, ciud_sexo
            FROM public.tb_ciudadano
            where ciud_numero_documento =? and tido_id= ? and ciud_estado  = 'A'";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $ciudadano_dni);
        $sql->bindValue(2, $tido_id);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }
    public function insert_ciudadano_grupo($ciud_id, $grupoproced_procedempresa)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "INSERT INTO sc_giros.td_grupoproced_ciud(ciud_id, grupoproced_procedempresa) VALUES (?, ?)";
        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $ciud_id);
        $stmt->bindValue(2, $grupoproced_procedempresa);
        $stmt->execute();
        return $resultado = $stmt->fetchAll();
    }
    public function insert_ciudadano($ciud_numero_documento, $ciud_nombre, $ciud_primer_apellido, $ciud_segundo_apellido,$ciud_direccion,$foto, $fechanac,$sexo, $tipe_id_val, $tido_id)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "INSERT INTO public.tb_ciudadano (
                ciud_numero_documento,
                ciud_nombre,
                ciud_primer_apellido,
                ciud_segundo_apellido,
                ciud_created_at,
                ciud_estado,
                ciud_domicilio_real,
                ciud_foto,
                ciud_sexo,
                ciud_fecha_nac,
                tipe_id,
                tido_id
            ) VALUES (
                ?,
                ?,
                ?,
                ?,
                NOW(),
                'A',
                ?,
                ?,?,?,
                ?,
                ?
            )";
        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $ciud_numero_documento);
        $stmt->bindValue(2, strtoupper($ciud_nombre));
        $stmt->bindValue(3, strtoupper($ciud_primer_apellido));
        $stmt->bindValue(4, strtoupper($ciud_segundo_apellido));
        $stmt->bindValue(5, strtoupper($ciud_direccion));
        $fotoCarnet = '';
        if($foto !==''){
            $fotoCarnet = "data:image/png;base64," . $foto;
        }
        $stmt->bindValue(6, $fotoCarnet);
        $stmt->bindValue(7, $sexo);
        $stmt->bindValue(8, $fechanac, PDO::PARAM_NULL);
        $stmt->bindValue(9, $tipe_id_val);
        $stmt->bindValue(10, $tido_id);
        $stmt->execute();
        $ciudadano_id = $conectar->lastInsertId();
        return $ciudadano_id;
    }
    public function actualizar_sexo_fecha_nac($ciud_numero_documento, $ciud_sexo, $ciud_fecha_nac)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "UPDATE public.tb_ciudadano SET ciud_sexo = ?, ciud_fecha_nac = ? WHERE ciud_numero_documento = ?";
        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $ciud_sexo);
        $stmt->bindValue(2, $ciud_fecha_nac);
        $stmt->bindValue(3, $ciud_numero_documento);
        $stmt->execute();
        // No es necesario fetchAll() después de una consulta de actualización
        // No hay datos para recuperar aquí
    }
    public function getlastId() {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT ciud_id
            FROM public.tb_ciudadano 
            ORDER BY ciud_id DESC 
            LIMIT 1";
        $stmt = $conectar->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            return $result['ciud_id'];
        } else {
            return null; // Si no hay resultados, retorna null
        }
    }
    public function actualizarDir($id_ciud, $direccion_nueva) {
        if($id_ciud ===  null){
            $id_ciud = $this->getlastId();
        }
        // Conexión a la base de datos
        $conectar = parent::conexion();
        parent::set_names();

        // Obtener la dirección actual
        $sql = "SELECT ciud_domicilio_real FROM public.tb_ciudadano WHERE ciud_id = ?";
        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $id_ciud);
        $stmt->execute();
        $direccion = $stmt->fetch(PDO::FETCH_ASSOC);

        // Actualizar solo si la dirección ha cambiado
        if ($direccion['ciud_domicilio_real'] != $direccion_nueva) {
            // Actualizar la dirección
            $sql = "UPDATE public.tb_ciudadano SET ciud_domicilio_real = ? WHERE ciud_id = ?";
            $stmt = $conectar->prepare($sql);
            $stmt->bindValue(1, strtoupper($direccion_nueva));
            $stmt->bindValue(2, $id_ciud);
            $stmt->execute();
            
        }

        return $resultado = $stmt->fetchAll();
    }
}
