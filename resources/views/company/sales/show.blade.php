<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        Sales Invoice
    </title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">
<style>
body{
margin:0;
padding:10px;
background:#f3f4f6;
font-family:'Segoe UI',sans-serif;
font-size:13px;
}

.invoice-card{

max-width:1100px;

margin:auto;

background:#fff;

padding:20px;

border-radius:12px;

box-shadow:0 3px 10px rgba(0,0,0,.06);

}
.invoice-logo{

width:80px;

height:80px;

object-fit:contain;

display:block;

margin:0 auto 10px;

}

.invoice-top{

display:flex;

justify-content:space-between;

gap:20px;

margin-bottom:15px;

}

.company-box,
.customer-box{

width:30%;

}

.invoice-center{

width:40%;

text-align:center;

}

.company-title,
.customer-title{

font-size:13px;

font-weight:700;

color:#2563eb;

margin-bottom:8px;

}

.company-name,
.customer-name{

font-size:20px;

font-weight:700;

margin-bottom:8px;

}

.info-text{

font-size:12px;

margin-bottom:4px;

}

.invoice-heading{

font-size:42px;

font-weight:800;

line-height:1;

margin-top:5px;

}

.invoice-info{

border:1px solid #e5e7eb;

padding:12px;

border-radius:10px;

margin-bottom:15px;

}

.invoice-info td{

padding:6px;

font-size:12px;

}

.status-badge{

padding:3px 10px;

border-radius:20px;

font-size:11px;

background:#22c55e;

color:white;

}

.product-table{

width:100%;

border-collapse:collapse;

margin-top:10px;

}

.product-table th{

background:#071c57;

color:white;

padding:10px;

font-size:12px;

}

.product-table td{

padding:8px;

font-size:12px;

border-bottom:1px solid #ececec;

}

.total-section{

display:flex;

margin-top:15px;

gap:15px;

align-items:stretch;

}

.note-box{

flex:1;

border:1px solid #dbe2ea;

padding:15px;

border-radius:10px;

}

.total-box{

width:320px;

border:1px solid #dbe2ea;

border-radius:10px;

overflow:hidden;

}

.total-box table{

width:100%;

}

.total-box th{

padding:10px;

font-size:12px;

background:#fafafa;

}

.total-box td{

padding:10px;

text-align:right;

font-size:12px;

font-weight:700;

}

.signature-box{

margin-top:25px;

text-align:right;

}

.signature-line{

width:220px;

margin-left:auto;

border-top:2px solid #555;

margin-bottom:8px;

}

@media print{

body{

background:white;

padding:0;

}

.invoice-card{

box-shadow:none;

padding:10px;

}

}

</style>


</head>

 <body>

<div class="invoice-wrapper">

<button
onclick="printInvoice()"
class="btn btn-primary print-btn mb-3">

Print Invoice

</button>
<div class="invoice-wrapper">
<a href="{{ route('company.sales.index') }}"
class="btn btn-primary">

← Back To Sales

</a>

<div
class="invoice-card"
id="invoiceArea">

<div class="invoice-top">

<!-- COMPANY -->

<div class="company-box">

<div class="company-title">

COMPANY DETAILS

</div>

<div class="company-name">

{{ $invoice->company->company_name ?? '' }}

</div>

<div class="info-text">

📞 {{ $invoice->company->mobile ?? '' }}

</div>

<div class="info-text">

✉ {{ $invoice->company->email ?? '' }}

</div>

<div class="info-text">

📍 {{ $invoice->company->address ?? '' }}

</div>

<div class="info-text">

PAN:

{{ $invoice->company->pan_number ?? '' }}

</div>

</div>


<!-- CENTER -->

<div class="invoice-center">

@if(
$invoice->company &&
$invoice->company->logo_path
)

<img

src="{{ asset(

'companies/' .

$invoice->company->id .

'/' .

$invoice->company->logo_path

) }}"

class="invoice-logo"

