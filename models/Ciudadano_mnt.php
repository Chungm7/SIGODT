<?php


class Ciudadano extends Conectar
{

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
    // En models/Ciudadano.php
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
            // cinco parámetros para los cinco criterios
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
    // En models/Ciudadano.php
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

        // Convertir a mayúsculas
        $numero    = $d['ciud_numero_documento'];
        $apellido1 = mb_strtoupper($d['ciud_primer_apellido'], 'UTF-8');
        $apellido2 = mb_strtoupper($d['ciud_segundo_apellido'], 'UTF-8');
        $nombre    = mb_strtoupper($d['ciud_nombre'],           'UTF-8');
        // Si la foto viene vacía o no está definida, pasar null
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

        // Al usar execute con array, PDO detecta null y lo inserta como NULL en la BD
        return $stmt->execute([
            $numero,
            $apellido1,
            $apellido2,
            $nombre,
            $foto,     // puede ser string o null
            $fecha,
            $sexo,
            $tido
        ]);
    }

    public function editar($ciud_id, $d)
    {
        $conectar = parent::conexion();
        parent::set_names();

        // Convertir a mayúsculas
        $numero    = $d['ciud_numero_documento'];
        $apellido1 = mb_strtoupper($d['ciud_primer_apellido'], 'UTF-8');
        $apellido2 = mb_strtoupper($d['ciud_segundo_apellido'], 'UTF-8');
        $nombre    = mb_strtoupper($d['ciud_nombre'],           'UTF-8');
        // Foto nullable
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
            $foto,      // null si no hay foto
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
