@extends('company.layout')

@section('content')

<div class="dg-page">

    <header class="dg-toolbar">
        <div class="container-fluid">
            <div class="d-flex flex-nowrap align-items-center gap-2">

                <div class="flex-shrink-0">
                    <h1 class="h4 mb-0">Salary Sheets</h1>
                </div>

                <div class="flex-fill d-flex justify-content-end align-items-center gap-2 flex-wrap flex-md-nowrap">
                    <nav class="btn-group" aria-label="Salary sheet toolbar">
                        <a href="{{ route('company.dashboard') }}" class="btn btn-outline-secondary dg-btn">Dashboard</a>
                        @if (userCan('salary.create'))
                            <a href="{{ route('company.salary-sheets.create') }}" class="btn btn-primary dg-btn">New Salary Sheet</a>
                        @endif
                        <a href="{{ route('company.salary-sheets.index') }}" class="btn btn-outline-secondary dg-btn">Refresh</a>
                        <a href="{{ route('company.salary-sheets.print', request()->query()) }}" target="_blank" class="btn btn-outline-secondary dg-btn">Print List</a>
                        @if (userCan('salary.payment.view'))
                            <a href="{{ route('company.employee-payment.index') }}" class="btn btn-outline-secondary dg-btn">Payments</a>
                        @endif
                        @if (userCan('employee.view'))
                            <a href="{{ route('company.employee-account.index') }}" class="btn btn-outline-secondary dg-btn">Employee</a>
                        @endif
                        @if (userCan('salary.view'))
                            <a href="{{ route('company.payroll-register.index') }}" class="btn btn-outline-secondary dg-btn">Payroll Register</a>
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

            <section class="dg-section dg-filter">
                <article class="card dg-card">
                    <header class="card-header dg-card-header">
                        <h2 class="h6 mb-0">Filter</h2>
                    </header>

                    <div class="card-body dg-card-body">
                        <form method="GET" action="{{ route('company.salary-sheets.index') }}">
                            <div class="row g-2 align-items-end">

                                <div class="col-md-2 col-lg-2">
                                    <label for="financial_year_id" class="form-label">Financial Year</label>
                                    <select name="financial_year_id" id="financial_year_id" class="form-select dg-select">
                                        <option value="">All Years</option>
                                        @foreach ($financialYears as $financialYear)
                                            <option value="{{ $financialYear->id }}" @selected(
                                                request()->has('financial_year_id')
                                                    ? request('financial_year_id') == $financialYear->id
                                                    : ($activeFy && $activeFy->id == $financialYear->id)
                                            )>{{ $financialYear->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-2 col-lg-2">
                                    <label for="salary_month" class="form-label">Salary Month</label>
                                    <input type="month" name="salary_month" id="salary_month" class="form-control dg-input" value="{{ request('salary_month') }}">
                                </div>

                                <div class="col-md-3 col-lg-2">
                                    <label for="employee_id" class="form-label">Employee</label>
                                    <select name="employee_id" id="employee_id" class="form-select dg-select">
                                        <option value="">All Employees</option>
                                        @foreach ($employees as $employee)
                                            <option value="{{ $employee->id }}" @selected(request('employee_id') == $employee->id)>
                                                {{ $employee->employee_code }} — {{ $employee->full_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-2 col-lg-2">
                                    <label for="search" class="form-label">Search</label>
                                    <input type="text" name="search" id="search" class="form-control dg-input" value="{{ request('search') }}" placeholder="Employee / Code">
                                </div>

                                <div class="col-md-2 col-lg-2">
                                    <label for="status" class="form-label">Status</label>
                                    <select name="status" id="status" class="form-select dg-select">
                                        <option value="" @selected(!request()->has('status'))>Active</option>
                                        <option value="all" @selected(request('status') === 'all')>All</option>
                                        <option value="unpaid" @selected(request('status') === 'unpaid')>Unpaid</option>
                                        <option value="partial" @selected(request('status') === 'partial')>Partial</option>
                                        <option value="paid" @selected(request('status') === 'paid')>Paid</option>
                                        <option value="cancelled" @selected(request('status') === 'cancelled')>Cancelled</option>
                                    </select>
                                </div>

                                @if (request('per_page'))
                                    <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                                @endif

                                <div class="col-md-2 col-lg-2 d-flex gap-2">
                                    <button type="submit" class="btn btn-primary dg-btn">Search</button>
                                    <a href="{{ route('company.salary-sheets.index') }}" class="btn btn-outline-secondary dg-btn">Reset</a>
                                </div>

                            </div>
                        </form>
                    </div>
                </article>
            </section>

            <section class="dg-section dg-summary mb-2">
                <div class="row dg-row g-2">

                    <div class="col-12 col-md-4">
                        <article class="card dg-card h-100">
                            <header class="card-header dg-card-header py-1 px-3 border-bottom-0">
                                <span class="small mb-0">Total Net Salary</span>
                            </header>
                            <div class="card-body dg-card-body py-1 px-3 pt-0 text-end">
                                <span class="fw-bold fs-6">{{ number_format($totalAmount, 2) }}</span>
                            </div>
                        </article>
                    </div>

                    <div class="col-12 col-md-4">
                        <article class="card dg-card h-100">
                            <header class="card-header dg-card-header py-1 px-3 border-bottom-0">
                                <span class="small mb-0">Paid Amount</span>
                            </header>
                            <div class="card-body dg-card-body py-1 px-3 pt-0 text-end">
                                <span class="fw-bold fs-6">{{ number_format($totalPaid, 2) }}</span>
                            </div>
                        </article>
                    </div>

                    <div class="col-12 col-md-4">
                        <article class="card dg-card h-100">
                            <header class="card-header dg-card-header py-1 px-3 border-bottom-0">
                                <span class="small mb-0">Due Amount</span>
                            </header>
                            <div class="card-body dg-card-body py-1 px-3 pt-0 text-end">
                                <span class="fw-bold fs-6">{{ number_format($totalDue, 2) }}</span>
                            </div>
                        </article>
                    </div>

                </div>
            </section>

            <section class="dg-section" id="dgSalarySheetList">
                <article class="card dg-card dg-print">
                    <header class="card-header dg-card-header dg-list-card-header">
                        <h2 class="dg-list-card-title">Salary Sheet List</h2>

                        <form method="GET" action="{{ route('company.salary-sheets.index') }}" class="dg-list-per-page">
                            <input type="hidden" name="financial_year_id" value="{{ request('financial_year_id', $activeFy?->id) }}">
                            <input type="hidden" name="salary_month" value="{{ request('salary_month') }}">
                            <input type="hidden" name="employee_id" value="{{ request('employee_id') }}">
                            <input type="hidden" name="search" value="{{ request('search') }}">
                            <input type="hidden" name="status" value="{{ request()->has('status') ? request('status') : '' }}">

                            <label for="per_page" class="dg-list-per-page-label">Show</label>
                            <select name="per_page" id="per_page" class="form-select dg-select dg-list-per-page-select" onchange="this.form.submit()">
                                <option value="10" @selected($perPage == 10)>10</option>
                                <option value="20" @selected($perPage == 20)>20</option>
                                <option value="100" @selected($perPage == 100)>100</option>
                                <option value="200" @selected($perPage == 200)>200</option>
                            </select>
                        </form>
                    </header>

                    <div class="card-body dg-card-body dg-list-card-body">
                        <div class="dg-table-scroll">
                            <table class="table dg-table dg-table-compact">
                                <thead class="dg-head">
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">Employee</th>
                                        <th scope="col">Code</th>
                                        <th scope="col">Salary Month</th>
                                        <th scope="col" class="dg-col-num">Net Salary</th>
                                        <th scope="col" class="dg-col-num">Paid</th>
                                        <th scope="col" class="dg-col-num">Due</th>
                                        <th scope="col" class="dg-col-status">Status</th>
                                        <th scope="col" class="dg-action-col">Action</th>
                                    </tr>
                                </thead>

                                <tbody class="dg-body">
                                    @forelse ($salarySheets as $salary)
                                        <tr class="dg-row">
                                            <td>{{ $salarySheets->firstItem() + $loop->index }}</td>
                                            <td>{{ $salary->employee->full_name ?? $salary->employee->first_name }}</td>
                                            <td>{{ $salary->employee->employee_code ?? '-' }}</td>
                                            <td>{{ $salary->salary_month }}</td>
                                            <td class="dg-col-num">{{ number_format($salary->net_salary, 2) }}</td>
                                            <td class="dg-col-num">{{ number_format($salary->paid_amount ?? 0, 2) }}</td>
                                            <td class="dg-col-num">{{ number_format($salary->due_amount ?? 0, 2) }}</td>
                                            <td class="dg-col-status">
                                                @include('company.salary-sheets.partials.status-badge', ['salarySheet' => $salary])
                                            </td>
                                            <td class="dg-action-col">
                                                <div class="dg-action-group" role="group" aria-label="Salary sheet actions">
                                                    @if (userCan('salary.view'))
                                                        <a href="{{ route('company.salary-sheets.show', $salary->id) }}" class="btn btn-sm btn-outline-primary dg-action-btn">View</a>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr class="dg-row">
                                            <td colspan="9" class="text-center">No salary sheets found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="dg-list-footer">
                            <p class="dg-list-meta">
                                Showing {{ $salarySheets->firstItem() ?? 0 }} to {{ $salarySheets->lastItem() ?? 0 }} of {{ $salarySheets->total() }} records
                            </p>

                            <div class="dg-pagination">
                                {{ $salarySheets->withQueryString()->onEachSide(1)->links('pagination::bootstrap-5') }}
                            </div>
                        </div>
                    </div>
                </article>
            </section>

        </div>
    </main>

</div>

@endsection
