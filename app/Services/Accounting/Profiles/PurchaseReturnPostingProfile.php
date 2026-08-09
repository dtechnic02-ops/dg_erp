<?php

namespace App\Services\Accounting\Profiles;

use App\Models\PurchaseReturn;
use RuntimeException;

class PurchaseReturnPostingProfile
{
    public function build(array $data): array
    {
        $product = $data['product_net']; $service = $data['service_net']; $tax = $data['tax']; $total = $data['total'];
        if (bccomp(bcadd(bcadd($product, $service, 4), $tax, 4), $total, 4) !== 0 || bccomp($total, '0.0000', 4) <= 0) {
            throw new RuntimeException('Purchase Return accounting values are not balanced.');
        }

        $lines = [[
            'chart_account_system_code' => 'SUPPLIER_RETURN_RECEIVABLE', 'operational_account_id' => null,
            'description' => 'Unsettled Purchase Return - ' . $data['return_number'], 'debit' => $total, 'credit' => '0.0000',
            'subledger_type' => null, 'subledger_id' => null,
        ]];
        if ($product !== '0.0000') $lines[] = $this->credit('INVENTORY', 'Returned inventory', $product, $data['return_number']);
        if ($service !== '0.0000') $lines[] = $this->credit($data['service_account_system_code'], 'Returned service value', $service, $data['return_number']);
        if ($tax !== '0.0000') $lines[] = $this->credit('INPUT_TAX_RECEIVABLE', 'Returned input tax', $tax, $data['return_number']);

        return [
            'company_id' => $data['company_id'], 'financial_year_id' => $data['financial_year_id'],
            'entry_date' => $data['return_date'], 'reference_number' => $data['return_number'],
            'source_module' => 'purchase_return', 'source_type' => 'purchase_return', 'source_type_aliases' => [PurchaseReturn::class],
            'source_id' => $data['return_id'], 'source_event' => 'created',
            'source_key' => 'purchase_return:' . $data['return_id'] . ':created',
            'description' => 'Purchase Return - ' . $data['return_number'], 'posted_by' => $data['created_by'], 'lines' => $lines,
        ];
    }

    private function credit(string $code, string $description, string $amount, string $number): array
    {
        return ['chart_account_system_code'=>$code,'operational_account_id'=>null,'description'=>$description.' - '.$number,'debit'=>'0.0000','credit'=>$amount,'subledger_type'=>null,'subledger_id'=>null];
    }
}
