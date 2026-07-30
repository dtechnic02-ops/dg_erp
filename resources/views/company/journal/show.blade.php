@extends('company.layout')

@section('title', 'Journal Voucher')

@section('content')

@php
    $user = auth()->user();
    $canEdit = $user && ((int) $user->role_id === \App\Models\Role::COMPANY_ADMIN_ID || $user->hasPermission('edit_journal'));
    $canPrint = $user && ((int) $user->role_id === \App\Models\Role::COMPANY_ADMIN_ID || $user->hasPermission('print_journal'));
    $company = auth()->user()->company;

    $totalDebit = 0.0;
    $totalCredit = 0.0;

    foreach ($journal->items as $item) {
        if ($item->type === 'debit') {
            $totalDebit += (float) $item->amount;
        } else {
            $totalCredit += (float) $item->amount;
        }
    }
@endphp

<div class="dg-page dg-invoice">

    <header class="dg-toolbar dg-invoice-toolbar d-print-none">
        <div class="container-fluid">
            <div class="d-flex flex-nowrap align-items-center justify-content-end gap-2">
                <nav class="btn-group" aria-label="Journal voucher toolbar">
                    <a href="{{ route('company.journal.index') }}" class="btn btn-outline-secondary dg-btn">Back</a>
                    @if ($canPrint)
                        <a href="{{ route('company.journal.print-voucher', $journal->id) }}" target="_blank" class="btn btn-outline-primary dg-btn">
                            <span aria-hidden="true">🖨</span> Print
                        </a>
                    @endif
                    @if ($canEdit && $journal->isActive())
                        <a href="{{ route('company.journal.edit', $journal->id) }}" class="btn btn-outline-primary dg-btn">Edit</a>
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

            @if ($canEdit && $journal->isPosted() && !$journal->reversal_of_journal_id)
                <form method="POST" action="{{ route('company.journal.reverse', $journal->id) }}" class="card dg-card mb-3 d-print-none">
                    @csrf
                    <div class="card-body dg-card-body d-flex flex-wrap align-items-end gap-2">
                        <div class="flex-grow-1">
                            <label for="cancel_reason" class="form-label">Reversal Reason <span class="text-danger">*</span></label>
                            <input type="text" name="cancel_reason" id="cancel_reason" class="form-control dg-input" maxlength="1000" required>
                        </div>
                        <button type="submit" class="btn btn-outline-danger dg-btn">Reverse Journal</button>
                    </div>
                </form>
            @endif

            <article class="dg-invoice-sheet dg-print">

                <h1 class="dg-invoice-doc-title">JOURNAL VOUCHER</h1>

                <div class="dg-invoice-parties">
                    <section class="dg-invoice-party dg-invoice-party-company">
                        <h2 class="dg-invoice-party-title">Company Information</h2>
                        <div class="dg-invoice-company-block">
                            @if ($company?->logo_path)
                                <img
                                    src="{{ asset('companies/' . $company->id . '/' . $company->logo_path) }}"
                                    alt="{{ $company->company_name ?? 'Company' }}"
                                    class="dg-invoice-logo">
                            @endif
                            <div class="dg-invoice-field-list">
                                @if (!empty($company?->company_name))
                                    <div class="dg-invoice-field-row">
                                        <span class="dg-invoice-field-label">Company Name</span>
                                        <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                        <span class="dg-invoice-field-value">{{ $company->company_name }}</span>
                                    </div>
                                @endif
                                @if (!empty($company?->address))
                                    <div class="dg-invoice-field-row">
                                        <span class="dg-invoice-field-label">Address</span>
                                        <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                        <span class="dg-invoice-field-value">{{ $company->address }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </section>

                    <section class="dg-invoice-party dg-invoice-party-details">
                        <h2 class="dg-invoice-party-title">Voucher Summary</h2>
                        <div class="dg-invoice-field-list">
                            <div class="dg-invoice-field-row">
                                <span class="dg-invoice-field-label">Journal No</span>
                                <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                <span class="dg-invoice-field-value">{{ $journal->journal_no }}</span>
                            </div>
                            <div class="dg-invoice-field-row">
                                <span class="dg-invoice-field-label">Journal Date</span>
                                <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                <span class="dg-invoice-field-value">{{ $journal->journal_date?->format('d-m-Y') ?? '-' }}</span>
                            </div>
                            <div class="dg-invoice-field-row">
                                <span class="dg-invoice-field-label">Reference No</span>
                                <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                <span class="dg-invoice-field-value">{{ $journal->reference_no ?: '-' }}</span>
                            </div>
                            <div class="dg-invoice-field-row">
                                <span class="dg-invoice-field-label">Status</span>
                                <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                <span class="dg-invoice-field-value">
                                    @if ($journal->isPosted())
                                        <span class="dg-badge dg-badge-status dg-badge-success">Posted</span>
                                    @elseif ($journal->status === \App\Models\Journal::STATUS_REVERSED)
                                        <span class="dg-badge dg-badge-status dg-badge-secondary">Reversed</span>
                                    @else
                                        <span class="dg-badge dg-badge-status dg-badge-secondary">Cancelled</span>
                                    @endif
                                </span>
                            </div>
                            @if (!empty($journal->financialYear?->name))
                                <div class="dg-invoice-field-row">
                                    <span class="dg-invoice-field-label">Financial Year</span>
                                    <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                    <span class="dg-invoice-field-value">{{ $journal->financialYear->name }}</span>
                                </div>
                            @endif
                        </div>
                    </section>
                </div>

                <section class="dg-invoice-lines">
                    <h2 class="dg-invoice-lines-title">Transaction Details</h2>
                    <div class="dg-table-scroll">
                        <table class="table dg-table dg-invoice-table">
                            <thead class="dg-head">
                                <tr>
                                    <th scope="col" class="dg-col-num">#</th>
                                    <th scope="col">Account</th>
                                    <th scope="col">Related Party</th>
                                    <th scope="col" class="dg-col-num">Debit</th>
                                    <th scope="col" class="dg-col-num">Credit</th>
                                    <th scope="col">Remark</th>
                                </tr>
                            </thead>
                            <tbody class="dg-body">
                                @foreach ($journal->items as $item)
                                    <tr class="dg-row">
                                        <td class="dg-col-num">{{ $loop->iteration }}</td>
                                        <td>{{ $item->account->account_name ?? '-' }}</td>
                                        <td>{{ $item->sub_ledger_label ?: '-' }}</td>
                                        <td class="dg-col-num">
                                            @if ($item->type === 'debit')
                                                {{ number_format($item->amount, 2) }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="dg-col-num">
                                            @if ($item->type === 'credit')
                                                {{ number_format($item->amount, 2) }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>{{ $item->note ?: '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="dg-row">
                                    <th colspan="3" class="text-end">Total</th>
                                    <th class="dg-col-num">{{ number_format($totalDebit, 2) }}</th>
                                    <th class="dg-col-num">{{ number_format($totalCredit, 2) }}</th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </section>

                <div class="dg-invoice-footer">
                    <section class="dg-invoice-payment">
                        <h2 class="dg-invoice-party-title">Narration</h2>
                        <div class="dg-invoice-note-block">
                            <div class="dg-invoice-note-body">{{ $journal->note ?: '-' }}</div>
                        </div>

                        <h2 class="dg-invoice-party-title mt-3">Attachment</h2>
                        <div class="dg-invoice-note-block">
                            @if ($journal->attachment)
                                <div class="dg-invoice-note-body">
                                    <a href="{{ asset($journal->attachment) }}" target="_blank" rel="noopener">View Attachment</a>
                                </div>
                            @else
                                <div class="dg-invoice-note-body">-</div>
                            @endif
                        </div>
                    </section>

                    <section class="dg-invoice-totals">
                        <h2 class="dg-invoice-party-title">Summary</h2>
                        <div class="dg-invoice-totals-box">
                            <div class="dg-summary-item">
                                <span class="dg-summary-label">Total Debit</span>
                                <span class="dg-summary-value">{{ number_format($totalDebit, 2) }}</span>
                            </div>
                            <div class="dg-summary-item">
                                <span class="dg-summary-label">Total Credit</span>
                                <span class="dg-summary-value">{{ number_format($totalCredit, 2) }}</span>
                            </div>
                            <div class="dg-invoice-totals-divider"></div>
                            <div class="dg-summary-item dg-summary-total">
                                <span class="dg-summary-label">Amount</span>
                                <span class="dg-summary-value">{{ number_format($journal->total_amount, 2) }}</span>
                            </div>
                        </div>
                    </section>
                </div>

                <section class="dg-invoice-lines">
                    <h2 class="dg-invoice-lines-title">Audit Information</h2>
                    <div class="dg-invoice-field-list">
                        <div class="dg-invoice-field-row">
                            <span class="dg-invoice-field-label">Created By</span>
                            <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                            <span class="dg-invoice-field-value">{{ $journal->createdBy->name ?? '-' }}</span>
                        </div>
                        <div class="dg-invoice-field-row">
                            <span class="dg-invoice-field-label">Created At</span>
                            <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                            <span class="dg-invoice-field-value">{{ $journal->created_at?->format('d-m-Y H:i') ?? '-' }}</span>
                        </div>
                        @if ($journal->updated_by)
                            <div class="dg-invoice-field-row">
                                <span class="dg-invoice-field-label">Updated By</span>
                                <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                <span class="dg-invoice-field-value">{{ $journal->updatedByUser->name ?? '-' }}</span>
                            </div>
                            <div class="dg-invoice-field-row">
                                <span class="dg-invoice-field-label">Updated At</span>
                                <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                <span class="dg-invoice-field-value">{{ $journal->updated_at?->format('d-m-Y H:i') ?? '-' }}</span>
                            </div>
                        @endif
                    </div>
                </section>

            </article>

        </div>
    </main>
</div>

@endsection
