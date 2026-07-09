@extends('company.layout')

@section('content')

<div class="d-flex justify-content-between mb-3">

<h4>

Units

</h4>

<button

type="button"

class="btn btn-primary"

data-bs-toggle="modal"

data-bs-target="#addModal"

>

+ Add Unit

</button>

</div>



@if(session('success'))

<div class="alert alert-success">

{{ session('success') }}

</div>

@endif



<div class="card">

<div class="card-body">

<table class="table table-hover align-middle">

<thead>

<tr>



<th>Name</th>

<th>Short</th>

<th width="150">

Action

</th>

</tr>

</thead>

<tbody>

@forelse($units as $unit)

<tr>



<td>

{{ $unit->name }}

</td>

<td>

{{ $unit->short_name }}

</td>

<td>

<button

type="button"

class="btn btn-sm btn-warning editBtn"

data-id="{{ $unit->id }}"

data-name="{{ $unit->name }}"

data-short="{{ $unit->short_name }}"

>

Edit

</button>

<form

action="{{route('company.units.destroy',$unit->id)}}"

method="POST"

class="d-inline"

>

@csrf

<button

type="submit"

class="btn btn-sm btn-danger"

onclick="return confirm('Delete this unit?')"

>

Delete

</button>

</form>




</td>

</tr>

@empty

<tr>

<td colspan="4">

No Data

</td>

</tr>

@endforelse

</tbody>

</table>


{{ $units->links() }}

</div>

</div>




<!-- ADD MODAL -->

<div
class="modal fade"
id="addModal"
>

<div class="modal-dialog">


<form
method="POST"
action="{{ route('company.units.store') }}"
class="modal-content"
id="unitForm"
>

@csrf



<div class="modal-header">

<h5>

Add Unit

</h5>

</div>


<div class="modal-body">

<input

name="name"

class="form-control mb-2"

placeholder="Unit Name"

required

>


<input

name="short_name"

class="form-control"

placeholder="Short Name"

required

>

</div>


<div class="modal-footer">
<button
type="submit"
class="btn btn-success"
id="unitSubmitBtn"
>

Add Unit

</button>

</div>

</form>

</div>

</div>




<!-- EDIT MODAL -->

<div
class="modal fade"
id="editModal"
>

<div class="modal-dialog">

<form
method="POST"
id="editForm"
class="modal-content"
>

@csrf



<div class="modal-header">

<h5>

Edit Unit

</h5>

</div>


<div class="modal-body">

<input

id="editName"

name="name"

class="form-control mb-2"

required

>


<input

id="editShort"

name="short_name"

class="form-control"

required

>

</div>


<div class="modal-footer">

<button

type="submit"

class="btn btn-primary"

>

Save

</button>

</div>

</form>

</div>

</div>


<script>

document
.querySelectorAll('.editBtn')

.forEach(btn=>{

btn.onclick=()=>{

document
.getElementById(
'editName'
)
.value=
btn.dataset.name;


document
.getElementById(
'editShort'
)
.value=
btn.dataset.short;


document
.getElementById(
'editForm'
)
.action=

'{{ url("/company/units/update") }}/'

+btn.dataset.id;


new bootstrap.Modal(

document.getElementById(
'editModal'
)

).show();

};

});

</script>


@endsection

