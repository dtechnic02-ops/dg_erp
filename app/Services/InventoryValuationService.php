<?php

namespace App\Services;

use App\Models\InventoryValuation;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class InventoryValuationService
{
    public function recordIncomingMovement(StockMovement $movement): InventoryValuation
    {
        if (! $movement->exists || ! in_array($movement->type, ['opening_stock', 'purchase'], true)) {
            throw new InvalidArgumentException('Only opening stock and purchase movements can create inventory valuations.');
        }

        return DB::transaction(function () use ($movement): InventoryValuation {
            if (InventoryValuation::query()->where('stock_movement_id', $movement->id)->exists()) {
                throw new RuntimeException('An inventory valuation already exists for this stock movement.');
            }

            $product = Product::query()
                ->where('company_id', $movement->company_id)
                ->lockForUpdate()
                ->find($movement->product_id);

            if (! $product) {
                throw new RuntimeException('The valuation product could not be resolved for this company.');
            }

            $latest = InventoryValuation::query()
                ->where('company_id', $movement->company_id)
                ->where('product_id', $movement->product_id)
                ->orderByDesc('valuation_sequence')
                ->lockForUpdate()
                ->first();

            $quantityBefore = $latest ? $this->decimal($latest->quantity_after, 6) : '0.000000';
            $valueBefore = $latest ? $this->decimal($latest->inventory_value_after, 4) : '0.0000';
            $averageBefore = $latest ? $this->decimal($latest->average_cost_after, 8) : '0.00000000';
            $quantityChange = $this->positive($movement->quantity, 6, 'movement quantity');
            $unitCost = $this->nonNegative($movement->unit_price, 8, 'movement unit cost');

            if (bccomp($quantityBefore, $this->decimal($movement->before_stock, 6), 6) !== 0) {
                throw new RuntimeException('The stock movement quantity does not match the latest inventory valuation.');
            }

            $quantityAfter = bcadd($quantityBefore, $quantityChange, 6);
            if (bccomp($quantityAfter, $this->decimal($movement->after_stock, 6), 6) !== 0) {
                throw new RuntimeException('The stock movement after quantity does not match the inventory valuation.');
            }

            $valueChange = bcmul($quantityChange, $unitCost, 4);
            $valueAfter = bcadd($valueBefore, $valueChange, 4);
            $averageAfter = bcdiv($valueAfter, $quantityAfter, 8);
            [$sourceModule, $sourceType, $sourceId, $sourceEvent] = $this->source($movement);

            return InventoryValuation::create([
                'company_id' => $movement->company_id,
                'product_id' => $movement->product_id,
                'stock_movement_id' => $movement->id,
                'valuation_sequence' => ($latest?->valuation_sequence ?? 0) + 1,
                'movement_type' => $movement->type,
                'source_module' => $sourceModule,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'source_event' => $sourceEvent,
                'quantity_before' => $quantityBefore,
                'quantity_change' => $quantityChange,
                'quantity_after' => $quantityAfter,
                'inventory_value_before' => $valueBefore,
                'inventory_value_change' => $valueChange,
                'inventory_value_after' => $valueAfter,
                'average_cost_before' => $averageBefore,
                'movement_unit_cost' => $unitCost,
                'average_cost_after' => $averageAfter,
                'valued_at' => now(),
            ]);
        });
    }

    private function source(StockMovement $movement): array
    {
        if ($movement->type === 'opening_stock') {
            return ['product', 'product_opening_stock', (int) $movement->product_id, 'created'];
        }

        $purchase = PurchaseInvoice::query()
            ->where('company_id', $movement->company_id)
            ->where('financial_year_id', $movement->financial_year_id)
            ->where('invoice_no', $movement->reference_no)
            ->first();

        if (! $purchase) {
            throw new RuntimeException('The purchase source could not be resolved for this stock movement.');
        }

        return ['purchase', PurchaseInvoice::class, (int) $purchase->id, 'created'];
    }

    private function positive(mixed $value, int $scale, string $field): string
    {
        $value = $this->nonNegative($value, $scale, $field);
        if (bccomp($value, '0', $scale) <= 0) {
            throw new InvalidArgumentException("The {$field} must be greater than zero.");
        }

        return $value;
    }

    private function nonNegative(mixed $value, int $scale, string $field): string
    {
        if (! is_numeric($value) || bccomp((string) $value, '0', $scale) < 0) {
            throw new InvalidArgumentException("The {$field} must be a non-negative decimal.");
        }

        return $this->decimal((string) $value, $scale);
    }

    private function decimal(mixed $value, int $scale): string
    {
        return bcadd((string) $value, '0', $scale);
    }
}
