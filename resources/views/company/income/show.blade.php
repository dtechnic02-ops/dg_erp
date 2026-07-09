@extends('company.layout')

@section('content')

<link
rel="stylesheet"
href="{{ asset('assets/company/css/voucher.css') }}"
>

<div class="container">

<div class="card">

<div class="card-body">

<div class="d-flex justify-content-between align-items-center mb-3 no-print">

<h4 class="mb-0">

Income Details

</h4>

<div class="d-flex gap-2">

<a
href="{{ route('company.income.index') }}"
class="btn btn-secondary"
>

Back

</a>

<button
type="button"
class="btn btn-dark"
onclick="window.print()"
>

Print

</button>

</div>

</div>


<div id="printArea">

@include('company.partials.print-header-portrait')


<h4 class="text-center mb-3">

Income Voucher

</h4>


<table class="table table-bordered">

<tr>

<th width="250">

Income No

</th>

<td>

{{ $income->income_no }}

</td>

</tr>

<tr>

<th>

Financial Year

</th>

<td>

{{ $income->financialYear->name ?? '-' }}

</td>

</tr>

<tr>

<th>

Title

</th>

<td>

{{ $income->title }}

</td>

</tr>

<tr>

<th>

Category

</th>

<td>

{{ $income->category }}

</td>

</tr>

<tr>

<th>

Account

</th>

<td>

{{ $income->account->account_name ?? '' }}

</td>

</tr>

<tr>

<th>

Amount

</th>

<td>

<strong>

{{ number_format($income->amount,2) }}

</strong>

</td>

</tr>

<tr>

<th>

Income Date

</th>

<td>

{{ $income->income_date }}

</td>

</tr>

@if($income->attachment)

<tr>

<th>

Attachment

</th>

<td>

<a
target="_blank"
href="{{ asset($income->attachment) }}"
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

{{ $income->note }}

</td>

</tr>

<tr>

<th>

Created At

</th>

<td>

{{ $income->created_at }}

</td>

</tr>

</table>


@include('company.partials.print-footer-portrait')

</div>

</div>

</div>

</div>

@endsection