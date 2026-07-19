@extends('company.layout')

@section('title', 'Sales Payment List Print')

@section('content')

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

    $filterInvoiceNo = request('invoice_no') ?: '-';

    $filterAccount = '-';
    if (request('account_id')) {
        $selectedAccount = $accounts->firstWhere('id', (int) request('account_id'));
        $filterAccount = $selectedAccount->account_name ?? '-';
    }

    if (request('status') === '0') {
        $filterStatus = 'Cancelled';
    } elseif (request()->has('status') && request('status') === '') {
        $filterStatus = 'All';
    } elseif (!request()->has('status') || request('status') === '1') {
        $filterStatus = 'Active';
    } else {
        $filterStatus = '-';
    }
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
                            <h1 class="dg-print-list-title">SALES PAYMENT LIST</h1>
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
                                    <span class="dg-print-list-header-filter-label">Invoice No</span>
                                    <span class="dg-print-list-header-filter-sep">:</span>
                                    <span class="dg-print-list-header-filter-value">{{ $filterInvoiceNo }}</span>
                                </div>
                                <div class="dg-print-list-header-filter-row">
                                    <span class="dg-print-list-header-filter-label">Account</span>
                                    <span class="dg-print-list-header-filter-sep">:</span>
                                    <span class="dg-print-list-header-filter-value">{{ $filterAccount }}</span>
                                </div>
                                <div class="dg-print-list-header-filter-row">
                                    <span class="dg-print-list-header-filter-label">Status</span>
                                    <span class="dg-print-list-header-filter-sep">:</span>
                                    <span class="dg-print-list-header-filter-value">{{ $filterStatus }}</span>
                                </div>
                            </div>
                        </section>
                    </header>

                    <div class="dg-summary-bar dg-print-list-summary">
                        <div class="dg-summary-bar-row">
                            <div class="dg-summary-bar-item">
                                <span class="dg-summary-bar-label">Total Collection</span>
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
                                <span class="dg-summary-bar-label">Active</span>
                                <span class="dg-summary-bar-sep">:</span>
                                <span class="dg-summary-bar-value">{{ number_format($activeCount) }}</span>
                            </div>
                            <div class="dg-summary-bar-item">
                                <span class="dg-summary-bar-label">Cancelled</span>
                                <span class="dg-summary-bar-sep">:</span>
                                <span class="dg-summary-bar-value">{{ number_format($cancelledCount) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="dg-print-list-table-wrap">
                        <table class="table dg-print-list-table">
                            <thead class="dg-head">
                                <tr>
                                    <th scope="col" class="dg-col-num">#</th>
                                    <th scope="col">Payment No</th>
                                    <th scope="col" class="dg-col-date">Payment Date</th>
                                    <th scope="col">Invoice No</th>
                                    <th scope="col">Customer</th>
                                    <th scope="col">Account</th>
                                    <th scope="col" class="dg-col-num">Paid Amount</th>
                                    <th scope="col" class="dg-col-status">Status</th>
                                </tr>
                            </thead>
                            <tbody class="dg-body">
                                @forelse ($payments as $payment)
                                    <tr class="dg-row">
                                        <td class="dg-col-num">{{ $loop->iteration }}</td>
                                        <td>{{ $payment->payment_no }}</td>
                                        <td class="dg-col-date">{{ $payment->payment_date?->format('d-m-Y') }}</td>
                                        <td>{{ $payment->salesInvoice->invoice_no ?? '-' }}</td>
                                        <td>{{ $payment->customer->name ?? '-' }}</td>
                                        <td>{{ $payment->account->account_name ?? '-' }}</td>
                                        <td class="dg-col-num">{{ number_format($payment->paid_amount, 2) }}</td>
                                        <td class="dg-col-status">
                                            @if ((int) $payment->status === 1)
                                                <span class="dg-badge dg-badge-status dg-badge-success">Active</span>
                                            @else
                                                <span class="dg-badge dg-badge-status dg-badge-secondary">Cancelled</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="dg-row">
                                        <td colspan="8" class="dg-print-list-empty">No payment records found.</td>
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

@endsection
