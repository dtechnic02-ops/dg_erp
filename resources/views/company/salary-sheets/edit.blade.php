@extends('company.layout')

@section('content')

<div class="dg-page">

    <header class="dg-toolbar">
        <div class="container-fluid">
            <div class="row align-items-center g-2">
                <div class="col">
                    <h1 class="h4 mb-0">Edit Salary Sheet</h1>
                    <p class="text-muted small mb-0">{{ $salarySheet->salary_month }} — {{ $salarySheet->employee->full_name ?? $salarySheet->employee->first_name }}</p>
                </div>
                <div class="col-auto">
                    <nav class="btn-group" aria-label="Salary sheet edit toolbar">
                        <a href="{{ route('company.salary-sheets.show', $salarySheet->id) }}" class="btn btn-outline-secondary dg-btn">View</a>
                        <a href="{{ route('company.salary-sheets.index') }}" class="btn btn-outline-secondary dg-btn">Back</a>
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

            <form method="POST" action="{{ route('company.salary-sheets.update', $salarySheet->id) }}">
                @csrf
                <input type="hidden" name="financial_year_id" value="{{ $salarySheet->financial_year_id }}">

                <section class="dg-section">
                    <article class="card dg-card">
                        <header class="card-header dg-card-header">
                            <h2 class="h6 mb-0">Salary Details</h2>
                        </header>
                        <div class="card-body dg-card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="financial_year_display" class="form-label">Financial Year</label>
                                    <input type="text" id="financial_year_display" value="{{ $salarySheet->financialYear->name ?? $salarySheet->financial_year_id }}" class="form-control dg-input" readonly>
                                </div>

                                <div class="col-md-4">
                                    <label for="employee_id" class="form-label">Employee *</label>
                                    <select name="employee_id" id="employee_id" class="form-select dg-select" required onchange="document.getElementById('basic_salary').value = this.options[this.selectedIndex].dataset.salary || 0;">
                                        @foreach ($employees as $employee)
                                            <option value="{{ $employee->id }}" data-salary="{{ $employee->basic_salary }}" @selected(old('employee_id', $salarySheet->employee_id) == $employee->id)>
                                                {{ $employee->employee_code }} - {{ $employee->full_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label for="salary_month" class="form-label">Salary Month *</label>
                                    <input type="month" name="salary_month" id="salary_month" value="{{ old('salary_month', $salarySheet->salary_month) }}" class="form-control dg-input" required>
                                </div>

                                <div class="col-md-4">
                                    <label for="basic_salary" class="form-label">Basic Salary</label>
                                    <input type="text" id="basic_salary" value="{{ $salarySheet->basic_salary }}" class="form-control dg-input" readonly>
                                </div>

                                <div class="col-md-3">
                                    <label for="working_days" class="form-label">Working Days *</label>
                                    <input type="number" name="working_days" id="working_days" value="{{ old('working_days', $salarySheet->working_days) }}" class="form-control dg-input" required>
                                </div>

                                <div class="col-md-3">
                                    <label for="present_days" class="form-label">Present Days *</label>
                                    <input type="number" name="present_days" id="present_days" value="{{ old('present_days', $salarySheet->present_days) }}" class="form-control dg-input" required>
                                </div>

                                <div class="col-md-3">
                                    <label for="absent_days" class="form-label">Absent Days</label>
                                    <input type="number" name="absent_days" id="absent_days" value="{{ old('absent_days', $salarySheet->absent_days) }}" class="form-control dg-input">
                                </div>

                                <div class="col-md-3">
                                    <label for="allowance" class="form-label">Allowance</label>
                                    <input type="number" step="0.01" name="allowance" id="allowance" value="{{ old('allowance', $salarySheet->allowance) }}" class="form-control dg-input">
                                </div>

                                <div class="col-md-3">
                                    <label for="bonus" class="form-label">Bonus</label>
                                    <input type="number" step="0.01" name="bonus" id="bonus" value="{{ old('bonus', $salarySheet->bonus) }}" class="form-control dg-input">
                                </div>

                                <div class="col-md-3">
                                    <label for="overtime_amount" class="form-label">Overtime Amount</label>
                                    <input type="number" step="0.01" name="overtime_amount" id="overtime_amount" value="{{ old('overtime_amount', $salarySheet->overtime_amount) }}" class="form-control dg-input">
                                </div>

                                <div class="col-md-3">
                                    <label for="deduction" class="form-label">Deduction</label>
                                    <input type="number" step="0.01" name="deduction" id="deduction" value="{{ old('deduction', $salarySheet->deduction) }}" class="form-control dg-input">
                                </div>

                                <div class="col-12">
                                    <label for="note" class="form-label">Note</label>
                                    <textarea name="note" id="note" rows="3" class="form-control dg-input">{{ old('note', $salarySheet->note) }}</textarea>
                                </div>
                            </div>
                        </div>
                        <footer class="card-footer dg-card-footer">
                            <button type="submit" class="btn btn-primary dg-btn">Update Salary Sheet</button>
                        </footer>
                    </article>
                </section>
            </form>

        </div>
    </main>

</div>

@endsection
