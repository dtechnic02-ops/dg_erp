@extends('company.layout')

@section('title', request('print') ? 'VAT Report Print' : 'VAT Report')

@section('content')

@php
    $company = auth()->user()->company;
    $fromDateValue = !empty($fromDate) ? \Illuminate\Support\Carbon::parse($fromDate)->format('Y-m-d') : '';
    $toDateValue = !empty($toDate) ? \Illuminate\Support\Carbon::parse($toDate)->format('Y-m-d') : '';
    $transactionCount = $transactions->count();
    $printQuery = array_merge(request()->query(), ['print' => 1]);

    if (request('status') === '0') {
        $filterStatus = 'Cancelled';
    } elseif (request()->has('status') && request('status') === '') {
        $filterStatus = 'All';
    } else {
        $filterStatus = 'Active';
    }

    if (request()->has('financial_year_id')) {
        if (request('financial_year_id')) {
            $selectedFy = $financialYears->firstWhere('id', (int) request('financial_year_id'));
            $filterFinancialYear = $selectedFy->name ?? '-';
        } else {
            $filterFinancialYear = 'All Years';
        }
    } elseif ($activeFy) {
        $filterFinancialYear = $activeFy->name;
    } else {
        $filterFinancialYear = '-';
    }

    $filterDateFrom = $fromDateValue
        ? \Illuminate\Support\Carbon::parse($fromDateValue)->format('d-m-Y')
        : '-';
    $filterDateTo = $toDateValue
        ? \Illuminate\Support\Carbon::parse($toDateValue)->format('d-m-Y')
        : '-';
    $filterTransactionType = match ($type) {
        'sale' => 'Sale',
        'sales_return' => 'Sales Return',
        'purchase' => 'Purchase',
        'purchase_return' => 'Purchase Return',
        default => 'All Types',
    };
@endphp

