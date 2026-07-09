@extends('company.layout')

@section('content')

<div class="container">

<div class="card">

<div class="card-header">

<h4>

Edit Income

</h4>

</div>

<form
method="POST"
action="{{ route('company.income.update',$income->id) }}"
enctype="multipart/form-data"
>

@csrf

<div class="card-body">

<div class="row">

<div class="col-md-4">

<label>

Income No

</label>

<input
readonly
name="income_no"
value="{{ old('income_no',$income->income_no) }}"
class="form-control"

>

</div>

<div class="col-md-4">

<label>

Financial Year

</label>

<input
readonly
class="form-control"
value="{{ $income->financialYear->name ?? '' }}"

>

</div>

<div class="col-md-4">

<label>

Date

</label>

<input
required
type="date"
name="income_date"
value="{{ old('income_date',$income->income_date) }}"
class="form-control"

>

</div>

</div>

<div class="row mt-3">

<div class="col-md-6">

<label>

Title

</label>

<input
required
name="title"
value="{{ old('title',$income->title) }}"
class="form-control"

>

</div>

<div class="col-md-3">

<label>

Amount

</label>

<input
required
step="0.01"
type="number"
name="amount"
value="{{ old('amount',$income->amount) }}"
class="form-control"

>

</div>

<div class="col-md-3">

<label>

Category

</label>

<select
name="category"
class="form-select"

>

<option value="">

Select

</option>

@foreach($categories as $category)

<option
value="{{ $category->name }}"
{{ old('category',$income->category)==$category->name ? 'selected':'' }}
>

{{ $category->name }}

</option>

@endforeach

</select>

</div>

</div>

<div class="row mt-3">

<div class="col-md-6">

<label>

Account

</label>

<select
required
name="account_id"
class="form-select"

>

@foreach($accounts as $account)

<option
value="{{ $account->id }}"
{{ old('account_id',$income->account_id)==$account->id ? 'selected':'' }}
>

{{ $account->account_name }}

</option>

@endforeach

</select>

</div>

<div class="col-md-6">

<label>

Attachment

</label>

<input
type="file"
name="attachment"
class="form-control"

>

</div>

</div>

<div class="mt-3">

<label>

Note

</label>

<textarea
name="note"
class="form-control"
rows="4"
>{{ old('note',$income->note) }}</textarea>

</div>

</div>

<div class="card-footer">

<button
class="btn btn-primary"

>

Update Income

</button>

</div>

</form>

</div>

</div>

@endsection
