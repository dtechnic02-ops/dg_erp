@extends('company.layout')

@section('title', 'Subscription')

@section('content')

<div class="dg-page">

    <header class="dg-toolbar">
        <div class="container-fluid">
            <div class="d-flex flex-nowrap align-items-center gap-2">
                <div class="flex-shrink-0">
                    <h1 class="h4 mb-0">Subscription Plan</h1>
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

            @if ($activeSubscription)
                <section class="dg-section">
                    <div class="dg-summary d-flex flex-row flex-wrap justify-content-start align-items-center gap-4 mb-0">
                        <div class="dg-summary-item mb-0 border-0 p-0">
                            <span>Current :</span>
                            <span class="fw-bold">{{ str_replace('_', ' ', ucfirst($activeSubscription->subscription_type)) }}@if($activeSubscription->plan) — {{ $activeSubscription->plan->name }}@endif</span>
                        </div>
                        <div class="dg-summary-item mb-0 border-0 p-0">
                            <span>Staff Limit :</span>
                            <span class="fw-bold">{{ $activeSubscription->staff_limit }}</span>
                        </div>
                        <div class="dg-summary-item mb-0 border-0 p-0">
                            <span>Expiry :</span>
                            <span class="fw-bold">{{ $activeSubscription->expiry_date?->format('d M Y') ?? 'Lifetime' }}</span>
                        </div>
                    </div>
                </section>
            @endif

            <section class="dg-section">
                <article class="card dg-card">
                    <header class="card-header dg-card-header">
                        <h2 class="h6 mb-0">Submit Payment</h2>
                    </header>
                    <div class="card-body dg-card-body">
                        <form method="POST" action="{{ route('company.subscription.payment.store') }}" enctype="multipart/form-data" class="dg-form">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Plan</label>
                                    <select name="subscription_plan_id" class="form-select dg-select" required>
                                        @foreach ($plans as $plan)
                                            <option value="{{ $plan->id }}" @selected(old('subscription_plan_id') == $plan->id)>{{ $plan->name }} — {{ $plan->staff_limit }} Staff</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Billing Cycle</label>
                                    <select name="billing_cycle_id" class="form-select dg-select" required>
                                        @foreach ($billingCycles as $cycle)
                                            <option value="{{ $cycle->id }}" @selected(old('billing_cycle_id') == $cycle->id)>{{ $cycle->name }}@if($cycle->duration_days) ({{ $cycle->duration_days }} days)@endif</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Payment Screenshot</label>
                                    <input type="file" name="screenshot" class="form-control dg-input" accept="image/*" required>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary dg-btn">Submit Payment</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </article>
            </section>

            <section class="dg-section">
                <article class="card dg-card">
                    <header class="card-header dg-card-header">
                        <h2 class="h6 mb-0">Available Plans</h2>
                    </header>
                    <div class="card-body dg-card-body p-0">
                        <div class="table-responsive">
                            <table class="table dg-table mb-0">
                                <thead class="dg-head">
                                    <tr>
                                        <th>Plan</th>
                                        <th>Staff Limit</th>
                                        <th>Billing Options</th>
                                    </tr>
                                </thead>
                                <tbody class="dg-body">
                                    @foreach ($plans as $plan)
                                        <tr class="dg-row">
                                            <td>{{ $plan->name }}</td>
                                            <td>{{ $plan->staff_limit }}</td>
                                            <td>
                                                @forelse ($plan->billingOptions as $option)
                                                    {{ $option->billingCycle->name ?? '-' }} — {{ number_format($option->price, 2) }} {{ $option->currency_code }}@if(!$loop->last), @endif
                                                @empty
                                                    No billing options
                                                @endforelse
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </article>
            </section>

        </div>
    </main>
</div>
@endsection
