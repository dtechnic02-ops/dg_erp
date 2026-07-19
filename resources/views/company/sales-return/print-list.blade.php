@extends('company.layout')

@section('title', 'Sales Return List Print')

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

    $filterRefundStatus = request('refund_status')
        ? ucfirst(request('refund_status'))
        : 'All';

    if (request('status') === '0') {
        $filterStatus = 'Cancelled';
    } elseif (request('status') === '1') {
        $filterStatus = 'Active';
    } elseif (request()->has('status') && request('status') === '') {
        $filterStatus = 'All';
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
                            <h1 class="dg-print-list-title">SALES RETURN LIST</h1>
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
                                <span class="dg-summary-bar-label">Return Total</span>
                                <span class="dg-summary-bar-sep">:</span>
                                <span class="dg-summary-bar-value">{{ number_format($grandTotal, 2) }}</span>
                            </div>
                            <div class="dg-summary-bar-item">
                                <span class="dg-summary-bar-label">Refund Amount</span>
                                <span class="dg-summary-bar-sep">:</span>
                                <span class="dg-summary-bar-value">{{ number_format($totalRefunded, 2) }}</span>
                            </div>
                            <div class="dg-summary-bar-item">
                                <span class="dg-summary-bar-label">Return Count</span>
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
                                    <th scope="col">Return No</th>
                                    <th scope="col" class="dg-col-date">Return Date</th>
                                    <th scope="col">Original Invoice No</th>
                                    <th scope="col">Customer</th>
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
                                        <td class="dg-col-num">{{ $loop->iteration }}</td>
                                        <td>{{ $return->return_no }}</td>
                                        <td class="dg-col-date">{{ $return->return_date?->format('d-m-Y') }}</td>
                                        <td>{{ $return->invoice->invoice_no ?? '-' }}</td>
                                        <td>{{ $return->customer->name ?? '-' }}</td>
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
                                        <td colspan="12" class="dg-print-list-empty">No sales returns found.</td>
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
