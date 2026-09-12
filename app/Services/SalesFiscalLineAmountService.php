<?php

namespace App\Services;

use InvalidArgumentException;

class SalesFiscalLineAmountService
{
    public function calculate(float $quantity, float $unitPrice, float $discountAmount, float $vatRate): array
    {
        if ($quantity < 0 || $unitPrice < 0 || $vatRate < 0) {
            throw new InvalidArgumentException('Fiscal sales line amounts cannot be negative.');
        }

        $grossBase = round($quantity * $unitPrice, 2);
        $discountAmount = round($discountAmount, 2);

        if ($discountAmount < 0) {
            throw new InvalidArgumentException('Line discount cannot be negative.');
        }
        if ($discountAmount > $grossBase) {
            throw new InvalidArgumentException('Line discount cannot exceed line gross amount.');
        }

        $netBase = round($grossBase - $discountAmount, 2);
        $vatAmount = round($netBase * ($vatRate / 100), 2);

        return [
            'gross_base' => $grossBase,
            'discount_amount' => $discountAmount,
            'net_base' => $netBase,
            'vat_amount' => $vatAmount,
            'line_total' => round($netBase + $vatAmount, 2),
        ];
    }

    public function returnShare(array $original, array $alreadyReturned, float $returnQuantity): array
    {
        $originalQuantity = (float) $original['quantity'];
        $returnedQuantity = (float) $alreadyReturned['quantity'];
        $remainingQuantity = round($originalQuantity - $returnedQuantity, 2);

        if ($returnQuantity <= 0 || $returnQuantity > $remainingQuantity || $originalQuantity <= 0) {
            throw new InvalidArgumentException('Return qty exceeds available qty.');
        }

        $final = abs($returnQuantity - $remainingQuantity) < 0.00001;
        $share = ['quantity' => $returnQuantity];

        foreach (['discount_amount', 'net_base', 'vat_amount', 'line_total'] as $field) {
            $originalAmount = round((float) $original[$field], 2);
            $priorAmount = round((float) ($alreadyReturned[$field] ?? 0), 2);
            $share[$field] = $final
                ? round($originalAmount - $priorAmount, 2)
                : round($originalAmount * ($returnQuantity / $originalQuantity), 2);

            if ($share[$field] < 0 || round($priorAmount + $share[$field], 2) > $originalAmount) {
                throw new InvalidArgumentException('A fiscal return cannot exceed the original frozen line values.');
            }
        }

        return $share;
    }
}
