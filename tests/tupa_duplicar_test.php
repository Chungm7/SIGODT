<?php
// Dependency-free unit test for Tupa duplication contract

class Conectar
{
    public static $db;
    public function conexion()
    {
        return self::$db;
    }
    public function set_names()
    {
        return true;
    }
}

require_once __DIR__ . '/../models/Tupa.php';

function check($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

class StatementDouble
{
    public $bindings = [];
    private $rows;
    public function __construct($rows)
    {
        $this->rows = $rows;
    }
    public function bindValue($key, $value, $type = null)
    {
        $this->bindings[$key] = [$value, $type];
    }
    public function execute()
    {
        return true;
    }
    public function fetchAll($mode = null)
    {
        return $this->rows;
    }
}

class ConnectionDouble
{
    public $sql;
    public $statement;
    public function __construct($rows)
    {
        $this->statement = new StatementDouble($rows);
    }
    public function prepare($sql)
    {
        $this->sql = $sql;
        return $this->statement;
    }
}

// 1. Test PHP Model binding and query syntax
$db = new ConnectionDouble([['duplicar_tupa' => 99]]);
Conectar::$db = $db;
$model = new tupa();
$result = $model->duplicar_tupa(10);

check(strpos($db->sql, 'SELECT sc_giros.duplicar_tupa(p_tupa_id := ?);') !== false, 'Model executes sc_giros.duplicar_tupa function');
check($db->statement->bindings[1][0] === 10, 'Model correctly binds tupa_id parameter');
check($result[0]['duplicar_tupa'] === 99, 'Model returns function execution result');

// 2. Test SQL migration file existence and integrity
$sqlFile = __DIR__ . '/../database/functions/duplicar_tupa.sql';
check(file_exists($sqlFile), 'Migration script file exists');
$sqlContent = file_get_contents($sqlFile);
check(strpos($sqlContent, 'CREATE OR REPLACE FUNCTION sc_giros.duplicar_tupa') !== false, 'SQL script defines duplicar_tupa function');
check(strpos($sqlContent, 'p_tupa_id integer') !== false, 'SQL script uses p_tupa_id integer signature');
check(strpos($sqlContent, 'RETURNS integer') !== false, 'SQL script returns integer ID');
check(strpos($sqlContent, '3,') !== false, 'SQL script inserts new document with est = 3 (En Proceso)');
check(strpos($sqlContent, '\'0\',') !== false, 'SQL script inserts new document with tupa_block = \'0\'');
check(strpos($sqlContent, 'sc_giros.td_tasaproced') !== false, 'SQL script clones td_tasaproced');
check(strpos($sqlContent, 'sc_giros.td_reqproced') !== false, 'SQL script clones td_reqproced');
check(strpos($sqlContent, 'cod_ref') !== false, 'SQL script recalculates cod_ref');

echo "All tupa_duplicar unit tests passed successfully!\n";
