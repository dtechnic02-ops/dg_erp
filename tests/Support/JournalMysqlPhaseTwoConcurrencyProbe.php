<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\ChartAccount;
use App\Models\Company;
use App\Models\FinancialYear;
use App\Models\Journal;
use App\Services\JournalService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

require dirname(__DIR__, 2).'/vendor/autoload.php';

$database=$argv[2]??'';
if(!str_starts_with($database,'dg_erp_journal_verify_'))throw new RuntimeException('Refusing non-verification database.');
putenv('DB_DATABASE='.$database);$_ENV['DB_DATABASE']=$database;$_SERVER['DB_DATABASE']=$database;
$app=require dirname(__DIR__,2).'/bootstrap/app.php';$app->make(Kernel::class)->bootstrap();

function service():JournalService{return app(JournalService::class);}
function journalPayload(string $key):array{return ['financial_year_id'=>1,'journal_date'=>'2026-06-15','journal_type'=>'general','reference_no'=>'MYSQL','description'=>'Concurrency probe','request_key'=>$key,'lines'=>[['chart_account_id'=>1,'account_id'=>1,'debit'=>'10.0000','credit'=>'0.0000'],['chart_account_id'=>2,'debit'=>'0.0000','credit'=>'10.0000']]];}
function approved(string $key):Journal{$j=service()->createDraft(journalPayload($key),1,1);$j=service()->submit($j,1);return service()->approve($j,2);}
function worker(string $mode,int $id,string $barrier):never{while(!is_file($barrier))usleep(1000);try{$j=Journal::findOrFail($id);$result=$mode==='post'?service()->post($j,3):service()->reverse($j,2,'Concurrent correction');echo 'success:'.$result->id;}catch(Throwable $e){echo 'rejected:'.get_class($e);}exit;}

$action=$argv[1]??'';
if($action==='worker')worker($argv[3],(int)$argv[4],$argv[5]);
if($action==='setup'){
    DB::table('users')->where('id',1)->update(['company_id'=>null]);DB::table('accounts')->delete();DB::table('chart_accounts')->delete();DB::table('financial_years')->delete();DB::table('users')->whereIn('id',[2,3])->delete();DB::table('companies')->where('id',1)->delete();foreach(['accounts','chart_accounts','financial_years','companies'] as $table)DB::statement('ALTER TABLE '.$table.' AUTO_INCREMENT = 1');
    DB::table('companies')->insert(['id'=>1,'company_name'=>'Journal Verify','mobile'=>'verify-phase2','email'=>'verify-phase2@example.test','status'=>'active','created_at'=>now(),'updated_at'=>now()]);
    DB::table('users')->where('id',1)->update(['company_id'=>1]);
    foreach([2,3] as $id)DB::table('users')->insert(['id'=>$id,'name'=>'Worker '.$id,'email'=>'worker'.$id.'@example.test','password'=>bcrypt('test'),'company_id'=>1,'role_id'=>1,'created_at'=>now(),'updated_at'=>now()]);
    FinancialYear::create(['id'=>1,'company_id'=>1,'name'=>'FY26','start_date'=>'2026-01-01','end_date'=>'2026-12-31','is_active'=>1,'created_by'=>1]);
    ChartAccount::create(['id'=>1,'company_id'=>1,'code'=>'1100','name'=>'Cash','account_class'=>'asset','normal_balance'=>'debit','level'=>3,'allow_manual_entry'=>1,'status'=>'active']);
    ChartAccount::create(['id'=>2,'company_id'=>1,'code'=>'3100','name'=>'Equity','account_class'=>'equity','normal_balance'=>'credit','level'=>3,'allow_manual_entry'=>1,'status'=>'active']);
    Account::create(['id'=>1,'company_id'=>1,'bank_name'=>'Cash','account_name'=>'Cash','account_no'=>'VERIFY-1','account_type'=>'Cash','opening_balance'=>0,'current_balance'=>0,'status'=>'active']);
    $post=approved((string)Str::uuid());$reverse=service()->post(approved((string)Str::uuid()),3);
    echo json_encode(['post'=>$post->id,'reverse'=>$reverse->id]);exit;
}
if(!in_array($action,['post','reverse'],true))throw new RuntimeException('Mode must be post or reverse.');
$id=(int)($argv[3]??0);$barrier=sys_get_temp_dir().DIRECTORY_SEPARATOR.'journal_phase2_'.bin2hex(random_bytes(6));$workers=[];
for($i=0;$i<2;$i++){$pipes=[];$process=proc_open([PHP_BINARY,__FILE__,'worker',$database,$action,(string)$id,$barrier],[1=>['pipe','w'],2=>['pipe','w']],$pipes);$workers[]=[$process,$pipes];}
touch($barrier);$results=[];foreach($workers as [$process,$pipes]){$results[]=trim(stream_get_contents($pipes[1]));$error=trim(stream_get_contents($pipes[2]));fclose($pipes[1]);fclose($pipes[2]);if(proc_close($process)!==0||$error!=='')throw new RuntimeException($error);}unlink($barrier);sort($results);
if(count(array_filter($results,fn($r)=>str_starts_with($r,'success:')))!==1)throw new RuntimeException('Expected one successful worker: '.json_encode($results));
if($action==='post'){
    $entryCount=DB::table('accounting_entries')->where('source_type','manual_journal')->where('source_id',$id)->where('source_event','posted')->count();$lineCount=DB::table('accounting_entry_lines')->whereIn('accounting_entry_id',DB::table('accounting_entries')->where('source_id',$id)->select('id'))->count();$audit=DB::table('journal_audit_events')->where('journal_id',$id)->where('event','posted')->count();$aux=DB::table('account_transactions')->where('reference_type','ManualJournal')->where('reference_id',$id)->count();
    if($entryCount!==1||$lineCount!==2||$audit!==1||$aux!==1||Journal::find($id)->status!=='posted')throw new RuntimeException(json_encode(compact('entryCount','lineCount','audit','aux','results')));
}else{
    $journalCount=DB::table('journals')->where('reversal_of_journal_id',$id)->count();$accounting=DB::table('accounting_entries')->whereNotNull('reversal_of_id')->where('source_id',$id)->count();$aux=DB::table('account_transactions')->whereNotNull('reversed_transaction_id')->where('reference_id','!=',$id)->count();$audit=DB::table('journal_audit_events')->where('journal_id',$id)->where('event','reversed')->count();
    $reversalLines=DB::table('journal_items')->whereIn('journal_id',DB::table('journals')->where('reversal_of_journal_id',$id)->select('id'))->count();$subledgers=DB::table('customer_transactions')->count()+DB::table('supplier_transactions')->count();
    if($journalCount!==1||$reversalLines!==2||$accounting!==1||$aux!==1||$audit!==1||$subledgers!==0||Journal::find($id)->status!=='reversed')throw new RuntimeException(json_encode(compact('journalCount','reversalLines','accounting','aux','audit','subledgers','results')));
}
$balance=(string)DB::table('accounts')->where('id',1)->value('current_balance');$partials=['orphan_entries'=>DB::table('accounting_entries')->whereNotIn('source_id',DB::table('journals')->select('id'))->count(),'orphan_lines'=>DB::table('accounting_entry_lines')->whereNotIn('accounting_entry_id',DB::table('accounting_entries')->select('id'))->count()];
echo 'PASS '.json_encode(compact('action','results','balance','partials')).PHP_EOL;
