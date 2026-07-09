@extends('company.layout')

@section('content')

<div class="container-fluid">

    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-3">

        <div>

            <h4 class="mb-0">
                Sales Returns
            </h4>

            <small class="text-muted">
                Manage sales return invoices
            </small>

        </div>

    </div>

    {{-- FILTER --}}
    <div class="card border-0 shadow-sm mb-3">

        <div class="card-body">

            <form method="GET">

                <div class="row g-3">

                    <div class="col-md-3">

                        <label class="form-label">
                            Customer
                        </label>

                        <select
                            name="customer_id"
                            class="form-select"
                        >

                            <option value="">
                                All Customers
                            </option>

                            @foreach($customers as $customer)

                                <option
                                    value="{{ $customer->id }}"
                                    {{ request('customer_id') == $customer->id ? 'selected' : '' }}
                                >
                                    {{ $customer->name }}
                                </option>

                            @endforeach

                        </select>

                    </div>

                    <div class="col-md-3">

                        <label class="form-label">
                            Start Date
                        </label>

                        <input
                            type="date"
                            name="start_date"
                            class="form-control"
                            value="{{ request('start_date') }}"
                        >

                    </div>

                    <div class="col-md-3">

                        <label class="form-label">
                            End Date
                        </label>

                        <input
                            type="date"
                            name="end_date"
                            class="form-control"
                            value="{{ request('end_date') }}"
                        >

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
                    <div class="col-md-3">

                        <label class="form-label d-block">
                            &nbsp;
                        </label>

                        <button class="btn btn-primary">

                            Filter

                        </button>

                        <a
                            href="{{ route('company.sales-return.index') }}"
                            class="btn btn-light border"
                        >
                            Reset
                        </a>

                    </div>

                </div>

            </form>

        </div>

    </div>

    {{-- SUMMARY --}}
    <div class="row mb-3">

        <div class="col-md-4">

            <div class="card border-0 shadow-sm">

                <div class="card-body">

                    <small class="text-muted">
                        Subtotal
                    </small>

                    <h5 class="mb-0 mt-2">

                        {{ number_format($returns->sum('subtotal'), 2) }}

                    </h5>

                </div>

            </div>

        </div>

        <div class="col-md-4">

            <div class="card border-0 shadow-sm">

                <div class="card-body">

                    <small class="text-muted">
                        Total VAT
                    </small>

                    <h5 class="mb-0 mt-2 text-warning">

                        {{ number_format($returns->sum('total_vat'), 2) }}

                    </h5>

                </div>

            </div>

        </div>

        <div class="col-md-4">

            <div class="card border-0 shadow-sm">

                <div class="card-body">

                    <small class="text-muted">
                        Grand Total
                    </small>

                    <h5 class="mb-0 mt-2 text-danger">

                        {{ number_format($returns->sum('grand_total'), 2) }}

                    </h5>

                </div>

            </div>

        </div>

    </div>

    {{-- TABLE --}}
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

                            <th>Customer</th>

                            <th>Subtotal</th>

                            <th>VAT</th>

                            <th>Grand Total</th>
                            <th>Refunded</th>

<th>Remaining</th>

<th>Refund Status</th>

                            <th>Status</th>

                            <th width="150">
                                Action
                            </th>

                        </tr>

                    </thead>

                   <tbody>

    @forelse($returns as $key => $return)

        @php

            $refunded =
                $return->refunds
                ->sum('refund_amount');

            $remaining =
                $return->grand_total
                - $refunded;

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

                {{ date('d M Y', strtotime($return->return_date)) }}

            </td>

            <td>

                {{ $return->invoice->invoice_no ?? '-' }}

            </td>

            <td>

                {{ $return->customer->name ?? '-' }}

            </td>

            <td>

                {{ number_format($return->subtotal, 2) }}

            </td>

            <td>

                {{ number_format($return->total_vat, 2) }}

            </td>

            <td>

                <strong class="text-danger">

                    {{ number_format($return->grand_total, 2) }}

                </strong>

            </td>

            <td>

                <span class="text-success">

                    {{ number_format($refunded, 2) }}

                </span>

            </td>

            <td>

                <span class="text-danger">

                    {{ number_format($remaining, 2) }}

                </span>

            </td>

            <td>

                @if($refunded <= 0)

                    <span class="badge bg-danger">
                        Unpaid
                    </span>

                @elseif(
                    $refunded <
                    $return->grand_total
                )

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

                <span class="badge bg-success">
                    Returned
                </span>

            </td>

            <td>

                <div class="d-flex gap-1 flex-wrap">

                    <a
                        href="{{ route('company.sales-return.show', $return->id) }}"
                        class="btn btn-sm btn-info"
                    >
                        View
                    </a>

                    <a
                        href="{{ route('company.sales-return.print', $return->id) }}"
                        class="btn btn-sm btn-dark"
                    >
                        Print
                    </a>

                    @if($remaining > 0)

                    

                        <a
                            href="{{ route('company.sales-return-refund.create', $return->id) }}"
                            class="btn btn-sm btn-warning"
                        >
                            Refund
                        </a>

                    @endif

                </div>

            </td>

        </tr>

    @empty

        <tr>

            <td
                colspan="13"
                class="text-center py-4"
            >

                No sales returns found.

            </td>

        </tr>

    @endforelse

</tbody>

                </table>

            </div>

        </div>

    </div>

    {{-- PAGINATION --}}
    <div class="mt-3">

        {{ $returns->links() }}

    </div>

</div>

@endsection