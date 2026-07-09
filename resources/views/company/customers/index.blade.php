@extends('company.layout')
@push('styles')

<link

rel="stylesheet"

href="{{ asset(

'assets/company/css/form.css'

) }}">
@section('content')

<div class="page-title">

<h3>

👥 Customer Management

</h3>

<div class="d-flex gap-2 flex-wrap">

<form
method="GET"
class="d-flex gap-2">

<input
name="search"
value="{{ request('search') }}"
placeholder="Search Customer"
class="erp-input">

<button
class="erp-btn btn-blue">

Search

</button>

</form>

<a
href="{{ route(
'company.customers.print'
) }}"
target="_blank"
class="erp-btn btn-blue">

Print

</a>

<button
class="erp-btn btn-green"
data-bs-toggle="modal"
data-bs-target="#customerModal">

* Add Customer

</button>

</div>

</div>

<div class="card-box">

<div class="table-responsive">

<table class="erp-table">

<thead>

<tr>

<th>Image</th>
<th>Name</th>
<th>Authority</th>
<th>Mobile</th>
<th>Email</th>
<th>Balance</th>
<th>Status</th>
<th width="170">Action</th>

</tr>

</thead>

<tbody>

@forelse($customers as $c)

<tr>

<td>

@if($c->image_path)

<img
src="{{ asset($c->image_path) }}"
class="erp-image">

@endif

</td>

<td>{{ $c->name }}</td>

<td>{{ $c->authority_name }}</td>

<td>{{ $c->mobile }}</td>

<td>{{ $c->email }}</td>

<td>

{{ number_format(
$c->current_balance,
2
) }}

</td>

<td>

@if($c->status=='active')

<span class="stock stock-ok">

Active

</span>

@else

<span class="stock stock-out">

Inactive

</span>

@endif

</td>

<td>

<div class="action-box">

<button
class="erp-btn btn-green"
data-bs-toggle="modal"
data-bs-target="#edit{{ $c->id }}">

Edit

</button>
<a
href="{{ route(

    'company.customers.show',

    $c->id

) }}"
class="erp-btn btn-info">

View

</a>

<form
method="POST"
action="{{ route(
'company.customers.delete',
$c->id
) }}">

@csrf

<button
class="erp-btn btn-red"
onclick="return confirm('Delete this customer?')">
Delete

</button>

</form>

</div>

</td>

</tr>

@empty

<tr>

<td colspan="8">

No Customers Found

</td>

</tr>

@endforelse

</tbody>

</table>

</div>

<div class="mt-3">

{{ $customers->links() }}

</div>

</div>

@foreach($customers as $c)

<div
class="modal fade"
id="edit{{ $c->id }}">

<div class="modal-dialog modal-xl">

<div class="modal-content">

<form
method="POST"
enctype="multipart/form-data"
action="{{ route(
'company.customers.update',
$c->id
) }}">

@csrf

<div class="modal-header">

<h5>Edit Customer</h5>

<button
type="button"
class="btn-close"
data-bs-dismiss="modal">

</button>

</div>

<div class="modal-body">

@include(
'company.customers.form',
[
'customer'=>$c
]
)

</div>

<div class="modal-footer">

<button
class="erp-btn btn-blue">

Update

</button>

</div>

</form>

</div>

</div>

</div>

@endforeach

<div
class="modal fade"
id="customerModal">

<div class="modal-dialog modal-xl">

<div class="modal-content">

<form
method="POST"
enctype="multipart/form-data"
action="{{ route(
'company.customers.store'
) }}">

@csrf

<div class="modal-header">

<h5>

Add Customer

</h5>

<button
type="button"
class="btn-close"
data-bs-dismiss="modal">

</button>

</div>

<div class="modal-body">

@include(
'company.customers.form'
)

</div>

<div class="modal-footer">

<button
class="erp-btn btn-blue">

Save

</button>

</div>

</form>

</div>

</div>

</div>

@endsection
