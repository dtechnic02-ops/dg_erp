@extends('company.layout')

@section('content')

<div class="container">

<div class="card">

<div class="card-header">

Create Party Account

</div>

<div class="card-body">

<form
method="POST"
enctype="multipart/form-data"
action="{{ route(
'company.party-account.store'
) }}">

@csrf

<div class="row">

<div class="col-md-4 mb-3">

<label>

Account No

</label>

<input
readonly
value="{{ $accountNo }}"
class="form-control">

</div>

<div class="col-md-4 mb-3">

<label>

Name

</label>

<input
required
name="name"
class="form-control">

</div>

<div class="col-md-4 mb-3">

<label>

Phone

</label>

<input
name="phone"
class="form-control">

</div>

<div class="col-md-6 mb-3">

<label>

Type

</label>

<select
name="type"
class="form-select">

<option value="person">

Person

</option>

<option value="bank">

Bank

</option>

<option value="customer">

Customer

</option>

<option value="supplier">

Supplier

</option>

<option value="company">

Company

</option>

<option value="other">

Other

</option>

</select>

</div>

<div class="col-md-6 mb-3">

<label>

Opening Balance

</label>

<input
type="number"
step="0.01"
name="opening_balance"
value="0"
class="form-control">

</div>

<div class="col-md-12 mb-3">

<label>

Address

</label>

<textarea
name="address"
class="form-control"></textarea>

</div>

<div class="col-md-4 mb-3">

<label>

Photo

</label>

<input
type="file"
name="photo"
accept=".jpg,.jpeg,.png"
class="form-control">

</div>

<div class="col-md-4 mb-3">

<label>

ID Card

</label>

<input
type="file"
name="id_card"
accept=".jpg,.jpeg,.png,.pdf"
class="form-control">

</div>

<div class="col-md-4 mb-3">

<label>

Document

</label>

<input
type="file"
name="document"
accept=".pdf"
class="form-control">

</div>

<div class="col-md-12 mb-3">

<label>

Note

</label>

<textarea
name="note"
class="form-control"></textarea>

</div>

</div>

<button class="btn btn-primary">

Save Party

</button>

</form>

</div>

</div>

</div>

@endsection