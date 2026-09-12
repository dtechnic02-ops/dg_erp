<?php

namespace App\Services;

use App\Models\SalesInvoice;
use App\Models\SalesReturn;
use Illuminate\Validation\ValidationException;

class SalesFiscalReconciliationService
{
    private const TOLERANCE = 0.001;

    public function __construct(
        private readonly SalesFiscalLineAmountService $lineAmounts,
        private readonly SalesTaxClassificationService $taxClassifications,
    ) {
    }

    public function reconcile(SalesInvoice $invoice): array
    {
        $invoice->loadMissing('items');

        return $this->reconcileLines($invoice->items, [
            'subtotal' => $invoice->subtotal,
            'discount' => $invoice->discount,
            'vat_total' => $invoice->total_vat,
            'grand_total' => $invoice->grand_total,
        ], true);
    }

    public function reconcileReturn(SalesReturn $return): array
    {
        $return->loadMissing('items');

        return $this->reconcileLines($return->items, [
            'subtotal' => $return->subtotal,
            'vat_total' => $return->total_vat,
            'grand_total' => $return->grand_total,
        ], false);
    }

    private function reconcileLines(iterable $items, array $header, bool $validateIssuedLineFormula): array
    {
        $values = array_fill_keys([
            'gross_sales', 'total_discount', 'net_sales', 'taxable_sales', 'exempt_sales',
            'zero_rated_sales', 'export_sales', 'out_of_scope_sales', 'vat_total', 'grand_total',
        ], 0.0);
        $errors = [];
        $lineResults = [];

        foreach ($items as $index => $item) {
            $line = $index + 1;
            if ($item->fiscal_discount_amount === null) {
                $errors[] = "Sales line {$line} has no authoritative fiscal discount evidence.";
            }
            if ($item->fiscal_net_base === null) {
                $errors[] = "Sales line {$line} has no authoritative fiscal net-base evidence.";
            }

            $classification = trim((string) $item->tax_classification);
            if ($classification === '') {
                $errors[] = "Sales line {$line} has no authoritative tax classification.";
            } elseif ($classification === SalesTaxClassificationService::LEGACY_UNCLASSIFIED) {
                $errors[] = "Sales line {$line} is legacy_unclassified and cannot be fiscally reconciled.";
            } elseif (! in_array($classification, SalesTaxClassificationService::SELECTABLE, true)) {
                $errors[] = "Sales line {$line} has an invalid tax classification.";
            }

            if ($item->fiscal_discount_amount === null || $item->fiscal_net_base === null) {
                continue;
            }

            $quantity = (float) $item->quantity;
            $unitPrice = (float) $item->unit_price;
            $discount = (float) $item->fiscal_discount_amount;
            $netBase = (float) $item->fiscal_net_base;
            $vat = (float) $item->vat_amount;
            $lineTotal = (float) $item->total_price;
            $vatRate = (float) $item->vat_rate;

            if (min($quantity, $unitPrice, $discount, $netBase, $vat, $lineTotal, $vatRate) < 0) {
                $errors[] = "Sales line {$line} contains a negative fiscal amount.";
                continue;
            }

            if ($validateIssuedLineFormula) {
                try {
                    $expected = $this->lineAmounts->calculate($quantity, $unitPrice, $discount, $vatRate);
                    $gross = $expected['gross_base'];
                    if (! $this->same($expected['net_base'], $netBase)
                        || ! $this->same($expected['vat_amount'], $vat)
                        || ! $this->same($expected['line_total'], $lineTotal)) {
                        $errors[] = "Sales line {$line} does not reconcile to its frozen fiscal amounts.";
                    }
                } catch (\InvalidArgumentException) {
                    $gross = round($quantity * $unitPrice, 2);
                    $errors[] = "Sales line {$line} contains invalid fiscal amount evidence.";
                }
            } else {
                $gross = round($netBase + $discount, 2);
                if (! $this->same($netBase + $vat, $lineTotal)) {
                    $errors[] = "Sales return line {$line} net base and VAT do not equal its frozen total.";
                }
            }

            if (in_array($classification, SalesTaxClassificationService::SELECTABLE, true)) {
                try {
                    $this->taxClassifications->normalizeForNewLine($classification, $vatRate, true);
                } catch (ValidationException) {
                    $errors[] = "Sales line {$line} tax classification and VAT rate are inconsistent.";
                }
            }

            $lineResults[$item->getKey()] = [
                'gross_amount' => $gross,
                'discount_amount' => $discount,
                'net_base' => $netBase,
                'tax_classification' => $classification,
                'vat_rate' => $vatRate,
                'vat_amount' => $vat,
                'line_total' => $lineTotal,
            ];

            $values['gross_sales'] = $this->sum($values['gross_sales'], $gross);
            $values['total_discount'] = $this->sum($values['total_discount'], $discount);
            $values['net_sales'] = $this->sum($values['net_sales'], $netBase);
            $values['vat_total'] = $this->sum($values['vat_total'], $vat);
            $values['grand_total'] = $this->sum($values['grand_total'], $lineTotal);

            $bucket = match ($classification) {
                SalesTaxClassificationService::VAT_TAXABLE => 'taxable_sales',
                SalesTaxClassificationService::VAT_EXEMPT => 'exempt_sales',
                SalesTaxClassificationService::ZERO_RATED => 'zero_rated_sales',
                SalesTaxClassificationService::EXPORT => 'export_sales',
                SalesTaxClassificationService::OUT_OF_SCOPE => 'out_of_scope_sales',
                default => null,
            };
            if ($bucket !== null) {
                $values[$bucket] = $this->sum($values[$bucket], $netBase);
            }
        }

        $bucketTotal = round($values['taxable_sales'] + $values['exempt_sales']
            + $values['zero_rated_sales'] + $values['export_sales'] + $values['out_of_scope_sales'], 2);
        if (! $this->same($bucketTotal, $values['net_sales'])) {
            $errors[] = 'Fiscal classification buckets do not equal canonical net sales.';
        }
        if (! $this->same($values['gross_sales'] - $values['total_discount'], $values['net_sales'])) {
            $errors[] = 'Canonical gross sales less discount does not equal net sales.';
        }
        if (! $this->same($values['net_sales'] + $values['vat_total'], $values['grand_total'])) {
            $errors[] = 'Canonical net sales plus VAT does not equal grand total.';
        }

        $checks = [
            'subtotal' => ['gross_sales', 'Fiscal header subtotal does not equal canonical gross sales.'],
            'vat_total' => ['vat_total', 'Fiscal header VAT does not equal canonical VAT.'],
            'grand_total' => ['grand_total', 'Fiscal header grand total does not equal canonical grand total.'],
        ];
        if (array_key_exists('discount', $header)) {
            $checks['discount'] = ['total_discount', 'Fiscal header discount does not equal canonical line discounts.'];
        }
        foreach ($checks as $headerField => [$valueField, $message]) {
            if (! $this->same((float) $header[$headerField], $values[$valueField])) {
                $errors[] = $message;
            }
        }

        $errors = array_values(array_unique($errors));

        return $values + ['lines' => $lineResults, 'is_reconciled' => $errors === [], 'errors' => $errors];
    }

    private function sum(float $left, float $right): float
    {
        return round($left + $right, 2);
    }

    private function same(float $left, float $right): bool
    {
        return abs(round($left, 2) - round($right, 2)) <= self::TOLERANCE;
    }
}
