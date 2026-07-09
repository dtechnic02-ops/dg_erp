@extends('company.layout')

@section('content')

<div class="container-fluid">

<div class="page-title">

<h3>

Income Entries

</h3>

<div>

<a

href="{{ route('company.income.print',request()->query()) }}"

target="_blank"

class="erp-btn btn-green"

>

Print

</a>

<a

href="{{ route('company.income.create') }}"

class="erp-btn btn-blue"

>

Add Income

</a>

</div>

</div>

<div class="card-box">

<form

method="GET"

class="filter-row mb-3"

>

<input

name="search"

value="{{ request('search') }}"

placeholder="Income Title"

class="erp-input"

>

<input

type="date"

name="from_date"

value="{{ request('from_date') }}"

class="erp-input"

>

<input

type="date"

name="to_date"

value="{{ request('to_date') }}"

class="erp-input"

>

<select

name="financial_year"

class="erp-input"

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

<button

class="erp-btn btn-gray"

>

Filter

</button>

</form>

<div class="table-responsive">

<table class="erp-table">

<thead>

<tr>

<th>

Income No

</th>

<th>

Title

</th>

<th>

Account

</th>

<th>

Amount

</th>

<th>

Date

</th>

<th>

FY

</th>

<th>

Action

</th>

</tr>

</thead>

<tbody>

@forelse($incomes as $income)

<tr>

<td>

{{ $income->income_no }}

</td>

<td>

{{ $income->title }}

</td>

<td>

{{ $income->account->account_name ?? '' }}

</td>

<td>

{{ number_format($income->amount,2) }}

</td>

<td>

{{ $income->income_date }}

</td>

<td>

{{ $income->financialYear->name ?? '-' }}

</td>

<td>

<div class="action-box">

<a

href="{{ route('company.income.show',$income->id) }}"

class="erp-btn btn-gray"

>

View

</a>

<a

href="{{ route('company.income.edit',$income->id) }}"

class="erp-btn btn-blue"

>

Edit

</a>

<form

method="POST"

action="{{ route('company.income.delete',$income->id) }}"

>

@csrf

<button

onclick="return confirm('Delete Income?')"

class="erp-btn btn-red"

>

Delete

</button>

</form>

</div>

</td>

</tr>

@empty

<tr>

<td colspan="7">

No Income Found

</td>

</tr>

@endforelse

</tbody>

</table>

</div>

<div class="mt-3">

{{ $incomes->links() }}

</div>

</div>

</div>

@endsection
