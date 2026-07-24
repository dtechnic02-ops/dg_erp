@extends('company.layout')

@section('content')

<div class="dg-page">

    <header class="dg-toolbar">
        <div class="container-fluid">
            <div class="d-flex align-items-center gap-2">

                <div class="flex-fill">
                    <h1 class="h4 mb-0">Employee Management</h1>
                </div>

                <div class="flex-shrink-0">
                    <div class="dg-summary mb-0">
                        <div class="dg-summary-item mb-0">
                            <span>Total Opening Due Salary</span>
                            <span class="fw-bold">{{ number_format($totalOpeningDueSalary, 2) }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex-fill d-flex justify-content-end align-items-center gap-2 flex-wrap flex-md-nowrap">
                    <form method="GET" action="{{ route('company.employee-account.index') }}" class="d-flex gap-2">
                        <label for="search" class="visually-hidden">Search Employee</label>
                        <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Search Employee" class="form-control form-control-sm dg-input">

                        <label for="status" class="visually-hidden">Status</label>
                        <select name="status" id="status" class="form-select form-select-sm dg-select w-auto">
                            <option value="">All Status</option>
                            <option value="active" @selected(request('status') === 'active')>Active</option>
                            <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                        </select>

                        <button type="submit" class="btn btn-sm btn-primary dg-btn">Search</button>
                    </form>

                    <nav class="btn-group flex-wrap" aria-label="Employee list toolbar">
                        @if (userCan('employee.create'))
                            <a href="{{ route('company.employee-account.create') }}" class="btn btn-sm btn-success dg-btn">Add Employee</a>
                        @endif

                        @if (userCan('salary.view'))
                            <a href="{{ route('company.payroll-register.index') }}" class="btn btn-sm btn-outline-secondary dg-btn">
                                Payroll Register
                            </a>
                        @endif

                        <a href="{{ route('company.employee-account.print', request()->query()) }}" target="_blank" class="btn btn-sm btn-outline-secondary dg-btn">Print</a>
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
                    <header class="card-header dg-card-header">
                        <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
                            <h2 class="h6 mb-0">Employee List</h2>

                            <form method="GET" action="{{ route('company.employee-account.index') }}" class="d-flex align-items-center gap-2 mb-0">
                                <input type="hidden" name="search" value="{{ request('search') }}">
                                <input type="hidden" name="status" value="{{ request('status') }}">

                                <label for="per_page" class="mb-0 fw-bold">Per Page:</label>
                                <select name="per_page" id="per_page" class="form-select form-select-sm dg-select w-auto" onchange="this.form.submit()">
                                    <option value="10" @selected($perPage == 10)>10</option>
                                    <option value="25" @selected($perPage == 25)>25</option>
                                    <option value="50" @selected($perPage == 50)>50</option>
                                    <option value="100" @selected($perPage == 100)>100</option>
                                    <option value="200" @selected($perPage == 200)>200</option>
                                    <option value="500" @selected($perPage == 500)>500</option>
                                </select>
                            </form>
                        </div>
                    </header>

                    <div class="card-body dg-card-body">
                        <div class="table-responsive">
                            <table class="table dg-table">
                                <thead class="dg-head">
                                    <tr>
                                        <th scope="col">Image</th>
                                        <th scope="col">Code</th>
                                        <th scope="col">Name</th>
                                        <th scope="col">Designation</th>
                                        <th scope="col">Phone</th>
                                        <th scope="col">Email</th>
                                        <th scope="col" class="text-end">Opening Due</th>
                                        <th scope="col">Status</th>
                                        <th scope="col" width="140">Action</th>
                                    </tr>
                                </thead>

                                <tbody class="dg-body">
                                    @forelse ($employees as $employee)
                                        <tr class="dg-row">
                                            <td>
                                                @if ($employee->photo)
                                                    <img src="{{ asset($employee->photo) }}" alt="{{ $employee->full_name }}" width="40" height="40">
                                                @endif
                                            </td>
                                            <td>{{ $employee->employee_code }}</td>
                                            <td>{{ $employee->full_name }}</td>
                                            <td>{{ $employee->designation ?: '-' }}</td>
                                            <td>{{ $employee->phone ?: '-' }}</td>
                                            <td>{{ $employee->email ?: '-' }}</td>
                                            <td class="text-end">{{ number_format($employee->opening_due_salary, 2) }}</td>
                                            <td>
                                                @if ($employee->isActive())
                                                    <span class="badge bg-success">Active</span>
                                                @else
                                                    <span class="badge bg-secondary">Inactive</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group" aria-label="Employee actions">
                                                    @if (userCan('employee.view'))
                                                        <a href="{{ route('company.employee-account.show', $employee->id) }}" class="btn btn-sm btn-outline-info dg-btn">View</a>
                                                    @endif

                                                    @if (userCan('salary.view'))
                                                        <a href="{{ route('company.employee-ledger.show', $employee->id) }}" class="btn btn-sm btn-outline-secondary dg-btn">Ledger</a>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr class="dg-row">
                                            <td colspan="9" class="text-center">No Employees Found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                            <p class="mb-0 text-muted">
                                Showing {{ $employees->firstItem() ?? 0 }} to {{ $employees->lastItem() ?? 0 }} of {{ $employees->total() }} records
                            </p>

                            <nav aria-label="Employee list pagination">
                                {{ $employees->links() }}
                            </nav>
                        </div>
                    </div>
                </article>
            </section>

        </div>
    </main>

</div>

@endsection
