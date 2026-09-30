<?php
class empresa extends conectar
{

    public function get_empresa_x_RUC($empr_ruc)
    {
        $conectar = parent::conexion();
        parent::set_names();

        $sql = "SELECT
                MIN(empr_id) AS empr_id,
                empr_razon_social,
                empr_nombre_comercial,
                ARRAY_AGG(empr_direccion) AS direcciones
            FROM public.tb_empresa
            WHERE empr_ruc = ?
            GROUP BY empr_razon_social, empr_nombre_comercial
            ORDER BY empr_razon_social
        ";

        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $empr_ruc);
        $sql->execute();

        return $sql->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDirecciones($empr_ruc)
    {
        $conectar = parent::conexion();
        parent::set_names();

        $sql = "SELECT
                empr_id,
                empr_direccion
            FROM public.tb_empresa
            WHERE empr_ruc = ?";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $empr_ruc);
        $sql->execute();

        return $sql->fetchAll(PDO::FETCH_ASSOC);
    }


    public function get_cantidad_grupo($procedempresa)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT count(grupoproced_id)
            FROM sc_giros.td_grupoproced_vehiculos where grupoproced_procedempresa = ?";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $procedempresa);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }

    public function get_cantidad_grupo_ciud($procedempresa)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT count(grupoproced_id)
            FROM sc_giros.td_grupoproced_ciud where grupoproced_procedempresa = ?";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $procedempresa);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }
    public function actualizarDir($empr_id, $direccion_nueva)
    {
        // Conexión a la base de datos
        $conectar = parent::conexion();
        parent::set_names();

        // Obtener la dirección actual
        $sql = "SELECT empr_direccion FROM public.tb_empresa where empr_id=?";
        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $empr_id);
        $stmt->execute();
        $direccion = $stmt->fetch(PDO::FETCH_ASSOC);

        // Actualizar solo si la dirección ha cambiado
        if ($direccion['empr_direccion'] != $direccion_nueva) {
            // Actualizar la dirección
            $sql = "UPDATE public.tb_empresa SET empr_direccion= ? WHERE empr_id = ?";
            $stmt = $conectar->prepare($sql);
            $stmt->bindValue(1, $direccion_nueva);
            $stmt->bindValue(2, $empr_id);
            $stmt->execute();
        }

