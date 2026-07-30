<?php

namespace App\Services\Accounting\Builders;

use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\AccountingEntry;
use App\Models\FinancialYear;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnRefund;
use App\Models\PurchaseReturnRefundAdjustment;
use App\Models\SupplierTransaction;
use RuntimeException;

class PurchaseReturnRefundAccountingDataBuilder
{
    public function build(PurchaseReturnRefund $refund): array
    {
        if (! $refund->exists || ! $refund->isActive()) throw new RuntimeException('Only an active persisted purchase return refund can be posted.');
        $companyId=(int)$refund->company_id; $refundId=(int)$refund->id;
        $return=PurchaseReturn::where('company_id',$companyId)->find($refund->purchase_return_id);
        $fy=FinancialYear::where('company_id',$companyId)->find($refund->financial_year_id);
        if(!$return || !$fy || (int)$return->status!==1 || $refund->refund_date < $fy->start_date || $refund->refund_date > $fy->end_date) throw new RuntimeException('Purchase return refund financial-year validation failed.');
        $adjust=$this->money($refund->adjust_amount);$cash=$this->money($refund->cash_amount);$settlement=$this->money($refund->refund_amount);
        if(bccomp(bcadd($adjust,$cash,4),$settlement,4)!==0 || bccomp($settlement,'0.0000',4)<=0)throw new RuntimeException('Purchase return refund settlement is invalid.');
        $adjustments=PurchaseReturnRefundAdjustment::where('company_id',$companyId)->where('purchase_return_refund_id',$refundId)->where('status',1)->sum('adjust_amount');
        if(bccomp($this->money($adjustments),$adjust,4)!==0)throw new RuntimeException('Purchase return refund adjustments do not equal persisted adjust amount.');
        $account=null;if(bccomp($cash,'0.0000',4)>0){$account=Account::where('company_id',$companyId)->where('status','active')->find($refund->account_id);if(!$account||!in_array($account->account_type,['Cash','Bank','ATM','Wallet'],true))throw new RuntimeException('Purchase return refund account is invalid.');$transaction=AccountTransaction::where('company_id',$companyId)->where('reference_type','purchase_return_refund')->where('reference_id',$refundId)->where('status',1)->first();if(!$transaction||bccomp($this->money($transaction->debit),$cash,4)!==0)throw new RuntimeException('Purchase return cash settlement does not match account transaction.');}
        $supplier=SupplierTransaction::where('company_id',$companyId)->where('reference_id',$refundId)->whereIn('reference_type',['purchase_return_refund','purchase_return_refund_adjustment'])->where('status',1)->sum('debit');if(bccomp($this->money($supplier),$settlement,4)!==0)throw new RuntimeException('Purchase return supplier settlement does not match refund.');
        $fullTax=$this->money($return->total_vat);$fullNet=bcsub($this->money($return->grand_total),$fullTax,4);
        $prior=PurchaseReturnRefund::where('company_id',$companyId)->where('purchase_return_id',$return->id)->active()->where('id','!=',$refundId)->pluck('id')->all();$priorNet='0.0000';$priorTax='0.0000';
        if($prior){$entries=AccountingEntry::where('company_id',$companyId)->whereIn('source_type',['purchase_return_refund',PurchaseReturnRefund::class])->whereIn('source_id',$prior)->where('source_event','created')->where('status','posted')->whereNull('reversal_of_id')->with('lines.chartAccount')->get();if($entries->count()!==count($prior))throw new RuntimeException('A prior active purchase return refund has no posted accounting entry.');foreach($entries as $entry)foreach($entry->lines as $line){if($line->chartAccount?->system_code==='PURCHASE_RETURNS')$priorNet=bcadd($priorNet,$this->money($line->credit),4);if($line->chartAccount?->system_code==='INPUT_TAX_RECEIVABLE')$priorTax=bcadd($priorTax,$this->money($line->credit),4);}}
        $cumulative=bcadd($settlement,'0.0000',4);foreach(PurchaseReturnRefund::where('company_id',$companyId)->where('purchase_return_id',$return->id)->active()->where('id','!=',$refundId)->get() as $r)$cumulative=bcadd($cumulative,$this->money($r->refund_amount),4);$final=bccomp($cumulative,$this->money($return->grand_total),4)===0;
        $net=$final?bcsub($fullNet,$priorNet,4):bcdiv(bcmul($fullNet,$settlement,8),$this->money($return->grand_total),4);$tax=$final?bcsub($fullTax,$priorTax,4):bcsub($settlement,$net,4);if(bccomp(bcadd($net,$tax,4),$settlement,4)!==0)throw new RuntimeException('Purchase return accounting components are not balanced.');
        return ['company_id'=>$companyId,'refund_id'=>$refundId,'refund_date'=>$refund->refund_date->format('Y-m-d'),'refund_number'=>$refund->refund_no,'supplier_id'=>(int)$refund->supplier_id,'created_by'=>$refund->created_by,'adjust_amount'=>$adjust,'cash_amount'=>$cash,'settlement_amount'=>$settlement,'account_id'=>$account?->id,'account_type'=>$account?->account_type,'components'=>['net'=>$net,'tax'=>$tax]];
    }
    private function money(mixed $v):string{return number_format((float)$v,4,'.','');}
}
