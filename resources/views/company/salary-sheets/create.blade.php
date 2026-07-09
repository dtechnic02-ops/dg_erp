@extends('company.layout')

@section('content')

<div class="container-fluid">

<div class="row justify-content-center">

<div class="col-md-8">

<div class="card">

<div class="card-header">
<h5>Create Salary Sheet</h5>
</div>

<div class="card-body">

<form
action="{{ route('company.salary-sheets.store') }}"
method="POST"
>

@csrf

<div class="row">

<div class="col-md-4 mb-3">

<label>Financial Year</label>

<select
    name="financial_year_id"
    class="form-control"
    required
>

<option value="">
Select Financial Year
</option>

@foreach($financialYears as $financialYear)

<option value="{{ $financialYear->id }}">

{{ $financialYear->name }}

</option>

@endforeach

</select>

</div>
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

<option value="">
Select Employee
</option>

@foreach($employees as $employee)

<option
value="{{ $employee->id }}"
data-salary="{{ $employee->basic_salary }}"
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
class="form-control"
required
>

</div>


<div class="col-md-4 mb-3">

<label>Basic Salary</label>

<input

type="text"
name="basic_salary"
id="basic_salary"
class="form-control"
readonly
>

</div>


<div class="col-md-3 mb-3">

<label>Working Days</label>

<input
type="number"
name="working_days"
value="30"
class="form-control"
required
>

</div>


<div class="col-md-3 mb-3">

<label>Present Days</label>

<input
type="number"
name="present_days"
value="30"
class="form-control"
required
>

</div>


<div class="col-md-3 mb-3">

<label>Absent Days</label>

<input
type="number"
name="absent_days"
value="0"
class="form-control"
>

</div>


<div class="col-md-3 mb-3">

<label>Allowance</label>

<input
type="number"
step="0.01"
name="allowance"
value="0"
class="form-control"
>

</div>


<div class="col-md-3 mb-3">

<label>Bonus</label>

<input
type="number"
step="0.01"
name="bonus"
value="0"
class="form-control"
>

</div>


<div class="col-md-3 mb-3">

<label>Overtime Amount</label>

<input
type="number"
step="0.01"
name="overtime_amount"
value="0"
class="form-control"
>

</div>


<div class="col-md-3 mb-3">

<label>Deduction</label>

<input
type="number"
step="0.01"
name="deduction"
value="0"
class="form-control"
>

</div>


<div class="col-md-12 mb-3">

<label>Note</label>

<textarea
name="note"
rows="3"
class="form-control"
></textarea>

</div>


<div class="col-md-12">

<button
type="submit"
class="btn btn-primary"
>

Save Salary Sheet

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