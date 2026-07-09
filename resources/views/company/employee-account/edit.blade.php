@extends('company.layout')

@section('content')

<div class="container">

<div class="card">

<div class="card-header">

<h4>

Edit Employee

</h4>

</div>

<form
method="POST"
enctype="multipart/form-data"
action="{{ route(
'company.employee-account.update',
$employee->id
) }}">

@csrf

<div class="card-body">

<div class="row">

<div class="col-md-3">

<label>Employee Code</label>

<input
readonly
value="{{ $employee->employee_code }}"
class="form-control">

</div>

<div class="col-md-3">

<label>First Name</label>

<input
name="first_name"
value="{{ $employee->first_name }}"
class="form-control">

</div>

<div class="col-md-3">

<label>Middle Name</label>

<input
name="middle_name"
value="{{ $employee->middle_name }}"
class="form-control">

</div>

<div class="col-md-3">

<label>Last Name</label>

<input
name="last_name"
value="{{ $employee->last_name }}"
class="form-control">

</div>


<div class="col-md-3 mt-3">

<label>Phone</label>

<input
name="phone"
value="{{ $employee->phone }}"
class="form-control">

</div>

<div class="col-md-3 mt-3">

<label>Email</label>

<input
name="email"
value="{{ $employee->email }}"
class="form-control">

</div>

<div class="col-md-3 mt-3">

<label>Gender</label>

<select
name="gender"
class="form-select">

<option value="Male"
{{ $employee->gender=='Male' ? 'selected':'' }}>

Male

</option>

<option value="Female"
{{ $employee->gender=='Female' ? 'selected':'' }}>

Female

</option>

<option value="Other"
{{ $employee->gender=='Other' ? 'selected':'' }}>

Other

</option>

</select>

</div>

<div class="col-md-3 mt-3">

<label>DOB</label>

<input
type="date"
name="dob"
value="{{ $employee->dob }}"
class="form-control">

</div>


<div class="col-md-6 mt-3">

<label>Address</label>

<input
name="address"
value="{{ $employee->address }}"
class="form-control">

</div>

<div class="col-md-3 mt-3">

<label>Joining Date</label>

<input
type="date"
name="joining_date"
value="{{ $employee->joining_date }}"
class="form-control">

</div>

<div class="col-md-3 mt-3">

<label>Designation</label>

<input
name="designation"
value="{{ $employee->designation }}"
class="form-control">

</div>


<div class="col-md-3 mt-3">

<label>Department</label>

<input
name="department"
value="{{ $employee->department }}"
class="form-control">

</div>

<div class="col-md-3 mt-3">

<label>Post</label>

<input
name="post"
value="{{ $employee->post }}"
class="form-control">

</div>

<div class="col-md-3 mt-3">

<label>Employment Type</label>

<select
name="employment_type"
class="form-select">

<option value="permanent"
{{ $employee->employment_type=='permanent' ? 'selected':'' }}>

Permanent

</option>

<option value="contract"
{{ $employee->employment_type=='contract' ? 'selected':'' }}>

Contract

</option>

<option value="temporary"
{{ $employee->employment_type=='temporary' ? 'selected':'' }}>

Temporary

</option>

<option value="intern"
{{ $employee->employment_type=='intern' ? 'selected':'' }}>

Intern

</option>

</select>

</div>

<div class="col-md-3 mt-3">

<label>Salary Type</label>

<select
name="salary_type"
class="form-select">

<option value="monthly"
{{ $employee->salary_type=='monthly' ? 'selected':'' }}>

Monthly

</option>

<option value="daily"
{{ $employee->salary_type=='daily' ? 'selected':'' }}>

Daily

</option>

</select>

</div>


<div class="col-md-3 mt-3">

<label>Basic Salary</label>

<input
name="basic_salary"
value="{{ $employee->basic_salary }}"
class="form-control">

</div>

<div class="col-md-3 mt-3">

<label>Opening Due Salary</label>

<input
name="opening_due_salary"
value="{{ $employee->opening_due_salary }}"
class="form-control">

</div>


<div class="col-md-4 mt-3">

<label>Bank Name</label>

<input
name="bank_name"
value="{{ $employee->bank_name }}"
class="form-control">

</div>

<div class="col-md-4 mt-3">

<label>Bank Account</label>

<input
name="bank_account_no"
value="{{ $employee->bank_account_no }}"
class="form-control">

</div>

<div class="col-md-4 mt-3">

<label>Account Holder</label>

<input
name="account_holder_name"
value="{{ $employee->account_holder_name }}"
class="form-control">

</div>


<div class="col-md-3 mt-3">

<label>CIT No</label>

<input
name="cit_no"
value="{{ $employee->cit_no }}"
class="form-control">

</div>

<div class="col-md-3 mt-3">

<label>PAN No</label>

<input
name="pan_no"
value="{{ $employee->pan_no }}"
class="form-control">

</div>

<div class="col-md-3 mt-3">

<label>Emergency Contact</label>

<input
name="emergency_contact"
value="{{ $employee->emergency_contact }}"
class="form-control">

</div>
<div class="col-md-3 mt-3">

<label>

Photo

</label>

<input
type="file"
name="photo"
class="form-control">

@if($employee->photo)

<div class="mt-2">

<img
src="{{ asset($employee->photo) }}"
width="100">

</div>

@endif

</div>



<div class="col-md-3 mt-3">

<label>

CV Attachment

</label>

<input
type="file"
name="cv_attachment"
class="form-control">

@if($employee->cv_attachment)

<div class="mt-2">

<a
target="_blank"
class="btn btn-sm btn-dark"
href="{{ asset(
$employee->cv_attachment
) }}">

View CV

</a>

</div>

@endif

</div>



<div class="col-md-3 mt-3">

<label>

ID Document

label>

<input
type="file"
name="id_document"
class="form-control">

@if($employee->id_document)

<div class="mt-2">

<a
target="_blank"
class="btn btn-sm btn-dark"
href="{{ asset(
$employee->id_document
) }}">

View ID

</a>

</div>

@endif

</div>



<div class="col-md-3 mt-3">

<label>

Contract Document

</label>

<input
type="file"
name="contract_document"
class="form-control">

@if($employee->contract_document)

<div class="mt-2">

<a
target="_blank"
class="btn btn-sm btn-dark"
href="{{ asset(
$employee->contract_document
) }}">

View Contract

</a>

</div>

@endif

</div>
<div class="col-md-3 mt-3">

<label>Emergency Phone</label>

<input
name="emergency_phone"
value="{{ $employee->emergency_phone }}"
class="form-control">

</div>


<div class="col-md-12 mt-3">

<label>Note</label>

<textarea
name="note"
class="form-control">{{ $employee->note }}</textarea>

</div>

</div>

</div>

<div class="card-footer">

<button class="btn btn-primary">

Update Employee

</button>

</div>

</form>

</div>

</div>

@endsection