@extends('company.layout')

@section('title', 'Journal Voucher')

@section('content')

@php
    $user = auth()->user();
    $canEdit = $user && ($user->hasPermission('journal.edit-draft') || $user->hasPermission('edit_journal'));
    $canPrint = $user?->hasPermission('print_journal') ?? false;
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
                    @if ($canEdit && $journal->isDraft() && !$journal->is_locked)
                        <a href="{{ route('company.journal.edit', $journal->id) }}" class="btn btn-outline-primary dg-btn">Edit</a>
                    @endif
                    @if($user?->hasPermission('journal.audit-view'))<a href="{{ route('company.journal.audit', $journal->id) }}" class="btn btn-outline-secondary dg-btn">Audit</a>@endif
                </nav>
            </div>
            @if (!$journal->source_module || $journal->source_module === 'journal')
                <div class="d-flex flex-wrap justify-content-end gap-2 mt-2" id="dg-journal-workflow-actions">
                    @if ($journal->status === \App\Models\Journal::STATUS_DRAFT && !$journal->is_locked && $user?->hasPermission('journal.submit'))
                        <form method="POST" action="{{ route('company.journal.submit', $journal->id) }}">@csrf<button class="btn btn-primary dg-btn">Submit</button></form>
                    @endif
                    @if ($journal->status === \App\Models\Journal::STATUS_SUBMITTED && !$journal->is_locked && $user?->hasPermission('journal.approve'))
                        <form method="POST" action="{{ route('company.journal.approve', $journal->id) }}">@csrf<button class="btn btn-success dg-btn">Approve</button></form>
                    @endif
                    @if ($journal->status === \App\Models\Journal::STATUS_APPROVED && !$journal->is_locked && $user?->hasPermission('journal.post'))
                        <form method="POST" action="{{ route('company.journal.post', $journal->id) }}">@csrf<button class="btn btn-success dg-btn">Post</button></form>
                    @endif
                    @foreach ([['journal.reject','reject','Reject'],['journal.cancel','cancel','Cancel'],['journal.reverse','reverse','Reverse']] as [$permission,$action,$label])
                        @php $visible = ($action === 'reject' && $journal->status === \App\Models\Journal::STATUS_SUBMITTED) || ($action === 'cancel' && $journal->status === \App\Models\Journal::STATUS_DRAFT) || ($action === 'reverse' && $journal->status === \App\Models\Journal::STATUS_POSTED); @endphp
                        @if ($visible && !$journal->is_locked && $user?->hasPermission($permission))
                            <form method="POST" action="{{ route('company.journal.'.$action, $journal->id) }}" class="d-flex gap-1">@csrf<input name="reason" class="form-control" required maxlength="1000" placeholder="{{ $label }} reason"><button class="btn btn-outline-danger dg-btn">{{ $label }}</button></form>
                        @endif
                    @endforeach
                    @if (!$journal->is_locked && in_array($journal->status, [\App\Models\Journal::STATUS_DRAFT,\App\Models\Journal::STATUS_SUBMITTED,\App\Models\Journal::STATUS_APPROVED,\App\Models\Journal::STATUS_POSTED], true) && $user?->hasPermission('journal.lock'))
                        <form method="POST" action="{{ route('company.journal.lock', $journal->id) }}" class="d-flex gap-1">@csrf<input name="reason" class="form-control" required maxlength="1000" placeholder="Lock reason"><button class="btn btn-outline-secondary dg-btn">Lock</button></form>
                    @elseif ($journal->is_locked && $user?->hasPermission('journal.unlock'))
                        <form method="POST" action="{{ route('company.journal.unlock', $journal->id) }}" class="d-flex gap-1">@csrf<input name="reason" class="form-control" required maxlength="1000" placeholder="Unlock reason"><button class="btn btn-outline-secondary dg-btn">Unlock</button></form>
                    @endif
                </div>
            @endif
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
                                        <span class="dg-badge dg-badge-status dg-badge-secondary">{{ ucfirst($journal->status) }}</span>
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
                                    <th scope="col">Operational Account</th>
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
                                        <td>{{ $item->chartAccount ? $item->chartAccount->code . ' — ' . $item->chartAccount->name : ($item->account->account_name ?? '-') }}</td>
                                        <td>{{ $item->account->account_name ?? '-' }}</td>
                                        <td>{{ $item->sub_ledger_label ?: '-' }}</td>
                                        <td class="dg-col-num">
                                            @if ($item->type === 'debit')
                                                {{ number_format((float) ($item->debit ?: $item->amount), 4) }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="dg-col-num">
                                            @if ($item->type === 'credit')
                                                {{ number_format((float) ($item->credit ?: $item->amount), 4) }}
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
                                    <th colspan="4" class="text-end">Total</th>
                                    <th class="dg-col-num">{{ number_format($totalDebit, 4) }}</th>
                                    <th class="dg-col-num">{{ number_format($totalCredit, 4) }}</th>
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
                            <div class="dg-summary-item">
                                <span class="dg-summary-label">Difference</span>
                                <span class="dg-summary-value">{{ number_format($totalDebit - $totalCredit, 4) }}</span>
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
                        @foreach ([
                            ['Submitter', $journal->submittedByUser, $journal->submitted_at],
                            ['Approver', $journal->approvedByUser, $journal->approved_at],
                            ['Poster', $journal->postedByUser, $journal->posted_at],
                            ['Rejector', $journal->rejectedByUser, $journal->rejected_at],
                            ['Canceller', $journal->cancelledByUser, $journal->cancelled_at],
                            ['Reverser', $journal->reversedByUser, $journal->reversed_at],
                            ['Locked By', $journal->lockedByUser, $journal->locked_at],
                            ['Unlocked By', $journal->unlockedByUser, $journal->unlocked_at],
                        ] as [$label, $actor, $when])
                            @if ($actor || $when)<div class="dg-invoice-field-row"><span class="dg-invoice-field-label">{{ $label }}</span><span class="dg-invoice-field-sep">:</span><span class="dg-invoice-field-value">{{ $actor->name ?? '-' }} @if($when) — {{ $when->format('d-m-Y H:i') }} @endif</span></div>@endif
                        @endforeach
                        @foreach ([['Rejection Reason',$journal->rejection_reason],['Cancellation Reason',$journal->cancellation_reason ?: $journal->cancel_reason],['Reversal Reason',$journal->reversal_reason],['Lock Reason',$journal->lock_reason],['Unlock Reason',$journal->unlock_reason]] as [$label,$value])
                            @if ($value)<div class="dg-invoice-field-row"><span class="dg-invoice-field-label">{{ $label }}</span><span class="dg-invoice-field-sep">:</span><span class="dg-invoice-field-value">{{ $value }}</span></div>@endif
                        @endforeach
                        <div class="dg-invoice-field-row"><span class="dg-invoice-field-label">Source Identity</span><span class="dg-invoice-field-sep">:</span><span class="dg-invoice-field-value">{{ implode(' / ', array_filter([$journal->source_module,$journal->source_type,$journal->source_id,$journal->source_key])) ?: '-' }}</span></div>
                        <div class="dg-invoice-field-row"><span class="dg-invoice-field-label">Accounting Entry</span><span class="dg-invoice-field-sep">:</span><span class="dg-invoice-field-value">{{ $journal->accountingEntry?->entry_number ?? '-' }}</span></div>
                        <div class="dg-invoice-field-row"><span class="dg-invoice-field-label">Reversal Link</span><span class="dg-invoice-field-sep">:</span><span class="dg-invoice-field-value">{{ $journal->reversalJournal?->journal_no ?? $journal->originalJournal?->journal_no ?? '-' }}</span></div>
                    </div>
                </section>

            </article>

        </div>
    </main>
</div>

@endsection
