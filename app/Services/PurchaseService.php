<?php

namespace App\Services;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseItem;
use App\Models\PurchasePayment;

class PurchaseService
{/*
|--------------------------------------------------------------------------
| RECALCULATE PURCHASE INVOICE
|--------------------------------------------------------------------------
*/
public static function recalculateInvoice(
    int $invoiceId
)
{
    $invoice = PurchaseInvoice::findOrFail(
        $invoiceId
    );

    $subtotal = PurchaseItem::where(
        'purchase_invoice_id',
        $invoice->id
    )
    ->where(
        'status',
        1
    )
    ->sum(
        'total_price'
    );

    $totalVat = PurchaseItem::where(
        'purchase_invoice_id',
        $invoice->id
    )
    ->where(
        'status',
        1
    )
    ->sum(
        'vat_amount'
    );

    $grandTotal =
        $subtotal
        -
        $invoice->discount
        +
        $totalVat;

    $paidAmount = PurchasePayment::where(
        'purchase_invoice_id',
        $invoice->id
    )
    ->where(
        'status',
        1
    )
    ->sum(
        'amount'
    );

    $dueAmount =
        $grandTotal
        -
        $paidAmount;

    $paymentStatus =
        $dueAmount <= 0
        ? 'paid'
        : (
            $paidAmount > 0
            ? 'partial'
            : 'unpaid'
        );

    $invoice->update([

        'subtotal' =>
            $subtotal,

        'total_vat' =>
            $totalVat,

        'grand_total' =>
            $grandTotal,

        'paid_amount' =>
            $paidAmount,

        'due_amount' =>
            $dueAmount,

        'payment_status' =>
            $paymentStatus,

    ]);
}


   /*
|--------------------------------------------------------------------------
| RECALCULATE ALL PURCHASE INVOICES
|--------------------------------------------------------------------------
*/
public static function recalculateAllInvoices(
    int $companyId
)
{
    PurchaseInvoice::where(
        'company_id',
        $companyId
    )
    ->chunk(100, function ($invoices) {

        foreach ($invoices as $invoice)
        {
            self::recalculateInvoice(
                $invoice->id
            );
        }

    });
}
}