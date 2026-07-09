@extends('company.layout')

@section('content')

<div class="container-fluid">

<div class="card border-0 shadow-sm">

<div class="card-header">

<h4>

Loan Saving Withdraw

</h4>

</div>

<div class="card-body">

<form
method="POST"
action="{{ route(
'company.loan-saving-withdraw.store'
) }}">

@csrf

<input
type="hidden"
name="loan_account_id"
value="{{ $loan->id }}">

<div class="row">

<div class="col-md-4 mb-3">

<label>

Loan No

</label>

<input
readonly
class="form-control"
value="{{ $loan->loan_no }}">

</div>


<div class="col-md-4 mb-3">

<label>

Party

</label>

<input
readonly
class="form-control"
value="{{ $loan->partyAccount->name ?? '-' }}">

</div>


<div class="col-md-4 mb-3">

<label>

Current Saving Balance

</label>

<input
readonly
class="form-control"
value="{{ number_format(
$savingBalance,
2
) }}">

</div>


<div class="col-md-4 mb-3">

<label>

Withdraw Amount

</label>

<input
required
type="number"
step="0.01"
name="amount"
class="form-control">

</div>


<div class="col-md-4 mb-3">

<label>

Withdraw Date

</label>

<input
required
type="date"
name="date"
value="{{ date('Y-m-d') }}"
class="form-control">

</div>


<div class="col-md-4 mb-3">

<label>

Receive From Account

</label>

<select
required
name="account_id"
class="form-select">

<option value="">

Select Account

</option>

@foreach($accounts as $account)

<option
value="{{ $account->id }}">

{{ $account->account_name }}

(

{{ number_format(
$account->current_balance,
2
) }}

)

</option>

@endforeach

</select>

</div>


<div class="col-md-12 mb-3">

<label>

Note

</label>

<textarea
name="note"
rows="3"
class="form-control"></textarea>

</div>

</div>

<button
class="btn btn-primary">

Withdraw Saving

</button>

<a
href="{{ route(
'company.loan-account.show',
$loan->id
) }}"
class="btn btn-dark">

Back

</a>

</form>

</div>

</div>

</div>

@endsection