<?php

namespace App\Services\Cbms;

class CbmsTaxAmountMapper
{
    public function map(array $reconciliation): array
    {
        return [
            'total_sales' => round((float) $reconciliation['grand_total'], 2),
            'taxable_sales_vat' => round((float) $reconciliation['taxable_sales'], 2),
            'vat' => round((float) $reconciliation['vat_total'], 2),
            'excisable_amount' => 0.0,
            'excise' => 0.0,
            'taxable_sales_hst' => 0.0,
            'hst' => 0.0,
            'amount_for_esf' => 0.0,
            'esf' => 0.0,
            'export_sales' => round((float) $reconciliation['export_sales'], 2),
            'tax_exempted_sales' => round((float) $reconciliation['exempt_sales'], 2),
        ];
    }
}
