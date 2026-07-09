<!DOCTYPE html>

<html>
<head>

<title>Contra Report</title>


<link
rel="stylesheet"
href="{{ asset('assets/company/css/print.css') }}">
</head>

<body>

@include('company.partials.print-header')

<table class="table-report">

<thead>
<tr>
<th>SN</th>
<th>Date</th>
<th>Voucher No</th>
<th>From Account</th>
<th>To Account</th>
<th>Amount</th>
<th>Reference</th>
</tr>
</thead>

<tbody>

@php
$total = 0;
@endphp

@forelse($contras as $contra)

@php
$total += $contra->amount;
@endphp

<tr>
<td>{{ $loop->iteration }}</td>
<td>{{ $contra->contra_date }}</td>
<td>{{ $contra->contra_no }}</td>
<td>{{ $contra->fromAccount->account_name ?? '' }}</td>
<td>{{ $contra->toAccount->account_name ?? '' }}</td>
<td class="text-right">{{ number_format($contra->amount,2) }}</td>
<td>{{ $contra->reference_no }}</td>
</tr>

@empty

<tr>
<td colspan="7">
No Contra Found
</td>
</tr>

@endforelse

<tr>
<td colspan="5">
<b>Grand Total</b>
</td>

<td class="text-right">
<b>{{ number_format($total,2) }}</b>
</td>

<td></td>

</tr>

</tbody>

</table>
@include('company.partials.print-footer-portrait')
<script>
document.addEventListener('DOMContentLoaded', function () {
    window.print();
});
</script>