@extends('company.layout')

@section('content')

<div class="container-fluid">

<div class="d-flex justify-content-between mb-3">

<h4>

Party Accounts

</h4>

<a
href="{{ route(
'company.party-account.create'
) }}"
class="btn btn-primary">

Create Party

</a>

</div>

<div class="card">

<div class="card-body table-responsive">

<table class="table table-bordered">

<thead>

<tr>

<th>

Account No

</th>

<th>

Name

</th>

<th>

Type

</th>

<th>

Balance

</th>

<th>

Action

</th>

</tr>

</thead>

<tbody>

@forelse($parties as $party)

<tr>

<td>

{{ $party->account_no }}

</td>

<td>

{{ $party->name }}

</td>

<td>

{{ ucfirst(
$party->type
) }}

</td>

<td>

{{ number_format(
$party->current_balance,
2
) }}

</td>

<td>

<a
href="{{ route(
'company.party-account.show',
$party->id
) }}"
class="btn btn-dark btn-sm">

View

</a>

</td>

</tr>

@empty

<tr>

<td colspan="5">

No party accounts

</td>

</tr>

@endforelse

</tbody>

</table>

{{ $parties->links() }}

</div>

</div>

</div>

@endsection