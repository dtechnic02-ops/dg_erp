@extends('company.layout')

@section('title','Contra List')

@section('content')

<div class="card">

    <div class="card-header d-flex justify-content-between align-items-center">

        <h5 class="mb-0">
            Contra List
        </h5>

        <div>

            <a
                href="{{ route('company.contra.create') }}"
                class="btn btn-primary"
            >
                Add Contra
            </a>

            <form
                action="{{ route('company.contra.print') }}"
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
                    name="from_account_id"
                    value="{{ request('from_account_id') }}"
                >

                <input
                    type="hidden"
                    name="to_account_id"
                    value="{{ request('to_account_id') }}"
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
                    class="btn btn-dark"
                >
                    Print
                </button>

            </form>

        </div>

    </div>

    <div class="card-body">

        <form method="GET">

            <div class="row">

                <div class="col-md-3 mb-2">

                    <select
                        name="financial_year_id"
                        class="form-control"
                    >

                        <option value="">
                            All Financial Years
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

                <div class="col-md-2 mb-2">

                    <select
                        name="from_account_id"
                        class="form-control"
                    >

                        <option value="">
                            From Account
                        </option>

                        @foreach($accounts as $account)

                        <option
                            value="{{ $account->id }}"
                            {{ request('from_account_id') == $account->id ? 'selected' : '' }}
                        >
                            {{ $account->account_name }}
                        </option>

                        @endforeach

                    </select>

                </div>

                <div class="col-md-2 mb-2">

                    <select
                        name="to_account_id"
                        class="form-control"
                    >

                        <option value="">
                            To Account
                        </option>

                        @foreach($accounts as $account)

                        <option
                            value="{{ $account->id }}"
                            {{ request('to_account_id') == $account->id ? 'selected' : '' }}
                        >
                            {{ $account->account_name }}
                        </option>

                        @endforeach

                    </select>

                </div>

                <div class="col-md-2 mb-2">

                    <input
                        type="date"
                        name="start_date"
                        class="form-control"
                        value="{{ request('start_date') }}"
                    >

                </div>

                <div class="col-md-2 mb-2">

                    <input
                        type="date"
                        name="end_date"
                        class="form-control"
                        value="{{ request('end_date') }}"
                    >

                </div>

                <div class="col-md-1 mb-2">

                    <button
                        type="submit"
                        class="btn btn-success w-100"
                    >
                        Go
                    </button>

                </div>

            </div>

        </form>

        <div class="table-responsive">

            <table class="table table-bordered table-striped">

                <thead>

                    <tr>

                        <th>#</th>

                        <th>Date</th>

                        <th>Voucher No</th>

                        <th>From Account</th>

                        <th>To Account</th>

                        <th>Amount</th>

                        <th width="180">
                            Action
                        </th>

                    </tr>

                </thead>

                <tbody>

                    @forelse($contras as $contra)

                    <tr>

                        <td>
                            {{ $loop->iteration }}
                        </td>

                        <td>
                            {{ $contra->contra_date }}
                        </td>

                        <td>
                            {{ $contra->contra_no }}
                        </td>

                        <td>
                            {{ $contra->fromAccount->account_name ?? '' }}
                        </td>

                        <td>
                            {{ $contra->toAccount->account_name ?? '' }}
                        </td>

                        <td>
                            {{ number_format($contra->amount,2) }}
                        </td>

                        <td>

                            <a
                                href="{{ route('company.contra.show',$contra->id) }}"
                                class="btn btn-info btn-sm"
                            >
                                View
                            </a>

                            <a
                                href="{{ route('company.contra.edit',$contra->id) }}"
                                class="btn btn-warning btn-sm"
                            >
                                Edit
                            </a>

                            <form
                                action="{{ route('company.contra.delete',$contra->id) }}"
                                method="POST"
                                class="d-inline"
                            >

                                @csrf

                                <button
                                    type="submit"
                                    class="btn btn-danger btn-sm"
                                    onclick="return confirm('Delete this record?')"
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

        {{ $contras->links() }}

    </div>

</div>

@endsection