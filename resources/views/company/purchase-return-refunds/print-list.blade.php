@include('company.partials.print-header-portrait')

<!DOCTYPE html>
<html>

<head>

<link
rel="stylesheet"
href="{{ asset('assets/company/css/print.css') }}"
>

<title>

Purchase Return Refund Report

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

Purchase Return Refund Report

</div>

<table class="table-report">

<thead>

<tr>

<th>#</th>

<th>Date</th>

<th>Refund No</th>

<th>Supplier</th>

<th>Account</th>

<th>Amount</th>

</tr>

</thead>

<tbody>

@php
$total = 0;
@endphp

@forelse($refunds as $refund)

@php
$total += $refund->amount;
@endphp

<tr>

<td>
{{ $loop->iteration }}
</td>

<td>
{{ $refund->refund_date }}
</td>

<td>
{{ $refund->refund_no }}
</td>

<td>
{{ $refund->purchaseReturn->supplier->name ?? '-' }}
</td>

<td>
{{ $refund->account->account_name ?? '-' }}
</td>

<td class="text-right">
{{ number_format($refund->amount,2) }}
</td>

</tr>

@empty

<tr>

<td colspan="6">

No Refund Found

</td>

</tr>

@endforelse

<tr>

<td colspan="5">

<b>Total</b>

</td>

<td class="text-right">

<b>

{{ number_format($total,2) }}

</b>

</td>

</tr>

</tbody>

</table>

@include('company.partials.print-footer-portrait')

</body>

</html>