
<!DOCTYPE html>
<html>

<head>

<meta charset="utf-8">

<title>

Purchase Invoice

</title>

<style>

@page{

size:A4 portrait;

margin:10mm;

}

*{

margin:0;

padding:0;

box-sizing:border-box;

}

body{

font-family:Arial,sans-serif;

background:#f2f5fb;

color:#111;

}

.invoice{

width:190mm;

margin:auto;

background:white;

padding:22px;

border-radius:14px;

border:1px solid #dfe7f3;

}

/* ===================
HEADER
=================== */

.header{

display:grid;

grid-template-columns:

32% 36% 32%;

gap:15px;

align-items:start;

margin-bottom:22px;

}

.side-box{

padding:12px;

min-height:240px;

}

.side-box h4{

font-size:18px;

color:#1d4ed8;

margin-bottom:18px;

}

.side-box h2{

font-size:24px;

margin-bottom:14px;

}

.side-box p{

font-size:13px;

line-height:1.7;

margin-bottom:8px;

word-break:break-word;

}

/* ===================
CENTER
=================== */

.center-box{

display:flex;

flex-direction:column;

align-items:center;

text-align:center;

padding:10px;

border-left:

1px solid #dbe3f4;

border-right:

1px solid #dbe3f4;

}

.center-logo{

max-width:140px;

max-height:90px;

object-fit:contain;

margin-bottom:12px;

}

.center-company{

font-size:24px;

font-weight:700;

margin-bottom:6px;

}

.center-pan{

font-size:13px;

margin-bottom:18px;

color:#666;

}

.invoice-title{

font-size:58px;

font-weight:800;

line-height:.9;

}

.invoice-title span{

color:#2563eb;

}

/* ===================
INFO STRIP
=================== */

.info-strip{

display:grid;

grid-template-columns:

repeat(4,1fr);

border:1px solid #dbe3f4;

border-radius:12px;

padding:18px;

margin-bottom:25px;

text-align:center;

gap:10px;

}

.left-info,
.right-info{

display:flex;

flex-direction:column;

justify-content:center;

}

.info-strip strong{

font-size:16px;

}

/* ===================
TABLE
=================== */

.table-box{

border:1px solid #dbe3f4;

border-radius:12px;

overflow:hidden;

margin-bottom:25px;

}

table{

width:100%;

border-collapse:collapse;

table-layout:fixed;

}

thead{

background:#0b2d78;

color:white;

}

th{

padding:13px;

font-size:13px;

}

td{

padding:13px;

font-size:13px;

border-bottom:

1px solid #edf1f7;

}

tbody tr:nth-child(even){

background:#fafbfd;

}

/* ===================
BOTTOM
=================== */

.bottom{

display:grid;

grid-template-columns:

60% 40%;

border:1px solid #dbe3f4;

border-radius:12px;

overflow:hidden;

}

.left-bottom{

padding:24px;

}

.left-bottom h3{

margin-bottom:10px;

}

.right-bottom{

padding:24px;

border-left:

1px solid #dbe3f4;

}

.total-row{

display:flex;

justify-content:space-between;

padding:10px 0;

border-bottom:

1px solid #eee;

}

.grand{

font-size:24px;

font-weight:bold;

color:#2563eb;

}

.due{

font-size:28px;

font-weight:bold;

color:#dc2626;

}

/* ===================
FOOTER
=================== */

.footer{

display:flex;

justify-content:space-between;

align-items:flex-end;

margin-top:35px;

}

.signature{

text-align:center;

}

.line{

width:220px;

border-top:1px solid black;

margin-top:70px;

padding-top:8px;

}

/* PRINT */

@media print{

body{

background:white;

}

.invoice{

width:100%;

border:none;

box-shadow:none;

}

}


</style>


<body>

<div class="invoice">

<!-- =======================
HEADER
======================= -->

<div class="header">

<!-- COMPANY -->


<div class="side-box company-box">



<h2>
{{ $invoice->company->company_name ?? '-' }}

</h2>

<p>

Mobile :

{{ $invoice->company->mobile ?? '-' }}

