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



        $filterSupplier = '-';

        if (request('supplier_id')) {

            $selectedSupplier = $suppliers->firstWhere('id', (int) request('supplier_id'));

            $filterSupplier = $selectedSupplier->name ?? '-';

        }



        $filterRefundStatus = request('refund_status') ? ucfirst(request('refund_status')) : 'All';



        if (request('status') === '0') {

            $filterStatus = 'Cancelled';

        } elseif (request('status') === '1') {

            $filterStatus = 'Active';

        } elseif (request()->has('status') && request('status') === '') {

            $filterStatus = 'All';

        } else {

            $filterStatus = 'Active';

        }



        $totalCount = $returns->total();

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

                                <h1 class="dg-print-list-title">PURCHASE RETURN LIST</h1>

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

                                        <span class="dg-print-list-header-filter-label">Supplier</span>

                                        <span class="dg-print-list-header-filter-sep">:</span>

                                        <span class="dg-print-list-header-filter-value">{{ $filterSupplier }}</span>

                                    </div>

                                    <div class="dg-print-list-header-filter-row">

                                        <span class="dg-print-list-header-filter-label">Refund Status</span>

                                        <span class="dg-print-list-header-filter-sep">:</span>

                                        <span class="dg-print-list-header-filter-value">{{ $filterRefundStatus }}</span>

                                    </div>

                                    <div class="dg-print-list-header-filter-row">

                                        <span class="dg-print-list-header-filter-label">Return Status</span>

                                        <span class="dg-print-list-header-filter-sep">:</span>

                                        <span class="dg-print-list-header-filter-value">{{ $filterStatus }}</span>

                                    </div>

                                </div>

                            </section>

                        </header>



                        <div class="dg-summary-bar dg-print-list-summary">

                            <div class="dg-summary-bar-row">

                                <div class="dg-summary-bar-item">

                                    <span class="dg-summary-bar-label">Subtotal</span>

                                    <span class="dg-summary-bar-sep">:</span>

                                    <span class="dg-summary-bar-value">{{ number_format($totalSubtotal, 2) }}</span>

                                </div>

                                <div class="dg-summary-bar-item">

                                    <span class="dg-summary-bar-label">Total VAT</span>

                                    <span class="dg-summary-bar-sep">:</span>

                                    <span class="dg-summary-bar-value">{{ number_format($totalVat, 2) }}</span>

                                </div>

                                <div class="dg-summary-bar-item">

                                    <span class="dg-summary-bar-label">Return Total</span>

                                    <span class="dg-summary-bar-sep">:</span>

                                    <span class="dg-summary-bar-value">{{ number_format($grandTotal, 2) }}</span>

                                </div>

                                <div class="dg-summary-bar-item">

                                    <span class="dg-summary-bar-label">Return Count</span>

                                    <span class="dg-summary-bar-sep">:</span>

                                    <span class="dg-summary-bar-value">{{ number_format($totalCount) }}</span>

                                </div>

                                <div class="dg-summary-bar-item">

                                    <span class="dg-summary-bar-label">Return Status</span>

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

                                        <th scope="col">Return No</th>

                                        <th scope="col" class="dg-col-date">Return Date</th>

                                        <th scope="col">Original Invoice No</th>

                                        <th scope="col">Supplier</th>

                                        <th scope="col" class="dg-col-num">Subtotal</th>

                                        <th scope="col" class="dg-col-num">VAT</th>

                                        <th scope="col" class="dg-col-num">Return Total</th>

                                        <th scope="col" class="dg-col-num">Refunded</th>

                                        <th scope="col" class="dg-col-num">Remaining</th>

                                        <th scope="col" class="dg-col-status">Refund Status</th>

                                        <th scope="col" class="dg-col-status">Return Status</th>

                                    </tr>

                                </thead>

                                <tbody class="dg-body">

                                    @forelse ($returns as $return)

                                        <tr class="dg-row">

                                            <td class="dg-col-num">{{ $returns->firstItem() + $loop->index }}</td>

                                            <td>{{ $return->return_no }}</td>

                                            <td class="dg-col-date">{{ $return->return_date?->format('d-m-Y') }}</td>

                                            <td>{{ $return->invoice->invoice_no ?? '-' }}</td>

                                            <td>{{ $return->supplier->name ?? '-' }}</td>

                                            <td class="dg-col-num">{{ number_format($return->subtotal, 2) }}</td>

                                            <td class="dg-col-num">{{ number_format($return->total_vat, 2) }}</td>

                                            <td class="dg-col-num">{{ number_format($return->grand_total, 2) }}</td>

                                            <td class="dg-col-num">{{ number_format($return->refunded_amount, 2) }}</td>

                                            <td class="dg-col-num">{{ number_format($return->remaining_amount, 2) }}</td>

                                            <td class="dg-col-status">

                                                @if ($return->refund_status === 'Paid')

                                                    <span class="dg-badge dg-badge-status dg-badge-success">Paid</span>

                                                @elseif ($return->refund_status === 'Partial')

                                                    <span class="dg-badge dg-badge-status dg-badge-warning">Partial</span>

                                                @else

                                                    <span class="dg-badge dg-badge-status dg-badge-danger">Unpaid</span>

                                                @endif

                                            </td>

                                            <td class="dg-col-status">

                                                @if ((int) $return->status === 1)

                                                    <span class="dg-badge dg-badge-status dg-badge-success">Active</span>

                                                @else

                                                    <span class="dg-badge dg-badge-status dg-badge-secondary">Cancelled</span>

                                                @endif

                                            </td>

                                        </tr>

                                    @empty

                                        <tr class="dg-row">

                                            <td colspan="12" class="dg-print-list-empty">No purchase returns found.</td>

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

                    <h1 class="h4 mb-0">Purchase Returns</h1>

                </div>



                <div class="flex-fill d-flex justify-content-end align-items-center gap-2 flex-wrap flex-md-nowrap">

                    <nav class="btn-group" aria-label="Purchase return toolbar">

                        <a href="{{ route('company.dashboard') }}" class="btn btn-outline-secondary dg-btn">Dashboard</a>

                        <a href="{{ route('company.purchases.index') }}" class="btn btn-outline-secondary dg-btn">Purchases</a>

                        <a href="{{ route('company.purchase-return.index') }}" class="btn btn-outline-secondary dg-btn">Refresh</a>

                        <a href="{{ route('company.purchase-return.index', array_merge(request()->query(), ['print' => 1])) }}" target="_blank" class="btn btn-outline-secondary dg-btn">Print List</a>

                        <a href="{{ route('company.suppliers.index') }}" class="btn btn-outline-secondary dg-btn">Supplier</a>

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



                    <div class="card-body dg-card-body dg-filter-card-body">

                        <form method="GET" action="{{ route('company.purchase-return.index') }}" class="dg-filter-form">

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



                                <div class="dg-filter-field dg-filter-field-supplier">

                                    <label for="supplier_id" class="dg-filter-label">Supplier</label>

                                    <select name="supplier_id" id="supplier_id" class="form-select dg-select dg-filter-control">

                                        <option value="">All Suppliers</option>

                                        @foreach ($suppliers as $supplier)

                                            <option value="{{ $supplier->id }}" @selected(request('supplier_id') == $supplier->id)>{{ $supplier->name }}</option>

                                        @endforeach

                                    </select>

                                </div>



                                <div class="dg-filter-field dg-filter-field-status">

                                    <label for="refund_status" class="dg-filter-label">Refund Status</label>

                                    <select name="refund_status" id="refund_status" class="form-select dg-select dg-filter-control">

                                        <option value="">All</option>

                                        <option value="paid" @selected(request('refund_status') === 'paid')>Paid</option>

                                        <option value="partial" @selected(request('refund_status') === 'partial')>Partial</option>

                                        <option value="unpaid" @selected(request('refund_status') === 'unpaid')>Unpaid</option>

                                    </select>

                                </div>



                                <div class="dg-filter-field dg-filter-field-status">

                                    <label for="status" class="dg-filter-label">Return Status</label>

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

                                        <a href="{{ route('company.purchase-return.index') }}" class="btn btn-outline-secondary dg-btn dg-filter-btn">Reset</a>

                                    </div>

                                </div>



                            </div>

                        </form>

                    </div>

                </article>

            </section>



            <section class="dg-section" id="dgPurchaseReturnList">

                <article class="card dg-card dg-print">

                    <header class="card-header dg-card-header dg-list-card-header">

                        <h2 class="dg-list-card-title">Purchase Return List</h2>



                        <form method="GET" action="{{ route('company.purchase-return.index') }}" class="dg-list-per-page">

                            <input type="hidden" name="financial_year_id" value="{{ request('financial_year_id', $activeFy?->id) }}">

                            <input type="hidden" name="start_date" value="{{ request('start_date', !empty($startDate) ? \Illuminate\Support\Carbon::parse($startDate)->format('Y-m-d') : '') }}">

                            <input type="hidden" name="end_date" value="{{ request('end_date', !empty($endDate) ? \Illuminate\Support\Carbon::parse($endDate)->format('Y-m-d') : '') }}">

                            <input type="hidden" name="supplier_id" value="{{ request('supplier_id') }}">

                            <input type="hidden" name="refund_status" value="{{ request('refund_status') }}">

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

                                <span class="dg-summary-bar-label">Subtotal</span>

                                <span class="dg-summary-bar-sep">:</span>

                                <span class="dg-summary-bar-value">{{ number_format($totalSubtotal, 2) }}</span>

                            </div>

                            <div class="dg-summary-bar-item">

                                <span class="dg-summary-bar-label">Total VAT</span>

                                <span class="dg-summary-bar-sep">:</span>

                                <span class="dg-summary-bar-value">{{ number_format($totalVat, 2) }}</span>

                            </div>

                            <div class="dg-summary-bar-item">

                                <span class="dg-summary-bar-label">Return Total</span>

                                <span class="dg-summary-bar-sep">:</span>

                                <span class="dg-summary-bar-value">{{ number_format($grandTotal, 2) }}</span>

                            </div>

                            <div class="dg-summary-bar-item">

                                <span class="dg-summary-bar-label">Return Count</span>

                                <span class="dg-summary-bar-sep">:</span>

                                <span class="dg-summary-bar-value">{{ number_format($returns->total()) }}</span>

                            </div>

                            <div class="dg-summary-bar-item">

                                <span class="dg-summary-bar-label">Return Status</span>

                                <span class="dg-summary-bar-sep">:</span>

                                <span class="dg-summary-bar-value">

                                    @if (request('status') === '0')

                                        Cancelled

                                    @elseif (request()->has('status') && request('status') === '')

                                        All

                                    @elseif (request('status') === '1')

                                        Active

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

                                        <th scope="col">Return No</th>

                                        <th scope="col" class="dg-col-date">Return Date</th>

                                        <th scope="col">Original Invoice No</th>

                                        <th scope="col">Supplier</th>

                                        <th scope="col" class="dg-col-num">Subtotal</th>

                                        <th scope="col" class="dg-col-num">VAT</th>

                                        <th scope="col" class="dg-col-num">Return Total</th>

                                        <th scope="col" class="dg-col-num">Refunded</th>

                                        <th scope="col" class="dg-col-num">Remaining</th>

                                        <th scope="col" class="dg-col-status">Refund Status</th>

                                        <th scope="col" class="dg-col-status">Return Status</th>

                                        <th scope="col" class="dg-action-col">Action</th>

                                    </tr>

                                </thead>



                                <tbody class="dg-body">

                                    @forelse ($returns as $return)

                                        <tr class="dg-row">

                                            <td>{{ $returns->firstItem() + $loop->index }}</td>

                                            <td>{{ $return->return_no }}</td>

                                            <td class="dg-col-date">{{ $return->return_date?->format('d-m-Y') }}</td>

                                            <td>{{ $return->invoice->invoice_no ?? '-' }}</td>

                                            <td>{{ $return->supplier->name ?? '-' }}</td>

                                            <td class="dg-col-num">{{ number_format($return->subtotal, 2) }}</td>

                                            <td class="dg-col-num">{{ number_format($return->total_vat, 2) }}</td>

                                            <td class="dg-col-num">{{ number_format($return->grand_total, 2) }}</td>

                                            <td class="dg-col-num">{{ number_format($return->refunded_amount, 2) }}</td>

                                            <td class="dg-col-num">{{ number_format($return->remaining_amount, 2) }}</td>

                                            <td class="dg-col-status">

                                                @if ($return->refund_status === 'Paid')

                                                    <span class="dg-badge dg-badge-status dg-badge-success">Paid</span>

                                                @elseif ($return->refund_status === 'Partial')

                                                    <span class="dg-badge dg-badge-status dg-badge-warning">Partial</span>

                                                @else

                                                    <span class="dg-badge dg-badge-status dg-badge-danger">Unpaid</span>

                                                @endif

                                            </td>

                                            <td class="dg-col-status">

                                                @if ($return->status == 1)

                                                    <span class="dg-badge dg-badge-status dg-badge-success">Active</span>

                                                @else

                                                    <span class="dg-badge dg-badge-status dg-badge-secondary">Cancelled</span>

                                                @endif

                                            </td>

                                            <td class="dg-action-col">

                                                <div class="dg-action-group" role="group" aria-label="Return actions for {{ $return->return_no }}">

                                                    <a href="{{ route('company.purchase-return.show', $return->id) }}" class="btn btn-sm btn-outline-primary dg-action-btn">View</a>

                                                    @if ((float) $return->remaining_amount > 0)

                                                        <a href="{{ route('company.purchase-return-refunds.create', $return->id) }}" class="btn btn-sm btn-outline-warning dg-action-btn">Refund</a>

                                                    @endif

                                                    @if ((int) $return->status === 1 && $return->refund_status === 'Unpaid')

                                                        <button type="button" class="btn btn-sm btn-outline-danger dg-action-btn" data-bs-toggle="modal" data-bs-target="#dgPurchaseReturnCancelModal{{ $return->id }}">Cancel</button>

                                                    @endif

                                                </div>

                                            </td>

                                        </tr>

                                    @empty

                                        <tr class="dg-row">

                                            <td colspan="13" class="text-center">No purchase returns found.</td>

                                        </tr>

                                    @endforelse

                                </tbody>

                            </table>

                        </div>



                        <div class="dg-list-footer">

                            <p class="dg-list-meta">

                                Showing {{ $returns->firstItem() ?? 0 }} to {{ $returns->lastItem() ?? 0 }} of {{ $returns->total() }} records

                            </p>



                            <div class="dg-pagination">

                                {{ $returns->withQueryString()->onEachSide(1)->links('pagination::bootstrap-5') }}

                            </div>

                        </div>

                    </div>

                </article>

            </section>



            @foreach ($returns as $return)
                @if ((int) $return->status === 1 && $return->refund_status === 'Unpaid')
                    @include('company.partials.dg-sales-cancel-modal', [
                        'modalId' => 'dgPurchaseReturnCancelModal' . $return->id,
                        'modalTitle' => 'Cancel Purchase Return',
                        'action' => route('company.purchase-return.cancel', $return->id),
                        'submitLabel' => 'Cancel Return',
                        'entityId' => $return->id,
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
                    var modalEl = document.getElementById('dgPurchaseReturnCancelModal{{ old('cancel_entity_id') }}');

                    if (modalEl) {
                        bootstrap.Modal.getOrCreateInstance(modalEl).show();
                    }
                });
            </script>
        @endpush
    @endif
@endif



@endsection

