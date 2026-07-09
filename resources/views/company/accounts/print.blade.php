<!DOCTYPE html>
<html>
<head>

<title>Accounts Print</title>

<meta charset="utf-8">

<style>

body{
font-family:Arial,sans-serif;
font-size:12px;
color:#222;
}

.print-table{
width:100%;
border-collapse:collapse;
margin-top:15px;
}

.print-table th,
.print-table td{
border:1px solid #ddd;
padding:8px;
}

.print-table th{
background:#f3f4f6;
}

.text-right{
text-align:right;
}

.no-print{
margin-bottom:15px;
}

@media print{

.no-print{
display:none;
}

}

</style>

</head>

<body>

@include('company.partials.print-header')

<div class="no-print">

<button onclick="window.print()">

Print

</button>

</div>

<h2>

Account List

</h2>


<table class="print-table">

<thead>

<tr>

<th>Type</th>

<th>Name</th>

<th>Provider</th>

<th>Account</th>

<th>Currency</th>

<th>Balance</th>

<th>Status</th>

</tr>

</thead>

<tbody>

@foreach($accounts as $a)

<tr>

<td>{{ ucfirst($a->account_type) }}</td>

<td>{{ $a->account_name }}</td>

<td>{{ $a->bank_name }}</td>

<td>{{ $a->account_no }}</td>

<td>{{ $a->currency }}</td>

<td class="text-right">

{{ number_format($a->current_balance,2) }}

</td>

<td>

{{ ucfirst($a->status) }}

</td>

</tr>

@endforeach

</tbody>

</table>


@include('company.partials.print-footer')


<script>

window.onload=function(){

window.print();

}

</script>


</body>

</html>
