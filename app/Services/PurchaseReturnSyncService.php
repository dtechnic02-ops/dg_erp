<?php

namespace App\Services;

use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnRefund;

class PurchaseReturnSyncService
{
    public static function calculateRefundedAmount(
        PurchaseReturn $purchaseReturn,
        bool $lock = false
    ): float {
        $query = PurchaseReturnRefund::where('company_id', $purchaseReturn->company_id)
            ->where('purchase_return_id', $purchaseReturn->id)
            ->active()
            ->whereNull('deleted_at');

        if ($lock) {
            $refunds = $query->lockForUpdate()->get();

            return round(
                $refunds->sum(
                    fn ($refund) => (float) $refund->adjust_amount + (float) $refund->cash_amount
                ),
                2
            );
        }

        $total = $query
            ->selectRaw('COALESCE(SUM(adjust_amount + cash_amount), 0) as total')
            ->value('total');

        return round((float) $total, 2);
    }

    public static function calculateRemainingAmount(
        PurchaseReturn $purchaseReturn,
        bool $lock = false
    ): float {
        $refundedAmount = self::calculateRefundedAmount($purchaseReturn, $lock);
        $grandTotal = round((float) $purchaseReturn->grand_total, 2);

        return max(0, round($grandTotal - $refundedAmount, 2));
    }

    public static function sync(
        PurchaseReturn|int $purchaseReturn,
        bool $lock = false
    ): PurchaseReturn {
        if (is_int($purchaseReturn)) {
            $purchaseReturn = PurchaseReturn::findOrFail($purchaseReturn);
        }

        $grandTotal = round((float) $purchaseReturn->grand_total, 2);
        $refundedAmount = self::calculateRefundedAmount($purchaseReturn, $lock);
        $remainingAmount = round($grandTotal - $refundedAmount, 2);

        if ($refundedAmount > $grandTotal) {
            throw new \Exception('Refunded amount exceeds grand total.');
        }

        if ($remainingAmount < 0) {
            throw new \Exception('Remaining amount cannot be negative.');
        }

        $purchaseReturn->update([
            'adjust_amount' => $refundedAmount,
            'refund_amount' => $remainingAmount,
        ]);

        self::migrateLegacyActiveStatuses($purchaseReturn);

        return $purchaseReturn->fresh();
    }

    protected static function migrateLegacyActiveStatuses(PurchaseReturn $purchaseReturn): void
    {
        PurchaseReturnRefund::where('company_id', $purchaseReturn->company_id)
            ->where('purchase_return_id', $purchaseReturn->id)
            ->where('status', PurchaseReturnRefund::STATUS_LEGACY_ACTIVE)
            ->update(['status' => PurchaseReturnRefund::STATUS_ACTIVE]);
    }
}
