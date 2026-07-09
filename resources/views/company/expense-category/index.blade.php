@extends('company.layout')

@section('content')

<div class="container-fluid">

<div class="d-flex justify-content-between align-items-center mb-3">

<div>

<h4 class="mb-0">

Expense Categories

</h4>

<small class="text-muted">

Manage expense categories

</small>

</div>

<a
href="{{ route('company.expense-category.create') }}"
class="btn btn-primary">

Add Category

</a>

</div>


<div class="card border-0 shadow-sm mb-3">

<div class="card-body">

<form method="GET">

<div class="row">

<div class="col-md-4">

<input
type="text"
name="search"
class="form-control"
placeholder="Search category..."
value="{{ request('search') }}"
>

</div>

<div class="col-md-2">

<button class="btn btn-primary">

Filter

</button>

<a
href="{{ route('company.expense-category.index') }}"
class="btn btn-light border">

Reset

</a>

</div>

</div>

</form>

</div>

</div>


<div class="card border-0 shadow-sm">

<div class="table-responsive">

<table class="table table-hover mb-0">

<thead class="table-light">

<tr>

<th>#</th>

<th>Name</th>

<th>Description</th>

<th>Status</th>

</tr>

</thead>

<tbody>

@forelse($categories as $key=>$category)

<tr>

<td>

{{ $categories->firstItem()+$key }}

</td>

<td>

{{ $category->name }}

</td>

<td>

{{ $category->description ?? '-' }}

</td>

<td>

@if($category->status)

<span class="badge bg-success">

Active

</span>

@else

<span class="badge bg-danger">

Inactive

</span>

@endif

</td>

</tr>

@empty

<tr>

<td colspan="4"
class="text-center">

No category found.

</td>

</tr>

@endforelse

</tbody>

</table>

</div>

</div>


<div class="mt-3">

{{ $categories->links() }}

</div>

</div>

@endsection