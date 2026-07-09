
@extends('company.layout')

@push('styles')

<link
rel="stylesheet"
href="{{ asset(
'assets/company/css/form.css'
) }}">

@endpush

@section('content')

<h3 class="page-title">

{{ $product ? '✏️ Edit Product' : '📦 Add Product' }}

</h3>


@if(session('success'))

<div class="alert-success">

{{ session('success') }}

</div>

@endif


@if($errors->any())

<div class="alert alert-danger">

{{ $errors->first() }}

</div>

@endif



<div class="form-card">

<form

method="POST"

enctype="multipart/form-data"

action="{{

$product

? route(
'company.products.update',
$product->id
)

: route(
'company.products.store'
)

}}">

@csrf

@if($product)

@method('PUT')

@endif



<div class="form-grid">

<!-- NAME -->

<div class="form-group">

<label class="form-label">

Product Name

</label>

<input

name="name"

class="form-input"

value="{{ $product->name ?? '' }}"

required>

</div>



<!-- BARCODE -->

<div class="form-group">

<label class="form-label">

Barcode

</label>

<input

name="barcode"

class="form-input"

value="{{ $product->barcode ?? '' }}">

</div>



<!-- STATUS -->

<div class="form-group">

<label class="form-label">

Status

</label>

<select

name="status"

class="form-input">

<option
value="active"

{{ ($product->status ?? '')=='active' ? 'selected':'' }}>

Active

</option>

<option
value="inactive"

{{ ($product->status ?? '')=='inactive' ? 'selected':'' }}>

Inactive

</option>

</select>

</div>



<!-- CATEGORY -->

<div class="form-number">

<label class="form-label">

     Category    .

</label>

<select

name="category_id"

class="form-input1">

@foreach($categories as $cat)

<option

value="{{ $cat->id }}"

{{

isset($product)

&&

$product->category_id==$cat->id

? 'selected'

: ''

}}>

{{ $cat->name }}

</option>

@endforeach

</select>

</div>



<!-- UNIT -->

<div class="form-number">

<label class="form-label">

Unit       .

</label>

<select

name="unit_id"

class="form-input1">

@foreach($units as $u)

<option

value="{{ $u->id }}"

{{

isset($product)

&&

$product->unit_id==$u->id

? 'selected'

: ''

}}>

{{ $u->name }}

</option>

@endforeach

</select>

</div>



<!-- OPENING -->

<div class="form-number">

<label class="form-label">

Opening Stock

</label>

<input

type="number"

step="0.01"

name="opening_stock"

class="form-input1"

value="{{ $product->opening_stock ?? 0 }}">

</div>



<!-- COST -->

<div class="form-number">

<label class="form-label">

Cost Price

</label>

<input

type="number"

name="cost_price"

class="form-input1"

value="{{ $product->cost_price ?? '' }}">

</div>



<!-- RETAIL -->

<div class="form-number">

<label class="form-label">

Retail Price

</label>

<input

type="number"

name="retail_price"

class="form-input1"

value="{{ $product->retail_price ?? '' }}">

</div>



<!-- WHOLESALE -->

<div class="form-number">

<label class="form-label">

Wholesale Price

</label>

<input

type="number"

name="wholesale_price"

class="form-input1"

value="{{ $product->wholesale_price ?? '' }}">

</div>



<!-- ALERT -->

<div class="form-number">

<label class="form-label">

Stock Alert

</label>

<input

type="number"

name="stock_alert"

class="form-input1"

value="{{ $product->stock_alert ?? '' }}">

</div>



<!-- IMAGE -->

<div class="form-number">

<label class="form-label">

Image

</label>

<input

type="file"

name="image"

class="form-input">

</div>



<!-- PREVIEW -->

<div class="form-number">

<label class="form-label">

Preview

</label>

@if(isset($product) && $product->image)

<img

src="{{ asset($product->image) }}"

class="form-image">

@endif

</div>



<!-- DESCRIPTION -->

<div class="form-group form-full">

<label class="form-label">

Description

</label>

<textarea

name="description"

rows="3"

class="form-input">{{ $product->description ?? '' }}</textarea>

</div>


</div>



<div class="form-actions">

<button

class="erp-btn btn-blue">

{{ $product ? 'Update' : 'Save' }}

</button>


<a

href="{{ route(
'company.products.index'
) }}"

class="erp-btn btn-gray">

Back

</a>

</div>



</form>

</div>

@endsection
```
