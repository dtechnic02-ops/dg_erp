@extends('company.layout')

@section('title', 'Salary Sheet Report Print')

@section('content')

@php
    $company = auth()->user()->company;

    if (request('status') === 'cancelled') {
        $filterStatus = 'Cancelled';
    } elseif (request()->has('status') && request('status') === 'all') {
        $filterStatus = 'All';
    } elseif (request()->filled('status')) {
        $filterStatus = ucfirst((string) request('status'));
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

    $filterSalaryMonth = request('salary_month') ?: '-';
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
                                    <span class="dg-invoice-field-value">Salary Sheet List</span>
                                </div>
                                <div class="dg-invoice-field-row">
                                    <span class="dg-invoice-field-label">Financial Year</span>
                                    <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                    <span class="dg-invoice-field-value">{{ $filterFinancialYear }}</span>
                                </div>
                                <div class="dg-invoice-field-row">
                                    <span class="dg-invoice-field-label">Salary Month</span>
                                    <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                    <span class="dg-invoice-field-value">{{ $filterSalaryMonth }}</span>
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

                    <h1 class="dg-invoice-print-title text-center">Salary Sheet Report</h1>

                    <div class="table-responsive">
                        <table class="table dg-table dg-invoice-table">
                            <thead class="dg-head">
                                <tr>
                                    <th scope="col">SN</th>
                                    <th scope="col">Employee Code</th>
                                    <th scope="col">Employee Name</th>
                                    <th scope="col">Month</th>
                                    <th scope="col" class="text-end">Basic</th>
                                    <th scope="col" class="text-center">Present</th>
                                    <th scope="col" class="text-center">Absent</th>
                                    <th scope="col" class="text-end">Net Salary</th>
                                    <th scope="col" class="text-end">Paid</th>
                                    <th scope="col" class="text-end">Due</th>
                                    <th scope="col">Status</th>
                                </tr>
                            </thead>
                            <tbody class="dg-body">
                                @forelse ($salarySheets as $index => $salary)
                                    <tr class="dg-row">
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $salary->employee->employee_code ?? '-' }}</td>
                                        <td>{{ $salary->employee->full_name ?? trim(($salary->employee->first_name ?? '') . ' ' . ($salary->employee->last_name ?? '')) }}</td>
                                        <td>{{ $salary->salary_month }}</td>
                                        <td class="text-end">{{ number_format((float) $salary->basic_salary, 2) }}</td>
                                        <td class="text-center">{{ $salary->present_days }}</td>
                                        <td class="text-center">{{ $salary->absent_days }}</td>
                                        <td class="text-end">{{ number_format((float) $salary->net_salary, 2) }}</td>
                                        <td class="text-end">{{ number_format((float) ($salary->paid_amount ?? 0), 2) }}</td>
                                        <td class="text-end">{{ number_format((float) ($salary->due_amount ?? 0), 2) }}</td>
                                        <td>{{ ucfirst($salary->status) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" class="text-center text-muted">No salary sheet records found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if ($salarySheets->isNotEmpty())
                                <tfoot>
                                    <tr>
                                        <th colspan="7" class="text-end">Active Total</th>
                                        <th class="text-end">{{ number_format($totalAmount, 2) }}</th>
                                        <th class="text-end">{{ number_format($totalPaid, 2) }}</th>
                                        <th class="text-end">{{ number_format($totalDue, 2) }}</th>
                                        <th></th>
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
