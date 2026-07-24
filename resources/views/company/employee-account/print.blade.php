@extends('company.layout')

@section('title', 'Employee List Print')

@section('content')

@php
    $company = auth()->user()->company;

    if (request('status') === 'inactive') {
        $filterStatus = 'Inactive';
    } elseif (request('status') === 'active') {
        $filterStatus = 'Active';
    } elseif (request()->filled('status')) {
        $filterStatus = ucfirst((string) request('status'));
    } else {
        $filterStatus = 'All';
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
                                    <span class="dg-invoice-field-value">Employee List</span>
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

                    <h1 class="dg-invoice-print-title text-center">Employee List</h1>

                    <div class="table-responsive">
                        <table class="table dg-table dg-invoice-table">
                            <thead class="dg-head">
                                <tr>
                                    <th scope="col">SN</th>
                                    <th scope="col">Code</th>
                                    <th scope="col">Name</th>
                                    <th scope="col">Designation</th>
                                    <th scope="col">Phone</th>
                                    <th scope="col">Email</th>
                                    <th scope="col" class="text-end">Opening Due</th>
                                    <th scope="col">Status</th>
                                </tr>
                            </thead>
                            <tbody class="dg-body">
                                @forelse ($employees as $index => $employee)
                                    <tr class="dg-row">
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $employee->employee_code }}</td>
                                        <td>{{ $employee->full_name }}</td>
                                        <td>{{ $employee->designation ?: '-' }}</td>
                                        <td>{{ $employee->phone ?: '-' }}</td>
                                        <td>{{ $employee->email ?: '-' }}</td>
                                        <td class="text-end">{{ number_format((float) $employee->opening_due_salary, 2) }}</td>
                                        <td>{{ $employee->isActive() ? 'Active' : 'Inactive' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">No employees found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if ($employees->isNotEmpty())
                                <tfoot>
                                    <tr>
                                        <th colspan="6" class="text-end">Totals</th>
                                        <th class="text-end">{{ number_format($totalOpeningDueSalary, 2) }}</th>
                                        <th>{{ $totalEmployees }} emp.</th>
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
