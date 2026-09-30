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

    public function listarCiudadanos()
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT 
                    c.ciud_id, 
                    c.ciud_razon_social, 
                    c.tido_id,
                    td.tido_descripcion
                FROM public.tb_ciudadano c
                INNER JOIN public.tb_tipo_documento td 
                    ON c.tido_id = td.tido_id
                WHERE c.ciud_estado = 'A'
                ORDER BY c.ciud_razon_social ASC";
        $stmt = $conectar->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function mostrar($ciud_id)
    {
        $conectar = parent::conexion();
        parent::set_names();

        $sql = "SELECT
                ciud_id,
                ciud_numero_documento,
                ciud_primer_apellido,
                ciud_segundo_apellido,
                ciud_nombre,
                ciud_foto,
                ciud_fecha_nac,
                ciud_sexo,
                tido_id
            FROM public.tb_ciudadano
            WHERE ciud_id = ?";
        $stmt = $conectar->prepare($sql);
        $stmt->execute([$ciud_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function list_ciudadano($search = "", $start = 0, $length = 10, $order_column = "0", $order_dir = "desc")
    {
        $conectar = parent::conexion();
        parent::set_names();

        $column_map = [
            0 => "c.ciud_id",
            1 => "td.tido_descripcion",
            2 => "c.ciud_numero_documento",
            3 => "c.ciud_primer_apellido",
            4 => "c.ciud_segundo_apellido",
            5 => "c.ciud_nombre",
            6 => "c.ciud_estado"
        ];
        $order_col = $column_map[intval($order_column)] ?? "c.ciud_id";
        $order_dir = strtolower($order_dir) === "asc" ? "ASC" : "DESC";

        $sql = "SELECT
                    c.ciud_id,
                    c.ciud_numero_documento,
                    c.ciud_primer_apellido,
                    c.ciud_segundo_apellido,
                    c.ciud_nombre,
                    c.tido_id,
                    td.tido_descripcion AS tipo_documento,
                    c.ciud_celular,
                    c.ciud_estado
                FROM public.tb_ciudadano c
                INNER JOIN public.tb_tipo_documento td 
                    ON c.tido_id = td.tido_id
                WHERE c.ciud_estado <> 'E'";

        $params = [];
        if ($search !== "") {
            $sql .= " AND (
                c.ciud_numero_documento ILIKE ? OR
                c.ciud_primer_apellido    ILIKE ? OR
                c.ciud_segundo_apellido   ILIKE ? OR
                c.ciud_nombre             ILIKE ? OR
                (c.ciud_primer_apellido || ' ' || c.ciud_segundo_apellido || ' ' || c.ciud_nombre) ILIKE ?
            )";
            $like = "%{$search}%";
            $params = array_fill(0, 5, $like);
        }

        $sql .= " ORDER BY {$order_col} {$order_dir}
                  LIMIT ? OFFSET ?";
        $params[] = intval($length);
        $params[] = intval($start);

        $stmt = $conectar->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function get_total_ciudadano($search = "")
    {
        $conectar = parent::conexion();
        parent::set_names();

        $sql = "SELECT COUNT(*) AS total
                FROM public.tb_ciudadano
                WHERE ciud_estado <> 'E'";
        $params = [];
        if ($search !== "") {
            $sql .= " AND (
                ciud_numero_documento ILIKE ? OR
                ciud_primer_apellido    ILIKE ? OR
                ciud_segundo_apellido   ILIKE ? OR
                ciud_nombre             ILIKE ?
            )";
            $params = array_fill(0, 4, "%{$search}%");
        }

        $stmt = $conectar->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetch(PDO::FETCH_ASSOC)["total"];
    }

    public function get_tito_doc()
    {
        $conectar = parent::conexion();
        parent::set_names();

        $sql = "SELECT 
                tido_id, 
                tido_descripcion, 
                tido_estado, 
                tido_detalle, 
                tido_created_at, 
                tido_update_at
            FROM public.tb_tipo_documento
            WHERE tido_estado = 'A'
            ORDER BY tido_descripcion";
        $stmt = $conectar->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function existeDocumento($numero, $tido_id)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "
            SELECT COUNT(*) AS total
            FROM public.tb_ciudadano
            WHERE ciud_numero_documento = ?
              AND tido_id               = ?
              AND ciud_estado <> 'E'
        ";
        $stmt = $conectar->prepare($sql);
        $stmt->execute([$numero, $tido_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'] > 0;
    }

    public function insertar($d)
    {
        $conectar = parent::conexion();
        parent::set_names();

        $numero    = $d['ciud_numero_documento'];
        $apellido1 = mb_strtoupper($d['ciud_primer_apellido'], 'UTF-8');
        $apellido2 = mb_strtoupper($d['ciud_segundo_apellido'], 'UTF-8');
        $nombre    = mb_strtoupper($d['ciud_nombre'],           'UTF-8');
        $foto = (isset($d['ciud_foto']) && $d['ciud_foto'] !== '')
            ? $d['ciud_foto']
            : null;
        $fecha     = $d['ciud_fecha_nac'];
        $sexo      = $d['ciud_sexo'];
        $tido      = $d['tido_id'];

        $sql = "INSERT INTO public.tb_ciudadano (
                ciud_numero_documento,
                ciud_primer_apellido,
                ciud_segundo_apellido,
                ciud_nombre,
                ciud_foto,
                ciud_fecha_nac,
                ciud_sexo,
                tido_id,
                ciud_estado,
                ciud_created_at,
                tipe_id
            ) VALUES (
                ?,?,?,?,?,?,?,?,'A', now(), 1
            )";
        $stmt = $conectar->prepare($sql);

        return $stmt->execute([
            $numero,
            $apellido1,
            $apellido2,
            $nombre,
            $foto,
            $fecha,
            $sexo,
            $tido
        ]);
    }

    public function editar($ciud_id, $d)
    {
        $conectar = parent::conexion();
        parent::set_names();

        $numero    = $d['ciud_numero_documento'];
        $apellido1 = mb_strtoupper($d['ciud_primer_apellido'], 'UTF-8');
        $apellido2 = mb_strtoupper($d['ciud_segundo_apellido'], 'UTF-8');
        $nombre    = mb_strtoupper($d['ciud_nombre'],           'UTF-8');
        $foto = (isset($d['ciud_foto']) && $d['ciud_foto'] !== '')
            ? $d['ciud_foto']
            : null;
        $fecha     = $d['ciud_fecha_nac'];
        $sexo      = $d['ciud_sexo'];
        $tido      = $d['tido_id'];

        $sql = "UPDATE public.tb_ciudadano SET
                ciud_numero_documento = ?,
                ciud_primer_apellido  = ?,
                ciud_segundo_apellido = ?,
                ciud_nombre           = ?,
                ciud_foto             = ?,
                ciud_fecha_nac        = ?,
                ciud_sexo             = ?,
                tido_id               = ?,
                ciud_updated_at       = now()
            WHERE ciud_id = ?";
        $stmt = $conectar->prepare($sql);

        return $stmt->execute([
            $numero,
            $apellido1,
            $apellido2,
            $nombre,
            $foto,
            $fecha,
            $sexo,
            $tido,
            $ciud_id
        ]);
    }

    public function inactivar($ciud_id)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "UPDATE public.tb_ciudadano
                SET ciud_estado = 'E',
                    ciud_updated_at = now()
                WHERE ciud_id = ?";
        $stmt = $conectar->prepare($sql);
        return $stmt->execute([$ciud_id]);
    }
}

if (!class_exists('Ciudadano', false)) {
    class_alias('ciudadano', 'Ciudadano');
}
