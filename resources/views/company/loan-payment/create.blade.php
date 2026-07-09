@extends('company.layout')

@section('content')

<div class="container-fluid">

<div class="card border-0 shadow-sm">

<div class="card-header">

<h4>

Loan Payment

</h4>

</div>

<div class="card-body">

<form
method="POST"
action="{{ route(
'company.loan-payment.store'
) }}"
enctype="multipart/form-data">

@csrf

<input
type="hidden"
name="loan_account_id"
value="{{ $loan->id }}">

<div class="row">

<div class="col-md-4 mb-3">

<label class="form-label">

Loan No

</label>

<input
readonly
class="form-control"
value="{{ $loan->loan_no }}">

</div>

<div class="col-md-4 mb-3">

<label class="form-label">

Party

</label>

<input
readonly
class="form-control"
value="{{ $loan->partyAccount->name ?? '-' }}">

</div>

<div class="col-md-4 mb-3">

<label class="form-label">

Remaining Principal

</label>

<input
readonly
class="form-control"
value="{{ number_format(
$loan->remaining_principal,
2
) }}">

</div>


<div class="col-md-3 mb-3">

<label class="form-label">

Principal

</label>

<input
required
type="number"
step="0.01"
name="principal_amount"
value="0"
class="form-control">

</div>


<div class="col-md-3 mb-3">

<label class="form-label">

Interest

</label>

<input
type="number"
step="0.01"
name="interest_amount"
value="0"
class="form-control">

</div>


<div class="col-md-2 mb-3">

<label class="form-label">

Fine

</label>

<input
type="number"
step="0.01"
name="fine_amount"
value="0"
class="form-control">

</div>


<div class="col-md-2 mb-3">

<label class="form-label">

Saving

</label>

<input
type="number"
step="0.01"
name="saving_amount"
value="0"
class="form-control">

</div>


<div class="col-md-2 mb-3">

<label class="form-label">

Date

</label>

<input
required
type="date"
name="payment_date"
value="{{ date('Y-m-d') }}"
class="form-control">

</div>


<div class="col-md-6 mb-3">

<label class="form-label">

Account

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

-

Balance:

{{ number_format(
$account->current_balance,
2
) }}

</option>

@endforeach

</select>

</div>


<div class="col-md-6 mb-3">

<label class="form-label">

Attachment

</label>

<input
type="file"
name="attachment"
accept=".jpg,.jpeg,.png,.pdf"
class="form-control">

<small class="text-muted">

jpg / png / pdf only

</small>

</div>


<div class="col-md-12 mb-3">

<label class="form-label">

Note

</label>

<textarea
name="note"
rows="4"
class="form-control"></textarea>

</div>

</div>

<button
class="btn btn-primary">

Save Payment

</button>

<a
href="{{ route(
'company.loan-payment.index'
) }}"
class="btn btn-dark">

Back

</a>

</form>

</div>

</div>

</div>

@endsection