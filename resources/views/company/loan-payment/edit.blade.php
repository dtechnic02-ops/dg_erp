@extends('company.layout')

@section('title', 'Edit Loan Payment')

@section('content')

@php
    $canView = userCan('view_loan_payment');
@endphp

<div class="dg-page">

    <header class="dg-toolbar">
        <div class="container-fluid">
            <div class="d-flex flex-nowrap align-items-center justify-content-between gap-2">
                <h1 class="h4 mb-0">Edit Loan Payment</h1>
                <nav class="btn-group" aria-label="Loan payment edit toolbar">
                    @if ($canView)
                        <a href="{{ route('company.loan-payment.index') }}" class="btn btn-outline-secondary dg-btn">Payment List</a>
                        <a href="{{ route('company.loan-payment.show', $payment->id) }}" class="btn btn-outline-secondary dg-btn">View Payment</a>
                    @endif
                </nav>
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

            <form method="POST" action="{{ route('company.loan-payment.update', $payment->id) }}" enctype="multipart/form-data" class="dg-form">
                @csrf

                <section class="dg-section">
                    <article class="card dg-card">
                        <header class="card-header dg-card-header">
                            <h2 class="h6 mb-0">Payment Information</h2>
                        </header>
                        <div class="card-body dg-card-body">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label" for="reference_no">Reference No</label>
                                    <input type="text" id="reference_no" class="form-control dg-input" value="{{ $payment->reference_no ?? '-' }}" readonly>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="financial_year">Financial Year</label>
                                    <input type="text" id="financial_year" class="form-control dg-input" value="{{ $payment->financialYear->name ?? '-' }}" readonly>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="payment_date">Payment Date</label>
                                    <input type="date" id="payment_date" class="form-control dg-input" value="{{ $payment->payment_date?->format('Y-m-d') }}" readonly>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="status">Status</label>
                                    <input type="text" id="status" class="form-control dg-input" value="{{ $payment->isActive() ? 'Active' : 'Cancelled' }}" readonly>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="loan_no">Loan No</label>
                                    <input type="text" id="loan_no" class="form-control dg-input" value="{{ $payment->loanAccount->loan_no ?? '-' }}" readonly>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="party_name">Party</label>
                                    <input type="text" id="party_name" class="form-control dg-input" value="{{ $payment->loanAccount->partyAccount->name ?? '-' }}" readonly>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="payment_source">Payment Source</label>
                                    <input type="text" id="payment_source" class="form-control dg-input" value="{{ $payment->isPaidFromSaving() ? 'Saving Withdraw' : 'Account' }}" readonly>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="account_name">Cash/Bank Account</label>
                                    <input type="text" id="account_name" class="form-control dg-input" value="{{ $payment->account->account_name ?? '-' }}" readonly>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="next_payment_date">Next Payment Date</label>
                                    <input type="date" name="next_payment_date" id="next_payment_date" class="form-control dg-input" value="{{ old('next_payment_date', $payment->next_payment_date?->format('Y-m-d')) }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="attachment_current">Current Attachment</label>
                                    <input type="text" id="attachment_current" class="form-control dg-input" value="{{ $payment->attachment ? 'Attached' : '-' }}" readonly>
                                </div>
                            </div>
                        </div>
                    </article>
                </section>

                <section class="dg-section">
                    <article class="card dg-card">
                        <header class="card-header dg-card-header">
                            <h2 class="h6 mb-0">Financial Information</h2>
                        </header>
                        <div class="card-body dg-card-body">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label" for="principal_amount">Principal</label>
                                    <input type="text" id="principal_amount" class="form-control dg-input text-end" value="{{ number_format($payment->principal_amount, 2) }}" readonly>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="interest_amount">Interest</label>
                                    <input type="text" id="interest_amount" class="form-control dg-input text-end" value="{{ number_format($payment->interest_amount, 2) }}" readonly>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="fine_amount">Fine</label>
                                    <input type="text" id="fine_amount" class="form-control dg-input text-end" value="{{ number_format($payment->fine_amount, 2) }}" readonly>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="saving_amount">Saving</label>
                                    <input type="text" id="saving_amount" class="form-control dg-input text-end" value="{{ number_format($payment->saving_amount, 2) }}" readonly>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="total_amount">Total</label>
                                    <input type="text" id="total_amount" class="form-control dg-input text-end" value="{{ number_format($payment->total_amount, 2) }}" readonly>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="remaining_principal">Remaining Principal</label>
                                    <input type="text" id="remaining_principal" class="form-control dg-input text-end" value="{{ number_format($payment->remaining_principal, 2) }}" readonly>
                                </div>
                            </div>
                            <p class="text-muted small mb-0 mt-3">Financial amounts cannot be edited. Cancel and create a new payment to change amounts.</p>
                        </div>
                    </article>
                </section>

                <section class="dg-section">
                    <article class="card dg-card">
                        <header class="card-header dg-card-header">
                            <h2 class="h6 mb-0">Documentation</h2>
                        </header>
                        <div class="card-body dg-card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="attachment">Replace Attachment</label>
                                    <input type="file" name="attachment" id="attachment" class="form-control dg-input">
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="note">Note</label>
                                    <textarea name="note" id="note" rows="3" class="form-control dg-input">{{ old('note', $payment->note) }}</textarea>
                                </div>
                            </div>
                        </div>
                    </article>
                </section>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('company.loan-payment.show', $payment->id) }}" class="btn btn-outline-secondary dg-btn">Cancel</a>
                    <button type="submit" class="btn btn-primary dg-btn">Update Payment</button>
                </div>
            </form>
        </div>
    </main>
</div>

@endsection
