<?php

namespace App\Services;

use App\Models\AccountingPeriodLock;
use App\Models\Account;
use App\Models\ChartAccount;
use App\Models\Company;
use App\Models\FinancialYear;
use App\Models\Journal;
use App\Models\JournalAuditEvent;
use App\Models\Role;
use App\Models\User;
use App\Models\AccountTransaction;
use App\Models\Customer;
use App\Models\CustomerTransaction;
use App\Models\Supplier;
use App\Models\SupplierTransaction;
use App\Services\Accounting\Integrations\JournalAccountingIntegrationService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class JournalService
{
    public function __construct(private readonly JournalAccountingIntegrationService $accountingIntegration) {}
    private const TRANSITIONS = [
        Journal::STATUS_DRAFT => [Journal::STATUS_SUBMITTED, Journal::STATUS_CANCELLED],
        Journal::STATUS_SUBMITTED => [Journal::STATUS_APPROVED, Journal::STATUS_REJECTED],
        Journal::STATUS_REJECTED => [Journal::STATUS_DRAFT],
        Journal::STATUS_APPROVED => [Journal::STATUS_POSTED, Journal::STATUS_CANCELLED],
        Journal::STATUS_POSTED => [Journal::STATUS_REVERSED],
        Journal::STATUS_CANCELLED => [],
        Journal::STATUS_REVERSED => [],
    ];

    public function createDraft(array $data, int $companyId, int $actorId): Journal
    {
        return DB::transaction(function () use ($data, $companyId, $actorId) {
            $fy = $this->validateFinancialContext($companyId, (int) $data['financial_year_id'], $data['journal_date']);
            $lines = $this->validateLines($data['lines'], $companyId);
            if (Journal::where('company_id', $companyId)->where('request_key', $data['request_key'])->exists()) {
                throw ValidationException::withMessages(['request_key' => 'This Journal request has already been submitted.']);
            }
            $journal = Journal::create([
                'company_id' => $companyId, 'financial_year_id' => $fy->id,
                'journal_no' => $this->nextNumber($companyId, $fy), 'journal_date' => $data['journal_date'],
                'journal_type' => $data['journal_type'], 'reference_no' => $data['reference_no'] ?? null,
                'description' => $data['description'], 'remarks' => $data['remarks'] ?? null,
                'note' => $data['description'], 'request_key' => $data['request_key'],
                'total_amount' => $this->totalDebit($lines), 'created_by' => $actorId,
                'status' => Journal::STATUS_DRAFT,
            ]);
            $this->replaceLines($journal, $lines);
            $this->audit($journal, 'created', null, Journal::STATUS_DRAFT, $actorId);
            return $journal->load(['items.chartAccount', 'auditEvents']);
        });
    }

    public function updateDraft(Journal $journal, array $data, int $actorId): Journal
    {
        return DB::transaction(function () use ($journal, $data, $actorId) {
            $journal = Journal::whereKey($journal->id)->where('company_id', $journal->company_id)->lockForUpdate()->firstOrFail();
            $this->assertManualJournal($journal);
            if (!$journal->isDraft() || $journal->is_locked) throw new RuntimeException('Only an unlocked Draft Journal may be edited.');
            $fy = $this->validateFinancialContext($journal->company_id, (int) $data['financial_year_id'], $data['journal_date']);
            if ((int) $fy->id !== (int) $journal->financial_year_id) throw new RuntimeException('A Journal Financial Year cannot be changed after numbering.');
            $lines = $this->validateLines($data['lines'], $journal->company_id);
            $journal->update([
                'journal_date' => $data['journal_date'], 'journal_type' => $data['journal_type'],
                'reference_no' => $data['reference_no'] ?? null, 'description' => $data['description'],
                'remarks' => $data['remarks'] ?? null, 'note' => $data['description'],
                'total_amount' => $this->totalDebit($lines), 'updated_by' => $actorId,
            ]);
            $journal->items()->delete();
            $this->replaceLines($journal, $lines);
            $this->audit($journal, 'updated_draft', Journal::STATUS_DRAFT, Journal::STATUS_DRAFT, $actorId);
            return $journal->load(['items.chartAccount', 'auditEvents']);
        });
    }

    public function assertTransition(string $from, string $to): void
    {
        if (!in_array($to, self::TRANSITIONS[$from] ?? [], true)) throw new RuntimeException("Invalid Journal status transition from {$from} to {$to}.");
    }

    public function submit(Journal $journal, int $actorId): Journal
    {
        return $this->transition($journal, $actorId, Journal::STATUS_DRAFT, Journal::STATUS_SUBMITTED, 'submitted', function (Journal $locked) use ($actorId) {
            $this->validateExisting($locked);
            return ['submitted_by'=>$actorId,'submitted_at'=>now()];
        });
    }

    public function approve(Journal $journal, int $actorId): Journal
    {
        $metadata = null;
        if ($this->actorMayBypassMakerCheckerSeparation($actorId, (int) $journal->company_id)
            && ((int) $journal->created_by === $actorId || (int) $journal->submitted_by === $actorId)) {
            $metadata = ['admin_override' => true, 'override_type' => 'self_approval'];
        }

        return $this->transition($journal, $actorId, Journal::STATUS_SUBMITTED, Journal::STATUS_APPROVED, 'approved', function (Journal $locked) use ($actorId) {
            $this->assertMakerCheckerSeparationForApprove($locked, $actorId);
            $this->validateExisting($locked);
            return ['approved_by'=>$actorId,'approved_at'=>now()];
        }, null, $metadata);
    }

    public function reject(Journal $journal, int $actorId, string $reason): Journal
    {
        $reason=$this->reason($reason,'Rejection');
        return $this->transition($journal,$actorId,Journal::STATUS_SUBMITTED,Journal::STATUS_DRAFT,'rejected',fn()=>['rejected_by'=>$actorId,'rejected_at'=>now(),'rejection_reason'=>$reason],$reason);
    }

    public function cancel(Journal $journal, int $actorId, string $reason): Journal
    {
        $reason=$this->reason($reason,'Cancellation');
        return $this->transition($journal,$actorId,Journal::STATUS_DRAFT,Journal::STATUS_CANCELLED,'cancelled',fn()=>['cancelled_by'=>$actorId,'cancelled_at'=>now(),'cancelled_date'=>now()->toDateString(),'cancellation_reason'=>$reason,'cancel_reason'=>$reason],$reason);
    }

    public function setLock(Journal $journal, int $actorId, bool $lock, string $reason): Journal
    {
        $reason=$this->reason($reason,$lock?'Lock':'Unlock');
        return DB::transaction(function()use($journal,$actorId,$lock,$reason){
            $j=Journal::where('company_id',$journal->company_id)->lockForUpdate()->findOrFail($journal->id);
            $this->assertManualJournal($j);
            if(!in_array($j->status,[Journal::STATUS_DRAFT,Journal::STATUS_SUBMITTED,Journal::STATUS_APPROVED,Journal::STATUS_POSTED],true))throw new RuntimeException('This Journal status cannot be locked or unlocked.');
            if((bool)$j->is_locked===$lock)throw new RuntimeException($lock?'Journal is already locked.':'Journal is not locked.');
            $data=$lock?['is_locked'=>1,'locked_by'=>$actorId,'locked_at'=>now(),'lock_reason'=>$reason]:['is_locked'=>0,'unlocked_by'=>$actorId,'unlocked_at'=>now(),'unlock_reason'=>$reason];$j->update($data);$this->audit($j,$lock?'locked':'unlocked',$j->status,$j->status,$actorId,$reason);return $j->fresh();
        });
    }

    public function post(Journal $journal, int $actorId): Journal
    {
        return DB::transaction(function()use($journal,$actorId){
            $j=Journal::with('items')->where('company_id',$journal->company_id)->lockForUpdate()->findOrFail($journal->id);
            $this->assertManualJournal($j);
            if($j->status!==Journal::STATUS_APPROVED||$j->is_locked)throw new RuntimeException('Only an unlocked Approved Journal may be posted.');
            $this->assertMakerCheckerSeparationForPost($j, $actorId);
            $this->validateExisting($j);
            $sourceKey='manual-journal:'.$j->id.':posted';
            $entry=$this->accountingIntegration->postJournal($j, (int) $j->company_id, $actorId);
            $this->createAuxiliary($j,$actorId);
            $j->update(['source_module'=>'journal','source_type'=>'manual_journal','source_id'=>$j->id,'source_key'=>$sourceKey,'status'=>Journal::STATUS_POSTED,'posted_by'=>$actorId,'posted_at'=>now()]);
            $postMetadata = ['accounting_entry_id' => $entry->id];
            if ($this->actorMayBypassMakerCheckerSeparation($actorId, (int) $j->company_id)
                && in_array($actorId, [(int) $j->created_by, (int) $j->approved_by], true)) {
                $postMetadata['admin_override'] = true;
                $postMetadata['override_type'] = 'self_post';
            }
            $this->audit($j,'posted',Journal::STATUS_APPROVED,Journal::STATUS_POSTED,$actorId,null,$postMetadata);return $j->fresh();
        });
    }

    public function reverse(Journal $journal, int $actorId, string $reason): Journal
    {
        $reason=$this->reason($reason,'Reversal');
        return DB::transaction(function()use($journal,$actorId,$reason){
            $original=Journal::with('items')->where('company_id',$journal->company_id)->lockForUpdate()->findOrFail($journal->id);
            $this->assertManualJournal($original);
            if($original->status!==Journal::STATUS_POSTED||$original->reversal_of_journal_id)throw new RuntimeException('Only an original Posted Journal may be reversed.');
            $fy=$this->validateFinancialContext($original->company_id,$original->financial_year_id,$original->journal_date->format('Y-m-d'));
            if(Journal::where('reversal_of_journal_id',$original->id)->lockForUpdate()->exists())throw new RuntimeException('This Journal has already been reversed.');
            $reversal=Journal::create(['company_id'=>$original->company_id,'financial_year_id'=>$fy->id,'journal_no'=>$this->nextNumber($original->company_id,$fy),'journal_date'=>$original->journal_date,'journal_type'=>Journal::TYPE_REVERSAL,'reference_no'=>$original->reference_no,'description'=>'Reversal of '.$original->journal_no,'remarks'=>$reason,'note'=>$reason,'source_module'=>'journal','source_type'=>'manual_journal_reversal','source_id'=>$original->id,'source_key'=>'manual-journal:'.$original->id.':reversed','total_amount'=>$original->total_amount,'created_by'=>$actorId,'posted_by'=>$actorId,'posted_at'=>now(),'reversal_of_journal_id'=>$original->id,'status'=>Journal::STATUS_POSTED]);
            foreach($original->items as $i=>$item)$reversal->items()->create(['company_id'=>$original->company_id,'chart_account_id'=>$item->chart_account_id,'account_id'=>$item->account_id,'debit'=>$item->credit,'credit'=>$item->debit,'type'=>$item->type==='debit'?'credit':'debit','amount'=>$item->amount,'description'=>'Reversal: '.$item->description,'reference'=>$item->reference,'line_number'=>$i+1,'sub_ledger_type'=>$item->sub_ledger_type,'sub_ledger_id'=>$item->sub_ledger_id,'status'=>1]);
            $this->accountingIntegration->reverseJournal($original, $reversal, $actorId);
            $reversal->load('items');$this->reverseAuxiliary($original,$reversal,$actorId);
            $original->update(['status'=>Journal::STATUS_REVERSED,'reversed_by'=>$actorId,'reversed_at'=>now(),'reversal_reason'=>$reason]);$this->audit($original,'reversed',Journal::STATUS_POSTED,Journal::STATUS_REVERSED,$actorId,$reason,['reversal_journal_id'=>$reversal->id]);$this->audit($reversal,'posted_reversal',null,Journal::STATUS_POSTED,$actorId,$reason,['original_journal_id'=>$original->id]);return $reversal;
        });
    }

    private function transition(Journal $journal,int $actorId,string $from,string $to,string $event,callable $extra,?string $reason=null,?array $metadata=null):Journal{return DB::transaction(function()use($journal,$actorId,$from,$to,$event,$extra,$reason,$metadata){$j=Journal::where('company_id',$journal->company_id)->lockForUpdate()->findOrFail($journal->id);$this->assertManualJournal($j);if($j->status!==$from)throw new RuntimeException("Only {$from} Journals may be {$event}.");if($j->is_locked)throw new RuntimeException('Locked Journal cannot be changed.');$this->assertTransition($from,$to==Journal::STATUS_DRAFT&&$event==='rejected'?Journal::STATUS_REJECTED:$to);$j->update(array_merge(['status'=>$to],$extra($j)));$this->audit($j,$event,$from,$to,$actorId,$reason,$metadata);return $j->fresh();});}

    private function actorMayBypassMakerCheckerSeparation(int $actorId, int $companyId): bool
    {
        return User::query()
            ->whereKey($actorId)
            ->where('company_id', $companyId)
            ->where('role_id', Role::COMPANY_ADMIN_ID)
            ->exists();
    }

    private function assertMakerCheckerSeparationForApprove(Journal $journal, int $actorId): void
    {
        if ($this->actorMayBypassMakerCheckerSeparation($actorId, (int) $journal->company_id)) {
            return;
        }

        if ((int) $journal->created_by === $actorId || (int) $journal->submitted_by === $actorId) {
            throw new RuntimeException('Maker/checker separation prevents self-approval.');
        }
    }

    private function assertMakerCheckerSeparationForPost(Journal $journal, int $actorId): void
    {
        if ($this->actorMayBypassMakerCheckerSeparation($actorId, (int) $journal->company_id)) {
            return;
        }

        if (in_array($actorId, [(int) $journal->created_by, (int) $journal->approved_by], true)) {
            throw new RuntimeException('Maker/checker/poster segregation prevents this posting.');
        }
    }

    private function validateExisting(Journal $j):void{$this->validateFinancialContext($j->company_id,$j->financial_year_id,$j->journal_date->format('Y-m-d'));$lines=$j->items()->orderBy('line_number')->get()->map(fn($i)=>['chart_account_id'=>$i->chart_account_id,'account_id'=>$i->account_id,'debit'=>$i->debit,'credit'=>$i->credit,'description'=>$i->description,'reference'=>$i->reference,'subledger_type'=>$i->sub_ledger_type,'subledger_id'=>$i->sub_ledger_id])->all();$this->validateLines($lines,$j->company_id);}
    private function accountingLines(Journal $j):array{return $j->items->map(fn($i)=>['chart_account_id'=>$i->chart_account_id,'operational_account_id'=>$i->account_id,'debit'=>$i->debit,'credit'=>$i->credit,'description'=>$i->description,'subledger_type'=>$i->sub_ledger_type,'subledger_id'=>$i->sub_ledger_id])->all();}
    private function createAuxiliary(Journal $j,int $actorId):void{foreach($j->items as $i){$common=['company_id'=>$j->company_id,'financial_year_id'=>$j->financial_year_id,'transaction_date'=>$j->journal_date->format('Y-m-d'),'voucher_no'=>$j->journal_no,'reference_type'=>'ManualJournal','reference_id'=>$j->id,'journal_item_id'=>$i->id,'reference_no'=>$j->reference_no,'description'=>$i->description?:$j->description,'debit'=>$i->debit,'credit'=>$i->credit,'created_by'=>$actorId,'status'=>1];if($i->account_id)AccountBalanceService::createTransaction($common+['account_id'=>$i->account_id],false);if($i->sub_ledger_type==='customer'){$this->validateSubledger($j,$i,'customer');CustomerTransactionService::createTransaction($common+['customer_id'=>$i->sub_ledger_id]);}elseif($i->sub_ledger_type==='supplier'){$this->validateSubledger($j,$i,'supplier');SupplierTransactionService::createTransaction($common+['supplier_id'=>$i->sub_ledger_id]);}elseif($i->sub_ledger_type)throw new RuntimeException('Unsupported Journal subledger type.');}}
    private function validateSubledger(Journal $j,$i,string $type):void{$code=$type==='customer'?'ACCOUNTS_RECEIVABLE':'ACCOUNTS_PAYABLE';$chart=ChartAccount::where('company_id',$j->company_id)->findOrFail($i->chart_account_id);if($chart->system_code!==$code)throw new RuntimeException(ucfirst($type).' subledger requires the '.$code.' control Chart Account.');$model=$type==='customer'?Customer::class:Supplier::class;if(!$model::where('company_id',$j->company_id)->whereKey($i->sub_ledger_id)->exists())throw new RuntimeException('Invalid company subledger identity.');}
    private function reverseAuxiliary(Journal $o,Journal $r,int $actor):void{foreach($o->items as $idx=>$item){$ri=$r->items[$idx];$base=['company_id'=>$o->company_id,'financial_year_id'=>$o->financial_year_id,'transaction_date'=>$o->journal_date->format('Y-m-d'),'voucher_no'=>$r->journal_no,'reference_type'=>'ManualJournal','reference_id'=>$r->id,'journal_item_id'=>$ri->id,'description'=>'Reversal of '.$o->journal_no,'created_by'=>$actor,'status'=>1];if($item->account_id){$q=AccountTransaction::where('company_id',$o->company_id)->where('reference_type','ManualJournal')->where('reference_id',$o->id)->where('journal_item_id',$item->id)->where('status',1)->lockForUpdate()->get();if($q->count()!==1||AccountTransaction::where('reversed_transaction_id',$q->first()?->id)->exists())throw new RuntimeException('Original AccountTransaction is missing, duplicated, or already reversed.');AccountBalanceService::createTransaction($base+['account_id'=>$item->account_id,'reversed_transaction_id'=>$q->first()->id,'debit'=>$q->first()->credit,'credit'=>$q->first()->debit],false);}foreach([['customer',CustomerTransaction::class,CustomerTransactionService::class,'customer_id'],['supplier',SupplierTransaction::class,SupplierTransactionService::class,'supplier_id']] as [$type,$model,$service,$key])if($item->sub_ledger_type===$type){$q=$model::where('company_id',$o->company_id)->where('reference_type','ManualJournal')->where('reference_id',$o->id)->where('journal_item_id',$item->id)->where('status',1)->lockForUpdate()->get();if($q->count()!==1||$model::where('reversed_transaction_id',$q->first()?->id)->exists())throw new RuntimeException('Original subledger transaction is missing, duplicated, or already reversed.');$service::createTransaction($base+[$key=>$item->sub_ledger_id,'reversed_transaction_id'=>$q->first()->id,'debit'=>$q->first()->credit,'credit'=>$q->first()->debit]);}}}
    private function reason(string $reason,string $label):string{$reason=trim($reason);if($reason==='')throw ValidationException::withMessages(['reason'=>"{$label} reason is required."]);return $reason;}
    private function assertManualJournal(Journal $journal):void{if($journal->source_module&&$journal->source_module!=='journal')throw new RuntimeException('Source-generated Journals cannot be changed from the Manual Journal module.');}

    private function validateFinancialContext(int $companyId, int $financialYearId, string $date): FinancialYear
    {
        $companyQuery = Company::whereKey($companyId);
        if (Schema::hasColumn('companies', 'status')) {
            $companyQuery->where('status', 'active');
        }
        if (!$companyQuery->exists()) {
            throw ValidationException::withMessages(['company_id' => 'The company must be active for Journal processing.']);
        }
        $fy = FinancialYear::where('company_id', $companyId)->whereKey($financialYearId)->where('is_active', 1)->first();
        if (!$fy || (Schema::hasColumn('financial_years', 'is_closed') && $fy->is_closed) || (Schema::hasColumn('financial_years', 'is_locked') && $fy->is_locked)) {
            throw ValidationException::withMessages(['financial_year_id' => 'Select an active, open, unlocked company Financial Year.']);
        }
        $businessDate = CarbonImmutable::createFromFormat('Y-m-d', $date)->format('Y-m-d');
        if ($businessDate < $fy->start_date || $businessDate > $fy->end_date) throw ValidationException::withMessages(['journal_date' => 'Business Date must be inside the selected Financial Year.']);
        if (Schema::hasTable('accounting_period_locks') && AccountingPeriodLock::where('company_id', $companyId)->where('financial_year_id', $fy->id)->where('is_locked', 1)->whereDate('date_from', '<=', $businessDate)->whereDate('date_to', '>=', $businessDate)->exists()) {
            throw ValidationException::withMessages(['journal_date' => 'Business Date belongs to a locked accounting period.']);
        }
        return $fy;
    }

    private function validateLines(array $lines, int $companyId): array
    {
        if (count($lines) < 2) throw ValidationException::withMessages(['lines' => 'At least two Journal lines are required.']);
        $debit = 0; $credit = 0;
        foreach ($lines as $index => &$line) {
            $account = ChartAccount::where('company_id', $companyId)->whereKey($line['chart_account_id'])->where('level', 3)->where('allow_manual_entry', 1)->where('status', 'active')->when(Schema::hasColumn('chart_accounts', 'is_locked'), fn ($q) => $q->where('is_locked', 0))->first();
            if (!$account) throw ValidationException::withMessages(["lines.{$index}.chart_account_id" => 'Select an active, unlocked Level 3 posting Chart Account from this company.']);
            $this->validateOperationalAccountLine($account, isset($line['account_id']) ? (int) $line['account_id'] : null, $companyId, $index);
            $d = $this->scaled($line['debit']); $c = $this->scaled($line['credit']);
            if (($d > 0 && $c > 0) || ($d === 0 && $c === 0)) throw ValidationException::withMessages(["lines.{$index}" => 'Each line requires either Debit or Credit, never both.']);
            $debit += $d; $credit += $c; $line['_debit'] = $d; $line['_credit'] = $c;
        }
        unset($line);
        if ($debit !== $credit) throw ValidationException::withMessages(['lines' => 'Total Debit must equal Total Credit to four decimal places.']);
        return $lines;
    }

    private function validateOperationalAccountLine(ChartAccount $chartAccount, ?int $accountId, int $companyId, int $index): void
    {
        $requiredCode = in_array($chartAccount->system_code, ['CASH_IN_HAND', 'BANK_ACCOUNTS'], true)
            ? $chartAccount->system_code
            : null;

        if (!$requiredCode) {
            if ($accountId) {
                throw ValidationException::withMessages(["lines.{$index}.account_id" => 'Operational Account is only allowed for Cash or Bank Chart Accounts.']);
            }

            return;
        }

        if (!$accountId) {
            throw ValidationException::withMessages(["lines.{$index}.account_id" => 'Select an operational account for this Cash or Bank Chart Account.']);
        }

        $operational = Account::where('company_id', $companyId)->whereKey($accountId)->whereIn('status', [1, 'active'])->first();
        if (!$operational) {
            throw ValidationException::withMessages(["lines.{$index}.account_id" => 'The operational Account is invalid for this company.']);
        }

        $expectedCode = match ($operational->account_type) {
            'Cash' => 'CASH_IN_HAND',
            'Bank', 'ATM', 'Wallet' => 'BANK_ACCOUNTS',
            default => null,
        };

        if ($expectedCode !== $requiredCode) {
            throw ValidationException::withMessages(["lines.{$index}.account_id" => 'Operational Account must match its required Cash or Bank Chart Account.']);
        }
    }

    private function replaceLines(Journal $journal, array $lines): void
    {
        foreach ($lines as $i => $line) {
            $debit = $this->decimal($line['_debit']); $credit = $this->decimal($line['_credit']);
            $journal->items()->create([
                'company_id' => $journal->company_id, 'chart_account_id' => $line['chart_account_id'],
                'account_id' => $line['account_id'] ?? null, 'debit' => $debit, 'credit' => $credit,
                'type' => $line['_debit'] > 0 ? 'debit' : 'credit', 'amount' => $line['_debit'] > 0 ? $debit : $credit,
                'description' => $line['description'] ?? null, 'reference' => $line['reference'] ?? null,
                'note' => $line['description'] ?? null, 'line_number' => $i + 1,
                'sub_ledger_type' => $line['subledger_type'] ?? null, 'sub_ledger_id' => $line['subledger_id'] ?? null, 'status' => 1,
            ]);
        }
    }

    private function nextNumber(int $companyId, FinancialYear $fy): string
    {
        FinancialYear::whereKey($fy->id)->lockForUpdate()->firstOrFail();
        $sequence = DB::table('journal_number_sequences')->where('company_id', $companyId)->where('financial_year_id', $fy->id)->lockForUpdate()->first();
        if (!$sequence) {
            DB::table('journal_number_sequences')->insert(['company_id' => $companyId, 'financial_year_id' => $fy->id, 'next_number' => 2, 'created_at' => now(), 'updated_at' => now()]);
            $number = 1;
        } else {
            $number = (int) $sequence->next_number;
            DB::table('journal_number_sequences')->where('company_id', $companyId)->where('financial_year_id', $fy->id)->update(['next_number' => $number + 1, 'updated_at' => now()]);
        }
        return 'JRN-' . $companyId . '-' . $fy->id . '-' . str_pad((string) $number, 8, '0', STR_PAD_LEFT);
    }

    private function audit(Journal $journal, string $event, ?string $previous, ?string $new, int $actorId, ?string $reason=null, ?array $metadata=null): void
    {
        JournalAuditEvent::create(['company_id' => $journal->company_id, 'financial_year_id' => $journal->financial_year_id, 'journal_id' => $journal->id, 'event' => $event, 'previous_status' => $previous, 'new_status' => $new, 'actor_id' => $actorId, 'event_at' => now(),'reason'=>$reason,'metadata'=>$metadata]);
    }

    private function scaled(mixed $value): int
    {
        $text = trim((string) $value);
        if (!preg_match('/^\d{1,16}(?:\.(\d{1,4}))?$/', $text, $m)) throw ValidationException::withMessages(['lines' => 'Amounts must be non-negative with no more than four decimal places.']);
        [$whole, $fraction] = array_pad(explode('.', $text, 2), 2, '');
        return ((int) $whole * 10000) + (int) str_pad($fraction, 4, '0');
    }

    private function decimal(int $scaled): string { return intdiv($scaled, 10000) . '.' . str_pad((string) ($scaled % 10000), 4, '0', STR_PAD_LEFT); }
    private function totalDebit(array $lines): string { return $this->decimal(array_sum(array_column($lines, '_debit'))); }
}
