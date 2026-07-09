@extends('company.layout')

@push('styles')

<link
rel="stylesheet"
href="{{ asset(
    'assets/company/css/form.css'
) }}">

@endpush

@section('content')

<div class="card">

    <div class="card-header">

        <h5>
            Account Transactions
        </h5>

    </div>

    <div class="card-body">

        <form
            method="GET"
            action=""
        >

            <div class="row">

                <div class="col-md-4">

                    <input
                        type="text"
                        name="search"
                        class="form-control"
                        placeholder="Search Voucher No"
                        value="{{ request('search') }}"
                    >

                </div>

                <div class="col-md-4">

                    <select
                        name="account_id"
                        class="form-control"
                    >

                        <option value="">
                            All Accounts
                        </option>

                        @foreach($accounts as $account)

                            <option
                                value="{{ $account->id }}"
                                @selected(
                                    request('account_id') == $account->id
                                )
                            >

                                {{ $account->account_name }}

                            </option>

                        @endforeach

                    </select>

                </div>

                <div class="col-md-2">

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >

                        Search

                    </button>

                </div>

            </div>

        </form>

        <hr>

        <div class="table-responsive">

            <table class="table table-bordered table-striped">

                <thead>

                    <tr>

                        <th>
                            SN
                        </th>

                        <th>
                            Date
                        </th>

                        <th>
                            Voucher No
                        </th>

                        <th>
                            Account
                        </th>

                        <th>
                            Description
                        </th>

                        <th class="text-end">
                            Debit
                        </th>

                        <th class="text-end">
                            Credit
                        </th>

                        <th class="text-end">
                            Balance
                        </th>

                        <th>
                            Action
                        </th>

                    </tr>

                </thead>

                <tbody>

                    @forelse($transactions as $transaction)

                        <tr>

                            <td>

                                {{ $loop->iteration }}

                            </td>

                            <td>

                                {{ $transaction->transaction_date }}

                            </td>

                            <td>

                                {{ $transaction->voucher_no }}

                            </td>

                            <td>

                                {{ $transaction->account->account_name ?? '' }}

                            </td>

                            <td>

                                {{ $transaction->description }}

                            </td>

                            <td class="text-end">

                                {{ number_format(
                                    $transaction->debit,
                                    2
                                ) }}

                            </td>

                            <td class="text-end">

                                {{ number_format(
                                    $transaction->credit,
                                    2
                                ) }}

                            </td>

                            <td class="text-end">

                                {{ number_format(
                                    $transaction->balance,
                                    2
                                ) }}

                            </td>

                            <td>

                                <a
                                    href="{{ route(
    'company.account-transaction.show',
    $transaction->id
) }}"
                                    class="btn btn-info btn-sm"
                                >

                                    View

                                </a>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="9"
                                class="text-center"
                            >

                                No Data Found.

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        <div>

            {{ $transactions->links() }}

        </div>

    </div>

</div>

@endsection