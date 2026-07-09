@extends('company.layout')

@section('content')

<div class="container-fluid">

<div class="page-title">



Salary sheets



<div>>
<form method="GET"
      action="{{ route('company.salary-sheets.index') }}">

<div class="row mb-3">

    <div class="col-md-3">
        <input type="text"
               name="search"
               value="{{ request('search') }}"
               class="form-control"
               placeholder="Employee Search">
    </div>

    <div class="col-md-2">
        <input type="month"
               name="salary_month"
               value="{{ request('salary_month') }}"
               class="form-control">
    </div>

    <div class="col-md-2">
        <select name="status"
                class="form-control">

            <option value="">
                All Status
            </option>

            <option value="paid"
                {{ request('status') == 'paid' ? 'selected' : '' }}>
                Paid
            </option>

            <option value="unpaid"
                {{ request('status') == 'unpaid' ? 'selected' : '' }}>
                Unpaid
            </option>

        </select>
    </div>

    <div class="col-md-5">

        <button type="submit"
                class="btn btn-primary">
            Filter
        </button>

        <a href="{{ route('company.salary-sheets.index') }}"
           class="btn btn-secondary">
            Reset
        </a>

        <a href="{{ route('company.salary-sheets.create') }}"
           class="btn btn-success">
            Add Salary
        </a>

        <a href="{{ route('company.salary-sheets.print') }}"
           class="btn btn-dark">
            Print
        </a>

    </div>

</div>

</form>
<table class="table table-bordered">
    <thead>
        <tr>
            <th>#</th>
            <th>Employee</th>
            <th>Month</th>
            <th>Basic Salary</th>
            <th>Net Salary</th>
            <th>Status</th>
            <th width="180">Action</th>
        </tr>
    </thead>

    <tbody>

    @forelse($salarySheets as $salary)

        <tr>

            <td>{{ $salary->id }}</td>

            <td>
                {{ $salary->employee->first_name }}
            </td>

            <td>
                {{ $salary->salary_month }}
            </td>

            <td>
                {{ number_format($salary->basic_salary,2) }}
            </td>

            <td>
                {{ number_format($salary->net_salary,2) }}
            </td>

            <td>

                @if($salary->status=='paid')

                    <span class="badge bg-success">
                        Paid
                    </span>

                @else

                    <span class="badge bg-warning">
                        Unpaid
                    </span>

                @endif

            </td>

            <td>

                <a
    href="{{ route('company.salary-sheets.show',$salary->id) }}"
    class="btn btn-info btn-sm"
>
    Show
</a>

                <a
    href="{{ route('company.salary-sheets.edit',$salary->id) }}"
    class="btn btn-primary btn-sm"
>
    Edit
</a>
                <form
    action="{{ route('company.salary-sheets.delete',$salary->id) }}"
    method="POST"
    style="display:inline"
>

    @csrf

    <button
        type="submit"
        class="btn btn-danger btn-sm"
        onclick="return confirm('Delete this Salary Sheet?')"
    >
        Delete
    </button>

</form>

            </td>

        </tr>

    @empty

        <tr>
            <td colspan="7">
                No Data Found
            </td>
        </tr>

    @endforelse

    </tbody>

</table>
</tbody>
@endsection

{{ $salarySheets->links() }}