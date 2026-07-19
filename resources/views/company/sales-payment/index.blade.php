@extends('company.layout')

@section('content')

<div class="dg-page">

    <header class="dg-toolbar @if (request('print')) d-print-none @endif">
        <div class="container-fluid">
            <div class="d-flex flex-nowrap align-items-center gap-2">

                <div class="flex-shrink-0">
                    <h1 class="h4 mb-0">Sales Payments</h1>
                </div>

                <div class="flex-fill d-flex justify-content-end align-items-center gap-2 flex-wrap flex-md-nowrap">
                    <nav class="btn-group" aria-label="Sales payment toolbar">
                        <a href="{{ route('company.dashboard') }}" class="btn btn-outline-secondary dg-btn">Dashboard</a>
                        <a href="{{ route('company.sales.index') }}" class="btn btn-primary dg-btn">New Payment</a>
                        <a href="{{ route('company.sales-payment.index') }}" class="btn btn-outline-secondary dg-btn">Refresh</a>
                        <a href="{{ route('company.sales-payment.print-list', request()->query()) }}" target="_blank" class="btn btn-outline-secondary dg-btn">Print List</a>
                        <a href="{{ route('company.sales.index') }}" class="btn btn-outline-secondary dg-btn">Sales</a>
                        <a href="{{ route('company.customers.index') }}" class="btn btn-outline-secondary dg-btn">Customer</a>
                    </nav>
                </div>

            </div>
        </div>
    </header>

    <main class="dg-container">
        <div class="container-fluid">

            @if ($errors->any())
                <div class="alert alert-danger dg-alert d-print-none" role="alert">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('success'))
                <div class="alert alert-success dg-alert d-print-none" role="alert">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger dg-alert d-print-none" role="alert">
                    {{ session('error') }}
                </div>
            @endif

            <section class="dg-section dg-filter @if (request('print')) d-print-none @endif">
                <article class="card dg-card">
                    <header class="card-header dg-card-header">
                        <h2 class="h6 mb-0">Filter</h2>
                    </header>

                    <div class="card-body dg-card-body dg-filter-card-body">
                        <form method="GET" action="{{ route('company.sales-payment.index') }}" class="dg-filter-form">
                            <div class="dg-filter-grid">

                                <div class="dg-filter-field dg-filter-field-fy">
                                    <label for="financial_year_id" class="dg-filter-label">Financial Year</label>
                                    <select name="financial_year_id" id="financial_year_id" class="form-select dg-select dg-filter-control">
                                        <option value="">All Years</option>
                                        @foreach ($financialYears as $financialYear)
                                            <option value="{{ $financialYear->id }}" @selected(
                                                request()->has('financial_year_id')
                                                    ? request('financial_year_id') == $financialYear->id
                                                    : ($activeFy && $activeFy->id == $financialYear->id)
                                            )>{{ $financialYear->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="dg-filter-field dg-filter-field-date">
                                    <label for="start_date" class="dg-filter-label">Date From</label>
                                    <input type="date" name="start_date" id="start_date" class="form-control dg-input dg-filter-control" value="{{ request('start_date', !empty($startDate) ? \Illuminate\Support\Carbon::parse($startDate)->format('Y-m-d') : '') }}">
                                </div>

                                <div class="dg-filter-field dg-filter-field-date">
                                    <label for="end_date" class="dg-filter-label">Date To</label>
                                    <input type="date" name="end_date" id="end_date" class="form-control dg-input dg-filter-control" value="{{ request('end_date', !empty($endDate) ? \Illuminate\Support\Carbon::parse($endDate)->format('Y-m-d') : '') }}">
                                </div>

                                <div class="dg-filter-field dg-filter-field-customer">
                                    <label for="customer_id" class="dg-filter-label">Customer</label>
                                    <select name="customer_id" id="customer_id" class="form-select dg-select dg-filter-control">
                                        <option value="">All Customers</option>
                                        @foreach ($customers as $customer)
                                            <option value="{{ $customer->id }}" @selected(request('customer_id') == $customer->id)>{{ $customer->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="dg-filter-field dg-filter-field-invoice">
                                    <label for="invoice_no" class="dg-filter-label">Invoice No</label>
                                    <input type="text" name="invoice_no" id="invoice_no" class="form-control dg-input dg-filter-control" value="{{ request('invoice_no') }}" placeholder="Invoice No">
                                </div>

                                <div class="dg-filter-field dg-filter-field-account">
                                    <label for="account_id" class="dg-filter-label">Account</label>
                                    <select name="account_id" id="account_id" class="form-select dg-select dg-filter-control">
                                        <option value="">All Accounts</option>
                                        @foreach ($accounts as $account)
                                            <option value="{{ $account->id }}" @selected(request('account_id') == $account->id)>{{ $account->account_name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="dg-filter-field dg-filter-field-status">
                                    <label for="status" class="dg-filter-label">Status</label>
                                    <select name="status" id="status" class="form-select dg-select dg-filter-control">
                                        <option value="" @selected(request()->has('status') && request('status') === '')>All</option>
                                        <option value="1" @selected(!request()->has('status') || request('status') === '1')>Active</option>
                                        <option value="0" @selected(request('status') === '0')>Cancelled</option>
                                    </select>
                                </div>

                                @if (request('per_page'))
                                    <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                                @endif

                                <div class="dg-filter-field dg-filter-field-actions">
                                    <span class="dg-filter-label dg-filter-label-action" aria-hidden="true">&nbsp;</span>
                                    <div class="dg-filter-actions">
                                        <button type="submit" class="btn btn-primary dg-btn dg-filter-btn">Search</button>
                                        <a href="{{ route('company.sales-payment.index') }}" class="btn btn-outline-secondary dg-btn dg-filter-btn">Reset</a>
                                    </div>
                                </div>

                            </div>
                        </form>
                    </div>
                </article>
            </section>

            <section class="dg-section" id="dgSalesPaymentList">
                <article class="card dg-card dg-print">
                    <header class="card-header dg-card-header dg-list-card-header">
                        <h2 class="dg-list-card-title">Sales Payment List</h2>

                        <form method="GET" action="{{ route('company.sales-payment.index') }}" class="dg-list-per-page @if (request('print')) d-print-none @endif">
                            <input type="hidden" name="financial_year_id" value="{{ request('financial_year_id', $activeFy?->id) }}">
                            <input type="hidden" name="start_date" value="{{ request('start_date', !empty($startDate) ? \Illuminate\Support\Carbon::parse($startDate)->format('Y-m-d') : '') }}">
                            <input type="hidden" name="end_date" value="{{ request('end_date', !empty($endDate) ? \Illuminate\Support\Carbon::parse($endDate)->format('Y-m-d') : '') }}">
                            <input type="hidden" name="customer_id" value="{{ request('customer_id') }}">
                            <input type="hidden" name="invoice_no" value="{{ request('invoice_no') }}">
                            <input type="hidden" name="account_id" value="{{ request('account_id') }}">
                            <input type="hidden" name="status" value="{{ request()->has('status') ? request('status') : '1' }}">

                            <label for="per_page" class="dg-list-per-page-label">Show</label>
                            <select name="per_page" id="per_page" class="form-select dg-select dg-list-per-page-select" onchange="this.form.submit()">
                                <option value="10" @selected($perPage == 10)>10</option>
                                <option value="20" @selected($perPage == 20)>20</option>
                                <option value="100" @selected($perPage == 100)>100</option>
                                <option value="200" @selected($perPage == 200)>200</option>
                            </select>
                        </form>
                    </header>

                    <div class="dg-summary-bar">
                        <div class="dg-summary-bar-row">
                            <div class="dg-summary-bar-item">
                                <span class="dg-summary-bar-label">Collection</span>
                                <span class="dg-summary-bar-sep">:</span>
                                <span class="dg-summary-bar-value">{{ number_format($totalPayment, 2) }}</span>
                            </div>
                            <div class="dg-summary-bar-item">
                                <span class="dg-summary-bar-label">Paid Amount</span>
                                <span class="dg-summary-bar-sep">:</span>
                                <span class="dg-summary-bar-value">{{ number_format($totalPayment, 2) }}</span>
                            </div>
                            <div class="dg-summary-bar-item">
                                <span class="dg-summary-bar-label">Payment Count</span>
                                <span class="dg-summary-bar-sep">:</span>
                                <span class="dg-summary-bar-value">{{ number_format($totalCount) }}</span>
                            </div>
                            <div class="dg-summary-bar-item">
                                <span class="dg-summary-bar-label">Status</span>
                                <span class="dg-summary-bar-sep">:</span>
                                <span class="dg-summary-bar-value">
                                    @if (request('status') === '0')
                                        Cancelled
                                    @elseif (request()->has('status') && request('status') === '')
                                        All
                                    @else
                                        Active
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="card-body dg-card-body dg-list-card-body">
                        <div class="dg-table-scroll">
                            <table class="table dg-table dg-table-compact">
                                <thead class="dg-head">
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">Payment No</th>
                                        <th scope="col" class="dg-col-date">Payment Date</th>
                                        <th scope="col">Invoice No</th>
                                        <th scope="col">Customer</th>
                                        <th scope="col">Account</th>
                                        <th scope="col" class="dg-col-num dg-col-paid">Paid Amount</th>
                                        <th scope="col" class="dg-col-status">Status</th>
                                        <th scope="col" class="dg-action-col d-print-none">Action</th>
                                    </tr>
                                </thead>

                                <tbody class="dg-body">
                                    @forelse ($payments as $payment)
                                        <tr class="dg-row">
                                            <td>{{ $payments->firstItem() + $loop->index }}</td>
                                            <td>{{ $payment->payment_no }}</td>
                                            <td class="dg-col-date">{{ $payment->payment_date?->format('d-m-Y') }}</td>
                                            <td>{{ $payment->salesInvoice->invoice_no ?? '-' }}</td>
                                            <td>{{ $payment->customer->name ?? '-' }}</td>
                                            <td>{{ $payment->account->account_name ?? '-' }}</td>
                                            <td class="dg-col-num dg-col-paid">{{ number_format($payment->paid_amount, 2) }}</td>
                                            <td class="dg-col-status">
                                                @if ($payment->status == 1)
                                                    <span class="dg-badge dg-badge-status dg-badge-success">Active</span>
                                                @else
                                                    <span class="dg-badge dg-badge-status dg-badge-secondary">Cancelled</span>
                                                @endif
                                            </td>
                                            <td class="dg-action-col d-print-none">
                                                <div class="dg-action-group" role="group" aria-label="Payment actions for {{ $payment->payment_no }}">
                                                    <a href="{{ route('company.sales-payment.show', $payment->id) }}" class="btn btn-sm btn-outline-primary dg-action-btn">View</a>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr class="dg-row">
                                            <td colspan="9" class="text-center">No payment records found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="dg-list-footer @if (request('print')) d-print-none @endif">
                            <p class="dg-list-meta">
                                Showing {{ $payments->firstItem() ?? 0 }} to {{ $payments->lastItem() ?? 0 }} of {{ $payments->total() }} records
                            </p>

                            <div class="dg-pagination">
                                {{ $payments->withQueryString()->onEachSide(1)->links('pagination::bootstrap-5') }}
                            </div>
                        </div>
                    </div>
                </article>
            </section>

        </div>
    </main>

</div>

@if (request('print'))
    @push('scripts')
        <script>
            window.addEventListener('load', function () {
                window.print();
            });
        </script>
    @endpush
@endif

@endsection
