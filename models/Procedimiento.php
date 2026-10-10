<?php
class Procedimiento extends Conectar
{

    public function insert_proced($proced_tupa, $proced_area, $proced_cod, $proced_nom, $proced_campo, $proced_administradotipo, $proced_tipoindvasc)
    {
        if (!is_scalar($proced_tupa) || filter_var($proced_tupa, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
            return false;
        }
        $conectar = parent::conexion();
        parent::set_names();
        // Check the stored lifecycle in the insert itself, never a posted state.
        $sql = "INSERT INTO sc_giros.tm_procedimiento(proced_tupa, proced_area, proced_cod, proced_nom, fechacrea, est, proced_tipocampo, proced_administradotipo, proced_tipoindvasc)
                SELECT tupa_id, ?, ?, ?, NOW(), '1', ?, ?, ?
                FROM sc_giros.tm_tupa
                WHERE tupa_id = ? AND est IN (2,3);";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $proced_area);
        $sql->bindValue(2, $proced_cod);
        $sql->bindValue(3, $proced_nom);
        $sql->bindValue(4, $proced_campo);
        $sql->bindValue(5, $proced_administradotipo);
        $sql->bindValue(6, $proced_tipoindvasc);
        $sql->bindValue(7, $proced_tupa, PDO::PARAM_INT);
        return $sql->execute() && $sql->rowCount() === 1;
    }




    public static function registration_id($value)
    {
        return (is_int($value) || is_string($value))
            && filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) !== false;
    }

    // Shared only by new-trámite selectors, detail and the guarded insertion.
    private function registration_source()
    {
        return " FROM sc_giros.tm_procedimiento p
            INNER JOIN tb_dependencia a ON a.depe_id = p.proced_area
            INNER JOIN sc_giros.tm_tupa d ON d.tupa_id = p.proced_tupa
            WHERE a.depe_estado = 'A' AND a.nior_id != 5
              AND a.depe_id NOT IN (2,200) AND p.est = 1
              AND d.est = 2 AND d.tipo_doc IN ('TUPA','TUSNE')
              AND (:role = 9 OR EXISTS (
                  SELECT 1 FROM sc_giros.td_areausu tau
                  WHERE tau.depe_id = a.depe_id AND tau.est = 1 AND tau.pers_id = :person))";
    }

