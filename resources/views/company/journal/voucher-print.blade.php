@extends('company.layout')

@section('title', 'Journal Voucher Print')

@section('content')

@php
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

<div class="dg-page dg-invoice dg-invoice-print">

    <main class="dg-container">
        <div class="container-fluid">

            <div id="printArea">
                <article class="dg-invoice-sheet">

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
                                    <span class="dg-invoice-field-value">{{ $journal->isPosted() ? 'Posted' : ($journal->status === \App\Models\Journal::STATUS_REVERSED ? 'Reversed' : 'Cancelled') }}</span>
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
                                <div class="dg-invoice-note-body">{{ $journal->attachment ? 'Attached' : '-' }}</div>
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

                    <footer class="border-top pt-2 mt-3">
                        <div class="row g-2 text-center small">
                            <div class="col-4">
                                <div class="border-top pt-4 mt-4">Prepared By</div>
                                <div class="fw-bold">{{ $journal->createdBy->name ?? '-' }}</div>
                            </div>
                            <div class="col-4">
                                <div class="border-top pt-4 mt-4">Checked By</div>
                                <div>&nbsp;</div>
                            </div>
                            <div class="col-4">
                                <div class="border-top pt-4 mt-4">Approved By</div>
                                <div>&nbsp;</div>
                            </div>
                        </div>
                        <div class="text-end small text-muted mt-2">
                            Printed Date: {{ now()->format('d-m-Y H:i') }}
                        </div>
                    </footer>

                </article>
            </div>

        </div>
    </main>
</div>

<script>
    window.addEventListener('load', function () {
        window.print();
    });
</script>

@endsection
