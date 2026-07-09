@extends('company.layout')

@section('content')

<div class="container">

<div class="card">

<div class="card-header">

<h4>

Create Income

</h4>

</div>

<form
method="POST"
enctype="multipart/form-data"
action="{{ route(
'company.income.store'
) }}">

@csrf

<div class="card-body">

<div class="row">

<div class="col-md-3">

<label>

Income No

</label>

<input
readonly
name="income_no"
value="{{ $incomeNo }}"
class="form-control">

</div>


<div class="col-md-5">

<label>

Title *

</label>

<input
required
name="title"
value="{{ old('title') }}"
class="form-control">

</div>


<div class="col-md-4">

<label>

Category

</label>

<select
name="category"
class="form-select">

<option value="">

Select Category

</option>

@foreach(

$categories as $category

)

<option
value="{{ $category->name }}"
{{ old('category')==$category->name ? 'selected' : '' }}>

{{ $category->name }}

</option>

@endforeach

</select>

</div>



<div class="col-md-4 mt-3">

<label>

Account *

</label>

<select
required
name="account_id"
class="form-select">

<option value="">

Select Account

</option>

@foreach(

$accounts as $account

)

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



<div class="col-md-4 mt-3">

<label>

Amount *

</label>

<input
required
name="amount"
type="number"
step="0.01"
value="{{ old('amount') }}"
class="form-control">

</div>



<div class="col-md-4 mt-3">

<label>

Income Date *

</label>

<input
required
type="date"
name="income_date"
value="{{ date('Y-m-d') }}"
class="form-control">

</div>



<div class="col-md-6 mt-3">

<label>

Attachment

</label>

<input
type="file"
name="attachment"
class="form-control">

</div>



<div class="col-md-12 mt-3">

<label>

Note

</label>

<textarea
rows="4"
name="note"
class="form-control">{{ old('note') }}</textarea>

</div>

</div>

</div>


<div class="card-footer">

<button class="btn btn-primary">

Save Income

</button>

</div>

</form>

</div>

</div>

@endsection