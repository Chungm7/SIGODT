<?php
class tupa extends Conectar
{

    public function insert_tupa($tupa_nom, $tupa_año, $tipo_doc)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "INSERT INTO sc_giros.tm_tupa(tupa_nom, tupa_año, est, tipo_doc) VALUES (?, ?, 1, ?);";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $tupa_nom);
        $sql->bindValue(2, $tupa_año);
        $sql->bindValue(3, $tipo_doc);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }

    public function update_tupa($tupa_id, $tupa_nom, $tupa_año, $tipo_doc)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "UPDATE sc_giros.tm_tupa
                SET
                    tupa_nom = ?,
                    tupa_año = ?,
                    tipo_doc = ?
                WHERE
                    tupa_id = ?";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $tupa_nom);
        $sql->bindValue(2, $tupa_año);
        $sql->bindValue(3, $tipo_doc);
        $sql->bindValue(4, $tupa_id);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }

    public function delete_tupa($tupa_id)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "UPDATE sc_giros.tm_tupa
                SET
                    est = 0
                WHERE
                    tupa_id = ?";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $tupa_id);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }
    public function bloquear_tupa($tupa_id)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "UPDATE sc_giros.tm_tupa
                SET
                    tupa_block = '1'
                WHERE
                    tupa_id = ?";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $tupa_id);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }
    public function  duplicar_tupa($tupa_id)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT sc_giros.duplicar_tupa(p_tupa_id := ?);";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $tupa_id);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }

    public function desbloquear_tupa($tupa_id)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "UPDATE sc_giros.tm_tupa
                SET
                tupa_block = '0'
                WHERE
                    tupa_id = ?";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $tupa_id);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }
    public function activar_tupa($tupa_id)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "UPDATE sc_giros.tm_tupa
                SET
                    est = 2
                WHERE
                    tupa_id = ?";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $tupa_id);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }
    public function desactivar_tupa($tupa_id)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "UPDATE sc_giros.tm_tupa
                SET
                    est = 1
                WHERE
                    tupa_id = ?";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $tupa_id);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }
    public function get_tupa_tusnet()
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT tupa_id, tupa_año, est, tupa_nom, tupa_block, tipo_doc FROM sc_giros.tm_tupa WHERE est IN (1,2) ORDER BY tupa_id DESC";
        $sql = $conectar->prepare($sql);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }
    public function get_tupa()
    {
        $conectar = parent::conexion();
        parent::set_names();

        // Ajustamos la consulta para que recupere tanto TUPA como TUSNE
        $sql = "SELECT tupa_id, tupa_año, est, tupa_nom, tupa_block, tipo_doc 
            FROM sc_giros.tm_tupa 
            WHERE est IN (1, 2) 
            AND tipo_doc IN ('TUPA', 'TUSNE')  -- Aseguramos que tanto TUPA como TUSNE se recuperen
            ORDER BY tupa_id DESC"; 

        $sql = $conectar->prepare($sql);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }

    public function get_docs()
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT tupa_id, tupa_año, est, tupa_nom, tupa_block, tipo_doc FROM sc_giros.tm_tupa WHERE est IN (1,2) ORDER BY tupa_id DESC";
        $sql = $conectar->prepare($sql);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }
    public function get_tupa_usu()
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT * FROM sc_giros.tm_tupa WHERE est=2";
        $sql = $conectar->prepare($sql);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }

    public function get_tupa_id($tupa_id)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT * FROM sc_giros.tm_tupa WHERE est IN (1,2) AND tupa_id = ?";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $tupa_id);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }
}
