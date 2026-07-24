@extends('company.layout')

@section('title', 'Loan Saving Ledger Details')

@section('content')

@php
    $canPrint = userCan('print_loan_saving_ledger');
    $canViewAccount = userCan('view_loan_account');
    $canViewPayment = userCan('view_loan_payment');
@endphp

<div class="dg-page dg-invoice">

    <header class="dg-toolbar dg-invoice-toolbar d-print-none">
        <div class="container-fluid">
            <div class="d-flex flex-nowrap align-items-center justify-content-end gap-2">
                <nav class="btn-group flex-wrap" aria-label="Loan saving ledger detail toolbar">
                    <a href="{{ route('company.loan-saving-ledger.index') }}" class="btn btn-outline-secondary dg-btn">Back</a>
                    @if ($canPrint)
                        <button type="button" onclick="window.print()" class="btn btn-outline-secondary dg-btn">Print</button>
                    @endif
                    @if ($canViewAccount && $ledger->loanAccount)
                        <a href="{{ route('company.loan-account.show', $ledger->loan_account_id) }}" class="btn btn-outline-primary dg-btn">Loan Details</a>
                    @endif
                    @if ($canViewPayment && $ledger->loan_payment_id && $ledger->loanPayment)
                        <a href="{{ route('company.loan-payment.show', $ledger->loan_payment_id) }}" class="btn btn-outline-success dg-btn">Loan Payment</a>
                    @endif
                </nav>
            </div>
        </div>
    </header>

    <main class="dg-container">
        <div class="container-fluid">

            @if (session('success'))
                <div class="alert alert-success dg-alert d-print-none" role="alert">{{ session('success') }}</div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger dg-alert d-print-none" role="alert">{{ session('error') }}</div>
            @endif

            <article class="dg-invoice-sheet dg-print">

                <h1 class="dg-invoice-doc-title">LOAN SAVING LEDGER</h1>

                <section class="dg-section">
                    <div class="card dg-card">
                        <div class="card-header dg-card-header py-1">
                            <h6 class="mb-0">Transaction Information</h6>
                        </div>

                        <div class="card-body dg-card-body p-2">
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <div class="dg-row">
                                        <span class="dg-label d-inline mb-0">Financial Year :</span>
                                        {{ $ledger->financialYear->name ?? '-' }}
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="dg-row">
                                        <span class="dg-label d-inline mb-0">Business Date :</span>
                                        {{ optional($ledger->date)->format('d-m-Y') ?: '-' }}
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="dg-row">
                                        <span class="dg-label d-inline mb-0">Type :</span>
                                        @if ($ledger->type === 'deposit')
                                            <span class="badge bg-success">Deposit</span>
                                        @else
                                            <span class="badge bg-danger">Withdraw</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="dg-row">
                                        <span class="dg-label d-inline mb-0">Amount :</span>
                                        {{ number_format($ledger->amount, 2) }}
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="dg-row">
                                        <span class="dg-label d-inline mb-0">Balance After :</span>
                                        {{ number_format($ledger->balance_after, 2) }}
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="dg-row">
                                        <span class="dg-label d-inline mb-0">Cash/Bank Account :</span>
                                        {{ $ledger->account->account_name ?? '-' }}
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="dg-row">
                                        <span class="dg-label d-inline mb-0">Status :</span>
                                        @if ($ledger->status)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary">Inactive</span>
                                        @endif
                                    </div>
                                </div>

                                @if ($ledger->attachment)
                                    <div class="col-md-12">
                                        <div class="dg-row">
                                            <span class="dg-label d-inline mb-0">Attachment :</span>
                                            <a href="{{ asset($ledger->attachment) }}" target="_blank" rel="noopener noreferrer">View Attachment</a>
                                        </div>
                                    </div>
                                @endif

                                @if ($ledger->note)
                                    <div class="col-md-12">
                                        <div class="dg-row">
                                            <span class="dg-label d-inline mb-0">Note :</span>
                                            {{ $ledger->note }}
                                        </div>
                                    </div>
                                @endif
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
                                        {{ $ledger->loanAccount->loan_no ?? '-' }}
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="dg-row">
                                        <span class="dg-label d-inline mb-0">Loan Name :</span>
                                        {{ $ledger->loanAccount->loan_name ?? '-' }}
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="dg-row">
                                        <span class="dg-label d-inline mb-0">Party :</span>
                                        {{ $ledger->loanAccount->partyAccount->name ?? '-' }}
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="dg-row">
                                        <span class="dg-label d-inline mb-0">Current Saving Balance :</span>
                                        {{ number_format($currentSavingBalance, 2) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

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
                                        {{ $ledger->createdBy->name ?? '-' }}
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="dg-row">
                                        <span class="dg-label d-inline mb-0">System Entry Time :</span>
                                        {{ optional($ledger->created_at)->format('d-m-Y H:i') ?: '-' }}
                                    </div>
                                </div>

                                @if ($ledger->loan_payment_id)
                                    <div class="col-md-6">
                                        <div class="dg-row">
                                            <span class="dg-label d-inline mb-0">Linked Payment :</span>
                                            @if ($ledger->loanPayment)
                                                <a href="{{ route('company.loan-payment.show', $ledger->loan_payment_id) }}">
                                                    Payment #{{ $ledger->loan_payment_id }}
                                                </a>
                                            @else
                                                #{{ $ledger->loan_payment_id }}
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </section>

            </article>

        </div>
    </main>

</div>

@endsection
