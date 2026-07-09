@extends('company.layout')

@section('content')

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<div class="container-fluid">

    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-3">

        <h4 class="text-white">
            Sales Invoices
        </h4>

        <a
            href="{{ route('company.sales.create') }}"
            class="btn btn-primary btn-sm"
        >

            + New Sales

        </a>

    </div>

    {{-- FILTER --}}
    <div class="card mb-3">

        <div class="card-body">

            <form method="GET">

                <div class="row g-3">

                    <div class="col-md-3">

                        <label class="form-label">
                            Search
                        </label>

                        <input
                            type="text"
                            name="search"
                            class="form-control"
                            placeholder="Invoice no / customer"
                            value="{{ request('search') }}"
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

                    <div class="col-md-2">

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

                    <div class="col-md-2">

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

                    <div class="col-md-2">

                        <label class="form-label d-block">
                            &nbsp;
                        </label>

                        <button class="btn btn-primary">

                            Filter

                        </button>

                        <a
                            href="{{ route('company.sales.index') }}"
                            class="btn btn-light border"
                        >
                            Reset
                        </a>

                    </div>

                </div>

            </form>

        </div>

    </div>

    {{-- TABLE --}}
    <div class="card">

        <div class="card-body table-responsive">

            <table class="table table-bordered table-sm align-middle">

                <thead class="table-light">

                    <tr>

                        <th>#</th>

                        <th>Invoice No</th>

                        <th>Date</th>

                        <th>Customer</th>

                        <th>Grand Total</th>

                        <th>Paid</th>

                        <th>Due</th>

                        <th>Payment Status</th>

                        <th width="300">
                            Action
                        </th>

                    </tr>

                </thead>

                <tbody>

                    @forelse($invoices as $key => $invoice)

                    <tr>

                        <td>

                            {{ $invoices->firstItem() + $key }}

                        </td>

                        <td>

                            <strong>

                                {{ $invoice->invoice_no }}

                            </strong>

                        </td>

                        <td>

                            {{ date('d M Y', strtotime($invoice->sale_date)) }}

                        </td>

                        <td>

                            {{ $invoice->customer->name ?? '-' }}

                        </td>

                        <td>

                            <strong>

                                {{ number_format($invoice->grand_total, 2) }}

                            </strong>

                        </td>

                        <td>

                            <span class="text-success">

                                {{ number_format($invoice->paid_amount, 2) }}

                            </span>

                        </td>

                        <td>

                            <span class="text-danger">

                                {{ number_format($invoice->due_amount, 2) }}

                            </span>

                        </td>

                        <td>

                            @if($invoice->payment_status == 'paid')

                                <span class="badge bg-success">
                                    Paid
                                </span>

                            @elseif($invoice->payment_status == 'partial')

                                <span class="badge bg-warning text-dark">
                                    Partial
                                </span>

                            @else

                                <span class="badge bg-danger">
                                    Unpaid
                                </span>

                            @endif

                        </td>

                        <td>

                            <div class="d-flex gap-1 flex-wrap">

                                {{-- VIEW --}}
                                <a
                                    href="{{ route('company.sales.show', $invoice->id) }}"
                                    class="btn btn-info btn-sm"
                                >
                                    View
                                </a>

                                {{-- PRINT --}}
                                <a
                                    href="{{ route('company.sales.print', $invoice->id) }}"
                                    class="btn btn-dark btn-sm"
                                >
                                    Print
                                </a>

                                {{-- PAYMENT --}}
                                @if($invoice->due_amount > 0)

                                    <a
                                        href="{{ route('company.sales-payment.create', $invoice->id) }}"
                                        class="btn btn-success btn-sm"
                                    >
                                        Payment
                                    </a>

                                @endif

                                {{-- RETURN --}}
                                <a
                                    href="{{ route('company.sales-return.create', $invoice->id) }}"
                                    class="btn btn-warning btn-sm"
                                >
                                    Return
                                </a>

                            </div>

                        </td>

                    </tr>

                    @empty

                    <tr>

                        <td
                            colspan="9"
                            class="text-center"
                        >

                            No Sales Invoice Found

                        </td>

                    </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

    {{-- PAGINATION --}}
    <div class="mt-3">

        {{ $invoices->links() }}

    </div>

</div>

@endsection