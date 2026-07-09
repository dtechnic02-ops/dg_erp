@extends('company.layout')

@section('content')

<style>


.dashboard-top{

display:flex;
justify-content:space-between;
align-items:center;

margin-bottom:15px;

}

.top-info{

display:grid;

grid-template-columns:
repeat(5,1fr);

gap:10px;

margin-bottom:15px;

}

.info-box{

background:

linear-gradient(
90deg,
#111827,
#172554
);

color:white;

padding:14px;

border-radius:10px;

font-size:13px;

font-weight:600;

}

.stats-grid{

display:grid;

grid-template-columns:
repeat(5,1fr);

gap:8px;

margin-bottom:12px;

}

.stat-card{

padding:10px;

min-height:62px;

border-radius:8px;

font-size:12px;

}

.stat-title{

font-size:12px;

opacity:.9;

}

.stat-value{

font-size:18px;

margin-top:4px;

}

.stat-card:nth-child(1){background:#2563eb;}
.stat-card:nth-child(2){background:#16a34a;}
.stat-card:nth-child(3){background:#f59e0b;}
.stat-card:nth-child(4){background:#9333ea;}
.stat-card:nth-child(5){background:#0891b2;}
.stat-card:nth-child(6){background:#ec4899;}
.stat-card:nth-child(7){background:#22c55e;}
.stat-card:nth-child(8){background:#ef4444;}
.stat-card:nth-child(9){background:#22c55e;}
.stat-card:nth-child(10){background:#02c55e;}

.dashboard-row{

display:grid;

grid-template-columns:
1fr 1fr;

gap:10px;

margin-bottom:10px;

}

.dashboard-row-3{

display:grid;

grid-template-columns:
1fr 1fr 1fr;

gap:10px;

margin-bottom:10px;

}

.box{

background:white;

border-radius:10px;

padding:15px;

box-shadow:

0 1px 3px rgba(0,0,0,.08);

min-height:150px;

}

.box h6{

margin-bottom:12px;

font-weight:700;

}

.quick-grid{

display:grid;

grid-template-columns:
1fr 1fr;

gap:8px;

}

.quick-grid a{

padding:10px;

border-radius:8px;

text-align:center;

color:white;

text-decoration:none;

font-size:13px;

font-weight:600;

}

.q1{background:#2563eb;}
.q2{background:#16a34a;}
.q3{background:#9333ea;}
.q4{background:#ef4444;}

.table-sm td{

font-size:12px;

}
.low-stock-box{

border-left:

5px solid red;

}

@media(max-width:992px){

/* Cards */

.top-info{

grid-template-columns:
repeat(3,1fr);

gap:10px;

}

.stats-grid{

grid-template-columns:
repeat(3,1fr);

gap:10px;

}

.stat-value{

font-size:25px;

font-weight:700;

}

/* Dashboard boxes */

.box h6{

font-size:16px;

}

.box{

font-size:14px;

}

/* Tables */

.table td,
.table th{

font-size:14px;

}

/* Buttons */

.erp-btn{

font-size:15px;

}

/* Mobile top */

.mobile-top{

font-size:14px;

}

}
</style>
<div class="dashboard-top">

<h3>

Dashboard

</h3>

<div>

<a class="btn btn-primary">

+ Sale

</a>

<a class="btn btn-success">

+ Purchase

</a>

</div>

</div>



<div class="top-info">

<div class="info-box">

👤

{{ auth()->user()->name }}

</div>



<div class="info-box">

🏢

{{ auth()->user()->company->company_name }}

</div>



<div class="info-box">

🟢 Online Staff

:

{{ $data['staff'] }}

</div>



<div class="info-box">

💰 Total Wallet

<br>

{{ number_format($data['cash']+$data['bank'],2) }}

</div>



<div class="info-box">

📅

{{ now()->format('d M Y') }}

</div>

</div>

<div class="stats-grid">

<div class="stat-card">

<div class="stat-title">

📦 Products

</div>

<div class="stat-value">

{{ $data['products'] }}

</div>

</div>


<div class="stat-card">

<div class="stat-title">

🧾 Sales

</div>

<div class="stat-value">

{{ $data['sales'] }}

</div>

</div>


<div class="stat-card">

<div class="stat-title">

🛒 Purchases

</div>

<div class="stat-value">

{{ $data['purchases'] }}

</div>

</div>


<div class="stat-card">

<div class="stat-title">

👥 Customers

</div>

<div class="stat-value">

{{ $data['customers'] }}

</div>

</div>


<div class="stat-card">

<div class="stat-title">

💵 Cash Wallet

</div>

<div class="stat-value">

{{ number_format($data['cash'],2) }}

</div>

</div>


<div class="stat-card">

<div class="stat-title">

🏦 Bank Wallet

</div>

<div class="stat-value">

{{ number_format($data['bank'],2) }}

</div>

</div>




<div class="stat-card">

<div class="stat-title">

📊 Stock Items

</div>

<div class="stat-value">

{{ $data['stock_items'] }}

</div>

</div>
<div class="stat-card">

<div class="stat-title">

💳 Customer Due

</div>

<div class="stat-value">

{{ number_format($data['customer_due'],2) }}

</div>

</div>


<div class="stat-card">

<div class="stat-title">

📄 Supplier Due

</div>

<div class="stat-value">

{{ number_format($data['supplier_due'],2) }}

</div>

</div>

</div>


<div class="dashboard-row">

<div class="box">

<h6>Sales Chart</h6>

<small>

{{ $salesChart->count() }}

days data

</small>

</div>



<div class="box">

<h6>Purchase Chart</h6>

<small>

{{ $purchaseChart->count() }}

days data

</small>

</div>

</div>



<div class="dashboard-row">

<div class="box">

<h6>

Recent Sales

</h6>

<table class="table table-sm">

@foreach($recentSales as $sale)

<tr>

<td>

{{ $sale->invoice_no }}

</td>

<td>

{{ number_format($sale->grand_total,2) }}

</td>

</tr>

@endforeach

</table>

</div>



<div class="box">

<h6>

Recent Purchases

</h6>

<table class="table table-sm">

@foreach($recentPurchases as $purchase)

<tr>

<td>

{{ $purchase->invoice_no }}

</td>

<td>

{{ number_format($purchase->grand_total,2) }}

</td>

</tr>

@endforeach

</table>

</div>

</div>



<div class="dashboard-row-3">



<div class="box low-stock-box">

<h6>

Low Stock

</h6>

@foreach($lowStock as $item)

<div>

{{ $item->name }}

-

{{ $item->current_stock }}

</div>

@endforeach

</div>



<div class="box">

<h6>

Staff Activity

</h6>

@foreach($staffActivity as $staff)

<div>

{{ $staff->name }}

<br>

<small>

{{ $staff->last_seen }}

</small>

</div>

<hr>

@endforeach

</div>



<div class="box">

<h6>

Quick Actions

</h6>

<div class="quick-grid">

<a class="q1">

New Sale

</a>

<a class="q2">

Add Product

</a>

<a class="q3">

Customer

</a>

<a class="q4">

Expense

</a>

</div>

</div>

</div>

@endsection