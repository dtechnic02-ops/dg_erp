<?php

namespace App\Services\Accounting\Builders;

use App\Models\AccountingEntry;
use App\Models\FinancialYear;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturn;
use App\Services\Accounting\PurchaseReturnValuationService;
use RuntimeException;

class PurchaseReturnAccountingDataBuilder
{
    public function __construct(private readonly PurchaseReturnValuationService $valuation) {}

    public function build(PurchaseReturn $return): array
    {
        if (! $return->exists || (int) $return->status !== 1) throw new RuntimeException('Only an active persisted Purchase Return can be posted.');
        $companyId = (int) $return->company_id;
        $invoice = PurchaseInvoice::where('company_id', $companyId)->with('items')->find($return->purchase_invoice_id);
        if (! $invoice || (int) $invoice->status !== 1 || (int) $invoice->supplier_id !== (int) $return->supplier_id) throw new RuntimeException('The Purchase Return and original Purchase are inconsistent.');

        $fy = FinancialYear::where('company_id', $companyId)->where('is_active', 1)->find($return->financial_year_id);
        $date = $return->return_date?->format('Y-m-d');
        $start=$fy?->start_date instanceof \DateTimeInterface?$fy->start_date->format('Y-m-d'):(string)$fy?->start_date;
        $end=$fy?->end_date instanceof \DateTimeInterface?$fy->end_date->format('Y-m-d'):(string)$fy?->end_date;
        if (! $fy || ! $date || $date < $start || $date > $end || (int) $invoice->financial_year_id !== (int) $fy->id) {
            throw new RuntimeException('Purchase Return Financial Year or Business Date validation failed.');
        }

        $items = $return->items()->where('status', 1)->get();
        if ($items->isEmpty()) throw new RuntimeException('The Purchase Return has no active lines.');
        $quantities = $items->mapWithKeys(fn ($item) => [(int) $item->purchase_item_id => $item->quantity])->all();
        $values = $this->valuation->calculate($invoice, $quantities, true);

        if (! $this->same(bcadd($values['product_net'], $values['service_net'], 4), $return->subtotal)
            || ! $this->same($values['tax'], $return->total_vat)
            || ! $this->same($values['total'], $return->grand_total)) {
            throw new RuntimeException('Purchase Return persisted values do not match the approved original Purchase basis.');
        }

        $original = AccountingEntry::where('company_id', $companyId)
            ->where('source_key', 'purchase:' . $invoice->id . ':created')
            ->where('status', 'posted')->whereNull('reversal_of_id')->with('lines.chartAccount')->first();
        if (! $original) throw new RuntimeException('The original Purchase Accounting Entry is required for Purchase Return recognition.');

        $codes = $original->lines->pluck('chartAccount.system_code')->filter()->all();
        if ($values['product_net'] !== '0.0000' && ! in_array('INVENTORY', $codes, true)) throw new RuntimeException('The original Purchase does not contain the approved Inventory value account.');
        if ($values['service_net'] !== '0.0000' && ! in_array('SERVICE_PURCHASE_EXPENSE', $codes, true)) throw new RuntimeException('The original persisted service value account cannot be resolved safely.');
        if ($values['tax'] !== '0.0000' && ! in_array('INPUT_TAX_RECEIVABLE', $codes, true)) throw new RuntimeException('The original Purchase does not contain recoverable input tax evidence.');

        return [
            'company_id' => $companyId,
            'financial_year_id' => (int) $fy->id,
            'return_id' => (int) $return->id,
            'return_date' => $date,
            'return_number' => (string) $return->return_no,
            'supplier_id' => (int) $return->supplier_id,
            'created_by' => $return->created_by,
            'product_net' => $values['product_net'],
            'service_net' => $values['service_net'],
            'tax' => $values['tax'],
            'total' => $values['total'],
            'service_account_system_code' => $values['service_net'] === '0.0000' ? null : 'SERVICE_PURCHASE_EXPENSE',
        ];
    }

    private function same(mixed $left, mixed $right): bool { return bccomp((string) $left, (string) $right, 2) === 0; }
}
