<?php

namespace App\Services;

use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturnRefundAdjustment;

class PurchaseInvoicePaymentStateService
{
    public static function syncInvoicePaymentState(PurchaseInvoice $invoice): void
    {
        $paidAmount = round(
            $invoice->sumActivePaidAmount() + self::sumActiveRefundAdjustments($invoice),
            2
        );
        $dueAmount = max(
            0,
            round((float) $invoice->grand_total - $paidAmount, 2)
        );

        $invoice->update([
            'paid_amount'    => $paidAmount,
            'due_amount'     => $dueAmount,
            'payment_status' => self::resolveInvoicePaymentStatus($paidAmount, $dueAmount),
        ]);
    }

    public static function sumActiveRefundAdjustments(PurchaseInvoice $invoice): float
    {
        return round(
            (float) PurchaseReturnRefundAdjustment::where('company_id', $invoice->company_id)
                ->where('purchase_invoice_id', $invoice->id)
                ->where('status', 1)
                ->sum('adjust_amount'),
            2
        );
    }

    public static function resolveInvoicePaymentStatus(
        float $paidAmount,
        float $dueAmount
    ): string {
        if ($dueAmount <= 0) {
            return 'paid';
        }

        if ($paidAmount > 0) {
            return 'partial';
        }

        return 'unpaid';
    }
}
