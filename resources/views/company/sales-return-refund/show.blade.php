@extends('company.layout')

@section('content')

@include('company.partials.print-style')

<div class="container-fluid">

<div id="printArea">

@include('company.partials.print-header-portrait')

<div class="card">

<div class="card-header d-flex justify-content-between align-items-center">

<h4>

Sales Return Refund Details

</h4>

<div>

<a href="{{ route('company.sales-return-refund.index') }}"
class="btn btn-secondary">

Back

</a>

<button
onclick="window.print()"
class="btn btn-primary">

Print

</button>

</div>

</div>

<div class="row">

<div class="col-md-6 col-6 mb-4">

<label class="fw-bold">

Refund No

</label>

<div>

{{ $refund->refund_no }}

</div>

</div>

<div class="col-md-6 col-6 mb-4">

<label class="fw-bold">

Refund Date

</label>

<div>

{{ $refund->refund_date }}

</div>

</div>

<div class="col-md-6 col-6 mb-4">

<label class="fw-bold">

Customer

</label>

<div>

{{ $refund->customer->name ?? '-' }}

</div>

</div>

<div class="col-md-6 col-6 mb-4">

<label class="fw-bold">

Account

</label>

<div>

{{ $refund->account->account_name ?? '-' }}

</div>

</div>

<div class="col-md-6 col-6 mb-4">

<label class="fw-bold">

Sales Return No

</label>

<div>

{{ $refund->salesReturn->return_no ?? '-' }}

</div>

</div>

<div class="col-md-6 col-6 mb-4">

<label class="fw-bold">

Refund Amount

</label>

<div>

{{ number_format($refund->refund_amount,2) }}

</div>

</div>

<div class="col-12">

<label class="fw-bold">

Note

</label>

<div>

{{ $refund->note ?? '-' }}

</div>

</div>

</div>


@include('company.partials.print-footer-portrait')

</div>

</div>

@endsection