    public function get_registration_options($person, $role, $area = null, $procedure = null, $document = null)
    {
        if ($procedure !== null && $area === null) return [];
        foreach ([$person, $role] as $id) {
            if (!self::registration_id($id)) return [];
        }
        foreach ([$area, $procedure, $document] as $id) {
            if ($id !== null && !self::registration_id($id)) return [];
        }
        $conectar = parent::conexion();
        parent::set_names();
        $sql = 'SELECT DISTINCT p.*, a.depe_id, a.depe_denominacion' . $this->registration_source();
        $bindings = [':person' => $person, ':role' => $role];
        foreach ([':area' => [$area, 'p.proced_area'], ':procedure' => [$procedure, 'p.proced_id'], ':document' => [$document, 'p.proced_tupa']] as $key => $filter) {
            if ($filter[0] !== null) {
                $sql .= " AND {$filter[1]} = $key";
                $bindings[$key] = $filter[0];
            }
        }
        $stmt = $conectar->prepare($sql . ' ORDER BY p.proced_nom');
        foreach ($bindings as $key => $value) $stmt->bindValue($key, $value, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 🔹 Método para crear un procedimiento ciudadano
    public function crearProcedCiud($ciud_id, $proced_id, $empr_id = null, $usu_crea, $empresa_ruc = null, $empresa_razon_social = null, $role = null, $area = null)
    {
        $conectar = parent::conexion();
        parent::set_names();

        foreach ([$ciud_id, $proced_id, $usu_crea, $role, $area] as $id) {
            if (!self::registration_id($id)) return false;
        }
        $sql = "INSERT INTO sc_giros.td_procedciudadano
                    (ciud_id, proced_id, empr_id, usu_crea, empresa_ruc, empresa_razon_social)
                SELECT :citizen, p.proced_id, :company, :creator, :ruc, :name"
                . $this->registration_source()
                . " AND p.proced_id = :procedure AND p.proced_area = :area
                RETURNING procedciudadano_id";

        $stmt = $conectar->prepare($sql);
        foreach ([':citizen' => $ciud_id, ':procedure' => $proced_id, ':creator' => $usu_crea,
                  ':person' => $usu_crea, ':role' => $role, ':area' => $area] as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        }
        $stmt->bindValue(':company', $empr_id, $empr_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':ruc', $empresa_ruc, $empresa_ruc === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':name', $empresa_razon_social, $empresa_razon_social === null ? PDO::PARAM_NULL : PDO::PARAM_STR);

        $stmt->execute();

        // Obtener el ID insertado
        return $stmt->fetchColumn();
    }

    // 🔹 Método para actualizar el código del procedimiento ciudadano
    public function actualizarCod($proced_id, $procedciudadanoID)
    {
        $conectar = parent::conexion();
        parent::set_names();

        $sql = "UPDATE sc_giros.td_procedciudadano
                SET procedciudadano_cod = (
                    SELECT proced_cod FROM sc_giros.tm_procedimiento WHERE proced_id = ?
                ) || '_' || ?
                WHERE procedciudadano_id = ?
                RETURNING procedciudadano_cod"; // 🔹 Retorna el código generado

        $stmt = $conectar->prepare($sql);
        $stmt->bindParam(1, $proced_id, PDO::PARAM_INT);
        $stmt->bindParam(2, $procedciudadanoID, PDO::PARAM_INT);
        $stmt->bindParam(3, $procedciudadanoID, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return $stmt->fetchColumn(); // 🔹 Retorna el nuevo código si el update fue exitoso
        }
        return false;
    }


    // 🔹 Método para insertar tasas asociadas al trámite del ciudadano
    public function updateTasas($proced_id, $procedciudadanoID)
    {
        $conectar = parent::conexion();
        parent::set_names();

        $sql = "INSERT INTO sc_giros.td_tasatciud (
                    tasatciud_tasaproced,
                    tasatciud_procedciud,
                    est
                )
                SELECT tt.tasaproced_id, ?, 1
                FROM sc_giros.td_tasaproced tt
                WHERE tt.proced_id = ? AND tt.est = 1
                RETURNING tasatciud_id"; // 🔹 Retorna IDs de las tasas insertadas

        $stmt = $conectar->prepare($sql);
        $stmt->bindParam(1, $procedciudadanoID, PDO::PARAM_INT);
        $stmt->bindParam(2, $proced_id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return $stmt->fetchAll(PDO::FETCH_COLUMN); // 🔹 Retorna IDs de tasas insertadas
        }
        return false;
    }



    public function actualizarprocedciudadano($procedciudadano_id, $ciud_id, $empr_id)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "UPDATE sc_giros.td_procedciudadano
            SET ciud_id = ?,
            empr_id= ?
            WHERE procedciudadano_id=?;";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $ciud_id, PDO::PARAM_INT);
        $sql->bindValue(2, $empr_id !== '' ? $empr_id : null, PDO::PARAM_INT);
        $sql->bindValue(3, $procedciudadano_id);

        $sql->execute();
        return $resultado = $sql->fetchAll();
    }

