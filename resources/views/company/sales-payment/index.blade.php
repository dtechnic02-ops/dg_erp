@extends('company.layout')

@section('content')

<div class="container-fluid">

    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-3">

        <div>

            <h4 class="mb-0">
                Sales Payments
            </h4>

            <small class="text-muted">
                Manage customer payment collections
            </small>

        </div>

    </div>

    {{-- FILTER --}}
    <div class="card border-0 shadow-sm mb-3">

        <div class="card-body">

            <form method="GET">

                <div class="row g-2">

                    <div class="col-md-2">

                        <label class="form-label">
                            Search
                        </label>

                        <input
                            type="text"
                            name="search"
                            class="form-control"
                            placeholder="Payment no / customer"
                            value="{{ request('search') }}"
                        >

                    </div>

                    <div class="col-md-2">

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

                        <label class="form-label d-block">
                            &nbsp;
                        </label>

                        <button class="btn btn-primary">

                            Filter

                        </button>

                        <a
                            href="{{ route('company.sales-payment.index') }}"
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
                        Total Collection
                    </small>

                    <h5 class="mb-0 mt-2 text-success">

                        {{ number_format($totalPayment, 2) }}

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

                            <th>Payment No</th>

                            <th>Date</th>

                            <th>Invoice No</th>

                            <th>Customer</th>

                            <th>Account</th>

                            <th>Amount</th>

                            <th>Status</th>

                        </tr>

                    </thead>

                    <tbody>

                        @forelse($payments as $key => $payment)

                            <tr>

                                <td>

                                    {{ $payments->firstItem() + $key }}

                                </td>

                                <td>

                                    <strong>

                                        {{ $payment->payment_no }}

                                    </strong>

                                </td>

                                <td>

                                    {{ date('d M Y', strtotime($payment->payment_date)) }}

                                </td>

                                <td>

                                    {{ $payment->salesInvoice->invoice_no ?? '-' }}

                                </td>

                                <td>

                                    {{ $payment->customer->name ?? '-' }}

                                </td>

                                <td>

                                    {{ $payment->account->account_name ?? '-' }}

                                </td>

                                <td>

                                    <strong class="text-success">

                                        {{ number_format($payment->paid_amount, 2) }}

                                    </strong>

                                </td>

                                <td>

                                    <span class="badge bg-success">
                                        Received
                                    </span>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td
                                    colspan="8"
                                    class="text-center py-4"
                                >

                                    No payment records found.

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

    {{ $payments->links() }}

</div>
       

    </div>

</div>

@endsection