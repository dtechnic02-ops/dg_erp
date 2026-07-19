@extends('company.layout')

@section('title', 'Sales Return Refund List Print')

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
                                <span class="dg-summary-bar-value">{{ number_format($totalCount ?? $refunds->count()) }}</span>
                            </div>
                            @if (isset($activeCount))
                                <div class="dg-summary-bar-item">
                                    <span class="dg-summary-bar-label">Active</span>
                                    <span class="dg-summary-bar-sep">:</span>
                                    <span class="dg-summary-bar-value">{{ number_format($activeCount) }}</span>
                                </div>
                            @endif
                            @if (isset($cancelledCount))
                                <div class="dg-summary-bar-item">
                                    <span class="dg-summary-bar-label">Cancelled</span>
                                    <span class="dg-summary-bar-sep">:</span>
                                    <span class="dg-summary-bar-value">{{ number_format($cancelledCount) }}</span>
                                </div>
                            @endif
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
                                        <td class="dg-col-num">{{ $loop->iteration }}</td>
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

@endsection
