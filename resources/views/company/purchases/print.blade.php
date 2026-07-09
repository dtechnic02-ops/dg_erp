<!DOCTYPE html>
<html>

<head>

    <meta charset="utf-8">

    <title>
        Purchase Report
    </title>

    <link
        rel="stylesheet"
        href="{{ asset('assets/company/css/print.css') }}"
    >

</head>

<body onload="window.print()">

@include('company.partials.print-header-portrait')

<button
    class="print-btn"
    onclick="window.print()"
>
    Print
</button>

<div class="report-title">
    Purchase Report
</div>

<table class="table-report">

    <thead>

        <tr>
            <th>Date</th>
            <th>Invoice No</th>
            <th>Supplier</th>
            <th>Total</th>
            <th>Paid</th>
            <th>Due</th>
        </tr>

    </thead>

    <tbody>

        @php
            $totalGrand = 0;
            $totalPaid = 0;
            $totalDue = 0;
        @endphp

        @foreach($invoices as $invoice)

            @php
                $totalGrand += $invoice->grand_total;
                $totalPaid += $invoice->paid_amount;
                $totalDue += $invoice->due_amount;
            @endphp

            <tr>

                <td>
                    {{ $invoice->purchase_date }}
                </td>

                <td>
                    {{ $invoice->invoice_no }}
                </td>

                <td>
                    {{ $invoice->supplier->name ?? '' }}
                </td>

                <td class="text-right">
                    {{ number_format($invoice->grand_total, 2) }}
                </td>

                <td class="text-right">
                    {{ number_format($invoice->paid_amount, 2) }}
                </td>

                <td class="text-right">
                    {{ number_format($invoice->due_amount, 2) }}
                </td>

            </tr>

        @endforeach

        <tr>

            <td colspan="3">
                <strong>Total</strong>
            </td>

            <td class="text-right">
                <strong>
                    {{ number_format($totalGrand, 2) }}
                </strong>
            </td>

            <td class="text-right">
                <strong>
                    {{ number_format($totalPaid, 2) }}
                </strong>
            </td>

            <td class="text-right">
                <strong>
                    {{ number_format($totalDue, 2) }}
                </strong>
            </td>

        </tr>

    </tbody>

</table>

@include('company.partials.print-footer-portrait')

</body>
</html>