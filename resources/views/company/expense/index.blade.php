@extends('company.layout')

@section('content')
<div class="card-header">

    <div class="d-flex justify-content-between align-items-center">

        <h5 class="mb-0">
            Expense List
        </h5>

        <div>

            <a
                href="{{ route('company.expense.create') }}"
                class="btn btn-primary btn-sm"
            >
                Add Expense
            </a>

            <form
                action="{{ route('company.expense.print') }}"
                method="GET"
                target="_blank"
                class="d-inline"
            >

                <input
                    type="hidden"
                    name="financial_year_id"
                    value="{{ request('financial_year_id') }}"
                >

                <input
                    type="hidden"
                    name="expense_category_id"
                    value="{{ request('expense_category_id') }}"
                >

                <input
                    type="hidden"
                    name="account_id"
                    value="{{ request('account_id') }}"
                >

                <input
                    type="hidden"
                    name="start_date"
                    value="{{ request('start_date') }}"
                >

                <input
                    type="hidden"
                    name="end_date"
                    value="{{ request('end_date') }}"
                >

                <button
                    type="submit"
                    class="btn btn-secondary btn-sm"
                >
                    Print
                </button>

            </form>

        </div>

    </div>

</div>

    <div class="card-body">

        <form method="GET">

            <div class="row g-2">

                <div class="col-md-2">

                    <select
                        name="financial_year_id"
                        class="form-select form-select-sm"
                    >

                        <option value="">
                            All FY
                        </option>

                        @foreach($financialYears as $fy)

                        <option
                            value="{{ $fy->id }}"
                            {{ request('financial_year_id') == $fy->id ? 'selected' : '' }}
                        >
                            {{ $fy->name }}
                        </option>

                        @endforeach

                    </select>

                </div>

                <div class="col-md-2">

                    <select
                        name="expense_category_id"
                        class="form-select form-select-sm"
                    >

                        <option value="">
                            All Categories
                        </option>

                        @foreach($categories as $category)

                        <option
                            value="{{ $category->id }}"
                            {{ request('expense_category_id') == $category->id ? 'selected' : '' }}
                        >
                            {{ $category->name }}
                        </option>

                        @endforeach

                    </select>

                </div>

                <div class="col-md-2">

                    <select
                        name="account_id"
                        class="form-select form-select-sm"
                    >

                        <option value="">
                            All Accounts
                        </option>

                        @foreach($accounts as $account)

                        <option
                            value="{{ $account->id }}"
                            {{ request('account_id') == $account->id ? 'selected' : '' }}
                        >
                            {{ $account->account_name }}
                        </option>

                        @endforeach

                    </select>

                </div>

                <div class="col-md-2">

                    <input
                        type="date"
                        name="start_date"
                        value="{{ request('start_date') }}"
                        class="form-control form-control-sm"
                    >

                </div>

                <div class="col-md-2">

                    <input
                        type="date"
                        name="end_date"
                        value="{{ request('end_date') }}"
                        class="form-control form-control-sm"
                    >

                </div>

                <div class="col-md-2">

                    <button
                        type="submit"
                        class="btn btn-primary btn-sm w-100"
                    >
                        Filter
                    </button>

                </div>

            </div>

        </form>

        <hr>

        <div class="table-responsive">

            <table class="table table-bordered table-sm align-middle">

                <thead>

                    <tr>

                        <th>#</th>

                        <th>Voucher No</th>

                        <th>Date</th>

                        <th>Category</th>

                        <th>Account</th>

                        <th class="text-end">
                            Amount
                        </th>

                        <th>Action</th>

                    </tr>

                </thead>

                <tbody>

                    @forelse($expenses as $expense)

                    <tr>

                        <td>
                            {{ $loop->iteration }}
                        </td>

                        <td>
                            {{ $expense->expense_no }}
                        </td>

                        <td>
                            {{ $expense->expense_date }}
                        </td>

                        <td>
                            {{ $expense->category->name ?? '' }}
                        </td>

                        <td>
                            {{ $expense->account->account_name ?? '' }}
                        </td>

                        <td class="text-end">
                            {{ number_format($expense->amount,2) }}
                        </td>

                       <td class="text-nowrap">

    <a
        href="{{ route('company.expense.show',$expense->id) }}"
        class="btn btn-info btn-sm"
    >
        View
    </a>

    <a
        href="{{ route('company.expense.edit',$expense->id) }}"
        class="btn btn-warning btn-sm"
    >
        Edit
    </a>

    <form
        action="{{ route('company.expense.delete',$expense->id) }}"
        method="POST"
        class="d-inline"
        onsubmit="return confirm('Are you sure?')"
    >

        @csrf

        @method('DELETE')

        <button
            type="submit"
            class="btn btn-danger btn-sm"
        >
            Delete
        </button>

    </form>

</td>

                    </tr>

                    @empty

                    <tr>

                        <td
                            colspan="7"
                            class="text-center"
                        >
                            No Data Found
                        </td>

                    </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        {{ $expenses->links() }}

    </div>

</div>


</div>



@endsection