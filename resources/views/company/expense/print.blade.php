@extends('company.layout')

@section('title', 'Expense Report Print')

@section('content')

@php
    $company = auth()->user()->company;

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

    $filterDateFrom = request('start_date')
        ? \Illuminate\Support\Carbon::parse(request('start_date'))->format('d-m-Y')
        : '-';
    $filterDateTo = request('end_date')
        ? \Illuminate\Support\Carbon::parse(request('end_date'))->format('d-m-Y')
        : '-';

    if (request()->filled('expense_category_id')) {
        $selectedCategory = \App\Models\ExpenseCategory::where('company_id', auth()->user()->company_id)
            ->find((int) request('expense_category_id'));
        $filterCategory = $selectedCategory->name ?? '-';
    } else {
        $filterCategory = 'All Categories';
    }

    $filterSearch = request('search') ?: '-';
@endphp

<div class="dg-page dg-invoice dg-invoice-print">

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
                                @if (!empty($company?->mobile))
                                    <div class="dg-invoice-field-row">
                                        <span class="dg-invoice-field-label">Phone</span>
                                        <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                        <span class="dg-invoice-field-value">{{ $company->mobile }}</span>
                                    </div>
                                @endif
                            </div>
                        </section>

                        <section class="dg-invoice-print-header-col dg-invoice-print-header-right">
                            <h2 class="dg-invoice-party-title">Report Information</h2>
                            <div class="dg-invoice-field-list">
                                <div class="dg-invoice-field-row">
                                    <span class="dg-invoice-field-label">Report</span>
                                    <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                    <span class="dg-invoice-field-value">Expense List</span>
                                </div>
                                <div class="dg-invoice-field-row">
                                    <span class="dg-invoice-field-label">Financial Year</span>
                                    <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                    <span class="dg-invoice-field-value">{{ $filterFinancialYear }}</span>
                                </div>
                                <div class="dg-invoice-field-row">
                                    <span class="dg-invoice-field-label">Date From</span>
                                    <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                    <span class="dg-invoice-field-value">{{ $filterDateFrom }}</span>
                                </div>
                                <div class="dg-invoice-field-row">
                                    <span class="dg-invoice-field-label">Date To</span>
                                    <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                    <span class="dg-invoice-field-value">{{ $filterDateTo }}</span>
                                </div>
                                <div class="dg-invoice-field-row">
                                    <span class="dg-invoice-field-label">Category</span>
                                    <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                    <span class="dg-invoice-field-value">{{ $filterCategory }}</span>
                                </div>
                                <div class="dg-invoice-field-row">
                                    <span class="dg-invoice-field-label">Search</span>
                                    <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                    <span class="dg-invoice-field-value">{{ $filterSearch }}</span>
                                </div>
                                <div class="dg-invoice-field-row">
                                    <span class="dg-invoice-field-label">Status</span>
                                    <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                    <span class="dg-invoice-field-value">{{ $filterStatus }}</span>
                                </div>
                                <div class="dg-invoice-field-row">
                                    <span class="dg-invoice-field-label">Printed Date</span>
                                    <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                    <span class="dg-invoice-field-value">{{ now()->format('d-m-Y H:i') }}</span>
                                </div>
                            </div>
                        </section>
                    </header>

                    <h1 class="dg-invoice-print-title text-center">Expense Report</h1>

                    <div class="table-responsive">
                        <table class="table dg-table dg-invoice-table">
                            <thead class="dg-head">
                                <tr>
                                    <th scope="col">SN</th>
                                    <th scope="col">Expense No</th>
                                    <th scope="col">Reference No</th>
                                    <th scope="col">Category</th>
                                    <th scope="col">Account</th>
                                    <th scope="col">Date</th>
                                    <th scope="col">FY</th>
                                    <th scope="col">Status</th>
                                    <th scope="col" class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="dg-body">
                                @forelse ($expenses as $index => $expense)
                                    <tr class="dg-row">
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $expense->expense_no }}</td>
                                        <td>{{ $expense->reference_no ?? '-' }}</td>
                                        <td>{{ $expense->category->name ?? '-' }}</td>
                                        <td>{{ $expense->account->account_name ?? '-' }}</td>
                                        <td>{{ $expense->expense_date?->format('d-m-Y') ?? '-' }}</td>
                                        <td>{{ $expense->financialYear->name ?? '-' }}</td>
                                        <td>{{ $expense->isActive() ? 'Active' : 'Cancelled' }}</td>
                                        <td class="text-end">{{ number_format($expense->amount, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted">No expense records found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if ($expenses->isNotEmpty())
                                <tfoot>
                                    <tr>
                                        <th colspan="8" class="text-end">Active Total</th>
                                        <th class="text-end">{{ number_format($totalAmount, 2) }}</th>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>

                    <footer class="dg-invoice-print-footer">
                        <div class="row g-2 text-center small">
                            <div class="col-4">
                                <div class="border-top pt-4 mt-4">Prepared By</div>
                                <div class="fw-bold">{{ auth()->user()->name }}</div>
                            </div>
                            <div class="col-4">
                                <div class="border-top pt-4 mt-4">Checked By</div>
                                <div>&nbsp;</div>
                            </div>
                            <div class="col-4">
                                <div class="border-top pt-4 mt-4">Approved By</div>
                                <div>&nbsp;</div>
                            </div>
                        </div>
                    </footer>

                </article>
            </div>

        </div>
    </main>
</div>

<script>
    window.addEventListener('load', function () {
        window.print();
    });
</script>

@endsection
