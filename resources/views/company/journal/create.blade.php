@extends('company.layout')

@section('content')

<div class="container">

<div class="card">

<div class="card-header">

<h4>

Create Journal Entry

</h4>

</div>

<form
method="POST"
action="{{ route(
'company.journal.store'
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
required
readonly
name="journal_no"
value="{{ old('journal_no',$journalNo) }}"
class="form-control">

</div>
<div class="col-md-4">

<label>

Financial Year

</label>

<input

class="form-control"

value="{{ $currentFY->name }}"

readonly

>

</div>

<div class="col-md-4">

<label>

Date

</label>

<input
required
type="date"
name="journal_date"
value="{{ old('journal_date',$currentFY->start_date) }}"
class="form-control">

</div>


<div class="col-md-4">

<label>

Attachment

</label>

<input
type="file"
name="attachment"
class="form-control">

</div>

</div>


<hr>


<div class="table-responsive">

<table
class="table table-bordered"
id="journalTable">

<thead>

<tr>

<th>

Account

</th>

<th>

Type

</th>

<th>

Amount

</th>

<th>

Note

</th>

<th>

Action

</th>

</tr>

</thead>

<tbody>

<tr>

<td>

<select
name="account_id[]"
class="form-select"
required>

<option value="">

Select

</option>

@foreach($accounts as $account)

<option value="{{ $account->id }}">

{{ $account->account_name }}

</option>

@endforeach

</select>

</td>


<td>

<select
name="type[]"
class="form-select">

<option value="debit">

Debit

</option>

<option value="credit">

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
class="form-control amount">

</td>


<td>

<input
name="row_note[]"
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
</div>

</tbody>

</table>


<button
type="button"
id="addRow"
class="btn btn-dark">

Add Row

</button>


<div class="row mt-4">

<div class="col-md-4">

<label>

Note
</label>

<textarea
name="note"
class="form-control">

</textarea>

</div>


<div class="col-md-4">

<div class="alert alert-success">

Debit Total:

<h4 id="debitTotal">

0.00

</h4>

</div>

</div>


<div class="col-md-4">

<div class="alert alert-danger">

Credit Total:

<h4 id="creditTotal">

0.00

</h4>

</div>

</div>

</div>

</div>


<div class="card-footer">

<button
class="btn btn-primary">

Save Journal

</button>

</div>

</form>

</div>

</div>



<script>

document

.getElementById(

'addRow'

)

.addEventListener(

'click',

function(){

let row=

document.querySelector(

'#journalTable tbody tr'

)

.cloneNode(

true

);

row.querySelectorAll(

'input'

)

.forEach(

i=>i.value=''

);


row.querySelectorAll(

'select'

)

.forEach(

s=>s.selectedIndex=0

);

document

.querySelector(

'#journalTable tbody'

)

.appendChild(

row

);

calculateTotals();

}

);


document

.addEventListener(

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

)

.length>1

){

e.target

.closest(

'tr'

)

.remove();

calculateTotals();

}

}

});


document

.addEventListener(

'input',

calculateTotals

);

document

.addEventListener(

'change',

calculateTotals

);


function calculateTotals(){

let debit=0;

let credit=0;

document

.querySelectorAll(

'#journalTable tbody tr'

)

.forEach(

row=>{

let type=

row.querySelector(

'select[name="type[]"]'

).value;

let amount=

parseFloat(

row.querySelector(

'.amount'

).value

)||0;

if(

type=='debit'

){

debit+=amount;

}else{

credit+=amount;

}

});

document

.getElementById(

'debitTotal'

)

.innerText=

debit.toFixed(2);

document

.getElementById(

'creditTotal'

)

.innerText=

credit.toFixed(2);

}

calculateTotals();

</script>

@endsection
