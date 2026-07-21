@extends('company.layout')

@section('title', 'Purchase Payment')

@section('content')

@php
    $company = auth()->user()->company;
    $invoice = $payment->invoice;

    $paidAmount = (float) $payment->amount;
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
    $supplierPhone = $payment->supplier?->mobile ?: ($payment->supplier?->telephone ?? null);

    $invoiceTotal = (float) ($invoice->grand_total ?? 0);
    $invoicePaid = (float) ($invoice->paid_amount ?? 0);
    $previouslyPaid = max(0, round($invoicePaid - ((int) $payment->status === 1 ? $paidAmount : 0), 2));
    $remainingDue = (float) ($invoice->due_amount ?? 0);
@endphp

<div class="dg-page">

    <header class="dg-toolbar d-print-none">
        <div class="container-fluid">
            <div class="d-flex flex-nowrap align-items-center justify-content-end gap-2">
                <nav class="btn-group" aria-label="Purchase payment toolbar">
                    <a href="{{ route('company.purchase-payments.index') }}" class="btn btn-outline-secondary dg-btn">Back</a>
                    <a href="{{ route('company.purchase-payments.print', $payment->id) }}" target="_blank" class="btn btn-outline-secondary dg-btn">Print</a>
                    @if ($invoice)
                        <a href="{{ route('company.purchases.show', $invoice->id) }}" class="btn btn-outline-primary dg-btn">View Invoice</a>
                    @endif
                    @if ((int) $payment->status === 1)
                        <button type="button" class="btn btn-outline-danger dg-btn" data-bs-toggle="modal" data-bs-target="#dgPurchasePaymentCancelModal">Cancel Payment</button>
                    @endif
                </nav>
            </div>
        </div>
    </header>

    @if ((int) $payment->status === 1)
        <div class="modal fade" id="dgPurchasePaymentCancelModal" tabindex="-1" aria-labelledby="dgPurchasePaymentCancelModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('company.purchase-payments.cancel', $payment->id) }}">
                        @csrf

                        <div class="modal-header">
                            <h5 class="modal-title" id="dgPurchasePaymentCancelModalLabel">Cancel Purchase Payment</h5>
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
                <div class="alert alert-success dg-alert d-print-none" role="alert">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger dg-alert d-print-none" role="alert">
                    {{ session('error') }}
                </div>
            @endif

            <section class="dg-section">
                <article class="card dg-card dg-payment dg-print">

                    <header class="text-center border-bottom pb-2 mb-2">
                        @if ($company?->logo_path)
                            <img
                                src="{{ asset('companies/' . $company->id . '/' . $company->logo_path) }}"
                                alt="{{ $company->company_name ?? 'Company' }}"
                                class="dg-print-logo d-block mx-auto mb-1">
                        @endif
                        <h1 class="dg-print-title mb-0">Purchase Payment Voucher</h1>
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
                                        <h2 class="h6 mb-0">Supplier Information</h2>
                                    </header>
                                    <div class="card-body dg-card-body py-2 px-3">
                                        <div class="dg-summary-bar-item">
                                            <span class="dg-summary-bar-label text-muted">Supplier Name</span>
                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>
                                            <span class="dg-summary-bar-value">{{ $payment->supplier->name ?? '-' }}</span>
                                        </div>
                                        <div class="dg-summary-bar-item">
                                            <span class="dg-summary-bar-label text-muted">Contact Person</span>
                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>
                                            <span class="dg-summary-bar-value">{{ $payment->supplier->authority_name ?? '-' }}</span>
                                        </div>
                                        <div class="dg-summary-bar-item">
                                            <span class="dg-summary-bar-label text-muted">Phone</span>
                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>
                                            <span class="dg-summary-bar-value">{{ $supplierPhone ?: '-' }}</span>
                                        </div>
                                        <div class="dg-summary-bar-item">
                                            <span class="dg-summary-bar-label text-muted">Email</span>
                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>
                                            <span class="dg-summary-bar-value">{{ $payment->supplier->email ?? '-' }}</span>
                                        </div>
                                        <div class="dg-summary-bar-item">
                                            <span class="dg-summary-bar-label text-muted">VAT No</span>
                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>
                                            <span class="dg-summary-bar-value">{{ !empty($payment->supplier?->tax_no) ? $payment->supplier->tax_no : '-' }}</span>
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
                                                @if (!empty($invoice?->purchase_date))
                                                    {{ \Illuminate\Support\Carbon::parse($invoice->purchase_date)->format('d-m-Y') }}
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
                                            <span class="dg-summary-bar-label text-muted">Amount Paid</span>
                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>
                                            <span class="dg-summary-bar-value text-success">Rs. {{ number_format($paidAmount, 2) }}</span>
                                        </div>
                                        <div class="dg-summary-bar-item">
                                            <span class="dg-summary-bar-label text-muted">Status</span>
                                            <span class="dg-summary-bar-sep text-muted" aria-hidden="true">:</span>
                                            <span class="dg-summary-bar-value">
                                                @if ((int) $payment->status === 1)
                                                    <span class="dg-badge dg-badge-status dg-badge-success">Paid</span>
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
                                    <div class="dg-signature-label">Supplier Signature</div>
                                </div>
                                <div class="col-6 text-center">
                                    <div class="dg-signature-line"></div>
                                    <div class="dg-signature-label">Authorized Signature</div>
                                </div>
                            </div>
                        </footer>

                    </div>
                </article>
            </section>

        </div>
    </main>

</div>

@if ($errors->has('cancel_date') || $errors->has('cancel_reason'))
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var modalEl = document.getElementById('dgPurchasePaymentCancelModal');

                if (modalEl) {
                    bootstrap.Modal.getOrCreateInstance(modalEl).show();
                }
            });
        </script>
    @endpush
@endif

@endsection
