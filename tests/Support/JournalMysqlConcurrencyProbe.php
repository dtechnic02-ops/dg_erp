<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__, 2))->safeLoad();

function journalPdo(string $database): PDO {
    if (!str_starts_with($database, 'dg_erp_journal_verify_')) throw new RuntimeException('Refusing non-verification database.');
    return new PDO('mysql:host='.($_ENV['DB_HOST']??'127.0.0.1').';port='.($_ENV['DB_PORT']??3306).';dbname='.$database.';charset=utf8mb4', $_ENV['DB_USERNAME']??'root', $_ENV['DB_PASSWORD']??'', [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
}
function createDraft(PDO $pdo,string $key): string {
    $pdo->beginTransaction();
    try {
        $fy=(int)$pdo->query('SELECT id FROM financial_years WHERE company_id=1 AND is_active=1 FOR UPDATE')->fetchColumn();
        $q=$pdo->prepare('SELECT id FROM journals WHERE company_id=1 AND request_key=? FOR UPDATE');$q->execute([$key]);
        if($q->fetchColumn()) throw new RuntimeException('duplicate-request');
        $seq=$pdo->query("SELECT next_number FROM journal_number_sequences WHERE company_id=1 AND financial_year_id={$fy} FOR UPDATE")->fetchColumn();
        if($seq===false){$number=1;$pdo->exec("INSERT INTO journal_number_sequences(company_id,financial_year_id,next_number,created_at,updated_at) VALUES(1,{$fy},2,NOW(),NOW())");}
        else{$number=(int)$seq;$pdo->exec("UPDATE journal_number_sequences SET next_number=".($number+1).",updated_at=NOW() WHERE company_id=1 AND financial_year_id={$fy}");}
        $no='JRN-1-'.$fy.'-'.str_pad((string)$number,8,'0',STR_PAD_LEFT);
        $s=$pdo->prepare("INSERT INTO journals(company_id,financial_year_id,journal_no,journal_date,journal_type,description,request_key,total_amount,created_by,is_locked,status,created_at,updated_at) VALUES(1,?,?,'2026-06-01','general','Probe',?,1,1,0,'draft',NOW(),NOW())");$s->execute([$fy,$no,$key]);$id=(int)$pdo->lastInsertId();
        foreach([[1,'debit'],[2,'credit']] as [$chart,$type]){$s=$pdo->prepare("INSERT INTO journal_items(company_id,journal_id,chart_account_id,type,amount,debit,credit,line_number,status,created_at,updated_at) VALUES(1,?,?,?,1,?,?,?,1,NOW(),NOW())");$s->execute([$id,$chart,$type,$type==='debit'?1:0,$type==='credit'?1:0,$chart]);}
        $s=$pdo->prepare("INSERT INTO journal_audit_events(company_id,financial_year_id,journal_id,event,new_status,actor_id,event_at) VALUES(1,?,?,'created','draft',1,NOW())");$s->execute([$fy,$id]);$pdo->commit();return 'success:'.$no;
    } catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();return str_contains($e->getMessage(),'duplicate')||($e instanceof PDOException&&$e->getCode()==='23000')?'duplicate':'error:'.$e->getMessage();}
}
if(($argv[1]??'')==='--worker'){while(!is_file($argv[4]))usleep(1000);echo createDraft(journalPdo($argv[2]),$argv[3]);exit;}
$db=$argv[1]??'';$mode=$argv[2]??'first';$barrier=sys_get_temp_dir().'/journal_'.bin2hex(random_bytes(6));$keys=$mode==='duplicate'?['aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa','aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa']:['aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa','bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb'];$workers=[];
foreach($keys as $key){$pipes=[];$p=proc_open([PHP_BINARY,__FILE__,'--worker',$db,$key,$barrier],[1=>['pipe','w'],2=>['pipe','w']],$pipes);$workers[]=[$p,$pipes];}touch($barrier);$results=[];foreach($workers as [$p,$pipes]){$results[]=trim(stream_get_contents($pipes[1]));$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);if(proc_close($p)!==0||$err)throw new RuntimeException($err);}unlink($barrier);
$pdo=journalPdo($db);$count=(int)$pdo->query('SELECT COUNT(*) FROM journals')->fetchColumn();$lines=(int)$pdo->query('SELECT COUNT(*) FROM journal_items')->fetchColumn();$audit=(int)$pdo->query("SELECT COUNT(*) FROM journal_audit_events WHERE event='created'")->fetchColumn();$next=(int)$pdo->query('SELECT next_number FROM journal_number_sequences WHERE company_id=1')->fetchColumn();sort($results);
if($mode==='first'&&($count!==2||$lines!==4||$audit!==2||$next!==3||count(array_unique($results))!==2))throw new RuntimeException(json_encode(compact('results','count','lines','audit','next')));
if($mode==='duplicate'&&($count!==1||$lines!==2||$audit!==1||$next!==2||$results!==['duplicate','success:JRN-1-1-00000001']))throw new RuntimeException(json_encode(compact('results','count','lines','audit','next')));
echo 'PASS '.json_encode(compact('mode','results','count','lines','audit','next')).PHP_EOL;
