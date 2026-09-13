<?php

namespace App\Services;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseItem;

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

    $invoice->update([

        'subtotal' =>
            $subtotal,

        'total_vat' =>
            $totalVat,

        'grand_total' =>
            $grandTotal,

    ]);

    PurchaseInvoicePaymentStateService::syncInvoicePaymentState(
        $invoice->fresh()
    );
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
