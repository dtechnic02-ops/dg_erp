<?php

namespace App\Services;

use App\Models\InventoryValuation;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PurchaseReturnInventoryValuationService
{
    public function recordReturn(
        PurchaseReturn $return,
        PurchaseReturnItem $returnItem,
        PurchaseItem $purchaseItem,
        string $date,
        int $financialYearId
    ): InventoryValuation {
        return DB::transaction(function () use ($return, $returnItem, $purchaseItem, $date, $financialYearId): InventoryValuation {
            [$return, $returnItem, $purchaseItem] = $this->lockAndValidateSource(
                $return,
                $returnItem,
                $purchaseItem
            );

            if ($this->returnValuationQuery($return, $returnItem, 'created')->lockForUpdate()->exists()) {
                throw new RuntimeException('An inventory valuation already exists for this purchase return item.');
            }

            $original = $this->resolveOriginalPurchaseValuation($return, $purchaseItem);
            $quantity = $this->decimal($returnItem->quantity, 6);
            $cost = $this->decimal($original->movement_unit_cost, 8);

            if (bccomp($quantity, '0', 6) <= 0 || bccomp($cost, '0', 8) < 0) {
                throw new RuntimeException('The purchase return inventory source is invalid.');
            }

            [$previousQuantity, $previousValue] = $this->activeReturnedTotals($return, $purchaseItem);
            $returnedQuantity = bcadd($previousQuantity, $quantity, 6);
            $originalQuantity = $this->decimal($original->quantity_change, 6);

            if (bccomp($returnedQuantity, $originalQuantity, 6) > 0) {
                throw new RuntimeException('The purchase return quantity exceeds the original purchase valuation.');
            }

            $value = bccomp($returnedQuantity, $originalQuantity, 6) === 0
                ? bcsub($this->decimal($original->inventory_value_change, 4), $previousValue, 4)
                : bcmul($quantity, $cost, 4);

            if (bccomp($value, '0', 4) < 0) {
                throw new RuntimeException('The purchase return inventory value is invalid.');
            }

            [$product, $latest] = $this->lockProductAndLatest($return->company_id, $returnItem->product_id);
            $quantityAfter = bcsub($this->decimal($latest->quantity_after, 6), $quantity, 6);
            $valueAfter = bcsub($this->decimal($latest->inventory_value_after, 4), $value, 4);
            $this->assertValidResult($quantityAfter, $valueAfter, 'purchase return');

            $movement = StockService::decrease(
                $product,
                $quantity,
                'purchase_return',
                $return->return_no,
                $financialYearId,
                $date,
                $cost,
                'Purchase Return'
            );

            $this->assertMovementContinuity($movement, $return, $returnItem, $latest, $quantityAfter);

            return $this->createValuation(
                $return,
                $returnItem,
                $movement,
                $latest,
                'purchase_return',
                'created',
                bcsub('0', $quantity, 6),
                bcsub('0', $value, 4),
                $quantityAfter,
                $valueAfter,
                $cost
            );
        });
    }

    public function reverseReturn(
        PurchaseReturn $return,
        PurchaseReturnItem $returnItem,
        string $date,
        int $financialYearId,
        string $reason
    ): InventoryValuation {
        return DB::transaction(function () use ($return, $returnItem, $date, $financialYearId, $reason): InventoryValuation {
            $return = PurchaseReturn::query()
                ->where('company_id', $return->company_id)
                ->lockForUpdate()
                ->findOrFail($return->id);
            $returnItem = PurchaseReturnItem::query()
                ->where('company_id', $return->company_id)
                ->where('purchase_return_id', $return->id)
                ->whereNotNull('product_id')
                ->lockForUpdate()
                ->findOrFail($returnItem->id);

            $original = $this->returnValuationQuery($return, $returnItem, 'created')
                ->lockForUpdate()
                ->first();

            if (! $original) {
                throw new RuntimeException('The original purchase return inventory valuation could not be resolved.');
            }

            if ($this->returnValuationQuery($return, $returnItem, 'cancelled')->lockForUpdate()->exists()
                || InventoryValuation::query()
                    ->where('company_id', $return->company_id)
                    ->where('reversal_of_id', $original->id)
                    ->lockForUpdate()
                    ->exists()) {
                throw new RuntimeException('This purchase return inventory valuation has already been reversed.');
            }

            $originalMovement = StockMovement::query()
                ->where('company_id', $return->company_id)
                ->where('product_id', $returnItem->product_id)
                ->lockForUpdate()
                ->find($original->stock_movement_id);

            if (! $originalMovement
                || $originalMovement->type !== 'purchase_return'
                || (string) $originalMovement->reference_no !== (string) $return->return_no
                || bccomp($this->decimal($originalMovement->quantity, 6), $this->decimal($original->quantity_change, 6), 6) !== 0) {
                throw new RuntimeException('The original purchase return inventory valuation is invalid.');
            }

            $quantity = bcsub('0', $this->decimal($original->quantity_change, 6), 6);
            $value = bcsub('0', $this->decimal($original->inventory_value_change, 4), 4);
            $cost = $this->decimal($original->movement_unit_cost, 8);

            if (bccomp($quantity, '0', 6) <= 0 || bccomp($value, '0', 4) < 0 || bccomp($cost, '0', 8) < 0) {
                throw new RuntimeException('The original purchase return inventory valuation is invalid.');
            }

            [$product, $latest] = $this->lockProductAndLatest($return->company_id, $returnItem->product_id);
            $quantityAfter = bcadd($this->decimal($latest->quantity_after, 6), $quantity, 6);
            $valueAfter = bcadd($this->decimal($latest->inventory_value_after, 4), $value, 4);

            $movement = StockService::increase(
                $product,
                $quantity,
                'purchase_return_cancel',
                $return->return_no,
                $financialYearId,
                $date,
                $cost,
                'Purchase Return Cancel: '.$reason
            );

            $this->assertMovementContinuity($movement, $return, $returnItem, $latest, $quantityAfter);

            return $this->createValuation(
                $return,
                $returnItem,
                $movement,
                $latest,
                'purchase_return_cancel',
                'cancelled',
                $quantity,
                $value,
                $quantityAfter,
                $valueAfter,
                $cost,
                $original->id
            );
        });
    }

    private function lockAndValidateSource(
        PurchaseReturn $return,
        PurchaseReturnItem $returnItem,
        PurchaseItem $purchaseItem
    ): array {
        $return = PurchaseReturn::query()
            ->where('company_id', $return->company_id)
            ->lockForUpdate()
            ->findOrFail($return->id);
        $returnItem = PurchaseReturnItem::query()
            ->where('company_id', $return->company_id)
            ->where('purchase_return_id', $return->id)
            ->whereNotNull('product_id')
            ->lockForUpdate()
            ->findOrFail($returnItem->id);
        $purchaseItem = PurchaseItem::query()
            ->where('company_id', $return->company_id)
            ->where('purchase_invoice_id', $return->purchase_invoice_id)
            ->where('item_type', PurchaseItem::ITEM_TYPE_PRODUCT)
            ->lockForUpdate()
            ->findOrFail($purchaseItem->id);

        if ((int) $returnItem->purchase_item_id !== (int) $purchaseItem->id
            || (int) $returnItem->product_id !== (int) $purchaseItem->product_id) {
            throw new RuntimeException('The purchase return inventory source does not match its purchase item.');
        }

        return [$return, $returnItem, $purchaseItem];
    }

    private function resolveOriginalPurchaseValuation(
        PurchaseReturn $return,
        PurchaseItem $purchaseItem
    ): InventoryValuation {
        $purchase = PurchaseInvoice::query()
            ->where('company_id', $return->company_id)
            ->lockForUpdate()
            ->find($return->purchase_invoice_id);

        if (! $purchase) {
            throw new RuntimeException('The original purchase inventory valuation could not be paired safely.');
        }

        $items = PurchaseItem::query()
            ->where('company_id', $return->company_id)
            ->where('purchase_invoice_id', $return->purchase_invoice_id)
            ->where('item_type', PurchaseItem::ITEM_TYPE_PRODUCT)
            ->where('product_id', $purchaseItem->product_id)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
        $originals = InventoryValuation::query()
            ->where('company_id', $return->company_id)
            ->where('product_id', $purchaseItem->product_id)
            ->where('source_module', 'purchase')
            ->where('source_type', PurchaseInvoice::class)
            ->where('source_id', $return->purchase_invoice_id)
            ->where('source_event', 'created')
            ->where('movement_type', 'purchase')
            ->orderBy('valuation_sequence')
            ->lockForUpdate()
            ->get();

        $index = $items->search(fn (PurchaseItem $item): bool => (int) $item->id === (int) $purchaseItem->id);
        if ($index === false || $items->count() !== $originals->count()) {
            throw new RuntimeException('The original purchase inventory valuation could not be paired safely.');
        }

        $original = $originals->values()->get($index);
        $movement = $original ? StockMovement::query()
            ->where('company_id', $return->company_id)
            ->where('product_id', $purchaseItem->product_id)
            ->lockForUpdate()
            ->find($original->stock_movement_id) : null;

        if (! $original || ! $movement
            || $movement->type !== 'purchase'
            || (string) $movement->reference_no !== (string) $purchase->invoice_no
            || bccomp($this->decimal($movement->quantity, 6), $this->decimal($purchaseItem->quantity, 6), 6) !== 0
            || bccomp($this->decimal($original->quantity_change, 6), $this->decimal($purchaseItem->quantity, 6), 6) !== 0) {
            throw new RuntimeException('The original purchase inventory valuation could not be paired safely.');
        }

        return $original;
    }

    private function activeReturnedTotals(PurchaseReturn $return, PurchaseItem $purchaseItem): array
    {
        $returnItemIds = PurchaseReturnItem::query()
            ->where('company_id', $return->company_id)
            ->where('purchase_item_id', $purchaseItem->id)
            ->where('product_id', $purchaseItem->product_id)
            ->pluck('id');
        $created = InventoryValuation::query()
            ->where('company_id', $return->company_id)
            ->where('product_id', $purchaseItem->product_id)
            ->where('source_type', PurchaseReturnItem::class)
            ->whereIn('source_id', $returnItemIds)
            ->where('source_event', 'created')
            ->orderBy('valuation_sequence')
            ->lockForUpdate()
            ->get();
        $reversedIds = InventoryValuation::query()
            ->where('company_id', $return->company_id)
            ->whereIn('reversal_of_id', $created->pluck('id'))
            ->lockForUpdate()
            ->pluck('reversal_of_id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        $quantity = '0.000000';
        $value = '0.0000';
        foreach ($created as $valuation) {
            if (in_array((int) $valuation->id, $reversedIds, true)) {
                continue;
            }
            $quantity = bcadd($quantity, bcsub('0', $this->decimal($valuation->quantity_change, 6), 6), 6);
            $value = bcadd($value, bcsub('0', $this->decimal($valuation->inventory_value_change, 4), 4), 4);
        }

        return [$quantity, $value];
    }

    private function lockProductAndLatest(int $companyId, int $productId): array
    {
        $product = Product::query()
            ->where('company_id', $companyId)
            ->lockForUpdate()
            ->findOrFail($productId);
        $latest = InventoryValuation::query()
            ->where('company_id', $companyId)
            ->where('product_id', $productId)
            ->orderByDesc('valuation_sequence')
            ->lockForUpdate()
            ->first();

        if (! $latest
            || bccomp($this->decimal($latest->quantity_after, 6), $this->decimal($product->current_stock, 6), 6) !== 0) {
            throw new RuntimeException('Inventory quantity continuity is invalid.');
        }

        return [$product, $latest];
    }

    private function assertMovementContinuity(
        StockMovement $movement,
        PurchaseReturn $return,
        PurchaseReturnItem $returnItem,
        InventoryValuation $latest,
        string $quantityAfter
    ): void {
        if ((int) $movement->company_id !== (int) $return->company_id
            || (int) $movement->product_id !== (int) $returnItem->product_id
            || bccomp($this->decimal($movement->before_stock, 6), $this->decimal($latest->quantity_after, 6), 6) !== 0
            || bccomp($this->decimal($movement->after_stock, 6), $quantityAfter, 6) !== 0) {
            throw new RuntimeException('The purchase return stock movement does not match inventory valuation continuity.');
        }
    }

    private function assertValidResult(string $quantityAfter, string $valueAfter, string $operation): void
    {
        if (bccomp($quantityAfter, '0', 6) < 0 || bccomp($valueAfter, '0', 4) < 0) {
            throw new RuntimeException("The {$operation} would create negative inventory quantity or value.");
        }

        if (bccomp($quantityAfter, '0', 6) === 0 && bccomp($valueAfter, '0', 4) !== 0) {
            throw new RuntimeException("The {$operation} would break inventory valuation continuity.");
        }
    }

    private function returnValuationQuery(
        PurchaseReturn $return,
        PurchaseReturnItem $returnItem,
        string $event
    ) {
        return InventoryValuation::query()
            ->where('company_id', $return->company_id)
            ->where('product_id', $returnItem->product_id)
            ->where('source_module', 'purchase')
            ->where('source_type', PurchaseReturnItem::class)
            ->where('source_id', $returnItem->id)
            ->where('source_event', $event);
    }

    private function createValuation(
        PurchaseReturn $return,
        PurchaseReturnItem $returnItem,
        StockMovement $movement,
        InventoryValuation $latest,
        string $movementType,
        string $sourceEvent,
        string $quantityChange,
        string $valueChange,
        string $quantityAfter,
        string $valueAfter,
        string $cost,
        ?int $reversalOfId = null
    ): InventoryValuation {
        if (InventoryValuation::query()->where('stock_movement_id', $movement->id)->exists()) {
            throw new RuntimeException('An inventory valuation already exists for this stock movement.');
        }

        $averageAfter = bccomp($quantityAfter, '0', 6) === 0
            ? '0.00000000'
            : bcdiv($valueAfter, $quantityAfter, 8);

        return InventoryValuation::create([
            'company_id' => $return->company_id,
            'product_id' => $returnItem->product_id,
            'stock_movement_id' => $movement->id,
            'valuation_sequence' => $latest->valuation_sequence + 1,
            'movement_type' => $movementType,
            'source_module' => 'purchase',
            'source_type' => PurchaseReturnItem::class,
            'source_id' => $returnItem->id,
            'source_event' => $sourceEvent,
            'quantity_before' => $latest->quantity_after,
            'quantity_change' => $quantityChange,
            'quantity_after' => $quantityAfter,
            'inventory_value_before' => $latest->inventory_value_after,
            'inventory_value_change' => $valueChange,
            'inventory_value_after' => $valueAfter,
            'average_cost_before' => $latest->average_cost_after,
            'movement_unit_cost' => $cost,
            'average_cost_after' => $averageAfter,
            'reversal_of_id' => $reversalOfId,
            'valued_at' => now(),
        ]);
    }

    private function decimal(mixed $value, int $scale): string
    {
        return bcadd((string) $value, '0', $scale);
    }
}
