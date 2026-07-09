@extends('company.layout')

@section('content')

<div class="container-fluid">

    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-3">

        <div>

            <h4 class="mb-0">
                Sales Return Refunds
            </h4>

            <small class="text-muted">
                Manage sales return refunds
            </small>

        </div>

    </div>

    {{-- FILTER --}}
  <div class="card border-0 shadow-sm mb-3">

    <div class="card-body">

        <form method="GET">

            <div class="row g-3">

                {{-- Search --}}
                <div class="col-md-2">

                    <label class="form-label">
                        Search
                    </label>

                    <input
                        type="text"
                        name="search"
                        class="form-control"
                        placeholder="Refund no / customer"
                        value="{{ request('search') }}"
                    >

                </div>

                {{-- Customer --}}
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

                {{-- Start Date --}}
                <div class="col-md-2">

                    <label class="form-label">
                        Start Date
                    </label>

                    <input
                        type="date"
                        name="start_date"
                        class="form-control"
                        value="{{ request('start_date', $startDate) }}"
                    >

                </div>

                {{-- End Date --}}
                <div class="col-md-2">

                    <label class="form-label">
                        End Date
                    </label>

                    <input
                        type="date"
                        name="end_date"
                        class="form-control"
                        value="{{ request('end_date', $endDate) }}"
                    >

                </div>

                {{-- Financial Year --}}
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

                {{-- Buttons --}}
                <div class="col-md-2">

                    <label class="form-label d-block">
                        &nbsp;
                    </label>

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Filter
                    </button>

                    <a
                        href="{{ route('company.sales-return-refund.index') }}"
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
    <div class="card border-0 shadow-sm">

        <div class="card-body p-0">

            <div class="table-responsive">

                <table class="table table-hover align-middle mb-0">

                    <thead class="table-light">

                        <tr>

                            <th>#</th>

                            <th>Refund No</th>

                            <th>Date</th>

                            <th>Return No</th>

                            <th>Customer</th>

                            <th>Account</th>

                            <th>Refund Amount</th>

                            <th width="150">
                                Action
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                        @forelse($refunds as $key => $refund)

                            <tr>

                                <td>

                                    {{ $refunds->firstItem() + $key }}

                                </td>

                                <td>

                                    <strong>

                                        {{ $refund->refund_no }}

                                    </strong>

                                </td>

                                <td>

                                    {{ date('d M Y', strtotime($refund->refund_date)) }}

                                </td>

                                <td>

                                    {{ $refund->salesReturn->return_no ?? '-' }}

                                </td>

                                <td>

                                    {{ $refund->customer->name ?? '-' }}

                                </td>

                                <td>

                                    {{ $refund->account->account_name ?? '-' }}

                                </td>

                                <td>

                                    <strong class="text-danger">

                                        {{ number_format($refund->refund_amount, 2) }}

                                    </strong>

                                </td>

                                <td>

                                    <div class="d-flex gap-2">

                                        <a
                                            href="{{ route('company.sales-return-refund.show', $refund->id) }}"
                                            class="btn btn-sm btn-info"
                                        >
                                            View
                                        </a>

                                        <a
                                            href="{{ route('company.sales-return-refund.print', $refund->id) }}"
                                            class="btn btn-sm btn-dark"
                                        >
                                            Print
                                        </a>

                                    </div>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td
                                    colspan="8"
                                    class="text-center py-4"
                                >

                                    No refund records found.

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

        {{ $refunds->links() }}

    </div>

</div>

@endsection