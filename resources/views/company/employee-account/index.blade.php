@extends('company.layout')

@section('content')

<div class="container">

<div class="card">

<div class="card-header d-flex justify-content-between">

<h4>

Employees

</h4>

<a
href="{{ route('company.employee-account.create') }}"
class="btn btn-primary">

Add Employee

</a>

</div>


<div class="card-body">

<form
class="mb-3">

<div class="row">

<div class="col-md-4">

<input
name="search"
value="{{ request('search') }}"
placeholder="Search employee..."
class="form-control">

</div>

<div class="col-md-2">

<button class="btn btn-dark">

Filter

</button>

</div>

</div>

</form>


<table class="table table-bordered">

<thead>

<tr>

<th>Code</th>

<th>Name</th>

<th>Phone</th>

<th>Designation</th>

<th>Salary</th>

<th width="220">

Action

</th>

</tr>

</thead>

<tbody>

@forelse($employees as $employee)

<tr>

<td>

{{ $employee->employee_code }}

</td>

<td>

{{

trim(

$employee->first_name

.' '

.$employee->last_name

)

}}

</td>

<td>

{{ $employee->phone }}

</td>

<td>

{{ $employee->designation }}

</td>

<td>

{{ number_format(
$employee->basic_salary,
2
) }}

</td>

<td>

<div class="d-flex gap-1">

<a
class="btn btn-dark btn-sm"
href="{{ route(
'company.employee-account.show',
$employee->id
) }}">

View

</a>


<a
class="btn btn-warning btn-sm"
href="{{ route(
'company.employee-account.edit',
$employee->id
) }}">

Edit

</a>


<form
method="POST"
action="{{ route(
'company.employee-account.delete',
$employee->id
) }}">

@csrf

<button
onclick="return confirm('Delete Employee?')"
class="btn btn-danger btn-sm">

Delete

</button>

</form>

</div>

</td>

</tr>

@empty

<tr>

<td colspan="6">

No Employees

</td>

</tr>

@endforelse

</tbody>

</table>

{{ $employees->links() }}

</div>

</div>

</div>

@endsection