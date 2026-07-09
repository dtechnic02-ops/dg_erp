@extends('company.layout')

@section('content')

<div class="container-fluid">

<div class="row justify-content-center">

<div class="col-md-7">

<div class="card border-0 shadow-sm">

<div class="card-header">

<h5 class="mb-0">

Create Expense Category

</h5>

</div>

<div class="card-body">

<form
method="POST"
action="{{ route('company.expense-category.store') }}"
>

@csrf

<div class="mb-3">

<label class="form-label">

Category Name

</label>

<input
type="text"
name="name"
class="form-control"
value="{{ old('name') }}"
required
>

@error('name')

<small class="text-danger">

{{ $message }}

</small>

@enderror

</div>


<div class="mb-3">

<label class="form-label">

Description

</label>

<textarea
name="description"
class="form-control"
rows="4"
>{{ old('description') }}</textarea>

</div>


<div class="d-flex gap-2">

<button class="btn btn-primary">

Save Category

</button>

<a
href="{{ route('company.expense-category.index') }}"
class="btn btn-light border">

Cancel

</a>

</div>

</form>

</div>

</div>

</div>

</div>

</div>

@endsection