@extends('company.layout')

@push('styles')

<link

rel="stylesheet"

href="{{ asset('assets/company/css/form.css') }}">

@endpush

@section('content')

<div class="page-title">

<h2>Accounts</h2>

<div>

<button

type="button"

class="erp-btn btn-green"

data-bs-toggle="modal"

data-bs-target="#createAccountModal">

Add Account

</button>

<a

href="{{ route('company.accounts.print') }}"

target="_blank"

class="erp-btn btn-blue">

Print

</a>

</div>

</div>

@if(session('success'))

<div

class="alert-success"

id="success-alert"

>

{{ session('success') }}

</div>


<script>

setTimeout(

function(){

let el =

document.getElementById(

'success-alert'

);

if(el){

el.remove();

}

},

3000

);

</script>

@endif

@if($errors->any())

<div class="alert alert-danger">

{{ $errors->first() }}

</div>

@endif

<div class="card-box">

<form method="GET">

<div class="action-box">

<input

type="text"

name="search"

value="{{ request('search') }}"

placeholder="Search Accounts"

class="erp-input"

>

<button

type="submit"

class="erp-btn btn-blue"

>

Search

</button>

</div>

</form>

</div>

<div class="card-box table-responsive">

<table class="erp-table">

<thead>

<tr>

<th>Image</th>
<th>Type</th>
<th>Bank</th>

<th>Name</th>

<th>Account No</th>

<th>Balance</th>

<th>Status</th>

<th>Action</th>

</tr>

</thead>

<tbody>

@forelse($accounts as $account)

<tr>

<td>

@if($account->image_path)

<img

src="{{ asset($account->image_path) }}"

class="erp-image"

>

@endif

</td>
<td>

{{ $account->account_type }}

</td>

<td>

{{ $account->bank_name }}

</td>

<td>

{{ $account->account_name }}

</td>

<td>

{{ $account->account_no }}

</td>

<td>

{{ number_format(

$account->current_balance,

2

) }}

</td>

<td>

{{ $account->status }}

</td>

<td>

<div class="action-box">

<a
href="{{ route('company.accounts.show', $account->id) }}"
class="erp-btn btn-green">

View

</a>

<a
href="#"
class="erp-btn btn-blue"
data-bs-toggle="modal"
data-bs-target="#editAccount{{ $account->id }}"
onclick="return false;">

Edit

</a>

<form
method="POST"
action="{{ route('company.accounts.delete',$account->id) }}"
onsubmit="return confirm('Delete Account ?')">

@csrf

<button class="erp-btn btn-red">

Delete

</button>

</form>

</div>


</td>

</tr>

<div

class="modal fade"

id="editAccount{{ $account->id }}"

tabindex="-1"

>

<div class="modal-dialog modal-xl">

<div class="modal-content">

<form

method="POST"

action="{{ route(

'company.accounts.update',

$account->id

) }}"

enctype="multipart/form-data"

>

@csrf

<div class="modal-header">

<h5>

Edit Account

</h5>


<button

type="button"

class="btn-close"

data-bs-dismiss="modal"

>

</button>

</div>

<div class="modal-body">

<div class="form-card">

<div class="form-grid">

<div class="form-group">

<label class="form-label">

Account Type

</label>

<select

name="account_type"

class="form-input"

required>

<option

value="Cash"

{{ $account->account_type=='Cash' ? 'selected':'' }}

>

Cash

</option>


<option

value="Bank"

{{ $account->account_type=='Bank' ? 'selected':'' }}

>

Bank

</option>


<option

value="Wallet"

{{ $account->account_type=='Wallet' ? 'selected':'' }}

>

Wallet

</option>


<option

value="ATM"

{{ $account->account_type=='ATM' ? 'selected':'' }}

>

ATM

</option>

</select>

</div>

<div class="form-group">

<label class="form-label">

Bank Name

</label>

<input

name="bank_name"

class="form-input"

value="{{ $account->bank_name }}">

</div>

<div class="form-group">

<label class="form-label">

Account Name

</label>

<input

name="account_name"

class="form-input"

value="{{ $account->account_name }}"

required>

</div>

<div class="form-group">

<label class="form-label">

Branch

</label>

<input

name="branch"

class="form-input"

value="{{ $account->branch }}">

</div>

<div class="form-group">

<label class="form-label">

Account Number

</label>

<input

name="account_no"

class="form-input"

value="{{ $account->account_no }}">

</div>

<div class="form-group">

<label class="form-label">

IBAN

</label>

<input

name="iban"

class="form-input"

value="{{ $account->iban }}">

</div>

<div class="form-group">

<label class="form-label">

Swift Code

</label>

<input

name="swift_code"

class="form-input"

value="{{ $account->swift_code }}">

</div>

<div class="form-group">

<label class="form-label">

Currency

</label>

<select

name="currency"

class="form-input"

>

<option

value="AED"

{{ $account->currency=='AED' ? 'selected':'' }}

>

AED

</option>


<option

value="USD"

{{ $account->currency=='USD' ? 'selected':'' }}

>

USD

</option>


<option

value="NPR"

{{ $account->currency=='NPR' ? 'selected':'' }}

>

NPR

</option>


<option

value="INR"

{{ $account->currency=='INR' ? 'selected':'' }}

>

INR

</option>


<option

value="EUR"

{{ $account->currency=='EUR' ? 'selected':'' }}

>

EUR

</option>


<option

value="GBP"

{{ $account->currency=='GBP' ? 'selected':'' }}

>

GBP

</option>

</select>

</div>

<div class="form-group">

<label class="form-label">

Opening Balance

</label>

<input

type="number"

step="0.01"

name="opening_balance"

class="form-input"

value="{{ $account->opening_balance }}">

</div>

<div class="form-group">

<label class="form-label">

Status

</label>

<select

name="status"

class="form-input">

<option

value="active"

{{

$account->status=='active'

? 'selected'

:''

}}

>

Active

</option>

<option

value="inactive"

{{

$account->status=='inactive'

? 'selected'

:''

}}

>

Inactive

</option>

</select>

</div>

<div class="form-group">

<label class="form-label">

Image

</label>

<input

type="file"

name="image_path"

class="form-input">

</div>

<div class="form-group">

<label class="form-label">

Preview

</label>

@if($account->image_path)

<img

src="{{ asset(

$account->image_path

) }}"

class="form-image">

@endif

</div>

<div class="form-group form-full">

<label class="form-label">

Note

</label>

<textarea

name="note"

class="form-input"

>{{ $account->note }}</textarea>

</div>

</div>

</div>

</div>

<div class="modal-footer">

<button

type="submit"

class="erp-btn btn-red"

>

Update

</button>

</div>

</form>

</div>

</div>

</div>

@empty

<tr>

<td colspan="7">

No Accounts Found

</td>

</tr>

@endforelse

</tbody>

</table>

</div>

<div>

{{ $accounts->links() }}

</div>

@include('company.accounts.form')

@endsection
