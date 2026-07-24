@extends('company.layout')

@section('title', 'Salary Payment Receipt')

@section('content')

@php
    $company = auth()->user()->company;
    $salarySheet = $employeePayment->salarySheet;

    $paidAmount = (float) $employeePayment->amount;
    $amountRupees = (int) floor($paidAmount);
    $amountPaisa = (int) round(($paidAmount - $amountRupees) * 100);
    $rupeeWords = trim(preg_replace('/\s+only$/i', '', preg_replace('/\s+only\s+thousand\s+/i', ' Thousand ', numberToWords($amountRupees))));
    if ($amountPaisa > 0) {
        $paisaWords = trim(preg_replace('/\s+only$/i', '', preg_replace('/\s+only\s+thousand\s+/i', ' Thousand ', numberToWords($amountPaisa))));
        $amountInWords = $rupeeWords . ' Rupees and ' . $paisaWords . ' Paisa Only';
    } else {
        $amountInWords = $rupeeWords . ' Rupees Only';
    }

    $companyPhone = $company?->mobile ?: ($company?->telephone ?? null);
    $sheetDue = $salarySheet
        ? max(0, round((float) $salarySheet->net_salary - ((float) $salarySheet->paid_amount - ($employeePayment->isActive() ? $paidAmount : 0)), 2))
        : 0;
@endphp

<div class="dg-page dg-payment-print">

    <main class="dg-container">
        <div class="container-fluid">

            <div id="printArea">
                <article class="card dg-card dg-payment">

                    <header class="text-center border-bottom pb-2 mb-2">
                        @if ($company?->logo_path)
                            <img
                                src="{{ asset('companies/' . $company->id . '/' . $company->logo_path) }}"
                                alt="{{ $company->company_name ?? 'Company' }}"
                                class="dg-print-logo d-block mx-auto mb-1">
                        @endif
                        <h1 class="dg-print-title mb-0">Salary Payment Receipt</h1>
                    </header>

                    <div class="card-body dg-card-body py-2 px-3">

                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <section class="card dg-card h-100 mb-0">
                                    <header class="card-header dg-card-header py-2">
                                        <h2 class="h6 mb-0">Company Information</h2>
                                    </header>
                                    <div class="card-body dg-card-body py-2 px-3">
                                        <div class="dg-summary-bar-item">
                                            <span class="dg-summary-bar-label text-muted">Company Name</span>
                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>
                                            <span class="dg-summary-bar-value">{{ $company->company_name ?? '-' }}</span>
                                        </div>
                                        <div class="dg-summary-bar-item">
                                            <span class="dg-summary-bar-label text-muted">Phone</span>
                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>
                                            <span class="dg-summary-bar-value">{{ $companyPhone ?: '-' }}</span>
                                        </div>
                                    </div>
                                </section>
                            </div>

                            <div class="col-6">
                                <section class="card dg-card h-100 mb-0">
                                    <header class="card-header dg-card-header py-2">
                                        <h2 class="h6 mb-0">Payment Summary</h2>
                                    </header>
                                    <div class="card-body dg-card-body py-2 px-3">
                                        <div class="dg-summary-bar-item">
                                            <span class="dg-summary-bar-label text-muted">Voucher No</span>
                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>
                                            <span class="dg-summary-bar-value">{{ $employeePayment->voucher_no }}</span>
                                        </div>
                                        <div class="dg-summary-bar-item">
                                            <span class="dg-summary-bar-label text-muted">Payment Date</span>
                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>
                                            <span class="dg-summary-bar-value">{{ $employeePayment->payment_date?->format('d-m-Y') ?? '-' }}</span>
                                        </div>
                                        <div class="dg-summary-bar-item">
                                            <span class="dg-summary-bar-label text-muted">Status</span>
                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>
                                            <span class="dg-summary-bar-value">{{ $employeePayment->isActive() ? 'Active' : 'Cancelled' }}</span>
                                        </div>
                                    </div>
                                </section>
                            </div>
                        </div>

                        <section class="card dg-card mb-2">
                            <header class="card-header dg-card-header py-2">
                                <h2 class="h6 mb-0">Employee & Salary Details</h2>
                            </header>
                            <div class="card-body dg-card-body py-2 px-3">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <div class="dg-summary-bar-item">
                                            <span class="dg-summary-bar-label text-muted">Employee</span>
                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>
                                            <span class="dg-summary-bar-value">{{ $employeePayment->employee->full_name ?? $employeePayment->employee->first_name }}</span>
                                        </div>
                                        <div class="dg-summary-bar-item">
                                            <span class="dg-summary-bar-label text-muted">Employee Code</span>
                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>
                                            <span class="dg-summary-bar-value">{{ $employeePayment->employee->employee_code ?? '-' }}</span>
                                        </div>
                                        <div class="dg-summary-bar-item">
                                            <span class="dg-summary-bar-label text-muted">Salary Month</span>
                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>
                                            <span class="dg-summary-bar-value">{{ $salarySheet->salary_month ?? '-' }}</span>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="dg-summary-bar-item">
                                            <span class="dg-summary-bar-label text-muted">Account</span>
                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>
                                            <span class="dg-summary-bar-value">{{ $employeePayment->account->account_name ?? '-' }}</span>
                                        </div>
                                        <div class="dg-summary-bar-item">
                                            <span class="dg-summary-bar-label text-muted">Paid Amount</span>
                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>
                                            <span class="dg-summary-bar-value fw-bold">{{ number_format($paidAmount, 2) }}</span>
                                        </div>
                                        <div class="dg-summary-bar-item">
                                            <span class="dg-summary-bar-label text-muted">Due Amount</span>
                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>
                                            <span class="dg-summary-bar-value">{{ number_format($sheetDue, 2) }}</span>
                                        </div>
                                        <div class="dg-summary-bar-item">
                                            <span class="dg-summary-bar-label text-muted">Amount in Words</span>
                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>
                                            <span class="dg-summary-bar-value">{{ $amountInWords }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <footer class="border-top pt-2 mt-2">
                            <div class="row g-2 text-center small">
                                <div class="col-4">
                                    <div class="border-top pt-4 mt-4">Prepared By</div>
                                    <div class="fw-bold">{{ $employeePayment->creator->name ?? '-' }}</div>
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
                            <div class="text-end small text-muted mt-2">
                                Printed Date: {{ now()->format('d-m-Y H:i') }}
                            </div>
                        </footer>

                    </div>
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
