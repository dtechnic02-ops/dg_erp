@extends('company.layout')

@section('content')

<div class="page-title">

<h3>

📂 Product Categories

</h3>


<div class="d-flex gap-2 flex-wrap">

<form

method="GET"

action="{{ route(
'company.categories.index'
) }}"

class="d-flex gap-2">

<input

name="search"

value="{{ request('search') }}"

placeholder="Search"

class="erp-input">


<button

class="erp-btn btn-blue">

Search

</button>

</form>



<button

class="erp-btn btn-blue"

data-bs-toggle="modal"

data-bs-target="#categoryModal">

+ Add Category

</button>

</div>

</div>




<div class="card-box">

<div class="table-responsive">

<table class="erp-table">

<thead>

<tr>

<th>

Name

</th>

<th>

Description

</th>

<th width="180">

Action

</th>

</tr>

</thead>


<tbody>

@forelse($categories as $cat)

<tr>

<td>

{{ $cat->name }}

</td>

<td>

{{ $cat->description ?? '-' }}

</td>

<td>

<div class="action-box">


<button

class="erp-btn btn-green"

data-bs-toggle="modal"

data-bs-target="#editModal{{ $cat->id }}">

Edit

</button>



<form

method="POST"

action="{{ route(
'company.categories.delete',
$cat->id
) }}">

@csrf
<button

type="submit"

class="erp-btn btn-red"

onclick="return confirm('Delete Category?')"

>

Delete



</button>

</form>

</div>

</td>

</tr>

@empty

<tr>

<td colspan="3">

No Categories Found

</td>

</tr>

@endforelse

</tbody>

</table>

</div>


<div class="mt-3">

{{ $categories->links() }}

</div>

</div>




<!-- EDIT MODALS -->

@foreach($categories as $cat)

<div

class="modal fade"

id="editModal{{ $cat->id }}"

tabindex="-1">

<div class="modal-dialog">

<div class="modal-content">

<form

method="POST"

action="{{ route(
'company.categories.update',
$cat->id
) }}">

@csrf


<div class="modal-header">

<h5>

Edit Category

</h5>

<button

type="button"

class="btn-close"

data-bs-dismiss="modal">

</button>

</div>



<div class="modal-body">


<div class="form-group">

<label class="form-label">

Category Name

</label>

<input

name="name"

class="form-input"

value="{{ $cat->name }}"

required>

</div>


<div class="form-group mt-3">

<label class="form-label">

Description

</label>

<textarea

name="description"

class="form-input"

rows="3">{{ $cat->description }}</textarea>

</div>

</div>



<div class="modal-footer">

<button

type="button"

class="erp-btn btn-gray"

data-bs-dismiss="modal">

Close

</button>


<button

class="erp-btn btn-blue">

Update

</button>

</div>

</form>

</div>

</div>

</div>

@endforeach





<!-- ADD MODAL -->

<div

class="modal fade"

id="categoryModal"

tabindex="-1">

<div class="modal-dialog">

<div class="modal-content">

<form

method="POST"

id="categoryForm"

action="{{ route(
'company.categories.store'
) }}">

@csrf



<div class="modal-header">

<h5>

Add Category

</h5>

<button

type="button"

class="btn-close"

data-bs-dismiss="modal">

</button>

</div>



<div class="modal-body">


<div class="form-group">

<label class="form-label">

Category Name

</label>

<input

name="name"

class="form-input"

required>

</div>



<div class="form-group mt-3">

<label class="form-label">

Description

</label>

<textarea

name="description"

class="form-input"

rows="3"></textarea>

</div>

</div>



<div class="modal-footer">

<button

type="button"

class="erp-btn btn-gray"

data-bs-dismiss="modal">

Close

</button>


<button

type="submit"

id="categorySubmitBtn"

class="erp-btn btn-blue">

Save

</button>

</div>


</form>

</div>
</div>

<script>

document
.getElementById(
'categoryForm'
)

.addEventListener(

'submit',

function(){

const btn =

document.getElementById(
'categorySubmitBtn'
);

btn.disabled=true;

btn.innerText='Saving...';

}

);

</script>

@endsection