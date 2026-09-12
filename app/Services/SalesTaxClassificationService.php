<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class SalesTaxClassificationService
{
    public const VAT_TAXABLE = 'vat_taxable';
    public const VAT_EXEMPT = 'vat_exempt';
    public const ZERO_RATED = 'zero_rated';
    public const EXPORT = 'export';
    public const OUT_OF_SCOPE = 'out_of_scope';
    public const LEGACY_UNCLASSIFIED = 'legacy_unclassified';

    public const SELECTABLE = [
        self::VAT_TAXABLE,
        self::VAT_EXEMPT,
        self::ZERO_RATED,
        self::EXPORT,
        self::OUT_OF_SCOPE,
    ];

    public function normalizeForNewLine(?string $classification, float $vatRate, bool $required): string
    {
        $classification = trim((string) $classification);

        if ($classification === '') {
            if ($required) {
                throw ValidationException::withMessages([
                    'tax_classification' => 'Tax classification is required for every Nepal sales line.',
                ]);
            }

            return $vatRate > 0 ? self::VAT_TAXABLE : self::LEGACY_UNCLASSIFIED;
        }

        if (! in_array($classification, self::SELECTABLE, true)) {
            throw ValidationException::withMessages([
                'tax_classification' => 'The selected tax classification is invalid.',
            ]);
        }

        if ($classification === self::VAT_TAXABLE && $vatRate <= 0) {
            throw ValidationException::withMessages([
                'tax_classification' => 'VAT Taxable lines require a VAT rate greater than zero.',
            ]);
        }

        if ($classification !== self::VAT_TAXABLE && $vatRate != 0.0) {
            throw ValidationException::withMessages([
                'tax_classification' => 'VAT Exempt, Zero Rated, Export and Out of Scope lines require a zero VAT rate.',
            ]);
        }

        return $classification;
    }

    public function summarize(iterable $items): array
    {
        $summary = array_fill_keys([
            self::VAT_TAXABLE,
            self::VAT_EXEMPT,
            self::ZERO_RATED,
            self::EXPORT,
            self::OUT_OF_SCOPE,
            self::LEGACY_UNCLASSIFIED,
        ], 0.0);

        foreach ($items as $item) {
            $classification = $item->tax_classification ?: self::LEGACY_UNCLASSIFIED;
            if (! array_key_exists($classification, $summary)) {
                $classification = self::LEGACY_UNCLASSIFIED;
            }

            $lineBase = $item->fiscal_net_base !== null
                ? (float) $item->fiscal_net_base
                : ((float) $item->quantity * (float) $item->unit_price);
            $summary[$classification] = round($summary[$classification] + $lineBase, 2);
        }

        return [
            'taxable_sales_vat' => $summary[self::VAT_TAXABLE],
            'tax_exempted_sales' => $summary[self::VAT_EXEMPT],
            'export_sales' => $summary[self::EXPORT],
            'zero_rated_sales' => $summary[self::ZERO_RATED],
            'out_of_scope_sales' => $summary[self::OUT_OF_SCOPE],
            'legacy_unclassified_sales' => $summary[self::LEGACY_UNCLASSIFIED],
        ];
    }

}
