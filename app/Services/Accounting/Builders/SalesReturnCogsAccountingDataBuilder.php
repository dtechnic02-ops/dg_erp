<?php

namespace App\Services\Accounting\Builders;

use App\Models\SalesCostSnapshot;
use App\Models\SalesReturn;
use DateTimeInterface;
use RuntimeException;

class SalesReturnCogsAccountingDataBuilder
{
    public function build(SalesReturn $return): array
    {
        $items = $return->items()->where('company_id', $return->company_id)->where('status', 1)->whereNotNull('product_id')->orderBy('product_id')->get();
        if ($items->isEmpty()) throw new RuntimeException('The sales return has no product cost restoration to post.');
        $amount = '0.0000';
        foreach ($items as $item) {
            $snapshot = SalesCostSnapshot::where('company_id', $return->company_id)->where('sales_item_id', $item->sales_item_id)->first();
            if (! $snapshot) throw new RuntimeException('The original sales cost snapshot is required for every returned product.');
            $amount = bcadd($amount, bcmul((string) $item->quantity, (string) $snapshot->movement_unit_cost, 4), 4);
        }
        if (bccomp($amount, '0', 4) <= 0) throw new RuntimeException('The sales return COGS amount must be greater than zero.');
        $date = $return->return_date instanceof DateTimeInterface ? $return->return_date->format('Y-m-d') : (string) $return->return_date;
        return ['company_id' => $return->company_id, 'entry_date' => $date, 'reference_number' => $return->return_no, 'source_module' => 'sales_return_cogs', 'source_type' => 'sales_return_cogs', 'source_id' => $return->id, 'source_event' => 'created', 'source_key' => 'sales-return-cogs:' . $return->id . ':created', 'description' => 'Sales return inventory restoration - ' . $return->return_no, 'posted_by' => $return->created_by, 'lines' => [
            ['chart_account_system_code' => 'INVENTORY', 'operational_account_id' => null, 'description' => 'Inventory restored on sales return', 'debit' => $amount, 'credit' => '0.0000', 'subledger_type' => null, 'subledger_id' => null],
            ['chart_account_system_code' => 'COST_OF_GOODS_SOLD', 'operational_account_id' => null, 'description' => 'Cost of goods sold reversal', 'debit' => '0.0000', 'credit' => $amount, 'subledger_type' => null, 'subledger_id' => null],
        ]];
    }
    public function hasProductItems(SalesReturn $return): bool { return $return->items()->where('company_id', $return->company_id)->where('status', 1)->whereNotNull('product_id')->exists(); }
}
