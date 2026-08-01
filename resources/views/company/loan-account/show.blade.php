@extends('company.layout')

@section('title', 'Loan Details')

@section('content')

@php
    $isClosed = $loan->isActive() && (float) $loan->remaining_principal <= 0;
    $canEdit = userCan('edit_loan_account') && $loan->isActive() && !$isClosed;
    $canPrint = userCan('print_loan_account');
    $canCancelPermission = userCan('cancel_loan_account');
    $canCreatePayment = userCan('create_loan_payment') && $loan->isActive() && (float) $loan->remaining_principal > 0;
@endphp

<div class="dg-page dg-invoice">

    <header class="dg-toolbar dg-invoice-toolbar d-print-none">
        <div class="container-fluid">
            <div class="d-flex flex-nowrap align-items-center justify-content-end gap-2">
                <nav class="btn-group flex-wrap" aria-label="Loan detail toolbar">
                    <a href="{{ route('company.loan-account.index') }}" class="btn btn-outline-secondary dg-btn">Back</a>
                    @if ($canPrint)
                        <button type="button" onclick="window.print()" class="btn btn-outline-secondary dg-btn">Print</button>
                    @endif
                    @if ($canEdit)
                        <a href="{{ route('company.loan-account.edit', $loan->id) }}" class="btn btn-outline-primary dg-btn">Edit</a>
                    @endif
                    @if ($canCreatePayment)
                        <a href="{{ route('company.loan-payment.create', $loan->id) }}" class="btn btn-outline-success dg-btn">Loan Payment</a>
                    @endif
                    @if ($canCancelPermission && $canCancel)
                        <form method="POST" action="{{ route('company.loan-account.cancel', $loan->id) }}" class="d-inline" onsubmit="return confirm('Cancel this loan account? This will reverse the original loan balances.')">
                            @csrf
                            <label class="visually-hidden" for="loan_cancel_date">Cancel Date</label>
                            <input type="date" id="loan_cancel_date" name="cancel_date" value="{{ old('cancel_date', date('Y-m-d')) }}" required class="form-control form-control-sm d-inline-block w-auto">
                            <label class="visually-hidden" for="loan_cancel_reason">Cancel Reason</label>
                            <input type="text" id="loan_cancel_reason" name="cancel_reason" value="{{ old('cancel_reason') }}" required maxlength="500" placeholder="Cancel reason" class="form-control form-control-sm d-inline-block w-auto">
                            <button type="submit" class="btn btn-outline-danger dg-btn">Cancel Loan</button>
                        </form>
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

                <h1 class="dg-invoice-doc-title">LOAN ACCOUNT</h1>

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
                                        {{ $loan->loan_no }}
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="dg-row">
                                        <span class="dg-label d-inline mb-0">Financial Year :</span>
                                        {{ $loan->financialYear->name ?? '-' }}
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="dg-row">
                                        <span class="dg-label d-inline mb-0">Loan Name :</span>
                                        {{ $loan->loan_name }}
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="dg-row">
                                        <span class="dg-label d-inline mb-0">Loan Type :</span>
                                        @if ($loan->loan_type === 'taken')
                                            <span class="badge bg-info">Taken</span>
                                        @else
                                            <span class="badge bg-primary">Given</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="dg-row">
                                        <span class="dg-label d-inline mb-0">Loan Date :</span>
                                        {{ optional($loan->start_date)->format('d-m-Y') ?: '-' }}
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="dg-row">
                                        <span class="dg-label d-inline mb-0">Due Date :</span>
                                        {{ optional($loan->end_date)->format('d-m-Y') ?: '-' }}
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="dg-row">
                                        <span class="dg-label d-inline mb-0">Party :</span>
                                        {{ $loan->partyAccount->name ?? '-' }}
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="dg-row">
                                        <span class="dg-label d-inline mb-0">Cash / Bank Account :</span>
                                        {{ $loan->account->account_name ?? '-' }}
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="dg-row">
                                        <span class="dg-label d-inline mb-0">Principal Amount :</span>
                                        {{ number_format($loan->principal_amount, 2) }}
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="dg-row">
                                        <span class="dg-label d-inline mb-0">Remaining Principal :</span>
                                        {{ number_format($loan->remaining_principal, 2) }}
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="dg-row">
                                        <span class="dg-label d-inline mb-0">Interest Rate :</span>
                                        {{ number_format($loan->interest_rate, 2) }}%
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="dg-row">
                                        <span class="dg-label d-inline mb-0">Status :</span>
                                        @if ($loan->isCancelled())
                                            <span class="badge bg-danger">Cancelled</span>
                                        @elseif ($isClosed)
                                            <span class="badge bg-dark">Closed</span>
                                        @else
                                            <span class="badge bg-success">Active</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="dg-row">
                                        <span class="dg-label d-inline mb-0">Created By :</span>
                                        {{ $loan->createdBy->name ?? '-' }}
                                    </div>
                                </div>

                                @if ($loan->isCancelled())
                                    <div class="col-md-6">
                                        <div class="dg-row">
                                            <span class="dg-label d-inline mb-0">Cancelled By :</span>
                                            {{ $loan->cancelledBy->name ?? '-' }}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="dg-row">
                                            <span class="dg-label d-inline mb-0">Cancelled At :</span>
                                            {{ optional($loan->cancelled_at)->format('d-m-Y H:i') ?: '-' }}
                                        </div>
                                        <div class="dg-summary-bar-item">
                                            <span class="dg-label d-inline mb-0">Cancel Reason :</span>
                                            {{ $loan->cancel_reason ?? '-' }}
                                        </div>
                                    </div>
                                @endif

                                @if ($loan->attachment)
                                    <div class="col-md-6">
                                        <div class="dg-row">
                                            <span class="dg-label d-inline mb-0">Attachment :</span>
                                            <a href="{{ asset($loan->attachment) }}" target="_blank" rel="noopener noreferrer">View Attachment</a>
                                        </div>
                                    </div>
                                @endif

                                @if ($loan->note)
                                    <div class="col-12">
                                        <div class="dg-row">
                                            <span class="dg-label d-inline mb-0">Note :</span>
                                            {{ $loan->note }}
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </section>

                <section class="dg-section">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <div class="card dg-card h-100">
                                <div class="card-body dg-card-body py-2">
                                    <span class="dg-label d-block mb-1">Saving Deposit</span>
                                    <span class="fw-bold fs-5">{{ number_format($totalSavingDeposit, 2) }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card dg-card h-100">
                                <div class="card-body dg-card-body py-2">
                                    <span class="dg-label d-block mb-1">Saving Withdraw</span>
                                    <span class="fw-bold fs-5">{{ number_format($totalSavingWithdraw, 2) }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card dg-card h-100">
                                <div class="card-body dg-card-body py-2">
                                    <span class="dg-label d-block mb-1">Saving Balance</span>
                                    <span class="fw-bold fs-5">{{ number_format($currentSavingBalance, 2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="dg-section">
                    <div class="card dg-card">
                        <div class="card-header dg-card-header py-1">
                            <h6 class="mb-0">Saving History</h6>
                        </div>

                        <div class="card-body dg-card-body p-2">
                            <div class="table-responsive">
                                <table class="table dg-table mb-0">
                                    <thead class="dg-head">
                                        <tr>
                                            <th scope="col">Date</th>
                                            <th scope="col">Type</th>
                                            <th scope="col" class="text-end">Amount</th>
                                            <th scope="col" class="text-end">Balance</th>
                                            <th scope="col">Note</th>
                                        </tr>
                                    </thead>
                                    <tbody class="dg-body">
                                        @forelse ($loan->savingLedgers as $item)
                                            <tr class="dg-row">
                                                <td>{{ optional($item->date)->format('d-m-Y') ?: $item->date }}</td>
                                                <td>
                                                    @if ($item->type === 'deposit')
                                                        <span class="badge bg-success">Deposit</span>
                                                    @else
                                                        <span class="badge bg-danger">Withdraw</span>
                                                    @endif
                                                </td>
                                                <td class="text-end">{{ number_format($item->amount, 2) }}</td>
                                                <td class="text-end">{{ number_format($item->balance_after, 2) }}</td>
                                                <td>{{ $item->note ?: '-' }}</td>
                                            </tr>
                                        @empty
                                            <tr class="dg-row">
                                                <td colspan="5" class="text-center">No Saving History Found</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </section>

            </article>

        </div>
    </main>

</div>

@endsection
