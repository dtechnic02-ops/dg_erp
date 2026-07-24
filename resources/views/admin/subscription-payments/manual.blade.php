@extends('admin.layout')

@section('title', 'Manual Subscription Payment')

@section('content')

<div class="dg-page">

    <header class="dg-toolbar">
        <div class="container-fluid">
            <div class="d-flex flex-nowrap align-items-center gap-2">
                <div class="flex-shrink-0">
                    <h1 class="h4 mb-0">Manual Subscription Payment</h1>
                </div>
                <div class="flex-fill d-flex justify-content-end">
                    <a href="{{ route('admin.subscription-payments.index') }}" class="btn btn-outline-secondary dg-btn">Back to Payments</a>
                </div>
            </div>
        </div>
    </header>

    <main class="dg-container">
        <div class="container-fluid">

            @if (session('success'))
                <div class="alert alert-success dg-alert" role="alert">{{ session('success') }}</div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger dg-alert" role="alert">{{ session('error') }}</div>
            @endif

            <section class="dg-section">
                <article class="card dg-card">
                    <header class="card-header dg-card-header">
                        <h2 class="h6 mb-0">Payment Details</h2>
                    </header>
                    <div class="card-body dg-card-body">
                        <form method="POST" action="{{ route('admin.subscription-payments.manual.store') }}" enctype="multipart/form-data" class="dg-form">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Company</label>
                                    <select name="company_id" class="form-select dg-select" required>
                                        @foreach ($companies as $company)
                                            <option value="{{ $company->id }}" @selected(old('company_id') == $company->id)>{{ $company->company_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Plan</label>
                                    <select name="subscription_plan_id" class="form-select dg-select" required>
                                        @foreach ($plans as $plan)
                                            <option value="{{ $plan->id }}" @selected(old('subscription_plan_id') == $plan->id)>{{ $plan->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Billing Cycle</label>
                                    <select name="billing_cycle_id" class="form-select dg-select" required>
                                        @foreach ($billingCycles as $cycle)
                                            <option value="{{ $cycle->id }}" @selected(old('billing_cycle_id') == $cycle->id)>{{ $cycle->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Action Type</label>
                                    <select name="action_type" class="form-select dg-select">
                                        <option value="assign" @selected(old('action_type', 'assign') === 'assign')>Assign</option>
                                        <option value="renew" @selected(old('action_type') === 'renew')>Renew</option>
                                        <option value="upgrade" @selected(old('action_type') === 'upgrade')>Upgrade</option>
                                        <option value="downgrade" @selected(old('action_type') === 'downgrade')>Downgrade</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Amount</label>
                                    <input type="number" step="0.01" name="amount" class="form-control dg-input" value="{{ old('amount') }}" required min="0">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Payment Method</label>
                                    <input type="text" name="payment_method" class="form-control dg-input" value="{{ old('payment_method', 'manual') }}" required maxlength="50">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Proof</label>
                                    <input type="file" name="proof" class="form-control dg-input" accept="image/*">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Notes</label>
                                    <textarea name="notes" class="form-control dg-textarea" maxlength="1000">{{ old('notes') }}</textarea>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary dg-btn">Save Payment</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </article>
            </section>

        </div>
    </main>
</div>
@endsection
