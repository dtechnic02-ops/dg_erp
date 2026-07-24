<?php

namespace App\Services;

class VoucherRouteService
{
    public static function url(
        string $referenceType,
        int $referenceId,
        ?string $returnUrl = null
    )
    {
        $return = $returnUrl

            ? '?return=' .
              urlencode($returnUrl)

            : '';

        return match ($referenceType)
        {
            'sales_invoice' =>

                route(

                    'company.sales.show',

                    $referenceId

                ) . $return,

            'sales_payment' =>

                route(

                    'company.sales-payment.show',

                    $referenceId

                ) . $return,

            'sales_return' =>

                route(

                    'company.sales-return.show',

                    $referenceId

                ) . $return,

            'purchase_invoice' =>

                route(

                    'company.purchases.show',

                    $referenceId

                ) . $return,

            'purchase_payment' =>

                route(

                    'company.purchase-payments.show',

                    $referenceId

                ) . $return,

            'purchase_return' =>

                route(

                    'company.purchase-return.show',

                    $referenceId

                ) . $return,

            'purchase_return_refund' =>

                route(

                    'company.purchase-return-refunds.show',

                    $referenceId

                ) . $return,

            default => '#',
        };
    }
}