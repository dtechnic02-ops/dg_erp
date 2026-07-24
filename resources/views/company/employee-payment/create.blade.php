@extends('company.layout')

@section('content')

@php
    $remainingDue = $salarySheet ? (float) $salarySheet->due_amount : 0;
@endphp

<div class="dg-page">

    <header class="dg-toolbar">
        <div class="container-fluid">
            <div class="row align-items-center g-2">
                <div class="col">
                    <h1 class="h4 mb-0">Create Salary Payment</h1>
                    <p class="text-muted small mb-0">Pay employee salary against a salary sheet</p>
                </div>
                <div class="col-auto">
                    @if ($salarySheet)
                        <a href="{{ route('company.salary-sheets.show', $salarySheet->id) }}" class="btn btn-outline-secondary dg-btn">Back to Salary Sheet</a>
                    @else
                        <a href="{{ route('company.employee-payment.index') }}" class="btn btn-outline-secondary dg-btn">Back</a>
                    @endif
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
                <div class="alert alert-danger dg-alert" role="alert">{{ session('error') }}</div>
            @endif

            @if (!$salarySheet)
                <div class="alert alert-warning dg-alert" role="alert">
                    Open this page from a Salary Sheet to create a linked payment.
                </div>
            @else
                <section class="dg-section">
                    <article class="card dg-card mb-3">
                        <header class="card-header dg-card-header">
                            <h2 class="h6 mb-0">Salary Sheet Summary</h2>
                        </header>
                        <div class="card-body dg-card-body">
                            <div class="row g-3">
                                <div class="col-md-3"><strong>Employee:</strong> {{ $salarySheet->employee->full_name }}</div>
                                <div class="col-md-3"><strong>Month:</strong> {{ $salarySheet->salary_month }}</div>
                                <div class="col-md-2"><strong>Net:</strong> {{ number_format($salarySheet->net_salary, 2) }}</div>
                                <div class="col-md-2"><strong>Paid:</strong> {{ number_format($salarySheet->paid_amount, 2) }}</div>
                                <div class="col-md-2"><strong>Due:</strong> {{ number_format($salarySheet->due_amount, 2) }}</div>
                            </div>
                        </div>
                    </article>
                </section>

                <form method="POST" action="{{ route('company.employee-payment.store') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="salary_sheet_id" value="{{ $salarySheet->id }}">

                    <section class="dg-section">
                        <article class="card dg-card">
                            <header class="card-header dg-card-header">
                                <h2 class="h6 mb-0">Payment Details</h2>
                            </header>
                            <div class="card-body dg-card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="voucher_no_display" class="form-label">Voucher No</label>
                                        <input type="text" id="voucher_no_display" value="{{ $voucherNo }}" class="form-control dg-input" readonly>
                                        <input type="hidden" name="voucher_no" value="{{ $voucherNo }}">
                                    </div>

                                    <div class="col-md-4">
                                        <label for="payment_date" class="form-label">Payment Date *</label>
                                        <input type="date" name="payment_date" id="payment_date" value="{{ old('payment_date', date('Y-m-d')) }}" class="form-control dg-input" required>
                                    </div>

                                    <div class="col-md-4">
                                        <label for="account_id" class="form-label">Payment Account *</label>
                                        <select name="account_id" id="account_id" class="form-select dg-select" required>
                                            <option value="">Select Account</option>
                                            @foreach ($accounts as $account)
                                                <option value="{{ $account->id }}" @selected(old('account_id') == $account->id)>
                                                    {{ $account->account_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-4">
                                        <label for="amount" class="form-label">Payment Amount *</label>
                                        <input type="number" step="0.01" name="amount" id="amount" value="{{ old('amount', $remainingDue) }}" max="{{ $remainingDue }}" class="form-control dg-input" required>
                                        <div class="form-text">Maximum allowed: {{ number_format($remainingDue, 2) }}</div>
                                    </div>

                                    <div class="col-md-4">
                                        <label for="attachment" class="form-label">Attachment</label>
                                        <input type="file" name="attachment" id="attachment" class="form-control dg-input">
                                    </div>

                                    <div class="col-12">
                                        <label for="note" class="form-label">Note</label>
                                        <textarea name="note" id="note" rows="3" class="form-control dg-input">{{ old('note') }}</textarea>
                                    </div>
                                </div>
                            </div>
                            <footer class="card-footer dg-card-footer">
                                <button type="submit" class="btn btn-primary dg-btn">Save Payment</button>
                            </footer>
                        </article>
                    </section>
                </form>
            @endif

        </div>
    </main>

</div>

@endsection
