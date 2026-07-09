@extends('company.layout')

@section('content')

<div class="container-fluid">

    {{-- PAGE HEADER --}}

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h4 class="mb-1">

                Purchase Payment

            </h4>

            <small class="text-muted">

                Invoice :
                {{ $invoice->invoice_no }}

            </small>

        </div>

        <a href="{{ route(
            'company.purchases.show',
            $invoice->id
        ) }}"
           class="btn btn-secondary">

            Back

        </a>

    </div>

    {{-- ALERTS --}}

    @if(session('success'))

        <div class="alert alert-success">

            {{ session('success') }}

        </div>

    @endif

    @if($errors->any())

        <div class="alert alert-danger">

            <ul class="mb-0">

                @foreach($errors->all() as $error)

                    <li>{{ $error }}</li>

                @endforeach

            </ul>

        </div>

    @endif

    {{-- FORM --}}

    <form action="{{ route(
            'company.purchase-payments.store'
        ) }}"
        method="POST"
        enctype="multipart/form-data">

        @csrf

        <input type="hidden"
               name="purchase_invoice_id"
               value="{{ $invoice->id }}">

        <div class="card border-0 shadow-sm">

            <div class="card-body">

                {{-- SUPPLIER INFO --}}

                <div class="row mb-4">

                    <div class="col-md-3">

                        <label class="form-label">

                            Supplier

                        </label>

                        <input type="text"
                               class="form-control"
                               value="{{ $invoice->supplier->name }}"
                               readonly>

                    </div>

                    <div class="col-md-3">

                        <label class="form-label">

                            Grand Total

                        </label>

                        <input type="text"
                               class="form-control"
                               value="{{ number_format(
                                    $invoice->grand_total,
                                    2
                               ) }}"
                               readonly>

                    </div>

                    <div class="col-md-3">

                        <label class="form-label">

                            Paid Amount

                        </label>

                        <input type="text"
                               class="form-control"
                               value="{{ number_format(
                                    $invoice->paid_amount,
                                    2
                               ) }}"
                               readonly>

                    </div>

                    <div class="col-md-3">

                        <label class="form-label">

                            Due Amount

                        </label>

                        <input type="text"
                               class="form-control text-danger fw-bold"
                               value="{{ number_format(
                                    $invoice->due_amount,
                                    2
                               ) }}"
                               readonly>

                    </div>

                </div>

                {{-- PAYMENT INFO --}}

                <div class="row">

                    {{-- PAYMENT DATE --}}

                    <div class="col-md-4 mb-3">

                        <label class="form-label">

                            Payment Date

                        </label>

                        <input type="date"
                               name="payment_date"
                               class="form-control"
                               value="{{ date('Y-m-d') }}"
                               required>

                    </div>

                    {{-- PAYMENT AMOUNT --}}

                    <div class="col-md-4 mb-3">

                        <label class="form-label">

                            Payment Amount

                        </label>

                        <input type="number"
       step="0.01"
       min="0.01"

       max="{{ $invoice->due_amount }}"

       name="amount"

       id="payment_amount"

       class="form-control"

       required>

                        <small class="text-muted">

                            Max :
                            {{ number_format(
                                $invoice->due_amount,
                                2
                            ) }}

                        </small>

                    </div>

                    {{-- PAYMENT METHOD --}}

          <div class="col-md-12 mb-4">

    <label class="form-label fw-bold">

        Payment Account

    </label>

    <select
        name="account_id"
        id="account_select"
        class="form-select account-select"
        required>

        <option value="">

            Select Account

        </option>

        @foreach($accounts as $account)

            <option
                value="{{ $account->id }}"

                data-balance="
                    {{ $account->current_balance }}
                ">

                {{ $account->account_name }}

                |

                {{ ucfirst(
                    $account->account_type
                ) }}

                |

                Balance:
                {{ number_format(
                    $account->current_balance,
                    2
                ) }}

                {{ $account->currency }}

            </option>

        @endforeach

    </select>

    {{-- LIVE BALANCE --}}

    <div
        id="selected_balance"
        class="balance-preview mt-3"
        style="display:none;">

    </div>

</div>
                    {{-- REFERENCE NO --}}

                    <div class="col-md-6 mb-3">

                        <label class="form-label">

                            Reference No

                        </label>

                        <input type="text"
                               name="reference_no"
                               class="form-control">

                    </div>

                    {{-- RECEIPT FILE --}}

                    <div class="col-md-6 mb-3">

                        <label class="form-label">

                            Receipt File

                        </label>

                        <input type="file"
                               name="receipt_file"
                               class="form-control"

                               accept=".jpg,.jpeg,.png,.pdf">

                        <small class="text-muted">

                            JPG, PNG, PDF only.
                            Max 10MB

                        </small>

                    </div>

                    {{-- NOTE --}}

                    <div class="col-md-12 mb-3">

                        <label class="form-label">

                            Note

                        </label>

                        <textarea name="note"
                                  rows="3"
                                  class="form-control"></textarea>

                    </div>

                </div>

                {{-- SUBMIT --}}

                <div class="
    d-flex
    justify-content-between
    align-items-center
    mt-4
">

    {{-- BACK BUTTON --}}

    <a href="{{ route(
        'company.purchases.show',
        $invoice->id
    ) }}"
       class="btn btn-secondary px-4">

        <i class="fa fa-arrow-left"></i>

        Back

    </a>

    {{-- SAVE BUTTON --}}

    <button type="submit"
            class="btn btn-success px-4">

        <i class="fa fa-save"></i>

        Save Payment

    </button>

</div>

            </div>

        </div>

    </form>

</div>

{{-- PAYMENT VALIDATION --}}

<script>

let accountSelect =
    document.getElementById(
        'account_select'
    );

let paymentInput =
    document.getElementById(
        'payment_amount'
    );

let balancePreview =
    document.getElementById(
        'selected_balance'
    );

/**
 * 🔥 ACCOUNT CHANGE
 */

accountSelect.addEventListener(
    'change',
    function () {

        let option =
            this.options[
                this.selectedIndex
            ];

        let balance =
            parseFloat(
                option.dataset.balance
            ) || 0;

        if (this.value)
        {
            balancePreview.style.display =
                'block';

            balancePreview.innerHTML =
                'Available Balance : ' +
                balance.toLocaleString();
        }
        else
        {
            balancePreview.style.display =
                'none';
        }

    }
);

/**
 * 🔥 PAYMENT VALIDATION
 */

paymentInput.addEventListener(
    'input',
    function () {

        let amount =
            parseFloat(
                this.value
            ) || 0;

        let due =
            parseFloat(
                this.max
            ) || 0;

        // DUE CHECK

        if (amount > due)
        {
            this.value = due;

            alert(
                'Payment exceeds due amount.'
            );

            return;
        }

        // ACCOUNT CHECK

        let option =
            accountSelect.options[
                accountSelect.selectedIndex
            ];

        let balance =
            parseFloat(
                option.dataset.balance
            ) || 0;

        if (amount > balance)
        {
            this.value = balance;

            alert(
                'Insufficient account balance.'
            );
        }

    }
);

</script>
<style>

.account-select{

    background:#0f172a !important;
    color:white !important;

    border:2px solid #1e293b;

    border-radius:14px;

    padding:14px;

}

.account-select:focus{

    border-color:#3b82f6;

    box-shadow:none;

}

.balance-preview{

    background:#052e16;

    color:#22c55e;

    border:1px solid #14532d;

    border-radius:14px;

    padding:14px;

    font-size:18px;

    font-weight:bold;

}

</style>
@endsection