@if (request('print'))

    <div class="dg-page dg-invoice dg-invoice-print dg-vat-report-print">

        <main class="dg-container">
            <div class="container-fluid">

                <div id="printArea">
                    <article class="dg-invoice-sheet">

                        <header class="dg-invoice-print-header">
                            <section class="dg-invoice-print-header-col dg-invoice-print-header-left">
                                <h2 class="dg-invoice-party-title">Company Information</h2>
                                <div class="dg-invoice-field-list">
                                    @if (!empty($company?->company_name))
                                        <div class="dg-invoice-field-row">
                                            <span class="dg-invoice-field-label">Company Name</span>
                                            <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                            <span class="dg-invoice-field-value dg-invoice-company-name">{{ $company->company_name }}</span>
                                        </div>
                                    @endif

                                    @if (!empty($company?->address))
                                        <div class="dg-invoice-field-row">
                                            <span class="dg-invoice-field-label">Address</span>
                                            <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                            <span class="dg-invoice-field-value">{{ $company->address }}</span>
                                        </div>
                                    @endif

                                    @if (!empty($company?->address_line_2))
                                        <div class="dg-invoice-field-row">
                                            <span class="dg-invoice-field-label">Address Line 2</span>
                                            <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                            <span class="dg-invoice-field-value">{{ $company->address_line_2 }}</span>
                                        </div>
                                    @endif

                                    @if (!empty($company?->mobile))
                                        <div class="dg-invoice-field-row">
                                            <span class="dg-invoice-field-label">Phone</span>
                                            <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                            <span class="dg-invoice-field-value">{{ $company->mobile }}</span>
                                        </div>
                                    @elseif (!empty($company?->telephone))
                                        <div class="dg-invoice-field-row">
                                            <span class="dg-invoice-field-label">Phone</span>
                                            <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                            <span class="dg-invoice-field-value">{{ $company->telephone }}</span>
                                        </div>
                                    @endif

                                    @if (!empty($company?->email))
                                        <div class="dg-invoice-field-row">
                                            <span class="dg-invoice-field-label">Email</span>
                                            <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                            <span class="dg-invoice-field-value">{{ $company->email }}</span>
                                        </div>
                                    @endif

                                    @if (!empty($company?->vat_number))
                                        <div class="dg-invoice-field-row">
                                            <span class="dg-invoice-field-label">VAT No</span>
                                            <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                            <span class="dg-invoice-field-value">{{ $company->vat_number }}</span>
                                        </div>
                                    @endif

                                    @if (!empty($company?->pan_number))
                                        <div class="dg-invoice-field-row">
                                            <span class="dg-invoice-field-label">PAN No</span>
                                            <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                            <span class="dg-invoice-field-value">{{ $company->pan_number }}</span>
                                        </div>
                                    @endif
                                </div>
                            </section>

                            <div class="dg-invoice-print-header-col dg-invoice-print-header-center">
                                @if ($company?->logo_path)
                                    <img
                                        src="{{ asset('companies/' . $company->id . '/' . $company->logo_path) }}"
                                        alt="{{ $company->company_name ?? 'Company' }}"
                                        class="dg-invoice-print-logo-center">
                                @endif
                                <h1 class="dg-invoice-print-title">VAT REPORT</h1>
                            </div>

                            <section class="dg-invoice-print-header-col dg-invoice-print-header-right">
                                <h2 class="dg-invoice-party-title">Report Information</h2>
                                <div class="dg-invoice-field-list">
                                    <div class="dg-invoice-field-row">
                                        <span class="dg-invoice-field-label">Financial Year</span>
                                        <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                        <span class="dg-invoice-field-value">{{ $filterFinancialYear }}</span>
                                    </div>
                                    <div class="dg-invoice-field-row">
                                        <span class="dg-invoice-field-label">Status</span>
                                        <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                        <span class="dg-invoice-field-value">{{ $filterStatus }}</span>
                                    </div>
                                    <div class="dg-invoice-field-row">
                                        <span class="dg-invoice-field-label">From Date</span>
                                        <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                        <span class="dg-invoice-field-value">{{ $filterDateFrom }}</span>
                                    </div>
                                    <div class="dg-invoice-field-row">
                                        <span class="dg-invoice-field-label">To Date</span>
                                        <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                        <span class="dg-invoice-field-value">{{ $filterDateTo }}</span>
                                    </div>
                                    <div class="dg-invoice-field-row">
                                        <span class="dg-invoice-field-label">Transaction Type</span>
                                        <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                        <span class="dg-invoice-field-value">{{ $filterTransactionType }}</span>
                                    </div>
                                </div>
                            </section>
                        </header>

                        <div class="dg-invoice-print-header-rule" role="presentation"></div>

                        <section class="dg-invoice-lines dg-vat-report-summary-section">
                            <h2 class="dg-invoice-lines-title">VAT Summary</h2>
                            <div class="dg-table-scroll">
                                <table class="table dg-table dg-invoice-table dg-vat-report-summary-table">
                                    <thead class="dg-head">
                                        <tr>
                                            <th scope="col">Description</th>
                                            <th scope="col" class="dg-col-num">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody class="dg-body">
                                        <tr class="dg-row">
                                            <td>Sales VAT</td>
                                            <td class="dg-col-num">{{ number_format($salesVat, 2) }}</td>
                                        </tr>
                                        <tr class="dg-row">
                                            <td>Sales Return VAT</td>
                                            <td class="dg-col-num">{{ number_format($salesReturnVat, 2) }}</td>
                                        </tr>
                                        <tr class="dg-row">
                                            <td>Purchase VAT</td>
                                            <td class="dg-col-num">{{ number_format($purchaseVat, 2) }}</td>
                                        </tr>
                                        <tr class="dg-row">
                                            <td>Purchase Return VAT</td>
                                            <td class="dg-col-num">{{ number_format($purchaseReturnVat, 2) }}</td>
                                        </tr>
                                        <tr class="dg-row dg-vat-report-summary-highlight">
                                            <td>Net Output VAT</td>
                                            <td class="dg-col-num">{{ number_format($netOutputVat, 2) }}</td>
                                        </tr>
                                        <tr class="dg-row dg-vat-report-summary-highlight">
                                            <td>Net Input VAT</td>
                                            <td class="dg-col-num">{{ number_format($netInputVat, 2) }}</td>
                                        </tr>
                                        <tr class="dg-row dg-vat-report-summary-total">
                                            <td>VAT Payable</td>
                                            <td class="dg-col-num">{{ number_format($vatPayable, 2) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </section>

                        <section class="dg-invoice-lines">
                            <h2 class="dg-invoice-lines-title">VAT Transactions</h2>
                            <div class="dg-table-scroll">
                                <table class="table dg-table dg-invoice-table dg-vat-report-transactions-table">
                                    <thead class="dg-head">
                                        <tr>
                                            <th scope="col" class="dg-col-num">#</th>
                                            <th scope="col" class="dg-col-date">Date</th>
                                            <th scope="col" class="dg-col-voucher">Voucher</th>
                                            <th scope="col" class="dg-col-type">Type</th>
                                            <th scope="col" class="dg-col-party">Party</th>
                                            <th scope="col" class="dg-col-num">VAT Amount</th>
                                            <th scope="col" class="dg-col-status">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="dg-body">
                                        @forelse ($transactions as $key => $row)
                                            <tr class="dg-row">
                                                <td class="dg-col-num">{{ $key + 1 }}</td>
                                                <td class="dg-col-date">
                                                    {{ \Illuminate\Support\Carbon::parse($row['date'])->format('d-m-Y') }}
                                                </td>
                                                <td class="dg-col-voucher">{{ $row['voucher_no'] }}</td>
                                                <td class="dg-col-type">{{ $row['type'] }}</td>
                                                <td class="dg-col-party">{{ $row['party'] }}</td>
                                                <td class="dg-col-num">{{ number_format($row['vat_amount'], 2) }}</td>
                                                <td class="dg-col-status">
                                                    @if (($row['status'] ?? 'Active') === 'Active')
                                                        <span class="dg-badge dg-badge-status dg-badge-success">Active</span>
                                                    @else
                                                        <span class="dg-badge dg-badge-status dg-badge-secondary">Cancelled</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr class="dg-row">
                                                <td colspan="7" class="text-center">No VAT transactions found.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </section>

                        <div class="dg-vat-report-print-footer">
                            @if (!empty($company?->print_note))
                                <div class="dg-print-note">
                                    {!! nl2br(e($company->print_note)) !!}
                                </div>
                            @endif

                            <table class="dg-print-signature-table dg-vat-report-signature-table">
                                <tr>
                                    <td class="text-center">
                                        <div class="dg-signature-line"></div>
                                        <div class="dg-signature-label">Prepared By</div>
                                    </td>
                                    <td class="text-center">
                                        <div class="dg-signature-line"></div>
                                        <div class="dg-signature-label">Checked By</div>
                                    </td>
                                    <td class="text-center">
                                        <div class="dg-signature-line"></div>
                                        <div class="dg-signature-label">Authorized By</div>
                                    </td>
                                </tr>
                            </table>

                            <div class="dg-print-meta-footer dg-vat-report-meta-footer">
                                <span class="fw-semibold">Printed By:</span> {{ auth()->user()->name ?? '' }}
                                <span class="dg-vat-report-meta-sep">|</span>
                                <span class="fw-semibold">Printed:</span> {{ now()->format('d M Y H:i') }}
                                <span class="dg-vat-report-meta-sep">|</span>
                                <span class="fw-semibold">Page:</span>
                                <span class="dg-vat-print-page-number"></span>
                            </div>
                        </div>

                    </article>
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
                    <h1 class="h4 mb-0">VAT Report</h1>
                </div>

                <div class="flex-fill d-flex justify-content-end align-items-center gap-2 flex-wrap flex-md-nowrap">
                    <nav class="btn-group" aria-label="VAT report toolbar">
                        <a href="{{ route('company.dashboard') }}" class="btn btn-outline-secondary dg-btn">Dashboard</a>
                        <a href="{{ route('company.vat-report.index', request()->query()) }}" class="btn btn-outline-secondary dg-btn">Refresh</a>
                        <a
                            href="{{ route('company.vat-report.index', $printQuery) }}"
                            target="_blank"
                            class="btn btn-outline-secondary dg-btn"
                        >Print Report</a>
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
                        <form method="GET" action="{{ route('company.vat-report.index') }}">
                            <div class="row g-2 align-items-end">

                                <div class="col-md-2 col-lg-1">
                                    <label for="financial_year_id" class="form-label">Financial Year</label>
                                    <select name="financial_year_id" id="financial_year_id" class="form-select dg-select">
                                        <option value="">All Years</option>
                                        @foreach ($financialYears as $fyOption)
                                            <option
                                                value="{{ $fyOption->id }}"
                                                @selected(
                                                    request()->has('financial_year_id')
                                                        ? request('financial_year_id') == $fyOption->id
                                                        : ($activeFy && $activeFy->id == $fyOption->id)
                                                )
                                            >{{ $fyOption->name }}</option>
                                        @endforeach
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

                                <div class="col-md-2 col-lg-1">
                                    <label for="from_date" class="form-label">Date From</label>
                                    <input
                                        type="date"
                                        name="from_date"
                                        id="from_date"
                                        class="form-control dg-input"
                                        value="{{ request('from_date', $fromDateValue) }}"
                                    >
                                </div>

                                <div class="col-md-2 col-lg-1">
                                    <label for="to_date" class="form-label">Date To</label>
                                    <input
                                        type="date"
                                        name="to_date"
                                        id="to_date"
                                        class="form-control dg-input"
                                        value="{{ request('to_date', $toDateValue) }}"
                                    >
                                </div>

                                <div class="col-md-3 col-lg-2">
                                    <label for="type" class="form-label">Transaction Type</label>
                                    <select name="type" id="type" class="form-select dg-select">
                                        <option value="" @selected(empty($type))>All Types</option>
                                        <option value="sale" @selected($type === 'sale')>Sale</option>
                                        <option value="sales_return" @selected($type === 'sales_return')>Sales Return</option>
                                        <option value="purchase" @selected($type === 'purchase')>Purchase</option>
                                        <option value="purchase_return" @selected($type === 'purchase_return')>Purchase Return</option>
                                    </select>
                                </div>

                                <div class="col-md-2 col-lg-2 d-flex gap-2">
                                    <button type="submit" class="btn btn-primary dg-btn">Search</button>
                                    <a href="{{ route('company.vat-report.index') }}" class="btn btn-outline-secondary dg-btn">Reset</a>
                                </div>

                            </div>
                        </form>
                    </div>
                </article>
            </section>

            <section class="dg-section dg-summary mb-2">
                <div class="row dg-row g-2">

                    <div class="col-6 col-md-4 col-lg-3">
                        <article class="card dg-card h-100">
                            <header class="card-header dg-card-header py-1 px-3 border-bottom-0">
                                <span class="small mb-0">Sales VAT</span>
                            </header>
                            <div class="card-body dg-card-body py-1 px-3 pt-0 text-end">
                                <span class="fw-bold fs-6">{{ number_format($salesVat, 2) }}</span>
                            </div>
                        </article>
                    </div>

                    <div class="col-6 col-md-4 col-lg-3">
                        <article class="card dg-card h-100">
                            <header class="card-header dg-card-header py-1 px-3 border-bottom-0">
                                <span class="small mb-0">Sales Return VAT</span>
                            </header>
                            <div class="card-body dg-card-body py-1 px-3 pt-0 text-end">
                                <span class="fw-bold fs-6">{{ number_format($salesReturnVat, 2) }}</span>
                            </div>
                        </article>
                    </div>

                    <div class="col-6 col-md-4 col-lg-3">
                        <article class="card dg-card h-100">
                            <header class="card-header dg-card-header py-1 px-3 border-bottom-0">
                                <span class="small mb-0">Purchase VAT</span>
                            </header>
                            <div class="card-body dg-card-body py-1 px-3 pt-0 text-end">
                                <span class="fw-bold fs-6">{{ number_format($purchaseVat, 2) }}</span>
                            </div>
                        </article>
                    </div>

                    <div class="col-6 col-md-4 col-lg-3">
                        <article class="card dg-card h-100">
                            <header class="card-header dg-card-header py-1 px-3 border-bottom-0">
                                <span class="small mb-0">Purchase Return VAT</span>
                            </header>
                            <div class="card-body dg-card-body py-1 px-3 pt-0 text-end">
                                <span class="fw-bold fs-6">{{ number_format($purchaseReturnVat, 2) }}</span>
                            </div>
                        </article>
                    </div>

                    <div class="col-6 col-md-4 col-lg-4">
                        <article class="card dg-card h-100">
                            <header class="card-header dg-card-header py-1 px-3 border-bottom-0">
                                <span class="small mb-0">Net Output VAT</span>
                            </header>
                            <div class="card-body dg-card-body py-1 px-3 pt-0 text-end">
                                <span class="fw-bold fs-6">{{ number_format($netOutputVat, 2) }}</span>
                            </div>
                        </article>
                    </div>

                    <div class="col-6 col-md-4 col-lg-4">
                        <article class="card dg-card h-100">
                            <header class="card-header dg-card-header py-1 px-3 border-bottom-0">
                                <span class="small mb-0">Net Input VAT</span>
                            </header>
                            <div class="card-body dg-card-body py-1 px-3 pt-0 text-end">
                                <span class="fw-bold fs-6">{{ number_format($netInputVat, 2) }}</span>
                            </div>
                        </article>
                    </div>

                    <div class="col-12 col-md-4 col-lg-4">
                        <article class="card dg-card h-100">
                            <header class="card-header dg-card-header py-1 px-3 border-bottom-0">
                                <span class="small mb-0">VAT Payable</span>
                            </header>
                            <div class="card-body dg-card-body py-1 px-3 pt-0 text-end">
                                <span class="fw-bold fs-6">{{ number_format($vatPayable, 2) }}</span>
                            </div>
                        </article>
                    </div>

                </div>
            </section>

            <section class="dg-section" id="dgVatReportList">
                <article class="card dg-card">
                    <header class="card-header dg-card-header dg-list-card-header">
                        <h2 class="dg-list-card-title">VAT Transactions</h2>

                        <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end dg-list-per-page">
                            <label for="vat_table_search" class="dg-list-per-page-label mb-0">Search</label>
                            <input
                                type="text"
                                id="vat_table_search"
                                class="form-control dg-input dg-list-per-page-select"
                                placeholder="Voucher, type, party"
                                autocomplete="off"
                            >

                            <label for="vat_per_page" class="dg-list-per-page-label mb-0">Show</label>
                            <select id="vat_per_page" class="form-select dg-select dg-list-per-page-select">
                                <option value="10">10</option>
                                <option value="20" selected>20</option>
                                <option value="100">100</option>
                                <option value="200">200</option>
                            </select>

                            <a
                                href="{{ route('company.vat-report.index', $printQuery) }}"
                                target="_blank"
                                class="btn btn-outline-secondary dg-btn btn-sm"
                            >Print</a>
                        </div>
                    </header>

                    <div class="card-body dg-card-body dg-list-card-body">
                        <div class="dg-table-scroll">
                            <table class="table dg-table dg-table-compact" id="dgVatTransactionsTable">
                                <thead class="dg-head">
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col" class="dg-col-date">Date</th>
                                        <th scope="col">Voucher</th>
                                        <th scope="col">Type</th>
                                        <th scope="col">Party</th>
                                        <th scope="col" class="dg-col-num">VAT Amount</th>
                                        <th scope="col" class="dg-col-status">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="dg-body">
                                    @forelse ($transactions as $key => $row)
                                        <tr class="dg-row" data-vat-row>
                                            <td>{{ $key + 1 }}</td>
                                            <td class="dg-col-date">
                                                {{ \Illuminate\Support\Carbon::parse($row['date'])->format('d-m-Y') }}
                                            </td>
                                            <td>{{ $row['voucher_no'] }}</td>
                                            <td>{{ $row['type'] }}</td>
                                            <td>{{ $row['party'] }}</td>
                                            <td class="dg-col-num">{{ number_format($row['vat_amount'], 2) }}</td>
                                            <td class="dg-col-status">
                                                @if (($row['status'] ?? 'Active') === 'Active')
                                                    <span class="dg-badge dg-badge-status dg-badge-success">Active</span>
                                                @else
                                                    <span class="dg-badge dg-badge-status dg-badge-secondary">Cancelled</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr class="dg-row" data-vat-empty-row>
                                            <td colspan="7" class="text-center">No VAT transactions found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="dg-list-footer">
                            <p class="dg-list-meta" id="vat_table_meta">
                                Showing {{ $transactionCount > 0 ? 1 : 0 }} to {{ $transactionCount }} of {{ $transactionCount }} records
                            </p>
                        </div>
                    </div>
                </article>
            </section>

        </div>
    </main>

</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var table = document.getElementById('dgVatTransactionsTable');
            var searchInput = document.getElementById('vat_table_search');
            var perPageSelect = document.getElementById('vat_per_page');
            var meta = document.getElementById('vat_table_meta');

            if (!table || !searchInput || !perPageSelect || !meta) {
                return;
            }

            var rows = Array.from(table.querySelectorAll('tbody tr[data-vat-row]'));
            var emptyRow = table.querySelector('tbody tr[data-vat-empty-row]');
            var filteredRows = rows.slice();
            var currentPage = 1;

            function normalize(value) {
                return (value || '').toString().toLowerCase().trim();
            }

            function applyFilters() {
                var term = normalize(searchInput.value);

                filteredRows = rows.filter(function (row) {
                    if (!term) {
                        return true;
                    }

                    var text = normalize(row.textContent);
                    return text.indexOf(term) !== -1;
                });

                currentPage = 1;
                renderRows();
            }

            function renderRows() {
                var perPage = parseInt(perPageSelect.value, 10) || 20;
                var total = filteredRows.length;
                var totalPages = Math.max(1, Math.ceil(total / perPage));

                if (currentPage > totalPages) {
                    currentPage = totalPages;
                }

                var startIndex = (currentPage - 1) * perPage;
                var endIndex = Math.min(startIndex + perPage, total);

                rows.forEach(function (row) {
                    row.classList.add('d-none');
                });

                if (emptyRow) {
                    emptyRow.classList.add('d-none');
                }

                if (total === 0) {
                    if (emptyRow) {
                        emptyRow.classList.remove('d-none');
                    }

                    meta.textContent = 'Showing 0 to 0 of 0 records';
                    return;
                }

                filteredRows.slice(startIndex, endIndex).forEach(function (row, index) {
                    row.classList.remove('d-none');
                    row.children[0].textContent = startIndex + index + 1;
                });

                meta.textContent = 'Showing ' + (startIndex + 1) + ' to ' + endIndex + ' of ' + total + ' records';
            }

            searchInput.addEventListener('input', applyFilters);
            perPageSelect.addEventListener('change', renderRows);

            renderRows();
        });
    </script>
@endpush

@endif

@endsection
