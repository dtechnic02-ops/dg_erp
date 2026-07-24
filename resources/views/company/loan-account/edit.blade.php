@extends('company.layout')

@section('title', 'Edit Loan')

@section('content')

@php
    $canView = userCan('view_loan_account');
@endphp

<div class="dg-page">

    <header class="dg-toolbar">
        <div class="container-fluid">
            <div class="d-flex flex-nowrap align-items-center gap-2">
                <div class="flex-shrink-0">
                    <h1 class="h4 mb-0">Edit Loan</h1>
                </div>
                <div class="flex-fill d-flex justify-content-end align-items-center gap-2 flex-wrap">
                    <nav class="btn-group flex-wrap" aria-label="Loan edit toolbar">
                        @if ($canView)
                            <a href="{{ route('company.loan-account.show', $loan->id) }}" class="btn btn-outline-secondary dg-btn">Back</a>
                            <a href="{{ route('company.loan-account.index') }}" class="btn btn-outline-secondary dg-btn">Loan List</a>
                        @endif
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

            <form
                method="POST"
                action="{{ route('company.loan-account.update', $loan->id) }}"
                enctype="multipart/form-data"
                class="dg-form">
                @csrf

                <section class="dg-section">
                    <article class="card dg-card">
                        <header class="card-header dg-card-header">
                            <h2 class="h6 mb-0">Loan Information</h2>
                        </header>

                        <div class="card-body dg-card-body">
                            <div class="row g-3">
                                <div class="col-lg-4 col-md-6">
                                    <label class="form-label dg-label">Loan No</label>
                                    <input type="text" class="form-control dg-input" value="{{ $loan->loan_no }}" readonly>
                                </div>

                                <div class="col-lg-4 col-md-6">
                                    <label for="loan_name" class="form-label dg-label">Loan Name <span class="text-danger">*</span></label>
                                    <input type="text" name="loan_name" id="loan_name" class="form-control dg-input" value="{{ old('loan_name', $loan->loan_name) }}" required>
                                </div>

                                <div class="col-lg-4 col-md-6">
                                    <label class="form-label dg-label">Loan Type</label>
                                    <input type="text" class="form-control dg-input" value="{{ ucfirst($loan->loan_type) }}" readonly>
                                </div>

                                <div class="col-lg-4 col-md-6">
                                    <label class="form-label dg-label">Principal Amount</label>
                                    <input type="text" class="form-control dg-input" value="{{ number_format($loan->principal_amount, 2) }}" readonly>
                                </div>

                                <div class="col-lg-4 col-md-6">
                                    <label class="form-label dg-label">Remaining Principal</label>
                                    <input type="text" class="form-control dg-input" value="{{ number_format($loan->remaining_principal, 2) }}" readonly>
                                </div>

                                <div class="col-lg-4 col-md-6">
                                    <label for="end_date" class="form-label dg-label">Due Date</label>
                                    <input
                                        type="date"
                                        name="end_date"
                                        id="end_date"
                                        class="form-control dg-input"
                                        value="{{ old('end_date', optional($loan->end_date)->format('Y-m-d')) }}">
                                </div>

                                <div class="col-lg-4 col-md-6">
                                    <label class="form-label dg-label">Status</label>
                                    <input type="text" class="form-control dg-input" value="Active" readonly>
                                </div>

                                <div class="col-lg-8 col-md-6">
                                    <label for="attachment" class="form-label dg-label">Attachment</label>
                                    <input type="file" name="attachment" id="attachment" accept=".jpg,.jpeg,.png,.pdf" class="form-control dg-input">
                                    @if ($loan->attachment)
                                        <small class="text-muted">
                                            Current:
                                            <a href="{{ asset($loan->attachment) }}" target="_blank" rel="noopener noreferrer">View Attachment</a>
                                        </small>
                                    @endif
                                </div>

                                <div class="col-12">
                                    <label for="note" class="form-label dg-label">Note</label>
                                    <textarea name="note" id="note" rows="3" class="form-control dg-input dg-textarea">{{ old('note', $loan->note) }}</textarea>
                                </div>
                            </div>
                        </div>
                    </article>
                </section>

                <section class="dg-section">
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="submit" class="btn btn-primary dg-btn">Update Loan</button>
                        <a href="{{ route('company.loan-account.show', $loan->id) }}" class="btn btn-outline-secondary dg-btn">Cancel</a>
                    </div>
                </section>

            </form>

        </div>
    </main>

</div>

@endsection
