@include('company.partials.print-header-portrait')

<!DOCTYPE html>

<html>

<head>
<link
    rel="stylesheet"
    href="{{ asset('assets/company/css/print.css') }}"
>
<title>

Income Report

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

Income Report

</div>
<table class="table-report">

<thead>

<tr>

<th>

Income No

</th>

<th>

Title

</th>

<th>

Account

</th>

<th>

Date

</th>

<th>

FY

</th>

<th>

Amount

</th>

</tr>

</thead>

<tbody>

@php

$total=0;

@endphp

@forelse($incomes as $income)

@php

$total += $income->amount;

@endphp

<tr>

<td>

{{ $income->income_no }}

</td>

<td>

{{ $income->title }}

</td>

<td>

{{ $income->account->account_name ?? '' }}

</td>

<td>

{{ $income->income_date }}

</td>

<td>

{{ $income->financialYear->name ?? '-' }}

</td>

<td class="text-right">

{{ number_format(

$income->amount,

2

) }}

</td>

</tr>

@empty

<tr>

<td colspan="6">

No Income Found

</td>

</tr>

@endforelse

<tr>

<td colspan="5">

<b>

Total

</b>

</td>

<td class="text-right">

<b>

{{ number_format(

$total,

2

) }}

</b>

</td>

</tr>

</tbody>

</table>

@include('company.partials.print-footer-portrait')

</body>

</html>