</p>

<p>

Telephone :

{{ $invoice->company->telephone ?? '-' }}

</p>

<p>

Email :

{{ $invoice->company->email ?? '-' }}

</p>

<p>

Address :

{{ $invoice->company->address ?? '-' }}

</p>

<p>

Country :

{{ $invoice->company->country ?? '-' }}

</p>

<p>

PAN :

{{ $invoice->company->pan_number ?? '-' }}

</p>

<p>

VAT :

{{ $invoice->company->vat_number ?? '-' }}

</p>

<p>

Website :

{{ $invoice->company->website ?? '-' }}

</p>

</div>



<!-- CENTER -->

<div class="center-box">

@if(
$invoice->company &&
$invoice->company->logo_path
)

<img

class="center-logo"

src="{{

asset(

'companies/' .

$invoice->company->id .

'/' .

$invoice->company->logo_path

)

}}"

>

@endif


<div class="invoice-title">

PURCHASE

<br>

<span>

INVOICE

</span>

</div>

</div>


<!-- SUPPLIER -->

<div class="side-box supplier-box">

<h4>

SUPPLIER DETAILS

</h4>

<h2>

{{ $invoice->supplier->name }}

</h2>

<p>

{{ $invoice->supplier->phone }}

</p>

<p>

{{ $invoice->supplier->email }}

</p>

<p>

{{ $invoice->supplier->address }}

</p>

<p>

TAX :

{{

$invoice->supplier->pan_no
?? $invoice->supplier->tax_no
?? '-'

}}

</p>

</div>

</div>


<!-- =======================
INFO STRIP
======================= -->

<div class="info-strip">

<div class="left-info">

<div>

Purchase No

</div>

<strong>

{{ $invoice->invoice_no }}

</strong>

</div>


<div class="left-info">

<div>

Purchase Date

</div>

<strong>

{{ $invoice->purchase_date }}

</strong>

</div>


<div class="right-info">

<div>

Status

</div>

<strong>

{{ strtoupper($invoice->payment_status) }}

</strong>

</div>


<div class="right-info">

<div>

Payment Term

</div>

<strong>

Due on Receipt

</strong>

</div>

</div>


<!-- =======================
TABLE
======================= -->

<div class="table-box">

<table>

<thead>

<tr>

<th>#</th>

<th>Product</th>

<th>Qty</th>

<th>Rate</th>

<th>VAT %</th>

<th>VAT Amount</th>

<th>Total</th>

</tr>

</thead>

<tbody>

@foreach($invoice->items as $item)

<tr>

<td>

{{ $loop->iteration }}

</td>

<td>

{{ $item->product->name ?? '-' }}

</td>

<td>

{{ $item->quantity }}

</td>

<td>

{{ number_format($item->unit_price,2) }}

</td>

<td>

{{ $item->vat_rate }}

</td>

<td>

{{ number_format($item->vat_amount,2) }}

</td>

<td>

{{ number_format($item->total,2) }}

</td>

</tr>

@endforeach

</tbody>

</table>

</div>


<!-- =======================
BOTTOM
======================= -->

<div class="bottom">

<div class="left-bottom">

<h3>

AMOUNT IN WORDS

</h3>

<p>

{{ $invoice->note }}

</p>

<h3>

NOTE

</h3>

<p>

{{ $invoice->note }}

</p>

</div>


<div class="right-bottom">

<div>

Subtotal

{{ number_format($invoice->subtotal,2) }}

</div>

<div>

VAT

{{ number_format($invoice->total_vat,2) }}

</div>

<div>

Discount

{{ number_format($invoice->discount,2) }}

</div>

<div>

Grand Total

{{ number_format($invoice->grand_total,2) }}

</div>

<div>

Paid

{{ number_format($invoice->paid_amount,2) }}

</div>

<div>

Due

{{ number_format($invoice->due_amount,2) }}

</div>

</div>

</div>


<!-- FOOTER -->

<div class="footer">

<div>

Thank you for your business!

</div>

<div>

Authorized Signature

</div>

</div>

</div>

</body>

