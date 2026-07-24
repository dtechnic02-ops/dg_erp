@extends('company.layout')

@section('content')

<div class="dg-page">

    <header class="dg-toolbar">
        <div class="container-fluid">
            <div class="row align-items-center g-2">
                <div class="col">
                    <h1 class="h4 mb-0">Edit Employee</h1>
                    <p class="text-muted small mb-0">{{ $employee->employee_code }} — {{ $employee->full_name }}</p>
                </div>
                <div class="col-auto">
                    <nav class="btn-group" aria-label="Employee edit toolbar">
                        <a href="{{ route('company.employee-account.show', $employee->id) }}" class="btn btn-outline-secondary dg-btn">View</a>
                        <a href="{{ route('company.employee-account.index') }}" class="btn btn-outline-secondary dg-btn">Back</a>
                    </nav>
                </div>
            </div>
        </div>
    </header>

    <main class="dg-container">
        <div class="container-fluid">

            @if ($errors->any())
                <div class="alert alert-danger dg-alert" role="alert">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger dg-alert" role="alert">{{ session('error') }}</div>
            @endif

            <form method="POST" enctype="multipart/form-data" action="{{ route('company.employee-account.update', $employee->id) }}">
                @csrf

                <section class="dg-section">
                    <article class="card dg-card">
                        <header class="card-header dg-card-header">
                            <h2 class="h6 mb-0">Employee Information</h2>
                        </header>
                        <div class="card-body dg-card-body">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label for="employee_code_display" class="form-label">Employee Code</label>
                                    <input type="text" id="employee_code_display" value="{{ $employee->employee_code }}" class="form-control dg-input" readonly>
                                </div>

                                <div class="col-md-3">
                                    <label for="first_name" class="form-label">First Name *</label>
                                    <input required name="first_name" id="first_name" value="{{ old('first_name', $employee->first_name) }}" class="form-control dg-input">
                                </div>

                                <div class="col-md-3">
                                    <label for="middle_name" class="form-label">Middle Name</label>
                                    <input name="middle_name" id="middle_name" value="{{ old('middle_name', $employee->middle_name) }}" class="form-control dg-input">
                                </div>

                                <div class="col-md-3">
                                    <label for="last_name" class="form-label">Last Name</label>
                                    <input name="last_name" id="last_name" value="{{ old('last_name', $employee->last_name) }}" class="form-control dg-input">
                                </div>

                                <div class="col-md-3">
                                    <label for="phone" class="form-label">Phone</label>
                                    <input name="phone" id="phone" value="{{ old('phone', $employee->phone) }}" class="form-control dg-input">
                                </div>

                                <div class="col-md-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input name="email" id="email" value="{{ old('email', $employee->email) }}" class="form-control dg-input">
                                </div>

                                <div class="col-md-3">
                                    <label for="gender" class="form-label">Gender</label>
                                    <select name="gender" id="gender" class="form-select dg-select">
                                        <option value="Male" @selected(old('gender', $employee->gender) === 'Male')>Male</option>
                                        <option value="Female" @selected(old('gender', $employee->gender) === 'Female')>Female</option>
                                        <option value="Other" @selected(old('gender', $employee->gender) === 'Other')>Other</option>
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label for="dob" class="form-label">DOB</label>
                                    <input type="date" name="dob" id="dob" value="{{ old('dob', $employee->dob) }}" class="form-control dg-input">
                                </div>

                                <div class="col-md-6">
                                    <label for="address" class="form-label">Address</label>
                                    <input name="address" id="address" value="{{ old('address', $employee->address) }}" class="form-control dg-input">
                                </div>

                                <div class="col-md-3">
                                    <label for="joining_date" class="form-label">Joining Date *</label>
                                    <input required type="date" name="joining_date" id="joining_date" value="{{ old('joining_date', $employee->joining_date) }}" class="form-control dg-input">
                                </div>

                                <div class="col-md-3">
                                    <label for="designation" class="form-label">Designation</label>
                                    <input name="designation" id="designation" value="{{ old('designation', $employee->designation) }}" class="form-control dg-input">
                                </div>

                                <div class="col-md-3">
                                    <label for="department" class="form-label">Department</label>
                                    <input name="department" id="department" value="{{ old('department', $employee->department) }}" class="form-control dg-input">
                                </div>

                                <div class="col-md-3">
                                    <label for="post" class="form-label">Post</label>
                                    <input name="post" id="post" value="{{ old('post', $employee->post) }}" class="form-control dg-input">
                                </div>

                                <div class="col-md-3">
                                    <label for="employment_type" class="form-label">Employment Type</label>
                                    <select name="employment_type" id="employment_type" class="form-select dg-select">
                                        <option value="permanent" @selected(old('employment_type', $employee->employment_type) === 'permanent')>Permanent</option>
                                        <option value="contract" @selected(old('employment_type', $employee->employment_type) === 'contract')>Contract</option>
                                        <option value="temporary" @selected(old('employment_type', $employee->employment_type) === 'temporary')>Temporary</option>
                                        <option value="intern" @selected(old('employment_type', $employee->employment_type) === 'intern')>Intern</option>
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label for="salary_type" class="form-label">Salary Type</label>
                                    <select name="salary_type" id="salary_type" class="form-select dg-select">
                                        <option value="monthly" @selected(old('salary_type', $employee->salary_type) === 'monthly')>Monthly</option>
                                        <option value="daily" @selected(old('salary_type', $employee->salary_type) === 'daily')>Daily</option>
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label for="basic_salary" class="form-label">Basic Salary</label>
                                    <input name="basic_salary" id="basic_salary" value="{{ old('basic_salary', $employee->basic_salary) }}" class="form-control dg-input">
                                </div>

                                <div class="col-md-3">
                                    <label for="opening_due_salary" class="form-label">Opening Due Salary</label>
                                    <input name="opening_due_salary" id="opening_due_salary" value="{{ old('opening_due_salary', $employee->opening_due_salary) }}" class="form-control dg-input">
                                </div>

                                <div class="col-md-4">
                                    <label for="bank_name" class="form-label">Bank Name</label>
                                    <input name="bank_name" id="bank_name" value="{{ old('bank_name', $employee->bank_name) }}" class="form-control dg-input">
                                </div>

                                <div class="col-md-4">
                                    <label for="bank_account_no" class="form-label">Bank Account</label>
                                    <input name="bank_account_no" id="bank_account_no" value="{{ old('bank_account_no', $employee->bank_account_no) }}" class="form-control dg-input">
                                </div>

                                <div class="col-md-4">
                                    <label for="account_holder_name" class="form-label">Account Holder</label>
                                    <input name="account_holder_name" id="account_holder_name" value="{{ old('account_holder_name', $employee->account_holder_name) }}" class="form-control dg-input">
                                </div>

                                <div class="col-md-3">
                                    <label for="cit_no" class="form-label">CIT No</label>
                                    <input name="cit_no" id="cit_no" value="{{ old('cit_no', $employee->cit_no) }}" class="form-control dg-input">
                                </div>

                                <div class="col-md-3">
                                    <label for="pan_no" class="form-label">PAN No</label>
                                    <input name="pan_no" id="pan_no" value="{{ old('pan_no', $employee->pan_no) }}" class="form-control dg-input">
                                </div>

                                <div class="col-md-3">
                                    <label for="emergency_contact" class="form-label">Emergency Contact</label>
                                    <input name="emergency_contact" id="emergency_contact" value="{{ old('emergency_contact', $employee->emergency_contact) }}" class="form-control dg-input">
                                </div>

                                <div class="col-md-3">
                                    <label for="emergency_phone" class="form-label">Emergency Phone</label>
                                    <input name="emergency_phone" id="emergency_phone" value="{{ old('emergency_phone', $employee->emergency_phone) }}" class="form-control dg-input">
                                </div>

                                <div class="col-md-3">
                                    <label for="photo" class="form-label">Photo</label>
                                    <input type="file" name="photo" id="photo" class="form-control dg-input">
                                    @if ($employee->photo)
                                        <div class="mt-2">
                                            <img src="{{ asset($employee->photo) }}" width="100" alt="Employee photo">
                                        </div>
                                    @endif
                                </div>

                                <div class="col-md-3">
                                    <label for="cv_attachment" class="form-label">CV Attachment</label>
                                    <input type="file" name="cv_attachment" id="cv_attachment" class="form-control dg-input">
                                    @if ($employee->cv_attachment)
                                        <div class="mt-2">
                                            <a target="_blank" class="btn btn-sm btn-outline-secondary dg-btn" href="{{ asset($employee->cv_attachment) }}">View CV</a>
                                        </div>
                                    @endif
                                </div>

                                <div class="col-md-3">
                                    <label for="id_document" class="form-label">ID Document</label>
                                    <input type="file" name="id_document" id="id_document" class="form-control dg-input">
                                    @if ($employee->id_document)
                                        <div class="mt-2">
                                            <a target="_blank" class="btn btn-sm btn-outline-secondary dg-btn" href="{{ asset($employee->id_document) }}">View ID</a>
                                        </div>
                                    @endif
                                </div>

                                <div class="col-md-3">
                                    <label for="contract_document" class="form-label">Contract Document</label>
                                    <input type="file" name="contract_document" id="contract_document" class="form-control dg-input">
                                    @if ($employee->contract_document)
                                        <div class="mt-2">
                                            <a target="_blank" class="btn btn-sm btn-outline-secondary dg-btn" href="{{ asset($employee->contract_document) }}">View Contract</a>
                                        </div>
                                    @endif
                                </div>

                                <div class="col-12">
                                    <label for="note" class="form-label">Note</label>
                                    <textarea name="note" id="note" rows="4" class="form-control dg-input">{{ old('note', $employee->note) }}</textarea>
                                </div>
                            </div>
                        </div>
                        <footer class="card-footer dg-card-footer">
                            <button type="submit" class="btn btn-primary dg-btn">Update Employee</button>
                        </footer>
                    </article>
                </section>
            </form>

        </div>
    </main>

</div>

@endsection
