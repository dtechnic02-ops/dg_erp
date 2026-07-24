@extends('company.layout')

@section('content')

<div class="dg-page">

    <header class="dg-toolbar">
        <div class="container-fluid">
            <div class="row align-items-center g-2">
                <div class="col">
                    <h1 class="h4 mb-0">Salary Sheet Details</h1>
                    <p class="text-muted small mb-0">{{ $salarySheet->salary_month }} — {{ $salarySheet->employee->full_name ?? $salarySheet->employee->first_name }}</p>
                </div>
                <div class="col-auto">
                    <nav class="btn-group flex-wrap" aria-label="Salary sheet show toolbar">
                        <a href="{{ route('company.salary-sheets.index') }}" class="btn btn-outline-secondary dg-btn">Back</a>

                        @if (userCan('salary.edit') && $salarySheet->isEditable())
                            <a href="{{ route('company.salary-sheets.edit', $salarySheet->id) }}" class="btn btn-outline-success dg-btn">Edit</a>
                        @endif

                        @if (userCan('salary.payment.create') && $salarySheet->canAcceptPayment())
                            <a href="{{ route('company.employee-payment.create', ['salary_sheet_id' => $salarySheet->id]) }}" class="btn btn-success dg-btn">Pay Salary</a>
                        @endif

                        @if (userCan('salary.cancel') && $salarySheet->isCancellable())
                            <a href="#cancel" class="btn btn-outline-danger dg-btn">Cancel</a>
                        @endif
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
                    <header class="card-header dg-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h2 class="h6 mb-0">Salary Information</h2>
                        @include('company.salary-sheets.partials.status-badge', ['salarySheet' => $salarySheet])
                    </header>
                    <div class="card-body dg-card-body">
                        <div class="table-responsive">
                            <table class="table dg-table">
                                <tbody>
                                    <tr><th width="220">Employee</th><td>{{ $salarySheet->employee->full_name ?? $salarySheet->employee->first_name }}</td></tr>
                                    <tr><th>Employee Code</th><td>{{ $salarySheet->employee->employee_code }}</td></tr>
                                    <tr><th>Financial Year</th><td>{{ $salarySheet->financialYear->name ?? $salarySheet->financial_year_id }}</td></tr>
                                    <tr><th>Salary Month</th><td>{{ $salarySheet->salary_month }}</td></tr>
                                    <tr><th>Basic Salary</th><td>{{ number_format($salarySheet->basic_salary, 2) }}</td></tr>
                                    <tr><th>Working Days</th><td>{{ $salarySheet->working_days }}</td></tr>
                                    <tr><th>Present Days</th><td>{{ $salarySheet->present_days }}</td></tr>
                                    <tr><th>Absent Days</th><td>{{ $salarySheet->absent_days }}</td></tr>
                                    <tr><th>Allowance</th><td>{{ number_format($salarySheet->allowance, 2) }}</td></tr>
                                    <tr><th>Bonus</th><td>{{ number_format($salarySheet->bonus, 2) }}</td></tr>
                                    <tr><th>Overtime</th><td>{{ number_format($salarySheet->overtime_amount, 2) }}</td></tr>
                                    <tr><th>Deduction</th><td>{{ number_format($salarySheet->deduction, 2) }}</td></tr>
                                    <tr><th>Net Salary</th><td><strong>{{ number_format($salarySheet->net_salary, 2) }}</strong></td></tr>
                                    <tr><th>Paid Amount</th><td>{{ number_format($salarySheet->paid_amount ?? 0, 2) }}</td></tr>
                                    <tr><th>Due Amount</th><td><strong>{{ number_format($salarySheet->due_amount ?? $salarySheet->net_salary, 2) }}</strong></td></tr>
                                    <tr><th>Outstanding Balance</th><td>{{ number_format($salarySheet->due_amount ?? 0, 2) }}</td></tr>
                                    <tr><th>Note</th><td>{{ $salarySheet->note ?: '-' }}</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </article>
            </section>

            <section class="dg-section">
                <article class="card dg-card">
                    <header class="card-header dg-card-header">
                        <h2 class="h6 mb-0">Payment History</h2>
                    </header>
                    <div class="card-body dg-card-body">
                        <div class="table-responsive">
                            <table class="table dg-table">
                                <thead>
                                    <tr>
                                        <th>Payment Date</th>
                                        <th>Voucher</th>
                                        <th>Account</th>
                                        <th class="text-end">Amount</th>
                                        <th>Status</th>
                                        <th class="text-end">Remaining Balance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $runningDue = (float) $salarySheet->net_salary; @endphp
                                    @forelse ($salarySheet->employeePayments->sortBy('payment_date') as $payment)
                                        @php
                                            if ($payment->isActive()) {
                                                $runningDue = max(0, round($runningDue - (float) $payment->amount, 2));
                                            }
                                        @endphp
                                        <tr>
                                            <td>{{ $payment->payment_date?->format('Y-m-d') }}</td>
                                            <td>
                                                @if (userCan('salary.payment.view'))
                                                    <a href="{{ route('company.employee-payment.show', $payment->id) }}">{{ $payment->voucher_no }}</a>
                                                @else
                                                    {{ $payment->voucher_no }}
                                                @endif
                                            </td>
                                            <td>{{ $payment->account->account_name ?? '-' }}</td>
                                            <td class="text-end">{{ number_format($payment->amount, 2) }}</td>
                                            <td>
                                                @if ($payment->isActive())
                                                    <span class="badge bg-success">Active</span>
                                                @else
                                                    <span class="badge bg-secondary">Cancelled</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                @if ($payment->isActive())
                                                    {{ number_format($runningDue, 2) }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">No salary payments recorded yet.</td>
                                        </tr>
                                    @endforelse
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
                                    <tr><th width="220">Created By</th><td>{{ $salarySheet->creator->name ?? '-' }}</td></tr>
                                    <tr><th>Created At</th><td>{{ $salarySheet->created_at?->format('Y-m-d H:i') ?? '-' }}</td></tr>
                                    <tr><th>Updated By</th><td>{{ $salarySheet->updater->name ?? '-' }}</td></tr>
                                    <tr><th>Updated At</th><td>{{ $salarySheet->updated_at?->format('Y-m-d H:i') ?? '-' }}</td></tr>
                                    @if ($salarySheet->isCancelled())
                                        <tr><th>Cancelled By</th><td>{{ $salarySheet->canceller->name ?? '-' }}</td></tr>
                                        <tr><th>Cancelled At</th><td>{{ $salarySheet->cancelled_at?->format('Y-m-d H:i') ?? '-' }}</td></tr>
                                        <tr><th>Cancel Reason</th><td>{{ $salarySheet->cancel_reason ?: '-' }}</td></tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </article>
            </section>

            @if (userCan('salary.cancel') && $salarySheet->isCancellable())
                <section class="dg-section" id="cancel">
                    <article class="card dg-card border-danger">
                        <header class="card-header dg-card-header">
                            <h2 class="h6 mb-0 text-danger">Cancel Salary Sheet</h2>
                        </header>
                        <div class="card-body dg-card-body">
                            <form method="POST" action="{{ route('company.salary-sheets.cancel', $salarySheet->id) }}" onsubmit="return confirm('Cancel this salary sheet? This action preserves the record as cancelled.')">
                                @csrf
                                <div class="mb-3">
                                    <label for="cancel_reason" class="form-label">Cancel Reason *</label>
                                    <textarea name="cancel_reason" id="cancel_reason" rows="3" class="form-control dg-input" required maxlength="500">{{ old('cancel_reason') }}</textarea>
                                </div>
                                <button type="submit" class="btn btn-danger dg-btn">Cancel Salary Sheet</button>
                            </form>
                        </div>
                    </article>
                </section>
            @endif

        </div>
    </main>

</div>

@endsection
