@extends('company.layout')

@section('content')

<div class="dg-page">

    <header class="dg-toolbar">
        <div class="container-fluid">
            <div class="row align-items-center g-2">
                <div class="col">
                    <h1 class="h4 mb-0">Employee Ledger</h1>
                    <p class="text-muted small mb-0">{{ $employee->employee_code }} — {{ $employee->full_name ?? $employee->first_name }}</p>
                </div>
                <div class="col-auto">
                    <nav class="btn-group flex-wrap" aria-label="Employee ledger toolbar">
                        <a href="{{ route('company.employee-account.show', $employee->id) }}" class="btn btn-outline-secondary dg-btn">Employee Profile</a>
                        <button type="button" onclick="window.print()" class="btn btn-outline-dark dg-btn">Print</button>
                    </nav>
                </div>
            </div>
        </div>
    </header>

    <main class="dg-container">
        <div class="container-fluid" id="printArea">

            <section class="dg-section">
                <article class="card dg-card">
                    <header class="card-header dg-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h2 class="h6 mb-0">Financial Summary</h2>
                        <form method="GET" action="{{ route('company.employee-ledger.show', $employee->id) }}" class="d-flex align-items-center gap-2 mb-0">
                            <select name="financial_year_id" class="form-select form-select-sm dg-select">
                                @foreach ($financialYears as $fy)
                                    <option value="{{ $fy->id }}" @selected($selectedFinancialYear && (int) $selectedFinancialYear->id === (int) $fy->id)>
                                        {{ $fy->name }}{{ !$fy->is_active ? ' (Inactive)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-sm btn-primary dg-btn">Filter</button>
                        </form>
                    </header>
                    <div class="card-body dg-card-body">
                        <div class="row g-3">
                            <div class="col-md-4 col-lg-2">
                                <div class="border rounded p-3 h-100">
                                    <div class="text-muted small">Department</div>
                                    <div class="fw-bold">{{ $employee->department ?: '-' }}</div>
                                </div>
                            </div>
                            <div class="col-md-4 col-lg-2">
                                <div class="border rounded p-3 h-100">
                                    <div class="text-muted small">Designation</div>
                                    <div class="fw-bold">{{ $employee->designation ?: '-' }}</div>
                                </div>
                            </div>
                            <div class="col-md-4 col-lg-2">
                                <div class="border rounded p-3 h-100">
                                    <div class="text-muted small">Opening Due</div>
                                    <div class="fw-bold">{{ number_format($opening_due_salary ?? 0, 2) }}</div>
                                </div>
                            </div>
                            <div class="col-md-4 col-lg-2">
                                <div class="border rounded p-3 h-100">
                                    <div class="text-muted small">Active Sheet Due</div>
                                    <div class="fw-bold">{{ number_format($active_sheet_due ?? 0, 2) }}</div>
                                </div>
                            </div>
                            <div class="col-md-4 col-lg-2">
                                <div class="border rounded p-3 h-100">
                                    <div class="text-muted small">Salary Sheets</div>
                                    <div class="fw-bold">{{ $salarySheets->count() }}</div>
                                </div>
                            </div>
                            <div class="col-md-4 col-lg-2">
                                <div class="border rounded p-3 h-100">
                                    <div class="text-muted small">Outstanding Due</div>
                                    <div class="fw-bold text-danger">{{ number_format($outstanding_due, 2) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
            </section>

            <section class="dg-section">
                <article class="card dg-card">
                    <header class="card-header dg-card-header">
                        <h2 class="h6 mb-0">Salary History</h2>
                    </header>
                    <div class="card-body dg-card-body">
                        <div class="table-responsive">
                            <table class="table dg-table">
                                <thead>
                                    <tr>
                                        <th>Salary Month</th>
                                        <th class="text-end">Net Salary</th>
                                        <th class="text-end">Paid</th>
                                        <th class="text-end">Due</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($salarySheets as $sheet)
                                        <tr>
                                            <td>
                                                <a href="{{ route('company.salary-sheets.show', $sheet->id) }}">{{ $sheet->salary_month }}</a>
                                            </td>
                                            <td class="text-end">{{ number_format($sheet->net_salary, 2) }}</td>
                                            <td class="text-end">{{ number_format($sheet->paid_amount, 2) }}</td>
                                            <td class="text-end">{{ number_format($sheet->due_amount, 2) }}</td>
                                            <td>
                                                @if ($sheet->isCancelled())
                                                    <span class="badge bg-secondary">Cancelled</span>
                                                @elseif ($sheet->isPaid())
                                                    <span class="badge bg-success">Paid</span>
                                                @elseif ($sheet->isPartial())
                                                    <span class="badge bg-warning text-dark">Partial</span>
                                                @else
                                                    <span class="badge bg-danger">Unpaid</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-center text-muted">No salary sheets found.</td></tr>
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
                        <h2 class="h6 mb-0">Payment History</h2>
                    </header>
                    <div class="card-body dg-card-body">
                        <div class="table-responsive">
                            <table class="table dg-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Voucher</th>
                                        <th>Salary Month</th>
                                        <th>Account</th>
                                        <th class="text-end">Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($payments as $payment)
                                        <tr>
                                            <td>{{ $payment->payment_date?->format('Y-m-d') }}</td>
                                            <td>
                                                <a href="{{ route('company.employee-payment.show', $payment->id) }}">{{ $payment->voucher_no }}</a>
                                            </td>
                                            <td>{{ $payment->salarySheet->salary_month ?? ($payment->salary_year . '-' . str_pad((string) $payment->salary_month, 2, '0', STR_PAD_LEFT)) }}</td>
                                            <td>{{ $payment->account->account_name ?? '-' }}</td>
                                            <td class="text-end">{{ number_format($payment->amount, 2) }}</td>
                                            <td>
                                                @if ($payment->isActive())
                                                    <span class="badge bg-success">Active</span>
                                                @else
                                                    <span class="badge bg-secondary">Cancelled</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="text-center text-muted">No payments found.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </article>
            </section>

        </div>
    </main>

</div>

@endsection
