@extends('company.layout')

@section('title', 'Purchase Return Refund Details')

@section('content')

@php
    $company = auth()->user()->company;
    $purchaseReturn = $refund->purchaseReturn;

    $refundTotalAmount = (float) $refund->refund_amount;
    $amountRupees = (int) floor($refundTotalAmount);
    $amountPaisa = (int) round(($refundTotalAmount - $amountRupees) * 100);
    $rupeeWords = trim(preg_replace('/\s+only$/i', '', preg_replace('/\s+only\s+thousand\s+/i', ' Thousand ', numberToWords($amountRupees))));
    if ($amountPaisa > 0) {
        $paisaWords = trim(preg_replace('/\s+only$/i', '', preg_replace('/\s+only\s+thousand\s+/i', ' Thousand ', numberToWords($amountPaisa))));
        $amountInWords = $rupeeWords . ' Rupees and ' . $paisaWords . ' Paisa Only';
    } else {
        $amountInWords = $rupeeWords . ' Rupees Only';
    }
@endphp

<div class="dg-page dg-invoice">

    <header class="dg-toolbar dg-invoice-toolbar d-print-none">
        <div class="container-fluid">
            <div class="d-flex flex-nowrap align-items-center justify-content-end gap-2">
                <nav class="btn-group" aria-label="Purchase return refund toolbar">
                    <a href="{{ route('company.purchase-return-refunds.index') }}" class="btn btn-outline-secondary dg-btn">Back</a>
                    <a href="{{ route('company.purchase-return-refunds.print', $refund->id) }}" target="_blank" class="btn btn-outline-secondary dg-btn">Print</a>
                    @if ($purchaseReturn)
                        <a href="{{ route('company.purchase-return.show', $purchaseReturn->id) }}" class="btn btn-outline-primary dg-btn">View Return</a>
                    @endif
                    @if ((int) $refund->status !== 0)
                        <button type="button" class="btn btn-outline-danger dg-btn" data-bs-toggle="modal" data-bs-target="#dgPurchaseReturnRefundCancelModal">Cancel Refund</button>
                    @endif
                </nav>
            </div>
        </div>
    </header>

    @if ((int) $refund->status !== 0)
        @include('company.partials.dg-sales-cancel-modal', [
            'modalId' => 'dgPurchaseReturnRefundCancelModal',
            'modalTitle' => 'Cancel Purchase Return Refund',
            'action' => route('company.purchase-return-refunds.cancel', $refund->id),
            'submitLabel' => 'Cancel Refund',
            'entityId' => $refund->id,
        ])
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

            <article class="dg-invoice-sheet dg-print">

                <h1 class="dg-invoice-doc-title">PURCHASE RETURN REFUND</h1>

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
                                        <span class="dg-invoice-field-value dg-invoice-company-name">{{ $company->company_name }}</span>
                                    </div>
                                @endif

                                @if (!empty($company?->address))
                                    <div class="dg-invoice-field-row">
                                        <span class="dg-invoice-field-label">Address</span>
                                        <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                        <span class="dg-invoice-field-value">{{ $company->address }}</span>
                                    </div>
                                @endif

                                @if (!empty($company?->address_line_2))
                                    <div class="dg-invoice-field-row">
                                        <span class="dg-invoice-field-label">Address Line 2</span>
                                        <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                        <span class="dg-invoice-field-value">{{ $company->address_line_2 }}</span>
                                    </div>
                                @endif

                                @if (!empty($company?->mobile))
                                    <div class="dg-invoice-field-row">
                                        <span class="dg-invoice-field-label">Phone</span>
                                        <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                        <span class="dg-invoice-field-value">{{ $company->mobile }}</span>
                                    </div>
                                @elseif (!empty($company?->telephone))
                                    <div class="dg-invoice-field-row">
                                        <span class="dg-invoice-field-label">Phone</span>
                                        <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                        <span class="dg-invoice-field-value">{{ $company->telephone }}</span>
                                    </div>
                                @endif

                                @if (!empty($company?->email))
                                    <div class="dg-invoice-field-row">
                                        <span class="dg-invoice-field-label">Email</span>
                                        <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                        <span class="dg-invoice-field-value">{{ $company->email }}</span>
                                    </div>
                                @endif

                                @if (!empty($company?->vat_number))
                                    <div class="dg-invoice-field-row">
                                        <span class="dg-invoice-field-label">VAT No</span>
                                        <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                        <span class="dg-invoice-field-value">{{ $company->vat_number }}</span>
                                    </div>
                                @endif

                                @if (!empty($company?->pan_number))
                                    <div class="dg-invoice-field-row">
                                        <span class="dg-invoice-field-label">PAN No</span>
                                        <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                        <span class="dg-invoice-field-value">{{ $company->pan_number }}</span>
                                    </div>
                                @endif

                                @if (!empty($company?->website))
                                    <div class="dg-invoice-field-row">
                                        <span class="dg-invoice-field-label">Website</span>
                                        <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                        <span class="dg-invoice-field-value">{{ $company->website }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </section>

                    <section class="dg-invoice-party dg-invoice-party-supplier">
                        <h2 class="dg-invoice-party-title">Supplier Information</h2>
                        <div class="dg-invoice-field-list">
                            @if (!empty($refund->supplier?->name))
                                <div class="dg-invoice-field-row">
                                    <span class="dg-invoice-field-label">Supplier Name</span>
                                    <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                    <span class="dg-invoice-field-value">{{ $refund->supplier->name }}</span>
                                </div>
                            @endif

                            @if (!empty($refund->supplier?->authority_name))
                                <div class="dg-invoice-field-row">
                                    <span class="dg-invoice-field-label">Contact Person</span>
                                    <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                    <span class="dg-invoice-field-value">{{ $refund->supplier->authority_name }}</span>
                                </div>
                            @endif

                            @if (!empty($refund->supplier?->address))
                                <div class="dg-invoice-field-row">
                                    <span class="dg-invoice-field-label">Address</span>
                                    <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                    <span class="dg-invoice-field-value">{{ $refund->supplier->address }}</span>
                                </div>
                            @endif

                            @if (!empty($refund->supplier?->mobile))
                                <div class="dg-invoice-field-row">
                                    <span class="dg-invoice-field-label">Phone</span>
                                    <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                    <span class="dg-invoice-field-value">{{ $refund->supplier->mobile }}</span>
                                </div>
                            @elseif (!empty($refund->supplier?->telephone))
                                <div class="dg-invoice-field-row">
                                    <span class="dg-invoice-field-label">Phone</span>
                                    <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                    <span class="dg-invoice-field-value">{{ $refund->supplier->telephone }}</span>
                                </div>
                            @endif

                            @if (!empty($refund->supplier?->email))
                                <div class="dg-invoice-field-row">
                                    <span class="dg-invoice-field-label">Email</span>
                                    <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                    <span class="dg-invoice-field-value">{{ $refund->supplier->email }}</span>
                                </div>
                            @endif

                            @if ($refund->supplier)
                                <div class="dg-invoice-field-row">
                                    <span class="dg-invoice-field-label">VAT No</span>
                                    <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                    <span class="dg-invoice-field-value">{{ !empty($refund->supplier->tax_no) ? $refund->supplier->tax_no : '-' }}</span>
                                </div>

                                <div class="dg-invoice-field-row">
                                    <span class="dg-invoice-field-label">PAN No</span>
                                    <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                    <span class="dg-invoice-field-value">-</span>
                                </div>
                            @endif
                        </div>
                    </section>

                    <section class="dg-invoice-party dg-invoice-party-details">
                        <h2 class="dg-invoice-party-title">Refund Information</h2>
                        <div class="dg-invoice-field-list">
                            @if (!empty($refund->refund_no))
                                <div class="dg-invoice-field-row">
                                    <span class="dg-invoice-field-label">Refund No</span>
                                    <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                    <span class="dg-invoice-field-value">{{ $refund->refund_no }}</span>
                                </div>
                            @endif

                            @if (!empty($refund->refund_date))
                                <div class="dg-invoice-field-row">
                                    <span class="dg-invoice-field-label">Refund Date</span>
                                    <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                    <span class="dg-invoice-field-value">{{ $refund->refund_date->format('d-m-Y') }}</span>
                                </div>
                            @endif

                            <div class="dg-invoice-field-row">
                                <span class="dg-invoice-field-label">Refund Status</span>
                                <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                <span class="dg-invoice-field-value">
                                    @if ((int) $refund->status === 0)
                                        <span class="dg-badge dg-badge-status dg-badge-secondary">Cancelled</span>
                                    @else
                                        <span class="dg-badge dg-badge-status dg-badge-success">Active</span>
                                    @endif
                                </span>
                            </div>

                            @if (!empty($refund->financialYear?->name))
                                <div class="dg-invoice-field-row">
                                    <span class="dg-invoice-field-label">Financial Year</span>
                                    <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                    <span class="dg-invoice-field-value">{{ $refund->financialYear->name }}</span>
                                </div>
                            @endif

                            @if (!empty($purchaseReturn?->return_no))
                                <div class="dg-invoice-field-row">
                                    <span class="dg-invoice-field-label">Original Return No</span>
                                    <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                    <span class="dg-invoice-field-value">{{ $purchaseReturn->return_no }}</span>
                                </div>
                            @endif

                            @if (!empty($refund->reference_no))
                                <div class="dg-invoice-field-row">
                                    <span class="dg-invoice-field-label">Reference No</span>
                                    <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                    <span class="dg-invoice-field-value">{{ $refund->reference_no }}</span>
                                </div>
                            @endif
                        </div>
                    </section>
                </div>

                <section class="dg-invoice-lines">
                    <h2 class="dg-invoice-lines-title">Refund Details</h2>
                    <div class="dg-table-scroll">
                        <table class="table dg-table dg-invoice-table">
                            <thead class="dg-head">
                                <tr>
                                    <th scope="col" class="dg-col-num">#</th>
                                    <th scope="col">Description</th>
                                    <th scope="col">Reference</th>
                                    <th scope="col" class="dg-col-date">Date</th>
                                    <th scope="col" class="dg-col-num">Amount</th>
                                    <th scope="col" class="dg-col-status">Refund Status</th>
                                </tr>
                            </thead>
                            <tbody class="dg-body">
                                @php $lineNo = 0; @endphp
                                @foreach ($refund->adjustments as $adjustment)
                                    @php $lineNo++; @endphp
                                    <tr class="dg-row">
                                        <td class="dg-col-num">{{ $lineNo }}</td>
                                        <td class="dg-invoice-item-name">Original Invoice Adjustment</td>
                                        <td>{{ $adjustment->invoice->invoice_no ?? '-' }}</td>
                                        <td class="dg-col-date">
                                            @if (!empty($adjustment->invoice?->purchase_date))
                                                {{ \Illuminate\Support\Carbon::parse($adjustment->invoice->purchase_date)->format('d-m-Y') }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="dg-col-num">{{ number_format($adjustment->adjust_amount, 2) }}</td>
                                        <td class="dg-col-status">
                                            @if ((int) $adjustment->status === 1)
                                                <span class="dg-badge dg-badge-status dg-badge-success">Active</span>
                                            @else
                                                <span class="dg-badge dg-badge-status dg-badge-secondary">Cancelled</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                @if ((float) $refund->cash_amount > 0)
                                    @php $lineNo++; @endphp
                                    <tr class="dg-row">
                                        <td class="dg-col-num">{{ $lineNo }}</td>
                                        <td class="dg-invoice-item-name">Cash Refund From Supplier</td>
                                        <td>{{ $refund->account->account_name ?? '-' }}</td>
                                        <td class="dg-col-date">{{ $refund->refund_date?->format('d-m-Y') ?? '-' }}</td>
                                        <td class="dg-col-num">{{ number_format($refund->cash_amount, 2) }}</td>
                                        <td class="dg-col-status">
                                            @if ((int) $refund->status === 0)
                                                <span class="dg-badge dg-badge-status dg-badge-secondary">Cancelled</span>
                                            @else
                                                <span class="dg-badge dg-badge-status dg-badge-success">Active</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endif
                                @if ($lineNo === 0)
                                    <tr class="dg-row">
                                        <td colspan="6" class="text-center">No refund details found.</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </section>

                <div class="dg-invoice-footer">
                    <section class="dg-invoice-payment">
                        <h2 class="dg-invoice-party-title">Settlement Information</h2>
                        <dl class="dg-invoice-dl">
                            <div class="dg-invoice-dl-row">
                                <dt class="dg-invoice-dl-label">Adjustment Amount</dt>
                                <dd class="dg-invoice-dl-value">{{ number_format($refund->adjust_amount, 2) }}</dd>
                            </div>
                            <div class="dg-invoice-dl-row">
                                <dt class="dg-invoice-dl-label">Cash Refund Amount</dt>
                                <dd class="dg-invoice-dl-value dg-summary-paid">{{ number_format($refund->cash_amount, 2) }}</dd>
                            </div>
                            <div class="dg-invoice-dl-row">
                                <dt class="dg-invoice-dl-label">Remaining Refund</dt>
                                <dd class="dg-invoice-dl-value dg-summary-due">{{ number_format($remainingRefund, 2) }}</dd>
                            </div>
                        </dl>

                        @if (!empty($refund->note))
                            <div class="dg-invoice-note-block">
                                <h3 class="dg-invoice-note-title">Note</h3>
                                <div class="dg-invoice-note-body">{{ $refund->note }}</div>
                            </div>
                        @endif

                        @if ($refund->attachment)
                            <div class="dg-invoice-note-block d-print-none">
                                <h3 class="dg-invoice-note-title">Attachment</h3>
                                <div class="dg-invoice-note-body">
                                    <a href="{{ asset($refund->attachment) }}" target="_blank" rel="noopener">View Attachment</a>
                                </div>
                            </div>
                        @endif
                    </section>

                    <section class="dg-invoice-totals">
                        <h2 class="dg-invoice-party-title">Summary</h2>
                        <div class="dg-invoice-totals-box">
                            <div class="dg-summary-item">
                                <span class="dg-summary-label">Adjustment</span>
                                <span class="dg-summary-value">{{ number_format($refund->adjust_amount, 2) }}</span>
                            </div>
                            <div class="dg-summary-item">
                                <span class="dg-summary-label">Cash Refund</span>
                                <span class="dg-summary-value">{{ number_format($refund->cash_amount, 2) }}</span>
                            </div>

                            <div class="dg-invoice-totals-divider"></div>

                            <div class="dg-summary-item dg-summary-total">
                                <span class="dg-summary-label">Refund Total</span>
                                <span class="dg-summary-value">{{ number_format($refund->refund_amount, 2) }}</span>
                            </div>
                        </div>
                    </section>
                </div>

                <section class="dg-invoice-amount-words">
                    <h2 class="dg-invoice-amount-words-title">Amount in Words</h2>
                    <p class="dg-invoice-amount-words-value">{{ $amountInWords }}</p>
                </section>

            </article>

        </div>
    </main>

</div>

@if ($errors->has('cancel_date') || $errors->has('cancel_reason'))
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var modalEl = document.getElementById('dgPurchaseReturnRefundCancelModal');

                if (modalEl) {
                    bootstrap.Modal.getOrCreateInstance(modalEl).show();
                }
            });
        </script>
    @endpush
@endif

@endsection
