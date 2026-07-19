<?php

namespace App\Services;

use App\Models\SalesReturn;
use App\Models\SalesReturnRefund;

class SalesReturnSyncService
{
    public static function calculateRefundedAmount(
        SalesReturn $salesReturn,
        bool $lock = false
    ): float {
        $query = SalesReturnRefund::where('company_id', $salesReturn->company_id)
            ->where('sales_return_id', $salesReturn->id)
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
        SalesReturn $salesReturn,
        bool $lock = false
    ): float {
        $refundedAmount = self::calculateRefundedAmount($salesReturn, $lock);
        $grandTotal = round((float) $salesReturn->grand_total, 2);

        return max(0, round($grandTotal - $refundedAmount, 2));
    }

    public static function sync(
        SalesReturn|int $salesReturn,
        bool $lock = false
    ): SalesReturn {
        if (is_int($salesReturn)) {
            $salesReturn = SalesReturn::findOrFail($salesReturn);
        }

        $grandTotal = round((float) $salesReturn->grand_total, 2);
        $refundedAmount = self::calculateRefundedAmount($salesReturn, $lock);
        $remainingAmount = round($grandTotal - $refundedAmount, 2);

        if ($refundedAmount > $grandTotal) {
            throw new \Exception('Refunded amount exceeds grand total.');
        }

        if ($remainingAmount < 0) {
            throw new \Exception('Remaining amount cannot be negative.');
        }

        $salesReturn->update([
            'adjust_amount' => $refundedAmount,
            'refund_amount' => $remainingAmount,
        ]);

        self::migrateLegacyActiveStatuses($salesReturn);

        return $salesReturn->fresh();
    }

    protected static function migrateLegacyActiveStatuses(SalesReturn $salesReturn): void
    {
        SalesReturnRefund::where('company_id', $salesReturn->company_id)
            ->where('sales_return_id', $salesReturn->id)
            ->where('status', SalesReturnRefund::STATUS_LEGACY_ACTIVE)
            ->update(['status' => SalesReturnRefund::STATUS_ACTIVE]);
    }
}
