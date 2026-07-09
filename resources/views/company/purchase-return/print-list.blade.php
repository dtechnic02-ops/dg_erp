@include('company.partials.print-header-portrait')

<!DOCTYPE html>
<html>

<head>

<link
rel="stylesheet"
href="{{ asset('assets/company/css/print.css') }}"
>

<title>

Purchase Return Report

</title>

</head>

<body onload="window.print()">

<button
class="print-btn"
onclick="window.print()"
>
Print
</button>

<div class="report-title">

Purchase Return Report

</div>

<table class="table-report">


    <thead>

        <tr>

            <th>#</th>

            <th>Return No</th>

            <th>Date</th>

            <th>Invoice</th>

            <th>Supplier</th>

            <th>Subtotal</th>

            <th>VAT</th>

            <th>Grand Total</th>

            <th>Paid</th>

            <th>Remaining</th>

            <th>Status</th>

        </tr>

    </thead>

    <tbody>

        @php

            $totalSubtotal = 0;
            $totalVat = 0;
            $totalGrand = 0;
            $totalPaid = 0;
            $totalRemaining = 0;

        @endphp

        @foreach($returns as $return)

            @php

                $paid =
                    $return->refunds->sum('amount');

                $remaining =
                    $return->grand_total - $paid;

                $totalSubtotal +=
                    $return->subtotal;

                $totalVat +=
                    $return->total_vat;

                $totalGrand +=
                    $return->grand_total;

                $totalPaid +=
                    $paid;

                $totalRemaining +=
                    $remaining;

            @endphp

            <tr>

                <td>
                    {{ $loop->iteration }}
                </td>

                <td>
                    {{ $return->return_no }}
                </td>

                <td>
                    {{ $return->return_date }}
                </td>

                <td>
                    {{ $return->purchaseInvoice->invoice_no ?? '-' }}
                </td>

                <td>
                    {{ $return->supplier->name ?? '-' }}
                </td>

                <td class="text-end">
                    {{ number_format($return->subtotal,2) }}
                </td>

                <td class="text-end">
                    {{ number_format($return->total_vat,2) }}
                </td>

                <td class="text-end">
                    {{ number_format($return->grand_total,2) }}
                </td>

                <td class="text-end">
                    {{ number_format($paid,2) }}
                </td>

                <td class="text-end">
                    {{ number_format($remaining,2) }}
                </td>

                <td>

                    @if($remaining <= 0)

                        Refunded

                    @elseif($paid > 0)

                        Partial

                    @else

                        Pending

                    @endif

                </td>

            </tr>

        @endforeach

    </tbody>

    <tfoot>

        <tr>

            <th colspan="5" class="text-end">
                Total
            </th>

            <th class="text-end">
                {{ number_format($totalSubtotal,2) }}
            </th>

            <th class="text-end">
                {{ number_format($totalVat,2) }}
            </th>

            <th class="text-end">
                {{ number_format($totalGrand,2) }}
            </th>

            <th class="text-end">
                {{ number_format($totalPaid,2) }}
            </th>

            <th class="text-end">
                {{ number_format($totalRemaining,2) }}
            </th>

            <th></th>

        </tr>

    </tfoot>

</table>
@include('company.partials.print-footer-portrait')

</body>

</html>