@extends('company.layout')

@section('content')

<link
rel="stylesheet"
href="{{ asset('assets/company/css/voucher.css') }}"
>
<div class="card-body">

<!-- TOP -->

<div class="d-flex justify-content-between align-items-center mb-3 no-print">

<h4 class="mb-0">

Journal Voucher

</h4>

<button
type="button"
class="btn btn-dark"
onclick="window.print()"
>

Print

</button>

</div>


<!-- PRINT AREA -->

<div id="printArea">

@include('company.partials.print-header-portrait')


<!-- VOUCHER INFO -->

<table class="table table-bordered">

<tr>

<th width="220">

Journal No

</th>

<td>

{{ $journal->journal_no }}

</td>

</tr>

<tr>

<th>

Financial Year

</th>

<td>

{{ $journal->financialYear->name ?? '-' }}

</td>

</tr>

<tr>

<th>

Date

</th>

<td>

{{ $journal->journal_date }}

</td>

</tr>

<tr>

<th>

Total Amount

</th>

<td>

{{ number_format($journal->total_amount,2) }}

</td>

</tr>

@if($journal->attachment)

<tr>

<th>

Attachment

</th>

<td>

<a
target="_blank"
href="{{ asset($journal->attachment) }}"
>

View Attachment

</a>

</td>

</tr>

@endif

<tr>

<th>

Note

</th>

<td>

{{ $journal->note }}

</td>

</tr>

</table>


<!-- ITEMS -->

<h5 class="mt-4 mb-3">

Journal Items

</h5>

<div class="table-responsive">

<table class="table table-bordered">

<thead>

<tr>

<th>

Account

</th>

<th width="120">

Debit

</th>

<th width="120">

Credit

</th>

<th>

Note

</th>

</tr>

</thead>

<tbody>

@php

$totalDebit=0;

$totalCredit=0;

@endphp


@forelse($journal->items as $item)

<tr>

<td>

{{ $item->account->account_name ?? '' }}

</td>

<td>

@if($item->type=='debit')

{{ number_format($item->amount,2) }}

@php

$totalDebit += $item->amount;

@endphp

@endif

</td>

<td>

@if($item->type=='credit')

{{ number_format($item->amount,2) }}

@php

$totalCredit += $item->amount;

@endphp

@endif

</td>

<td>

{{ $item->note }}

</td>

</tr>

@empty

<tr>

<td colspan="4">

No Journal Items

</td>

</tr>

@endforelse


<tr class="table-dark">

<th>

TOTAL

</th>

<th>

{{ number_format($totalDebit,2) }}

</th>

<th>

{{ number_format($totalCredit,2) }}

</th>

<th>

</th>

</tr>

</tbody>

</table>

</div>


@include('company.partials.print-footer-portrait')

</div>

</div>

</div>

</div>

@endsection