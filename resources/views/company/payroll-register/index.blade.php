@extends('company.layout')

@section('content')

<div class="dg-page">

    <header class="dg-toolbar">
        <div class="container-fluid">
            <div class="row align-items-center g-2">
                <div class="col">
                    <h1 class="h4 mb-0">Payroll Register</h1>
                    <p class="text-muted small mb-0">Salary summary by employee and month</p>
                </div>
                <div class="col-auto">
                    <nav class="btn-group flex-wrap" aria-label="Payroll register toolbar">
                        <a href="{{ route('company.payroll-register.print', request()->query()) }}" target="_blank" class="btn btn-outline-secondary dg-btn">Print</a>
                    </nav>
                </div>
            </div>
        </div>
    </header>

    <main class="dg-container">
        <div class="container-fluid">

            <section class="dg-section">
                <article class="card dg-card">
                    <header class="card-header dg-card-header">
                        <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
                            <h2 class="h6 mb-0">Register Filters</h2>
                            <form method="GET" action="{{ route('company.payroll-register.index') }}" class="d-flex align-items-center gap-2 flex-wrap mb-0">
                                <input type="text" name="search" value="{{ request('search') }}" placeholder="Employee search" class="form-control form-control-sm dg-input">
                                <input type="month" name="salary_month" value="{{ request('salary_month') }}" class="form-control form-control-sm dg-input">
                                <select name="financial_year_id" class="form-select form-select-sm dg-select">
                                    <option value="">Active FY</option>
                                    @foreach ($financialYears as $fy)
                                        <option value="{{ $fy->id }}" @selected((string) request('financial_year_id') === (string) $fy->id)>{{ $fy->name }}</option>
                                    @endforeach
                                </select>
                                <select name="department" class="form-select form-select-sm dg-select">
                                    <option value="">All Departments</option>
                                    @foreach ($departments as $department)
                                        <option value="{{ $department }}" @selected(request('department') === $department)>{{ $department }}</option>
                                    @endforeach
                                </select>
                                <select name="status" class="form-select form-select-sm dg-select">
                                    <option value="" @selected(!request()->has('status'))>Active Sheets</option>
                                    <option value="all" @selected(request('status') === 'all')>All Status</option>
                                    <option value="unpaid" @selected(request('status') === 'unpaid')>Unpaid</option>
                                    <option value="partial" @selected(request('status') === 'partial')>Partial</option>
                                    <option value="paid" @selected(request('status') === 'paid')>Paid</option>
                                    <option value="cancelled" @selected(request('status') === 'cancelled')>Cancelled</option>
                                </select>
                                <button type="submit" class="btn btn-sm btn-primary dg-btn">Filter</button>
                                <a href="{{ route('company.payroll-register.index') }}" class="btn btn-sm btn-outline-secondary dg-btn">Reset</a>
                            </form>
                        </div>
                    </header>

                    <div class="card-body dg-card-body">
                        <div class="row g-3 mb-3">
                            <div class="col-md-3">
                                <div class="border rounded p-3">
                                    <div class="text-muted small">Total Net Salary</div>
                                    <div class="fw-bold">{{ number_format($totals['net_salary'], 2) }}</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border rounded p-3">
                                    <div class="text-muted small">Total Paid</div>
                                    <div class="fw-bold text-success">{{ number_format($totals['paid'], 2) }}</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border rounded p-3">
                                    <div class="text-muted small">Total Due</div>
                                    <div class="fw-bold text-danger">{{ number_format($totals['due'], 2) }}</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border rounded p-3">
                                    <div class="text-muted small">Active / Cancelled</div>
                                    <div class="fw-bold">{{ number_format($activeCount) }} / {{ number_format($cancelledCount) }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table dg-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Employee</th>
                                        <th>Department</th>
                                        <th>Branch</th>
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
                                            <td>{{ $sheet->id }}</td>
                                            <td>{{ $sheet->employee->full_name ?? $sheet->employee->first_name }}</td>
                                            <td>{{ $sheet->employee->department ?: '-' }}</td>
                                            <td>{{ $company->company_name ?? '-' }}</td>
                                            <td>{{ $sheet->salary_month }}</td>
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
                                        <tr><td colspan="9" class="text-center text-muted">No payroll records found.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        {{ $salarySheets->links() }}
                    </div>
                </article>
            </section>

        </div>
    </main>

</div>

@endsection
