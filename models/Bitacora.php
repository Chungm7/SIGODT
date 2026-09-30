<?php
class Bitacora extends Conectar
{
    /**
     * Actualiza el pers_id del registro de auditoría más reciente generado por triggers,
     * protegiendo contra sobreescrituras en concurrencia mediante filtro de pers_id huérfano.
     */
    public function update_bitacora($pers_id)
    {
        $conectar = parent::conexion();
        parent::set_names();

        // Buscar el último bita_id que esté pendiente de usuario (pers_id nulo o cero)
        $sqlFind = "SELECT MAX(bita_id) AS bita_id FROM sc_seguridad.tb_bitacora WHERE pers_id IS NULL OR pers_id = 0;";
        $stmtFind = $conectar->prepare($sqlFind);
        $stmtFind->execute();
        $row = $stmtFind->fetch(PDO::FETCH_ASSOC);
        $bita_id = $row['bita_id'] ?? null;

        if (!empty($bita_id)) {
            // Actualizar solo el registro pendiente, garantizando no pisar la auditoría de otro operador
            $sql = "UPDATE sc_seguridad.tb_bitacora SET pers_id = ? WHERE bita_id = ? AND (pers_id IS NULL OR pers_id = 0);";
            $sql = $conectar->prepare($sql);
            $sql->bindValue(1, $pers_id);
            $sql->bindValue(2, $bita_id);
            $sql->execute();
            return $sql->fetchAll(PDO::FETCH_ASSOC);
        }

        return [];
    }

    /**
     * Actualiza un rango de registros de auditoría asociados a operaciones por lote
     */
    public function update_bitacora_grupo($pers_id, $menor)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $bita_id = $this->get_max_id()[0]["bita_id"] ?? 0;

        // Actualizar únicamente los registros del rango que aún no tengan usuario asignado
        $sql = "UPDATE sc_seguridad.tb_bitacora SET pers_id = ? WHERE bita_id >= ? AND bita_id <= ? AND (pers_id IS NULL OR pers_id = 0);";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $pers_id);
        $sql->bindValue(2, $menor);
        $sql->bindValue(3, $bita_id);
        $sql->execute();
        return $sql->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna el ID más alto registrado en la bitácora
     */
    public function get_max_id()
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT COALESCE(MAX(bita_id), 0) AS bita_id FROM sc_seguridad.tb_bitacora;";
        $sql = $conectar->prepare($sql);
        $sql->execute();
        return $sql->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>