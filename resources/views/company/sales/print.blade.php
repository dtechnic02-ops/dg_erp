<!DOCTYPE html>
<html>

<head>

    <meta charset="UTF-8">

    <title>
        Sales Invoice Print
    </title>

    <style>

        @page {
            size: A4;
            margin: 15px;
        }

        body {

            font-family: Arial, sans-serif;
            font-size: 13px;
            color: #000;

        }

        .container {

            width: 100%;

        }

        .header {

            text-align: center;
            margin-bottom: 20px;

        }

        .header h2 {

            margin: 0;
            font-size: 26px;

        }

        .header p {

            margin: 3px 0;

        }

        .invoice-title {

            text-align: center;
            margin: 20px 0;

        }

        .invoice-title h3 {

            margin: 0;
            border-bottom: 2px solid #000;
            display: inline-block;
            padding-bottom: 5px;

        }

        .info-table {

            width: 100%;
            margin-bottom: 20px;

        }

        .info-table td {

            padding: 5px;
            vertical-align: top;

        }

        .table {

            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;

        }

        .table th,
        .table td {

            border: 1px solid #000;
            padding: 8px;
            text-align: left;

        }

        .table th {

            background: #f1f1f1;

        }

        .text-end {

            text-align: right;

        }

        .summary {

            width: 350px;
            margin-left: auto;
            margin-top: 20px;

        }

        .summary table {

            width: 100%;
            border-collapse: collapse;

        }

        .summary td {

            border: 1px solid #000;
            padding: 8px;

        }

        .footer {

            margin-top: 60px;

        }

        .signature {

            width: 200px;
            text-align: center;
            border-top: 1px solid #000;
            padding-top: 5px;

        }

        .print-btn {

            margin-bottom: 15px;

        }

        @media print {

            .print-btn {

                display: none;

            }

        }

    </style>

</head>

<body>

<div class="container">

    {{-- PRINT BUTTON --}}
    <div class="print-btn">

        <button onclick="window.print()">

            Print Invoice

        </button>

    </div>

    {{-- COMPANY HEADER --}}
    <div class="header">

        <h2>

            {{ auth()->user()->company->company_name ?? 'Company Name' }}

        </h2>

        <p>

            {{ auth()->user()->company->address ?? '' }}

        </p>

        <p>

            Phone:
            {{ auth()->user()->company->phone ?? '' }}

        </p>

        <p>

            Email:
            {{ auth()->user()->company->email ?? '' }}

        </p>

    </div>

    {{-- TITLE --}}
    <div class="invoice-title">

        <h3>
            SALES INVOICE
        </h3>

    </div>

    {{-- INFO --}}
    <table class="info-table">

        <tr>

            <td width="50%">

                <strong>
                    Invoice No:
                </strong>

                {{ $invoice->invoice_no }}

                <br>

                <strong>
                    Invoice Date:
                </strong>

                {{ date('d M Y', strtotime($invoice->sale_date)) }}

            </td>

            <td width="50%">

                <strong>
                    Customer:
                </strong>

                {{ $invoice->customer->name ?? '-' }}

                <br>

                <strong>
                    Phone:
                </strong>

                {{ $invoice->customer->phone ?? '-' }}

            </td>

        </tr>

    </table>

    {{-- ITEMS --}}
    <table class="table">

        <thead>

            <tr>

                <th width="5%">
                    #
                </th>

                <th>
                    Product
                </th>

                <th width="10%">
                    Qty
                </th>

                <th width="15%">
                    Price
                </th>

                <th width="10%">
                    VAT %
                </th>

                <th width="15%">
                    Total
                </th>

            </tr>

        </thead>

        <tbody>

            @foreach($invoice->items as $key => $item)

            <tr>

                <td>

                    {{ $key + 1 }}

                </td>

                <td>

                    {{ $item->product->name ?? '-' }}

                </td>

                <td>

                    {{ $item->quantity }}

                </td>

                <td class="text-end">

                    {{ number_format($item->unit_price, 2) }}

                </td>

                <td class="text-end">

                    {{ number_format($item->vat_rate, 2) }}

                </td>

                <td class="text-end">

                    {{ number_format($item->total_price, 2) }}

                </td>

            </tr>

            @endforeach

        </tbody>

    </table>

    {{-- SUMMARY --}}
    <div class="summary">

        <table>

            <tr>

                <td>
                    Sub Total
                </td>

                <td class="text-end">

                    {{ number_format($invoice->subtotal, 2) }}

                </td>

            </tr>

            <tr>

                <td>
                    VAT Total
                </td>

                <td class="text-end">

                    {{ number_format($invoice->total_vat, 2) }}

                </td>

            </tr>

            <tr>

                <td>
                    Grand Total
                </td>

                <td class="text-end">

                    <strong>

                        {{ number_format($invoice->grand_total, 2) }}

                    </strong>

                </td>

            </tr>

            <tr>

                <td>
                    Paid Amount
                </td>

                <td class="text-end text-success">

                    {{ number_format($invoice->paid_amount, 2) }}

                </td>

            </tr>

            <tr>

                <td>
                    Due Amount
                </td>

                <td class="text-end text-danger">

                    {{ number_format($invoice->due_amount, 2) }}

                </td>

            </tr>

        </table>

    </div>

    {{-- NOTE --}}
    @if($invoice->note)

        <div style="margin-top: 25px;">

            <strong>
                Note:
            </strong>

            <p>

                {{ $invoice->note }}

            </p>

        </div>

    @endif

    {{-- FOOTER --}}
    <div class="footer">

        <table width="100%">

            <tr>

                <td>

                    <div class="signature">

                        Customer Signature

                    </div>

                </td>

                <td align="right">

                    <div class="signature">

                        Authorized Signature

                    </div>

                </td>

            </tr>

        </table>

    </div>

</div>

</body>

</html>