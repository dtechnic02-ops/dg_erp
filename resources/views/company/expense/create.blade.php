@extends('company.layout')

@section('content')

<div class="container-fluid">

<div class="row justify-content-center">

<div class="col-md-8">

<div class="card shadow-sm border-0">

<div class="card-header">

Create Expense

</div>

<div class="card-body">

<form
method="POST"
action="{{ route('company.expense.store') }}"
enctype="multipart/form-data">

@csrf


<div class="row">

<div class="col-md-6 mb-3">

<label>

Expense No

</label>

<input
class="form-control"
value="{{ $expenseNo }}"
readonly>

</div>


<div class="col-md-6 mb-3">

<label>

Date

</label>

<input
type="date"
name="expense_date"
class="form-control"
required>

</div>

</div>


<div class="mb-3">

<label>

Category

</label>

<select
name="expense_category_id"
class="form-select"
required>

<option value="">

Select Category

</option>

@foreach($categories as $category)

<option value="{{ $category->id }}">

{{ $category->name }}

</option>

@endforeach

</select>

</div>


<div class="mb-3">

<label>

Account

</label>

<select
name="account_id"
class="form-select"
required>

<option value="">

Select Account

</option>

@foreach($accounts as $account)

<option value="{{ $account->id }}">

{{ $account->account_name }}

</option>

@endforeach

</select>

</div>


<div class="mb-3">

<label>

Amount

</label>

<input
type="number"
step="0.01"
name="amount"
class="form-control"
required>

</div>


<div class="mb-3">

<label>

Reference No

label>

<input
name="reference_no"
class="form-control">

</div>


<div class="mb-3">

<label>

Attachment

</label>

<input
type="file"
name="attachment"
accept=".jpg,.jpeg,.png,.pdf"
class="form-control">

</div>


<div class="mb-3">

<label>

Note

</label>

<textarea
name="note"
class="form-control"></textarea>

</div>


<button class="btn btn-primary">

Save Expense

</button>

</form>

</div>

</div>

</div>

</div>

</div>

@endsection