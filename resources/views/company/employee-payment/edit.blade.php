@extends('company.layout')

@section('content')

@php
    $salarySheet = $employeePayment->salarySheet;
    $maxAllowed = $salarySheet
        ? max(0, round((float) $salarySheet->net_salary - ((float) $salarySheet->paid_amount - (float) $employeePayment->amount), 2))
        : (float) $employeePayment->amount;
@endphp

<div class="dg-page">

    <header class="dg-toolbar">
        <div class="container-fluid">
            <div class="row align-items-center g-2">
                <div class="col">
                    <h1 class="h4 mb-0">Edit Salary Payment</h1>
                    <p class="text-muted small mb-0">{{ $employeePayment->voucher_no }}</p>
                </div>
                <div class="col-auto">
                    <a href="{{ route('company.employee-payment.show', $employeePayment->id) }}" class="btn btn-outline-secondary dg-btn">Back</a>
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

            <form method="POST" action="{{ route('company.employee-payment.update', $employeePayment->id) }}" enctype="multipart/form-data">
                @csrf

                <section class="dg-section">
                    <article class="card dg-card">
                        <header class="card-header dg-card-header">
                            <h2 class="h6 mb-0">Payment Details</h2>
                        </header>
                        <div class="card-body dg-card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Voucher No</label>
                                    <input type="text" value="{{ $employeePayment->voucher_no }}" class="form-control dg-input" readonly>
                                </div>

                                <div class="col-md-4">
                                    <label for="payment_date" class="form-label">Payment Date *</label>
                                    <input type="date" name="payment_date" id="payment_date" value="{{ old('payment_date', $employeePayment->payment_date?->format('Y-m-d')) }}" class="form-control dg-input" required>
                                </div>

                                <div class="col-md-4">
                                    <label for="account_id" class="form-label">Payment Account *</label>
                                    <select name="account_id" id="account_id" class="form-select dg-select" required>
                                        @foreach ($accounts as $account)
                                            <option value="{{ $account->id }}" @selected(old('account_id', $employeePayment->account_id) == $account->id)>
                                                {{ $account->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label for="amount" class="form-label">Payment Amount *</label>
                                    <input type="number" step="0.01" name="amount" id="amount" value="{{ old('amount', $employeePayment->amount) }}" max="{{ $maxAllowed }}" class="form-control dg-input" required>
                                    <div class="form-text">Maximum allowed: {{ number_format($maxAllowed, 2) }}</div>
                                </div>

                                <div class="col-md-4">
                                    <label for="attachment" class="form-label">Attachment</label>
                                    <input type="file" name="attachment" id="attachment" class="form-control dg-input">
                                </div>

                                <div class="col-12">
                                    <label for="note" class="form-label">Note</label>
                                    <textarea name="note" id="note" rows="3" class="form-control dg-input">{{ old('note', $employeePayment->note) }}</textarea>
                                </div>
                            </div>
                        </div>
                        <footer class="card-footer dg-card-footer">
                            <button type="submit" class="btn btn-primary dg-btn">Update Payment</button>
                        </footer>
                    </article>
                </section>
            </form>

        </div>
    </main>

</div>

@endsection
