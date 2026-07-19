@extends('company.layout')

@section('title', 'Sales Payment Print')

@section('content')

@php
    $company = auth()->user()->company;
    $invoice = $payment->salesInvoice;

    $paidAmount = (float) $payment->paid_amount;
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
    $customerPhone = $payment->customer?->mobile ?: ($payment->customer?->telephone ?? null);

    $invoiceTotal = (float) ($invoice->grand_total ?? 0);
    $invoicePaid = (float) ($invoice->paid_amount ?? 0);
    $previouslyPaid = max(0, round($invoicePaid - ((int) $payment->status === 1 ? $paidAmount : 0), 2));
    $remainingDue = (float) ($invoice->due_amount ?? 0);
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
                        <h1 class="dg-print-title mb-0">Sales Payment Voucher</h1>
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
                                        <div class="dg-summary-bar-item">
                                            <span class="dg-summary-bar-label text-muted">VAT No</span>
                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>
                                            <span class="dg-summary-bar-value">{{ $company->vat_number ?? '-' }}</span>
                                        </div>
                                        <div class="dg-summary-bar-item">
                                            <span class="dg-summary-bar-label text-muted">PAN No</span>
                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>
                                            <span class="dg-summary-bar-value">{{ $company->pan_number ?? '-' }}</span>
                                        </div>
                                        <div class="dg-summary-bar-item">
                                            <span class="dg-summary-bar-label text-muted">Website</span>
                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>
                                            <span class="dg-summary-bar-value">{{ $company->website ?? '-' }}</span>
                                        </div>
                                    </div>
                                </section>
                            </div>

                            <div class="col-6">
                                <section class="card dg-card h-100 mb-0">
                                    <header class="card-header dg-card-header py-2">
                                        <h2 class="h6 mb-0">Customer Information</h2>
                                    </header>
                                    <div class="card-body dg-card-body py-2 px-3">
                                        <div class="dg-summary-bar-item">
                                            <span class="dg-summary-bar-label text-muted">Customer Name</span>
                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>
                                            <span class="dg-summary-bar-value">{{ $payment->customer->name ?? '-' }}</span>
                                        </div>
                                        <div class="dg-summary-bar-item">
                                            <span class="dg-summary-bar-label text-muted">Contact Person</span>
                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>
                                            <span class="dg-summary-bar-value">{{ $payment->customer->authority_name ?? '-' }}</span>
                                        </div>
                                        <div class="dg-summary-bar-item">
                                            <span class="dg-summary-bar-label text-muted">Phone</span>
                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>
                                            <span class="dg-summary-bar-value">{{ $customerPhone ?: '-' }}</span>
                                        </div>
                                        <div class="dg-summary-bar-item">
                                            <span class="dg-summary-bar-label text-muted">Email</span>
                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>
                                            <span class="dg-summary-bar-value">{{ $payment->customer->email ?? '-' }}</span>
                                        </div>
                                        <div class="dg-summary-bar-item">
                                            <span class="dg-summary-bar-label text-muted">VAT No</span>
                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>
                                            <span class="dg-summary-bar-value">{{ !empty($payment->customer?->tax_no) ? $payment->customer->tax_no : '-' }}</span>
                                        </div>
                                        <div class="dg-summary-bar-item">
                                            <span class="dg-summary-bar-label text-muted">PAN No</span>
                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>
                                            <span class="dg-summary-bar-value">-</span>
                                        </div>
                                    </div>
                                </section>
                            </div>
                        </div>

                        <section class="card dg-card mb-2">
                            <header class="card-header dg-card-header py-2">
                                <h2 class="h6 mb-0">Payment Details</h2>
                            </header>
                            <div class="card-body dg-card-body py-2 px-3">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <div class="dg-summary-bar-item">
                                            <span class="dg-summary-bar-label text-muted">Payment No</span>
                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>
                                            <span class="dg-summary-bar-value">{{ $payment->payment_no ?? '-' }}</span>
                                        </div>
                                        <div class="dg-summary-bar-item">
                                            <span class="dg-summary-bar-label text-muted">Financial Year</span>
                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>
                                            <span class="dg-summary-bar-value">{{ $payment->financialYear->name ?? '-' }}</span>
                                        </div>
                                        <div class="dg-summary-bar-item">
                                            <span class="dg-summary-bar-label text-muted">Reference No</span>
                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>
                                            <span class="dg-summary-bar-value">{{ $payment->reference_no ?? '-' }}</span>
                                        </div>
                                        <div class="dg-summary-bar-item">
                                            <span class="dg-summary-bar-label text-muted">Invoice Date</span>
                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>
                                            <span class="dg-summary-bar-value">
                                                @if (!empty($invoice?->sale_date))
                                                    {{ \Illuminate\Support\Carbon::parse($invoice->sale_date)->format('d-m-Y') }}
                                                @else
                                                    -
                                                @endif
                                            </span>
                                        </div>
                                        <div class="dg-summary-bar-item">
                                            <span class="dg-summary-bar-label text-muted">Previously Paid</span>
                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>
                                            <span class="dg-summary-bar-value">{{ number_format($previouslyPaid, 2) }}</span>
                                        </div>
                                        <div class="dg-summary-bar-item">
                                            <span class="dg-summary-bar-label text-muted">Remaining Due</span>
                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>
                                            <span class="dg-summary-bar-value text-warning">{{ number_format($remainingDue, 2) }}</span>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="dg-summary-bar-item">
                                            <span class="dg-summary-bar-label text-muted">Payment Date</span>
                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>
                                            <span class="dg-summary-bar-value">{{ $payment->payment_date?->format('d-m-Y') ?? '-' }}</span>
                                        </div>
                                        <div class="dg-summary-bar-item">
                                            <span class="dg-summary-bar-label text-muted">Payment Account</span>
                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>
                                            <span class="dg-summary-bar-value">{{ $payment->account->account_name ?? '-' }}</span>
                                        </div>
                                        <div class="dg-summary-bar-item">
                                            <span class="dg-summary-bar-label text-muted">Invoice No</span>
                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>
                                            <span class="dg-summary-bar-value">{{ $invoice->invoice_no ?? '-' }}</span>
                                        </div>
                                        <div class="dg-summary-bar-item">
                                            <span class="dg-summary-bar-label text-muted">Invoice Total</span>
                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>
                                            <span class="dg-summary-bar-value">{{ number_format($invoiceTotal, 2) }}</span>
                                        </div>
                                        <div class="dg-summary-bar-item">
                                            <span class="dg-summary-bar-label text-muted">This Payment</span>
                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>
                                            <span class="dg-summary-bar-value text-success">{{ number_format($paidAmount, 2) }}</span>
                                        </div>
                                        <div class="dg-summary-bar-item">
                                            <span class="dg-summary-bar-label text-muted">Amount Received</span>
                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>
                                            <span class="dg-summary-bar-value text-success">Rs. {{ number_format($paidAmount, 2) }}</span>
                                        </div>
                                        <div class="dg-summary-bar-item">
                                            <span class="dg-summary-bar-label text-muted">Status</span>
                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>
                                            <span class="dg-summary-bar-value">
                                                @if ((int) $payment->status === 1)
                                                    <span class="dg-badge dg-badge-status dg-badge-success">Received</span>
                                                @else
                                                    <span class="dg-badge dg-badge-status dg-badge-secondary">Cancelled</span>
                                                @endif
                                            </span>
                                        </div>
                                        <div class="dg-summary-bar-item">
                                            <span class="dg-summary-bar-label text-muted">Invoice Status</span>
                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>
                                            <span class="dg-summary-bar-value">
                                                @if (!empty($invoice?->payment_status))
                                                    <span class="dg-badge dg-badge-status dg-badge-{{ $invoice->payment_status == 'paid' ? 'success' : ($invoice->payment_status == 'partial' ? 'warning' : ($invoice->payment_status == 'cancelled' ? 'secondary' : 'danger')) }}">
                                                        {{ ucfirst($invoice->payment_status) }}
                                                    </span>
                                                @else
                                                    -
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-12 border-top pt-2 mt-1">
                                        <div class="dg-summary-bar-item">
                                            <span class="dg-summary-bar-label text-muted">Amount In Words</span>
                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>
                                            <span class="dg-summary-bar-value">{{ $amountInWords }}</span>
                                        </div>
                                        @if (!empty($payment->note))
                                            <div class="dg-note dg-summary-bar-item">
                                                <span class="dg-summary-bar-label text-muted">Note</span>
                                                <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>
                                                <span class="dg-summary-bar-value">{{ $payment->note }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </section>

                        <footer>
                            <div class="row g-2 mt-1">
                                <div class="col-6 text-center">
                                    <div class="dg-signature-line"></div>
                                    <div class="dg-signature-label">Customer Signature</div>
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
