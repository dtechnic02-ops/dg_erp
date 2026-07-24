@extends('company.layout')

@section('content')

@php
    $salarySheet = $employeePayment->salarySheet;
    $remainingDue = $salarySheet
        ? max(0, round((float) $salarySheet->net_salary - ((float) $salarySheet->paid_amount - ((int) $employeePayment->status === 1 ? (float) $employeePayment->amount : 0)), 2))
        : 0;
@endphp

<div class="dg-page">

    <header class="dg-toolbar">
        <div class="container-fluid">
            <div class="row align-items-center g-2">
                <div class="col">
                    <h1 class="h4 mb-0">Salary Payment Details</h1>
                    <p class="text-muted small mb-0">{{ $employeePayment->voucher_no }}</p>
                </div>
                <div class="col-auto">
                    <nav class="btn-group flex-wrap" aria-label="Employee payment toolbar">
                        <a href="{{ route('company.employee-payment.index') }}" class="btn btn-outline-secondary dg-btn">Back</a>
                        @if ($salarySheet)
                            <a href="{{ route('company.salary-sheets.show', $salarySheet->id) }}" class="btn btn-outline-secondary dg-btn">Salary Sheet</a>
                        @endif
                        @if (userCan('salary.payment.edit') && $employeePayment->isActive())
                            <a href="{{ route('company.employee-payment.edit', $employeePayment->id) }}" class="btn btn-outline-success dg-btn">Edit</a>
                        @endif
                        <a href="{{ route('company.employee-payment.print', $employeePayment->id) }}" target="_blank" class="btn btn-outline-dark dg-btn">Print Receipt</a>
                    </nav>
                </div>
            </div>
        </div>
    </header>

    <main class="dg-container">
        <div class="container-fluid">

            @if (session('success'))
                <div class="alert alert-success dg-alert" role="alert">{{ session('success') }}</div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger dg-alert" role="alert">{{ session('error') }}</div>
            @endif

            <section class="dg-section">
                <article class="card dg-card">
                    <header class="card-header dg-card-header d-flex justify-content-between align-items-center">
                        <h2 class="h6 mb-0">Payment Information</h2>
                        @if ($employeePayment->isActive())
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-secondary">Cancelled</span>
                        @endif
                    </header>
                    <div class="card-body dg-card-body">
                        <div class="table-responsive">
                            <table class="table dg-table">
                                <tbody>
                                    <tr><th width="220">Voucher No</th><td>{{ $employeePayment->voucher_no }}</td></tr>
                                    <tr><th>Payment Date</th><td>{{ $employeePayment->payment_date?->format('Y-m-d') }}</td></tr>
                                    <tr><th>Employee</th><td>{{ $employeePayment->employee->full_name ?? $employeePayment->employee->first_name }}</td></tr>
                                    <tr><th>Salary Sheet</th><td>{{ $salarySheet->salary_month ?? '-' }}</td></tr>
                                    <tr><th>Account</th><td>{{ $employeePayment->account->account_name ?? '-' }}</td></tr>
                                    <tr><th>Amount</th><td><strong>{{ number_format($employeePayment->amount, 2) }}</strong></td></tr>
                                    <tr><th>Remaining Balance</th><td>{{ number_format($remainingDue, 2) }}</td></tr>
                                    <tr><th>Note</th><td>{{ $employeePayment->note ?: '-' }}</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </article>
            </section>

            <section class="dg-section">
                <article class="card dg-card">
                    <header class="card-header dg-card-header">
                        <h2 class="h6 mb-0">Audit Information</h2>
                    </header>
                    <div class="card-body dg-card-body">
                        <div class="table-responsive">
                            <table class="table dg-table">
                                <tbody>
                                    <tr><th width="220">Created By</th><td>{{ $employeePayment->creator->name ?? '-' }}</td></tr>
                                    <tr><th>Created At</th><td>{{ $employeePayment->created_at?->format('Y-m-d H:i') ?? '-' }}</td></tr>
                                    <tr><th>Updated By</th><td>{{ $employeePayment->updater->name ?? '-' }}</td></tr>
                                    <tr><th>Updated At</th><td>{{ $employeePayment->updated_at?->format('Y-m-d H:i') ?? '-' }}</td></tr>
                                    @if ($employeePayment->isCancelled())
                                        <tr><th>Cancelled By</th><td>{{ $employeePayment->canceller->name ?? '-' }}</td></tr>
                                        <tr><th>Cancelled At</th><td>{{ $employeePayment->cancelled_at?->format('Y-m-d H:i') ?? '-' }}</td></tr>
                                        <tr><th>Cancel Reason</th><td>{{ $employeePayment->cancel_reason ?: '-' }}</td></tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </article>
            </section>

            @if (userCan('salary.payment.cancel') && $employeePayment->isActive())
                <section class="dg-section">
                    <article class="card dg-card border-danger">
                        <header class="card-header dg-card-header">
                            <h2 class="h6 mb-0 text-danger">Cancel Payment</h2>
                        </header>
                        <div class="card-body dg-card-body">
                            <form method="POST" action="{{ route('company.employee-payment.cancel', $employeePayment->id) }}" onsubmit="return confirm('Cancel this salary payment?')">
                                @csrf
                                <div class="mb-3">
                                    <label for="cancel_date" class="form-label">Cancel Date *</label>
                                    <input type="date" name="cancel_date" id="cancel_date" class="form-control dg-input" value="{{ old('cancel_date', date('Y-m-d')) }}" required>
                                </div>
                                <div class="mb-3">
                                    <label for="cancel_reason" class="form-label">Cancel Reason *</label>
                                    <textarea name="cancel_reason" id="cancel_reason" rows="3" class="form-control dg-input" required maxlength="500">{{ old('cancel_reason') }}</textarea>
                                </div>
                                <button type="submit" class="btn btn-danger dg-btn">Cancel Payment</button>
                            </form>
                        </div>
                    </article>
                </section>
            @endif

        </div>
    </main>

</div>

@endsection
