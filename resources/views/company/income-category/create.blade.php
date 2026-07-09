@extends('company.layout')

@section('content')

<div class="container">

<div class="card">

<div class="card-header">

Create Income Category

</div>

<form
method="POST"
action="{{ route(
'company.income-category.store'
) }}">

@csrf

<div class="card-body">

<label>

Name

</label>

<input
required
name="name"
class="form-control">


<label class="mt-3">

Code

</label>

<input
name="code"
class="form-control">


<label class="mt-3">

Note

</label>

<textarea
name="note"
class="form-control"></textarea>

</div>


<div class="card-footer">

<button class="btn btn-primary">

Save

</button>

</div>

</form>

</div>

</div>

@endsection