<?php
class ReporteUsuario extends Conectar {
    public static function positiveId($value) {
        if ((!is_string($value) && !is_int($value)) || !preg_match('/^[1-9][0-9]*$/D', (string)$value)) {
            throw new InvalidArgumentException('Identificador inválido.');
        }
        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false) { throw new InvalidArgumentException('Identificador inválido.'); }
        return $id;
    }

    public static function validate($input) {
        $filters = [];
        foreach (['start', 'end'] as $key) {
            $value = $input[$key] ?? null;
            $date = is_string($value) ? DateTimeImmutable::createFromFormat('!Y-m-d', $value) : false;
            if (!$date || $date->format('Y-m-d') !== $value || $value < '0001-01-01' || $value > '9998-12-31') {
                throw new InvalidArgumentException('Indique fechas válidas (AAAA-MM-DD).');
            }
            $filters[$key] = $value;
        }
        if ($filters['start'] > $filters['end']) { throw new InvalidArgumentException('El inicio no puede ser posterior al fin.'); }
        foreach (['document', 'procedure'] as $key) {
            $value = $input[$key] ?? '';
            $filters[$key] = $value === '' ? null : self::positiveId($value);
        }
        foreach (['page' => 1000000000, 'limit' => 100] as $key => $max) {
            $value = self::positiveId($input[$key] ?? ($key === 'page' ? 1 : 20));
            if ($value > $max) { throw new InvalidArgumentException('Paginación inválida.'); }
            $filters[$key] = $value;
        }
        return $filters;
    }

    private static function cents($value) {
        $value = (string)($value ?? '0');
        if (!preg_match('/^(-?)([0-9]+)(?:\.([0-9]{1,2}))?$/D', $value, $match)) {
            throw new UnexpectedValueException('Invalid stored monetary amount');
        }
        $amount = (int)$match[2] * 100 + (int)str_pad($match[3] ?? '', 2, '0');
        return ($match[1] === '-' ? -1 : 1) * $amount;
    }

    private static function money($cents) {
        return ($cents < 0 ? '-' : '') . intdiv(abs($cents), 100) . '.' . str_pad((string)(abs($cents) % 100), 2, '0', STR_PAD_LEFT);
    }

    public function report($emitter, $input, $full = false) {
        $emitter = self::positiveId($emitter);
        $f = self::validate($input);
        // One read snapshot supplies options and all aggregates. No current-state exclusions.
        // Optional filters are applied to individual lines below, before any aggregation.
        $sql = <<<'SQL'
SELECT og.ogciud_id AS order_id, og.fechacrea AS emitted_at, og.est AS state,
       gt.girot_id AS line_id, gt.importe AS amount,
       d.tupa_id AS document_id, d.tupa_nom AS document,
       p.proced_id AS procedure_id, p.proced_cod AS code, p.proced_nom AS procedure,
       BTRIM(CONCAT_WS(' / ',
         NULLIF(BTRIM(CONCAT_WS(' ', c.ciud_nombre, c.ciud_primer_apellido, c.ciud_segundo_apellido)), ''),
         COALESCE(NULLIF(e.empr_razon_social, ''), NULLIF(pc.empresa_razon_social, '')))) AS citizen
FROM sc_giros.td_ordengirociud og
LEFT JOIN sc_giros.td_giro_tasa_ciudadano gt ON gt.girot_giro = og.ogciud_id
LEFT JOIN sc_giros.td_tasatciud ttc ON gt.tasaciud_id = ttc.tasatciud_id
LEFT JOIN sc_giros.td_procedciudadano pc ON ttc.tasatciud_procedciud = pc.procedciudadano_id
LEFT JOIN sc_giros.tm_procedimiento p ON pc.proced_id = p.proced_id
LEFT JOIN sc_giros.tm_tupa d ON p.proced_tupa = d.tupa_id
LEFT JOIN public.tb_ciudadano c ON pc.ciud_id = c.ciud_id
LEFT JOIN public.tb_empresa e ON pc.empr_id = e.empr_id
WHERE og.pers_id = :emitter AND og.fechacrea >= :start AND og.fechacrea < :end
ORDER BY og.fechacrea DESC, og.ogciud_id DESC, gt.girot_id
SQL;
        $statement = $this->conexion()->prepare($sql);
        $statement->execute([
            ':emitter' => $emitter,
            ':start' => $f['start'],
            ':end' => (new DateTimeImmutable($f['end']))->modify('+1 day')->format('Y-m-d')
        ]);
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
        $orders = []; $groups = []; $documents = []; $procedures = []; $seen = [];
        $summary = ['orders' => 0, 'cancelled' => 0, 'lines' => 0, 'amount' => 0];
        foreach ($rows as $row) {
            $docId = $row['document_id']; $procId = $row['procedure_id'];
            $doc = $row['document'] ?: 'Sin documento';
            $procedure = $row['procedure'] ?: 'Sin procedimiento';
            if ($docId !== null) { $documents[$docId] = ['id' => (string)$docId, 'label' => $doc]; }
            if ($procId !== null && ($f['document'] === null || (string)$docId === (string)$f['document'])) {
                $procedures[$procId] = ['id' => (string)$procId, 'label' => ($row['code'] ?? '') . ' — ' . $procedure];
            }
            if (($f['document'] !== null && (string)$docId !== (string)$f['document']) ||
                ($f['procedure'] !== null && (string)$procId !== (string)$f['procedure'])) { continue; }
            $id = $row['order_id'];
            $state = (string)$row['state'];
            $isCancelled = in_array($state, ['0', '6'], true);
            if (!isset($orders[$id])) {
                $orders[$id] = ['order_id' => $id, 'emitted_at' => $row['emitted_at'], 'state' => $row['state'],
                    'state_label' => ['0' => 'Anulado', '1' => 'Girado', '6' => 'Extornado'][$state] ?? 'Estado ' . $state,
                    'citizens' => [], 'documents' => [], 'codes' => [], 'procedures' => [], 'amount' => 0];
                if ($isCancelled) {
                    $summary['cancelled']++;
                } else {
                    $summary['orders']++;
                }
            }
            foreach (['citizens' => $row['citizen'] ?: 'Sin ciudadano / razón social', 'documents' => $doc,
                'codes' => $row['code'] ?: 'Sin código', 'procedures' => $procedure] as $key => $label) {
                $orders[$id][$key][$label] = $label;
            }
            // Preserve headers with no detail. A real line contributes its stored TOTAL once.
            if ($row['line_id'] === null || isset($seen[$row['line_id']])) { continue; }
            $seen[$row['line_id']] = true;
            $amount = self::cents($row['amount']);
            $orders[$id]['amount'] += $amount;
            if (!$isCancelled) {
                $summary['amount'] += $amount;
                $summary['lines']++;
            }
            $key = ($docId ?? 'none') . ':' . ($procId ?? 'none');
            if (!isset($groups[$key])) {
                $groups[$key] = ['document' => $doc, 'code' => $row['code'] ?: 'Sin código', 'procedure' => $procedure,
                    'orders' => [], 'lines' => 0, 'amount' => 0];
            }
            if (!$isCancelled) {
                $groups[$key]['orders'][$id] = true;
                $groups[$key]['lines']++;
                $groups[$key]['amount'] += $amount;
            }
        }
        foreach ($orders as &$order) {
            foreach (['citizens', 'documents', 'codes', 'procedures'] as $key) { $order[$key] = array_values($order[$key]); }
            $order['amount'] = self::money($order['amount']);
        }
        unset($order);
        foreach ($groups as &$group) {
            $group['orders'] = count($group['orders']); $group['amount'] = self::money($group['amount']);
        }
        unset($group);
        $summary['amount'] = self::money($summary['amount']);
        $total = count($orders);
        $page = min($f['page'], max(1, (int)ceil($total / $f['limit'])));
        return ['filters' => $f, 'summary' => $summary, 'breakdown' => array_values($groups),
            'options' => ['documents' => array_values($documents), 'procedures' => array_values($procedures)],
            'details' => $full ? array_values($orders) : array_slice(array_values($orders), ($page - 1) * $f['limit'], $f['limit']),
            'total' => $total, 'page' => $page, 'limit' => $f['limit']];
    }
}
