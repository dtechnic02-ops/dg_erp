@include('company.partials.print-header-portrait')

<!DOCTYPE html>
<html>

<head>

<link rel="stylesheet"
      href="{{ asset('assets/company/css/print.css') }}">

<title>
Purchase Payment Report
</title>

</head>

<body onload="window.print()">

<button
class="print-btn"
onclick="window.print()">
Print
</button>

<div class="report-title">
Purchase Payment Report
</div>

<table class="table-report">

<thead>

<tr>
    <th>Payment No</th>
    <th>Date</th>
    <th>Supplier</th>
    <th>Account</th>
    <th>Amount</th>
</tr>

</thead>

<tbody>

@php
$total = 0;
@endphp

@forelse($payments as $payment)

@php
$total += $payment->amount;
@endphp

<tr>

<td>{{ $payment->payment_no }}</td>

<td>{{ $payment->payment_date }}</td>

<td>{{ $payment->supplier->name ?? '' }}</td>

<td>{{ $payment->account->account_name ?? '' }}</td>

<td class="text-right">
{{ number_format($payment->amount,2) }}
</td>

</tr>

@empty

<tr>
<td colspan="5">
No Payment Found
</td>
</tr>

@endforelse

<tr>

<td colspan="4">
<b>Total</b>
</td>

<td class="text-right">
<b>{{ number_format($total,2) }}</b>
</td>

</tr>

</tbody>

</table>

@include('company.partials.print-footer-portrait')

</body>
</html>