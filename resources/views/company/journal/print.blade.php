<!DOCTYPE html>

<html>

<head>

<title>

Journal Print

</title>

</head>

<body onload="window.print()">
@include('company.partials.print-header-portrait')

<button
onclick="window.print()"
class="print-btn"
>

Print

</button>

<h3 style="text-align:center">

Journal Report

</h3>

<table
border="1"
width="100%"
cellspacing="0"
cellpadding="6"
>

<tr>

<th>

Journal No

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

@foreach($journals as $journal)

<tr>

<td>

{{ $journal->journal_no }}

</td>

<td>

{{ $journal->journal_date }}

</td>

<td>

{{ $journal->financialYear->name ?? '-' }}

</td>

<td>

{{ number_format($journal->total_amount,2) }}

</td>

</tr>

@endforeach

</table>

@include(
'company.partials.print-footer-portrait'
)

</body>

</html>
