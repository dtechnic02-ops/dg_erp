<?php

namespace App\Services;

use App\Models\InventoryValuation;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseItem;
use App\Models\StockMovement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PurchaseCancellationInventoryValuationService
{
    public function reversePurchase(
        PurchaseInvoice $purchase,
        string $date,
        int $financialYearId,
        string $reason
    ): Collection {
        return DB::transaction(function () use ($purchase, $date, $financialYearId, $reason): Collection {
            $purchase = PurchaseInvoice::query()
                ->where('company_id', $purchase->company_id)
                ->lockForUpdate()
                ->findOrFail($purchase->id);

            $items = PurchaseItem::query()
                ->where('company_id', $purchase->company_id)
                ->where('purchase_invoice_id', $purchase->id)
                ->whereNotNull('product_id')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($items->isEmpty()) {
                return collect();
            }

            $pairsByProduct = $this->resolveAndValidatePairs($purchase, $items);
            $reversals = collect();

            foreach ($pairsByProduct as $pairs) {
                foreach ($pairs->reverse() as $pair) {
                    $reversals->push($this->reversePair(
                        $purchase,
                        $pair['item'],
                        $pair['valuation'],
                        $date,
                        $financialYearId,
                        $reason
                    ));
                }
            }

            return $reversals;
        });
    }

    private function resolveAndValidatePairs(PurchaseInvoice $purchase, Collection $items): Collection
    {
        $pairsByProduct = collect();

        foreach ($items->groupBy('product_id') as $productId => $productItems) {
            $originals = InventoryValuation::query()
                ->where('company_id', $purchase->company_id)
                ->where('product_id', $productId)
                ->where('source_module', 'purchase')
                ->where('source_type', PurchaseInvoice::class)
                ->where('source_id', $purchase->id)
                ->where('source_event', 'created')
                ->orderBy('valuation_sequence')
                ->lockForUpdate()
                ->get();

            if ($originals->count() !== $productItems->count()) {
                throw new RuntimeException('The original purchase inventory valuations could not be paired safely.');
            }

            if (InventoryValuation::query()
                ->where('company_id', $purchase->company_id)
                ->whereIn('reversal_of_id', $originals->pluck('id'))
                ->lockForUpdate()
                ->exists()) {
                throw new RuntimeException('This purchase inventory valuation has already been reversed.');
            }

            $product = Product::query()
                ->where('company_id', $purchase->company_id)
                ->lockForUpdate()
                ->find($productId);
            $latest = InventoryValuation::query()
                ->where('company_id', $purchase->company_id)
                ->where('product_id', $productId)
                ->orderByDesc('valuation_sequence')
                ->lockForUpdate()
                ->first();

            if (! $product || ! $latest || bccomp((string) $latest->quantity_after, (string) $product->current_stock, 6) !== 0) {
                throw new RuntimeException('Inventory quantity continuity is invalid.');
            }

            $purchaseQuantity = $originals->reduce(
                fn (string $total, InventoryValuation $valuation): string => bcadd($total, (string) $valuation->quantity_change, 6),
                '0.000000'
            );
            $purchaseValue = $originals->reduce(
                fn (string $total, InventoryValuation $valuation): string => bcadd($total, (string) $valuation->inventory_value_change, 4),
                '0.0000'
            );

            if (bccomp((string) $latest->quantity_after, $purchaseQuantity, 6) < 0) {
                throw new RuntimeException('Insufficient current stock to reverse this purchase.');
            }

            if (bccomp((string) $latest->inventory_value_after, $purchaseValue, 4) < 0) {
                throw new RuntimeException('The current inventory valuation cannot safely absorb this purchase reversal.');
            }

            $quantityAfter = bcsub((string) $latest->quantity_after, $purchaseQuantity, 6);
            $valueAfter = bcsub((string) $latest->inventory_value_after, $purchaseValue, 4);
            if (bccomp($quantityAfter, '0', 6) === 0 && bccomp($valueAfter, '0', 4) !== 0) {
                throw new RuntimeException('The current inventory valuation cannot safely absorb this purchase reversal.');
            }

            $pairs = collect();
            foreach ($productItems->values() as $index => $item) {
                $original = $originals->values()[$index];
                $movement = StockMovement::query()
                    ->where('company_id', $purchase->company_id)
                    ->where('product_id', $productId)
                    ->lockForUpdate()
                    ->find($original->stock_movement_id);

                if (! $movement
                    || $movement->type !== 'purchase'
                    || (string) $movement->reference_no !== (string) $purchase->invoice_no
                    || bccomp((string) $movement->quantity, (string) $item->quantity, 6) !== 0
                    || bccomp((string) $original->quantity_change, (string) $item->quantity, 6) !== 0) {
                    throw new RuntimeException('The original purchase inventory valuations could not be paired safely.');
                }

                $pairs->push(['item' => $item, 'valuation' => $original]);
            }

            $pairsByProduct->push($pairs);
        }

        return $pairsByProduct;
    }

    private function reversePair(
        PurchaseInvoice $purchase,
        PurchaseItem $item,
        InventoryValuation $original,
        string $date,
        int $financialYearId,
        string $reason
    ): InventoryValuation {
        $product = Product::query()
            ->where('company_id', $purchase->company_id)
            ->lockForUpdate()
            ->findOrFail($item->product_id);
        $latest = InventoryValuation::query()
            ->where('company_id', $purchase->company_id)
            ->where('product_id', $item->product_id)
            ->orderByDesc('valuation_sequence')
            ->lockForUpdate()
            ->first();

        if (! $latest || bccomp((string) $latest->quantity_after, (string) $product->current_stock, 6) !== 0) {
            throw new RuntimeException('Inventory quantity continuity is invalid.');
        }

        $quantity = bcadd((string) $original->quantity_change, '0', 6);
        $cost = bcadd((string) $original->movement_unit_cost, '0', 8);
        $value = bcadd((string) $original->inventory_value_change, '0', 4);
        if (bccomp($quantity, '0', 6) <= 0 || bccomp($cost, '0', 8) < 0 || bccomp($value, '0', 4) < 0) {
            throw new RuntimeException('The original purchase inventory valuation is invalid.');
        }

        $quantityAfter = bcsub((string) $latest->quantity_after, $quantity, 6);
        $valueAfter = bcsub((string) $latest->inventory_value_after, $value, 4);
        if (bccomp($quantityAfter, '0', 6) < 0) {
            throw new RuntimeException('Insufficient current stock to reverse this purchase.');
        }

        if (bccomp($valueAfter, '0', 4) < 0) {
            throw new RuntimeException('The current inventory valuation cannot safely absorb this purchase reversal.');
        }

        if (bccomp($quantityAfter, '0', 6) === 0 && bccomp($valueAfter, '0', 4) !== 0) {
            throw new RuntimeException('The current inventory valuation cannot safely absorb this purchase reversal.');
        }

        $movement = StockService::decrease(
            $product,
            $quantity,
            'purchase_cancel',
            $purchase->invoice_no,
            $financialYearId,
            $date,
            $cost,
            'Purchase Cancel: '.$reason
        );
        $averageAfter = bccomp($quantityAfter, '0', 6) === 0
            ? '0.00000000'
            : bcdiv($valueAfter, $quantityAfter, 8);

        return InventoryValuation::create([
            'company_id' => $purchase->company_id,
            'product_id' => $item->product_id,
            'stock_movement_id' => $movement->id,
            'valuation_sequence' => $latest->valuation_sequence + 1,
            'movement_type' => 'purchase_cancel',
            'source_module' => 'purchase',
            'source_type' => PurchaseInvoice::class,
            'source_id' => $purchase->id,
            'source_event' => 'cancelled',
            'quantity_before' => $latest->quantity_after,
            'quantity_change' => bcsub('0', $quantity, 6),
            'quantity_after' => $quantityAfter,
            'inventory_value_before' => $latest->inventory_value_after,
            'inventory_value_change' => bcsub('0', $value, 4),
            'inventory_value_after' => $valueAfter,
            'average_cost_before' => $latest->average_cost_after,
            'movement_unit_cost' => $cost,
            'average_cost_after' => $averageAfter,
            'reversal_of_id' => $original->id,
            'valued_at' => now(),
        ]);
    }
}
