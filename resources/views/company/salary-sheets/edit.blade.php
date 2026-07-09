@extends('company.layout')

@section('content')

<div class="container-fluid">

<div class="row justify-content-center">

<div class="col-md-8">

<div class="card">

<div class="card-header">

<h5>Edit Salary Sheet</h5>

</div>

<div class="card-body">

<form
action="{{ route('company.salary-sheets.update',$salarySheet->id) }}"
method="POST"
>

@csrf

<div class="row">

<div class="col-md-4 mb-3">

<label>Employee</label>

<select
name="employee_id"
id="employee_id"
class="form-control"
onchange="
document.getElementById('basic_salary').value =
this.options[this.selectedIndex].dataset.salary || 0;
"
>

@foreach($employees as $employee)

<option
value="{{ $employee->id }}"
data-salary="{{ $employee->basic_salary }}"
{{ $salarySheet->employee_id == $employee->id ? 'selected' : '' }}
>

{{ $employee->employee_code }}
-
{{ $employee->first_name }}

</option>

@endforeach

</select>

</div>

<div class="col-md-4 mb-3">

<label>Salary Month</label>

<input
type="month"
name="salary_month"
value="{{ $salarySheet->salary_month }}"
class="form-control"
required
>

</div>

<div class="col-md-4 mb-3">

<label>Basic Salary</label>

<input
type="text"
id="basic_salary"
class="form-control"
value="{{ $salarySheet->basic_salary }}"
readonly
>

</div>

<div class="col-md-3 mb-3">

<label>Working Days</label>

<input
type="number"
name="working_days"
value="{{ $salarySheet->working_days }}"
class="form-control"
required
>

</div>

<div class="col-md-3 mb-3">

<label>Present Days</label>

<input
type="number"
name="present_days"
value="{{ $salarySheet->present_days }}"
class="form-control"
required
>

</div>

<div class="col-md-3 mb-3">

<label>Absent Days</label>

<input
type="number"
name="absent_days"
value="{{ $salarySheet->absent_days }}"
class="form-control"
>

</div>

<div class="col-md-3 mb-3">

<label>Allowance</label>

<input
type="number"
step="0.01"
name="allowance"
value="{{ $salarySheet->allowance }}"
class="form-control"
>

</div>

<div class="col-md-3 mb-3">

<label>Bonus</label>

<input
type="number"
step="0.01"
name="bonus"
value="{{ $salarySheet->bonus }}"
class="form-control"
>

</div>

<div class="col-md-3 mb-3">

<label>Overtime Amount</label>

<input
type="number"
step="0.01"
name="overtime_amount"
value="{{ $salarySheet->overtime_amount }}"
class="form-control"
>

</div>

<div class="col-md-3 mb-3">

<label>Deduction</label>

<input
type="number"
step="0.01"
name="deduction"
value="{{ $salarySheet->deduction }}"
class="form-control"
>

</div>

<div class="col-md-12 mb-3">

<label>Note</label>

<textarea
name="note"
rows="3"
class="form-control"
>{{ $salarySheet->note }}</textarea>

</div>

<div class="col-md-12">

<button
type="submit"
class="btn btn-primary"
>

Update Salary Sheet

</button>

<a
href="{{ route('company.salary-sheets.index') }}"
class="btn btn-secondary"
>

Back

</a>

</div>

</div>

</form>

</div>

</div>

</div>

</div>

</div>

@endsection