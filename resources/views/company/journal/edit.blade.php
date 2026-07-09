@extends('company.layout')

@section('content')

<div class="container">

<div class="card">

<div class="card-header">

<h4>

Edit Journal Entry

</h4>

</div>

<form
method="POST"
action="{{ route(
'company.journal.update',
$journal->id
) }}"
enctype="multipart/form-data">

@csrf

<div class="card-body">

<div class="row">

<div class="col-md-4">

<label>

Journal No

</label>

<input
readonly
name="journal_no"
value="{{ old(
'journal_no',
$journal->journal_no
) }}"
class="form-control">

</div>

<div class="col-md-4">

<label>

Financial Year

</label>

<input
class="form-control"
value="{{ $journal->financialYear->name ?? '-' }}"
readonly>

</div>

<div class="col-md-4">

<label>

Date

</label>

<input
required
type="date"
name="journal_date"
value="{{ old(
'journal_date',
$journal->journal_date
) }}"
class="form-control">

</div>

<div class="col-md-4 mt-3">

<label>

Attachment

</label>

<input
type="file"
name="attachment"
class="form-control">

@if($journal->attachment)

small class="text-success">

Current attachment exists

</small>

@endif

</div>

</div>

<hr>

<table
class="table table-bordered"
id="journalTable">

<thead>

<tr>

<th>Account</th>

<th>Type</th>

<th>Amount</th>

<th>Note</th>

<th>Action</th>

</tr>

</thead>

<tbody>

@foreach($journal->items as $item)

<tr>

<td>

<select
name="account_id[]"
class="form-select"
required>

@foreach($accounts as $account)

<option
value="{{ $account->id }}"
{{ $item->account_id==$account->id ? 'selected':'' }}>

{{ $account->account_name }}

</option>

@endforeach

</select>

</td>

<td>

<select
name="type[]"
class="form-select">

<option
value="debit"
{{ $item->type=='debit' ? 'selected':'' }}>

Debit

</option>

<option
value="credit"
{{ $item->type=='credit' ? 'selected':'' }}>

Credit

</option>

</select>

</td>

<td>

<input
required
step="0.01"
type="number"
name="amount[]"
value="{{ $item->amount }}"
class="form-control amount">

</td>

<td>

<input
name="row_note[]"
value="{{ $item->note }}"
class="form-control">

</td>

<td>

<button
type="button"
class="btn btn-danger removeRow">

×

</button>

</td>

</tr>

@endforeach

</tbody>

</table>

<button
type="button"
id="addRow"
class="btn btn-dark">

Add Row

</button>

<div class="mt-4">

<textarea
name="note"
class="form-control">{{ $journal->note }}</textarea>

</div>

</div>

<div class="card-footer">

<button
class="btn btn-primary">

Update Journal

</button>

</div>

</form>

</div>

</div>

<script>

document.getElementById(
'addRow'
).addEventListener(
'click',
function(){

let row=
document.querySelector(
'#journalTable tbody tr'
).cloneNode(true);

row.querySelectorAll(
'input'
).forEach(
i=>i.value=''
);

document.querySelector(
'#journalTable tbody'
).appendChild(row);

});

document.addEventListener(
'click',
function(e){

if(
e.target.classList.contains(
'removeRow'
)
){

if(
document.querySelectorAll(
'#journalTable tbody tr'
).length>1
){

e.target.closest(
'tr'
).remove();

}

}

});

</script>

@endsection
