@extends('company.layout')

@section('content')

<div class="dg-page">

    <header class="dg-toolbar @if (request('print')) d-print-none @endif">
        <div class="container-fluid">
            <div class="d-flex flex-nowrap align-items-center gap-2">

                <div class="flex-shrink-0">
                    <h1 class="h4 mb-0">Sales Invoices</h1>
                </div>

                <div class="flex-fill d-flex justify-content-end align-items-center gap-2 flex-wrap flex-md-nowrap">
                    <nav class="btn-group" aria-label="Sales list toolbar">
                        <a href="{{ route('company.dashboard') }}" class="btn btn-outline-secondary dg-btn">Dashboard</a>
                        <a href="{{ route('company.sales.create') }}" class="btn btn-primary dg-btn">New Invoice</a>
                        <a href="{{ route('company.sales.index') }}" class="btn btn-outline-secondary dg-btn">Refresh</a>
                        <a href="{{ route('company.sales.print-list', request()->query()) }}" target="_blank" class="btn btn-outline-secondary dg-btn">Print List</a>
                        <a href="{{ route('company.sales-return.index') }}" class="btn btn-outline-secondary dg-btn">
                            <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                            Returns
                        </a>
                        <a href="{{ route('company.customers.index') }}" class="btn btn-outline-secondary dg-btn">Customer</a>
                        <a href="{{ route('company.products.index') }}" class="btn btn-outline-secondary dg-btn">Product</a>
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

                    <div class="card-body dg-card-body">
                        <form method="GET" action="{{ route('company.sales.index') }}">
                            <div class="row g-2 align-items-end">

                                <div class="col-md-2 col-lg-1">
                                    <label for="financial_year_id" class="form-label">Financial Year</label>
                                    <select name="financial_year_id" id="financial_year_id" class="form-select dg-select">
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

                                <div class="col-md-2 col-lg-1">
                                    <label for="start_date" class="form-label">Date From</label>
                                    <input type="date" name="start_date" id="start_date" class="form-control dg-input" value="{{ request('start_date', !empty($startDate) ? \Illuminate\Support\Carbon::parse($startDate)->format('Y-m-d') : '') }}">
                                </div>

                                <div class="col-md-2 col-lg-1">
                                    <label for="end_date" class="form-label">Date To</label>
                                    <input type="date" name="end_date" id="end_date" class="form-control dg-input" value="{{ request('end_date', !empty($endDate) ? \Illuminate\Support\Carbon::parse($endDate)->format('Y-m-d') : '') }}">
                                </div>

                                <div class="col-md-3 col-lg-2">
                                    <label for="customer_id" class="form-label">Customer</label>
                                    <select name="customer_id" id="customer_id" class="form-select dg-select">
                                        <option value="">All Customers</option>
                                        @foreach ($customers as $customer)
                                            <option value="{{ $customer->id }}" @selected(request('customer_id') == $customer->id)>{{ $customer->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-2 col-lg-1">
                                    <label for="search" class="form-label">Invoice No</label>
                                    <input type="text" name="search" id="search" class="form-control dg-input" value="{{ request('search') }}" placeholder="Invoice No">
                                </div>

                                <div class="col-md-2 col-lg-2">
                                    <label for="payment_status" class="form-label">Payment Status</label>
                                    <select name="payment_status" id="payment_status" class="form-select dg-select">
                                        <option value="">All Status</option>
                                        <option value="paid" @selected(request('payment_status') == 'paid')>Paid</option>
                                        <option value="partial" @selected(request('payment_status') == 'partial')>Partial</option>
                                        <option value="unpaid" @selected(request('payment_status') == 'unpaid')>Unpaid</option>
                                        <option value="cancelled" @selected(request('payment_status') == 'cancelled')>Cancelled</option>
                                    </select>
                                </div>

                                <div class="col-md-2 col-lg-1">
                                    <label for="status" class="form-label">Status</label>
                                    <select name="status" id="status" class="form-select dg-select">
                                        <option value="" @selected(request()->has('status') && request('status') === '')>All</option>
                                        <option value="1" @selected(!request()->has('status') || request('status') === '1')>Active</option>
                                        <option value="0" @selected(request('status') === '0')>Cancelled</option>
                                    </select>
                                </div>

                                @if (request('per_page'))
                                    <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                                @endif

                                <div class="col-md-2 col-lg-2 d-flex gap-2">
                                    <button type="submit" class="btn btn-primary dg-btn">Search</button>
                                    <a href="{{ route('company.sales.index') }}" class="btn btn-outline-secondary dg-btn">Reset</a>
                                </div>

                            </div>
                        </form>
                    </div>
                </article>
            </section>

            <section class="dg-section dg-summary mb-2">
                <div class="row dg-row g-2">

                    <div class="col-12 col-md-4">
                        <article class="card dg-card h-100">
                            <header class="card-header dg-card-header py-1 px-3 border-bottom-0">
                                <span class="small mb-0">Total Amount</span>
                            </header>
                            <div class="card-body dg-card-body py-1 px-3 pt-0 text-end">
                                <span class="fw-bold fs-6">{{ number_format($totalAmount, 2) }}</span>
                            </div>
                        </article>
                    </div>

                    <div class="col-12 col-md-4">
                        <article class="card dg-card h-100">
                            <header class="card-header dg-card-header py-1 px-3 border-bottom-0">
                                <span class="small mb-0">Paid Amount</span>
                            </header>
                            <div class="card-body dg-card-body py-1 px-3 pt-0 text-end">
                                <span class="fw-bold fs-6">{{ number_format($totalPaid, 2) }}</span>
                            </div>
                        </article>
                    </div>

                    <div class="col-12 col-md-4">
                        <article class="card dg-card h-100">
                            <header class="card-header dg-card-header py-1 px-3 border-bottom-0">
                                <span class="small mb-0">Due Amount</span>
                            </header>
                            <div class="card-body dg-card-body py-1 px-3 pt-0 text-end">
                                <span class="fw-bold fs-6">{{ number_format($totalDue, 2) }}</span>
                            </div>
                        </article>
                    </div>

                </div>
            </section>

            <section class="dg-section" id="dgSalesList">
                <article class="card dg-card dg-print">
                    <header class="card-header dg-card-header dg-list-card-header">
                        <h2 class="dg-list-card-title">Sales List</h2>

                        <form method="GET" action="{{ route('company.sales.index') }}" class="dg-list-per-page @if (request('print')) d-print-none @endif">
                            <input type="hidden" name="financial_year_id" value="{{ request('financial_year_id', $activeFy?->id) }}">
                            <input type="hidden" name="start_date" value="{{ request('start_date', !empty($startDate) ? \Illuminate\Support\Carbon::parse($startDate)->format('Y-m-d') : '') }}">
                            <input type="hidden" name="end_date" value="{{ request('end_date', !empty($endDate) ? \Illuminate\Support\Carbon::parse($endDate)->format('Y-m-d') : '') }}">
                            <input type="hidden" name="customer_id" value="{{ request('customer_id') }}">
                            <input type="hidden" name="search" value="{{ request('search') }}">
                            <input type="hidden" name="payment_status" value="{{ request('payment_status') }}">
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

                    <div class="card-body dg-card-body dg-list-card-body">
                        <div class="dg-table-scroll">
                            <table class="table dg-table dg-table-compact">
                                <thead class="dg-head">
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">Invoice No</th>
                                        <th scope="col" class="dg-col-date">Date</th>
                                        <th scope="col" class="dg-col-date">Due Date</th>
                                        <th scope="col">Customer</th>
                                        <th scope="col" class="dg-col-num dg-col-total">Total Amount</th>
                                        <th scope="col" class="dg-col-num dg-col-paid">Paid</th>
                                        <th scope="col" class="dg-col-num dg-col-due">Due</th>
                                        <th scope="col" class="dg-col-status">Due Days</th>
                                        <th scope="col" class="dg-col-status">Status</th>
                                        <th scope="col" class="dg-action-col d-print-none">Action</th>
                                    </tr>
                                </thead>

                                <tbody class="dg-body">
                                    @forelse ($invoices as $invoice)
                                        <tr class="dg-row">
                                            <td>{{ $invoices->firstItem() + $loop->index }}</td>
                                            <td>{{ $invoice->invoice_no }}</td>
                                            <td class="dg-col-date">{{ \Illuminate\Support\Carbon::parse($invoice->sale_date)->format('d-m-Y') }}</td>
                                            <td class="dg-col-date">{{ $invoice->due_date ? \Illuminate\Support\Carbon::parse($invoice->due_date)->format('d-m-Y') : '-' }}</td>
                                            <td>{{ $invoice->customer->name ?? '-' }}</td>
                                            <td class="dg-col-num dg-col-total">{{ number_format($invoice->grand_total, 2) }}</td>
                                            <td class="dg-col-num dg-col-paid">{{ number_format($invoice->paid_amount, 2) }}</td>
                                            <td class="dg-col-num dg-col-due">{{ number_format($invoice->due_amount, 2) }}</td>
                                            <td class="dg-col-status">{{ $invoice->dueDaysLabel() }}</td>
                                            <td class="dg-col-status">
                                                <span class="dg-badge dg-badge-status dg-badge-{{ $invoice->payment_status == 'paid' ? 'success' : ($invoice->payment_status == 'partial' ? 'warning' : ($invoice->payment_status == 'cancelled' ? 'secondary' : 'danger')) }}">
                                                    {{ ucfirst($invoice->payment_status) }}
                                                </span>
                                            </td>
                                            <td class="dg-action-col d-print-none">
                                                <div class="dg-action-group" role="group" aria-label="Invoice actions for {{ $invoice->invoice_no }}">
                                                    <a href="{{ route('company.sales.show', $invoice->id) }}" class="btn btn-sm btn-outline-primary dg-action-btn">View</a>
                                                    @if ((int) $invoice->status === 1)
                                                        @php
                                                            $hasActivePayments = (int) ($invoice->active_payments_count ?? 0) > 0;
                                                            $hasActiveReturns = in_array((int) $invoice->id, $activeReturnInvoiceIds ?? [], true);
                                                            $canCancelInvoice = !$hasActivePayments && !$hasActiveReturns;

                                                            if ($hasActivePayments && $hasActiveReturns) {
                                                                $cancelBlockMessage = 'Cannot cancel: active payment(s) and sales return(s) exist.';
                                                            } elseif ($hasActivePayments) {
                                                                $cancelBlockMessage = 'Cannot cancel: one or more active payments exist.';
                                                            } elseif ($hasActiveReturns) {
                                                                $cancelBlockMessage = 'Cannot cancel: one or more active sales returns exist.';
                                                            } else {
                                                                $cancelBlockMessage = '';
                                                            }
                                                        @endphp

                                                        @if ($canCancelInvoice)
                                                            <button type="button" class="btn btn-sm btn-outline-danger dg-action-btn" data-bs-toggle="modal" data-bs-target="#dgSalesInvoiceCancelModal{{ $invoice->id }}">Cancel</button>
                                                        @else
                                                            <span class="d-inline-block" tabindex="0" title="{{ $cancelBlockMessage }}" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="{{ $cancelBlockMessage }}">
                                                                <button type="button" class="btn btn-sm btn-outline-danger dg-action-btn" disabled aria-disabled="true">Cancel</button>
                                                            </span>
                                                        @endif
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr class="dg-row">
                                            <td colspan="11" class="text-center">No sales invoices found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="dg-list-footer @if (request('print')) d-print-none @endif">
                            <p class="dg-list-meta">
                                Showing {{ $invoices->firstItem() ?? 0 }} to {{ $invoices->lastItem() ?? 0 }} of {{ $invoices->total() }} records
                            </p>

                            <div class="dg-pagination">
                                {{ $invoices->withQueryString()->onEachSide(1)->links('pagination::bootstrap-5') }}
                            </div>
                        </div>
                    </div>
                </article>
            </section>

            @foreach ($invoices as $invoice)
                @php
                    $hasActivePayments = (int) ($invoice->active_payments_count ?? 0) > 0;
                    $hasActiveReturns = in_array((int) $invoice->id, $activeReturnInvoiceIds ?? [], true);
                    $canCancelInvoice = !$hasActivePayments && !$hasActiveReturns;
                @endphp
                @if ((int) $invoice->status === 1 && $canCancelInvoice)
                    @include('company.partials.dg-sales-cancel-modal', [
                        'modalId' => 'dgSalesInvoiceCancelModal' . $invoice->id,
                        'modalTitle' => 'Cancel Sales Invoice',
                        'action' => route('company.sales.cancel', $invoice->id),
                        'submitLabel' => 'Cancel Invoice',
                        'entityId' => $invoice->id,
                    ])
                @endif
            @endforeach

        </div>
    </main>

</div>

@if ($errors->has('cancel_date') || $errors->has('cancel_reason'))
    @if (old('cancel_entity_id'))
        @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    var modalEl = document.getElementById('dgSalesInvoiceCancelModal{{ old('cancel_entity_id') }}');

                    if (modalEl) {
                        bootstrap.Modal.getOrCreateInstance(modalEl).show();
                    }
                });
            </script>
        @endpush
    @endif
@endif

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
                bootstrap.Tooltip.getOrCreateInstance(el);
            });
        });
    </script>
@endpush

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
