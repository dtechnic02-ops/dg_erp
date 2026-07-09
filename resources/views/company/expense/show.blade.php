@extends('company.layout')

@section('content')

<div class="container-fluid">

<div class="card shadow-sm border-0">

<div class="card-header d-flex justify-content-between">

<h4>

Expense Details

</h4>

<button
onclick="window.print()"
class="btn btn-dark">

Print

</button>

</div>

<div class="card-body">

<table class="table table-bordered">

<tr>

<th>

Expense No

</th>

<td>

{{ $expense->expense_no }}

</td>

</tr>

<tr>

<th>

Date

</th>

<td>

{{ $expense->expense_date }}

</td>

</tr>

<tr>

<th>

Category

</th>

<td>

{{ $expense->category->name ?? '-' }}

</td>

</tr>

<tr>

<th>

Account

</th>

<td>

{{ $expense->account->account_name ?? '-' }}

</td>

</tr>

<tr>

<th>

Amount

</th>

<td>

{{ number_format($expense->amount,2) }}

</td>

</tr>

<tr>

<th>

Reference

</th>

<td>

{{ $expense->reference_no ?? '-' }}

</td>

</tr>

<tr>

<th>

Note

</th>

<td>

{{ $expense->note ?? '-' }}

</td>

</tr>
@if($expense->attachment)

<tr>

<th>

Attachment

</th>

<td>

<a
href="{{ asset($expense->attachment) }}"
target="_blank"
class="btn btn-sm btn-primary">

View Attachment

</a>

</td>

</tr>

@endif

</table>

</div>

</div>

</div>

@endsection