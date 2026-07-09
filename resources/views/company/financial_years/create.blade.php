@extends('company.layout')

@section('content')

<h3>

Financial Years

</h3>

<a href="{{ route('company.financial-years.create') }}">

Add Financial Year

</a>

<table>

<tr>

<th>Name</th>

<th>Start</th>

<th>End</th>

<th>Status</th>

</tr>

@foreach($financialYears as $fy)

<tr>

<td>

{{ $fy->name }}

</td>

<td>

{{ $fy->start_date }}

</td>

<td>

{{ $fy->end_date }}

</td>

<td>

@if($fy->is_active)

Active

@else

Inactive

@endif

</td>

</tr>

@endforeach

</table>

@endsection