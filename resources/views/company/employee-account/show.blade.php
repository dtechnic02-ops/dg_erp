@extends('company.layout')

@push('styles')
    @include('company.partials.print-style')
@endpush

@section('content')

<div class="dg-page">

    <header class="dg-toolbar">
        <div class="container-fluid">
            <div class="row align-items-center g-2">
                <div class="col">
                    <h1 class="h4 mb-0">Employee Details</h1>
                    <p class="text-muted small mb-0">{{ $employee->employee_code }} — {{ $employee->full_name }}</p>
                </div>
                <div class="col-auto">
                    <nav class="btn-group flex-wrap" aria-label="Employee show toolbar">
                        <a href="{{ route('company.employee-account.index') }}" class="btn btn-outline-secondary dg-btn">Back</a>

                        @if (userCan('employee.edit'))
                            <a href="{{ route('company.employee-account.edit', $employee->id) }}" class="btn btn-outline-success dg-btn">Edit</a>
                        @endif

                        @if (userCan('salary.view'))
                            <a href="{{ route('company.employee-ledger.show', $employee->id) }}" class="btn btn-outline-info dg-btn">Employee Ledger</a>
                        @endif

                        @if (userCan('employee.status'))
                            <form method="POST" action="{{ route('company.employee-account.toggle-status', $employee->id) }}" class="d-inline">
                                @csrf
                                <input type="hidden" name="status" value="{{ $employee->isActive() ? \App\Models\EmployeeAccount::STATUS_INACTIVE : \App\Models\EmployeeAccount::STATUS_ACTIVE }}">
                                <button type="submit" class="btn btn-outline-warning dg-btn" onclick="return confirm('{{ $employee->isActive() ? 'Deactivate this employee?' : 'Activate this employee?' }}')">
                                    {{ $employee->isActive() ? 'Deactivate' : 'Activate' }}
                                </button>
                            </form>
                        @endif

                        @if (userCan('employee.delete'))
                            <form method="POST" action="{{ route('company.employee-account.delete', $employee->id) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-outline-danger dg-btn" onclick="return confirm('Delete this employee? This is only allowed when no HR records exist.')">Delete</button>
                            </form>
                        @endif

                        <button type="button" onclick="window.print()" class="btn btn-outline-dark dg-btn">Print</button>
                    </nav>
                </div>
            </div>
        </div>
    </header>

    <main class="dg-container">
        <div class="container-fluid" id="printArea">

            @include('company.partials.print-header-portrait')

            @if (session('success'))
                <div class="alert alert-success dg-alert" role="alert">{{ session('success') }}</div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger dg-alert" role="alert">{{ session('error') }}</div>
            @endif

            <section class="dg-section">
                <article class="card dg-card">
                    <header class="card-header dg-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h2 class="h6 mb-0">Profile</h2>
                        @if ($employee->isActive())
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-secondary">Inactive</span>
                        @endif
                    </header>
                    <div class="card-body dg-card-body">
                        <div class="row g-4">
                            <div class="col-md-3 text-center">
                                @if ($employee->photo)
                                    <img src="{{ asset($employee->photo) }}" class="img-fluid rounded border" alt="Employee photo">
                                @else
                                    <div class="border rounded p-5 text-muted">No Photo</div>
                                @endif
                            </div>

                            <div class="col-md-9">
                                <div class="table-responsive">
                                    <table class="table dg-table">
                                        <tbody>
                                            <tr><th width="220">Employee Code</th><td>{{ $employee->employee_code }}</td></tr>
                                            <tr><th>Full Name</th><td>{{ $employee->full_name }}</td></tr>
                                            <tr><th>Phone</th><td>{{ $employee->phone ?: '-' }}</td></tr>
                                            <tr><th>Email</th><td>{{ $employee->email ?: '-' }}</td></tr>
                                            <tr><th>Gender</th><td>{{ $employee->gender ?: '-' }}</td></tr>
                                            <tr><th>DOB</th><td>{{ $employee->dob ?: '-' }}</td></tr>
                                            <tr><th>Address</th><td>{{ $employee->address ?: '-' }}</td></tr>
                                            <tr><th>Joining Date</th><td>{{ $employee->joining_date }}</td></tr>
                                            <tr><th>Designation</th><td>{{ $employee->designation ?: '-' }}</td></tr>
                                            <tr><th>Department</th><td>{{ $employee->department ?: '-' }}</td></tr>
                                            <tr><th>Post</th><td>{{ $employee->post ?: '-' }}</td></tr>
                                            <tr><th>Employment Type</th><td>{{ ucfirst($employee->employment_type) }}</td></tr>
                                            <tr><th>Salary Type</th><td>{{ ucfirst($employee->salary_type) }}</td></tr>
                                            <tr><th>Basic Salary</th><td>{{ number_format($employee->basic_salary, 2) }}</td></tr>
                                            <tr><th>Opening Due Salary</th><td>{{ number_format($employee->opening_due_salary, 2) }}</td></tr>
                                            <tr><th>Bank Name</th><td>{{ $employee->bank_name ?: '-' }}</td></tr>
                                            <tr><th>Bank Account</th><td>{{ $employee->bank_account_no ?: '-' }}</td></tr>
                                            <tr><th>Account Holder</th><td>{{ $employee->account_holder_name ?: '-' }}</td></tr>
                                            <tr><th>CIT No</th><td>{{ $employee->cit_no ?: '-' }}</td></tr>
                                            <tr><th>PAN No</th><td>{{ $employee->pan_no ?: '-' }}</td></tr>
                                            <tr><th>Emergency Contact</th><td>{{ $employee->emergency_contact ?: '-' }}</td></tr>
                                            <tr><th>Emergency Phone</th><td>{{ $employee->emergency_phone ?: '-' }}</td></tr>
                                            <tr><th>Note</th><td>{{ $employee->note ?: '-' }}</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
            </section>

            <section class="dg-section">
                <article class="card dg-card">
                    <header class="card-header dg-card-header">
                        <h2 class="h6 mb-0">Documents</h2>
                    </header>
                    <div class="card-body dg-card-body">
                        <div class="d-flex flex-wrap gap-2">
                            @if ($employee->cv_attachment)
                                <a target="_blank" class="btn btn-outline-secondary dg-btn" href="{{ asset($employee->cv_attachment) }}">View CV</a>
                            @endif

                            @if ($employee->id_document)
                                <a target="_blank" class="btn btn-outline-secondary dg-btn" href="{{ asset($employee->id_document) }}">View ID Document</a>
                            @endif

                            @if ($employee->contract_document)
                                <a target="_blank" class="btn btn-outline-secondary dg-btn" href="{{ asset($employee->contract_document) }}">View Contract</a>
                            @endif

                            @if (!$employee->cv_attachment && !$employee->id_document && !$employee->contract_document)
                                <span class="text-muted">No documents uploaded.</span>
                            @endif
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
                                    <tr>
                                        <th width="220">Created By</th>
                                        <td>{{ $employee->creator->name ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Created At</th>
                                        <td>{{ $employee->created_at?->format('Y-m-d H:i') ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Updated By</th>
                                        <td>{{ $employee->updater->name ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Updated At</th>
                                        <td>{{ $employee->updated_at?->format('Y-m-d H:i') ?? '-' }}</td>
                                    </tr>
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
