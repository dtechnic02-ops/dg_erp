@extends('company.layout')

@section('content')

<div class="container">

<div class="card">

<div class="card-header d-flex justify-content-between">

<h4>

Income Categories

</h4>

<a
href="{{ route(
'company.income-category.create'
) }}"
class="btn btn-primary">

Add Category

</a>

</div>

<div class="card-body">

<table class="table table-bordered">

<tr>

<th>

Name

</th>

<th>

Code

</th>

<th>

Action

</th>

</tr>

@foreach($categories as $item)

<tr>

<td>

{{ $item->name }}

</td>

<td>

{{ $item->code }}

</td>

<td>

<form
method="POST"
action="{{ route(
'company.income-category.delete',
$item->id
) }}">

@csrf

<button
class="btn btn-danger btn-sm">

Delete

</button>

</form>

</td>

</tr>

@endforeach

</table>

{{ $categories->links() }}

</div>

</div>

</div>

@endsection