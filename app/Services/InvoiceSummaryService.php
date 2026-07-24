<?php

namespace App\Services;

class InvoiceSummaryService
{
    /**
     * Sum line base amounts (quantity × unit price) for VAT-applicable items only.
     * Excludes No VAT and zero-rate lines. Display-only; does not affect saved totals.
     */
    public static function calculateTaxableAmount(iterable $items): float
    {
        $taxableAmount = 0.0;

        foreach ($items as $item) {
            if ((float) $item->vat_rate <= 0) {
                continue;
            }

            $taxableAmount += (float) $item->quantity * (float) $item->unit_price;
        }

        return $taxableAmount;
    }
}
