<?php


class Empresa extends Conectar
{

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

    public function list_empresa($search = "", $start = 0, $length = 10, $order_column = "0", $order_dir = "desc",$estado)
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
            // ahora 5 placeholders → array_fill con 5
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
            // aquí sí son 4 placeholders
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

    // En models/Empresa.php

    public function insertar($d)
    {
        $conectar = parent::conexion();
        parent::set_names();

        // Convertir a mayúsculas los campos de texto
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

        // Convertir a mayúsculas los campos de texto
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
