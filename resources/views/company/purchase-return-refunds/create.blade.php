@extends('company.layout')

@section('content')

@php
    $totalRefunded = max(0, round((float) $return->grand_total - (float) $remainingAmount, 2));
@endphp

<div class="dg-page">

    <header class="dg-toolbar">
        <div class="container-fluid">
            <div class="row align-items-center g-2">
                <div class="col">
                    <h1 class="h4 mb-0">Create Purchase Return Refund</h1>
                    <p class="text-muted small mb-0">Settle purchase return via invoice adjustment and optional refund from supplier</p>
                </div>
                <div class="col-auto">
                    <nav class="btn-group" aria-label="Purchase return refund create toolbar">
                        <a href="{{ route('company.purchase-return-refunds.index') }}" class="btn btn-outline-secondary dg-btn">Back</a>
                        <a href="{{ route('company.purchase-return.show', $return->id) }}" class="btn btn-outline-secondary dg-btn">View Return</a>
                    </nav>
                </div>
            </div>
        </div>
    </header>

    <main class="dg-container">
        <div class="container-fluid">

            @if ($errors->any())
                <div class="alert alert-danger dg-alert" role="alert">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger dg-alert" role="alert">
                    {{ session('error') }}
                </div>
            @endif

            <form method="POST" action="{{ route('company.purchase-return-refunds.store') }}" enctype="multipart/form-data" id="dgPurchaseReturnRefundForm"
                data-remaining="{{ $remainingAmount }}"
                data-grand="{{ $return->grand_total }}"
                data-refunded="{{ $totalRefunded }}">
                @csrf

                <input type="hidden" name="purchase_return_id" value="{{ $return->id }}">

                <div class="row g-3">
                    <div class="col-lg-8">

                        <section class="dg-section">
                            <article class="card dg-card mb-3">
                                <header class="card-header dg-card-header">
                                    <h2 class="h6 mb-0">Purchase Return Information</h2>
                                </header>
                                <div class="card-body dg-card-body">
                                    <div class="row g-3">
                                        <div class="col-12 col-md-4">
                                            <label for="refund_no_display" class="form-label">Refund No</label>
                                            <input type="text" id="refund_no_display" class="form-control dg-input" value="{{ $refundNo }}" readonly>
                                        </div>
                                        <div class="col-12 col-md-4">
                                            <label for="refund_date" class="form-label">Refund Date</label>
                                            <input type="date" name="refund_date" id="refund_date" class="form-control dg-input" value="{{ old('refund_date', date('Y-m-d')) }}" required>
                                        </div>
                                        <div class="col-12 col-md-4">
                                            <label for="supplier_display" class="form-label">Supplier</label>
                                            <input type="text" id="supplier_display" class="form-control dg-input" value="{{ $return->supplier->name ?? '-' }}" readonly>
                                        </div>
                                        <div class="col-12 col-md-3">
                                            <label for="return_no_display" class="form-label">Return No</label>
                                            <input type="text" id="return_no_display" class="form-control dg-input" value="{{ $return->return_no }}" readonly>
                                        </div>
                                        <div class="col-12 col-md-3">
                                            <label for="grand_return_display" class="form-label">Grand Return Amount</label>
                                            <input type="text" id="grand_return_display" class="form-control dg-input text-end" value="{{ number_format($return->grand_total, 2) }}" readonly>
                                        </div>
                                        <div class="col-12 col-md-3">
                                            <label for="total_refunded_display" class="form-label">Total Refunded</label>
                                            <input type="text" id="total_refunded_display" class="form-control dg-input text-end" value="{{ number_format($totalRefunded, 2) }}" readonly>
                                        </div>
                                        <div class="col-12 col-md-3">
                                            <label for="remaining_refund_display" class="form-label">Remaining Refund</label>
                                            <input type="text" id="remaining_refund_display" class="form-control dg-input text-end fw-bold text-danger" value="{{ number_format($remainingAmount, 2) }}" readonly>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        </section>

                        <section class="dg-section">
                            <article class="card dg-card mb-3">
                                <header class="card-header dg-card-header">
                                    <h2 class="h6 mb-0">Outstanding Invoice Adjustment</h2>
                                </header>
                                <div class="card-body dg-card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table dg-table mb-0">
                                            <thead class="dg-head">
                                                <tr>
                                                    <th scope="col">Invoice</th>
                                                    <th scope="col">Date</th>
                                                    <th scope="col" class="text-end">Grand Total</th>
                                                    <th scope="col" class="text-end">Paid</th>
                                                    <th scope="col" class="text-end">Due</th>
                                                    <th scope="col" class="text-end" style="min-width: 140px;">Adjustment Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody class="dg-body">
                                                @forelse ($outstandingInvoices as $invoice)
                                                    <tr class="dg-row">
                                                        <td>{{ $invoice->invoice_no }}</td>
                                                        <td>{{ $invoice->purchase_date ? \Illuminate\Support\Carbon::parse($invoice->purchase_date)->format('d-m-Y') : '-' }}</td>
                                                        <td class="text-end">{{ number_format($invoice->grand_total, 2) }}</td>
                                                        <td class="text-end">{{ number_format($invoice->paid_amount, 2) }}</td>
                                                        <td class="text-end">{{ number_format($invoice->due_amount, 2) }}</td>
                                                        <td class="text-end">
                                                            <input type="hidden" name="purchase_invoice_id[]" value="{{ $invoice->id }}">
                                                            <input type="number"
                                                                step="0.01"
                                                                min="0"
                                                                max="{{ min((float) $invoice->due_amount, (float) $remainingAmount) }}"
                                                                name="adjust_amount[]"
                                                                class="form-control dg-input adjust-amount text-end"
                                                                data-due="{{ $invoice->due_amount }}"
                                                                value="{{ old('adjust_amount.' . $loop->index, '0') }}">
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr class="dg-row">
                                                        <td colspan="6" class="text-center py-3">No outstanding invoices for adjustment.</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </article>
                        </section>

                        <section class="dg-section">
                            <article class="card dg-card mb-3">
                                <header class="card-header dg-card-header">
                                    <h2 class="h6 mb-0">Refund Settlement</h2>
                                </header>
                                <div class="card-body dg-card-body">
                                    <div class="row g-3">
                                        <div class="col-12 col-md-6">
                                            <label for="cash_amount" class="form-label">Refund From Supplier <span class="text-muted">(Optional)</span></label>
                                            <input type="number"
                                                step="0.01"
                                                min="0"
                                                max="{{ $remainingAmount }}"
                                                name="cash_amount"
                                                id="cash_amount"
                                                class="form-control dg-input text-end"
                                                value="{{ old('cash_amount', '0') }}">
                                            <div class="form-text">Leave zero for adjustment-only settlement.</div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label for="note" class="form-label">Note</label>
                                            <textarea name="note" id="note" class="form-control dg-input dg-note" rows="2">{{ old('note') }}</textarea>
                                        </div>
                                    </div>

                                    <div class="d-none" id="refund_account_section">
                                        <div class="row g-3">
                                            <div class="col-12 col-md-6">
                                                <label for="account_id" class="form-label">Refund Account</label>
                                                <select name="account_id" id="account_id" class="form-select dg-select">
                                                    <option value="">Select Account</option>
                                                    @foreach ($accounts as $account)
                                                        <option value="{{ $account->id }}" @selected(old('account_id') == $account->id)>
                                                            {{ $account->account_name }} - {{ number_format($account->current_balance, 2) }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <label for="reference_no" class="form-label">Reference No</label>
                                                <input type="text" name="reference_no" id="reference_no" class="form-control dg-input" value="{{ old('reference_no') }}" maxlength="100">
                                            </div>
                                        </div>
                                        <div class="row g-3">
                                            <div class="col-12 col-md-6">
                                                <label for="attachment" class="form-label">Attachment</label>
                                                <input type="file" name="attachment" id="attachment" class="form-control dg-input dg-attachment" accept=".pdf,.jpg,.jpeg,.png">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        </section>

                    </div>

                    <div class="col-lg-4">
                        <section class="dg-section dg-summary">
                            <article class="card dg-card">
                                <header class="card-header dg-card-header">
                                    <h2 class="h6 mb-0">Settlement Summary</h2>
                                </header>
                                <div class="card-body dg-card-body">
                                    <table class="table table-sm mb-3">
                                        <tbody>
                                            <tr>
                                                <th scope="row">Grand Return Amount</th>
                                                <td class="text-end">{{ number_format($return->grand_total, 2) }}</td>
                                            </tr>
                                            <tr>
                                                <th scope="row">Total Refunded</th>
                                                <td class="text-end text-success">{{ number_format($totalRefunded, 2) }}</td>
                                            </tr>
                                            <tr>
                                                <th scope="row">Remaining Refund</th>
                                                <td class="text-end text-danger">{{ number_format($remainingAmount, 2) }}</td>
                                            </tr>
                                            <tr>
                                                <th scope="row">Total Adjustment</th>
                                                <td class="text-end" id="summary_total_adjustment">0.00</td>
                                            </tr>
                                            <tr>
                                                <th scope="row">Refund From Supplier</th>
                                                <td class="text-end" id="summary_refund_to_customer">0.00</td>
                                            </tr>
                                            <tr>
                                                <th scope="row">Settlement Amount</th>
                                                <td class="text-end fw-bold" id="summary_settlement">0.00</td>
                                            </tr>
                                            <tr>
                                                <th scope="row">Remaining After Settlement</th>
                                                <td class="text-end fw-bold text-primary" id="summary_remaining_after">{{ number_format($remainingAmount, 2) }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <button type="submit" class="btn btn-success w-100 dg-btn">Save Refund</button>
                                </div>
                            </article>
                        </section>
                    </div>
                </div>
            </form>

        </div>
    </main>

</div>

@endsection
