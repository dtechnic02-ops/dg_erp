@extends('company.layout')

@section('title', 'Loan Payment')

@section('content')

@php
    $canViewAccount = userCan('view_loan_account');
    $canViewPayment = userCan('view_loan_payment');
@endphp

<div class="dg-page">

    <header class="dg-toolbar">
        <div class="container-fluid">
            <div class="d-flex flex-nowrap align-items-center gap-2">
                <div class="flex-shrink-0">
                    <h1 class="h4 mb-0">Loan Payment</h1>
                </div>

                <div class="flex-fill d-flex justify-content-end align-items-center gap-2 flex-wrap flex-md-nowrap">
                    <nav class="btn-group flex-wrap" aria-label="Loan payment create toolbar">
                        <a href="{{ route('company.dashboard') }}" class="btn btn-outline-secondary dg-btn">Dashboard</a>
                        @if ($canViewAccount)
                            <a href="{{ route('company.loan-account.index') }}" class="btn btn-outline-secondary dg-btn">Loan Ledger</a>
                        @endif
                        @if ($canViewPayment)
                            <a href="{{ route('company.loan-payment.index') }}" class="btn btn-outline-secondary dg-btn">Payment List</a>
                            <a href="{{ route('company.loan-payment.create', $loan->id) }}" class="btn btn-outline-secondary dg-btn">Refresh</a>
                        @endif
                    </nav>
                </div>
            </div>
        </div>
    </header>

    <main class="dg-container">
        <div class="container-fluid">

            @if ($errors->any())
                <div class="alert alert-danger dg-alert" role="alert">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger dg-alert" role="alert">{{ session('error') }}</div>
            @endif

            @unless ($activeFy)
                <div class="alert alert-warning dg-alert" role="alert">
                    Please activate a financial year before creating a loan payment.
                </div>
            @endunless

            <form
                method="POST"
                action="{{ route('company.loan-payment.store') }}"
                enctype="multipart/form-data"
                id="loanPaymentForm">

                @csrf
                <input type="hidden" name="request_key" value="{{ old('request_key', (string) \Illuminate\Support\Str::uuid()) }}">
                <input type="hidden" name="loan_account_id" value="{{ $loan->id }}">
                @if ($activeFy)
                    <input type="hidden" name="financial_year_id" value="{{ $activeFy->id }}">
                @endif

                <section class="dg-section">
                    <article class="card dg-card">
                        <header class="card-header dg-card-header">
                            <h2 class="h6 mb-0">Loan Information</h2>
                        </header>

                        <div class="card-body dg-card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label dg-label">Loan No</label>
                                    <input readonly class="form-control dg-input" value="{{ $loan->loan_no }}">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label dg-label">Party</label>
                                    <input readonly class="form-control dg-input" value="{{ $loan->partyAccount->name ?? '-' }}">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label dg-label">Remaining Principal</label>
                                    <input readonly class="form-control dg-input" value="{{ number_format($loan->remaining_principal, 2) }}">
                                </div>

                                <div class="col-md-4">
                                    <label for="financial_year" class="form-label dg-label">Financial Year</label>
                                    <input readonly id="financial_year" class="form-control dg-input" value="{{ $activeFy->name ?? 'No active financial year' }}">
                                </div>
                            </div>
                        </div>
                    </article>
                </section>

                <section class="dg-section">
                    <article class="card dg-card">
                        <header class="card-header dg-card-header">
                            <h2 class="h6 mb-0">Payment Details</h2>
                        </header>

                        <div class="card-body dg-card-body">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label dg-label d-block">Payment Source</label>
                                    <div class="d-flex flex-wrap gap-3">
                                        <div class="form-check" @if ($loan->loan_type !== \App\Models\LoanAccount::TYPE_TAKEN) hidden @endif>
                                            <input class="form-check-input" type="radio" name="payment_source" id="payment_source_account" value="account" @checked(old('payment_source', 'account') === 'account')>
                                            <label class="form-check-label" for="payment_source_account">Account</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="payment_source" id="payment_source_saving" value="saving" @checked(old('payment_source') === 'saving')>
                                            <label class="form-check-label" for="payment_source_saving">Saving Withdraw</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4" id="availableSavingWrap" style="display: none;">
                                    <label class="form-label dg-label">Available Saving Balance</label>
                                    <input readonly class="form-control dg-input" value="{{ number_format($savingBalance, 2) }}" id="availableSavingBalance">
                                </div>

                                <div class="col-md-3">
                                    <label for="principal_amount" class="form-label dg-label">Principal</label>
                                    <input required type="number" step="0.01" min="0" name="principal_amount" id="principal_amount" value="{{ old('principal_amount', 0) }}" class="form-control dg-input payment-amount-field">
                                </div>

                                <div class="col-md-3">
                                    <label for="interest_amount" class="form-label dg-label">Interest</label>
                                    <input type="number" step="0.01" min="0" name="interest_amount" id="interest_amount" value="{{ old('interest_amount', 0) }}" class="form-control dg-input payment-amount-field">
                                </div>

                                <div class="col-md-2">
                                    <label for="fine_amount" class="form-label dg-label">Fine</label>
                                    <input type="number" step="0.01" min="0" name="fine_amount" id="fine_amount" value="{{ old('fine_amount', 0) }}" class="form-control dg-input payment-amount-field">
                                </div>

                                <div class="col-md-2" id="savingAmountWrap" @if ($loan->loan_type !== \App\Models\LoanAccount::TYPE_TAKEN) hidden @endif>
                                    <label for="saving_amount" class="form-label dg-label">Saving</label>
                                    <input type="number" step="0.01" min="0" name="saving_amount" id="saving_amount" value="{{ old('saving_amount', 0) }}" class="form-control dg-input payment-amount-field">
                                </div>

                                <div class="col-md-3">
                                    <label for="payment_date" class="form-label dg-label">Payment Date</label>
                                    <input required type="date" name="payment_date" id="payment_date" value="{{ old('payment_date', date('Y-m-d')) }}" class="form-control dg-input">
                                </div>

                                <div class="col-md-3">
                                    <label for="next_payment_date" class="form-label dg-label">Next Payment Date</label>
                                    <input type="date" name="next_payment_date" id="next_payment_date" value="{{ old('next_payment_date', optional($loan->next_payment_date)->format('Y-m-d')) }}" class="form-control dg-input">
                                </div>

                                <div class="col-md-6" id="accountSelectWrap">
                                    <label for="account_id" class="form-label dg-label">Cash/Bank Account</label>
                                    <select name="account_id" id="account_id" class="form-select dg-select">
                                        <option value="">Select Account</option>
                                        @foreach ($accounts as $account)
                                            <option value="{{ $account->id }}" @selected(old('account_id') == $account->id)>
                                                {{ $account->account_name }} - Balance: {{ number_format($account->current_balance, 2) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label for="attachment" class="form-label dg-label">Attachment</label>
                                    <input type="file" name="attachment" id="attachment" accept=".jpg,.jpeg,.png,.pdf" class="form-control dg-input">
                                    <small class="text-muted">jpg / png / pdf only</small>
                                </div>

                                <div class="col-md-12">
                                    <label for="note" class="form-label dg-label">Note</label>
                                    <textarea name="note" id="note" rows="4" class="form-control dg-input">{{ old('note') }}</textarea>
                                </div>
                            </div>
                        </div>
                    </article>
                </section>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary dg-btn" @disabled(!$activeFy)>Save Payment</button>
                    <a href="{{ route('company.loan-payment.index') }}" class="btn btn-outline-secondary dg-btn">Back</a>
                </div>
            </form>
        </div>
    </main>
</div>

@push('scripts')
<script>
(function () {
    const accountSource = document.getElementById('payment_source_account');
    const savingSource = document.getElementById('payment_source_saving');
    const accountWrap = document.getElementById('accountSelectWrap');
    const savingWrap = document.getElementById('savingAmountWrap');
    const availableSavingWrap = document.getElementById('availableSavingWrap');
    const accountSelect = document.getElementById('account_id');
    const savingAmountInput = document.getElementById('saving_amount');

    if (!accountSource || !savingSource) {
        return;
    }

    function togglePaymentSource() {
        const fromSaving = savingSource.checked;

        accountWrap.style.display = fromSaving ? 'none' : '';
        savingWrap.style.display = fromSaving ? 'none' : '';
        availableSavingWrap.style.display = fromSaving ? '' : 'none';

        if (fromSaving) {
            accountSelect.removeAttribute('required');
            accountSelect.value = '';
            savingAmountInput.value = '0';
        } else {
            accountSelect.setAttribute('required', 'required');
        }
    }

    accountSource.addEventListener('change', togglePaymentSource);
    savingSource.addEventListener('change', togglePaymentSource);
    togglePaymentSource();
})();
</script>
@endpush

@endsection
