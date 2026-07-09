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

Purchase Return Refund Details

</h4>

<div class="d-flex gap-2">

<a
href="{{ route('company.purchase-return-refunds.index') }}"
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

Purchase Return Refund Voucher

</h4>

<table class="table table-bordered">

<tr>
<th width="250">Refund No</th>
<td>{{ $refund->refund_no }}</td>
</tr>

<tr>
<th>Financial Year</th>
<td>{{ $refund->financialYear->name ?? '-' }}</td>
</tr>

<tr>
<th>Refund Date</th>
<td>{{ $refund->refund_date }}</td>
</tr>

<tr>
<th>Supplier</th>
<td>
{{ $refund->purchaseReturn->supplier->name ?? '-' }}
</td>
</tr>

<tr>
<th>Return No</th>
<td>
{{ $refund->purchaseReturn->return_no ?? '-' }}
</td>
</tr>

<tr>
<th>Account</th>
<td>
{{ $refund->account->account_name ?? '-' }}
</td>
</tr>

<tr>
<th>Amount</th>
<td>

<strong>

{{ number_format(
    $refund->amount,
    2
) }}

</strong>

</td>
</tr>

<tr>
<th>Note</th>
<td>

{{ $refund->note ?? '-' }}

</td>
</tr>

<tr>
<th>Created At</th>
<td>

{{ $refund->created_at }}

</td>
</tr>

</table>

@include('company.partials.print-footer-portrait')

</div>

</div>

</div>

</div>

@endsection