@extends('company.layout')

@push('styles')

<link
rel="stylesheet"
href="{{ asset(
'assets/company/css/form.css'
) }}">

@endpush

@section('content')

<div class="dg-page">

    <header class="dg-toolbar">
        <div class="container-fluid">
            <div class="d-flex align-items-center gap-2">

                <div class="flex-fill">
                    <h1 class="h4 mb-0">Supplier Management</h1>
                </div>

                <div class="flex-shrink-0">
                    <div class="dg-summary mb-0">
                        <div class="dg-summary-item mb-0">
                            <span>Total Current Balance</span>
                            <span class="fw-bold">{{ number_format($totalCurrentBalance, 2) }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex-fill d-flex justify-content-end align-items-center gap-2">
                    <form method="GET" class="d-flex gap-2">
                        <label for="search" class="visually-hidden">Search Supplier</label>
                        <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Search Supplier" class="form-control dg-input">
                        <button type="submit" class="btn btn-primary dg-btn">Search</button>
                    </form>

                    <a href="{{ route('company.suppliers.print', request()->query()) }}" target="_blank" class="btn btn-outline-secondary dg-btn">Print</a>

                    <button type="button" class="btn btn-success dg-btn" data-bs-toggle="modal" data-bs-target="#supplierModal">Add Supplier</button>
                </div>

            </div>
        </div>
    </header>

<main class="dg-container">
<div class="container-fluid">

<section class="dg-section">
<article class="card dg-card">

<header class="card-header dg-card-header">
<h2 class="h6 mb-0">Supplier List</h2>
</header>

<div class="card-body dg-card-body">

<div class="table-responsive">

<table class="table dg-table">

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
alt="{{ $s->name }}"
width="40"
height="40">

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

<span class="badge bg-success">

Active

</span>

@else

<span class="badge bg-secondary">

Inactive

</span>

@endif

</td>

<td>
<div class="btn-group dg-action" role="group" aria-label="Supplier actions">

    <button
        type="button"
        class="btn btn-sm btn-outline-success dg-btn"
        data-bs-toggle="modal"
        data-bs-target="#edit{{ $s->id }}">

        Edit

    </button>

    <a href="{{ route('company.supplier-ledger.index', $s->id) }}"
       class="btn btn-sm btn-outline-secondary dg-btn">

        <i class="fas fa-book"></i>

        Ledger

    </a>

    <a
href="{{ route('company.suppliers.show', $s->id) }}"
class="btn btn-sm btn-outline-info dg-btn">

<i class="fas fa-eye"></i>
 Show
</a>

    <form
        method="POST"
        action="{{ route('company.suppliers.delete', $s->id) }}"
        class="d-inline">

        @csrf

        <button
            type="submit"
            class="btn btn-sm btn-outline-danger dg-btn"
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

<nav aria-label="Supplier list pagination" class="mt-3">

{{ $suppliers->links() }}

</nav>

</div>

</article>
</section>

</div>
</main>


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
type="submit"
class="btn btn-primary dg-btn">

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
type="submit"
class="btn btn-primary dg-btn">

Save

</button>

</div>

</form>

</div>

</div>

</div>

</div>

@endsection