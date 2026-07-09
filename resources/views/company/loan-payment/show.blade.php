@extends('company.layout')

@section('content')

<div class="container">

<table class="table table-bordered">

<tr>

<th>

Loan

</th>

<td>

{{ $payment->loanAccount->loan_no }}

</td>

</tr>

<tr>

<th>

Principal

</th>

<td>

{{ $payment->principal_amount }}

</td>

</tr>

<tr>

<th>

Interest

</th>

<td>

{{ $payment->interest_amount }}

</td>

</tr>

<tr>

<th>

Fine

</th>

<td>

{{ $payment->fine_amount }}

</td>

</tr>

<tr>

<th>

Saving

</th>

<td>

{{ $payment->saving_amount }}

</td>

</tr>

<tr>

<th>

Total

</th>

<td>

{{ $payment->total_amount }}

</td>

</tr>

</table>

</div>


@endsection