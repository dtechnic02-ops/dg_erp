@extends('company.layout')

@section('content')

<div class="container">

<div class="card">

<div class="card-header">

Party Details

</div>

<div class="card-body">

<table class="table table-bordered">

<tr>

<th>

Account No

</th>

<td>

{{ $party->account_no }}

</td>

</tr>

<tr>

<th>

Name

</th>

<td>

{{ $party->name }}

</td>

</tr>

<tr>

<th>

Phone

</th>

<td>

{{ $party->phone }}

</td>

</tr>

<tr>

<th>

Type

</th>

<td>

{{ ucfirst(
$party->type
) }}

</td>

</tr>

<tr>

<th>

Current Balance

</th>

<td>

{{ number_format(
$party->current_balance,
2
) }}

</td>

</tr>

<tr>

<th>

Address

</th>

<td>

{{ $party->address }}

</td>

</tr>

@if($party->photo)

<tr>

<th>

Photo

</th>

<td>

<img
width="120"
src="{{ asset(
$party->photo
) }}">

</td>

</tr>

@endif

</table>

</div>

</div>

</div>

@endsection