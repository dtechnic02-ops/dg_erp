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

    Purchase Payment Details

</h4>

<div class="d-flex gap-2">

    <a
        href="{{ route('company.purchase-payments.index') }}"
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


Purchase Payment Voucher

</h4>

<table class="table table-bordered">

<tr>

<th width="250">

    Payment No

</th>

<td>

    {{ $payment->payment_no }}

</td>


</tr>

<tr>


<th>

    Financial Year

</th>

<td>

    {{ $payment->financialYear->name ?? '-' }}

</td>


</tr>

<tr>

<th>

    Payment Date

</th>

<td>

    {{ $payment->payment_date }}

</td>


</tr>

<tr>


<th>

    Supplier

</th>

<td>

    {{ $payment->supplier->name ?? '-' }}

</td>


</tr>

<tr>

<th>

    Invoice No

</th>

<td>

    {{ $payment->invoice->invoice_no ?? '-' }}

</td>


</tr>

<tr>


<th>

    Account

</th>

<td>

    {{ $payment->account->account_name ?? '-' }}

</td>


</tr>

<tr>


<th>

    Amount

</th>

<td>

    <strong>

        {{ number_format($payment->amount,2) }}

    </strong>

</td>


</tr>

<tr>


<th>

    Payment Method

</th>

<td>

    {{ $payment->payment_method ?? '-' }}

</td>


</tr>

<tr>


<th>

    Reference No

</th>

<td>

    {{ $payment->reference_no ?? '-' }}

</td>


</tr>

@if($payment->receipt_file)

<tr>


<th>

    Receipt File

</th>

<td>

    <a
        target="_blank"
        href="{{ asset($payment->receipt_file) }}"
    >
        View Receipt
    </a>

</td>


</tr>

@endif

<tr>


<th>

    Note

</th>

<td>

    {{ $payment->note ?? '-' }}

</td>


</tr>

<tr>


<th>

    Created At

</th>

<td>

    {{ $payment->created_at }}

</td>


</tr>

</table>

@include('company.partials.print-footer-portrait')

</div>

</div>

</div>

</div>

@endsection
