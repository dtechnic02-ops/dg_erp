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
        if (! $refund->exists || ! $refund->isActive()) throw new RuntimeException('Only an active persisted Purchase Return Refund can be posted.');
        $companyId=(int)$refund->company_id; $refundId=(int)$refund->id;
        $return=PurchaseReturn::where('company_id',$companyId)->find($refund->purchase_return_id);
        $fy=FinancialYear::where('company_id',$companyId)->where('is_active',1)->find($refund->financial_year_id);
        $date=$refund->refund_date?->format('Y-m-d');
        $start=$fy?->start_date instanceof \DateTimeInterface?$fy->start_date->format('Y-m-d'):(string)$fy?->start_date;
        $end=$fy?->end_date instanceof \DateTimeInterface?$fy->end_date->format('Y-m-d'):(string)$fy?->end_date;
        if(!$return || !$fy || (int)$return->status!==1 || !$date || $date<$start || $date>$end) throw new RuntimeException('Purchase Return Refund Financial Year or Business Date validation failed.');

        $returnEntry=AccountingEntry::where('company_id',$companyId)->where('source_key','purchase_return:'.$return->id.':created')->where('status','posted')->whereNull('reversal_of_id')->first();
        if(!$returnEntry) throw new RuntimeException('The posted Purchase Return Accounting Entry is required before settlement.');

        $adjust=$this->money($refund->adjust_amount); $cash=$this->money($refund->cash_amount); $settlement=$this->money($refund->refund_amount);
        if(bccomp(bcadd($adjust,$cash,4),$settlement,4)!==0 || bccomp($settlement,'0.0000',4)<=0) throw new RuntimeException('Purchase Return Refund settlement is invalid.');

        $adjustments=PurchaseReturnRefundAdjustment::where('company_id',$companyId)->where('purchase_return_refund_id',$refundId)->where('status',1)->sum('adjust_amount');
        if(bccomp($this->money($adjustments),$adjust,4)!==0) throw new RuntimeException('Purchase Return Refund adjustments do not equal the persisted adjustment amount.');

        $account=null;
        if(bccomp($cash,'0.0000',4)>0){
            $account=Account::where('company_id',$companyId)->where('status','active')->find($refund->account_id);
            if(!$account||!in_array($account->account_type,['Cash','Bank','ATM','Wallet'],true)) throw new RuntimeException('Purchase Return Refund account is invalid.');
            $transaction=AccountTransaction::where('company_id',$companyId)->where('reference_type','purchase_return_refund')->where('reference_id',$refundId)->where('status',1)->first();
            if(!$transaction||bccomp($this->money($transaction->debit),$cash,4)!==0||bccomp($this->money($transaction->credit ?? 0),'0.0000',4)!==0) throw new RuntimeException('Purchase Return cash settlement does not match its AccountTransaction.');
        }

        $supplier=SupplierTransaction::where('company_id',$companyId)->where('reference_id',$refundId)->whereIn('reference_type',['purchase_return_refund','purchase_return_refund_adjustment'])->where('status',1)->sum('debit');
        if(bccomp($this->money($supplier),$settlement,4)!==0) throw new RuntimeException('Purchase Return supplier settlement does not match the Refund.');

        return ['company_id'=>$companyId,'financial_year_id'=>(int)$fy->id,'refund_id'=>$refundId,'refund_date'=>$date,'refund_number'=>$refund->refund_no,
            'supplier_id'=>(int)$refund->supplier_id,'created_by'=>$refund->created_by,'adjust_amount'=>$adjust,'cash_amount'=>$cash,
            'settlement_amount'=>$settlement,'account_id'=>$account?->id,'account_type'=>$account?->account_type];
    }

    private function money(mixed $value): string
    {
        $value=trim((string)$value);
        if(!preg_match('/^\d+(?:\.\d{1,4})?$/',$value)) throw new RuntimeException('A Purchase Return settlement amount is invalid.');
        [$whole,$fraction]=array_pad(explode('.',$value,2),2,'');
        return (ltrim($whole,'0')?:'0').'.'.str_pad($fraction,4,'0');
    }
}
