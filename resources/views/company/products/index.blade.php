@extends('company.layout')

@section('content')

<div class="page-title">

<h3>

📦 Product Management

</h3>

<div class="d-flex gap-2 flex-wrap">

<a
href="{{ route('company.products.create') }}"
class="erp-btn btn-blue">

➕ Add Product

</a>

<a
href="{{ route(
'company.products.export.excel',
request()->query()
) }}"
class="erp-btn btn-green">

Excel

</a>

<a
href="{{ route(
'company.products.export.pdf',
request()->query()
) }}"
class="erp-btn btn-blue">

PDF

</a>

</div>

</div>



<form
method="GET"
class="d-flex gap-2 flex-wrap mb-3">

<input
    type="text"
    name="search"
    class="form-control"
    value="{{ request('search') }}"
    placeholder="Search Name / Barcode">

<select
name="stock_filter"
class="erp-input"
style="max-width:170px">

<option value="">

All Stock

</option>

<option
value="out"
{{ request('stock_filter')=='out' ? 'selected':'' }}>

Out

</option>

<option
value="low"
{{ request('stock_filter')=='low' ? 'selected':'' }}>

Low

</option>

<option
value="available"
{{ request('stock_filter')=='available' ? 'selected':'' }}>

Available

</option>

</select>

<button
class="erp-btn btn-blue">

Filter

</button>

</form>



<div class="card-box">

<div class="table-responsive">

<table class="erp-table">

<thead>

<tr>

<th>Image</th>

<th>Name</th>

<th>Barcode</th>

<th>Cost</th>

<th>Retail</th>

<th>Wholesale</th>

<th>Stock</th>

<th>Status</th>

<th>Action</th>

</tr>

</thead>

<tbody>

@forelse($products as $p)

<tr>

<td>

<img
src="{{ $p->image
? asset($p->image)
: asset('no-image.png') }}"
class="erp-image">

</td>

<td>

{{ $p->name }}

</td>

<td>

{{ $p->barcode ?? '-' }}

</td>

<td>

{{ number_format(
$p->cost_price,
2
) }}

</td>

<td>

{{ number_format(
$p->retail_price,
2
) }}

</td>

<td>

{{ number_format(
$p->wholesale_price ?? 0,
2
) }}

</td>

<td>

@if($p->current_stock<=0)

<span class="stock stock-out">

Out

</span>

@elseif(
$p->stock_alert &&
$p->current_stock <= $p->stock_alert
)

<span class="stock stock-low">

{{ $p->current_stock }}

</span>

@else

<span class="stock stock-ok">

{{ $p->current_stock }}

</span>

@endif

</td>

<td>

{{ ucfirst($p->status) }}

</td>

<td>

<div class="action-box">

<a
href="{{ route(
'company.products.edit',
$p->id
) }}"
class="erp-btn btn-green">

Edit

</a>

<form
method="POST"
action="{{ route(
'company.products.destroy',
$p->id
) }}">

@csrf

@method('DELETE')

<button
class="erp-btn btn-red"

onclick="
return confirm(
'Delete Product?'
)
">

Delete

</button>

</form>

</div>

</td>

</tr>

@empty

<tr>

<td
colspan="9"
class="text-center p-4">

No Products Found

</td>

</tr>

@endforelse

</tbody>

</table>

</div>

</div>



<div class="mt-3">

{{ $products->links() }}

</div>

@endsection