alt="Logo">

@endif

<div class="invoice-heading">

SALES

<br>

INVOICE

</div>

</div>


<!-- CUSTOMER -->

<div class="customer-box text-end">

<div class="customer-title">

CUSTOMER DETAILS

</div>

<div class="customer-name">

{{ $invoice->customer->name ?? '' }}

</div>

<div class="info-text">

📞 {{ $invoice->customer->mobile ?? '' }}

</div>

<div class="info-text">

✉ {{ $invoice->customer->email ?? '' }}

</div>

<div class="info-text">

📍 {{ $invoice->customer->address ?? '' }}

</div>

</div>

</div>


<!-- INFO -->

<div class="invoice-info">

<table width="100%">

<tr>

<td>

<strong>

Invoice No

</strong>

</td>

<td>

{{ $invoice->invoice_no }}

</td>

<td>

<strong>

Status

</strong>

</td>

<td>

<span class="status-badge">

{{ ucfirst($invoice->payment_status) }}

</span>

</td>

</tr>


<tr>

<td>

<strong>

Date

</strong>

</td>

<td>

{{ $invoice->sale_date }}

</td>

<td>

<strong>

Term

</strong>

</td>

<td>

Due On Receipt

</td>

</tr>

</table>

</div>


<!-- PRODUCTS -->

<table class="product-table">

<thead>

<tr>

<th>#</th>

<th>Product</th>

<th>Qty</th>

<th>Rate</th>

<th>VAT%</th>

<th>VAT</th>

<th>Total</th>

</tr>

</thead>

<tbody>

@foreach($invoice->items as $key => $item)

<tr>

<td>

{{ $key+1 }}

</td>

<td>

{{ $item->product->name ?? '-' }}

@if($item->returned_qty > 0)

<br>

<small class="text-danger">

Returned:

{{ $item->returned_qty }}

</small>

@endif

</td>

<td>

{{ $item->quantity }}

</td>

<td>

{{ number_format($item->unit_price,2) }}

</td>

<td>

{{ number_format($item->vat_rate,2) }}%

</td>

<td>

{{ number_format($item->vat_amount,2) }}

</td>

<td>

{{ number_format($item->total_price,2) }}

</td>

</tr>

@endforeach

</tbody>

</table>


<!-- TOTAL AREA -->

<div class="total-section">

<div class="note-box">

<b>

Amount In Words

</b>

<br><br>

{{ numberToWords($invoice->grand_total) }}

<br><br>

<hr>

<b>

Note

</b>

<br>

बिक्री भएको सामान फिर्ता हुँदैन ।

कृपया सामान जाँच गरेर लिनुहोला।

</div>


<div class="total-box">

<table>

<tr>

<th>

Subtotal

</th>

<td>

{{ number_format($invoice->subtotal,2) }}

</td>

</tr>

<tr>

<th>

VAT

</th>

<td>

{{ number_format($invoice->total_vat,2) }}

</td>

</tr>

<tr>

<th>

Discount

</th>

<td>

{{ number_format($invoice->discount,2) }}

</td>

</tr>

<tr>

<th>

Grand Total

</th>

<td>

{{ number_format($invoice->grand_total,2) }}

</td>

</tr>

<tr>

<th>

Paid

</th>

<td>

{{ number_format($invoice->paid_amount,2) }}

</td>

</tr>

<tr>

<th>

Due

</th>

<td>

{{ number_format($invoice->due_amount,2) }}

</td>

</tr>

</table>

</div>

</div>


<div class="signature-box">

<div class="signature-line">

</div>

Authorized Signature

</div>

</div>

</div>


<script>

function printInvoice(){

let printContents=

document.getElementById(

'invoiceArea'

).innerHTML;

let originalContents=

document.body.innerHTML;

document.body.innerHTML=

printContents;

window.print();

document.body.innerHTML=

originalContents;

location.reload();

}

</script>

</body>

</html>