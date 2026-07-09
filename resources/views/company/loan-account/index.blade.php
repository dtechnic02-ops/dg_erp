@extends('company.layout')

@section('content')

<div class="container-fluid">

<div class="d-flex justify-content-between mb-3">

<h4>

Loan Accounts

</h4>

<a
href="{{ route('company.loan-account.create') }}"
class="btn btn-primary">

Create Loan

</a>

</div>

<div class="card">

<div class="card-body table-responsive">

<table class="table table-bordered">

<thead>

<tr>

<th>

Loan No

</th>

<th>

Name

</th>

<th>

Party

</th>

<th>

Principal

</th>

<th>

Remaining

</th>

<th>

Type

</th>

<th>

Action

</th>

</tr>

</thead>

<tbody>

@forelse($loans as $loan)

<tr>

<td>

{{ $loan->loan_no }}

</td>

<td>

{{ $loan->loan_name }}

</td>

<td>

{{ $loan->party_name }}

</td>

<td>

{{ number_format(
$loan->principal_amount,
2
) }}

</td>

<td>

{{ number_format(
$loan->remaining_principal,
2
) }}

</td>

<td>

{{ ucfirst(
$loan->loan_type
) }}

</td>

<td>

<div class="d-flex gap-1 flex-wrap">

<a
href="{{ route(
'company.loan-account.show',
$loan->id
) }}"
class="btn btn-dark btn-sm">

View

</a>

@if($loan->remaining_principal > 0)

<a
href="{{ route(
'company.loan-payment.create',
$loan->id
) }}"
class="btn btn-success btn-sm">

Payment

</a>

@endif

<a
href="{{ route(
'company.loan-saving-withdraw.create',
$loan->id
) }}"
class="btn btn-warning btn-sm">

Withdraw

</a>

</div>

</td>

</tr>

@empty

<tr>

<td colspan="7">

No loans found

</td>

</tr>

@endforelse

</tbody>

</table>

{{ $loans->links() }}

</div>

</div>

</div>

@endsection