@extends('company.layout')

@section('content')

<div class="container">

<div class="card">

<div class="card-header d-flex justify-content-between">

<h4>

Loan Details

</h4>

<button
onclick="window.print()"
class="btn btn-dark">

Print

</button>

</div>

<div class="card-body">

<table class="table table-bordered">

<tr>

<th>

Loan No

</th>

<td>

{{ $loan->loan_no }}

</td>

</tr>


<tr>

<th>

Loan Name

</th>

<td>

{{ $loan->loan_name }}

</td>

</tr>


<tr>

<th>

Party

</th>

<td>

{{ $loan->partyAccount->name ?? 'N/A' }}

</td>

</tr>


<tr>

<th>

Principal

</th>

<td>

{{ number_format(
$loan->principal_amount,
2
) }}

</td>

</tr>


<tr>

<th>

Remaining

</th>

<td>

{{ number_format(
$loan->remaining_principal,
2
) }}

</td>

</tr>


<tr>

<th>

Interest %

</th>

<td>

{{ $loan->interest_rate }}

</td>

</tr>


@if($loan->attachment)

<tr>

<th>

Attachment

</th>

<td>

<a
target="_blank"
href="{{ asset(
$loan->attachment
) }}">

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

{{ $loan->note }}

</td>

</tr>

</table>


<div class="row mt-4">

<div class="col-md-4">

<div class="card bg-success text-white">

<div class="card-body">

<small>

Saving Deposit

</small>

<h4>

{{ number_format(
$totalSavingDeposit,
2
) }}

</h4>

</div>

</div>

</div>


<div class="col-md-4">

<div class="card bg-danger text-white">

<div class="card-body">

<small>

Saving Withdraw

</small>

<h4>

{{ number_format(
$totalSavingWithdraw,
2
) }}

</h4>

</div>

</div>

</div>


<div class="col-md-4">

<div class="card bg-primary text-white">

<div class="card-body">

<small>

Saving Balance

</small>

<h4>

{{ number_format(
$currentSavingBalance,
2
) }}

</h4>

</div>

</div>

</div>

</div>

</div>

</div>

</div>
<div class="card mt-4">

<div class="card-header">

<h5>

Saving History

</h5>

</div>

<div class="card-body">

<table class="table table-bordered">

<thead>

<tr>

<th>Date</th>

<th>Type</th>

<th>Amount</th>

<th>Balance</th>

<th>Note</th>

</tr>

</thead>

<tbody>

@forelse($loan->savingLedgers as $item)

<tr>

<td>{{ $item->date }}</td>

<td>

@if($item->type=='deposit')

<span class="badge bg-success">

Deposit

</span>

@else

<span class="badge bg-danger">

Withdraw

</span>

@endif

</td>

<td>

{{ number_format($item->amount,2) }}

</td>

<td>

{{ number_format($item->balance_after,2) }}

</td>

<td>

{{ $item->note }}

</td>

</tr>

@empty

<tr>

<td colspan="5" class="text-center">

No Saving History Found

</td>

</tr>

@endforelse

</tbody>

</table>

</div>

</div>

@endsection