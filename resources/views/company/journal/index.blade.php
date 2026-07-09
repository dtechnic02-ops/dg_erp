@extends('company.layout')

@section('content')

<div class="container">

<div class="card">

<div class="card-header d-flex justify-content-between align-items-center">

<h4 class="mb-0">

Journal Entries

</h4>

<div>

<a
href="{{ route('company.journal.print',request()->query()) }}"
target="_blank"
class="btn btn-success"

>

Print

</a>

<a
href="{{ route('company.journal.create') }}"
class="btn btn-primary"

>

Add Journal

</a>

</div>

</div>

<div class="card-body">

<form
method="GET"
class="row g-2 align-items-end mb-3"
>

<div class="col-md-3">

<label>

Journal No

</label>

<input
name="search"
value="{{ request('search') }}"
placeholder="Journal No..."
class="form-control"

>

</div>

<div class="col-md-2">

<label>

Start Date

</label>

<input
type="date"
name="from_date"
value="{{ request('from_date') }}"
class="form-control"

>

</div>

<div class="col-md-2">

<label>

End Date

</label>

<input
type="date"
name="to_date"
value="{{ request('to_date') }}"
class="form-control"

>

</div>

<div class="col-md-2">

<label>

Financial Year

</label>

<select
name="financial_year"
class="form-select"

>

<option value="">

All Years

</option>

@foreach($financialYears as $fy)

<option
value="{{ $fy->id }}"
{{ request('financial_year')==$fy->id ? 'selected':'' }}
>

{{ $fy->name }}

</option>

@endforeach

</select>

</div>

<div class="col-md-2">

<button
class="btn btn-dark w-100"

>

Filter

</button>

</div>

</form>

<div class="table-responsive">

<table class="table table-bordered table-striped">

<thead>

<tr>

<th>

Journal No

</th>

<th>

Date

</th>

<th>

FY

</th>

<th>

Amount

</th>

<th>

Created

</th>

<th width="180">

Action

</th>

</tr>

</thead>

<tbody>

@forelse($journals as $journal)

<tr>

<td>

{{ $journal->journal_no }}

</td>

<td>

{{ $journal->journal_date }}

</td>

<td>

{{ $journal->financialYear->name ?? '-' }}

</td>

<td>

{{ number_format($journal->total_amount,2) }}

</td>

<td>

{{ $journal->created_at->format('d M Y') }}

</td>

<td>

<a
href="{{ route('company.journal.show',$journal->id) }}"
class="btn btn-info btn-sm"

>

View

</a>

<a
href="{{ route('company.journal.edit',$journal->id) }}"
class="btn btn-warning btn-sm"

>

Edit

</a>

<form
class="d-inline"
method="POST"
action="{{ route('company.journal.delete',$journal->id) }}"
>

@csrf

<button
onclick="return confirm('Delete?')"
class="btn btn-danger btn-sm"

>

Delete

</button>

</form>

</td>

</tr>

@empty

<tr>

<td colspan="6">

No Journal Found

</td>

</tr>

@endforelse

</tbody>

</table>

</div>

<div class="mt-3">

{{ $journals->links() }}

</div>

</div>

</div>

</div>

@endsection