    public function update_proced($proced_id, $proced_cod, $proced_nom, $proced_campo, $proced_administradotipo, $proced_tipoindvasc)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "UPDATE sc_giros.tm_procedimiento
                SET
                proced_cod = ?,
                proced_nom = ?,
                proced_tipocampo = ?,
                proced_administradotipo = ?,
                proced_tipoindvasc = ?
                WHERE
                proced_id = ?";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $proced_cod);
        $sql->bindValue(2, $proced_nom);
        $sql->bindValue(3, $proced_campo);
        $sql->bindValue(4, $proced_administradotipo);
        $sql->bindValue(5, $proced_tipoindvasc);
        $sql->bindValue(6, $proced_id);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }


    public function delete_proced($proced_id)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "UPDATE sc_giros.tm_procedimiento
                SET
                    est = 0
                WHERE
                    proced_id = ?";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $proced_id);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }
    public function delete_proced_ciudadano($ciudadano, $orden_giro)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "CALL sc_giros.eliminar_proced_ciudadano(?,?);";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $ciudadano);
        $sql->bindValue(2, $orden_giro);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }
    public function cambiar_procedencia_proced_ciudadano($ciudadano)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "CALL sc_giros.cambiar_procedencia_proced_ciudadano(?);";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $ciudadano);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }

    public function get_proced_area_tupa($area_id, $tupa_id)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT t.proced_id, t.proced_cod, t.proced_nom, t.fechacrea, a.depe_denominacion, t.proced_tipocampo, t.proced_administradotipo, t.proced_tipoindvasc 
            FROM sc_giros.tm_procedimiento t
            INNER JOIN tb_dependencia a ON t.proced_area = a.depe_id
            WHERE t.est = 1 and t.proced_area = ? and t.proced_tupa = ? order by proced_nom asc ;";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $area_id);
        $sql->bindValue(2, $tupa_id);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }
    public function get_proced_area_tupa_tusne($area_id)
    {
        $conectar = parent::conexion();
        parent::set_names();

        $sql = "
            SELECT 
                p.proced_id,
                p.proced_cod,
                p.proced_nom,
                p.fechacrea,
                a.depe_denominacion,
                p.proced_tipocampo,
                p.proced_administradotipo,
                p.proced_tipoindvasc
            FROM sc_giros.tm_procedimiento p
            INNER JOIN tb_dependencia a 
                ON p.proced_area = a.depe_id
            WHERE 
                p.est = 1
                AND p.proced_area = :area_id
                AND p.proced_tupa IN (
                    SELECT tupa_id
                    FROM sc_giros.tm_tupa
                    WHERE est = 2
                    AND tipo_doc IN ('TUPA','TUSNE')
                )
            ORDER BY p.proced_nom ASC;
        ";

        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(':area_id', $area_id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listar_proceds($area_id, $tupa_id)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT t.proced_id, t.proced_cod, t.proced_nom, t.fechacrea, a.depe_denominacion, t.proced_tipocampo, t.proced_administradotipo, t.proced_tipoindvasc
            FROM sc_giros.tm_procedimiento t
            INNER JOIN tb_dependencia a ON t.proced_area = a.depe_id
            WHERE t.est = 1 and t.proced_area = ? and t.proced_tupa = ?;";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $area_id);
        $sql->bindValue(2, $tupa_id);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }
    public function get_tipoProc($procedimiento_id)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT proced_tipoindvasc 
            FROM sc_giros.tm_procedimiento 
            WHERE proced_id = ?;";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $procedimiento_id);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }
    public function get_proces_by_id($procedimiento_id)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT pro.proced_id, pro.proced_cod, pro.proced_nom, pro.fechacrea, pro.est, pro.proced_area, 
            pro.proced_tupa, pro.proced_tipocampo, pro.proced_administradotipo, pro.proced_tipoindvasc, tde.depe_denominacion
            FROM sc_giros.tm_procedimiento pro
            inner join tb_dependencia tde on tde.depe_id = pro.proced_area
            WHERE proced_id = ?";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $procedimiento_id);
        $sql->execute();

        // Devuelve un array asociativo
        return $sql->fetchAll(PDO::FETCH_ASSOC);
    }

    public function get_proced()
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT t.proced_id, t.proced_cod, t.proced_nom, t.fechacrea, a.area_nom
            FROM sc_giros.tm_procedimiento t
            INNER JOIN sc_giros.tm_area a ON t.proced_area = a.area_id
            WHERE t.est = 1";
        $sql = $conectar->prepare($sql);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }
    public function get_proced_cantTasas($proced_id)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT COUNT(tt.tasaproced_id) AS cantidad
            FROM sc_giros.td_tasaproced tt
            WHERE tt.proced_id = ? AND tt.est = 1;";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $proced_id);
        $sql->execute();
        return $resultado = $sql->fetchColumn();
    }
    public static function operational_filters($input)
    {
        if (!is_array($input)) throw new InvalidArgumentException('Filtros inválidos.');
        $result = [];
        foreach (['draw' => ['sEcho', 0, 0, 1000000000], 'start' => ['iDisplayStart', 0, 0, 1000000000], 'length' => ['iDisplayLength', 10, 1, 100]] as $key => $rule) {
            $value = $input[$key] ?? $input[$rule[0]] ?? $rule[1];
            if ((!is_int($value) && !is_string($value)) ||
                filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => $rule[2], 'max_range' => $rule[3]]]) === false) {
                throw new InvalidArgumentException('Paginación inválida.');
            }
            $result[$key] = (int)$value;
        }
        $state = $input['state'] ?? 'en_curso';
        if (!is_string($state) || !in_array($state, ['en_curso', 'todos', '0', '1', '3', '4', '5', '6', '9'], true)) {
            throw new InvalidArgumentException('Estado inválido.');
        }
        $result['state'] = $state;
        if (isset($input['search']) && !is_array($input['search'])) throw new InvalidArgumentException('Búsqueda inválida.');
        $search = isset($input['search']) && array_key_exists('value', $input['search']) ? $input['search']['value'] : ($input['sSearch'] ?? '');
        if (!is_string($search) || strlen($search) > 200 || preg_match('//u', $search) !== 1) {
            throw new InvalidArgumentException('Búsqueda inválida.');
        }
        $result['search'] = trim($search) === '' ? '' : '%' . strtr(trim($search), ['!' => '!!', '%' => '!%', '_' => '!_']) . '%';
        foreach (['date_from', 'date_to'] as $key) {
            $value = $input[$key] ?? '';
            if (!is_string($value)) throw new InvalidArgumentException('Fecha inválida.');
            if ($value !== '' && (substr($value, 0, 4) === '0000' || !preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/D', $value))) throw new InvalidArgumentException('Fecha inválida.');
            $date = $value === '' ? null : DateTime::createFromFormat('!Y-m-d', $value);
            if ($value !== '' && (!$date || $date->format('Y-m-d') !== $value)) throw new InvalidArgumentException('Fecha inválida.');
            $result[$key] = $value === '' ? null : $value;
        }
        if ($result['date_from'] !== null && $result['date_to'] !== null && $result['date_from'] > $result['date_to']) {
            throw new InvalidArgumentException('El rango de fechas no es válido.');
        }
        return $result;
    }

    public function get_operational_list($procedure, $person, $role, $area, $filters)
    {
        $empty = ['recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []];
        foreach ([$procedure, $person, $role] as $id) {
            if (!self::registration_id($id)) return $empty;
        }
        if ($area !== null && !self::registration_id($area)) return $empty;
        // Access is historical: assignment matters, current catalog lifecycle does not.
        // Retained cancelled links belong to history, not to current pending eligibility.
        $sql = <<<'SQL'
WITH params AS (
    SELECT CAST(:procedure AS integer) AS procedure, CAST(:person AS integer) AS person,
           CAST(:role AS integer) AS role, CAST(:area AS integer) AS area,
           CAST(:state AS text) AS state, CAST(:search AS text) AS search,
           CAST(:date_from AS date) AS date_from, CAST(:date_to AS date) AS date_to,
           CAST(:size AS integer) AS page_size, CAST(:offset AS integer) AS page_offset
), authorized AS (
    SELECT tc.*, c.ciud_numero_documento::text AS citizen_document,
           CONCAT_WS(' ', c.ciud_nombre, c.ciud_primer_apellido, c.ciud_segundo_apellido) AS citizen_name,
           COALESCE(NULLIF(BTRIM(tc.empresa_ruc::text), ''), NULLIF(BTRIM(e.empr_ruc::text), '')) AS company_document,
           COALESCE(NULLIF(BTRIM(tc.empresa_razon_social), ''), NULLIF(BTRIM(e.empr_razon_social), ''),
                    NULLIF(BTRIM(e.empr_nombre_comercial), '')) AS company_name,
           CONCAT_WS(' ', tc.empresa_razon_social, e.empr_razon_social, e.empr_nombre_comercial) AS company_search,
           CONCAT_WS(' ', tc.empresa_ruc::text, e.empr_ruc::text) AS company_document_search
    FROM sc_giros.td_procedciudadano tc
    JOIN sc_giros.tm_procedimiento p ON p.proced_id = tc.proced_id
    JOIN public.tb_ciudadano c ON c.ciud_id = tc.ciud_id
    LEFT JOIN public.tb_empresa e ON e.empr_id = tc.empr_id
    CROSS JOIN params q
    WHERE p.proced_id = q.procedure AND (q.area IS NULL OR p.proced_area = q.area)
      AND (q.role = 9 OR EXISTS (
          SELECT 1 FROM sc_giros.td_areausu tau
          WHERE tau.depe_id = p.proced_area AND tau.est = 1 AND tau.pers_id = q.person
      ))
), fees AS (
    SELECT ttc.* FROM sc_giros.td_tasatciud ttc
    JOIN authorized a ON a.procedciudadano_id = ttc.tasatciud_procedciud
), progress AS (
    SELECT tasatciud_procedciud AS procedure_id,
           COUNT(*) FILTER (WHERE est <> 0)::integer AS total,
           COUNT(*) FILTER (WHERE est IN (2,4,5))::integer AS pagadas
    FROM fees GROUP BY tasatciud_procedciud
), order_rows AS (
    SELECT DISTINCT a.procedciudadano_id, og.ogciud_id::text AS ogciud_id, og.est::integer AS est,
           'order'::text AS row_kind,
           'order:' || a.procedciudadano_id || ':' || og.ogciud_id AS row_id
    FROM authorized a
    JOIN fees f ON f.tasatciud_procedciud = a.procedciudadano_id
    JOIN sc_giros.td_giro_tasa_ciudadano gt ON gt.tasaciud_id = f.tasatciud_id
    JOIN sc_giros.td_ordengirociud og ON og.ogciud_id = gt.girot_giro
), procedure_candidates AS (
    SELECT a.procedciudadano_id, a.est,
           (a.est IN (1,2) AND (
               EXISTS (
                   SELECT 1 FROM fees f
                   WHERE f.tasatciud_procedciud = a.procedciudadano_id AND f.est IN (1,3)
                     AND NOT EXISTS (
                         SELECT 1 FROM sc_giros.td_giro_tasa_ciudadano valid_link
                         JOIN sc_giros.td_ordengirociud valid_order ON valid_order.ogciud_id = valid_link.girot_giro
                         WHERE valid_link.tasaciud_id = f.tasatciud_id AND valid_link.est = 1
                           AND valid_order.est NOT IN (0,3,6)
                     )
               ) OR (a.est = 1 AND NOT EXISTS (
                   SELECT 1 FROM fees f WHERE f.tasatciud_procedciud = a.procedciudadano_id
               ))
           )) AS pending
    FROM authorized a
), procedure_rows AS (
    SELECT c.procedciudadano_id, NULL::text AS ogciud_id,
           CASE WHEN c.pending THEN 9 ELSE c.est END::integer AS est,
           CASE WHEN c.pending THEN 'pending' ELSE 'procedure' END AS row_kind,
           CASE WHEN c.pending THEN 'pending:' ELSE 'procedure:' END || c.procedciudadano_id AS row_id
    FROM procedure_candidates c
    WHERE c.pending OR NOT EXISTS (
        SELECT 1 FROM order_rows o WHERE o.procedciudadano_id = c.procedciudadano_id
    )
), rows AS (
    SELECT * FROM order_rows
    UNION ALL
    SELECT * FROM procedure_rows
), listing AS (
    SELECT r.*, a.procedciudadano_cod, a.fechacrea,
           COALESCE(a.company_document, a.citizen_document) AS ciudadano_dni,
           COALESCE(a.company_name, a.citizen_name) AS nombre_completo,
           a.citizen_document, a.citizen_name, a.company_search, a.company_document_search,
           COALESCE(pr.total, 0) AS total, COALESCE(pr.pagadas, 0) AS pagadas,
           CASE WHEN r.row_kind = 'pending' THEN 9
                WHEN r.row_kind = 'order' AND r.est IN (0,1,3,4,5,6) THEN r.est
                WHEN r.row_kind = 'procedure' AND r.est IN (0,3,5,6) THEN r.est
                ELSE NULL END AS filter_state
    FROM rows r JOIN authorized a USING (procedciudadano_id)
    LEFT JOIN progress pr ON pr.procedure_id = r.procedciudadano_id
), filtered AS (
    SELECT l.* FROM listing l CROSS JOIN params q
    WHERE (q.state = 'todos' OR (q.state = 'en_curso' AND l.filter_state IN (1,9))
           OR q.state = l.filter_state::text)
      AND (q.date_from IS NULL OR l.fechacrea >= q.date_from)
      AND (q.date_to IS NULL OR l.fechacrea < q.date_to + INTERVAL '1 day')
      AND (q.search = '' OR l.ogciud_id ILIKE q.search ESCAPE '!'
           OR l.procedciudadano_cod ILIKE q.search ESCAPE '!'
           OR l.citizen_document ILIKE q.search ESCAPE '!' OR l.citizen_name ILIKE q.search ESCAPE '!'
           OR l.company_document_search ILIKE q.search ESCAPE '!' OR l.company_search ILIKE q.search ESCAPE '!')
), counts AS (
    SELECT (SELECT COUNT(*) FROM listing) AS records_total,
           (SELECT COUNT(*) FROM filtered) AS records_filtered
), page AS (
    SELECT * FROM filtered ORDER BY fechacrea DESC NULLS LAST, row_id
    LIMIT (SELECT page_size FROM params) OFFSET (SELECT page_offset FROM params)
)
SELECT counts.*, page.* FROM counts LEFT JOIN page ON TRUE
ORDER BY page.fechacrea DESC NULLS LAST, page.row_id
SQL;
        $conectar = parent::conexion();
        parent::set_names();
        $stmt = $conectar->prepare($sql);
        foreach ([':procedure' => $procedure, ':person' => $person, ':role' => $role, ':size' => $filters['length'], ':offset' => $filters['start']] as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        }
        $stmt->bindValue(':area', $area, $area === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        foreach (['state', 'search', 'date_from', 'date_to'] as $key) {
            $value = $filters[$key];
            $stmt->bindValue(':' . $key, $value, $value === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!$rows) return $empty;
        $result = ['recordsTotal' => (int)$rows[0]['records_total'], 'recordsFiltered' => (int)$rows[0]['records_filtered'], 'data' => []];
        foreach ($rows as $row) {
            if ($row['row_id'] === null) continue;
            unset($row['records_total'], $row['records_filtered']);
            $result['data'][] = $row;
        }
        return $result;
    }
    public function get_procedciudadano_id_admin($proced_id)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT * FROM sc_giros.get_proced_info_admin(?);";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $proced_id);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }


    public function get_procedempresaid($proced_id)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT * FROM sc_giros.get_procedempresa_info(?);";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $proced_id);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }
    public function get_procedciudadano_idprocedciudadano($procedciudadano_id)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = 'SELECT empr.empr_ruc, empr.empr_razon_social, empr.empr_nombre_comercial,tc.procedciudadano_id,tbc.ciud_domicilio_real, tbc.ciud_id, tbc.ciud_nombre, tbc.ciud_primer_apellido, tbc.ciud_segundo_apellido, tbc.ciud_numero_documento, tt.proced_tipoindvasc, vh.vehi_placa, tbc.ciud_sexo,tbc.ciud_fecha_nac
            FROM sc_giros.td_procedciudadano tc
            INNER JOIN public.tb_ciudadano tbc ON tc.ciud_id = tbc.ciud_id
			inner join sc_giros.tm_procedimiento tt ON tc.proced_id = tt.proced_id
			left join sc_transito_transporte."td_TIV" tiv ON tc.tiv_id = tiv.tiv_id
			left join sc_transito_transporte.td_vehiculo vh ON tiv.tiv_vehiculo= vh.vehi_id
			left join public.tb_empresa empr ON empr.empr_id= tc.empr_id
            WHERE procedciudadano_id = ?';
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $procedciudadano_id);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }
    public function get_proced_ciudadano_x_idciudadano($ciudadano_id)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT * FROM sc_giros.get_procedciudadano_info(?) order by fechacrea DESC";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $ciudadano_id);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }
    public function get_Areas()
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT * from sc_giros.tm_area
                WHERE est = 1;";
        $sql = $conectar->prepare($sql);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }
    public function get_Areas_usu($usu_id)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT * from sc_giros.td_areausu tau
            inner join tb_dependencia ta on tau.depe_id = ta.depe_id
                            WHERE tau.est = 1 and tau.pers_id = ?;";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $usu_id);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }
    public function get_todas_areas()
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT DISTINCT ta.depe_id, ta.depe_denominacion 
            FROM sc_giros.tm_procedimiento tau
            INNER JOIN tb_dependencia ta ON tau.proced_area = ta.depe_id;";
        $sql = $conectar->prepare($sql);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }
    public function get_proced_id($proced_id)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT * FROM sc_giros.tm_procedimiento WHERE est = 1 AND proced_id = ?";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $proced_id);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }
    public function get_ciudadano($ciudadano)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT c.ciudadano_nombre,
            c.ciudadano_apep,
            c.ciudadano_apem,
            c.ciudadano_dni
            FROM sc_giros.td_procedciudadano tc
            inner join sc_giros.tm_ciudadano c on procedciudadano_ciudadano = c.ciudadano_id
            where tc.ciudadano = ?";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $ciudadano);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }

    public function delete_proced_tasa($tasaproced_id)
    {
        $conectar = parent::conexion();
        parent::set_names();

        try {

            $sql = "CALL sc_giros.eliminartasaproced(?)";
            $stmt = $conectar->prepare($sql);
            $stmt->bindParam(1, $tasaproced_id, PDO::PARAM_INT);
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
            return false;
        }
    }



    public function insert_proced_tasa($proced_id, $tasa_id)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "INSERT INTO sc_giros.td_tasaproced(tasa_id, proced_id, est) VALUES (?, ?, 1) RETURNING tasaproced_id";
        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $tasa_id);
        $stmt->bindValue(2, $proced_id);
        $stmt->execute();


        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado;
    }

    public function update_cod_ref()
    {
        $conectar = parent::conexion();
        parent::set_names();

        // Actualizamos el campo cod_ref si cumple las condiciones
        $updateSql = "UPDATE sc_giros.td_tasaproced
                  SET cod_ref = CONCAT('TP', p.proced_tupa, '|', p.proced_area, '|', sc_giros.td_tasaproced.proced_id, '|', sc_giros.td_tasaproced.tasa_id)
                  FROM sc_giros.tm_procedimiento p
                  WHERE sc_giros.td_tasaproced.proced_id = p.proced_id
                  AND sc_giros.td_tasaproced.est = 1
                  AND sc_giros.td_tasaproced.cod_ref IS NULL";
        $updateStmt = $conectar->prepare($updateSql);
        $updateStmt->execute();
    }
    public function update_tasaproced($tasaproced_id, $tasaproced_pos, $tasaproced_monto, $cod_ref, $desc_tasa, $is_multiplica)
    {
        try {
            $conectar = parent::conexion();
            parent::set_names();

            // Llamamos al procedimiento almacenado con los parámetros actualizados
            $sql = "CALL sc_giros.actualizarposmonto(?, ?, ?, ?, ?, ?)";
            $stmt = $conectar->prepare($sql);
            $stmt->bindValue(1, $tasaproced_id, PDO::PARAM_INT);
            $stmt->bindValue(2, $tasaproced_pos, PDO::PARAM_INT);
            $stmt->bindValue(3, $tasaproced_monto, PDO::PARAM_STR);
            $stmt->bindValue(4, $cod_ref, PDO::PARAM_STR); // cod_ref como texto
            $stmt->bindValue(5, $desc_tasa, PDO::PARAM_STR);
            $stmt->bindValue(6, $is_multiplica, PDO::PARAM_INT); // El valor booleano 0 o 1
            $stmt->execute();

            // Si todo sale bien, retornamos un mensaje de éxito
            $response = ['success' => true];
        } catch (PDOException $e) {
            $mensajeError = $e->getMessage();

            // Ahora validamos si el mensaje contiene el mensaje deseado y lo mostramos en ese caso
            if (strpos($mensajeError, 'La posición ya existe para este trámite') !== false) {
                echo "Error: " . 'La posición ya existe para este trámite';
            } else {
                echo "Error: " . $mensajeError;
            }
            $response = ['success' => false, 'error' => $mensajeError];
        }
        echo json_encode($response);
    }


    public function get_tasaproced_id($tasaproced_id)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT td_tasaproced.tasaproced_id,
                   tm_tasa.tasa_nom,
                   td_tasaproced.desc_tasa,
                   tm_procedimiento.proced_nom,
                   td_tasaproced.tasaproced_pos,
                   td_tasaproced.tasaproced_monto,
                   td_tasaproced.cod_ref,
                   td_tasaproced.is_multiplica    -- Asegúrate de seleccionar el valor de is_multiplica
            FROM sc_giros.td_tasaproced 
            inner join sc_giros.tm_tasa on td_tasaproced.tasa_id=tm_tasa.tasa_id 
            inner join sc_giros.tm_procedimiento on td_tasaproced.proced_id=tm_procedimiento.proced_id 
            WHERE td_tasaproced.est = 1 
            and td_tasaproced.tasaproced_id =?";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(1, $tasaproced_id);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }

    public function get_data_proced_ciud($proceciudadano_id)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT pc.*, pr.*, ciud.ciud_numero_documento, ciud.ciud_primer_apellido, ciud.ciud_segundo_apellido, 
                           ciud.ciud_nombre, ciud.ciud_foto, ciud.ciud_fecha_nac, ciud.ciud_sexo, 
                           emp.empr_ruc, emp.empr_razon_social
                    FROM sc_giros.td_procedciudadano pc 
                    INNER JOIN sc_giros.tm_procedimiento pr ON pr.proced_id = pc.proced_id
                    INNER JOIN tb_ciudadano ciud ON ciud.ciud_id = pc.ciud_id
                    LEFT JOIN tb_empresa emp ON emp.empr_id = pc.empr_id 
                    WHERE pc.procedciudadano_id = ?";
        $stmt = $conectar->prepare($sql);
        $stmt->bindValue(1, $proceciudadano_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function get_procedimiento_detalle($proced_id, $registration = null)
    {
        $conectar = parent::conexion();
        parent::set_names();

        // 1. Obtener datos del procedimiento
        $sql1 = "SELECT proced_id, proced_cod, proced_nom, fechacrea, est, proced_area, proced_tupa, proced_tipocampo, proced_administradotipo, proced_tipoindvasc
             FROM sc_giros.tm_procedimiento
             WHERE proced_id = ?";
        if ($registration !== null) {
            $rows = $this->get_registration_options($registration['person'], $registration['role'], $registration['area'], $proced_id);
            $procedimiento = $rows[0] ?? false;
            if (!$procedimiento) return ['procedimiento' => false, 'tasas' => []];
        } else {
            $stmt1 = $conectar->prepare($sql1);
            $stmt1->bindValue(1, $proced_id);
            $stmt1->execute();
            $procedimiento = $stmt1->fetch(PDO::FETCH_ASSOC);
        }

        // 2. Obtener tasas asociadas al procedimiento
        $sql2 = "SELECT t.tasaproced_id, t.tasa_id, t.proced_id, t.tasaproced_pos, t.tasaproced_monto, t.est, t.desc_tasa, t.cod_ref, t.tasa_partida, t.is_multiplica,
                ta.tasa_nom, t.is_multiplica
             FROM sc_giros.td_tasaproced t
             INNER JOIN sc_giros.tm_tasa ta ON t.tasa_id = ta.tasa_id
             WHERE t.proced_id = ?";
        $stmt2 = $conectar->prepare($sql2);
        $stmt2->bindValue(1, $proced_id);
        $stmt2->execute();
        $tasas = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        return [
            "procedimiento" => $procedimiento,
            "tasas" => $tasas
        ];
    }
}
