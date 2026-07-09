@extends('company.layout')

@section('content')

<div class="container-fluid">

<div class="d-flex justify-content-between mb-3">

<h4>

Financial Years

</h4>

<a class="btn btn-primary"

href="{{ route('company.financial-years.create') }}">

Add Financial Year

</a>

</div>


@if(session('success'))

<div class="alert alert-success">

{{ session('success') }}

</div>

@endif


@if(session('error'))

<div class="alert alert-danger">

{{ session('error') }}

</div>

@endif


<div class="table-responsive">

<table class="table table-bordered">

<thead>

<tr>

<th>Name</th>

<th>Start</th>

<th>End</th>

<th>Status</th>

<th width="180">

Action

</th>

</tr>

</thead>

<tbody>

@forelse($financialYears as $fy)

<tr>

<td>

{{ $fy->name }}

</td>

<td>

{{ $fy->start_date }}

</td>

<td>

{{ $fy->end_date }}

</td>

<td>

@if($fy->is_active)

<span class="badge bg-success">

Active

</span>

@else

Inactive

@endif

</td>

<td>

<a class="btn btn-sm btn-warning"

href="{{ route(
'company.financial-years.edit',
$fy->id
) }}">

Edit

</a>


<form method="POST"

style="display:inline"

action="{{ route(
'company.financial-years.destroy',
$fy->id
) }}">

@csrf

@method('DELETE')

<button

class="btn btn-sm btn-danger"

onclick="return confirm(
'Delete?'
)">

Delete

</button>

</form>

</td>

</tr>

@empty

<tr>

<td colspan="5">

No Financial Years

</td>

</tr>

@endforelse

</tbody>

</table>

</div>

{{ $financialYears->links() }}

</div>

@endsection