        return $resultado = $stmt->fetchAll();
    }
    public function registrarEmpresa($empr_categoria, $empr_ruc, $empr_razon_social, $empr_nombre_comercial, $empr_direccion, $empr_telefono, $empr_area_establecimiento, $empr_correo, $empr_estado, $gico_id)
    {
        $conectar = parent::conexion();
        parent::set_names();

        $sql = "INSERT INTO public.tb_empresa(
                empr_categoria, empr_ruc, empr_razon_social, empr_nombre_comercial,
                empr_direccion, empr_telefono, empr_correo, empr_area_establecimiento,
                empr_estado, gico_id
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            RETURNING empr_id;";

        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $empr_categoria);
        $stmt->bindValue(2, $empr_ruc);
        $stmt->bindValue(3, $empr_razon_social);
        $stmt->bindValue(4, $empr_nombre_comercial);
        $stmt->bindValue(5, $empr_direccion);
        $stmt->bindValue(6, $empr_telefono);
        $stmt->bindValue(7, $empr_correo);
        $stmt->bindValue(8, $empr_area_establecimiento);
        $stmt->bindValue(9, $empr_estado);
        $stmt->bindValue(10, $gico_id, PDO::PARAM_STR);

        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado['empr_id'] ?? null;
    }

    public function getlastId()
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT empr_id
                FROM public.tb_empresa
                ORDER BY empr_id DESC 
                LIMIT 1";
        $stmt = $conectar->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            return $result['empr_id'];
        } else {
            return null; // Si no hay resultados, retorna null
        }
    }
    public function registrarGiro($gico_nombre)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "INSERT INTO public.tb_giro_comercial(gico_nombre)
                VALUES (?);";
        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $gico_nombre);
        $stmt->execute();
        return $resultado = $stmt->fetchAll();
    }
    public function getIdGiro($giro_nombre)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT gico_id 
            FROM public.tb_giro_comercial  
            WHERE gico_nombre = ? limit 1";
        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $giro_nombre, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            return $result['gico_id'];
        } else {
            return null; // Si no hay resultados, retorna null
        }
    }
    public function get_direcciones($empr_ruc)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT empr_id, empr_direccion
            FROM public.tb_empresa  
            WHERE empr_ruc = ?";
        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $empr_ruc, PDO::PARAM_STR);
        $stmt->execute();
        return $result = $stmt->fetchAll();
    }

    public function listarEmpresas()
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT empr_id, empr_razon_social
                FROM public.tb_empresa
                WHERE empr_estado = 'A'
                ORDER BY empr_razon_social ASC";
        $stmt = $conectar->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function list_empresa($search = "", $start = 0, $length = 10, $order_column = "0", $order_dir = "desc", $estado = "A")
    {
        $conectar = parent::conexion();
        parent::set_names();

        $column_map = [
            0 => "empr_id",
            1 => "empr_ruc",
            2 => "empr_razon_social",
            3 => "empr_nombre_comercial",
            4 => "empr_direccion",
            5 => "empr_estado"
        ];
        $order_col = $column_map[intval($order_column)] ?? "empr_id";
        $order_dir = strtolower($order_dir) === "asc" ? "ASC" : "DESC";

        $sql = "SELECT
                empr_id,
                empr_ruc,
                empr_razon_social,
                empr_nombre_comercial,
                empr_estado,
                empr_direccion
            FROM public.tb_empresa
            WHERE empr_estado = ?";

        $params = [$estado];
        if ($search !== "") {
            $sql .= " AND (
            empr_ruc ILIKE ? OR
            empr_razon_social ILIKE ? OR
            empr_direccion ILIKE ? OR
            empr_nombre_comercial ILIKE ? OR
            (empr_razon_social || ' ' || empr_nombre_comercial) ILIKE ?
        )";
            $like = "%{$search}%";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $sql .= " ORDER BY {$order_col} {$order_dir}
              LIMIT ? OFFSET ?";
        $params[] = intval($length);
        $params[] = intval($start);

        $stmt = $conectar->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function get_total_empresa($search = "")
    {
        $conectar = parent::conexion();
        parent::set_names();

        $sql = "SELECT COUNT(*) AS total
            FROM public.tb_empresa
            WHERE empr_estado <> 'I'";
        $params = [];
        if ($search !== "") {
            $sql .= " AND (
            empr_ruc ILIKE ? OR
            empr_razon_social ILIKE ? OR
            empr_nombre_comercial ILIKE ? OR
            (empr_razon_social || ' ' || empr_nombre_comercial) ILIKE ?
        )";
            $params = array_fill(0, 4, "%{$search}%");
        }

        $stmt = $conectar->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetch(PDO::FETCH_ASSOC)["total"];
    }

    public function existeRuc($ruc)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT COUNT(*) AS total
                FROM public.tb_empresa
                WHERE empr_ruc = ?
                  AND empr_estado <> 'I'";
        $stmt = $conectar->prepare($sql);
        $stmt->execute([$ruc]);
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'] > 0;
    }

    public function insertar($d)
    {
        $conectar = parent::conexion();
        parent::set_names();

        $ruc       = $d['empr_ruc'];
        $razon     = mb_strtoupper($d['empr_razon_social'],      'UTF-8');
        $comercial = mb_strtoupper($d['empr_nombre_comercial'],  'UTF-8');
        $direccion = mb_strtoupper($d['empr_direccion'],         'UTF-8');

        $sql = "INSERT INTO public.tb_empresa (
                empr_categoria,
                empr_ruc,
                empr_razon_social,
                empr_nombre_comercial,
                empr_direccion,
                empr_estado,
                empr_created_at,
                gico_id,
                empr_area_establecimiento
            ) VALUES (
                'I', ?,?,?,?,'A', now(), '{}', 0
            )";
        $stmt = $conectar->prepare($sql);
        return $stmt->execute([
            $ruc,
            $razon,
            $comercial,
            $direccion
        ]);
    }

    public function editar($empr_id, $d)
    {
        $conectar = parent::conexion();
        parent::set_names();

        $ruc       = $d['empr_ruc'];
        $razon     = mb_strtoupper($d['empr_razon_social'],      'UTF-8');
        $comercial = mb_strtoupper($d['empr_nombre_comercial'],  'UTF-8');
        $direccion = mb_strtoupper($d['empr_direccion'],         'UTF-8');

        $sql = "UPDATE public.tb_empresa SET
                empr_ruc                = ?,
                empr_razon_social       = ?,
                empr_nombre_comercial   = ?,
                empr_direccion          = ?,
                empr_updated_at         = now()
            WHERE empr_id = ?";
        $stmt = $conectar->prepare($sql);
        return $stmt->execute([
            $ruc,
            $razon,
            $comercial,
            $direccion,
            $empr_id
        ]);
    }

    public function inactivar($empr_id)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "UPDATE public.tb_empresa
                SET empr_estado    = 'I',
                    empr_updated_at = now()
                WHERE empr_id = ?";
        $stmt = $conectar->prepare($sql);
        return $stmt->execute([$empr_id]);
    }

    public function mostrar($empr_id)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT
                    empr_id,
                    empr_categoria,
                    empr_ruc,
                    empr_razon_social,
                    empr_nombre_comercial,
                    gico_id,
                    empr_tipo_actividad,
                    empr_direccion,
                    empr_telefono,
                    empr_correo,
                    empr_area_establecimiento,
                    ubig_id,
                    empr_sunat_estado,
                    empr_sunat_condicion
                FROM public.tb_empresa
                WHERE empr_id = ?";
        $stmt = $conectar->prepare($sql);
        $stmt->execute([$empr_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

if (!class_exists('Empresa', false)) {
    class_alias('empresa', 'Empresa');
}
