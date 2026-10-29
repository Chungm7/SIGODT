<?php
class Ordengiro extends conectar
{
    /** Typed owner membership, deliberately independent of all state/date filters. */
    private function nombre_association_sql($filterClause = '', $preCte = '')
    {
        $with = $preCte !== '' ? "WITH $preCte\nsource AS (" : "WITH source AS (";
        $areaFilter = '(:role = 9 OR EXISTS (SELECT 1 FROM sc_giros.td_areausu tau WHERE tau.depe_id = p.proced_area AND tau.est = 1 AND tau.pers_id = :person))';
        $where = $filterClause !== '' ? "    WHERE ($filterClause) AND $areaFilter\n" : "    WHERE $areaFilter\n";
        return <<<SQL
$with
    SELECT og.ogciud_id, tt.ciud_id, tt.empr_id, tt.procedciudadano_id,
           BTRIM(CONCAT_WS(' ', c.ciud_nombre, c.ciud_primer_apellido, c.ciud_segundo_apellido)) AS ciudadano,
           c.ciud_numero_documento::text AS documento,
           COALESCE(NULLIF(BTRIM(e.empr_ruc::text), ''), NULLIF(BTRIM(tt.empresa_ruc::text), '')) AS ruc,
           NULLIF(BTRIM(e.empr_razon_social), '') AS razon,
           NULLIF(BTRIM(e.empr_nombre_comercial), '') AS comercial,
           NULLIF(BTRIM(tt.empresa_razon_social), '') AS historica,
           NULLIF(BTRIM(tt.empresa_ruc::text), '') AS ruc_historico
    FROM sc_giros.td_ordengirociud og
    JOIN sc_giros.td_giro_tasa_ciudadano gt ON gt.girot_giro = og.ogciud_id
    JOIN sc_giros.td_tasatciud ttc ON gt.tasaciud_id = ttc.tasatciud_id
    JOIN sc_giros.td_procedciudadano tt ON ttc.tasatciud_procedciud = tt.procedciudadano_id
    JOIN sc_giros.tm_procedimiento p ON p.proced_id = tt.proced_id
    JOIN public.tb_ciudadano c ON c.ciud_id = tt.ciud_id
    LEFT JOIN public.tb_empresa e ON e.empr_id = tt.empr_id
$where), entities AS (
    SELECT 'ciudadano'::text AS entity_type, ciud_id::text AS entity_key,
           ogciud_id, COALESCE(NULLIF(ciudadano, ''), 'Ciudadano sin nombre') AS nombre,
           documento
    FROM source
    UNION ALL
    SELECT 'empresa',
           CASE WHEN ruc IS NOT NULL THEN 'ruc:' || ruc
                WHEN empr_id IS NOT NULL THEN 'id:' || empr_id::text
                ELSE 'sin:' || procedciudadano_id::text END,
           ogciud_id, COALESCE(alias.nombre, razon, comercial, historica, 'Empresa sin razón social'),
           COALESCE(ruc, CASE WHEN empr_id IS NOT NULL THEN 'Sin RUC · ID ' || empr_id::text
                             ELSE 'Sin identificar · trámite ' || procedciudadano_id::text END)
    FROM source
    CROSS JOIN LATERAL (VALUES (razon), (comercial), (historica)) AS alias(nombre)
    WHERE empr_id IS NOT NULL OR ruc_historico IS NOT NULL OR historica IS NOT NULL
), pairs AS (
    SELECT DISTINCT entity_type, entity_key, ogciud_id FROM entities
)
SQL;
    }

    public static function validar_nombre_input($operation, $input)
    {
        if (!is_array($input)) {
            throw new InvalidArgumentException('Parámetros inválidos.');
        }
        $page = filter_var($input['page'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1000000000]]);
        $limit = filter_var($input['limit'] ?? 10, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 100]]);
        if ($page === false || $limit === false) {
            throw new InvalidArgumentException('Paginación inválida.');
        }
        if ($operation === 'buscar_entidades_nombre') {
            $search = $input['search'] ?? null;
            if (!is_string($search) || trim($search) === '' || strlen($search) > 200 || preg_match('//u', $search) !== 1) {
                throw new InvalidArgumentException('Ingrese un nombre válido (máximo 200 bytes).');
            }
            return [trim($search), $page, $limit];
        }
        $type = $input['entity_type'] ?? null;
        $key = $input['entity_key'] ?? null;
        if (!is_string($key) || strlen($key) > 200 || preg_match('//u', $key) !== 1 ||
            !(($type === 'ciudadano' && preg_match('/^[1-9][0-9]*$/D', $key)) ||
              ($type === 'empresa' && (preg_match('/^(id|sin):[1-9][0-9]*$/D', $key) ||
               (strpos($key, 'ruc:') === 0 && trim(substr($key, 4)) !== '' && substr($key, 4) === trim(substr($key, 4))))))) {
            throw new InvalidArgumentException('Entidad inválida.');
        }
        return [$type, $key, $page, $limit];
    }

    /** One statement/snapshot supplies total, bounded page and rows, even for empty pages. */
    private function nombre_page($cte, $selection, $sort, $bindings, $page, $limit, $idColumn)
    {
        $sql = $cte . ", selected AS ($selection),
            settings AS (SELECT CAST(:page AS bigint) AS requested, CAST(:limit AS integer) AS size),
            totals AS (SELECT COUNT(*) AS total FROM selected),
            bounds AS (SELECT total, size,
                GREATEST(1, LEAST(requested, GREATEST(1, (total + size - 1) / size))) AS page
                FROM totals CROSS JOIN settings),
            paged AS (SELECT * FROM selected ORDER BY $sort
                LIMIT (SELECT size FROM bounds) OFFSET (SELECT (page - 1) * size FROM bounds))
            SELECT bounds.total, bounds.page, paged.* FROM bounds LEFT JOIN paged ON TRUE
            ORDER BY $sort";
        $query = parent::conexion()->prepare($sql);
        foreach ($bindings as $key => $value) {
            $query->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $query->bindValue(':page', $page, PDO::PARAM_INT);
        $query->bindValue(':limit', $limit, PDO::PARAM_INT);
        $query->execute();
        $rows = $query->fetchAll(PDO::FETCH_ASSOC);
        $result = ['data' => [], 'total' => (int)$rows[0]['total'], 'page' => (int)$rows[0]['page'], 'limit' => $limit];
        foreach ($rows as $row) {
            if ($row[$idColumn] === null) { continue; }
            unset($row['total'], $row['page']);
            $result['data'][] = $row;
        }
        return $result;
    }

    public function buscar_entidades_nombre($search, $page = 1, $limit = 10, $pers_id = 0, $rol_id = 0)
    {
        list($search, $page, $limit) = self::validar_nombre_input('buscar_entidades_nombre', compact('search', 'page', 'limit'));
        $preCte = <<<'SQL'
matched_ciud AS (
    SELECT c.ciud_id
    FROM public.tb_ciudadano c
    WHERE (c.ciud_nombre || ' ' || c.ciud_primer_apellido || ' ' || COALESCE(c.ciud_segundo_apellido, '')) ILIKE :search ESCAPE '!'
), matched_empr_ids AS (
    SELECT empr_id
    FROM public.tb_empresa
    WHERE empr_razon_social ILIKE :search ESCAPE '!' OR empr_nombre_comercial ILIKE :search ESCAPE '!'
), matched_proced_ids AS (
    SELECT procedciudadano_id
    FROM sc_giros.td_procedciudadano
    WHERE empresa_razon_social ILIKE :search ESCAPE '!'
    UNION
    SELECT procedciudadano_id
    FROM sc_giros.td_procedciudadano
    WHERE empr_id IN (SELECT empr_id FROM matched_empr_ids)
),
SQL;
        $filter = 'tt.ciud_id IN (SELECT ciud_id FROM matched_ciud) OR tt.procedciudadano_id IN (SELECT procedciudadano_id FROM matched_proced_ids)';
        $cte = $this->nombre_association_sql($filter, $preCte) . <<<'SQL'
, matched AS (
    SELECT DISTINCT entity_type, entity_key FROM entities WHERE nombre ILIKE :search ESCAPE '!'
), labels AS (
    SELECT entity_type, entity_key, MIN(nombre) AS nombre, MIN(documento) AS documento
    FROM entities GROUP BY entity_type, entity_key
)
SQL;
        $selection = 'SELECT p.entity_type, p.entity_key, l.nombre, l.documento, COUNT(*) AS ordenes
            FROM pairs p JOIN matched m USING (entity_type, entity_key)
            JOIN labels l USING (entity_type, entity_key)
            GROUP BY p.entity_type, p.entity_key, l.nombre, l.documento';
        $literal = '%' . strtr($search, ['!' => '!!', '%' => '!%', '_' => '!_']) . '%';
        $bindings = [':search' => $literal, ':person' => (int)$pers_id, ':role' => (int)$rol_id];
        return $this->nombre_page($cte, $selection, 'nombre, entity_type, entity_key', $bindings, $page, $limit, 'entity_key');
    }

    public function historial_entidad($entity_type, $entity_key, $page = 1, $limit = 10, $pers_id = 0, $rol_id = 0)
    {
        list($entity_type, $entity_key, $page, $limit) = self::validar_nombre_input('historial_entidad', compact('entity_type', 'entity_key', 'page', 'limit'));
        if ($entity_type === 'ciudadano') {
            $filter = 'tt.ciud_id = :key_filter';
            $bindings = [':type' => $entity_type, ':key' => $entity_key, ':key_filter' => (int)$entity_key];
        } else {
            if (strpos($entity_key, 'ruc:') === 0) {
                $filter = "COALESCE(NULLIF(BTRIM(e.empr_ruc::text), ''), NULLIF(BTRIM(tt.empresa_ruc::text), '')) = :key_filter";
                $bindings = [':type' => $entity_type, ':key' => $entity_key, ':key_filter' => substr($entity_key, 4)];
            } elseif (strpos($entity_key, 'id:') === 0) {
                $filter = 'tt.empr_id = :key_filter';
                $bindings = [':type' => $entity_type, ':key' => $entity_key, ':key_filter' => (int)substr($entity_key, 3)];
            } else {
                $filter = 'tt.procedciudadano_id = :key_filter';
                $bindings = [':type' => $entity_type, ':key' => $entity_key, ':key_filter' => (int)substr($entity_key, 4)];
            }
        }
        $cte = $this->nombre_association_sql($filter) . <<<'SQL'
, rate_rows AS (
    SELECT DISTINCT girot_id, girot_giro, importe FROM sc_giros.td_giro_tasa_ciudadano
    WHERE girot_giro IN (SELECT ogciud_id FROM pairs)
), amounts AS (
    SELECT girot_giro, SUM(importe) AS importe FROM rate_rows GROUP BY girot_giro
)
SQL;
        $selection = "SELECT og.ogciud_id, og.fechacrea, og.est AS orden_est, og.recibo_nro,
            TO_CHAR(og.fechacrea, 'YYYY-MM-DD') AS fecha,
            TO_CHAR(og.fechacrea, 'HH24:MI:SS') AS hora, COALESCE(a.importe, 0) AS importe
            FROM pairs p JOIN sc_giros.td_ordengirociud og ON og.ogciud_id = p.ogciud_id
            LEFT JOIN amounts a ON a.girot_giro = og.ogciud_id
            WHERE p.entity_type = :type AND p.entity_key = :key";
        $bindings[':person'] = (int)$pers_id;
        $bindings[':role'] = (int)$rol_id;
        // The same sort is used inside and outside paged; no ambiguous table aliases there.
        return $this->nombre_page($cte, $selection, 'fechacrea DESC NULLS LAST, ogciud_id DESC',
            $bindings, $page, $limit, 'ogciud_id');
    }

    public function get_orden_id($orden_id, $pers_id = 0, $rol_id = 0)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = 'SELECT 
                        og.ogciud_id,
                        TO_CHAR(og.fechacrea, \'YYYY-MM-DD\') AS fecha,
                        TO_CHAR(og.fechacrea, \'HH12:MI:SS AM\') AS hora,
                        CONCAT(tu.pers_nombre, \' \', tu.pers_apelpat, \' \', tu.pers_apelmat) AS nombre_completo,
                        CONCAT(c.ciud_nombre, \' \', c.ciud_primer_apellido, \' \', c.ciud_segundo_apellido) AS nombre_ciudadano,
                        c.ciud_numero_documento AS ciudadano_dni,
                        gt.importe,
                        p.proced_nom,
                        ta.depe_denominacion AS area_nom,
                        ts.tasa_nom,
                        p.proced_tipoindvasc,
                        empr.empr_ruc,
                        empr.empr_razon_social,
                        empr.empr_direccion,
                        tm.tupa_nom as proced_tupa,
                        og.ogciud_comentario,
                        c.ciud_domicilio_real,
                        c.tido_id
                    FROM 
                        sc_giros.td_ordengirociud og
                    INNER JOIN 
                        sc_escalafon.tb_persona tu ON og.pers_id = tu.pers_id
                    INNER JOIN 
                        sc_giros.td_giro_tasa_ciudadano gt ON gt.girot_giro = og.ogciud_id
                    INNER JOIN 
                        sc_giros.td_tasatciud ttc ON gt.tasaciud_id = ttc.tasatciud_id
                    INNER JOIN 
                        sc_giros.td_procedciudadano tt ON ttc.tasatciud_procedciud = tt.procedciudadano_id
                    INNER JOIN 
                        public.tb_ciudadano c ON c.ciud_id = tt.ciud_id
                    INNER JOIN 
                        sc_giros.tm_procedimiento p ON tt.proced_id = p.proced_id
                    INNER JOIN 
                        sc_giros.td_tasaproced tst ON ttc.tasatciud_tasaproced = tst.tasaproced_id
                    INNER JOIN 
                        sc_giros.tm_tasa ts ON tst.tasa_id = ts.tasa_id
                     INNER JOIN 
                        tb_dependencia ta ON p.proced_area = ta.depe_id
                    INNER JOIN
                        sc_giros.tm_tupa tm ON p.proced_tupa = tm.tupa_id
                    LEFT JOIN 
                        public.tb_empresa empr ON empr.empr_id = tt.empr_id
                    WHERE 
                        og.ogciud_id = :orden_id AND og.est = 1
                        AND (:role = 9 OR EXISTS (SELECT 1 FROM sc_giros.td_areausu tau WHERE tau.depe_id = p.proced_area AND tau.est = 1 AND tau.pers_id = :person))';
        $sql = $conectar->prepare($sql);
        $sql->bindValue(':orden_id', $orden_id);
        $sql->bindValue(':person', (int)$pers_id, PDO::PARAM_INT);
        $sql->bindValue(':role', (int)$rol_id, PDO::PARAM_INT);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }
    public function get_ordenes($pers_id = 0, $rol_id = 0)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = 'SELECT DISTINCT og.* FROM sc_giros.td_ordengirociud og INNER JOIN sc_giros.td_giro_tasa_ciudadano gt ON gt.girot_giro = og.ogciud_id INNER JOIN sc_giros.td_tasatciud ttc ON gt.tasaciud_id = ttc.tasatciud_id INNER JOIN sc_giros.td_procedciudadano tt ON ttc.tasatciud_procedciud = tt.procedciudadano_id INNER JOIN sc_giros.tm_procedimiento p ON tt.proced_id = p.proced_id WHERE (:role = 9 OR EXISTS (SELECT 1 FROM sc_giros.td_areausu tau WHERE tau.depe_id = p.proced_area AND tau.est = 1 AND tau.pers_id = :person))';
        $sql = $conectar->prepare($sql);
        $sql->bindValue(':person', (int)$pers_id, PDO::PARAM_INT);
        $sql->bindValue(':role', (int)$rol_id, PDO::PARAM_INT);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }
    public function get_tasas_ordenes($pers_id = 0, $rol_id = 0)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = 'SELECT tt.tasatciud_id, tt.tasatciud_tasaproced, tt.est as estadotasat, tt.tasatciud_procedciud, gtc.girot_id, gtc.fechacrea, gtc.tasaciud_id, gtc.cantidad, gtc.importe, gtc.est as estadotasagirada, gtc.girot_giro
            FROM sc_giros.td_tasatciud tt
            INNER JOIN sc_giros.td_procedciudadano pc ON tt.tasatciud_procedciud = pc.procedciudadano_id
            INNER JOIN sc_giros.tm_procedimiento p ON pc.proced_id = p.proced_id
            LEFT JOIN sc_giros.td_giro_tasa_ciudadano gtc ON gtc.tasaciud_id = tt.tasatciud_id
            WHERE (:role = 9 OR EXISTS (SELECT 1 FROM sc_giros.td_areausu tau WHERE tau.depe_id = p.proced_area AND tau.est = 1 AND tau.pers_id = :person))
            ORDER BY tt.tasatciud_id ASC;';
        $sql = $conectar->prepare($sql);
        $sql->bindValue(':person', (int)$pers_id, PDO::PARAM_INT);
        $sql->bindValue(':role', (int)$rol_id, PDO::PARAM_INT);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }

    public function get_tasas_semana($pers_id = 0, $rol_id = 0)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = 'SELECT 
            tt.tasatciud_id, 
            tt.tasatciud_tasaproced, 
            tt.est AS estadotasat, 
            tt.tasatciud_procedciud, 
            gtc.girot_id, 
            gtc.fechacrea, 
            gtc.tasaciud_id, 
            gtc.cantidad, 
            gtc.importe, 
            gtc.est AS estadotasagirada, 
            gtc.girot_giro
        FROM 
            sc_giros.td_tasatciud tt
        LEFT JOIN 
            sc_giros.td_giro_tasa_ciudadano gtc ON gtc.tasaciud_id = tt.tasatciud_id
        INNER JOIN sc_giros.td_procedciudadano pc ON tt.tasatciud_procedciud = pc.procedciudadano_id
        INNER JOIN sc_giros.tm_procedimiento p ON pc.proced_id = p.proced_id
        WHERE
            ((EXTRACT(WEEK FROM gtc.fechacrea) = EXTRACT(WEEK FROM CURRENT_DATE) AND EXTRACT(YEAR FROM gtc.fechacrea) = EXTRACT(YEAR FROM CURRENT_DATE))
            OR gtc.fechacrea IS NULL)
            AND (:role = 9 OR EXISTS (SELECT 1 FROM sc_giros.td_areausu tau WHERE tau.depe_id = p.proced_area AND tau.est = 1 AND tau.pers_id = :person))
        ORDER BY
            tt.tasatciud_id ASC;';
        $sql = $conectar->prepare($sql);
        $sql->bindValue(':person', (int)$pers_id, PDO::PARAM_INT);
        $sql->bindValue(':role', (int)$rol_id, PDO::PARAM_INT);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }
    public function get_ordenes_Usuario($pers_id = 0, $rol_id = 0)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT DISTINCT  oc.ogciud_id,
        oc.pers_id,
        oc.fechacrea,
        ta.depe_denominacion as area_nom,
        ta.depe_id as area_id,
        CONCAT(sp.pers_nombre, ' ', sp.pers_apelpat, ' ', sp.pers_apelmat) AS nombrecompleto
        FROM sc_giros.td_ordengirociud oc
        INNER JOIN sc_escalafon.tb_persona sp ON oc.pers_id = sp.pers_id
		INNER JOIN 
                sc_giros.td_giro_tasa_ciudadano gt ON gt.girot_giro = oc.ogciud_id
		INNER JOIN 
		sc_giros.td_tasatciud ttc ON ttc.tasatciud_id= gt.tasaciud_id
		INNER JOIN 
		sc_giros.td_tasaproced tp ON ttc.tasatciud_tasaproced = tp.tasaproced_id
		INNER JOIN 
        sc_giros.tm_procedimiento p ON p.proced_id = tp.proced_id
        INNER JOIN tb_dependencia ta ON p.proced_area = ta.depe_id
        WHERE (:role = 9 OR EXISTS (SELECT 1 FROM sc_giros.td_areausu tau WHERE tau.depe_id = p.proced_area AND tau.est = 1 AND tau.pers_id = :person))";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(':person', (int)$pers_id, PDO::PARAM_INT);
        $sql->bindValue(':role', (int)$rol_id, PDO::PARAM_INT);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }
    public function get_ordenes_total_usuario($pers_id, $rol_id = 0)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT
                    (SELECT COUNT(DISTINCT oc.ogciud_id)
                     FROM sc_giros.td_ordengirociud oc
                     INNER JOIN sc_escalafon.tb_persona sp ON oc.pers_id = sp.pers_id
                     INNER JOIN sc_giros.td_giro_tasa_ciudadano gt ON gt.girot_giro = oc.ogciud_id
                     INNER JOIN sc_giros.td_tasatciud ttc ON ttc.tasatciud_id = gt.tasaciud_id
                     INNER JOIN sc_giros.td_tasaproced tp ON ttc.tasatciud_tasaproced = tp.tasaproced_id
                     INNER JOIN sc_giros.tm_procedimiento p ON p.proced_id = tp.proced_id
                     INNER JOIN tb_dependencia ta ON p.proced_area = ta.depe_id
                     WHERE oc.pers_id = :person AND (:role = 9 OR EXISTS (SELECT 1 FROM sc_giros.td_areausu tau WHERE tau.depe_id = p.proced_area AND tau.est = 1 AND tau.pers_id = :person))) AS total_general,
                    (SELECT COUNT(DISTINCT oc.ogciud_id)
                     FROM sc_giros.td_ordengirociud oc
                     INNER JOIN sc_escalafon.tb_persona sp ON oc.pers_id = sp.pers_id
                     INNER JOIN sc_giros.td_giro_tasa_ciudadano gt ON gt.girot_giro = oc.ogciud_id
                     INNER JOIN sc_giros.td_tasatciud ttc ON ttc.tasatciud_id = gt.tasaciud_id
                     INNER JOIN sc_giros.td_tasaproced tp ON ttc.tasatciud_tasaproced = tp.tasaproced_id
                     INNER JOIN sc_giros.tm_procedimiento p ON p.proced_id = tp.proced_id
                     INNER JOIN tb_dependencia ta ON p.proced_area = ta.depe_id
                     WHERE oc.pers_id = :person AND DATE(oc.fechacrea) = CURRENT_DATE AND (:role = 9 OR EXISTS (SELECT 1 FROM sc_giros.td_areausu tau WHERE tau.depe_id = p.proced_area AND tau.est = 1 AND tau.pers_id = :person))) AS total_dia,
                    (SELECT COUNT(DISTINCT oc.ogciud_id)
                     FROM sc_giros.td_ordengirociud oc
                     INNER JOIN sc_escalafon.tb_persona sp ON oc.pers_id = sp.pers_id
                     INNER JOIN sc_giros.td_giro_tasa_ciudadano gt ON gt.girot_giro = oc.ogciud_id
                     INNER JOIN sc_giros.td_tasatciud ttc ON ttc.tasatciud_id = gt.tasaciud_id
                     INNER JOIN sc_giros.td_tasaproced tp ON ttc.tasatciud_tasaproced = tp.tasaproced_id
                     INNER JOIN sc_giros.tm_procedimiento p ON p.proced_id = tp.proced_id
                     INNER JOIN tb_dependencia ta ON p.proced_area = ta.depe_id
                     WHERE oc.pers_id = :person AND DATE(oc.fechacrea) = CURRENT_DATE - INTERVAL '1 day' AND (:role = 9 OR EXISTS (SELECT 1 FROM sc_giros.td_areausu tau WHERE tau.depe_id = p.proced_area AND tau.est = 1 AND tau.pers_id = :person))) AS total_ayer";
        $sql = $conectar->prepare($sql);
        $sql->bindValue(':person', (int)$pers_id, PDO::PARAM_INT);
        $sql->bindValue(':role', (int)$rol_id, PDO::PARAM_INT);
        $sql->execute();
        return $resultado = $sql->fetchAll();
    }
    
   
   
    public function get_ordenes_Usuario_area($pers_id = 0, $rol_id = 0)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT 
        oc.ogciud_id,
        oc.pers_id,
        oc.fechacrea,
        ta.depe_denominacion  as area_nom,
        tp.tasa_id,
        tp.cod_ref,
        ts.tasa_nom,
        p.proced_nom,
        ta.depe_id  as area_id,
        ttc.tasatciud_id, 
        ttc.tasatciud_tasaproced,
        ttc.est as estadotasat, 
        ttc.tasatciud_procedciud, 
        gt.girot_id,
        gt.fechacrea, 
        gt.tasaciud_id, 
        gt.cantidad, 
        gt.importe, 
        gt.est as estadotasagirada, 
        gt.girot_giro,
        CONCAT(sp.pers_nombre, ' ', sp.pers_apelpat, ' ', sp.pers_apelmat) AS nombrecompleto
    FROM 
        sc_giros.td_tasatciud ttc
    LEFT JOIN 
        sc_giros.td_tasaproced tp ON ttc.tasatciud_tasaproced = tp.tasaproced_id
    LEFT JOIN 
        sc_giros.tm_tasa ts ON ts.tasa_id = tp.tasa_id
    LEFT JOIN 
        sc_giros.tm_procedimiento p ON p.proced_id = tp.proced_id
    INNER JOIN tb_dependencia ta ON p.proced_area = ta.depe_id
    LEFT JOIN 
        sc_giros.td_giro_tasa_ciudadano gt ON gt.tasaciud_id = ttc.tasatciud_id
    LEFT JOIN 
        sc_giros.td_ordengirociud oc ON oc.ogciud_id = gt.girot_giro
    LEFT JOIN 
        sc_escalafon.tb_persona sp ON oc.pers_id = sp.pers_id
            WHERE tp.est = 1 AND (:role = 9 OR EXISTS (SELECT 1 FROM sc_giros.td_areausu tau WHERE tau.depe_id = p.proced_area AND tau.est = 1 AND tau.pers_id = :person))";
         $stmt = $conectar->prepare($sql);
         $stmt->bindValue(':person', (int)$pers_id, PDO::PARAM_INT);
         $stmt->bindValue(':role', (int)$rol_id, PDO::PARAM_INT);
         $stmt->execute();
         return $resultado = $stmt->fetchAll();
    }

    public function get_Tasas_Area($area_id, $tupa_id, $proced_id, $pers_id = 0, $rol_id = 0)
    {
        $conectar = parent::conexion();
        parent::set_names();
        $sql = "SELECT 
        oc.ogciud_id,
        oc.pers_id,
        oc.fechacrea,
        ta.depe_denominacion as area_nom,
        tp.tasa_id,
        tp.cod_ref,
        ts.tasa_nom,
        p.proced_nom,
        ttc.est as estadotasat, 
        gt.girot_id,
        gt.fechacrea, 
        gt.tasaciud_id, 
        gt.cantidad, 
        gt.importe, 
        gt.est as estadotasagirada, 
        gt.girot_giro
    FROM 
        sc_giros.td_tasatciud ttc
    LEFT JOIN 
        sc_giros.td_tasaproced tp ON ttc.tasatciud_tasaproced = tp.tasaproced_id
    LEFT JOIN 
        sc_giros.tm_tasa ts ON ts.tasa_id = tp.tasa_id
    LEFT JOIN 
        sc_giros.tm_procedimiento p ON p.proced_id = tp.proced_id
    INNER JOIN tb_dependencia ta ON p.proced_area = ta.depe_id
    LEFT JOIN 
        sc_giros.td_giro_tasa_ciudadano gt ON gt.tasaciud_id = ttc.tasatciud_id
    LEFT JOIN 
        sc_giros.td_ordengirociud oc ON oc.ogciud_id = gt.girot_giro
    LEFT JOIN 
        sc_escalafon.tb_persona sp ON oc.pers_id = sp.pers_id
            WHERE tp.est = 1 AND p.proced_tupa = :tupa_id AND p.proced_id = :proced_id AND p.proced_area = :area_id
              AND (:role = 9 OR EXISTS (SELECT 1 FROM sc_giros.td_areausu tau WHERE tau.depe_id = p.proced_area AND tau.est = 1 AND tau.pers_id = :person))";
         $stmt = $conectar->prepare($sql);
         $stmt->bindValue(':tupa_id', $tupa_id);
         $stmt->bindValue(':proced_id', $proced_id);
         $stmt->bindValue(':area_id', $area_id);
         $stmt->bindValue(':person', (int)$pers_id, PDO::PARAM_INT);
         $stmt->bindValue(':role', (int)$rol_id, PDO::PARAM_INT);
         $stmt->execute();
         return $resultado = $stmt->fetchAll();
    }

    public function get_ordenes_giro($documento, $pers_id = 0, $rol_id = 0)
    {
        $conectar = parent::conexion();
        $sql = "SELECT 
                  og.ogciud_id,
                  og.est orden_est,
                  og.recibo_nro,
                  tt.est as procedimiento_est,
                  TO_CHAR(og.fechacrea, 'YYYY-MM-DD') AS fecha,
                  TO_CHAR(og.fechacrea, 'HH12:MI:SS AM') AS hora,
                  CONCAT(tu.pers_nombre, ' ', tu.pers_apelpat, ' ', tu.pers_apelmat) AS nombre_girador,
                  CONCAT(c.ciud_nombre, ' ',c.ciud_primer_apellido,' ', c.ciud_segundo_apellido) as ciud_nombre,
                  c.ciud_numero_documento AS ciudadano_doc,
                  c.ciud_domicilio_real,
                  c.tido_id,
                  tido.tido_descripcion,
                  tm.tupa_nom as proced_tupa,
                  ta.depe_denominacion,
                  p.proced_id,
                  p.proced_nom,
                  tst.cod_ref,
                  ts.tasa_nom,
                  gt.importe,
                  p.proced_tipoindvasc,
                  empr.empr_ruc,
                  empr.empr_razon_social,
                  empr.empr_direccion,
                  og.ogciud_comentario
                FROM 
                  sc_giros.td_ordengirociud og
                INNER JOIN sc_escalafon.tb_persona tu ON og.pers_id = tu.pers_id
                INNER JOIN sc_giros.td_giro_tasa_ciudadano gt ON gt.girot_giro = og.ogciud_id
                INNER JOIN sc_giros.td_tasatciud ttc ON gt.tasaciud_id = ttc.tasatciud_id
                INNER JOIN sc_giros.td_procedciudadano tt ON ttc.tasatciud_procedciud = tt.procedciudadano_id
                INNER JOIN public.tb_ciudadano c ON c.ciud_id = tt.ciud_id
                INNER JOIN sc_giros.tm_procedimiento p ON tt.proced_id = p.proced_id
                INNER JOIN sc_giros.td_tasaproced tst ON ttc.tasatciud_tasaproced = tst.tasaproced_id
                INNER JOIN sc_giros.tm_tasa ts ON tst.tasa_id = ts.tasa_id
                INNER JOIN tb_dependencia ta ON p.proced_area = ta.depe_id
                INNER JOIN sc_giros.tm_tupa tm ON p.proced_tupa = tm.tupa_id
                LEFT JOIN public.tb_empresa empr ON empr.empr_id = tt.empr_id
                LEFT JOIN public.tb_tipo_documento tido ON c.tido_id = tido.tido_id
                WHERE (c.ciud_numero_documento = :documento OR empr.empr_ruc = :documento)
                  AND (:role = 9 OR EXISTS (SELECT 1 FROM sc_giros.td_areausu tau WHERE tau.depe_id = p.proced_area AND tau.est = 1 AND tau.pers_id = :person))
                ORDER BY og.fechacrea DESC";
        $query = $conectar->prepare($sql);
        $query->bindValue(':documento', $documento);
        $query->bindValue(':person', (int)$pers_id, PDO::PARAM_INT);
        $query->bindValue(':role', (int)$rol_id, PDO::PARAM_INT);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function get_ordenes_mes($search = "", $limit = 10, $offset = 0, $pers_id = 0, $rol_id = 0)
    {
        $conectar = parent::conexion();

        $where = "WHERE EXTRACT(MONTH FROM og.fechacrea) = EXTRACT(MONTH FROM CURRENT_DATE) 
                      AND EXTRACT(YEAR FROM og.fechacrea) = EXTRACT(YEAR FROM CURRENT_DATE)
                      AND (:role = 9 OR EXISTS (SELECT 1 FROM sc_giros.td_areausu tau WHERE tau.depe_id = p.proced_area AND tau.est = 1 AND tau.pers_id = :person))";

        if (!empty($search)) {
            $where .= " AND (
                        CONCAT(c.ciud_nombre, ' ', c.ciud_primer_apellido, ' ', c.ciud_segundo_apellido) ILIKE :search
                        OR c.ciud_numero_documento ILIKE :search
                        OR p.proced_nom ILIKE :search)";
        }

        $sql = "SELECT 
                    og.ogciud_id,
                    og.est orden_est,
                    og.recibo_nro,
                    TO_CHAR(og.fechacrea, 'YYYY-MM-DD') AS fecha,
                    TO_CHAR(og.fechacrea, 'HH12:MI:SS AM') AS hora,
                    CONCAT(tu.pers_nombre, ' ', tu.pers_apelpat, ' ', tu.pers_apelmat) AS nombre_girador,
                    CONCAT(c.ciud_nombre, ' ', c.ciud_primer_apellido, ' ', c.ciud_segundo_apellido) AS ciud_nombre,
                    c.ciud_numero_documento AS ciudadano_doc,
                    p.proced_nom,
                    gt.importe
                FROM sc_giros.td_ordengirociud og
                INNER JOIN sc_escalafon.tb_persona tu ON og.pers_id = tu.pers_id
                INNER JOIN sc_giros.td_giro_tasa_ciudadano gt ON gt.girot_giro = og.ogciud_id
                INNER JOIN sc_giros.td_procedciudadano tt ON gt.tasaciud_id = tt.procedciudadano_id
                INNER JOIN public.tb_ciudadano c ON c.ciud_id = tt.ciud_id
                INNER JOIN sc_giros.tm_procedimiento p ON tt.proced_id = p.proced_id
                $where
                ORDER BY og.fechacrea DESC
                LIMIT :limit OFFSET :offset";

        $query = $conectar->prepare($sql);

        if (!empty($search)) {
            $query->bindValue(':search', "%$search%", PDO::PARAM_STR);
        }

        $query->bindValue(':person', (int)$pers_id, PDO::PARAM_INT);
        $query->bindValue(':role', (int)$rol_id, PDO::PARAM_INT);
        $query->bindValue(':limit', $limit, PDO::PARAM_INT);
        $query->bindValue(':offset', $offset, PDO::PARAM_INT);
        $query->execute();

        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function get_total_ordenes_mes($search = "", $pers_id = 0, $rol_id = 0)
    {
        $conectar = parent::conexion();

        $where = "WHERE EXTRACT(MONTH FROM og.fechacrea) = EXTRACT(MONTH FROM CURRENT_DATE) 
                  AND EXTRACT(YEAR FROM og.fechacrea) = EXTRACT(YEAR FROM CURRENT_DATE)
                  AND (:role = 9 OR EXISTS (SELECT 1 FROM sc_giros.td_areausu tau WHERE tau.depe_id = p.proced_area AND tau.est = 1 AND tau.pers_id = :person))";

        if (!empty($search)) {
            $where .= " AND (
                        LOWER(CONCAT(tu.pers_nombre, ' ', tu.pers_apelpat, ' ', tu.pers_apelmat)) LIKE LOWER(:search)
                        OR LOWER(c.ciud_numero_documento) LIKE LOWER(:search)
                        OR LOWER(p.proced_nom) LIKE LOWER(:search))";
        }

        $sql = "SELECT COUNT(*) AS total FROM sc_giros.td_ordengirociud og
                INNER JOIN sc_escalafon.tb_persona tu ON og.pers_id = tu.pers_id
                INNER JOIN sc_giros.td_giro_tasa_ciudadano gt ON gt.girot_giro = og.ogciud_id
                INNER JOIN sc_giros.td_procedciudadano tt ON gt.tasaciud_id = tt.procedciudadano_id
                INNER JOIN public.tb_ciudadano c ON c.ciud_id = tt.ciud_id
                INNER JOIN sc_giros.tm_procedimiento p ON tt.proced_id = p.proced_id
                $where";

        $query = $conectar->prepare($sql);

        if (!empty($search)) {
            $query->bindValue(':search', "%$search%", PDO::PARAM_STR);
        }

        $query->bindValue(':person', (int)$pers_id, PDO::PARAM_INT);
        $query->bindValue(':role', (int)$rol_id, PDO::PARAM_INT);
        $query->execute();
        $row = $query->fetch(PDO::FETCH_ASSOC);
        return $row ? (int)$row['total'] : 0;
    }

    public function get_orden_giro_by_id($ogciud_id, $pers_id = 0, $rol_id = 0)
    {
        $conectar = parent::conexion();

        $sql = "SELECT 
                      og.ogciud_id,
                      og.est orden_est,
                      og.recibo_nro,
                      TO_CHAR(og.fechacrea, 'YYYY-MM-DD') AS fecha,
                      TO_CHAR(og.fechacrea, 'HH12:MI:SS AM') AS hora,
                      CONCAT(tu.pers_nombre, ' ', tu.pers_apelpat, ' ', tu.pers_apelmat) AS nombre_girador,
                      CONCAT(c.ciud_nombre, ' ', c.ciud_primer_apellido, ' ', c.ciud_segundo_apellido) AS ciud_nombre,
                      c.ciud_numero_documento AS ciudadano_doc,
                      p.proced_nom,
                      tt.est as procedimiento_est,
                      gt.importe
                  FROM sc_giros.td_ordengirociud og
                  LEFT JOIN sc_escalafon.tb_persona tu ON og.pers_id = tu.pers_id
                  LEFT JOIN sc_giros.td_giro_tasa_ciudadano gt ON gt.girot_giro = og.ogciud_id
                  LEFT JOIN sc_giros.td_procedciudadano tt ON gt.tasaciud_id = tt.procedciudadano_id
                  LEFT JOIN public.tb_ciudadano c ON c.ciud_id = tt.ciud_id
                  LEFT JOIN sc_giros.tm_procedimiento p ON tt.proced_id = p.proced_id
                  WHERE og.ogciud_id = :ogciud_id
                    AND (:role = 9 OR EXISTS (SELECT 1 FROM sc_giros.td_areausu tau WHERE tau.depe_id = p.proced_area AND tau.est = 1 AND tau.pers_id = :person))";

        $query = $conectar->prepare($sql);
        $query->bindValue(':ogciud_id', (string)$ogciud_id, PDO::PARAM_STR);
        $query->bindValue(':person', (int)$pers_id, PDO::PARAM_INT);
        $query->bindValue(':role', (int)$rol_id, PDO::PARAM_INT);
        $query->execute();
        $data = $query->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            return false;
        }

        $baseUrl = Conectar::getEnv('APP_URL', 'http://10.10.10.16/SIGODT');
        $pdfUrl = rtrim($baseUrl, '/') . "/controller/rc.php?op=imprimirxid";
        $postData = http_build_query(["ogciud_id" => $ogciud_id]);

        $options = [
            "http" => [
                "header" => "Content-type: application/x-www-form-urlencoded",
                "method" => "POST",
                "content" => $postData,
                "timeout" => 10
            ]
        ];
        $context = stream_context_create($options);
        $pdfContent = @file_get_contents($pdfUrl, false, $context);

        if ($pdfContent === false) {
            return false;
        }

        $data["pdf_base64"] = base64_encode($pdfContent);

        return $data;
    }
}

if (!class_exists('OrdenGiro', false)) {
    class_alias('Ordengiro', 'OrdenGiro');
}
