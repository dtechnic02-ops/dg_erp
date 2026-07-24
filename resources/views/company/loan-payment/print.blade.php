@extends('company.layout')

@section('title', 'Loan Payment Print')

@section('content')

@php
    $company = auth()->user()->company;
    $loan = $payment->loanAccount;
    $party = $loan->partyAccount ?? null;

    $paidAmount = (float) $payment->total_amount;
    $amountRupees = (int) floor($paidAmount);
    $amountPaisa = (int) round(($paidAmount - $amountRupees) * 100);
    $rupeeWords = trim(preg_replace('/\s+only$/i', '', preg_replace('/\s+only\s+thousand\s+/i', ' Thousand ', numberToWords($amountRupees))));
    if ($amountPaisa > 0) {
        $paisaWords = trim(preg_replace('/\s+only$/i', '', preg_replace('/\s+only\s+thousand\s+/i', ' Thousand ', numberToWords($amountPaisa))));
        $amountInWords = $rupeeWords . ' Rupees and ' . $paisaWords . ' Paisa Only';
    } else {
        $amountInWords = $rupeeWords . ' Rupees Only';
    }

    $companyPhone = $company?->mobile ?: ($company?->telephone ?? null);
    $partyPhone = $party?->mobile ?: ($party?->telephone ?? null);
@endphp

