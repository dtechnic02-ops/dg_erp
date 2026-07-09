
@extends('company.layout')

@section('content')

@include(
'company.partials.print-style'
)

<div class="container-fluid">

<div id="printArea">


@include(
'company.partials.print-header-portrait'
)



<div class="
d-flex
justify-content-between
align-items-center
mb-4
print-hide
">

<h4>

Purchase Return Details

</h4>


<div class="d-flex gap-2">

<a
href="{{ route(
'company.purchase-return.index'
) }}"
class="btn btn-dark">

Back

</a>


<button
onclick="window.print()"
class="btn btn-primary">

Print

</button>

</div>

</div>



<div class="row mb-4">

<div class="col-md-4">

<strong>

Return No:

</strong>

{{ $return->return_no }}

</div>


<div class="col-md-4">

<strong>

Date:

</strong>

{{ $return->return_date }}

</div>


<div class="col-md-4">

<strong>

Supplier:

</strong>

{{ $return->supplier->name ?? 'N/A' }}

</div>

</div>




<div class="card border-0 shadow-sm">

<div class="card-body">


<div class="table-responsive">

<table class="table table-bordered">

<thead class="table-dark">

<tr>

<th>

Product

</th>

<th>

Qty

</th>

<th>

Price

</th>

<th>

VAT

</th>

<th>

Total

</th>

</tr>

</thead>


<tbody>

@foreach($return->items as $item)

<tr>

<td>

{{ $item->product->name ?? 'Deleted Product' }}

</td>

<td>

{{ $item->quantity }}

</td>

<td>

{{ number_format(
$item->unit_price,
2
) }}

</td>

<td>

{{ number_format(
$item->vat_amount,
2
) }}

</td>

<td>

{{ number_format(
$item->total_price,
2
) }}

</td>

</tr>

@endforeach

</tbody>

</table>

</div>




<div class="row mt-4">

<div class="col-md-4 offset-md-8">

<table class="table table-bordered">

<tr>

<th>

Subtotal

</th>

<td>

{{ number_format(
$return->subtotal,
2
) }}

</td>

</tr>


<tr>

<th>

VAT

</th>

<td>

{{ number_format(
$return->total_vat,
2
) }}

</td>

</tr>


<tr>

<th>

Grand Total

</th>

<td class="fw-bold text-danger">

{{ number_format(
$return->grand_total,
2
) }}

</td>

</tr>

</table>

</div>

</div>

</div>

</div>



@include(
'company.partials.print-footer-portrait'
)


</div>

</div>

@endsection

