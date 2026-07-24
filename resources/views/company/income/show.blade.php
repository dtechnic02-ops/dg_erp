@extends('company.layout')

@section('title', 'Income Voucher')

@section('content')

@php
    $company = auth()->user()->company;
    $user = auth()->user();
    $canEdit = $user && ($user->role_id == 2 || $user->hasPermission('edit_income'));
    $canCancel = $user && ($user->role_id == 2 || $user->hasPermission('cancel_income'));
    $canPrint = $user && ($user->role_id == 2 || $user->hasPermission('print_income'));

    $paidAmount = (float) $income->amount;
    $amountRupees = (int) floor($paidAmount);
    $amountPaisa = (int) round(($paidAmount - $amountRupees) * 100);
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
                <nav class="btn-group" aria-label="Income voucher toolbar">
                    <a href="{{ route('company.income.index') }}" class="btn btn-outline-secondary dg-btn">Back</a>
                    @if ($canPrint)
                        <a href="{{ route('company.income.print-voucher', $income->id) }}" target="_blank" class="btn btn-outline-secondary dg-btn">Print</a>
                    @endif
                    @if ($canEdit && $income->isActive())
                        <a href="{{ route('company.income.edit', $income->id) }}" class="btn btn-outline-primary dg-btn">Edit</a>
                    @endif
                    @if ($canCancel && $income->isActive())
                        <button type="button" class="btn btn-outline-danger dg-btn" data-bs-toggle="modal" data-bs-target="#dgIncomeCancelModal">Cancel</button>
                    @endif
                </nav>
            </div>
        </div>
    </header>

    @if ($canCancel && $income->isActive())
        <div class="modal fade" id="dgIncomeCancelModal" tabindex="-1" aria-labelledby="dgIncomeCancelModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('company.income.cancel', $income->id) }}">
                        @csrf

                        <div class="modal-header">
                            <h5 class="modal-title" id="dgIncomeCancelModalLabel">Cancel Income</h5>
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
                            <button type="submit" class="btn btn-danger dg-btn">Cancel Income</button>
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

            <article class="dg-invoice-sheet dg-print">

                <h1 class="dg-invoice-doc-title">INCOME VOUCHER</h1>

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

                    <section class="dg-invoice-party dg-invoice-party-details">
                        <h2 class="dg-invoice-party-title">Voucher Summary</h2>
                        <div class="dg-invoice-field-list">
                            @if (!empty($income->income_no))
                                <div class="dg-invoice-field-row">
                                    <span class="dg-invoice-field-label">Income No</span>
                                    <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                    <span class="dg-invoice-field-value">{{ $income->income_no }}</span>
                                </div>
                            @endif

                            @if (!empty($income->income_date))
                                <div class="dg-invoice-field-row">
                                    <span class="dg-invoice-field-label">Income Date</span>
                                    <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                    <span class="dg-invoice-field-value">{{ $income->income_date->format('d-m-Y') }}</span>
                                </div>
                            @endif

                            <div class="dg-invoice-field-row">
                                <span class="dg-invoice-field-label">Status</span>
                                <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                <span class="dg-invoice-field-value">
                                    @if ($income->isActive())
                                        <span class="dg-badge dg-badge-status dg-badge-success">Active</span>
                                    @else
                                        <span class="dg-badge dg-badge-status dg-badge-secondary">Cancelled</span>
                                    @endif
                                </span>
                            </div>

                            @if (!empty($income->financialYear?->name))
                                <div class="dg-invoice-field-row">
                                    <span class="dg-invoice-field-label">Financial Year</span>
                                    <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                    <span class="dg-invoice-field-value">{{ $income->financialYear->name }}</span>
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
                                    <th scope="col">Title</th>
                                    <th scope="col">Category</th>
                                    <th scope="col">Account</th>
                                    <th scope="col" class="dg-col-num">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="dg-body">
                                <tr class="dg-row">
                                    <td class="dg-col-num">1</td>
                                    <td class="dg-invoice-item-name">{{ $income->title }}</td>
                                    <td>{{ $income->category->name ?? '-' }}</td>
                                    <td>{{ $income->account->account_name ?? '-' }}</td>
                                    <td class="dg-col-num">{{ number_format($income->amount, 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <div class="dg-invoice-footer">
                    <section class="dg-invoice-payment">
                        <h2 class="dg-invoice-party-title">Attachment</h2>
                        <div class="dg-invoice-note-block">
                            @if ($income->attachment)
                                <div class="dg-invoice-note-body">
                                    <a href="{{ asset($income->attachment) }}" target="_blank" rel="noopener" class="d-print-none">View Attachment</a>
                                    <span class="d-none d-print-inline">Attached</span>
                                </div>
                            @else
                                <div class="dg-invoice-note-body">-</div>
                            @endif
                        </div>

                        @if (!empty($income->note))
                            <div class="dg-invoice-note-block">
                                <h3 class="dg-invoice-note-title">Note</h3>
                                <div class="dg-invoice-note-body">{{ $income->note }}</div>
                            </div>
                        @endif
                    </section>

                    <section class="dg-invoice-totals">
                        <h2 class="dg-invoice-party-title">Summary</h2>
                        <div class="dg-invoice-totals-box">
                            <div class="dg-summary-item dg-summary-total">
                                <span class="dg-summary-label">Amount</span>
                                <span class="dg-summary-value">{{ number_format($income->amount, 2) }}</span>
                            </div>
                        </div>
                    </section>
                </div>

                <section class="dg-invoice-amount-words">
                    <h2 class="dg-invoice-amount-words-title">Amount in Words</h2>
                    <p class="dg-invoice-amount-words-value">{{ $amountInWords }}</p>
                </section>

                @if (!$income->isActive())
                    <section class="dg-invoice-lines">
                        <h2 class="dg-invoice-lines-title">Cancellation Details</h2>
                        <div class="dg-invoice-field-list">
                            <div class="dg-invoice-field-row">
                                <span class="dg-invoice-field-label">Cancelled Date</span>
                                <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                <span class="dg-invoice-field-value">{{ $income->cancelled_date?->format('d-m-Y') ?? '-' }}</span>
                            </div>
                            <div class="dg-invoice-field-row">
                                <span class="dg-invoice-field-label">Cancelled By</span>
                                <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                <span class="dg-invoice-field-value">{{ $income->cancelledByUser->name ?? '-' }}</span>
                            </div>
                            <div class="dg-invoice-field-row">
                                <span class="dg-invoice-field-label">Cancel Reason</span>
                                <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                <span class="dg-invoice-field-value">{{ $income->cancel_reason ?? '-' }}</span>
                            </div>
                        </div>
                    </section>
                @endif

                <section class="dg-invoice-lines">
                    <h2 class="dg-invoice-lines-title">Audit Information</h2>
                    <div class="dg-invoice-field-list">
                        <div class="dg-invoice-field-row">
                            <span class="dg-invoice-field-label">Created By</span>
                            <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                            <span class="dg-invoice-field-value">{{ $income->createdBy->name ?? '-' }}</span>
                        </div>
                        <div class="dg-invoice-field-row">
                            <span class="dg-invoice-field-label">Created At</span>
                            <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                            <span class="dg-invoice-field-value">{{ $income->created_at?->format('d-m-Y H:i') ?? '-' }}</span>
                        </div>
                        @if ($income->updated_by)
                            <div class="dg-invoice-field-row">
                                <span class="dg-invoice-field-label">Updated By</span>
                                <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                <span class="dg-invoice-field-value">{{ $income->updatedByUser->name ?? '-' }}</span>
                            </div>
                            <div class="dg-invoice-field-row">
                                <span class="dg-invoice-field-label">Updated At</span>
                                <span class="dg-invoice-field-sep" aria-hidden="true">:</span>
                                <span class="dg-invoice-field-value">{{ $income->updated_at?->format('d-m-Y H:i') ?? '-' }}</span>
                            </div>
                        @endif
                    </div>
                </section>

                <div class="row g-2 mt-3 pt-2 border-top">
                    <div class="col-4 text-center">
                        <div class="dg-signature-line"></div>
                        <div class="dg-signature-label">Prepared By</div>
                        <div class="small fw-bold">{{ $income->createdBy->name ?? auth()->user()->name }}</div>
                    </div>
                    <div class="col-4 text-center">
                        <div class="dg-signature-line"></div>
                        <div class="dg-signature-label">Checked By</div>
                    </div>
                    <div class="col-4 text-center">
                        <div class="dg-signature-line"></div>
                        <div class="dg-signature-label">Approved By</div>
                    </div>
                </div>

            </article>

        </div>
    </main>

</div>

@if ($errors->has('cancel_date') || $errors->has('cancel_reason'))
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var modalEl = document.getElementById('dgIncomeCancelModal');

                if (modalEl) {
                    bootstrap.Modal.getOrCreateInstance(modalEl).show();
                }
            });
        </script>
    @endpush
@endif

@endsection