<div class="dg-page dg-payment-print">

    <main class="dg-container">
        <div class="container-fluid">

            <div id="printArea">
                <article class="card dg-card dg-payment">

                    <header class="text-center border-bottom pb-2 mb-2">
                        @if ($company?->logo_path)
                            <img
                                src="{{ asset('companies/' . $company->id . '/' . $company->logo_path) }}"
                                alt="{{ $company->company_name ?? 'Company' }}"
                                class="dg-print-logo d-block mx-auto mb-1">
                        @endif
                        <h1 class="dg-print-title mb-0">Loan Payment Receipt</h1>
                    </header>

                    <div class="card-body dg-card-body py-2 px-3">

                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <section class="card dg-card h-100 mb-0">
                                    <header class="card-header dg-card-header py-2">
                                        <h2 class="h6 mb-0">Company Information</h2>
                                    </header>
                                    <div class="card-body dg-card-body py-2 px-3">
                                        <div class="dg-summary-bar-item">
                                            <span class="dg-summary-bar-label text-muted">Company Name</span>
                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>
                                            <span class="dg-summary-bar-value">{{ $company->company_name ?? '-' }}</span>
                                        </div>
                                        <div class="dg-summary-bar-item">
                                            <span class="dg-summary-bar-label text-muted">Address</span>
                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>
                                            <span class="dg-summary-bar-value">
                                                @if (!empty($company?->address))
                                                    {{ $company->address }}@if (!empty($company?->address_line_2)), {{ $company->address_line_2 }}@endif
                                                @else
                                                    -
                                                @endif
                                            </span>
                                        </div>
                                        <div class="dg-summary-bar-item">
                                            <span class="dg-summary-bar-label text-muted">Phone</span>
                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>
                                            <span class="dg-summary-bar-value">{{ $companyPhone ?: '-' }}</span>
                                        </div>
                                        <div class="dg-summary-bar-item">
                                            <span class="dg-summary-bar-label text-muted">Email</span>
                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>
                                            <span class="dg-summary-bar-value">{{ $company->email ?? '-' }}</span>
                                        </div>
                                    </div>
                                </section>
                            </div>

                            <div class="col-6">
                                <section class="card dg-card h-100 mb-0">
                                    <header class="card-header dg-card-header py-2">
                                        <h2 class="h6 mb-0">Party Information</h2>
                                    </header>
                                    <div class="card-body dg-card-body py-2 px-3">
                                        <div class="dg-summary-bar-item">
                                            <span class="dg-summary-bar-label text-muted">Party Name</span>
                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>
                                            <span class="dg-summary-bar-value">{{ $party->name ?? '-' }}</span>
                                        </div>
                                        <div class="dg-summary-bar-item">
                                            <span class="dg-summary-bar-label text-muted">Phone</span>
                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>
                                            <span class="dg-summary-bar-value">{{ $partyPhone ?: '-' }}</span>
                                        </div>
                                        <div class="dg-summary-bar-item">
                                            <span class="dg-summary-bar-label text-muted">Address</span>
                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>
                                            <span class="dg-summary-bar-value">{{ $party->address ?? '-' }}</span>
                                        </div>
                                    </div>
                                </section>
                            </div>
                        </div>

                        <section class="card dg-card mb-2">
                            <header class="card-header dg-card-header py-2">
                                <h2 class="h6 mb-0">Payment Information</h2>
                            </header>
                            <div class="card-body dg-card-body py-2 px-3">
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <div class="dg-summary-bar-item">
                                            <span class="dg-summary-bar-label text-muted">Reference No</span>
                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>
                                            <span class="dg-summary-bar-value">{{ $payment->reference_no ?? '-' }}</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="dg-summary-bar-item">
                                            <span class="dg-summary-bar-label text-muted">Payment Date</span>
                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>
                                            <span class="dg-summary-bar-value">{{ optional($payment->payment_date)->format('d-m-Y') ?: '-' }}</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="dg-summary-bar-item">
                                            <span class="dg-summary-bar-label text-muted">Financial Year</span>
                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>
                                            <span class="dg-summary-bar-value">{{ $payment->financialYear->name ?? '-' }}</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="dg-summary-bar-item">
                                            <span class="dg-summary-bar-label text-muted">Payment Source</span>
                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>
                                            <span class="dg-summary-bar-value">{{ $payment->isPaidFromSaving() ? 'Saving Withdraw' : 'Account' }}</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="dg-summary-bar-item">
                                            <span class="dg-summary-bar-label text-muted">Cash/Bank Account</span>
                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>
                                            <span class="dg-summary-bar-value">{{ $payment->account->account_name ?? '-' }}</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="dg-summary-bar-item">
                                            <span class="dg-summary-bar-label text-muted">Status</span>
                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>
                                            <span class="dg-summary-bar-value">{{ $payment->isActive() ? 'Active' : 'Cancelled' }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="card dg-card mb-2">
                            <header class="card-header dg-card-header py-2">
                                <h2 class="h6 mb-0">Loan Information</h2>
                            </header>
                            <div class="card-body dg-card-body py-2 px-3">
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <div class="dg-summary-bar-item">
                                            <span class="dg-summary-bar-label text-muted">Loan No</span>
                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>
                                            <span class="dg-summary-bar-value">{{ $loan->loan_no ?? '-' }}</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="dg-summary-bar-item">
                                            <span class="dg-summary-bar-label text-muted">Loan Name</span>
                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>
                                            <span class="dg-summary-bar-value">{{ $loan->loan_name ?? '-' }}</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="dg-summary-bar-item">
                                            <span class="dg-summary-bar-label text-muted">Loan Type</span>
                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>
                                            <span class="dg-summary-bar-value">{{ ucfirst($loan->loan_type ?? '-') }}</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="dg-summary-bar-item">
                                            <span class="dg-summary-bar-label text-muted">Next Payment Date</span>
                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>
                                            <span class="dg-summary-bar-value">{{ optional($payment->next_payment_date)->format('d-m-Y') ?: '-' }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="card dg-card mb-2">
                            <header class="card-header dg-card-header py-2">
                                <h2 class="h6 mb-0">Financial Information</h2>
                            </header>
                            <div class="card-body dg-card-body py-2 px-3">
                                <div class="table-responsive">
                                    <table class="table dg-table mb-2">
                                        <thead class="dg-head">
                                            <tr>
                                                <th>Principal</th>
                                                <th>Interest</th>
                                                <th>Fine</th>
                                                <th>Saving</th>
                                                <th class="text-end">Total</th>
                                                <th class="text-end">Remaining Principal</th>
                                            </tr>
                                        </thead>
                                        <tbody class="dg-body">
                                            <tr>
                                                <td>{{ number_format($payment->principal_amount, 2) }}</td>
                                                <td>{{ number_format($payment->interest_amount, 2) }}</td>
                                                <td>{{ number_format($payment->fine_amount, 2) }}</td>
                                                <td>{{ number_format($payment->saving_amount, 2) }}</td>
                                                <td class="text-end fw-bold">{{ number_format($payment->total_amount, 2) }}</td>
                                                <td class="text-end">{{ number_format($payment->remaining_principal, 2) }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="dg-amount-in-words border-top pt-2">
                                    <span class="text-muted">Amount in Words :</span>
                                    <span class="fw-semibold">{{ $amountInWords }}</span>
                                </div>
                                @if ($payment->note)
                                    <div class="dg-note dg-summary-bar-item mt-2">
                                        <span class="dg-summary-bar-label text-muted">Note</span>
                                        <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>
                                        <span class="dg-summary-bar-value">{{ $payment->note }}</span>
                                    </div>
                                @endif
                            </div>
                        </section>

                        @unless ($payment->isActive())
                            <section class="card dg-card mb-2">
                                <header class="card-header dg-card-header py-2">
                                    <h2 class="h6 mb-0">Cancel Information</h2>
                                </header>
                                <div class="card-body dg-card-body py-2 px-3">
                                    <div class="dg-summary-bar-item">
                                        <span class="dg-summary-bar-label text-muted">Cancelled Date</span>
                                        <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>
                                        <span class="dg-summary-bar-value">{{ optional($payment->cancelled_date)->format('d-m-Y') ?: '-' }}</span>
                                    </div>
                                    <div class="dg-summary-bar-item">
                                        <span class="dg-summary-bar-label text-muted">Cancelled By</span>
                                        <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>
                                        <span class="dg-summary-bar-value">{{ $payment->cancelledBy->name ?? '-' }}</span>
                                    </div>
                                    <div class="dg-summary-bar-item">
                                        <span class="dg-summary-bar-label text-muted">Cancel Reason</span>
                                        <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>
                                        <span class="dg-summary-bar-value">{{ $payment->cancel_reason ?? '-' }}</span>
                                    </div>
                                </div>
                            </section>
                        @endunless

                        <footer>
                            <div class="row g-2 mt-1">
                                <div class="col-6 text-center">
                                    <div class="dg-signature-line"></div>
                                    <div class="dg-signature-label">Party Signature</div>
                                </div>
                                <div class="col-6 text-center">
                                    <div class="dg-signature-line"></div>
                                    <div class="dg-signature-label">Authorized Signature</div>
                                </div>
                            </div>
                        </footer>

                    </div>
                </article>
            </div>

        </div>
    </main>

</div>

@push('scripts')
    <script>
        document.body.classList.add('dg-payment-print');
        window.addEventListener('load', function () {
            window.print();
        });
    </script>
@endpush

@endsection
