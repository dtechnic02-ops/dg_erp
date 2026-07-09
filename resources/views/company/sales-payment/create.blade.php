@extends('company.layout')

@section('content')

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        {{ session('error') }}

        <button
            type="button"
            class="btn-close"
            data-bs-dismiss="alert">
        </button>
    </div>
@endif

<div class="container-fluid">

    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-3">

        <div>

            <h4 class="mb-0">
                Receive Sales Payment
            </h4>

            <small class="text-muted">
                Receive customer invoice payment
            </small>

        </div>

        <a
            href="{{ route('company.sales-payment.index') }}"
            class="btn btn-light border"
        >
            Back
        </a>

    </div>

    <form
        method="POST"
        action="{{ route('company.sales-payment.store') }}"
    >

        @csrf

        <input
            type="hidden"
            name="sales_invoice_id"
            value="{{ $invoice->id }}"
        >

        <div class="row">

            {{-- LEFT --}}
            <div class="col-md-8">

                <div class="card border-0 shadow-sm mb-3">

                    <div class="card-body">

                        <div class="row g-3">

                            <div class="col-md-6">

                                <label class="form-label">
                                    Payment No
                                </label>

                                <input
                                    type="text"
                                    name="payment_no"
                                    class="form-control"
                                    value="{{ $paymentNo }}"
                                    readonly
                                >

                            </div>

                            <div class="col-md-6">

                                <label class="form-label">
                                    Payment Date
                                </label>

                                <input
                                    type="date"
                                    name="payment_date"
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
                                    value="{{ $invoice->customer->name ?? '-' }}"
                                    readonly
                                >

                            </div>

                            <div class="col-md-6">

                                <label class="form-label">
                                    Invoice No
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    value="{{ $invoice->invoice_no }}"
                                    readonly
                                >

                            </div>

                            <div class="col-md-6">

                                <label class="form-label">
                                    Receive Account
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
                                    Payment Amount
                                </label>

                                <input
                                    type="number"
                                    step="0.01"
                                    name="paid_amount"
                                    class="form-control"
                                    value="{{ $remainingAmount }}"
                                    max="{{ $remainingAmount }}"
                                    required
                                >

                            </div>

                            <div class="col-md-6">

                                <label class="form-label">
                                    Payment Method
                                </label>

                                <select
                                    name="payment_method"
                                    class="form-select"
                                >

                                    <option value="">
                                        Select Method
                                    </option>

                                    <option value="Cash">
                                        Cash
                                    </option>

                                    <option value="Bank">
                                        Bank
                                    </option>

                                    <option value="Card">
                                        Card
                                    </option>

                                    <option value="Cheque">
                                        Cheque
                                    </option>

                                </select>

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
                            Payment Summary
                        </h6>

                        <table class="table">

                            <tr>

                                <th>
                                    Invoice Total
                                </th>

                                <td class="text-end">

                                    {{ number_format($invoice->grand_total, 2) }}

                                </td>

                            </tr>
<tr>

<th>
Already Paid
</th>

<td class="text-end text-success">

{{
number_format(
$totalPaid,
2
)
}}

</td>

</tr>

                            <tr>

                                <th>
                                    Remaining Due
                                </th>

                                <td class="text-end text-danger">

                                    {{ number_format($remainingAmount, 2) }}

                                </td>

                            </tr>

                        </table>

                        <button
                            class="btn btn-success w-100"
                        >

                            Save Payment

                        </button>

                    </div>

                </div>

            </div>

        </div>

    </form>

</div>

@endsection