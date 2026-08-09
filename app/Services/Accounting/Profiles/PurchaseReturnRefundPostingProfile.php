<?php

namespace App\Services\Accounting\Profiles;

use App\Models\PurchaseReturnRefund;
use RuntimeException;

class PurchaseReturnRefundPostingProfile
{
    public function build(array $data): array
    {
        $adjust=$data['adjust_amount']; $cash=$data['cash_amount']; $settlement=$data['settlement_amount'];
        if(bccomp(bcadd($adjust,$cash,4),$settlement,4)!==0 || bccomp($settlement,'0.0000',4)<=0) throw new RuntimeException('Purchase Return Refund accounting is not balanced.');
        $lines=[];
        if($adjust!=='0.0000') $lines[]=['chart_account_system_code'=>'ACCOUNTS_PAYABLE','operational_account_id'=>null,'description'=>'Supplier payable adjustment - '.$data['refund_number'],'debit'=>$adjust,'credit'=>'0.0000','subledger_type'=>'supplier','subledger_id'=>$data['supplier_id']];
        if($cash!=='0.0000') $lines[]=['chart_account_system_code'=>$data['account_type']==='Cash'?'CASH_IN_HAND':'BANK_ACCOUNTS','operational_account_id'=>$data['account_id'],'description'=>'Purchase Return cash receipt - '.$data['refund_number'],'debit'=>$cash,'credit'=>'0.0000','subledger_type'=>null,'subledger_id'=>null];
        $lines[]=['chart_account_system_code'=>'SUPPLIER_RETURN_RECEIVABLE','operational_account_id'=>null,'description'=>'Purchase Return settlement clearing - '.$data['refund_number'],'debit'=>'0.0000','credit'=>$settlement,'subledger_type'=>null,'subledger_id'=>null];

        return ['company_id'=>$data['company_id'],'financial_year_id'=>$data['financial_year_id'],'entry_date'=>$data['refund_date'],'reference_number'=>$data['refund_number'],
            'source_module'=>'purchase_return_refund','source_type'=>'purchase_return_refund','source_type_aliases'=>[PurchaseReturnRefund::class],
            'source_id'=>$data['refund_id'],'source_event'=>'created','source_key'=>'purchase_return_refund:'.$data['refund_id'].':created',
            'description'=>'Purchase Return Refund settlement - '.$data['refund_number'],'posted_by'=>$data['created_by'],'lines'=>$lines];
    }
}
