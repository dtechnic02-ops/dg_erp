@extends('company.layout')

@section('content')


<div class="container-fluid">



<div>

<h4 class="mb-0">

Purchase Returns

</h4>

Manage purchase return invoices
<a
    href="{{ route(
        'company.purchase-return.print',
        request()->query()
    ) }}"
    target="_blank"
    class="btn btn-success"
>
    <i class="fa fa-print"></i>
    Print
</a>
</small>
<div class="card-body">
{{-- FILTER --}}
<div class="card border-0 shadow-sm mb-3">

    <div class="card-body">

        <form method="GET">

            <div class="row g-3">

                <div class="col-md-3">

                    <label class="form-label">
                        Supplier
                    </label>

                    <select
                        name="supplier_id"
                        class="form-select"
                    >

                        <option value="">
                            All Suppliers
                        </option>

                        @foreach($suppliers as $supplier)

                            <option
                                value="{{ $supplier->id }}"
                                {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}
                            >
                                {{ $supplier->name }}
                            </option>

                        @endforeach

                    </select>

                </div>

                <div class="col-md-3">

                    <label class="form-label">
                        Financial Year
                    </label>

                    <select
                        name="financial_year_id"
                        class="form-select"
                    >

                        <option value="">
                            All FY
                        </option>

                        @foreach($financialYears as $fy)

                            <option
                                value="{{ $fy->id }}"
                                {{ request('financial_year_id') == $fy->id ? 'selected' : '' }}
                            >
                                {{ $fy->name }}
                            </option>

                        @endforeach

                    </select>

                </div>
<div class="col-md-2">

    <label class="form-label">
        Status
    </label>

    <select
        name="status"
        class="form-select"
    >

        <option value="">
            Active
        </option>

        <option
            value="all"
            {{ request('status') == 'all' ? 'selected' : '' }}
        >
            All
        </option>

        <option
            value="active"
            {{ request('status') == 'active' ? 'selected' : '' }}
        >
            Active
        </option>

        <option
            value="cancelled"
            {{ request('status') == 'cancelled' ? 'selected' : '' }}
        >
            Cancelled
        </option>

    </select>

</div>
                <div class="col-md-2">

                    <label class="form-label">
                        Start Date
                    </label>

                    <input
                        type="date"
                        name="start_date"
                        class="form-control"
                        value="{{ request('start_date',$startDate) }}"
                    >

                </div>

                <div class="col-md-2">

                    <label class="form-label">
                        End Date
                    </label>

                    <input
                        type="date"
                        name="end_date"
                        class="form-control"
                        value="{{ request('end_date',$endDate) }}"
                    >

                </div>

                <div class="col-md-2">

                    <label class="form-label d-block">
                        &nbsp;
                    </label>

                    <button class="btn btn-primary">
                        Filter
                    </button>

                    <a
                        href="{{ route('company.purchase-return.index') }}"
                        class="btn btn-light border"
                    >
                        Reset
                    </a>

                </div>

            </div>

        </form>

    </div>

</div>


</div>

</div>

<div class="card border-0 shadow-sm mb-3">

    <div class="card-body">

        <div class="row text-center">

            <div class="col">

                <small class="text-muted">
                    Records
                </small>

                <h5 class="mb-0 text-primary">

                    {{ $totalRecords }}

                </h5>

            </div>

            <div class="col">

                <small class="text-muted">
                    Cancelled
                </small>

                <h5 class="mb-0 text-danger">

                    {{ $totalCancelled }}

                </h5>

            </div>

            <div class="col">

                <small class="text-muted">
                    Subtotal
                </small>

                <h5 class="mb-0">

                    {{ number_format($totalSubtotal,2) }}

                </h5>

            </div>

            <div class="col">

                <small class="text-muted">
                    Total VAT
                </small>

                <h5 class="mb-0 text-warning">

                    {{ number_format($totalVat,2) }}

                </h5>

            </div>

            <div class="col">

                <small class="text-muted">
                    Grand Total
                </small>

                <h5 class="mb-0 text-success">

                    {{ number_format($totalGrandTotal,2) }}

                </h5>

            </div>

        </div>

    </div>

</div>

<div class="card border-0 shadow-sm">

<div class="card-body p-0">

<div class="table-responsive">

<table class="table table-hover align-middle mb-0">

<thead class="table-light">

<tr>

<th>#</th>

<th>Return No</th>

<th>Date</th>

<th>Invoice</th>

<th>Supplier</th>

<th>Subtotal</th>

<th>VAT</th>

<th>Grand Total</th>

<th>Paid</th>

<th>Remaining</th>

<th>Refund Status</th>
<th>Return Status</th>

<th width="180">

Action

</th>

</tr>

</thead>

<tbody>

@forelse($returns as $key => $return)

@php

$refunded =
$return->refunds
?->sum('amount')
?? 0;

$remaining =
$return->grand_total
-
$refunded;

@endphp

<tr>

<td>

{{ $returns->firstItem() + $key }}

</td>

<td>

<strong>

{{ $return->return_no }}

</strong>

</td>

<td>

{{ date('d M Y',strtotime($return->return_date)) }}

</td>

<td>

{{ $return->purchaseInvoice->invoice_no ?? '-' }}

</td>

<td>

{{ $return->supplier->name ?? '-' }}

</td>

<td>

{{ number_format($return->subtotal,2) }}

</td>

<td>

{{ number_format($return->total_vat,2) }}

</td>

<td>

<strong class="text-danger">

{{ number_format($return->grand_total,2) }}

</strong>

</td>

<td>

<span class="text-success">

{{ number_format($refunded,2) }}

</span>

</td>

<td>

<span class="text-danger">

{{ number_format($remaining,2) }}

</span>

</td>

<td>

@if($refunded <= 0)

<span class="badge bg-danger">

Unpaid

</span>

@elseif($refunded < $return->grand_total)

<span class="badge bg-warning text-dark">

Partial

</span>

@else

<span class="badge bg-success">

Paid

</span>

@endif

</td>

<td>

@if($return->status == 1)

<span class="badge bg-success">
    Active
</span>

@else

<span class="badge bg-danger">
    Cancelled
</span>

@endif

</td>

<td>

<div class="d-flex gap-1 flex-wrap">

<a
    href="{{ route(
        'company.purchase-return.show',
        $return->id
    ) }}"
    class="btn btn-sm btn-info"
>
    View
</a>

@if(
    $remaining > 0
    &&
    $return->status == 1
)

<a
    href="{{ route(
        'company.purchase-return-refunds.create',
        $return->id
    ) }}"
    class="btn btn-sm btn-success"
>
    Refunds
</a>

@endif

@if($return->status == 1)

<form
    action="{{ route(
        'company.purchase-return.cancel',
        $return->id
    ) }}"
    method="POST"
    class="d-inline"
    onsubmit="return confirm('Are you sure to cancel this return?')"
>

    @csrf

    <button
        type="submit"
        class="btn btn-sm btn-danger"
    >
        Cancel
    </button>

</form>

@endif

</div>

</td>

</div>

</td>

</tr>

@empty

<tr>

<td colspan="13"
    class="text-center py-4">

No purchase returns found.

</td>

</tr>

@endforelse

</tbody>

</table>

</div>

</div>

</div>

<div class="mt-3">

{{ $returns->links() }}

</div>

</div>

@endsection
