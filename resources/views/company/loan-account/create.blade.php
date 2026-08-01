@extends('company.layout')

@section('title', 'Create Loan')

@section('content')

@php
    $canView = userCan('view_loan_account');
    $canViewPayment = userCan('view_loan_payment');
@endphp

<div class="dg-page">

    <header class="dg-toolbar">
        <div class="container-fluid">
            <div class="d-flex flex-nowrap align-items-center gap-2">

                <div class="flex-shrink-0">
                    <h1 class="h4 mb-0">Create Loan</h1>
                </div>

                <div class="flex-fill d-flex justify-content-end align-items-center gap-2 flex-wrap flex-md-nowrap">
                    <nav class="btn-group flex-wrap" aria-label="Loan create toolbar">
                        <a href="{{ route('company.dashboard') }}" class="btn btn-outline-secondary dg-btn">Dashboard</a>
                        @if ($canView)
                            <a href="{{ route('company.loan-account.index') }}" class="btn btn-outline-secondary dg-btn">Loan List</a>
                            <a href="{{ route('company.loan-account.create') }}" class="btn btn-outline-secondary dg-btn">Refresh</a>
                        @endif
                        @if ($canViewPayment)
                            <a href="{{ route('company.loan-payment.index') }}" class="btn btn-outline-secondary dg-btn">Loan Payment</a>
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

            @unless($activeFy)
                <div class="alert alert-warning dg-alert" role="alert">
                    Please activate a financial year before creating a loan.
                </div>
            @endunless

            <form
                method="POST"
                action="{{ route('company.loan-account.store') }}"
                enctype="multipart/form-data"
                class="dg-form">
                @csrf
                <input type="hidden" name="request_key" value="{{ old('request_key', (string) \Illuminate\Support\Str::uuid()) }}">

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

                                <div class="col-lg-4 col-md-6 col-12">
                                    <label for="loan_no" class="form-label dg-label">Loan No</label>
                                    <input
                                        type="text"
                                        id="loan_no"
                                        class="form-control dg-input"
                                        value="{{ $loanNo }}"
                                        readonly>
                                </div>

                                <div class="col-lg-4 col-md-6 col-12">
                                    <label for="financial_year" class="form-label dg-label">Financial Year</label>
                                    <input
                                        type="text"
                                        id="financial_year"
                                        class="form-control dg-input"
                                        value="{{ $activeFy->name ?? '-' }}"
                                        readonly>
                                </div>

                                <div class="col-lg-4 col-md-6 col-12">
                                    <label for="loan_type" class="form-label dg-label">
                                        Loan Type <span class="text-danger">*</span>
                                    </label>
                                    <select
                                        name="loan_type"
                                        id="loan_type"
                                        class="form-select dg-select"
                                        required>
                                        <option value="">Select Type</option>
                                        <option value="taken" @selected(old('loan_type') === 'taken')>Taken</option>
                                        <option value="given" @selected(old('loan_type') === 'given')>Given</option>
                                    </select>
                                </div>

                                <div class="col-lg-4 col-md-6 col-12">
                                    <label for="loan_name" class="form-label dg-label">
                                        Loan Name <span class="text-danger">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        name="loan_name"
                                        id="loan_name"
                                        class="form-control dg-input"
                                        value="{{ old('loan_name') }}"
                                        required>
                                </div>

                                <div class="col-lg-4 col-md-6 col-12">
                                    <label for="start_date" class="form-label dg-label">
                                        Loan Date <span class="text-danger">*</span>
                                    </label>
                                    <input
                                        type="date"
                                        name="start_date"
                                        id="start_date"
                                        class="form-control dg-input"
                                        value="{{ old('start_date', date('Y-m-d')) }}"
                                        @if($activeFy) min="{{ \Illuminate\Support\Carbon::parse($activeFy->start_date)->format('Y-m-d') }}" max="{{ \Illuminate\Support\Carbon::parse($activeFy->end_date)->format('Y-m-d') }}" @endif
                                        required>
                                </div>

                                <div class="col-lg-4 col-md-6 col-12">
                                    <label for="end_date" class="form-label dg-label">Due Date</label>
                                    <input
                                        type="date"
                                        name="end_date"
                                        id="end_date"
                                        class="form-control dg-input"
                                        value="{{ old('end_date') }}">
                                </div>

                            </div>
                        </div>
                    </article>
                </section>

                <section class="dg-section">
                    <article class="card dg-card">
                        <header class="card-header dg-card-header">
                            <h2 class="h6 mb-0">Party &amp; Financial Details</h2>
                        </header>

                        <div class="card-body dg-card-body">
                            <div class="row g-3">

                                <div class="col-lg-4 col-md-6 col-12">
                                    <label for="party_account_id" class="form-label dg-label">
                                        Party Account <span class="text-danger">*</span>
                                    </label>
                                    <select
                                        name="party_account_id"
                                        id="party_account_id"
                                        class="form-select dg-select"
                                        required>
                                        <option value="">Select Party</option>
                                        @foreach ($partyAccounts as $party)
                                            <option value="{{ $party->id }}" @selected(old('party_account_id') == $party->id)>
                                                {{ $party->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-lg-4 col-md-6 col-12">
                                    <label for="account_id" class="form-label dg-label">
                                        Cash / Bank Account <span class="text-danger">*</span>
                                    </label>
                                    <select
                                        name="account_id"
                                        id="account_id"
                                        class="form-select dg-select"
                                        required>
                                        <option value="">Select Account</option>
                                        @foreach ($accounts as $account)
                                            <option value="{{ $account->id }}" @selected(old('account_id') == $account->id)>
                                                {{ $account->account_name }} ({{ number_format($account->current_balance, 2) }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-lg-4 col-md-6 col-12">
                                    <label for="principal_amount" class="form-label dg-label">
                                        Principal Amount <span class="text-danger">*</span>
                                    </label>
                                    <input
                                        type="number"
                                        name="principal_amount"
                                        id="principal_amount"
                                        step="0.01"
                                        min="0.01"
                                        class="form-control dg-input"
                                        value="{{ old('principal_amount') }}"
                                        required>
                                </div>

                                <div class="col-lg-4 col-md-6 col-12">
                                    <label for="interest_rate" class="form-label dg-label">Interest Rate (%)</label>
                                    <input
                                        type="number"
                                        name="interest_rate"
                                        id="interest_rate"
                                        step="0.01"
                                        min="0"
                                        class="form-control dg-input"
                                        value="{{ old('interest_rate', 0) }}">
                                </div>

                                <div class="col-lg-8 col-md-6 col-12">
                                    <label for="attachment" class="form-label dg-label">Attachment</label>
                                    <input
                                        type="file"
                                        name="attachment"
                                        id="attachment"
                                        accept=".jpg,.jpeg,.png,.pdf"
                                        class="form-control dg-input">
                                    <small class="text-muted">PDF or image file. Maximum 5 MB.</small>
                                </div>

                            </div>
                        </div>
                    </article>
                </section>

                <section class="dg-section">
                    <article class="card dg-card">
                        <header class="card-header dg-card-header">
                            <h2 class="h6 mb-0">Other Information</h2>
                        </header>

                        <div class="card-body dg-card-body">
                            <div class="row g-3">

                                <div class="col-lg-8 col-12">
                                    <label for="note" class="form-label dg-label">Note</label>
                                    <textarea
                                        name="note"
                                        id="note"
                                        rows="3"
                                        class="form-control dg-input dg-textarea">{{ old('note') }}</textarea>
                                </div>

                                <input type="hidden" name="status" value="1">

                            </div>
                        </div>
                    </article>
                </section>

                <section class="dg-section">
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="submit" class="btn btn-primary dg-btn" @disabled(!$activeFy)>
                            Save Loan
                        </button>
                        <a href="{{ route('company.loan-account.index') }}" class="btn btn-outline-secondary dg-btn">
                            Cancel
                        </a>
                    </div>
                </section>

            </form>

        </div>
    </main>

</div>

@endsection
