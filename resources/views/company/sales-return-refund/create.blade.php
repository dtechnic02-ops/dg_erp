@extends('company.layout')

@section('content')
@if(session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
@endif

<div class="container-fluid">

    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-3">

        <div>

            <h4 class="mb-0">
                Create Sales Return Refund
            </h4>

            <small class="text-muted">
                Refund returned sales amount
            </small>

        </div>

        <a
            href="{{ route('company.sales-return-refund.index') }}"
            class="btn btn-light border"
        >
            Back
        </a>

    </div>

    <form
        method="POST"
        action="{{ route('company.sales-return-refund.store') }}"
    >

        @csrf

        <input
            type="hidden"
            name="sales_return_id"
            value="{{ $return->id }}"
        >

        <div class="row">

            {{-- LEFT --}}
            <div class="col-md-8">

                <div class="card border-0 shadow-sm mb-3">

                    <div class="card-body">

                        <div class="row g-3">

                            <div class="col-md-6">

                                <label class="form-label">
                                    Refund No
                                </label>

                                <input
                                    type="text"
                                    name="refund_no"
                                    class="form-control"
                                    value="{{ $refundNo }}"
                                    readonly
                                >

                            </div>

                            <div class="col-md-6">

                                <label class="form-label">
                                    Refund Date
                                </label>

                                <input
                                    type="date"
                                    name="refund_date"
                                    class="form-control"
                                    value="{{ date('Y-m-d') }}"
                                    required
                                >

                            </div>

                            <div class="col-md-6">

                                <label class="form-label">
                                    Customer
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    value="{{ $return->customer->name ?? '-' }}"
                                    readonly
                                >

                            </div>

                            <div class="col-md-6">

                                <label class="form-label">
                                    Return No
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    value="{{ $return->return_no }}"
                                    readonly
                                >

                            </div>

                            <div class="col-md-6">

                                <label class="form-label">
                                    Refund Account
                                </label>

                                <select
                                    name="account_id"
                                    class="form-select"
                                    required
                                >

                                    <option value="">
                                        Select Account
                                    </option>

                                    @foreach($accounts as $account)

                                        <option
                                            value="{{ $account->id }}"
                                        >
                                            {{ $account->account_name }}
                                            -
                                            {{ number_format($account->current_balance, 2) }}
                                        </option>

                                    @endforeach

                                </select>

                            </div>

                            <div class="col-md-6">

                                <label class="form-label">
                                    Refund Amount
                                </label>

                                <input
                                    type="number"
                                    step="0.01"
                                    name="refund_amount"
                                    class="form-control"
                                    value="{{ $remainingAmount }}"
                                    max="{{ $remainingAmount }}"
                                    required
                                >

                            </div>

                            <div class="col-md-12">

                                <label class="form-label">
                                    Note
                                </label>

                                <textarea
                                    name="note"
                                    class="form-control"
                                    rows="4"
                                ></textarea>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

            {{-- RIGHT --}}
            <div class="col-md-4">

                <div class="card border-0 shadow-sm">

                    <div class="card-body">

                        <h6 class="mb-3">
                            Refund Summary
                        </h6>

                        <table class="table">

                            <tr>

                                <th>
                                    Return Total
                                </th>

                                <td class="text-end">

                                    {{ number_format($return->grand_total, 2) }}

                                </td>

                            </tr>

                            <tr>

                                <th>
                                    Already Refunded
                                </th>

                                <td class="text-end text-success">

                                    {{
                                        number_format(
                                            $return->refunds->sum('refund_amount'),
                                            2
                                        )
                                    }}

                                </td>

                            </tr>

                            <tr>

                                <th>
                                    Remaining
                                </th>

                                <td class="text-end text-danger">

                                    {{ number_format($remainingAmount, 2) }}

                                </td>

                            </tr>

                        </table>

                        <button
                            class="btn btn-success w-100"
                        >

                            Save Refund

                        </button>

                    </div>

                </div>

            </div>

        </div>

    </form>

</div>

@endsection