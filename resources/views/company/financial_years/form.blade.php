@extends('company.layout')

@section('content')

<div class="container">

<h4>

{{ $financialYear ? 'Edit' : 'Create' }}

Financial Year

</h4>


<form method="POST"

action="{{

$financialYear

? route(
'company.financial-years.update',
$financialYear->id
)

: route(
'company.financial-years.store'
)

}}">

@csrf

@if($financialYear)

@method('PATCH')

@endif


<div class="mb-3">

<label>Name</label>

<input

class="form-control"

name="name"

value="{{ old(
'name',
$financialYear->name ?? ''
) }}"

required>

</div>


<div class="mb-3">

<label>Start Date</label>

<input

type="date"

class="form-control"

name="start_date"

value="{{ old(
'start_date',
$financialYear->start_date ?? ''
) }}"

required>

</div>


<div class="mb-3">

<label>End Date</label>

<input

type="date"

class="form-control"

name="end_date"

value="{{ old(
'end_date',
$financialYear->end_date ?? ''
) }}"

required>

</div>


<div class="mb-3">

<label>

<input

type="checkbox"

name="is_active"

value="1"

{{ old(
'is_active',
$financialYear->is_active ?? false
)

? 'checked'

: ''

}}

>

Active

</label>

</div>


<button class="btn btn-primary">

Save

</button>

</form>

</div>

@endsection