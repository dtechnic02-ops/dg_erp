<!-- CREATE MODAL -->

<div

class="modal fade"

id="createAccountModal"

tabindex="-1"

>

<div class="modal-dialog modal-xl">

<div class="modal-content">

<form

method="POST"

action="{{ route('company.accounts.store') }}"

enctype="multipart/form-data"

>

@csrf

<div class="modal-header">

<h5>Add Account</h5>

<button

type="button"

class="btn-close"

data-bs-dismiss="modal"

> </button>

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

required

>

<option value="">

Select Type

</option>

<option value="Cash">

Cash

</option>

<option value="Bank">

Bank

</option>

<option value="Wallet">

Wallet

</option>

<option value="ATM">

ATM

</option>

</select>

</div>

<div class="form-group">

<label class="form-label">

Bank Name

</label>

<input

type="text"

name="bank_name"

class="form-input"

>

</div>

<div class="form-group">

<label class="form-label">

Account Name

</label>

<input

type="text"

name="account_name"

class="form-input"

required

>

</div>

<div class="form-group">

<label class="form-label">

Branch

</label>

<input

type="text"

name="branch"

class="form-input"

>

</div>

<div class="form-group">

<label class="form-label">

Account Number

</label>

<input

type="text"

name="account_no"

class="form-input"

>

</div>

<div class="form-group">

<label class="form-label">

IBAN

</label>

<input

type="text"

name="iban"

class="form-input"

>

</div>

<div class="form-group">

<label class="form-label">

Swift Code

</label>

<input

type="text"

name="swift_code"

class="form-input"

>

</div>

<div class="form-group">

<label class="form-label">

Currency

</label>

<select

name="currency"

class="form-input"

>

<option value="AED" selected>

AED

</option>

<option value="USD">

USD

</option>

<option value="NPR">

NPR

</option>

<option value="INR">

INR

</option>

<option value="EUR">

EUR

</option>

<option value="GBP">

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

value="0"

>

</div>

<div class="form-group">

<label class="form-label">

Image

</label>

<input

type="file"

name="image_path"

class="form-input"

>

</div>

<div class="form-group">

<label class="form-label">

Status

</label>

<select

name="status"

class="form-input"

>

<option value="active">

Active

</option>

<option value="inactive">

Inactive

</option>

</select>

</div>

<div class="form-group form-full">

<label class="form-label">

Note

</label>

<textarea

name="note"

class="form-input"

></textarea>

</div>

</div>

</div>

</div>

<div class="modal-footer">

<button

type="button"

class="erp-btn btn-gray"

data-bs-dismiss="modal"

>

Close

</button>

<button

class="erp-btn btn-green"

>

Save Account

</button>

</div>

</form>

</div>

</div>

</div>
