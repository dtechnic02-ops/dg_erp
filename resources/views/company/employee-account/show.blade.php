@extends('company.layout')

@section('content')

@include(
'company.partials.print-style'
)

<div class="container-fluid">

<div id="printArea">


@include(
'company.partials.print-header-portrait'
)

<div class="container">

<div class="card">

<div class="card-header d-flex justify-content-between">

<h4>

Employee Details

</h4>

<div class="d-flex gap-2">

<a
class="btn btn-warning"
href="{{ route(
'company.employee-account.edit',
$employee->id
) }}">

Edit

</a>

<button
onclick="window.print()"
class="btn btn-dark">

Print

</button>

</div>

</div>


<div class="card-body">

<div class="row">

<div class="col-md-3 text-center">

@if($employee->photo)

<img
src="{{ asset(
$employee->photo
) }}"
class="img-fluid rounded border">

@else

<div class="border p-5">

No Photo

</div>

@endif

</div>


<div class="col-md-9">

<table class="table table-bordered">

<tr>

<th width="220">

Employee Code

</th>

<td>

{{ $employee->employee_code }}

</td>

</tr>

<tr>

<th>

Full Name

</th>

<td>

{{

trim(

$employee->first_name

.' '

.$employee->middle_name

.' '

.$employee->last_name

)

}}

</td>

</tr>

<tr>

<th>

Phone

</th>

<td>

{{ $employee->phone }}

</td>

</tr>

<tr>

<th>

Email

</th>

<td>

{{ $employee->email }}

</td>

</tr>

<tr>

<th>

Gender

</th>

<td>

{{ $employee->gender }}

</td>

</tr>

<tr>

<th>

DOB

</th>

<td>

{{ $employee->dob }}

</td>

</tr>

<tr>

<th>

Address

</th>

<td>

{{ $employee->address }}

</td>

</tr>

<tr>

<th>

Joining Date

</th>

<td>

{{ $employee->joining_date }}

</td>

</tr>

<tr>

<th>

Designation

</th>

<td>

{{ $employee->designation }}

</td>

</tr>

<tr>

<th>

Department

</th>

<td>

{{ $employee->department }}

</td>

</tr>

<tr>

<th>

Post

</th>

<td>

{{ $employee->post }}

</td>

</tr>

<tr>

<th>

Employment Type

</th>

<td>

{{ ucfirst(
$employee->employment_type
) }}

</td>

</tr>

<tr>

<th>

Salary Type

</th>

<td>

{{ ucfirst(
$employee->salary_type
) }}

</td>

</tr>

<tr>

<th>

Basic Salary

</th>

<td>

{{ number_format(
$employee->basic_salary,
2
) }}

</td>

</tr>

<tr>

<th>

Opening Due Salary

</th>

<td>

{{ number_format(
$employee->opening_due_salary,
2
) }}

</td>

</tr>

<tr>

<th>

Bank Name

</th>

<td>

{{ $employee->bank_name }}

</td>

</tr>

<tr>

<th>

Bank Account

</th>

<td>

{{ $employee->bank_account_no }}

</td>

</tr>

<tr>

<th>

Account Holder

</th>

<td>

{{ $employee->account_holder_name }}

</td>

</tr>

<tr>

<th>

CIT No

</th>

<td>

{{ $employee->cit_no }}

</td>

</tr>

<tr>

<th>

PAN No

</th>

<td>

{{ $employee->pan_no }}

</td>

</tr>

<tr>

<th>

Emergency Contact

</th>

<td>

{{ $employee->emergency_contact }}

</td>

</tr>

<tr>

<th>

Emergency Phone

</th>

<td>

{{ $employee->emergency_phone }}

</td>

</tr>

<tr>

<th>

Note

</th>

<td>

{{ $employee->note }}

</td>

</tr>

</table>

</div>

</div>


<hr>


<h5>

Documents

</h5>

<div class="d-flex flex-wrap gap-2">

@if($employee->cv_attachment)

<a
target="_blank"
class="btn btn-dark"
href="{{ asset(
$employee->cv_attachment
) }}">

View CV

</a>

@endif


@if($employee->id_document)

<a
target="_blank"
class="btn btn-primary"
href="{{ asset(
$employee->id_document
) }}">

View ID Document

</a>

@endif


@if($employee->contract_document)

<a
target="_blank"
class="btn btn-success"
href="{{ asset(
$employee->contract_document
) }}">

View Contract

</a>

@endif

</div>

</div>

</div>

</div>

@endsection