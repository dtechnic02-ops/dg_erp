@extends('company.layout')

@section('content')

<div class="container-fluid">

<div class="d-flex justify-content-between mb-3">

<div>

<h4>

Loan Payments

</h4>

<small class="text-muted">

Loan payment history

</small>

</div>

</div>

<div class="card border-0 shadow-sm">

<div class="card-body table-responsive">

<table class="table table-bordered align-middle">

<thead class="table-light">

<tr>

<th>

Date

</th>

<th>

Loan No

</th>

<th>

Party

</th>

<th>

Principal

</th>

<th>

Interest

</th>
<th>

Fine

</th>

<th>

Saving

</th>
<th>

Total

</th>

<th>

Remaining

</th>

<th>

Action

</th>

</tr>

</thead>

<tbody>

@forelse($payments as $payment)

<tr>

<td>

{{ $payment->payment_date }}

</td>

<td>

{{ $payment->loanAccount->loan_no ?? '-' }}

</td>

<td>

{{ $payment->loanAccount->partyAccount->name ?? '-' }}

</td>

<td>

{{ number_format(
$payment->principal_amount,
2
) }}

</td>

<td>

{{ number_format(
$payment->interest_amount,
2
) }}

</td>
<td>

{{ number_format(
$payment->fine_amount,
2
) }}

</td>

<td class="text-primary fw-bold">

{{ number_format(
$payment->saving_amount,
2
) }}

</td>
<td>

<strong class="text-success">

{{ number_format(
$payment->total_amount,
2
) }}

</strong>

</td>

<td>

<span class="text-danger">

{{ number_format(
$payment->remaining_principal,
2
) }}

</span>

</td>

<td>

<div class="d-flex gap-1">
<div class="d-flex gap-1">

<a
href="{{ route(
'company.loan-payment.show',
$payment->id
) }}"
class="btn btn-sm btn-primary">

View

</a>

</div>

</div>

</td>

</tr>

@empty

<tr>

<td
colspan="8"
class="text-center">

No payments found

</td>

</tr>

@endforelse

</tbody>

</table>

<div class="mt-3">

{{ $payments->links() }}

</div>

</div>

</div>

</div>

@endsection