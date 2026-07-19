@extends('company.layout')

@section('content')

@if (request('print'))
    @php
        $company = auth()->user()->company;

        $filterFinancialYear = '-';
        if (request()->has('financial_year_id')) {
            if (request('financial_year_id')) {
                $selectedFy = $financialYears->firstWhere('id', (int) request('financial_year_id'));
                $filterFinancialYear = $selectedFy->name ?? '-';
            } else {
                $filterFinancialYear = 'All Years';
            }
        } elseif ($activeFy) {
            $filterFinancialYear = $activeFy->name;
        }

        $filterDateFrom = !empty($startDate)
            ? \Illuminate\Support\Carbon::parse($startDate)->format('d-m-Y')
            : (request('start_date') ? \Illuminate\Support\Carbon::parse(request('start_date'))->format('d-m-Y') : '-');

        $filterDateTo = !empty($endDate)
            ? \Illuminate\Support\Carbon::parse($endDate)->format('d-m-Y')
            : (request('end_date') ? \Illuminate\Support\Carbon::parse(request('end_date'))->format('d-m-Y') : '-');

        $filterCustomer = '-';
        if (request('customer_id')) {
            $selectedCustomer = $customers->firstWhere('id', (int) request('customer_id'));
            $filterCustomer = $selectedCustomer->name ?? '-';
        }

        $filterRefundNo = request('search') ?: '-';
        $filterSearch = request('search') ?: '-';

        if (request('status') === '0') {
            $filterStatus = 'Cancelled';
        } elseif (request('status') === '1' || request('status') === '3') {
            $filterStatus = 'Active';
        } elseif (request()->has('status') && request('status') === '') {
            $filterStatus = 'All';
        } else {
            $filterStatus = 'Active';
        }

        $totalCount = $refunds->total();
    @endphp

    <div class="dg-page dg-print-list-landscape">

        <main class="dg-container">
            <div class="container-fluid">

                <div id="printArea">
                    <div class="dg-print-list-sheet">

                        <header class="dg-print-list-header dg-print-landscape">
                            <section class="dg-print-list-header-col dg-print-list-header-left">
                                @if ($company?->logo_path)
                                    <img
                                        src="{{ asset('companies/' . $company->id . '/' . $company->logo_path) }}"
                                        alt="{{ $company->company_name ?? 'Company Logo' }}"
                                        class="dg-print-list-logo">
                                @endif
                                <div class="dg-print-list-company">
                                    @if (!empty($company?->company_name))
                                        <div class="dg-print-list-company-name">{{ $company->company_name }}</div>
                                    @endif
                                    <div class="dg-print-list-company-meta">
                                        @if (!empty($company?->address))
                                            <div class="dg-print-list-company-line">{{ $company->address }}</div>
                                        @endif
                                        @if (!empty($company?->telephone) || !empty($company?->mobile))
                                            <div class="dg-print-list-company-line">
                                                <span class="dg-print-list-company-label">Phone</span>
                                                <span class="dg-print-list-company-sep">:</span>
                                                <span class="dg-print-list-company-value">
                                                    {{ $company->telephone ?? $company->mobile }}
                                                    @if (!empty($company->telephone) && !empty($company->mobile) && $company->telephone !== $company->mobile)
                                                        / {{ $company->mobile }}
                                                    @endif
                                                </span>
                                            </div>
                                        @endif
                                        @if (!empty($company?->email))
                                            <div class="dg-print-list-company-line">
                                                <span class="dg-print-list-company-label">Email</span>
                                                <span class="dg-print-list-company-sep">:</span>
                                                <span class="dg-print-list-company-value">{{ $company->email }}</span>
                                            </div>
                                        @endif
                                        @if (!empty($company?->vat_number))
                                            <div class="dg-print-list-company-line">
                                                <span class="dg-print-list-company-label">VAT No</span>
                                                <span class="dg-print-list-company-sep">:</span>
                                                <span class="dg-print-list-company-value">{{ $company->vat_number }}</span>
                                            </div>
                                        @endif
                                        @if (!empty($company?->pan_number))
                                            <div class="dg-print-list-company-line">
                                                <span class="dg-print-list-company-label">PAN No</span>
                                                <span class="dg-print-list-company-sep">:</span>
                                                <span class="dg-print-list-company-value">{{ $company->pan_number }}</span>
                                            </div>
                                        @endif
                                        @if (!empty($company?->website))
                                            <div class="dg-print-list-company-line">
                                                <span class="dg-print-list-company-label">Website</span>
                                                <span class="dg-print-list-company-sep">:</span>
                                                <span class="dg-print-list-company-value">{{ $company->website }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </section>

                            <section class="dg-print-list-header-col dg-print-list-header-center">
                                <h1 class="dg-print-list-title">SALES RETURN REFUND LIST</h1>
                            </section>

                            <section class="dg-print-list-header-col dg-print-list-header-right">
                                <div class="dg-print-list-header-filters">
                                    <div class="dg-print-list-header-filter-row">
                                        <span class="dg-print-list-header-filter-label">Financial Year</span>
                                        <span class="dg-print-list-header-filter-sep">:</span>
                                        <span class="dg-print-list-header-filter-value">{{ $filterFinancialYear }}</span>
                                    </div>
                                    <div class="dg-print-list-header-filter-row">
                                        <span class="dg-print-list-header-filter-label">Date From</span>
                                        <span class="dg-print-list-header-filter-sep">:</span>
                                        <span class="dg-print-list-header-filter-value">{{ $filterDateFrom }}</span>
                                    </div>
                                    <div class="dg-print-list-header-filter-row">
                                        <span class="dg-print-list-header-filter-label">Date To</span>
                                        <span class="dg-print-list-header-filter-sep">:</span>
                                        <span class="dg-print-list-header-filter-value">{{ $filterDateTo }}</span>
                                    </div>
                                    <div class="dg-print-list-header-filter-row">
                                        <span class="dg-print-list-header-filter-label">Customer</span>
                                        <span class="dg-print-list-header-filter-sep">:</span>
                                        <span class="dg-print-list-header-filter-value">{{ $filterCustomer }}</span>
                                    </div>
                                    <div class="dg-print-list-header-filter-row">
                                        <span class="dg-print-list-header-filter-label">Refund No</span>
                                        <span class="dg-print-list-header-filter-sep">:</span>
                                        <span class="dg-print-list-header-filter-value">{{ $filterRefundNo }}</span>
                                    </div>
                                    <div class="dg-print-list-header-filter-row">
                                        <span class="dg-print-list-header-filter-label">Refund Status</span>
                                        <span class="dg-print-list-header-filter-sep">:</span>
                                        <span class="dg-print-list-header-filter-value">{{ $filterStatus }}</span>
                                    </div>
                                    <div class="dg-print-list-header-filter-row">
                                        <span class="dg-print-list-header-filter-label">Search</span>
                                        <span class="dg-print-list-header-filter-sep">:</span>
                                        <span class="dg-print-list-header-filter-value">{{ $filterSearch }}</span>
                                    </div>
                                </div>
                            </section>
                        </header>

                        <div class="dg-summary-bar dg-print-list-summary">
                            <div class="dg-summary-bar-row">
                                <div class="dg-summary-bar-item">
                                    <span class="dg-summary-bar-label">Total Refund</span>
                                    <span class="dg-summary-bar-sep">:</span>
                                    <span class="dg-summary-bar-value">{{ number_format($totalRefund, 2) }}</span>
                                </div>
                                <div class="dg-summary-bar-item">
                                    <span class="dg-summary-bar-label">Adjustment</span>
                                    <span class="dg-summary-bar-sep">:</span>
                                    <span class="dg-summary-bar-value">{{ number_format($totalAdjust, 2) }}</span>
                                </div>
                                <div class="dg-summary-bar-item">
                                    <span class="dg-summary-bar-label">Cash Refund</span>
                                    <span class="dg-summary-bar-sep">:</span>
                                    <span class="dg-summary-bar-value">{{ number_format($totalCash, 2) }}</span>
                                </div>
                                <div class="dg-summary-bar-item">
                                    <span class="dg-summary-bar-label">Refunds</span>
                                    <span class="dg-summary-bar-sep">:</span>
                                    <span class="dg-summary-bar-value">{{ number_format($totalCount) }}</span>
                                </div>
                                <div class="dg-summary-bar-item">
                                    <span class="dg-summary-bar-label">Refund Status</span>
                                    <span class="dg-summary-bar-sep">:</span>
                                    <span class="dg-summary-bar-value">{{ $filterStatus }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="dg-print-list-table-wrap">
                            <table class="table dg-print-list-table">
                                <thead class="dg-head">
                                    <tr>
                                        <th scope="col" class="dg-col-num">#</th>
                                        <th scope="col">Refund No</th>
                                        <th scope="col" class="dg-col-date">Refund Date</th>
                                        <th scope="col">Original Return No</th>
                                        <th scope="col">Customer</th>
                                        <th scope="col">Account</th>
                                        <th scope="col" class="dg-col-num">Refund Total</th>
                                        <th scope="col" class="dg-col-num">Adjustment</th>
                                        <th scope="col" class="dg-col-status">Refund Status</th>
                                    </tr>
                                </thead>
                                <tbody class="dg-body">
                                    @forelse ($refunds as $refund)
                                        <tr class="dg-row">
                                            <td class="dg-col-num">{{ $refunds->firstItem() + $loop->index }}</td>
                                            <td>{{ $refund->refund_no }}</td>
                                            <td class="dg-col-date">{{ $refund->refund_date?->format('d-m-Y') ?? '-' }}</td>
                                            <td>{{ $refund->salesReturn->return_no ?? '-' }}</td>
                                            <td>{{ $refund->customer->name ?? '-' }}</td>
                                            <td>
                                                @if ($refund->account)
                                                    {{ $refund->account->account_name }}
                                                @elseif ((float) $refund->cash_amount <= 0 && (float) $refund->adjust_amount > 0)
                                                    Original Invoice Adjustment
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td class="dg-col-num">{{ number_format($refund->refund_amount, 2) }}</td>
                                            <td class="dg-col-num">{{ number_format($refund->adjust_amount, 2) }}</td>
                                            <td class="dg-col-status">
                                                @if ((int) $refund->status === 0)
                                                    <span class="dg-badge dg-badge-status dg-badge-secondary">Cancelled</span>
                                                @else
                                                    <span class="dg-badge dg-badge-status dg-badge-success">Active</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr class="dg-row">
                                            <td colspan="9" class="dg-print-list-empty">No sales return refunds found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @include('company.partials.print-footer-landscape')

                    </div>
                </div>

            </div>
        </main>

    </div>

    @push('scripts')
        <script>
            window.addEventListener('load', function () {
                window.print();
            });
        </script>
    @endpush

@else

<div class="dg-page">

    <header class="dg-toolbar">
        <div class="container-fluid">
            <div class="d-flex flex-nowrap align-items-center gap-2">

                <div class="flex-shrink-0">
                    <h1 class="h4 mb-0">Sales Return Refunds</h1>
                </div>

                <div class="flex-fill d-flex justify-content-end align-items-center gap-2 flex-wrap flex-md-nowrap">
                    <nav class="btn-group" aria-label="Sales return refund list toolbar">
                        <a href="{{ route('company.dashboard') }}" class="btn btn-outline-secondary dg-btn">Dashboard</a>
                        <a href="{{ route('company.sales-return.index') }}" class="btn btn-outline-secondary dg-btn">Returns</a>
                        <a href="{{ route('company.sales-return-refund.index') }}" class="btn btn-outline-secondary dg-btn">Refresh</a>
                        <a href="{{ route('company.sales-return-refund.index', array_merge(request()->query(), ['print' => 1])) }}" target="_blank" class="btn btn-outline-secondary dg-btn">Print List</a>
                        <a href="{{ route('company.customers.index') }}" class="btn btn-outline-secondary dg-btn">Customer</a>
                    </nav>
                </div>

            </div>
        </div>
    </header>

    <main class="dg-container">
        <div class="container-fluid">

            @if ($errors->any())
                <div class="alert alert-danger dg-alert" role="alert">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('success'))
                <div class="alert alert-success dg-alert" role="alert">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger dg-alert" role="alert">
                    {{ session('error') }}
                </div>
            @endif

            <section class="dg-section dg-filter">
                <article class="card dg-card">
                    <header class="card-header dg-card-header">
                        <h2 class="h6 mb-0">Filter</h2>
                    </header>

                    <div class="card-body dg-card-body">
                        <form method="GET" action="{{ route('company.sales-return-refund.index') }}">
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
                                    <label for="search" class="form-label">Refund No</label>
                                    <input type="text" name="search" id="search" class="form-control dg-input" value="{{ request('search') }}" placeholder="Refund No">
                                </div>

                                <div class="col-md-2 col-lg-1">
                                    <label for="status" class="form-label">Refund Status</label>
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
                                    <a href="{{ route('company.sales-return-refund.index') }}" class="btn btn-outline-secondary dg-btn">Reset</a>
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
                                <span class="small mb-0">Total Refund</span>
                            </header>
                            <div class="card-body dg-card-body py-1 px-3 pt-0 text-end">
                                <span class="fw-bold fs-6">{{ number_format($totalRefund, 2) }}</span>
                            </div>
                        </article>
                    </div>

                    <div class="col-12 col-md-4">
                        <article class="card dg-card h-100">
                            <header class="card-header dg-card-header py-1 px-3 border-bottom-0">
                                <span class="small mb-0">Adjustment</span>
                            </header>
                            <div class="card-body dg-card-body py-1 px-3 pt-0 text-end">
                                <span class="fw-bold fs-6">{{ number_format($totalAdjust, 2) }}</span>
                            </div>
                        </article>
                    </div>

                    <div class="col-12 col-md-4">
                        <article class="card dg-card h-100">
                            <header class="card-header dg-card-header py-1 px-3 border-bottom-0">
                                <span class="small mb-0">Cash Refund</span>
                            </header>
                            <div class="card-body dg-card-body py-1 px-3 pt-0 text-end">
                                <span class="fw-bold fs-6">{{ number_format($totalCash, 2) }}</span>
                            </div>
                        </article>
                    </div>

                </div>
            </section>

            <section class="dg-section" id="dgSalesReturnRefundList">
                <article class="card dg-card dg-print">
                    <header class="card-header dg-card-header dg-list-card-header">
                        <h2 class="dg-list-card-title">Sales Return Refund List</h2>

                        <form method="GET" action="{{ route('company.sales-return-refund.index') }}" class="dg-list-per-page">
                            <input type="hidden" name="financial_year_id" value="{{ request('financial_year_id', $activeFy?->id) }}">
                            <input type="hidden" name="start_date" value="{{ request('start_date', !empty($startDate) ? \Illuminate\Support\Carbon::parse($startDate)->format('Y-m-d') : '') }}">
                            <input type="hidden" name="end_date" value="{{ request('end_date', !empty($endDate) ? \Illuminate\Support\Carbon::parse($endDate)->format('Y-m-d') : '') }}">
                            <input type="hidden" name="customer_id" value="{{ request('customer_id') }}">
                            <input type="hidden" name="search" value="{{ request('search') }}">
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
                                        <th scope="col">Refund No</th>
                                        <th scope="col" class="dg-col-date">Refund Date</th>
                                        <th scope="col">Original Return No</th>
                                        <th scope="col">Customer</th>
                                        <th scope="col">Account</th>
                                        <th scope="col" class="dg-col-num dg-col-total">Refund Total</th>
                                        <th scope="col" class="dg-col-num">Adjustment</th>
                                        <th scope="col" class="dg-col-status">Refund Status</th>
                                        <th scope="col" class="dg-action-col">Action</th>
                                    </tr>
                                </thead>

                                <tbody class="dg-body">
                                    @forelse ($refunds as $refund)
                                        <tr class="dg-row">
                                            <td>{{ $refunds->firstItem() + $loop->index }}</td>
                                            <td>{{ $refund->refund_no }}</td>
                                            <td class="dg-col-date">{{ $refund->refund_date?->format('d-m-Y') ?? '-' }}</td>
                                            <td>{{ $refund->salesReturn->return_no ?? '-' }}</td>
                                            <td>{{ $refund->customer->name ?? '-' }}</td>
                                            <td>
                                                @if ($refund->account)
                                                    {{ $refund->account->account_name }}
                                                @elseif ((float) $refund->cash_amount <= 0 && (float) $refund->adjust_amount > 0)
                                                    Original Invoice Adjustment
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td class="dg-col-num dg-col-total">{{ number_format($refund->refund_amount, 2) }}</td>
                                            <td class="dg-col-num">{{ number_format($refund->adjust_amount, 2) }}</td>
                                            <td class="dg-col-status">
                                                @if ((int) $refund->status === 0)
                                                    <span class="dg-badge dg-badge-status dg-badge-secondary">Cancelled</span>
                                                @else
                                                    <span class="dg-badge dg-badge-status dg-badge-success">Active</span>
                                                @endif
                                            </td>
                                            <td class="dg-action-col">
                                                <div class="dg-action-group" role="group" aria-label="Refund actions for {{ $refund->refund_no }}">
                                                    <a href="{{ route('company.sales-return-refund.show', $refund->id) }}" class="btn btn-sm btn-outline-primary dg-action-btn">View</a>
                                                    @if ((int) $refund->status !== 0)
                                                        <button type="button" class="btn btn-sm btn-outline-danger dg-action-btn" data-bs-toggle="modal" data-bs-target="#dgSalesReturnRefundCancelModal{{ $refund->id }}">Cancel</button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr class="dg-row">
                                            <td colspan="10" class="text-center">No sales return refunds found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="dg-list-footer">
                            <p class="dg-list-meta">
                                Showing {{ $refunds->firstItem() ?? 0 }} to {{ $refunds->lastItem() ?? 0 }} of {{ $refunds->total() }} records
                            </p>

                            <div class="dg-pagination">
                                {{ $refunds->withQueryString()->onEachSide(1)->links('pagination::bootstrap-5') }}
                            </div>
                        </div>
                    </div>
                </article>
            </section>

            @foreach ($refunds as $refund)
                @if ((int) $refund->status !== 0)
                    @include('company.partials.dg-sales-cancel-modal', [
                        'modalId' => 'dgSalesReturnRefundCancelModal' . $refund->id,
                        'modalTitle' => 'Cancel Sales Return Refund',
                        'action' => route('company.sales-return-refund.cancel', $refund->id),
                        'submitLabel' => 'Cancel Refund',
                        'entityId' => $refund->id,
                    ])
                @endif
            @endforeach

        </div>
    </main>

</div>

@endif

@if ($errors->has('cancel_date') || $errors->has('cancel_reason'))
    @if (old('cancel_entity_id'))
        @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    var modalEl = document.getElementById('dgSalesReturnRefundCancelModal{{ old('cancel_entity_id') }}');

                    if (modalEl) {
                        bootstrap.Modal.getOrCreateInstance(modalEl).show();
                    }
                });
            </script>
        @endpush
    @endif
@endif

@endsection
