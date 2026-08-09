<?php

namespace App\Services\Accounting;

use App\Models\PurchaseInvoice;
use App\Models\PurchaseItem;
use RuntimeException;

class PurchaseReturnValuationService
{
    /** @param array<int, string|int|float> $quantities */
    public function calculate(PurchaseInvoice $invoice, array $quantities, bool $currentReturnAlreadyIncluded = false): array
    {
        $items = $invoice->items()->where('status', 1)->orderBy('id')->get();
        if ($items->isEmpty()) throw new RuntimeException('The original Purchase has no active value lines.');

        $groups = ['product' => [], 'service' => []];
        foreach ($items as $item) {
            if (! isset($groups[$item->item_type])) throw new RuntimeException('The original Purchase contains an unsupported item type.');
            $groups[$item->item_type][] = ['item' => $item, 'raw' => $this->cents($item->total_price) - $this->cents($item->vat_amount)];
        }

        $productRaw = array_sum(array_column($groups['product'], 'raw'));
        $serviceRaw = array_sum(array_column($groups['service'], 'raw'));
        $rawTotal = $productRaw + $serviceRaw;
        $discount = $this->cents($invoice->discount);
        if ($rawTotal < 1 || $discount > $rawTotal) throw new RuntimeException('The original Purchase value and discount are invalid.');

        $productDiscount = $this->proportion($discount, $productRaw, $rawTotal);
        $serviceDiscount = $discount - $productDiscount;
        $allocated = $this->allocateGroup($groups['product'], $productDiscount) + $this->allocateGroup($groups['service'], $serviceDiscount);

        $result = ['items' => [], 'product_net' => 0, 'service_net' => 0, 'tax' => 0, 'total' => 0];
        foreach ($quantities as $itemId => $quantity) {
            $item = $items->firstWhere('id', (int) $itemId);
            if (! $item || ! isset($allocated[$item->id])) throw new RuntimeException('A Purchase Return line does not belong to the original active Purchase.');
            $qty = $this->quantity($quantity);
            $originalQty = $this->quantity($item->quantity);
            $recordedReturned = $this->quantity($item->returned_qty ?? 0);
            $priorReturned = $currentReturnAlreadyIncluded ? $recordedReturned - $qty : $recordedReturned;
            $cumulativeReturned = $priorReturned + $qty;
            if ($qty < 1 || $priorReturned < 0 || $cumulativeReturned > $originalQty) throw new RuntimeException('A Purchase Return quantity is invalid for the original Purchase line.');
            $net = $this->proportion($allocated[$item->id], $cumulativeReturned, $originalQty) - $this->proportion($allocated[$item->id], $priorReturned, $originalQty);
            $taxTotal = $this->cents($item->vat_amount);
            $tax = $this->proportion($taxTotal, $cumulativeReturned, $originalQty) - $this->proportion($taxTotal, $priorReturned, $originalQty);
            $result['items'][$item->id] = ['net' => $this->money($net), 'tax' => $this->money($tax), 'total' => $this->money($net + $tax)];
            $result[$item->item_type . '_net'] += $net;
            $result['tax'] += $tax;
            $result['total'] += $net + $tax;
        }

        foreach (['product_net', 'service_net', 'tax', 'total'] as $key) $result[$key] = $this->money($result[$key]);
        return $result;
    }

    private function allocateGroup(array $rows, int $discount): array
    {
        if ($rows === []) return [];
        $total = array_sum(array_column($rows, 'raw')); $used = 0; $allocated = [];
        foreach ($rows as $index => $row) {
            $part = $index === array_key_last($rows) ? $discount - $used : $this->proportion($discount, $row['raw'], $total);
            $used += $part; $allocated[$row['item']->id] = $row['raw'] - $part;
        }
        return $allocated;
    }

    private function cents(mixed $value): int { return $this->scaled($value, 2); }
    private function quantity(mixed $value): int { return $this->scaled($value, 4); }
    private function proportion(int $amount, int $part, int $whole): int { return $amount === 0 || $part === 0 ? 0 : intdiv(($amount * $part) + intdiv($whole, 2), $whole); }
    private function money(int $cents): string { return intdiv($cents, 100) . '.' . str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT) . '00'; }

    private function scaled(mixed $value, int $scale): int
    {
        $value = trim((string) $value);
        if (! preg_match('/^\d+(?:\.\d+)?$/', $value)) throw new RuntimeException('A Purchase Return monetary or quantity value is invalid.');
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');
        $fraction = substr(str_pad($fraction, $scale + 1, '0'), 0, $scale + 1);
        $scaled = ((int) $whole * (10 ** $scale)) + (int) substr($fraction, 0, $scale);
        if ((int) ($fraction[$scale] ?? '0') >= 5) $scaled++;
        return $scaled;
    }
}
