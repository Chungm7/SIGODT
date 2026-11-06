<?php
// Public model behavior with a transactional PDO double, never a live connection.
class Conectar {
    public static $db;
    public function conexion() { return self::$db; }
    public function set_names() {}
}
if (!function_exists('mb_strtoupper')) { function mb_strtoupper($s, $encoding) { return strtoupper($s); } }
require __DIR__ . '/../models/Tasa.php';
function check($ok, $message) { if (!$ok) throw new RuntimeException($message); }
if (($argv[1] ?? '') === '--postgres-socket') {
    $socket = $argv[2] ?? '';
    check((bool) preg_match('~^/tmp/sigodt-giro-synthetic\.[a-zA-Z0-9]+$~D', $socket), 'Only disposable runner socket allowed');
    $db = new PDO("pgsql:host=$socket;dbname=postgres", 'postgres', null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    Conectar::$db = $db;
    $t = new Tasa();
    if (($argv[3] ?? '') === 'concurrent') {
        $i=(int)($argv[4] ?? 0);
        check($i>=1 && $i<=12, 'Bounded synthetic worker');
        if ($i%2) $t->emitirGrupo([1000+2*$i,1001+2*$i],[1,3],$i,'parallel group');
        else $t->Pagar_Order_Giro(1000+2*$i,$i,'parallel single',2);
        exit;
    }
    $db->exec("INSERT INTO sc_giros.td_tasaproced VALUES(1,12.5);
        INSERT INTO sc_giros.td_procedciudadano VALUES(100,1,1),(101,1,1);
        INSERT INTO sc_giros.td_tasatciud VALUES(100,1,100,1),(101,1,100,1),(102,1,101,1),(103,1,101,1)");
    $snapshot = function () use ($db) {
        return $db->query("SELECT json_build_array(
            (SELECT json_agg(h ORDER BY ogciud_id) FROM sc_giros.td_ordengirociud h),
            (SELECT json_agg(d ORDER BY tasaciud_id) FROM sc_giros.td_giro_tasa_ciudadano d),
            (SELECT json_agg(r ORDER BY tasatciud_id) FROM sc_giros.td_tasatciud r),
            (SELECT json_agg(p ORDER BY procedciudadano_id) FROM sc_giros.td_procedciudadano p),
            (SELECT json_agg(c ORDER BY year) FROM sc_giros.giro_year_counter c),
            (SELECT json_agg(a ORDER BY bita_id) FROM sc_seguridad.tb_bitacora a))::text")->fetchColumn();
    };
    $r = $t->emitirGrupo([100,101],[1,3],7,'synthetic group');
    $stmt = $db->prepare('SELECT cantidad,importe FROM sc_giros.td_giro_tasa_ciudadano WHERE girot_giro=? ORDER BY tasaciud_id');
    $stmt->execute([$r['ogciud_id']]);
    check($stmt->fetchAll(PDO::FETCH_NUM) == [[1,12.5],[3,37.5]], 'Real group ownership/quantities');
    check($db->query('SELECT est FROM sc_giros.td_procedciudadano WHERE procedciudadano_id=100')->fetchColumn() == 2, 'Real procedure state');
    $before=$snapshot();
    try { $t->emitirGrupo([102,999],[1,1],7,'late invalid'); throw new LogicException('Expected failure'); }
    catch (LogicException $e) { throw $e; } catch (Exception $e) {}
    check($snapshot()===$before, 'Late group failure must rollback headers/details/states/audit/counter');
    foreach ([[[102,102],[1,1]], [[102],[0]], [[],[]], [[102],['1.5']]] as $bad) {
        try { $t->emitirGrupo($bad[0],$bad[1],7,'bad'); throw new LogicException('Expected rejection'); }
        catch (InvalidArgumentException $e) {}
        check($snapshot()===$before,'Invalid request stores nothing');
    }
    $single=$t->Pagar_Order_Giro(102,7,'single',2);
    check($single['ogciud_id']!==$r['ogciud_id'] && $single['importe']==12.5, 'Real individual shares authority');
    check($db->query('SELECT est FROM sc_giros.td_procedciudadano WHERE procedciudadano_id=101')->fetchColumn()==1, 'Remaining pending keeps procedure open');
    check($db->query('SELECT count(*) FROM sc_seguridad.tb_bitacora WHERE pers_id=7')->fetchColumn()>=7, 'Own synthetic trigger audits attributed');
    $before=$snapshot();
    $db->exec("ALTER FUNCTION sc_giros.crear_ordengiro_ciud_id(integer,text) RENAME TO unavailable_for_test");
    try { $t->Pagar_Order_Giro(103,7,'missing migration'); throw new LogicException('Expected missing migration failure'); }
    catch (PDOException $e) {}
    check($snapshot()===$before, 'Missing schema has no unsafe fallback');
    $db->exec("ALTER FUNCTION sc_giros.unavailable_for_test(integer,text) RENAME TO crear_ordengiro_ciud_id");
    $before=$snapshot();
    check(is_string($t->insertarTasaGiros(103,'999998-9999',3)), 'Real standalone detail FK failure returns string');
    check($snapshot()===$before, 'Real standalone failure rolls back rate/detail changes');
    $header=$t->crearOrdenini(7,'standalone compatibility')[0]['ogciud_id'];
    check($t->insertarTasaGiros(103,$header,3)===true,'Real standalone success remains true');
    $stmt->execute([$header]);
    check($stmt->fetchAll(PDO::FETCH_NUM)==[[3,37.5]],'Real standalone amount/quantity');
    $before=$snapshot();
    check(is_string($t->insertarTasaGiros(103,$header,1)) && is_string($t->insertarTasaGiros(999,$header,1)),'Real standalone nonpending/missing returns string');
    check($snapshot()===$before,'Real standalone rejected requests have no stored effects');
    $db->exec("CALL sc_giros.crear_ordengiro_ciud(7,'external compatibility')");
    echo "Real PostgreSQL model transactions, rollback, audit, missing migration and CALL compatibility passed.\n";
    exit;
}
class GiroDB {
    public $active=false, $headers=[], $details=[], $counter=27049, $rates=[10=>1,11=>1], $fail=false, $auditFail=false, $queries=[];
    private $snapshot;
    function beginTransaction() { $this->snapshot=[$this->headers,$this->details,$this->counter,$this->rates]; $this->active=true; }
    function inTransaction() { return $this->active; }
    function commit() { $this->active=false; }
    function rollBack() { [$this->headers,$this->details,$this->counter,$this->rates]=$this->snapshot; $this->active=false; }
    function prepare($sql) { $this->queries[]=$sql; return new GiroStatement($this,$sql); }
}
class GiroStatement {
    private $db, $sql, $params=[];
    function __construct($db,$sql) { $this->db=$db; $this->sql=$sql; }
    function execute($p=[]) {
        $this->params=$p; $d=$this->db; $s=$this->sql;
        if (strpos($s,'crear_ordengiro_ciud_id')!==false) {
            if ($d->fail==='migration') throw new PDOException('SQL missing routine secret');
            $id=sprintf('%06d-2026',++$d->counter); $d->headers[]=$id;
        }
        if (strpos($s,'SET est = 2')!==false && strpos($s,'UPDATE sc_giros.td_tasatciud')!==false) $d->rates[$p[':tasatciud_id']]=2;
        if (strpos($s,'INSERT INTO sc_giros.td_giro_tasa_ciudadano')!==false) {
            if ($d->fail && count($d->details)===1) throw new PDOException('SQL late detail secret');
            $d->details[]=$p;
        }
        if (strpos($s,'UPDATE sc_seguridad.tb_bitacora')!==false && $d->auditFail) throw new PDOException('SQL audit secret');
    }
    function fetch($mode=null) {
        $id=$this->params[':tasatciud_id']??0;
        return ($this->db->rates[$id]??null)===1 ? ['ciudadano_id'=>1,'importe'=>'12.50'] : false;
    }
    function fetchColumn() {
        if (strpos($this->sql,'crear_ordengiro_ciud_id')!==false) return end($this->db->headers);
        return false;
    }
    function rowCount() { return 1; }
}
if (($argv[1] ?? '') === '--standalone-contract') { standaloneContract(); exit; }
if (($argv[1] ?? '') === '--connector-contract') { connectorContract(); exit; }
if (($argv[1] ?? '') === '--connector-default-method') {
    echo json_encode(defaultConnectorResults(stream_get_contents(STDIN)), JSON_THROW_ON_ERROR); exit;
}
$t=new Tasa();
Conectar::$db=$db=new GiroDB();
$result=$t->emitirGrupo([10,11],[1,3],7,'test');
check($result['ogciud_id']==='027050-2026','Actual group header ID');
check(count($db->headers)===1 && count($db->details)===2,'One header, two details');
check($db->details[1][':importe']===37.5 && $db->details[1][':cantidad']===3,'Tariff times quantity');
check($db->rates===[10=>2,11=>2] && !$db->active,'States committed');
foreach (['detail','migration','audit','pending'] as $failure) {
    Conectar::$db=$db=new GiroDB();
    if ($failure==='audit') $db->auditFail=true;
    elseif ($failure==='pending') $db->rates[11]=2;
    else $db->fail=$failure;
    $before=[$db->headers,$db->details,$db->counter,$db->rates];
    try { $t->emitirGrupo([10,11],[1,3],7,'test'); throw new LogicException('Expected group failure'); }
    catch (LogicException $e) { throw $e; } catch (Exception $e) {}
    check([$db->headers,$db->details,$db->counter,$db->rates]===$before && !$db->active,'Complete rollback: '.$failure);
}
foreach ([[[],[]], [[10,10],[1,1]], [[10],[0]], [[10],['2oops']], [[10],['1.5']], [[10],[-1]], [[10,11],[1]]] as $bad) {
    Conectar::$db=$db=new GiroDB();
    try { $t->emitirGrupo($bad[0],$bad[1],7,'test'); throw new LogicException('Expected invalid input'); }
    catch (InvalidArgumentException $e) {}
    check($db->counter===27049 && !$db->queries,'Reject invalid input before database work');
}
Conectar::$db=$db=new GiroDB();
$r=$t->Pagar_Order_Giro(10,7,'test',2);
check($r['success'] && $r['ogciud_id']==='027050-2026' && $db->details[0][':importe']===25.0,'Individual behavior');
check($t->insertarTasaGiros(11,$r['ogciud_id'],3)===true && $db->details[1][':importe']===37.5,'Standalone detail helper retains quantities');
echo "Model double validation/rollback/quantity/helper tests passed.\n";

// Execute the public switch in fresh processes; assert the whole response boundary.
function endpoint($op,$post,$session=true,$fail=false) {
    $script = <<<'PHP'
$calls=[];
class Tasa {
    function __call($name,$args) { global $calls; $calls[]=[$name,$args];
        if ($name==='Pagar_Order_Giro_trabajador') return [['ogciud_id'=>'000009-2026']];
        if ($name==='get_nmr_recibo_trabajador') return [['valor_recibo'=>20]];
        if ($name==='get_tasa_id') return [['tasa_id'=>1,'tasa_nom'=>'Test','multiplica'=>1]];
        return [];
    }
    function Pagar_Order_Giro(...$args) { if (FAILURE) throw new PDOException('SQLSTATE secret citizen'); return ['ogciud_id'=>'027050-2026']; }
    function emitirGrupo(...$args) { if (FAILURE) throw new PDOException('SQLSTATE secret citizen'); return ['ogciud_id'=>'027051-2026','data'=>[[10],[11]]]; }
}
class Empresa {} class Procedimiento {} class Bitacora {
    function __call($name,$args) { global $calls; $calls[]=[$name,$args]; return [['bita_id'=>4]]; }
}
http_response_code(200);
$source=file_get_contents('controller/tasa.php');
$source=preg_replace('/require_once\([^;]+;/', '', $source);
ob_start(); eval('?>'.$source); $body=ob_get_clean();
echo json_encode(['status'=>http_response_code(),'body'=>$body===''?null:json_decode($body,true,512,JSON_THROW_ON_ERROR),'calls'=>$calls]);
PHP;
    $prefix='const FAILURE='.var_export($fail,true).';$_GET='.var_export(['op'=>$op],true).';$_POST='.var_export($post,true).';$_SESSION='.var_export($session?['usua_id_SIGODT'=>7]:[],true).';';
    $proc=proc_open([PHP_BINARY,'-d','display_errors=stderr','-r',$prefix.$script],[1=>['pipe','w'],2=>['pipe','w']],$pipes,dirname(__DIR__));
    $out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);
    check(proc_close($proc)===0 && $err==='', 'Endpoint exit/stderr: '.$err);
    check(strpos($out,'SQLSTATE')===false && strpos($out,'citizen')===false,'No raw SQL/PII');
    return json_decode($out,true,512,JSON_THROW_ON_ERROR);
}
foreach (['Pagar_Orden_Giro','Pagar_Orden_Giro_grupo'] as $op) {
    $post=['tasatciudadano_id'=>10,'tasatciud_id'=>'10,11','cantidades'=>'1,3','comentario'=>'test'];
    $ok=endpoint($op,$post);
    check($ok['status']===200 && isset($ok['body']['ogciud_id']),'Endpoint success');
    $failed=endpoint($op,$post,true,true);
    check($failed['status']===500 && $failed['body']['error']===true && !isset($failed['body']['success']),'Controlled failed endpoint');
    $expired=endpoint($op,$post,false,true);
    check($expired['status']===200 && $expired['body']['error']===true && strpos($expired['body']['message'],'sesión')!==false,'Session semantics');
}
check(endpoint('Pagar_Orden_Giro_grupo',[])['status']===400,'Malformed group rejected');
$worker=endpoint('Pagar_Order_Giro_trabajador',['tasatciudadano_id'=>10,'comentario'=>'test']);
check(array_column($worker['calls'],0)===['get_max_id','Pagar_Order_Giro_trabajador','get_nmr_recibo_trabajador','update_nmr_recibo_trabajador','update_bitacora_grupo'],'Worker sibling call flow unchanged');
$company=endpoint('Pagar_Order_Giro_empresa',['tasatempr_id'=>10,'comentario'=>'test']);
check(array_column($company['calls'],0)===['Pagar_Order_Giro_Empresa','update_bitacora'],'Company sibling unchanged');
check(endpoint('mostrar',['tasa_id'=>1])['body']===['tasa_id'=>1,'tasa_nom'=>'Test','tasa_tipo'=>1],'Fee query unchanged');
echo "Controller double JSON/HTTP/session/no-leak/sibling tests passed; exit=0, stderr empty.\n";


function standaloneContract() {
    $t=new Tasa();
    Conectar::$db=$db=new GiroDB();
    check($t->insertarTasaGiros(10,'027050-2026',3)===true,'Standalone success remains true');
    check($db->details[0][':importe']===37.5 && $db->details[0][':cantidad']===3,'Standalone quantity retained');
    foreach (['detail','missing','nonpending','invalid','invalid-id'] as $failure) {
        Conectar::$db=$db=new GiroDB();
        if ($failure==='detail') { $db->details=[['existing'=>true]]; $db->fail=true; }
        if ($failure==='nonpending') $db->rates[10]=2;
        $before=[$db->headers,$db->details,$db->counter,$db->rates];
        try { $result=$t->insertarTasaGiros($failure==='missing'?999:($failure==='invalid-id'?0:10),'027050-2026',$failure==='invalid'?0:1); }
        catch (Throwable $e) { throw new RuntimeException('Standalone '.$failure.' must return error-string, not throw'); }
        check(is_string($result) && $result!=='','Standalone '.$failure.' failure returns string');
        check([$db->headers,$db->details,$db->counter,$db->rates]===$before && !$db->active,'Standalone '.$failure.' rollback/no effects');
        if (strpos($failure,'invalid')===0) check($db->queries===[],'Invalid quantity rejected before transaction');
    }
    echo "Standalone true/error-string/quantity/rollback contracts passed.\n";
}

// Extract only the connector method as source. Never require the config file:
// its environment loader and any real PDO connection must remain unexecuted.
function connectorMethod() {
    $source=file_get_contents(__DIR__.'/../config/conexion.php');
    check(preg_match('~    (?:protected|public) function conexion\([^)]*\)\s*\{.*?^    \}~ms',$source,$match)===1,'Connector source method found');
    return $match[0];
}
function connectorProcess($method,$stage,$call) {
    $method=str_replace('new PDO(', 'new SyntheticPDO(', $method, $count);
    check($count===1,'Exactly one synthetic PDO constructor substitution');
    $script=<<<'PHP'
class SyntheticPDO {
    public $calls=[];
    function __construct(...$args) { if (STAGE==='constructor') throw new PDOException('SYNTHETIC_CONNECTOR_DIAGNOSTIC'); }
    function setAttribute(...$args) { if (STAGE==='attribute') throw new PDOException('SYNTHETIC_CONNECTOR_DIAGNOSTIC'); $this->calls[]=['attribute',...$args]; }
    function exec($sql) { if (STAGE==='exec') throw new PDOException('SYNTHETIC_CONNECTOR_DIAGNOSTIC'); $this->calls[]=['exec',$sql]; }
}
PHP;
    $script.='class Conectar { protected $dbh; public static function getEnv($key,$default=null) { return ["DB_HOST"=>"synthetic","DB_PORT"=>"1","DB_NAME"=>"synthetic","DB_USER"=>"synthetic","DB_PASS"=>""][$key]; } public function set_names() {} '.$method.' public function probe($opt=null) { return $opt===null?$this->conexion():$this->conexion($opt); }}';
    $script.=$call;
    $proc=proc_open([PHP_BINARY,'-d','display_errors=stderr','-r','const STAGE='.var_export($stage,true).';'.$script],[1=>['pipe','w'],2=>['pipe','w']],$pipes,dirname(__DIR__));
    $out=stream_get_contents($pipes[1]); $err=stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]);
    return ['exit'=>proc_close($proc),'stdout'=>$out,'stderr'=>$err];
}
function defaultConnectorResults($method) {
    $results=[];
    foreach (['success','constructor','attribute','exec'] as $stage) {
        foreach (['omitted','false'] as $opt) {
            $call='$db=(new Conectar())->probe('.($opt==='false'?'false':'').');echo json_encode(["connected"=>true,"calls"=>$db->calls]);';
            $results[$stage.'/'.$opt]=connectorProcess($method,$stage,$call);
        }
    }
    return $results;
}
function connectorContract() {
    $method=connectorMethod();
    foreach (defaultConnectorResults($method) as $case=>$result) {
        check($result['exit']===0 && $result['stderr']==='','Default connector exit/stderr preserved: '.$case);
        if (strpos($case,'success/')===0) {
            check(json_decode($result['stdout'],true)===['connected'=>true,'calls'=>[['attribute',PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION],['exec',"SET NAMES 'utf8'"]]],'Default success behavior preserved');
        } else check($result['stdout']==='Error BD!: SYNTHETIC_CONNECTOR_DIAGNOSTIC<br/>','Default print+die preserved: '.$case);
    }
    foreach (['success','constructor','attribute','exec'] as $stage) {
        $r=connectorProcess($method,$stage,'try { $db=(new Conectar())->probe(true); echo "connected"; } catch (PDOException $e) { echo "caught:".$e->getMessage(); }');
        check($r===['exit'=>0,'stdout'=>$stage==='success'?'connected':'caught:SYNTHETIC_CONNECTOR_DIAGNOSTIC','stderr'=>''],'Opt-in success/exception propagation: '.$stage);
    }
    foreach (['Pagar_Orden_Giro','Pagar_Orden_Giro_grupo'] as $op) {
        $call=<<<'PHP'
require 'models/Tasa.php';
class Empresa {} class Procedimiento {} class Bitacora {}
$_SESSION=['usua_id_SIGODT'=>7];
$_POST=['tasatciudadano_id'=>10,'tasatciud_id'=>'10,11','cantidades'=>'1,3','comentario'=>'synthetic'];
http_response_code(200);
$source=preg_replace('/require_once\([^;]+;/', '', file_get_contents('controller/tasa.php'));
ob_start();eval('?>'.$source);$body=ob_get_clean();
echo json_encode(['status'=>http_response_code(),'body'=>json_decode($body,true)]);
PHP;
        $r=connectorProcess($method,'constructor','$_GET=["op"=>'.var_export($op,true).'];'.$call);
        check($r['exit']===0 && $r['stderr']==='','Actual endpoint connector failure exit/stderr');
        $response=json_decode($r['stdout'],true);
        check($response===['status'=>500,'body'=>['error'=>true,'message'=>'No se pudo emitir la orden. Actualice el listado antes de volver a intentarlo.']] && strpos($r['stdout'],'SYNTHETIC_CONNECTOR_DIAGNOSTIC')===false && strpos($r['stdout'],'Error BD!')===false,$op.' actual model connection failure must return generic HTTP500 JSON');
    }
    echo "Source-derived connector default success/print+die and actual model/controller JSON500 contracts passed.\n";
}
standaloneContract();
connectorContract();
