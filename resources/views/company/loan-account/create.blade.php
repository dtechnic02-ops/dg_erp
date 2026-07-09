@extends('company.layout')

@section('content')

<div class="container">

<form
method="POST"
enctype="multipart/form-data"
action="{{ route('company.employee-account.store') }}">

@csrf

<div class="card">

<div class="card-header">

<h4>

Create Employee

</h4>

</div>

<div class="card-body">

<div class="row">

<div class="col-md-3">

<label>Employee Code</label>

<input
readonly
name="employee_code"
value="{{ $employeeCode }}"
class="form-control">

</div>

<div class="col-md-3">

<label>First Name</label>

<input
required
name="first_name"
class="form-control">

</div>

<div class="col-md-3">

<label>Middle Name</label>

<input
name="middle_name"
class="form-control">

</div>

<div class="col-md-3">

<label>Last Name</label>

<input
name="last_name"
class="form-control">

</div>


<div class="col-md-3 mt-3">

<label>Phone</label>

<input
name="phone"
class="form-control">

</div>

<div class="col-md-3 mt-3">

<label>Email</label>

<input
name="email"
class="form-control">

</div>

<div class="col-md-3 mt-3">

<label>Gender</label>

<select
name="gender"
class="form-select">

<option value="">

Select

</option>

<option>

Male

</option>

<option>

Female

</option>

<option>

Other

</option>

</select>

</div>

<div class="col-md-3 mt-3">

<label>DOB</label>

<input
type="date"
name="dob"
class="form-control">

</div>


<div class="col-md-6 mt-3">

<label>Address</label>

<input
name="address"
class="form-control">

</div>

<div class="col-md-3 mt-3">

<label>Joining Date</label>

<input
required
type="date"
name="joining_date"
class="form-control">

</div>

<div class="col-md-3 mt-3">

<label>Designation</label>

<input
name="designation"
class="form-control">

</div>


<div class="col-md-3 mt-3">

<label>Department</label>

<input
name="department"
class="form-control">

</div>

<div class="col-md-3 mt-3">

<label>Post</label>

<input
name="post"
class="form-control">

</div>

<div class="col-md-3 mt-3">

<label>Employment Type</label>

<select
name="employment_type"
class="form-select">

<option value="permanent">

Permanent

</option>

<option value="contract">

Contract

</option>

<option value="temporary">

Temporary

</option>

<option value="intern">

Intern

</option>

</select>

</div>

<div class="col-md-3 mt-3">

<label>Salary Type</label>

<select
name="salary_type"
class="form-select">

<option value="monthly">

Monthly

</option>

<option value="daily">

Daily

</option>

</select>

</div>


<div class="col-md-3 mt-3">

<label>Basic Salary</label>

<input
name="basic_salary"
class="form-control">

</div>

<div class="col-md-3 mt-3">

<label>Opening Due Salary</label>

<input
name="opening_due_salary"
value="0"
class="form-control">

</div>


<div class="col-md-4 mt-3">

<label>Bank Name</label>

<input
name="bank_name"
class="form-control">

</div>

<div class="col-md-4 mt-3">

<label>Bank Account No</label>

<input
name="bank_account_no"
class="form-control">

</div>

<div class="col-md-4 mt-3">

<label>Account Holder</label>

<input
name="account_holder_name"
class="form-control">

</div>


<div class="col-md-3 mt-3">

<label>CIT No</label>

<input
name="cit_no"
class="form-control">

</div>

<div class="col-md-3 mt-3">

<label>PAN No</label>

<input
name="pan_no"
class="form-control">

</div>

<div class="col-md-3 mt-3">

<label>Emergency Contact</label>

<input
name="emergency_contact"
class="form-control">

</div>

<div class="col-md-3 mt-3">

<label>Emergency Phone</label>

<input
name="emergency_phone"
class="form-control">

</div>


<div class="col-md-3 mt-3">

<label>Photo</label>

<input
type="file"
name="photo"
class="form-control">

</div>

<div class="col-md-3 mt-3">

<label>CV</label>

<input
type="file"
name="cv_attachment"
class="form-control">

</div>

<div class="col-md-3 mt-3">

<label>ID Document</label>

<input
type="file"
name="id_document"
class="form-control">

</div>

<div class="col-md-3 mt-3">

<label>Contract</label>

<input
type="file"
name="contract_document"
class="form-control">

</div>


<div class="col-md-12 mt-3">

<label>Note</label>

<textarea
name="note"
class="form-control"></textarea>

</div>

</div>

</div>

<div class="card-footer">

<button class="btn btn-primary">

Save Employee

</button>

</div>

</div>

</form>

</div>

@endsection