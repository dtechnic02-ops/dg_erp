@extends('company.layout')

@push('styles')

<link
rel="stylesheet"
href="{{ asset(
'assets/company/css/form.css'
) }}">

@endpush

@section('content')

<div class="page-title">

<h3>

🏢 Supplier Management

</h3>

<div class="d-flex gap-2 flex-wrap">

<form
method="GET"
class="d-flex gap-2">

<input
name="search"
value="{{ request('search') }}"
placeholder="Search Supplier"
class="erp-input">

<button
class="erp-btn btn-blue">

Search

</button>

</form>



<button
class="erp-btn btn-green"
data-bs-toggle="modal"
data-bs-target="#supplierModal">

+ Add Supplier

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

@forelse($suppliers as $s)

<tr>

<td>

@if($s->image_path)

<img
src="{{ asset($s->image_path) }}"
class="erp-image">

@endif

</td>

<td>{{ $s->name }}</td>

<td>{{ $s->authority_name }}</td>

<td>{{ $s->mobile }}</td>

<td>{{ $s->email }}</td>

<td>

{{ number_format(
$s->current_balance,
2
) }}

</td>

<td>

@if($s->status=='active')

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
        data-bs-target="#edit{{ $s->id }}">

        Edit

    </button>

    <a href="{{ route('company.supplier-ledger.index', $s->id) }}"
       class="erp-btn btn-blue">

        <i class="fas fa-book"></i>

        Ledger

  <a
href="{{ route('company.suppliers.show', $s->id) }}"
class="btn btn-info btn-sm">

<i class="fas fa-eye"></i>
 Show
</a>

    <form
        method="POST"
        action="{{ route('company.suppliers.delete', $s->id) }}">

        @csrf

        <button
            class="erp-btn btn-red"
            onclick="return confirm('Delete Supplier?')">

            Delete

        </button>

    </form>

</div>

</td>

</tr>

@empty

<tr>

<td colspan="8">

No Suppliers Found

</td>

</tr>

@endforelse

</tbody>

</table>

</div>

<div class="mt-3">

{{ $suppliers->links() }}

</div>

</div>


{{-- Edit Modals --}}

@foreach($suppliers as $s)

<div
class="modal fade"
id="edit{{ $s->id }}">

<div class="modal-dialog modal-xl">

<div class="modal-content">

<form
method="POST"
enctype="multipart/form-data"
action="{{ route(
'company.suppliers.update',
$s->id
) }}">

@csrf

<div class="modal-header">

<h5>

Edit Supplier

</h5>

<button
type="button"
class="btn-close"
data-bs-dismiss="modal">

</button>

</div>

<div class="modal-body">

@include(
'company.suppliers.form',
[
'supplier'=>$s
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


{{-- Add Modal --}}

<div
class="modal fade"
id="supplierModal">

<div class="modal-dialog modal-xl">

<div class="modal-content">

<form
method="POST"
enctype="multipart/form-data"
action="{{ route(
'company.suppliers.store'
) }}">

@csrf

<div class="modal-header">

<h5>

Add Supplier

</h5>

<button
type="button"
class="btn-close"
data-bs-dismiss="modal">

</button>

</div>

<div class="modal-body">

@include(
'company.suppliers.form'
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