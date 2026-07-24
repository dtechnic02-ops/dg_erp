@extends('company.layout')

@section('title', 'Loan Payment Details')

@section('content')

@php
    $loan = $payment->loanAccount;
    $party = $loan->partyAccount ?? null;
    $canEdit = userCan('edit_loan_payment');
    $canPrint = userCan('print_loan_payment');
    $canCancel = userCan('cancel_loan_payment');
    $canViewAccount = userCan('view_loan_account');
@endphp

<div class="dg-page dg-invoice">

    <header class="dg-toolbar dg-invoice-toolbar d-print-none">
        <div class="container-fluid">
            <div class="d-flex flex-nowrap align-items-center justify-content-end gap-2">
                <nav class="btn-group flex-wrap" aria-label="Loan payment detail toolbar">
                    <a href="{{ route('company.loan-payment.index') }}" class="btn btn-outline-secondary dg-btn">Back</a>
                    @if ($canPrint)
                        <a href="{{ route('company.loan-payment.print', $payment->id) }}" target="_blank" class="btn btn-outline-secondary dg-btn">Print</a>
                    @endif
                    @if ($canEdit && $payment->isActive())
                        <a href="{{ route('company.loan-payment.edit', $payment->id) }}" class="btn btn-outline-primary dg-btn">Edit</a>
                    @endif
                    @if ($canCancel && $payment->isActive())
                        <button type="button" class="btn btn-outline-danger dg-btn" data-bs-toggle="modal" data-bs-target="#dgLoanPaymentCancelModal">Cancel Payment</button>
                    @endif
                    @if ($canViewAccount && $loan)
                        <a href="{{ route('company.loan-account.show', $payment->loan_account_id) }}" class="btn btn-outline-primary dg-btn">Loan Details</a>
                    @endif
                </nav>
            </div>
        </div>
    </header>

    @if ($canCancel && $payment->isActive())
        <div class="modal fade" id="dgLoanPaymentCancelModal" tabindex="-1" aria-labelledby="dgLoanPaymentCancelModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('company.loan-payment.cancel', $payment->id) }}">
                        @csrf

                        <div class="modal-header">
                            <h5 class="modal-title" id="dgLoanPaymentCancelModalLabel">Cancel Loan Payment</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="cancel_date" class="form-label">Cancel Date <span class="text-danger">*</span></label>
                                <input type="date" name="cancel_date" id="cancel_date" class="form-control dg-input" value="{{ old('cancel_date', date('Y-m-d')) }}" required>
                                @error('cancel_date')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-0">
                                <label for="cancel_reason" class="form-label">Cancel Reason <span class="text-danger">*</span></label>
                                <textarea name="cancel_reason" id="cancel_reason" class="form-control dg-input" rows="4" required>{{ old('cancel_reason') }}</textarea>
                                @error('cancel_reason')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary dg-btn" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-danger dg-btn">Cancel Payment</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <main class="dg-container">
        <div class="container-fluid">

            @if (session('success'))
                <div class="alert alert-success dg-alert d-print-none" role="alert">{{ session('success') }}</div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger dg-alert d-print-none" role="alert">{{ session('error') }}</div>
            @endif

            <article class="dg-invoice-sheet dg-print">

                <h1 class="dg-invoice-doc-title">LOAN PAYMENT VOUCHER</h1>

                <section class="dg-section">
                    <div class="card dg-card">
                        <div class="card-header dg-card-header py-1">
                            <h6 class="mb-0">Payment Information</h6>
                        </div>
                        <div class="card-body dg-card-body p-2">
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <div class="dg-row">
                                        <span class="dg-label d-inline mb-0">Reference No :</span>
                                        {{ $payment->reference_no ?? '-' }}
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="dg-row">
                                        <span class="dg-label d-inline mb-0">Financial Year :</span>
                                        {{ $payment->financialYear->name ?? '-' }}
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="dg-row">
                                        <span class="dg-label d-inline mb-0">Payment Date :</span>
                                        {{ optional($payment->payment_date)->format('d-m-Y') ?: '-' }}
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="dg-row">
                                        <span class="dg-label d-inline mb-0">Next Payment Date :</span>
                                        {{ optional($payment->next_payment_date)->format('d-m-Y') ?: '-' }}
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="dg-row">
                                        <span class="dg-label d-inline mb-0">Payment Source :</span>
                                        @if ($payment->isPaidFromSaving())
                                            <span class="badge bg-warning text-dark">Saving Withdraw</span>
                                        @else
                                            <span class="badge bg-primary">Account</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="dg-row">
                                        <span class="dg-label d-inline mb-0">Status :</span>
                                        @if ($payment->isActive())
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary">Cancelled</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="dg-section">
                    <div class="card dg-card">
                        <div class="card-header dg-card-header py-1">
                            <h6 class="mb-0">Loan Information</h6>
                        </div>
                        <div class="card-body dg-card-body p-2">
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <div class="dg-row">
                                        <span class="dg-label d-inline mb-0">Loan No :</span>
                                        {{ $loan->loan_no ?? '-' }}
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="dg-row">
                                        <span class="dg-label d-inline mb-0">Loan Name :</span>
                                        {{ $loan->loan_name ?? '-' }}
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="dg-row">
                                        <span class="dg-label d-inline mb-0">Loan Type :</span>
                                        {{ ucfirst($loan->loan_type ?? '-') }}
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="dg-row">
                                        <span class="dg-label d-inline mb-0">Loan Account :</span>
                                        {{ $loan->account->account_name ?? '-' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="dg-section">
                    <div class="card dg-card">
                        <div class="card-header dg-card-header py-1">
                            <h6 class="mb-0">Party Information</h6>
                        </div>
                        <div class="card-body dg-card-body p-2">
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <div class="dg-row">
                                        <span class="dg-label d-inline mb-0">Party Name :</span>
                                        {{ $party->name ?? '-' }}
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="dg-row">
                                        <span class="dg-label d-inline mb-0">Phone :</span>
                                        {{ $party->mobile ?? $party->telephone ?? '-' }}
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="dg-row">
                                        <span class="dg-label d-inline mb-0">Address :</span>
                                        {{ $party->address ?? '-' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="dg-section">
                    <div class="card dg-card">
                        <div class="card-header dg-card-header py-1">
                            <h6 class="mb-0">Account Information</h6>
                        </div>
                        <div class="card-body dg-card-body p-2">
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <div class="dg-row">
                                        <span class="dg-label d-inline mb-0">Cash/Bank Account :</span>
                                        {{ $payment->account->account_name ?? '-' }}
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="dg-row">
                                        <span class="dg-label d-inline mb-0">Payment Source :</span>
                                        {{ $payment->isPaidFromSaving() ? 'Saving Withdraw (No account movement)' : 'Account Payment' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="dg-section">
                    <div class="card dg-card">
                        <div class="card-header dg-card-header py-1">
                            <h6 class="mb-0">Financial Information</h6>
                        </div>
                        <div class="card-body dg-card-body p-2">
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <div class="dg-row">
                                        <span class="dg-label d-inline mb-0">Principal :</span>
                                        {{ number_format($payment->principal_amount, 2) }}
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="dg-row">
                                        <span class="dg-label d-inline mb-0">Interest :</span>
                                        {{ number_format($payment->interest_amount, 2) }}
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="dg-row">
                                        <span class="dg-label d-inline mb-0">Fine :</span>
                                        {{ number_format($payment->fine_amount, 2) }}
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="dg-row">
                                        <span class="dg-label d-inline mb-0">Saving :</span>
                                        {{ number_format($payment->saving_amount, 2) }}
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="dg-row">
                                        <span class="dg-label d-inline mb-0">Total :</span>
                                        <strong>{{ number_format($payment->total_amount, 2) }}</strong>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="dg-row">
                                        <span class="dg-label d-inline mb-0">Remaining Principal :</span>
                                        {{ number_format($payment->remaining_principal, 2) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                @if ($payment->attachment || $payment->note)
                    <section class="dg-section">
                        <div class="card dg-card">
                            <div class="card-header dg-card-header py-1">
                                <h6 class="mb-0">Documentation</h6>
                            </div>
                            <div class="card-body dg-card-body p-2">
                                <div class="row g-2">
                                    @if ($payment->attachment)
                                        <div class="col-md-12">
                                            <div class="dg-row">
                                                <span class="dg-label d-inline mb-0">Attachment :</span>
                                                <a href="{{ asset($payment->attachment) }}" target="_blank" rel="noopener noreferrer">View Attachment</a>
                                            </div>
                                        </div>
                                    @endif
                                    @if ($payment->note)
                                        <div class="col-md-12">
                                            <div class="dg-row">
                                                <span class="dg-label d-inline mb-0">Note :</span>
                                                {{ $payment->note }}
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </section>
                @endif

                <section class="dg-section">
                    <div class="card dg-card">
                        <div class="card-header dg-card-header py-1">
                            <h6 class="mb-0">Audit Information</h6>
                        </div>
                        <div class="card-body dg-card-body p-2">
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <div class="dg-row">
                                        <span class="dg-label d-inline mb-0">Created By :</span>
                                        {{ $payment->createdBy->name ?? '-' }}
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="dg-row">
                                        <span class="dg-label d-inline mb-0">System Entry Time :</span>
                                        {{ optional($payment->created_at)->format('d-m-Y H:i') ?: '-' }}
                                    </div>
                                </div>
                                @if ($payment->updatedBy)
                                    <div class="col-md-6">
                                        <div class="dg-row">
                                            <span class="dg-label d-inline mb-0">Updated By :</span>
                                            {{ $payment->updatedBy->name }}
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="dg-row">
                                            <span class="dg-label d-inline mb-0">Last Updated :</span>
                                            {{ optional($payment->updated_at)->format('d-m-Y H:i') ?: '-' }}
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </section>

                @unless ($payment->isActive())
                    <section class="dg-section">
                        <div class="card dg-card">
                            <div class="card-header dg-card-header py-1">
                                <h6 class="mb-0">Cancel Information</h6>
                            </div>
                            <div class="card-body dg-card-body p-2">
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <div class="dg-row">
                                            <span class="dg-label d-inline mb-0">Cancelled By :</span>
                                            {{ $payment->cancelledBy->name ?? '-' }}
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="dg-row">
                                            <span class="dg-label d-inline mb-0">Cancelled Date :</span>
                                            {{ optional($payment->cancelled_date)->format('d-m-Y') ?: '-' }}
                                        </div>
                                    </div>
                                    @if ($payment->cancel_reason)
                                        <div class="col-md-12">
                                            <div class="dg-row">
                                                <span class="dg-label d-inline mb-0">Cancel Reason :</span>
                                                {{ $payment->cancel_reason }}
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </section>
                @endunless

            </article>
        </div>
    </main>
</div>

@endsection
