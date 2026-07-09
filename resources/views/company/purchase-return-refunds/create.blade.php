@extends('company.layout')

@section('content')

<div class="container-fluid">

    {{-- PAGE HEADER --}}

    <div class="
        d-flex
        justify-content-between
        align-items-center
        mb-4
    ">

        <div>

            <h4 class="mb-1">

                Purchase Return Refund

            </h4>

            <small class="text-muted">

                Receive supplier refund payment

            </small>

        </div>

        {{-- BACK --}}

        <a href="{{ route(
                'company.purchase-return.index'
            ) }}"
           class="
                btn
                btn-dark
            ">

            <i class="
                fa-solid
                fa-arrow-left
            "></i>

            Back

        </a>

    </div>

    {{-- TOP SUMMARY --}}

    <div class="row mb-3">

        {{-- RETURN TOTAL --}}

        <div class="col-md-4">

            <div class="
                card
                border-0
                shadow-sm
                bg-danger
                text-white
            ">

                <div class="card-body">

                    <small>

                        Return Total

                    </small>

                    <h4 class="mb-0">

                        {{ number_format(
                            $return->grand_total,
                            2
                        ) }}

                    </h4>

                </div>

            </div>

        </div>

        {{-- REFUNDED --}}

        <div class="col-md-4">

            <div class="
                card
                border-0
                shadow-sm
                bg-success
                text-white
            ">

                <div class="card-body">

                    <small>

                        Total Refunded

                    </small>

                    <h4 class="mb-0">

                        {{ number_format(
                            $totalRefunded,
                            2
                        ) }}

                    </h4>

                </div>

            </div>

        </div>

        {{-- REMAINING --}}

        <div class="col-md-4">

            <div class="
                card
                border-0
                shadow-sm
                bg-warning
                text-dark
            ">

                <div class="card-body">

                    <small>

                        Remaining Refund

                    </small>

                    <h4 class="mb-0">

                        {{ number_format(
                            $remainingRefund,
                            2
                        ) }}

                    </h4>

                </div>

            </div>

        </div>

    </div>

    {{-- REFUND FORM --}}

    <div class="card border-0 shadow-sm">

        <div class="card-body">

            <form action="{{ route(
        'company.purchase-return-refunds.store'
    ) }}"
    method="POST"
    enctype="multipart/form-data">

                @csrf

                <input type="hidden"
                       name="purchase_return_id"
                       value="{{ $return->id }}">

                <div class="row g-3">

                    {{-- RETURN NO --}}

                    <div class="col-md-4">

                        <label class="form-label">

                            Return Invoice

                        </label>

                        <input type="text"
                               class="form-control"
                               value="{{ $return->return_no }}"
                               readonly>

                    </div>

                    {{-- SUPPLIER --}}

                    <div class="col-md-4">

                        <label class="form-label">

                            Supplier

                        </label>

                        <input type="text"
                               class="form-control"
                               value="{{ $return->supplier->name ?? '-' }}"
                               readonly>

                    </div>

                    {{-- DATE --}}

                    <div class="col-md-4">

                        <label class="form-label">

                            Refund Date

                        </label>

                        <input type="date"
                               name="refund_date"
                               class="form-control"
                               value="{{ now()->format('Y-m-d') }}"
                               required>

                    </div>

                    {{-- ACCOUNT --}}

                    <div class="col-md-6">

                        <label class="form-label">

                            Refund Account

                        </label>

                        <select name="account_id"
                                class="form-select"
                                required>

                            <option value="">

                                Select Account

                            </option>

                            @foreach($accounts as $account)

                                <option
                                    value="{{ $account->id }}">

                                    {{ $account->account_name }}

                                    -

                                    Balance:

                                    {{ number_format(
                                        $account->current_balance,
                                        2
                                    ) }}

                                </option>

                            @endforeach

                        </select>

                    </div>
                    {{-- PAYMENT METHOD --}}

<div class="col-md-6">

    <label class="form-label">

        Payment Method

    </label>

    <select
        name="payment_method"
        class="form-select"
        required>

        <option value="">

            Select Method

        </option>

        <option value="Cash">

            Cash

        </option>

        <option value="Bank">

            Bank

        </option>

        <option value="Cheque">

            Cheque

        </option>

        <option value="Online">

            Online Transfer

        </option>

    </select>

</div>

                    {{-- AMOUNT --}}

                    <div class="col-md-6">

                        <label class="form-label">

                            Refund Amount

                        </label>

                        <input type="number"
                               step="0.01"
                               min="0.01"

                               max="{{ $remainingRefund }}"

                               name="amount"

                               id="refundAmount"

                               class="form-control"

                               required>

                        <small class="text-danger">

                            Max:
                            {{ number_format(
                                $remainingRefund,
                                2
                            ) }}

                        </small>

                    </div>
                    {{-- ATTACHMENT --}}

<div class="col-md-12">

    <label class="form-label">

        Attachment

    </label>

    <input
        type="file"
        name="attachment"
        class="form-control"
        accept=".pdf,.jpg,.jpeg,.png">

    <small class="text-muted">

        Optional (PDF / JPG / PNG)

    </small>

</div>
                    {{-- NOTE --}}

                    <div class="col-md-12">

                        <label class="form-label">

                            Note

                        </label>

                        <textarea
                            name="note"
                            class="form-control"
                            rows="3"></textarea>

                    </div>

                </div>

                {{-- BUTTON --}}

                <div class="mt-4">

                    <button type="submit"
                            class="
                                btn
                                btn-success
                            ">

                        <i class="
                            fa-solid
                            fa-money-bill
                        "></i>

                        Receive Refund

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

{{-- VALIDATION --}}

<script>

document.getElementById(
    'refundAmount'
)
.addEventListener(
    'input',
    function(){

    let max =
        parseFloat(
            this.max
        ) || 0;

    let value =
        parseFloat(
            this.value
        ) || 0;

    if (value > max)
    {
        this.value = max;

        alert(
            'Refund exceeds remaining amount.'
        );
    }

});

</script>

@endsection